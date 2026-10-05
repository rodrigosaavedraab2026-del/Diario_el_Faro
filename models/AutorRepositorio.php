<?php
require_once __DIR__ . "/Repositorio.php";
require_once __DIR__ . "/Autor.php";

/**
 * MODELO (acceso a datos): AutorRepositorio — lectura de la tabla `autor`
 * mediante sentencias preparadas de la clase única Conexion.
 */
class AutorRepositorio extends Repositorio
{
    public function listarTodos()
    {
        $filas = $this->conexion->consultar("SELECT * FROM autor ORDER BY nombre");
        return array_map([Autor::class, "desdeFila"], $filas);
    }
}
