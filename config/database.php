<?php
/**
 * ARCHIVO DE CONFIGURACIÓN DE LA CONEXIÓN (requisito 5, semana 8)
 *
 * Único lugar del proyecto donde se escriben los datos para conectarse a
 * MySQL. Ninguna otra clase conoce el servidor, el usuario ni la clave:
 * solo la clase Conexion (models/Conexion.php) lee estas constantes.
 *
 * Buena práctica: el sitio NO se conecta con root, sino con la cuenta
 * "el_faro_app", que solo tiene permisos sobre la base el_faro
 * (SELECT, INSERT, UPDATE, DELETE y EXECUTE para los procedimientos).
 *
 * Cada valor puede sobrescribirse con una variable de entorno del servidor,
 * lo que permite publicar el sitio en un hosting sin modificar el código.
 * En un hosting sin variables de entorno, reemplazar los valores por
 * defecto (segundo término de cada línea) por los que entrega el proveedor.
 */
define("DB_HOST",    getenv("DB_HOST")    ?: "localhost");
define("DB_PORT",    getenv("DB_PORT")    ?: "3306");
define("DB_NOMBRE",  getenv("DB_NOMBRE")  ?: "el_faro");
define("DB_USUARIO", getenv("DB_USUARIO") ?: "el_faro_app");
define("DB_CLAVE",   getenv("DB_CLAVE")   !== false ? getenv("DB_CLAVE") : "ElFaro_2026");
define("DB_CHARSET", "utf8mb4");
