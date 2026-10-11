<?php
require_once __DIR__ . "/Repositorio.php";
require_once __DIR__ . "/SitioAsociado.php";

/**
 * MODELO (acceso a datos): SitioAsociadoRepositorio (semana 9)
 * Procedimiento almacenado: sp_sitio_listar (CONSULTA).
 */
class SitioAsociadoRepositorio extends Repositorio
{
    /** Sitios activos; con $slug, primero los de esa sección y luego los generales. */
    public function listar($slug = null, $limite = 12)
    {
        $filas = $this->conProcedimiento(
            fn() => $this->conexion->llamarProcedimiento("sp_sitio_listar", [$slug])["filas"],
            function () use ($slug) {
                return $this->conexion->consultar(
                    "SELECT sa.id_sitio, sa.nombre, sa.url, sa.descripcion, sa.categoria, s.slug AS seccion_slug
                       FROM sitio_asociado sa
                       LEFT JOIN seccion s ON s.id_seccion = sa.id_seccion
                      WHERE sa.activo = 1
                        AND (:slug1 IS NULL OR s.slug = :slug2 OR sa.id_seccion IS NULL)
                      ORDER BY (s.slug <=> :slug3) DESC, sa.orden, sa.nombre",
                    [":slug1" => $slug, ":slug2" => $slug, ":slug3" => $slug]
                );
            }
        );
        return array_map([SitioAsociado::class, "desdeFila"], array_slice($filas, 0, $limite));
    }
}
