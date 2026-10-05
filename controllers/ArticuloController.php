<?php
require_once __DIR__ . "/../models/ArticuloRepositorio.php";
require_once __DIR__ . "/../models/SeccionRepositorio.php";
require_once __DIR__ . "/../models/AutorRepositorio.php";

/**
 * CONTROLADOR: ArticuloController
 * Requisitos funcionales:
 *   a) Registrar un nuevo artículo              -> nuevo() + guardar()
 *   b) Presentar uno o más artículos por página -> index() + ver()
 */
class ArticuloController
{
    /** Imágenes disponibles en assets/img (lista blanca para el formulario). */
    const IMAGENES = ["destacada.jpg", "nota1.jpg", "nota2.jpg", "nota3.jpg", "nota4.jpg", "nota5.jpg", "nota6.jpg"];

    private $articulos;
    private $secciones;
    private $autores;

    public function __construct()
    {
        $this->articulos = new ArticuloRepositorio();
        $this->secciones = new SeccionRepositorio();
        $this->autores   = new AutorRepositorio();
    }

    /** Todas las noticias, paginadas (GET). */
    public function index()
    {
        $tituloPagina  = "Noticias";
        $seccionActiva = "noticias";
        [$pagina, $porPagina] = Paginador::leerParametros();
        $paginador = new Paginador($this->articulos->contar(), $pagina, $porPagina);
        $articulos = $this->articulos->listarPagina($paginador->porPagina, $paginador->offset());
        $rutaPaginacion = "index.php?c=articulo&a=index";
        require __DIR__ . "/../views/articulo/lista.php";
    }

    /** Un artículo completo por página (GET ?id=). */
    public function ver()
    {
        $articulo = $this->articulos->buscarPorId((int) ($_GET["id"] ?? 0));
        if ($articulo === null) {
            http_response_code(404);
            redirigir("index.php?c=articulo&a=index");
        }
        $tituloPagina  = $articulo->getTitulo();
        $seccionActiva = $articulo->getSeccionSlug();
        $recienPublicado = isset($_GET["nuevo"]);
        require __DIR__ . "/../views/articulo/ver.php";
    }

    /** Formulario de publicación (GET). */
    public function nuevo()
    {
        $errores = [];
        $datos = ["titulo" => "", "resumen" => "", "cuerpo" => "", "etiqueta" => "",
                  "imagen" => "destacada.jpg", "id_seccion" => "", "id_autor" => "", "destacado" => false];
        $this->mostrarFormulario($datos, $errores);
    }

    /** Procesa la publicación (POST): valida, crea el objeto y lo registra. */
    public function guardar()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            redirigir("index.php?c=articulo&a=nuevo");
        }

        $datos = [
            "titulo"     => trim($_POST["titulo"]     ?? ""),
            "resumen"    => trim($_POST["resumen"]    ?? ""),
            "cuerpo"     => trim($_POST["cuerpo"]     ?? ""),
            "etiqueta"   => trim($_POST["etiqueta"]   ?? ""),
            "imagen"     => trim($_POST["imagen"]     ?? ""),
            "id_seccion" => trim($_POST["id_seccion"] ?? ""),
            "id_autor"   => trim($_POST["id_autor"]   ?? ""),
            "destacado"  => isset($_POST["destacado"]),
        ];

        $idsSeccion = array_map(fn($s) => (string) $s->getId(), $this->secciones->listarTodas());
        $idsAutor   = array_map(fn($a) => (string) $a->getId(), $this->autores->listarTodos());

        $v = new Validador();
        if (!csrf_valido()) {
            $v->agregar("general", "La sesión expiró. Vuelve a enviar el formulario.");
        }
        $v->requerido("titulo", $datos["titulo"], "Ingresa un título.")
          ->largo("titulo", $datos["titulo"], 10, 200)
          ->requerido("resumen", $datos["resumen"], "Ingresa un resumen.")
          ->largo("resumen", $datos["resumen"], 20, 300)
          ->requerido("cuerpo", $datos["cuerpo"], "Escribe el cuerpo de la noticia.")
          ->largo("cuerpo", $datos["cuerpo"], 50, 10000)
          ->requerido("etiqueta", $datos["etiqueta"], "Ingresa una etiqueta.")
          ->largo("etiqueta", $datos["etiqueta"], 3, 40)
          ->enLista("id_seccion", $datos["id_seccion"], $idsSeccion, "Selecciona una sección.")
          ->enLista("id_autor", $datos["id_autor"], $idsAutor, "Selecciona un autor.")
          ->enLista("imagen", $datos["imagen"], self::IMAGENES, "Selecciona una imagen válida.");

        if (!$v->esValido()) {
            $this->mostrarFormulario($datos, $v->errores());
            return;
        }

        $articulo = new Articulo($datos["titulo"], $datos["resumen"], $datos["cuerpo"],
                                 $datos["etiqueta"], $datos["imagen"],
                                 (int) $datos["id_seccion"], (int) $datos["id_autor"]);
        $articulo->setDestacado($datos["destacado"]);
        $id = $this->articulos->registrar($articulo);

        // Patrón POST/Redirect/GET: recargar la página no duplica el artículo.
        redirigir("index.php?c=articulo&a=ver&id=" . $id . "&nuevo=1");
    }

    private function mostrarFormulario(array $datos, array $errores)
    {
        $tituloPagina  = "Publicar artículo";
        $seccionActiva = "publicar";
        $secciones = $this->secciones->listarTodas();
        $autores   = $this->autores->listarTodos();
        $imagenes  = self::IMAGENES;
        require __DIR__ . "/../views/articulo/form.php";
    }
}
