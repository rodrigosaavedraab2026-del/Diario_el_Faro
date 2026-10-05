<?php
/**
 * MODELO (entidad): Contacto
 * Representa un mensaje del formulario de contacto (tabla `contacto`).
 * La persistencia se delega a ContactoRepositorio (antes: archivo de texto).
 */
class Contacto
{
    const ASUNTOS = ["Enviar una noticia", "Comentario", "Publicidad", "Otro"];

    private $id;
    private $nombre;
    private $email;
    private $asunto;
    private $mensaje;
    private $estado;
    private $fechaEnvio;
    private $idUsuario;   // lector registrado que lo envió (lo completa un trigger)

    public function __construct($nombre = "", $email = "", $asunto = "Enviar una noticia", $mensaje = "")
    {
        $this->nombre     = $nombre;
        $this->email      = $email;
        $this->asunto     = $asunto;
        $this->mensaje    = $mensaje;
        $this->estado     = "nuevo";
        $this->fechaEnvio = date("Y-m-d H:i:s");
    }

    public function setId($v) { $this->id = (int) $v; }
    public function setNombre($v) { $this->nombre = $v; }
    public function setEmail($v) { $this->email = $v; }
    public function setAsunto($v) { $this->asunto = $v; }
    public function setMensaje($v) { $this->mensaje = $v; }

    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getEmail() { return $this->email; }
    public function getAsunto() { return $this->asunto; }
    public function getMensaje() { return $this->mensaje; }
    public function getEstado() { return $this->estado; }
    public function getFechaEnvio() { return $this->fechaEnvio; }
    public function getIdUsuario() { return $this->idUsuario; }
    public function setIdUsuario($v) { $this->idUsuario = $v === null ? null : (int) $v; }
}
