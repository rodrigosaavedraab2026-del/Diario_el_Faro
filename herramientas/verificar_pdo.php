<?php
/**
 * Verificación de la capa de datos (semana 8) — SOLO LÍNEA DE COMANDOS
 *
 * Uso (XAMPP, desde la carpeta del proyecto):
 *     C:\xampp\php\php.exe herramientas\verificar_pdo.php
 *
 * Comprueba la clase única Conexion, las sentencias preparadas y los
 * procedimientos almacenados de consulta e inserción. El mensaje de
 * prueba que inserta se elimina al terminar.
 */
if (PHP_SAPI !== "cli") {
    http_response_code(404);
    exit;
}

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../models/ArticuloRepositorio.php";
require_once __DIR__ . "/../models/SeccionRepositorio.php";
require_once __DIR__ . "/../models/UsuarioRepositorio.php";
require_once __DIR__ . "/../models/ContactoRepositorio.php";

$fallas = 0;
function revisar($descripcion, $condicion)
{
    global $fallas;
    echo ($condicion ? "  [OK]    " : "  [FALLA] ") . $descripcion . PHP_EOL;
    if (!$condicion) {
        $fallas++;
    }
}

echo PHP_EOL . "Diario El Faro — verificación PDO y procedimientos almacenados" . PHP_EOL;
echo str_repeat("-", 64) . PHP_EOL;

// 1. Clase única de conexión
$c1 = Conexion::obtenerInstancia();
$c2 = Conexion::obtenerInstancia();
revisar("Conexion es instancia única (Singleton)", $c1 === $c2);
revisar("conectar() devuelve un objeto PDO", $c1->conectar() instanceof PDO);
revisar("Los repositorios comparten la misma conexión",
    (new ReflectionProperty(Repositorio::class, "conexion"))->getValue(new SeccionRepositorio()) === $c1);
echo "          Servidor: " . $c1->consultarValor("SELECT VERSION()") .
     " · base: " . $c1->consultarValor("SELECT DATABASE()") . PHP_EOL;

// 2. Procedimientos almacenados disponibles
$procedimientos = array_column($c1->consultar(
    "SELECT ROUTINE_NAME FROM information_schema.ROUTINES
     WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_TYPE = :tipo ORDER BY ROUTINE_NAME",
    [":tipo" => "PROCEDURE"]
), "ROUTINE_NAME");
revisar(count($procedimientos) . " procedimientos almacenados en la base:", count($procedimientos) >= 7);
foreach (array_chunk($procedimientos, 2) as $par) {
    echo "          " . implode("", array_map(fn($n) => str_pad($n, 26), $par)) . PHP_EOL;
}

// 3. Consultas con procedimientos almacenados
$articulos = new ArticuloRepositorio();
$total = $articulos->contar();
revisar("sp_articulo_contar (OUT p_total) = $total artículos", $total > 0);
$pagina = $articulos->listarPagina(3, 0);
revisar("sp_articulo_listar (3 por página) devuelve " . count($pagina) . " artículos", count($pagina) === min(3, $total));
$uno = $articulos->buscarPorId($pagina[0]->getId());
revisar("sp_articulo_buscar(" . $pagina[0]->getId() . ") -> \"" . $uno->getTitulo() . "\"", $uno !== null);
$lectores = (new UsuarioRepositorio())->listarPagina(6, 0);
revisar("sp_usuario_listar devuelve " . count($lectores) . " lector(es)", count($lectores) > 0);

// 4. Inserción con procedimiento almacenado (se deshace al final)
$contacto = new Contacto("Prueba Automática", "prueba.semana8@elfaro.cl", "Otro", "Mensaje generado por verificar_pdo.php.");
$id = (new ContactoRepositorio())->registrar($contacto);
revisar("sp_contacto_registrar (OUT p_id) insertó el mensaje #$id", $id > 0);
$borrados = $c1->ejecutar("DELETE FROM contacto WHERE id_contacto = :id", [":id" => $id]);
revisar("Mensaje de prueba eliminado con sentencia preparada", $borrados === 1);

// 5. Sentencias preparadas frente a inyección SQL
$ataque = "deporte' OR '1'='1";
revisar("Inyección SQL en el slug no devuelve datos", (new SeccionRepositorio())->buscarPorSlug($ataque) === null);

echo str_repeat("-", 64) . PHP_EOL;
echo $fallas === 0 ? "Resultado: todas las verificaciones pasaron." : "Resultado: $fallas verificación(es) fallaron.";
echo PHP_EOL . PHP_EOL;
exit($fallas === 0 ? 0 : 1);
