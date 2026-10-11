<?php
require_once __DIR__ . "/Repositorio.php";
require_once __DIR__ . "/Articulo.php";

/**
 * MODELO (acceso a datos): ArticuloRepositorio
 * Lectura y registro de artículos a través de la clase única Conexion.
 *
 * Procedimientos almacenados utilizados (database/el_faro.sql):
 *   sp_articulo_listar     CONSULTA  página de artículos (LIMIT/OFFSET)
 *   sp_articulo_contar     CONSULTA  total de artículos (parámetro OUT)
 *   sp_articulo_buscar     CONSULTA  un artículo por id
 *   sp_articulo_registrar  INSERCIÓN nuevo artículo (transacción + OUT p_id)
 */
class ArticuloRepositorio extends Repositorio
{
    /** Cuenta artículos (opcionalmente de una sección y/o excluyendo el destacado). */
    public function contar($slugSeccion = null, $excluirDestacado = false)
    {
        return (int) $this->conProcedimiento(
            fn() => $this->conexion->llamarProcedimiento(
                "sp_articulo_contar",
                [$slugSeccion, (bool) $excluirDestacado],
                ["p_total"]
            )["salida"]["p_total"],
            function () use ($slugSeccion, $excluirDestacado) {
                [$where, $params] = $this->filtros($slugSeccion, $excluirDestacado);
                return $this->conexion->consultarValor("SELECT COUNT(*) FROM vw_articulo_detalle $where", $params);
            }
        );
    }

    /** Devuelve una página de artículos, del más reciente al más antiguo. */
    public function listarPagina($limite, $offset, $slugSeccion = null, $excluirDestacado = false)
    {
        $filas = $this->conProcedimiento(
            fn() => $this->conexion->llamarProcedimiento(
                "sp_articulo_listar",
                [$slugSeccion, (bool) $excluirDestacado, (int) $limite, (int) $offset]
            )["filas"],
            function () use ($limite, $offset, $slugSeccion, $excluirDestacado) {
                [$where, $params] = $this->filtros($slugSeccion, $excluirDestacado);
                $params[":limite"] = (int) $limite;
                $params[":offset"] = (int) $offset;
                return $this->conexion->consultar(
                    "SELECT * FROM vw_articulo_detalle $where
                     ORDER BY fecha_publicacion DESC, id_articulo DESC
                     LIMIT :limite OFFSET :offset",
                    $params
                );
            }
        );
        return array_map([Articulo::class, "desdeFila"], $filas);
    }

    /** Artículo destacado de la portada (sentencia preparada). */
    public function obtenerDestacado()
    {
        $fila = $this->conexion->consultarFila(
            "SELECT * FROM vw_articulo_detalle WHERE destacado = :destacado
             ORDER BY fecha_publicacion DESC LIMIT 1",
            [":destacado" => true]
        );
        return $fila ? Articulo::desdeFila($fila) : null;
    }

    /** Un artículo por su id. */
    public function buscarPorId($id)
    {
        $id = (int) $id;
        $fila = $this->conProcedimiento(
            fn() => $this->conexion->llamarProcedimiento("sp_articulo_buscar", [$id])["filas"][0] ?? null,
            fn() => $this->conexion->consultarFila(
                "SELECT * FROM vw_articulo_detalle WHERE id_articulo = :id", [":id" => $id]
            )
        );
        return $fila ? Articulo::desdeFila($fila) : null;
    }

    /**
     * Registra un artículo. El procedimiento sp_articulo_registrar trabaja
     * dentro de una TRANSACCIÓN: si el nuevo artículo es destacado, primero
     * quita la marca a los anteriores; ambas operaciones se confirman o se
     * deshacen juntas. Devuelve el id generado (parámetro OUT p_id).
     */
    public function registrar(Articulo $a)
    {
        $datos = [
            $a->getTitulo(), $a->getResumen(), $a->getCuerpo(), $a->getEtiqueta(),
            $a->getImagen(), (bool) $a->esDestacado(), (int) $a->getIdSeccion(), (int) $a->getIdAutor(),
        ];

        $id = $this->conProcedimiento(
            fn() => $this->conexion->llamarProcedimiento("sp_articulo_registrar", $datos, ["p_id"])["salida"]["p_id"],
            fn() => $this->registrarConSentencias($datos)
        );
        $a->setId($id);
        return $a->getId();
    }

