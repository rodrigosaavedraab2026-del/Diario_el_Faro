<?php
require_once __DIR__ . "/Repositorio.php";
require_once __DIR__ . "/Comentario.php";

/**
 * MODELO (acceso a datos): ComentarioRepositorio (semana 9)
 *
 * Procedimientos almacenados utilizados:
 *   sp_comentario_listar     CONSULTA  comentarios publicados de un artículo
 *   sp_comentario_registrar  INSERCIÓN nuevo comentario (OUT p_id)
 */
class ComentarioRepositorio extends Repositorio
{
    /** @return Comentario[] */
    public function listarPorArticulo($idArticulo)
    {
        $idArticulo = (int) $idArticulo;
        $filas = $this->conProcedimiento(
            fn() => $this->conexion->llamarProcedimiento("sp_comentario_listar", [$idArticulo])["filas"],
            fn() => $this->conexion->consultar(
                "SELECT id_comentario, id_articulo, nombre, texto, fecha_comentario
                   FROM comentario
                  WHERE id_articulo = :id AND estado = :estado
                  ORDER BY fecha_comentario ASC, id_comentario ASC",
                [":id" => $idArticulo, ":estado" => "publicado"]
            )
        );
        return array_map([Comentario::class, "desdeFila"], $filas);
    }

    public function registrar(Comentario $c)
    {
        $datos = [(int) $c->getIdArticulo(), $c->getNombre(), $c->getEmail(), $c->getTexto()];
        $id = $this->conProcedimiento(
            fn() => $this->conexion->llamarProcedimiento("sp_comentario_registrar", $datos, ["p_id"])["salida"]["p_id"],
            fn() => $this->conexion->insertar(
                "INSERT INTO comentario (id_articulo, nombre, email, texto) VALUES (?, ?, ?, ?)",
                $datos
            )
        );
        $c->setId($id);
        return $c->getId();
    }
}
