<?php
/**
 * MODELO (entidad): SitioAsociado — vínculo a un sitio externo relacionado
 * con el diario (tabla `sitio_asociado`, semana 9).
 */
class SitioAsociado
{
    const ICONOS = [
        "Gobierno"  => "fa-landmark",
        "Cultura"   => "fa-masks-theater",
        "Deporte"   => "fa-futbol",
        "Economía"  => "fa-briefcase",
        "Servicios" => "fa-circle-info",
    ];

    private $id;
    private $nombre;
    private $url;
    private $descripcion;
    private $categoria;

    public static function desdeFila(array $f)
    {
        $s = new SitioAsociado();
        $s->id          = (int) $f["id_sitio"];
        $s->nombre      = $f["nombre"];
        $s->url         = $f["url"];
        $s->descripcion = $f["descripcion"];
        $s->categoria   = $f["categoria"];
        return $s;
    }

    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getUrl() { return $this->url; }
    public function getDescripcion() { return $this->descripcion; }
    public function getCategoria() { return $this->categoria; }
    public function getIcono() { return self::ICONOS[$this->categoria] ?? "fa-link"; }

    /** Dominio legible (sin https:// ni www.) para mostrar junto al nombre. */
    public function getDominio()
    {
        $host = (string) parse_url($this->url, PHP_URL_HOST);
        return preg_replace("/^www\./", "", $host);
    }
}
