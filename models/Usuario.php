<?php
require_once __DIR__ . "/Localidad.php";
/**
 * MODELO (entidad): Usuario (Lector)
 * Representa una fila de la tabla `usuario`. Aplica encapsulamiento
 * (atributos privados) y HASH de contraseña con password_hash() (no es cifrado: ver SEGURIDAD.md).
 */
class Usuario
{
    private $id;
    private $nombre;
    private $apellido;
    private $email;
    private $passwordHash;  // se almacena SIEMPRE como hash bcrypt (irreversible), nunca en texto plano
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

    /**
     * HASHING vs CIFRADO
     *  - Un hash (bcrypt) es de UNA VÍA: no existe una clave para "descifrarlo".
     *    Para comprobar una contraseña se calcula de nuevo con la misma sal y
     *    se compara (password_verify). Si alguien roba la tabla, no obtiene las claves.
     *  - El cifrado (por ejemplo AES) es REVERSIBLE con una clave: sirve para
     *    datos que hay que volver a leer, nunca para contraseñas.
     * password_hash() genera una sal aleatoria por usuario y la guarda dentro
     * del propio hash ($2y$12$<sal><hash>), junto con el algoritmo y el costo.
     */
    const ALGORITMO_HASH = PASSWORD_BCRYPT;
    const OPCIONES_HASH  = ["cost" => 12];   // 2^12 iteraciones: lento a propósito para frenar ataques

    /** Guarda el HASH de la contraseña: el texto plano nunca se almacena. */
    public function setPassword($passwordPlano)
    {
        $this->passwordHash = password_hash($passwordPlano, self::ALGORITMO_HASH, self::OPCIONES_HASH);
    }

    public function setPasswordHash($hash) { $this->passwordHash = $hash; }

    /** Verificación segura: password_verify compara en tiempo constante. */
    public function verificarPassword($passwordPlano)
    {
        return password_verify($passwordPlano, (string) $this->passwordHash);
    }

    /** true si el hash se creó con un algoritmo o costo anterior y conviene renovarlo. */
    public function necesitaRehash()
    {
        return password_needs_rehash((string) $this->passwordHash, self::ALGORITMO_HASH, self::OPCIONES_HASH);
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
