<?php
require_once __DIR__ . "/Repositorio.php";
require_once __DIR__ . "/Contacto.php";

/**
 * MODELO (acceso a datos): ContactoRepositorio
 * Registro de los mensajes del formulario de contacto.
 *
 * Procedimiento almacenado utilizado:
 *   sp_contacto_registrar  INSERCIÓN mensaje + vínculo con el lector
 *                          (OUT p_id, OUT p_id_usuario)
 */
class ContactoRepositorio extends Repositorio
{
    public function registrar(Contacto $c)
    {
        $datos = [$c->getNombre(), $c->getEmail(), $c->getAsunto(), $c->getMensaje()];

        $salida = $this->conProcedimiento(
            fn() => $this->conexion->llamarProcedimiento(
                "sp_contacto_registrar", $datos, ["p_id", "p_id_usuario"]
            )["salida"],
            fn() => $this->registrarConSentencias($datos)
        );

        // El mensaje queda vinculado (llave foránea id_usuario) al lector
        // registrado que tenga el mismo correo, o en NULL si no existe.
        $c->setId($salida["p_id"]);
        $c->setIdUsuario($salida["p_id_usuario"] ?: null);
        return $c->getId();
    }

    /** Equivalente con sentencias preparadas (solo para hosting sin rutinas). */
    private function registrarConSentencias(array $d)
    {
        [$nombre, $email, $asunto, $mensaje] = $d;
        $id = $this->conexion->insertar(
            "INSERT INTO contacto (nombre, email, asunto, mensaje, id_usuario)
             VALUES (:nombre, :email, :asunto, :mensaje,
                     (SELECT u.id_usuario FROM usuario u WHERE u.email = :email_usuario LIMIT 1))",
            [":nombre" => $nombre, ":email" => $email, ":asunto" => $asunto,
             ":mensaje" => $mensaje, ":email_usuario" => $email]
        );
        $idUsuario = $this->conexion->consultarValor(
            "SELECT id_usuario FROM contacto WHERE id_contacto = :id", [":id" => $id]
        );
        return ["p_id" => $id, "p_id_usuario" => $idUsuario];
    }
}
