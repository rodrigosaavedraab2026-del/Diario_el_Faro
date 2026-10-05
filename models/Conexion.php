<?php
require_once __DIR__ . "/../config/database.php";

/**
 * Se lanza cuando el procedimiento almacenado solicitado no existe en la
 * base de datos (error 1305 de MySQL). Ocurre, por ejemplo, en hostings
 * gratuitos que no permiten crear rutinas. Los repositorios la capturan y
 * usan una sentencia preparada equivalente (modo compatibilidad).
 */
class ProcedimientoNoDisponible extends RuntimeException
{
}

/**
 * MODELO (infraestructura): Conexion — CLASE ÚNICA DE CONEXIÓN (semana 8)
 *
 * Requisitos 1 y 2 de la actividad:
 *   - Es la única clase del sitio que crea el objeto PDO. Aplica el patrón
 *     Singleton: toda la petición comparte una sola instancia y una sola
 *     conexión, sin importar cuántos repositorios la usen.
 *   - Ofrece los métodos de conexión, consulta e inserción que utilizan
 *     las demás clases:
 *       conectar()             crea (una vez) y devuelve la conexión PDO
 *       consultar()            SELECT  -> todas las filas
 *       consultarFila()        SELECT  -> una fila o null
 *       consultarValor()       SELECT  -> un valor (COUNT, EXISTS...)
 *       insertar()             INSERT  -> id generado
 *       ejecutar()             UPDATE / DELETE -> filas afectadas
 *       llamarProcedimiento()  CALL sp_...(?, ?, @salida) -> filas + parámetros OUT
 *
 * Requisito 3: todos los métodos trabajan con SENTENCIAS PREPARADAS
 * (prepare + bindValue + execute). Ningún dato del usuario se concatena
 * en el SQL, lo que evita la inyección SQL.
 */
final class Conexion
{
    /** Código de MySQL/MariaDB para "PROCEDURE ... does not exist". */
    const ERROR_PROCEDIMIENTO_INEXISTENTE = 1305;

    /** @var Conexion|null instancia única (Singleton) */
    private static $instancia = null;

    /** @var PDO|null conexión activa */
    private $pdo = null;

    /** El constructor es privado: solo obtenerInstancia() puede crear el objeto. */
    private function __construct()
    {
    }

    /** Se impide clonar la instancia única. */
    private function __clone()
    {
    }

    /** Devuelve siempre la misma instancia de Conexion. */
    public static function obtenerInstancia()
    {
        if (self::$instancia === null) {
            self::$instancia = new Conexion();
        }
        return self::$instancia;
    }

    /* ------------------------------------------------------------------
     * CONEXIÓN
     * ------------------------------------------------------------------ */

