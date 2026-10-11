<?php
require_once __DIR__ . "/../core/PoliticaHtml.php";
/**
 * MODELO (entidad): Articulo
 * Representa una fila de la tabla `articulo` (más el nombre de su sección
 * y de su autor, obtenidos con la vista vw_articulo_detalle).
 */
class Articulo
{
    private $id;
    private $titulo;
    private $resumen;
    private $cuerpo;
    private $etiqueta;
    private $imagen;
    private $destacado;
    private $fechaPublicacion;
    private $idSeccion;
    private $idAutor;
    // Datos de solo lectura provenientes de las tablas relacionadas
    private $seccion;
    private $seccionSlug;
    private $tagColor;
    private $autor;

    public function __construct($titulo = "", $resumen = "", $cuerpo = "", $etiqueta = "",
                                $imagen = "destacada.jpg", $idSeccion = null, $idAutor = null)
    {
        $this->titulo    = $titulo;
        $this->resumen   = $resumen;
        $this->cuerpo    = $cuerpo;
        $this->etiqueta  = $etiqueta;
        $this->imagen    = $imagen;
        $this->idSeccion = $idSeccion;
        $this->idAutor   = $idAutor;
        $this->destacado = false;
    }

    /** Crea un objeto Articulo a partir de una fila devuelta por PDO. */
    public static function desdeFila(array $f)
    {
        $a = new Articulo($f["titulo"], $f["resumen"], $f["cuerpo"], $f["etiqueta"],
                          $f["imagen"], (int) $f["id_seccion"], (int) $f["id_autor"]);
        $a->id               = (int) $f["id_articulo"];
        $a->destacado        = (bool) $f["destacado"];
        $a->fechaPublicacion = $f["fecha_publicacion"];
        $a->seccion          = $f["seccion"] ?? "";
        $a->seccionSlug      = $f["seccion_slug"] ?? "";
        $a->tagColor         = $f["color_etiqueta"] ?? "is-link";
        $a->autor            = $f["autor"] ?? "";
        return $a;
    }

    // SET
    public function setId($v) { $this->id = (int) $v; }
    public function setTitulo($v) { $this->titulo = $v; }
    public function setResumen($v) { $this->resumen = $v; }
    public function setCuerpo($v) { $this->cuerpo = $v; }
    public function setEtiqueta($v) { $this->etiqueta = $v; }
    public function setImagen($v) { $this->imagen = $v; }
    public function setDestacado($v) { $this->destacado = (bool) $v; }
    public function setIdSeccion($v) { $this->idSeccion = (int) $v; }
    public function setIdAutor($v) { $this->idAutor = (int) $v; }

    // GET
    public function getId() { return $this->id; }
    public function getTitulo() { return $this->titulo; }
    public function getResumen() { return $this->resumen; }
    public function getCuerpo() { return $this->cuerpo; }
    public function getEtiqueta() { return $this->etiqueta; }
    public function getImagen() { return $this->imagen; }
    public function esDestacado() { return $this->destacado; }
    public function getFechaPublicacion() { return $this->fechaPublicacion; }
    public function getIdSeccion() { return $this->idSeccion; }
    public function getIdAutor() { return $this->idAutor; }
    public function getSeccion() { return $this->seccion; }
    public function getSeccionSlug() { return $this->seccionSlug; }
    public function getTagColor() { return $this->tagColor; }
    public function getAutor() { return $this->autor; }

    /** Párrafos del cuerpo (separados por línea en blanco) para la Vista. */
    public function getParrafos()
    {
        $partes = preg_split("/\R\s*\R/u", trim((string) $this->cuerpo));
        return array_values(array_filter(array_map("trim", $partes), "strlen"));
    }

    /**
     * Cuerpo listo para la Vista, SIEMPRE seguro:
     *  - texto plano (artículos antiguos): se escapa con htmlspecialchars y se arma en párrafos;
     *  - HTML enriquecido: se filtra con la lista blanca de PoliticaHtml (defensa en profundidad,
     *    aunque ya se filtró al publicar, por si el dato se modificó directamente en MySQL).
     */
    public function getCuerpoHtml()
    {
        if (PoliticaHtml::tieneHtml($this->cuerpo)) {
            return (new PoliticaHtml())->limpiar($this->cuerpo);
        }
        $html = "";
        foreach ($this->getParrafos() as $parrafo) {
            $html .= "<p>" . nl2br(htmlspecialchars($parrafo, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")) . "</p>\n";
        }
        return $html;
    }

    /** Tiempo estimado de lectura (200 palabras por minuto, mínimo 1). */
    public function getMinutosLectura()
    {
        $palabras = str_word_count(strip_tags((string) $this->cuerpo . " " . $this->resumen), 0, "áéíóúüñÁÉÍÓÚÜÑ");
        return max(1, (int) round($palabras / 200));
    }
}
