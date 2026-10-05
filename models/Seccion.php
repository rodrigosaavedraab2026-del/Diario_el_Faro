<?php
/**
 * MODELO (entidad): Seccion — Actualidad, Deporte, Negocios, Cultura.
 */
class Seccion
{
    private $id;
    private $nombre;
    private $slug;
    private $colorEtiqueta;

    public function __construct($id = null, $nombre = "", $slug = "", $colorEtiqueta = "is-link")
    {
        $this->id            = $id;
        $this->nombre        = $nombre;
        $this->slug          = $slug;
        $this->colorEtiqueta = $colorEtiqueta;
    }

    public static function desdeFila(array $f)
    {
        return new Seccion((int) $f["id_seccion"], $f["nombre"], $f["slug"], $f["color_etiqueta"]);
    }

    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getSlug() { return $this->slug; }
    public function getColorEtiqueta() { return $this->colorEtiqueta; }
}
