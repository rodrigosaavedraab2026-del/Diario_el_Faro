<?php
require_once __DIR__ . "/Repositorio.php";
require_once __DIR__ . "/Usuario.php";

/**
 * MODELO (acceso a datos): UsuarioRepositorio
 * Registro y listado de lectores (tablas `usuario` y `localidad`) a través
 * de la clase única Conexion.
 *
 * Procedimientos almacenados utilizados:
 *   sp_usuario_registrar  INSERCIÓN lector + localidad (transacción + OUT p_id)
 *   sp_usuario_listar     CONSULTA  página de lectores con su localidad
 */
class UsuarioRepositorio extends Repositorio
{
    /** Verifica si un correo ya está registrado (sentencia preparada). */
    public function existeEmail($email)
    {
        return (bool) $this->conexion->consultarValor(
            "SELECT 1 FROM usuario WHERE email = :email LIMIT 1",
            [":email" => $email]
        );
    }

    /**
     * Registra un Usuario. sp_usuario_registrar ejecuta, en una sola
     * TRANSACCIÓN, la búsqueda o creación de la Localidad y el INSERT del
     * usuario con su llave foránea id_localidad. Devuelve el id (OUT p_id).
     */
    public function registrar(Usuario $u)
    {
        $loc = $u->getLocalidad();
        $pais   = $loc !== null ? $loc->getPais() : "";
        $ciudad = $loc !== null ? $loc->getCiudad() : "";

        $datos = [$u->getNombre(), $u->getApellido(), $u->getEmail(), $u->getPasswordHash(),
                  $u->getPlan(), $pais, $ciudad];

        $id = $this->conProcedimiento(
            fn() => $this->conexion->llamarProcedimiento("sp_usuario_registrar", $datos, ["p_id"])["salida"]["p_id"],
            fn() => $this->registrarConSentencias($datos)
        );
        $u->setId($id);
        return $u->getId();
    }

    /** Total de lectores registrados (sentencia preparada). */
    public function contar()
    {
        return (int) $this->conexion->consultarValor("SELECT COUNT(*) FROM usuario");
    }

    /** Página de usuarios con su localidad y cantidad de mensajes enviados.
     *  Nunca expone el hash de la contraseña. */
    public function listarPagina($limite, $offset)
    {
        $limite = (int) $limite;
        $offset = (int) $offset;
        $filas = $this->conProcedimiento(
            fn() => $this->conexion->llamarProcedimiento("sp_usuario_listar", [$limite, $offset])["filas"],
            fn() => $this->conexion->consultar(
                "SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.plan, u.fecha_registro,
                        l.id_localidad, l.pais, l.ciudad,
                        (SELECT COUNT(*) FROM contacto c WHERE c.id_usuario = u.id_usuario) AS mensajes
                 FROM usuario u
                 LEFT JOIN localidad l ON l.id_localidad = u.id_localidad
                 ORDER BY u.fecha_registro DESC, u.id_usuario DESC
                 LIMIT :limite OFFSET :offset",
                [":limite" => $limite, ":offset" => $offset]
            )
        );
        return array_map([Usuario::class, "desdeFila"], $filas);
    }

    /** Equivalente con sentencias preparadas (solo para hosting sin rutinas). */
    private function registrarConSentencias(array $d)
    {
        [$nombre, $apellido, $email, $hash, $plan, $pais, $ciudad] = $d;
        $this->conexion->iniciarTransaccion();
        try {
            $idLocalidad = null;
            if ($ciudad !== "") {
                $idLocalidad = $this->conexion->consultarValor(
                    "SELECT id_localidad FROM localidad WHERE pais = :pais AND ciudad = :ciudad",
                    [":pais" => $pais, ":ciudad" => $ciudad]
                );
                $idLocalidad = $idLocalidad ? (int) $idLocalidad : $this->conexion->insertar(
                    "INSERT INTO localidad (pais, ciudad) VALUES (:pais, :ciudad)",
                    [":pais" => $pais, ":ciudad" => $ciudad]
                );
            }
            $id = $this->conexion->insertar(
                "INSERT INTO usuario (nombre, apellido, email, password_hash, plan, id_localidad)
                 VALUES (:nombre, :apellido, :email, :hash, :plan, :id_localidad)",
                [":nombre" => $nombre, ":apellido" => $apellido, ":email" => $email,
                 ":hash" => $hash, ":plan" => $plan, ":id_localidad" => $idLocalidad]
            );
            $this->conexion->confirmar();
            return $id;
        } catch (Throwable $ex) {
            $this->conexion->revertir();
            throw $ex;
        }
    }
}
