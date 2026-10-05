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
}
