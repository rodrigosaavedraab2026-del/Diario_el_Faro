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

/**
 * e(): ESCAPE DE SALIDA para toda impresión en la Vista (prevención de XSS).
 * Convierte & < > " ' en entidades HTML, por lo que un texto como
 * <script>alert(1)</script> se MUESTRA como texto y el navegador no lo ejecuta.
 *  - ENT_QUOTES: escapa comillas simples y dobles (seguro también dentro de atributos).
 *  - ENT_SUBSTITUTE: reemplaza bytes UTF-8 inválidos en vez de devolver una cadena vacía.
 *  - ENT_HTML5: entidades según HTML5.
 * Regla del proyecto: todo dato variable se imprime con e(); la única excepción
 * es el cuerpo del artículo, que pasa por la lista blanca de core/PoliticaHtml.php.
 */
function e($texto)
{
    return htmlspecialchars((string) ($texto ?? ""), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, "UTF-8");
}

/* ------------------------------------------------------------------
 * VALIDACIÓN ESTRICTA DE PARÁMETROS GET (semana 9)
 * Los datos de la URL se aceptan solo si cumplen su formato exacto;
 * cualquier otro valor se trata como ausente (null).
 * ------------------------------------------------------------------ */

/** Entero positivo de la URL (?id=5). "5abc", "-1" o "1e3" se rechazan. */
function get_entero($clave, $minimo = 1)
{
    $valor = filter_input(INPUT_GET, $clave, FILTER_VALIDATE_INT, ["options" => ["min_range" => $minimo]]);
    return ($valor === false || $valor === null) ? null : $valor;
}

/** Identificador de sección (?s=deporte): minúsculas, números y guiones, hasta 60. */
function get_slug($clave)
{
    $valor = $_GET[$clave] ?? null;
    return (is_string($valor) && preg_match('/^[a-z0-9-]{1,60}$/', $valor)) ? $valor : null;
}

/** Texto de búsqueda (?q=): se quitan caracteres de control y se limita a 60. */
function get_busqueda($clave)
{
    $valor = $_GET[$clave] ?? "";
    if (!is_string($valor)) {
        return "";
    }
    $valor = preg_replace('/[\x00-\x1F\x7F]/u', "", $valor) ?? "";
    return mb_substr(trim($valor), 0, 60, "UTF-8");
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

/* ------------------------------------------------------------------
 * SEMANA 9 — DATOS COMPARTIDOS DEL LAYOUT
 * El Front Controller (index.php) obtiene del Modelo las secciones del
 * menú una sola vez y las deja aquí; header.php y footer.php solo las
 * leen. Así la Vista nunca consulta la base de datos (MVC).
 * ------------------------------------------------------------------ */
function layout($clave, $valor = null)
{
    static $datos = [];
    if (func_num_args() === 2) {
        $datos[$clave] = $valor;
    }
    return $datos[$clave] ?? null;
}

/** Oculta parte de un correo para listados públicos: r*****@correo.cl */
function ocultar_correo($email)
{
    $partes = explode("@", (string) $email, 2);
    if (count($partes) !== 2) {
        return "—";
    }
    $usuario = $partes[0];
    $visible = mb_substr($usuario, 0, 1, "UTF-8");
    return $visible . str_repeat("•", max(3, mb_strlen($usuario, "UTF-8") - 1)) . "@" . $partes[1];
}

/** Texto "hace X" para fechas recientes (comentarios y "Lo último"). */
function hace_cuanto($fechaMysql)
{
    $t = strtotime((string) $fechaMysql);
    if ($t === false) {
        return "";
    }
    $seg = time() - $t;
    if ($seg < 60)      return "hace un momento";
    if ($seg < 3600)    return "hace " . (int) floor($seg / 60) . " min";
    if ($seg < 86400)   return "hace " . (int) floor($seg / 3600) . " h";
    if ($seg < 7 * 86400) {
        $d = (int) floor($seg / 86400);
        return "hace " . $d . ($d === 1 ? " día" : " días");
    }
    return fecha_legible($fechaMysql);
}
