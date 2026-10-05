<?php
/** Configuración global del sitio El Faro (MVC). */
define("NOMBRE_SITIO", "Diario El Faro");

/** Cantidades permitidas de artículos por página (selector de la vista). */
define("POR_PAGINA_OPCIONES", [3, 6, 9]);
define("POR_PAGINA_DEFECTO", 6);

/** base_url(): arma rutas relativas a la raíz del proyecto. */
function base_url($ruta = "")
{
    $base = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/\\");
    return $base . "/" . ltrim($ruta, "/");
}

/** e(): escapa texto para prevenir inyección HTML/JS al imprimir en la Vista. */
function e($texto)
{
    return htmlspecialchars((string) ($texto ?? ""), ENT_QUOTES, "UTF-8");
}

/** redirigir(): patrón POST/Redirect/GET para evitar reenvíos del formulario. */
function redirigir($ruta)
{
    header("Location: " . base_url($ruta), true, 303);
    exit;
}

/* ------------------------------------------------------------------
 * PROTECCIÓN CSRF (recomendación del docente, semana 6)
 * Cada formulario POST incluye un token aleatorio guardado en la sesión.
 * El controlador rechaza la petición si el token no coincide.
 * ------------------------------------------------------------------ */
function csrf_token()
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

function csrf_campo()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valido()
{
    $enviado = $_POST["csrf_token"] ?? "";
    return is_string($enviado) && $enviado !== "" && hash_equals(csrf_token(), $enviado);
}

/** Formatea una fecha DATETIME de MySQL para mostrarla en la Vista. */
function fecha_legible($fechaMysql)
{
    $meses = ["", "enero", "febrero", "marzo", "abril", "mayo", "junio", "julio",
              "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
    $t = strtotime((string) $fechaMysql);
    if ($t === false) {
        return "";
    }
    return date("j", $t) . " de " . $meses[(int) date("n", $t)] . " de " . date("Y, H:i", $t);
}

/* ------------------------------------------------------------------
 * CONVENCIÓN DE NOMBRES EN FORMULARIOS (documentada en el README)
 *   form id ........ form-{formulario}           ej. form-contacto
 *   input id ....... {formulario}-{campo}        ej. contacto-email
 *   input name ..... {campo} = columna MySQL     ej. email
 *   ayuda/error id . ayuda-{formulario}-{campo}  ej. ayuda-contacto-email
 * Así la Vista, el script faro.js, el Controlador y la tabla usan el
 * mismo nombre de campo.
 * ------------------------------------------------------------------ */

/** Clase Bulma is-danger + atributos ARIA cuando el campo tiene error. */
function attrs_campo($formulario, $campo, array $errores)
{
    $ayuda = "ayuda-$formulario-$campo";
    $attrs = 'id="' . e("$formulario-$campo") . '" name="' . e($campo) . '" aria-describedby="' . e($ayuda) . '"';
    if (isset($errores[$campo])) {
        $attrs .= ' aria-invalid="true"';
    }
    return $attrs;
}

function clase_error($campo, array $errores)
{
    return isset($errores[$campo]) ? "is-danger" : "";
}

/** Párrafo de ayuda/error asociado al campo (siempre presente para faro.js). */
function ayuda_campo($formulario, $campo, array $errores)
{
    $hay = isset($errores[$campo]);
    return '<p class="help is-danger' . ($hay ? '' : ' is-hidden') . '" id="' .
           e("ayuda-$formulario-$campo") . '">' . ($hay ? e($errores[$campo]) : '') . '</p>';
}
