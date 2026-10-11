<?php
/**
 * CONFIGURACIÓN DE LA CONEXIÓN — credenciales FUERA del código
 * (semana 9, recomendación del docente: centralizar la configuración
 * sensible en un archivo .env o en variables de entorno).
 *
 * Antes (semana 8) los datos estaban como constantes con valores escritos
 * aquí mismo (define("DB_CLAVE", "…")). Ahora:
 *
 *   1. Los valores viven en el archivo .env de la raíz del proyecto, que
 *      NO se versiona ni se comparte (.gitignore) y que .htaccess bloquea
 *      para que nunca se pueda descargar desde el navegador.
 *      El archivo .env.example documenta qué variables se necesitan.
 *   2. La librería vlucas/phpdotenv (instalada con Composer en vendor/)
 *      carga el .env en $_ENV y $_SERVER, y valida que las variables
 *      obligatorias existan y tengan el formato correcto.
 *   3. Si el servidor ya define variables de entorno reales (por ejemplo,
 *      en un hosting con panel de variables), estas tienen prioridad:
 *      createImmutable() nunca sobrescribe una variable que ya existe.
 *   4. Solo la clase Conexion pide estos datos, y lo hace al conectarse
 *      (carga diferida), dentro del try/catch del controlador frontal: si
 *      falta una variable se muestra la página de error, sin exponer nada.
 */

/** @return array{host:string,puerto:int,nombre:string,usuario:string,clave:string,charset:string} */
function configuracion_bd()
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $raiz = dirname(__DIR__);
    if (is_file($raiz . "/vendor/autoload.php")) {
        require_once $raiz . "/vendor/autoload.php";
        try {
            $dotenv = Dotenv\Dotenv::createImmutable($raiz);
            $dotenv->safeLoad();                                   // no falla si no hay .env (variables del servidor)
            $dotenv->required(["DB_HOST", "DB_NOMBRE", "DB_USUARIO"])->notEmpty();
            $dotenv->required("DB_CLAVE");                         // puede ir vacía (root de XAMPP)
            $dotenv->ifPresent("DB_PUERTO")->isInteger();
        } catch (Dotenv\Exception\ExceptionInterface $ex) {
            // Se convierte en PDOException para que index.php muestre su página de error.
            throw new PDOException("Configuración de base de datos incompleta en .env: " . $ex->getMessage());
        }
    } else {
        cargar_env_simple($raiz . "/.env");                        // respaldo si no se subió vendor/
    }

    $config = [
        "host"    => env_valor("DB_HOST"),
        "puerto"  => (int) env_valor("DB_PUERTO", "3306"),
        "nombre"  => env_valor("DB_NOMBRE"),
        "usuario" => env_valor("DB_USUARIO"),
        "clave"   => (string) env_valor("DB_CLAVE", ""),
        "charset" => "utf8mb4",
    ];
    foreach (["host", "nombre", "usuario"] as $obligatoria) {
        if ($config[$obligatoria] === null || $config[$obligatoria] === "") {
            throw new PDOException("Falta la variable de entorno de base de datos: " . strtoupper($obligatoria));
        }
    }
    return $config;
}

/** Lee una variable: primero $_ENV/$_SERVER (phpdotenv) y luego getenv() (variables del servidor). */
function env_valor($clave, $porDefecto = null)
{
    $valor = $_ENV[$clave] ?? $_SERVER[$clave] ?? getenv($clave);
    return ($valor === false || $valor === null) ? $porDefecto : $valor;
}

/**
 * Respaldo mínimo (sin Composer): lee líneas CLAVE=valor del .env, ignora
 * comentarios y no sobrescribe variables ya definidas. Misma regla que
 * phpdotenv en modo inmutable.
 */
function cargar_env_simple($ruta)
{
    if (!is_readable($ruta)) {
        return;
    }
    foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === "" || $linea[0] === "#" || strpos($linea, "=") === false) {
            continue;
        }
        [$clave, $valor] = array_map("trim", explode("=", $linea, 2));
        $valor = preg_replace('/^(["\'])(.*)\1$/', '$2', $valor);
        if (env_valor($clave) === null) {
            $_ENV[$clave] = $valor;
        }
    }
}