    /** Equivalente con sentencias preparadas (solo para hosting sin rutinas). */
    private function registrarConSentencias(array $d)
    {
        $this->conexion->iniciarTransaccion();
        try {
            if ($d[5] === true) {
                $this->conexion->ejecutar("UPDATE articulo SET destacado = :no WHERE destacado = :si", [":no" => false, ":si" => true]);
            }
            $id = $this->conexion->insertar(
                "INSERT INTO articulo (titulo, resumen, cuerpo, etiqueta, imagen, destacado, id_seccion, id_autor)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                $d
            );
            $this->conexion->confirmar();
            return $id;
        } catch (Throwable $ex) {
            $this->conexion->revertir();
            throw $ex;
        }
    }

    /* ------------------------------------------------------------------
     * SEMANA 9 (usabilidad): búsqueda y artículos relacionados.
     * Sentencias preparadas: el texto buscado nunca se concatena al SQL.
     * ------------------------------------------------------------------ */

    /** Cuenta los artículos cuyo título, resumen o etiqueta contienen $texto. */
    public function contarBusqueda($texto)
    {
        return (int) $this->conexion->consultarValor(
            "SELECT COUNT(*) FROM vw_articulo_detalle
              WHERE titulo LIKE :t1 OR resumen LIKE :t2 OR etiqueta LIKE :t3",
            $this->parametrosBusqueda($texto)
        );
    }

    /** Página de resultados de búsqueda, del más reciente al más antiguo. */
    public function buscarTexto($texto, $limite, $offset)
    {
        $params = $this->parametrosBusqueda($texto);
        $params[":limite"] = (int) $limite;
        $params[":offset"] = (int) $offset;
        $filas = $this->conexion->consultar(
            "SELECT * FROM vw_articulo_detalle
              WHERE titulo LIKE :t1 OR resumen LIKE :t2 OR etiqueta LIKE :t3
              ORDER BY fecha_publicacion DESC, id_articulo DESC
              LIMIT :limite OFFSET :offset",
            $params
        );
        return array_map([Articulo::class, "desdeFila"], $filas);
    }

    /** Otros artículos de la misma sección (para "Más de ..." en la nota). */
    public function listarRelacionados($slugSeccion, $excluirId, $limite = 3)
    {
        $filas = $this->conexion->consultar(
            "SELECT * FROM vw_articulo_detalle
              WHERE seccion_slug = :slug AND id_articulo <> :id
              ORDER BY fecha_publicacion DESC LIMIT :limite",
            [":slug" => $slugSeccion, ":id" => (int) $excluirId, ":limite" => (int) $limite]
        );
        return array_map([Articulo::class, "desdeFila"], $filas);
    }

    /** Escapa los comodines de LIKE (% y _) para buscar el texto literal. */
    private function parametrosBusqueda($texto)
    {
        $patron = "%" . addcslashes((string) $texto, "%_\\") . "%";
        return [":t1" => $patron, ":t2" => $patron, ":t3" => $patron];
    }

    /** Condiciones WHERE con marcadores para la versión con sentencias preparadas. */
    private function filtros($slugSeccion, $excluirDestacado)
    {
        $cond = [];
        $params = [];
        if ($slugSeccion !== null) {
            $cond[] = "seccion_slug = :slug";
            $params[":slug"] = $slugSeccion;
        }
        if ($excluirDestacado) {
            $cond[] = "destacado = :no_destacado";
            $params[":no_destacado"] = false;
        }
        return [$cond ? "WHERE " . implode(" AND ", $cond) : "", $params];
    }
}
