<?php
require_once __DIR__ . "/../models/ContactoRepositorio.php";

/**
 * CONTROLADOR: ContactoController
 * Requisito funcional c) Registrar los datos del formulario de contacto
 * en la tabla `contacto` de MySQL (antes: archivo de texto).
 */
class ContactoController
{
    private $repositorio;

    public function __construct()
    {
        $this->repositorio = new ContactoRepositorio();
    }

    /** Muestra el formulario (GET). */
    public function index()
    {
        $tituloPagina = "Contacto";
        $seccionActiva = "contacto";
        $errores = [];
        $datos = ["nombre" => "", "email" => "", "asunto" => "Enviar una noticia", "mensaje" => ""];
        require __DIR__ . "/../views/contacto/form.php";
    }

    /** Procesa el envío (POST). */
    public function enviar()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            redirigir("index.php?c=contacto&a=index");
        }
        $tituloPagina = "Contacto";
        $seccionActiva = "contacto";

        $datos = [
            "nombre"  => trim($_POST["nombre"]  ?? ""),
            "email"   => trim($_POST["email"]   ?? ""),
            "asunto"  => trim($_POST["asunto"]  ?? ""),
            "mensaje" => trim($_POST["mensaje"] ?? "")
        ];

        $v = new Validador();
        if (!csrf_valido()) {
            $v->agregar("general", "La sesión expiró. Vuelve a enviar el formulario.");
        }
        $v->requerido("nombre", $datos["nombre"], "Ingresa tu nombre.")
          ->largo("nombre", $datos["nombre"], 2, 120)
          ->soloTexto("nombre", $datos["nombre"])
          ->requerido("email", $datos["email"], "Ingresa tu correo.")
          ->email("email", $datos["email"])
          ->largo("email", $datos["email"], 6, 150)
          ->enLista("asunto", $datos["asunto"], Contacto::ASUNTOS, "Selecciona un asunto válido.")
          ->requerido("mensaje", $datos["mensaje"], "Escribe tu mensaje.")
          ->largo("mensaje", $datos["mensaje"], 10, 2000);

        if (!$v->esValido()) {
            $errores = $v->errores();
            require __DIR__ . "/../views/contacto/form.php";
            return;
        }

        $contacto = new Contacto($datos["nombre"], $datos["email"], $datos["asunto"], $datos["mensaje"]);
        $this->repositorio->registrar($contacto);

        require __DIR__ . "/../views/contacto/gracias.php";
    }
}
