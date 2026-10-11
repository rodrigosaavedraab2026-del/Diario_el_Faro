<?php
/**
 * MODELO (entidad): Comentario — opinión de un lector sobre un artículo
 * (tabla `comentario`, semana 9). El correo se guarda pero nunca se publica.
 */
class Comentario
{
    /** Largos iguales a las columnas de MySQL (los usa también la Vista). */
    const LARGO_NOMBRE = [2, 60];
    const LARGO_TEXTO  = [10, 600];

    private $id;
    private $idArticulo;
    private $nombre;
    private $email;
    private $texto;
    private $fecha;

    public function __construct($idArticulo = null, $nombre = "", $email = "", $texto = "")
    {
        $this->idArticulo = $idArticulo;
        $this->nombre     = $nombre;
        $this->email      = $email;
        $this->texto      = $texto;
        $this->fecha      = date("Y-m-d H:i:s");
    }

    public static function desdeFila(array $f)
    {
        $c = new Comentario((int) $f["id_articulo"], $f["nombre"], "", $f["texto"]);
        $c->id    = (int) $f["id_comentario"];
        $c->fecha = $f["fecha_comentario"];
        return $c;
    }

    public function setId($v) { $this->id = (int) $v; }
    public function getId() { return $this->id; }
    public function getIdArticulo() { return $this->idArticulo; }
    public function getNombre() { return $this->nombre; }
    public function getEmail() { return $this->email; }
    public function getTexto() { return $this->texto; }
    public function getFecha() { return $this->fecha; }

    /** Iniciales para el avatar (máximo dos letras). */
    public function getIniciales()
    {
        $partes = preg_split("/\s+/u", trim((string) $this->nombre));
        $ini = "";
        foreach (array_slice($partes, 0, 2) as $p) {
            $ini .= mb_strtoupper(mb_substr($p, 0, 1, "UTF-8"), "UTF-8");
        }
        return $ini;
    }
}
