<?php
/**
 * Sesion — manejo protegido de la sesión PHP (semana 9, recomendación del docente).
 *
 * Medidas aplicadas:
 *  - Cookie de sesión con HttpOnly (JavaScript no puede leerla), SameSite=Lax
 *    (no viaja en peticiones POST de otros sitios) y Secure cuando el sitio
 *    se abre por HTTPS (InfinityFree entrega certificado).
 *  - session.use_strict_mode: PHP rechaza identificadores de sesión que él no
 *    creó (evita la fijación de sesión) y use_only_cookies: el id nunca va en la URL.
 *  - session_regenerate_id(true) al iniciar sesión: el identificador cambia
 *    justo cuando la persona obtiene privilegios.
 *  - Expiración por inactividad (30 minutos) y cierre completo de sesión
 *    (datos, cookie y archivo del servidor).
 *  - Límite de intentos de inicio de sesión: 5 fallos seguidos bloquean el
 *    formulario 60 segundos (frena ataques de fuerza bruta desde un navegador).
 *  - En la sesión solo se guardan id, nombre y correo del lector, nunca la
 *    contraseña ni su hash.
 */
class Sesion
{
    const NOMBRE_COOKIE = "ELFARO_SESION";
    const INACTIVIDAD_SEGUNDOS = 1800;
    const MAX_INTENTOS = 5;
    const BLOQUEO_SEGUNDOS = 60;

    public static function iniciar()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set("session.use_strict_mode", "1");
        ini_set("session.use_only_cookies", "1");
        ini_set("session.use_trans_sid", "0");
        session_name(self::NOMBRE_COOKIE);
        session_set_cookie_params([
            "lifetime" => 0,              // se borra al cerrar el navegador
            "path"     => "/",
            "secure"   => self::esHttps(),
            "httponly" => true,
            "samesite" => "Lax",
        ]);
        session_start();

        $ultima = $_SESSION["ultima_actividad"] ?? null;
        if ($ultima !== null && time() - $ultima > self::INACTIVIDAD_SEGUNDOS) {
            self::cerrar();
            session_start();
        }
        $_SESSION["ultima_actividad"] = time();
    }

    /** Guarda al lector autenticado y renueva el identificador de sesión. */
    public static function iniciarUsuario($id, $nombre, $email)
    {
        session_regenerate_id(true);
        $_SESSION["usuario"] = ["id" => (int) $id, "nombre" => (string) $nombre, "email" => (string) $email];
        unset($_SESSION["intentos_login"], $_SESSION["bloqueo_login"], $_SESSION["csrf_token"]);
    }

    /** @return array{id:int,nombre:string,email:string}|null */
    public static function usuario()
    {
        return $_SESSION["usuario"] ?? null;
    }

    public static function cerrar()
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), "", [
                "expires" => time() - 3600, "path" => $p["path"], "domain" => $p["domain"],
                "secure" => $p["secure"], "httponly" => $p["httponly"], "samesite" => $p["samesite"] ?: "Lax",
            ]);
        }
        session_destroy();
    }

    /* ---------------- Límite de intentos de inicio de sesión ---------------- */

    /** Segundos que faltan para poder intentar de nuevo (0 = no está bloqueado). */
    public static function segundosBloqueo()
    {
        $hasta = $_SESSION["bloqueo_login"] ?? 0;
        return max(0, $hasta - time());
    }

    public static function registrarFallo()
    {
        $_SESSION["intentos_login"] = ($_SESSION["intentos_login"] ?? 0) + 1;
        if ($_SESSION["intentos_login"] >= self::MAX_INTENTOS) {
            $_SESSION["bloqueo_login"] = time() + self::BLOQUEO_SEGUNDOS;
            $_SESSION["intentos_login"] = 0;
        }
    }

    private static function esHttps()
    {
        return (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
            || (($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https")
            || ((int) ($_SERVER["SERVER_PORT"] ?? 0) === 443);
    }
}
