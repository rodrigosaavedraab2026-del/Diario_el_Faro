<?php
require_once __DIR__ . "/Localidad.php";
/**
 * MODELO (entidad): Usuario (Lector)
 * Representa una fila de la tabla `usuario`. Aplica encapsulamiento
 * (atributos privados) y cifrado de contraseña con password_hash().
 */
class Usuario
{
    private $id;
    private $nombre;
    private $apellido;
    private $email;
    private $passwordHash;  // se almacena SIEMPRE cifrada (hash bcrypt)
    private $plan;          // gratuito | mensual | anual
    private $fechaRegistro;
    private $localidad;     // objeto Localidad asociado (composición)
    private $mensajes = 0;  // mensajes de contacto enviados (tabla contacto)

    public function __construct($nombre = "", $apellido = "", $email = "")
    {
        $this->nombre        = $nombre;
        $this->apellido      = $apellido;
        $this->email         = $email;
        $this->plan          = "gratuito";
        $this->fechaRegistro = date("Y-m-d H:i:s");
        $this->localidad     = null;
    }

    /** Crea un Usuario desde una fila de la consulta (sin el hash). */
    public static function desdeFila(array $f)
    {
        $u = new Usuario($f["nombre"], $f["apellido"], $f["email"]);
        $u->id            = (int) $f["id_usuario"];
        $u->plan          = $f["plan"];
        $u->fechaRegistro = $f["fecha_registro"];
        $u->mensajes      = (int) ($f["mensajes"] ?? 0);
        if (!empty($f["ciudad"])) {
            $u->localidad = new Localidad($f["pais"], $f["ciudad"], (int) $f["id_localidad"]);
        }
        return $u;
    }

    // SET
    public function setId($v) { $this->id = (int) $v; }
    public function setNombre($v) { $this->nombre = $v; }
    public function setApellido($v) { $this->apellido = $v; }
    public function setEmail($v) { $this->email = $v; }
    public function setPlan($v) { $this->plan = $v; }
    public function setLocalidad(Localidad $localidad) { $this->localidad = $localidad; }

    /** Cifra la contraseña: nunca se guarda en texto plano. */
    public function setPassword($passwordPlano)
    {
        $this->passwordHash = password_hash($passwordPlano, PASSWORD_DEFAULT);
    }

    /** Verifica una contraseña ingresada contra el hash almacenado. */
    public function verificarPassword($passwordPlano)
    {
        return password_verify($passwordPlano, (string) $this->passwordHash);
    }

    // GET
    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getApellido() { return $this->apellido; }
    public function getNombreCompleto() { return trim($this->nombre . " " . $this->apellido); }
    public function getEmail() { return $this->email; }
    public function getPasswordHash() { return $this->passwordHash; }
    public function getPlan() { return $this->plan; }
    public function getFechaRegistro() { return $this->fechaRegistro; }
    public function getLocalidad() { return $this->localidad; }
    public function getMensajes() { return $this->mensajes; }
}