    /**
     * Abre la conexión PDO la primera vez que se necesita (carga diferida)
     * con los datos de config/database.php, y la reutiliza en adelante.
     * Si falla, PDO lanza PDOException, que index.php captura para mostrar
     * una página de error sin exponer detalles técnicos.
     */
    public function conectar()
    {
        if ($this->pdo === null) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT .
                   ";dbname=" . DB_NOMBRE . ";charset=" . DB_CHARSET;
            $this->pdo = new PDO($dsn, DB_USUARIO, DB_CLAVE, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // errores como excepciones
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // filas como arreglos asociativos
                PDO::ATTR_EMULATE_PREPARES   => false,                  // preparación real en el motor
            ]);
        }
        return $this->pdo;
    }

    /** Cierra la conexión (buena práctica del apunte: asignar null al objeto PDO). */
    public function desconectar()
    {
        $this->pdo = null;
    }

    /* ------------------------------------------------------------------
     * CONSULTA DE DATOS
     * ------------------------------------------------------------------ */

    /** Ejecuta un SELECT preparado y devuelve todas las filas. */
    public function consultar($sql, array $parametros = [])
    {
        return $this->preparar($sql, $parametros)->fetchAll();
    }

    /** Ejecuta un SELECT preparado y devuelve la primera fila (o null). */
    public function consultarFila($sql, array $parametros = [])
    {
        $fila = $this->preparar($sql, $parametros)->fetch();
        return $fila === false ? null : $fila;
    }

    /** Ejecuta un SELECT preparado y devuelve el valor de la primera columna. */
    public function consultarValor($sql, array $parametros = [])
    {
        return $this->preparar($sql, $parametros)->fetchColumn();
    }

    /* ------------------------------------------------------------------
     * INSERCIÓN Y MODIFICACIÓN
     * ------------------------------------------------------------------ */

    /** Ejecuta un INSERT preparado y devuelve el id autoincremental generado. */
    public function insertar($sql, array $parametros = [])
    {
        $this->preparar($sql, $parametros);
        return (int) $this->conectar()->lastInsertId();
    }

    /** Ejecuta un UPDATE o DELETE preparado y devuelve las filas afectadas. */
    public function ejecutar($sql, array $parametros = [])
    {
        return $this->preparar($sql, $parametros)->rowCount();
    }

    /* ------------------------------------------------------------------
     * PROCEDIMIENTOS ALMACENADOS (requisito 4)
     * ------------------------------------------------------------------ */

    /**
     * Llama a un procedimiento almacenado con una sentencia preparada.
     *
     *   $entrada: valores de los parámetros IN, en orden.
     *   $salida:  nombres de los parámetros OUT; se reciben en variables de
     *             sesión de MySQL (@nombre) y se leen después con un SELECT,
     *             igual que en el ejemplo del apunte:
     *               CALL sp_contarProductosPorEstado('disponible', @numero);
     *               SELECT @numero;
     *
     * Ejemplo: llamarProcedimiento("sp_articulo_registrar", [$titulo, ...], ["p_id"])
     *   prepara  CALL sp_articulo_registrar(?, ?, ..., @p_id)
     *
     * Devuelve ["filas" => filas del primer resultado, "salida" => valores OUT].
     */
    public function llamarProcedimiento($nombre, array $entrada = [], array $salida = [])
    {
        // El nombre no puede ir como parámetro (?) en SQL; por eso solo se
        // aceptan identificadores con el prefijo sp_ (lista blanca por patrón).
        if (!preg_match('/^sp_[a-z0-9_]+$/', $nombre)) {
            throw new InvalidArgumentException("Nombre de procedimiento no válido: $nombre");
        }
        foreach ($salida as $variable) {
            if (!preg_match('/^p_[a-z0-9_]+$/', $variable)) {
                throw new InvalidArgumentException("Nombre de parámetro OUT no válido: $variable");
            }
        }

        $marcadores = array_merge(
            array_fill(0, count($entrada), "?"),
            array_map(fn($v) => "@" . $v, $salida)
        );
        $sql = "CALL " . $nombre . "(" . implode(", ", $marcadores) . ")";

        try {
            $sentencia = $this->preparar($sql, array_values($entrada));
        } catch (PDOException $ex) {
            if (($ex->errorInfo[1] ?? null) === self::ERROR_PROCEDIMIENTO_INEXISTENTE) {
                throw new ProcedimientoNoDisponible("El procedimiento $nombre no existe en la base de datos.", 0, $ex);
            }
            throw $ex;
        }

        // Un CALL puede devolver varios conjuntos de resultados: se lee el
        // primero que tenga columnas y se recorren todos para liberar la
        // conexión antes de la siguiente consulta.
        $filas = null;
        do {
            if ($sentencia->columnCount() > 0) {
                $conjunto = $sentencia->fetchAll();
                if ($filas === null) {
                    $filas = $conjunto;
                }
            }
        } while ($sentencia->nextRowset());
        $sentencia->closeCursor();

        $valoresSalida = [];
        if ($salida) {
            $columnas = array_map(fn($v) => "@" . $v . " AS " . $v, $salida);
            $valoresSalida = $this->consultarFila("SELECT " . implode(", ", $columnas)) ?? [];
        }

        return ["filas" => $filas ?? [], "salida" => $valoresSalida];
    }

    /* ------------------------------------------------------------------
     * TRANSACCIONES
     * ------------------------------------------------------------------ */

    public function iniciarTransaccion()
    {
        return $this->conectar()->beginTransaction();
    }

    public function confirmar()
    {
        return $this->conectar()->commit();
    }

    public function revertir()
    {
        $pdo = $this->conectar();
        return $pdo->inTransaction() ? $pdo->rollBack() : false;
    }

    /* ------------------------------------------------------------------
     * APOYO INTERNO
     * ------------------------------------------------------------------ */

    /**
     * Prepara la sentencia, amarra (bind) cada parámetro con su tipo y la
     * ejecuta. Acepta marcadores con nombre (:id) o posicionales (?).
     */
    private function preparar($sql, array $parametros)
    {
        $sentencia = $this->conectar()->prepare($sql);
        foreach ($parametros as $clave => $valor) {
            $marcador = is_int($clave) ? $clave + 1 : $clave;
            $sentencia->bindValue($marcador, is_bool($valor) ? (int) $valor : $valor, $this->tipoPdo($valor));
        }
        $sentencia->execute();
        return $sentencia;
    }

    /** Tipo PDO según el valor: enteros, nulos y booleanos no viajan como texto. */
    private function tipoPdo($valor)
    {
        if (is_int($valor) || is_bool($valor)) {
            return PDO::PARAM_INT;
        }
        if ($valor === null) {
            return PDO::PARAM_NULL;
        }
        return PDO::PARAM_STR;
    }
}
