<?php
require_once __DIR__ . "/../models/Usuario.php";
require_once __DIR__ . "/../models/Localidad.php";
require_once __DIR__ . "/../models/UsuarioRepositorio.php";

/**
 * CONTROLADOR: UsuarioController
 * Registro de cuentas de lector (tabla `usuario` + `localidad`) y
 * requisito funcional d) Presentar los usuarios registrados.
 */
class UsuarioController
{
    const PLANES = ["gratuito" => "Gratuito", "mensual" => "Mensual", "anual" => "Anual"];

    private $repositorio;

    public function __construct()
    {
        $this->repositorio = new UsuarioRepositorio();
    }

    /** Muestra el formulario de registro (GET). */
    public function registro()
    {
        $tituloPagina = "Crear cuenta";
        $seccionActiva = "registro";
        $errores = [];
        $datos = ["nombre" => "", "apellido" => "", "email" => "", "pais" => "Chile", "ciudad" => "Viña del Mar", "plan" => "gratuito"];
        require __DIR__ . "/../views/usuario/registro.php";
    }

    /** Procesa el registro (POST): valida, crea objetos, cifra y persiste. */
    public function guardar()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            redirigir("index.php?c=usuario&a=registro");
        }
        $tituloPagina = "Crear cuenta";
        $seccionActiva = "registro";

        $datos = [
            "nombre"   => trim($_POST["nombre"]   ?? ""),
            "apellido" => trim($_POST["apellido"] ?? ""),
            "email"    => mb_strtolower(trim($_POST["email"] ?? ""), "UTF-8"),
            "pais"     => trim($_POST["pais"]     ?? ""),
            "ciudad"   => trim($_POST["ciudad"]   ?? ""),
            "plan"     => trim($_POST["plan"]     ?? "gratuito")
        ];
        $password  = (string) ($_POST["password"]  ?? "");
        $password2 = (string) ($_POST["password2"] ?? "");

        $v = new Validador();
        if (!csrf_valido()) {
            $v->agregar("general", "La sesión expiró. Vuelve a enviar el formulario.");
        }
        $v->requerido("nombre", $datos["nombre"], "Ingresa tu nombre.")
          ->largo("nombre", $datos["nombre"], 2, 80)->soloTexto("nombre", $datos["nombre"])
          ->requerido("apellido", $datos["apellido"], "Ingresa tu apellido.")
          ->largo("apellido", $datos["apellido"], 2, 80)->soloTexto("apellido", $datos["apellido"])
          ->requerido("email", $datos["email"], "Ingresa tu correo.")
          ->email("email", $datos["email"])->largo("email", $datos["email"], 6, 150)
          ->largo("pais", $datos["pais"], 2, 80)->soloTexto("pais", $datos["pais"])
          ->largo("ciudad", $datos["ciudad"], 2, 80)->soloTexto("ciudad", $datos["ciudad"])
          ->enLista("plan", $datos["plan"], array_keys(self::PLANES), "Selecciona un plan válido.");

        if (!isset($v->errores()["email"]) && $this->repositorio->existeEmail($datos["email"])) {
            $v->agregar("email", "Este correo ya está registrado.");
        }
        if (strlen($password) < 8) {
            $v->agregar("password", "La contraseña debe tener al menos 8 caracteres.");
        } elseif (strlen($password) > 72) {
            $v->agregar("password", "La contraseña no puede superar los 72 caracteres.");
        } elseif ($password !== $password2) {
            $v->agregar("password2", "Las contraseñas no coinciden.");
        }

        if (!$v->esValido()) {
            $errores = $v->errores();
            require __DIR__ . "/../views/usuario/registro.php";
            return;
        }

        // Creación de objetos del Modelo
        $usuario = new Usuario($datos["nombre"], $datos["apellido"], $datos["email"]);
        $usuario->setPassword($password);           // cifrado dentro del Modelo
        $usuario->setPlan($datos["plan"]);
        $usuario->setLocalidad(new Localidad($datos["pais"], $datos["ciudad"]));

        $this->repositorio->registrar($usuario);    // INSERT transaccional en MySQL

        require __DIR__ . "/../views/usuario/exito.php";
    }

    /** Requisito d): lista paginada de lectores registrados (GET). */
    public function listado()
    {
        $tituloPagina  = "Lectores registrados";
        $seccionActiva = "lectores";
        [$pagina, $porPagina] = Paginador::leerParametros();
        $paginador = new Paginador($this->repositorio->contar(), $pagina, $porPagina);
        $usuarios  = $this->repositorio->listarPagina($paginador->porPagina, $paginador->offset());
        $rutaPaginacion = "index.php?c=usuario&a=listado";
        $planes = self::PLANES;
        require __DIR__ . "/../views/usuario/listado.php";
    }
}
