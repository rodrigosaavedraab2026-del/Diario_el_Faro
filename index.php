<?php
/**
 * index.php — FRONT CONTROLLER (punto de entrada único de la aplicación)
 *
 * Recibe todas las peticiones, instancia el controlador solicitado y
 * ejecuta su acción, según MVC. Enrutamiento: index.php?c=controlador&a=accion
 *
 * Flujo de datos (semana 8): Controlador -> Repositorio de la entidad ->
 * Conexion (clase única PDO) -> procedimiento almacenado en MySQL.
 *
 *   index.php                              -> Portada (artículos paginados)
 *   index.php?c=seccion&a=deporte          -> Sección Deporte
 *   index.php?c=seccion&a=negocios         -> Sección Negocios
 *   index.php?c=articulo&a=index           -> Todas las noticias (paginadas)
 *   index.php?c=articulo&a=ver&id=1        -> Un artículo por página
 *   index.php?c=articulo&a=nuevo           -> Formulario "Publicar artículo"
 *   index.php?c=articulo&a=guardar  (POST) -> Registra el artículo en MySQL
 *   index.php?c=contacto&a=index           -> Formulario de contacto
 *   index.php?c=contacto&a=enviar   (POST) -> Registra el contacto en MySQL
 *   index.php?c=usuario&a=registro         -> Formulario "Crear cuenta"
 *   index.php?c=usuario&a=guardar   (POST) -> Registra el usuario en MySQL
 *   index.php?c=usuario&a=listado          -> Lectores registrados
 *   index.php?c=articulo&a=buscar&q=texto  -> Buscador de noticias (semana 9)
 *   index.php?c=articulo&a=comentar (POST) -> Registra un comentario (semana 9)
 *   index.php?c=usuario&a=ingresar         -> Inicio de sesión (GET formulario / POST verificación)
 *   index.php?c=usuario&a=salir     (POST) -> Cierre de sesión
 */

require_once __DIR__ . "/core/Sesion.php";
Sesion::iniciar();   // cookie HttpOnly + SameSite, modo estricto y expiración por inactividad

require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/core/Paginador.php";
require_once __DIR__ . "/core/Validador.php";
require_once __DIR__ . "/core/PoliticaHtml.php";
require_once __DIR__ . "/controllers/HomeController.php";
require_once __DIR__ . "/controllers/SeccionController.php";
require_once __DIR__ . "/controllers/ArticuloController.php";
require_once __DIR__ . "/controllers/ContactoController.php";
require_once __DIR__ . "/controllers/UsuarioController.php";
require_once __DIR__ . "/models/SeccionRepositorio.php";

/* Cabeceras de seguridad (semana 9). La política CSP solo permite scripts
 * propios (faro.js): aunque un texto malicioso llegara a la página, el
 * navegador no ejecutaría scripts en línea ni de otros dominios. */
header("Content-Security-Policy: default-src 'self'; script-src 'self'; "
     . "style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
     . "font-src 'self' https://cdnjs.cloudflare.com; img-src 'self' https: data:; "
     . "frame-src https://www.youtube-nocookie.com; object-src 'none'; base-uri 'self'; "
     . "form-action 'self'; frame-ancestors 'self'");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("X-Frame-Options: SAMEORIGIN");

// Controlador y acción: solo letras minúsculas (además de la lista blanca de abajo).
$controlador = is_string($_GET["c"] ?? null) && preg_match('/^[a-z]{1,20}$/', $_GET["c"]) ? $_GET["c"] : "home";
$accion      = is_string($_GET["a"] ?? null) && preg_match('/^[a-z]{1,20}$/', $_GET["a"]) ? $_GET["a"] : "index";

$controladores = [
    "home"     => "HomeController",
    "seccion"  => "SeccionController",
    "articulo" => "ArticuloController",
    "contacto" => "ContactoController",
    "usuario"  => "UsuarioController"
];

if (!isset($controladores[$controlador])) {
    $controlador = "home";
    $accion = "index";
}

try {
    // Datos del layout (menú de secciones): se leen una vez desde el Modelo.
    layout("secciones", (new SeccionRepositorio())->listarConTotales());

    $clase  = $controladores[$controlador];
    $objeto = new $clase();

    // Solo se permiten métodos públicos declarados en el controlador.
    if (is_callable([$objeto, $accion]) && $accion[0] !== "_") {
        $objeto->$accion();
    } else {
        (new HomeController())->index();
    }
} catch (PDOException $ex) {
    // La base de datos no está disponible: se informa sin exponer detalles.
    error_log("[El Faro] Error de base de datos: " . $ex->getMessage());
    http_response_code(503);
    $tituloPagina = "Servicio no disponible";
    $seccionActiva = "";

    // Diagnóstico: el detalle técnico solo se muestra cuando el sitio se
    // abre desde el mismo equipo (XAMPP); a los visitantes nunca se les muestra.
    $detalleError = null;
    if (in_array($_SERVER["REMOTE_ADDR"] ?? "", ["127.0.0.1", "::1"], true)) {
        $codigo = (int) ($ex->errorInfo[1] ?? $ex->getCode());
        $pistas = [
            2002 => "MySQL no está iniciado: presiona Start en MySQL en el panel de XAMPP.",
            1045 => "El usuario el_faro_app no existe o su clave no coincide: importa database/el_faro.sql con phpMyAdmin.",
            1049 => "La base el_faro no existe: créala e importa database/el_faro.sql.",
            1146 => "Falta una tabla o la vista: vuelve a importar database/el_faro.sql completo.",
            1370 => "El usuario el_faro_app no tiene permiso EXECUTE: vuelve a importar database/el_faro.sql (incluye el GRANT de la semana 8).",
            1558 => "Las tablas internas de MySQL de XAMPP están desactualizadas: ejecuta C:\\xampp\\mysql\\bin\\mysql_upgrade.exe -u root, reinicia MySQL e importa de nuevo database/el_faro.sql.",
        ];
        $detalleError = [
            "codigo"  => $codigo,
            "mensaje" => $ex->getMessage(),
            "pista"   => $pistas[$codigo] ?? null,
        ];
    }
    require __DIR__ . "/views/errores/bd.php";
}
