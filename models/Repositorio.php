<?php
require_once __DIR__ . "/Conexion.php";

/**
 * MODELO (acceso a datos): Repositorio — CLASE BASE DE LAS ENTIDADES
 *
 * Requisito 6 de la actividad: las clases de cada entidad (Articulo,
 * Seccion, Autor, Usuario, Contacto) acceden a la base de datos mediante
 * un repositorio que hereda de esta clase. Así todas comparten la misma
 * instancia de Conexion, sin conocer los datos de conexión ni crear su
 * propio objeto PDO.
 */
abstract class Repositorio
{
    /** @var Conexion conexión compartida (instancia única) */
    protected $conexion;

    /** Se puede inyectar otra Conexion (útil en pruebas); por defecto, la única. */
    public function __construct(?Conexion $conexion = null)
    {
        $this->conexion = $conexion ?? Conexion::obtenerInstancia();
    }

    /**
     * Ejecuta la operación con el procedimiento almacenado y, solo si ese
     * procedimiento no existe en la base (hosting sin rutinas), ejecuta la
     * sentencia preparada equivalente. En XAMPP siempre se usa el
     * procedimiento almacenado.
     */
    protected function conProcedimiento(callable $procedimiento, callable $alternativa)
    {
        try {
            return $procedimiento();
        } catch (ProcedimientoNoDisponible $ex) {
            error_log("[El Faro] " . $ex->getMessage() . " Se usa la sentencia preparada equivalente.");
            return $alternativa();
        }
    }
}
