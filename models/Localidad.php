<?php
/**
 * MODELO (entidad): Localidad
 * País y ciudad del lector. En la base de datos es la tabla `localidad`,
 * relacionada 1:N con `usuario` (una localidad agrupa muchos usuarios).
 */
class Localidad
{
    private $id;
    private $pais;
    private $ciudad;

    public function __construct($pais = "", $ciudad = "", $id = null)
    {
        $this->pais   = $pais;
        $this->ciudad = $ciudad;
        $this->id     = $id;
    }

    public function setId($v) { $this->id = (int) $v; }
    public function setPais($v) { $this->pais = $v; }
    public function setCiudad($v) { $this->ciudad = $v; }
    public function getId() { return $this->id; }
    public function getPais() { return $this->pais; }
    public function getCiudad() { return $this->ciudad; }
}
