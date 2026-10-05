<?php
/**
 * MODELO (entidad): Autor — periodista que firma los artículos.
 */
class Autor
{
    private $id;
    private $nombre;
    private $email;

    public function __construct($id = null, $nombre = "", $email = "")
    {
        $this->id     = $id;
        $this->nombre = $nombre;
        $this->email  = $email;
    }

    public static function desdeFila(array $f)
    {
        return new Autor((int) $f["id_autor"], $f["nombre"], $f["email"]);
    }

    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getEmail() { return $this->email; }
}
