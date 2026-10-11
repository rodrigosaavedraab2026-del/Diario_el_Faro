<?php
require_once __DIR__ . "/Repositorio.php";
require_once __DIR__ . "/Seccion.php";

/**
 * MODELO (acceso a datos): SeccionRepositorio — lectura de la tabla `seccion`
 * mediante sentencias preparadas de la clase única Conexion.
 */
class SeccionRepositorio extends Repositorio
{
    public function listarTodas()
    {
        $filas = $this->conexion->consultar("SELECT * FROM seccion ORDER BY id_seccion");
        return array_map([Seccion::class, "desdeFila"], $filas);
    }

    public function buscarPorSlug($slug)
    {
        $fila = $this->conexion->consultarFila("SELECT * FROM seccion WHERE slug = :slug", [":slug" => $slug]);
        return $fila ? Seccion::desdeFila($fila) : null;
    }

    /** Secciones con su cantidad de artículos: [ [Seccion, total], ... ] (menú y portada). */
    public function listarConTotales()
    {
        $filas = $this->conexion->consultar(
            "SELECT s.*, COUNT(a.id_articulo) AS total
               FROM seccion s LEFT JOIN articulo a ON a.id_seccion = s.id_seccion
              GROUP BY s.id_seccion ORDER BY s.id_seccion"
        );
        return array_map(fn($f) => [Seccion::desdeFila($f), (int) $f["total"]], $filas);
    }
}
