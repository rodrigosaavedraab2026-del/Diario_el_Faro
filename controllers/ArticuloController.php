<?php
require_once __DIR__ . "/../models/ArticuloRepositorio.php";
require_once __DIR__ . "/../models/SeccionRepositorio.php";
require_once __DIR__ . "/../models/AutorRepositorio.php";
require_once __DIR__ . "/../models/ComentarioRepositorio.php";
require_once __DIR__ . "/../models/SitioAsociadoRepositorio.php";

/**
 * CONTROLADOR: ArticuloController
 * Requisitos funcionales:
 *   a) Registrar un nuevo artículo              -> nuevo() + guardar()
 *   b) Presentar uno o más artículos por página -> index() + ver()
 * Semana 9 (usabilidad y UX):
 *   - Comentarios de lectores en cada artículo  -> ver() + comentar()
 *   - Buscador de noticias                     -> buscar()
 *   - Artículos relacionados y sitios asociados en la nota -> ver()
 */
class ArticuloController
{
    /** Imágenes disponibles en assets/img (lista blanca para el formulario). */
    const IMAGENES = ["destacada.jpg", "nota1.jpg", "nota2.jpg", "nota3.jpg", "nota4.jpg", "nota5.jpg", "nota6.jpg"];

    private $articulos;
    private $secciones;
    private $autores;
    private $comentarios;
    private $sitios;

    public function __construct()
    {
        $this->articulos = new ArticuloRepositorio();
        $this->secciones = new SeccionRepositorio();
        $this->autores   = new AutorRepositorio();
        $this->comentarios = new ComentarioRepositorio();
        $this->sitios      = new SitioAsociadoRepositorio();
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

    /** Un artículo completo por página (GET ?id=), con sus comentarios. */
    public function ver()
    {
        $id = get_entero("id");
        $articulo = $id !== null ? $this->articulos->buscarPorId($id) : null;
        if ($articulo === null) {
            http_response_code(404);
            redirigir("index.php?c=articulo&a=index");
        }
        // Si el lector inició sesión, su nombre y correo se completan solos.
        $lector = Sesion::usuario();
        $this->mostrarArticulo($articulo, [], ["nombre" => $lector["nombre"] ?? "", "email" => $lector["email"] ?? "", "texto" => ""]);
    }

    /** Registra un comentario (POST) y vuelve al artículo (patrón PRG). */
    public function comentar()
    {
        $idArticulo = (int) ($_POST["id_articulo"] ?? 0);
        $articulo = $this->articulos->buscarPorId($idArticulo);
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || $articulo === null) {
            redirigir("index.php?c=articulo&a=index");
        }

        $datos = [
            "nombre" => trim($_POST["nombre"] ?? ""),
            "email"  => mb_strtolower(trim($_POST["email"] ?? ""), "UTF-8"),
            "texto"  => trim($_POST["texto"] ?? ""),
        ];
        [$minN, $maxN] = Comentario::LARGO_NOMBRE;
        [$minT, $maxT] = Comentario::LARGO_TEXTO;

        $v = new Validador();
        if (!csrf_valido()) {
            $v->agregar("general", "La sesión expiró. Vuelve a enviar tu comentario.");
        }
        $v->requerido("nombre", $datos["nombre"], "Ingresa tu nombre.")
          ->largo("nombre", $datos["nombre"], $minN, $maxN)
          ->soloTexto("nombre", $datos["nombre"])
          ->requerido("email", $datos["email"], "Ingresa tu correo.")
          ->email("email", $datos["email"])
          ->largo("email", $datos["email"], 6, 150)
          ->requerido("texto", $datos["texto"], "Escribe tu comentario.")
          ->largo("texto", $datos["texto"], $minT, $maxT)
          ->sinEtiquetas("texto", $datos["texto"]);
        if (!isset($_POST["normas"])) {
            $v->agregar("normas", "Debes aceptar las normas de la comunidad.");
        }

        if (!$v->esValido()) {
            $this->mostrarArticulo($articulo, $v->errores(), $datos);
            return;
        }

        $comentario = new Comentario($articulo->getId(), $datos["nombre"], $datos["email"], $datos["texto"]);
        $this->comentarios->registrar($comentario);
        redirigir("index.php?c=articulo&a=ver&id=" . $articulo->getId() . "&comentado=1#comentarios");
    }

    /** Buscador (GET ?q=): título, resumen o etiqueta, con paginación. */
    public function buscar()
    {
        $q = get_busqueda("q");
        $tituloPagina  = $q === "" ? "Buscar" : "Resultados para «" . $q . "»";
        $seccionActiva = "buscar";
        $consultaValida = mb_strlen($q, "UTF-8") >= 3;

        [$pagina, $porPagina] = Paginador::leerParametros();
        $total = $consultaValida ? $this->articulos->contarBusqueda($q) : 0;
        $paginador = new Paginador($total, $pagina, $porPagina);
        $articulos = $consultaValida ? $this->articulos->buscarTexto($q, $paginador->porPagina, $paginador->offset()) : [];
        $rutaPaginacion = "index.php?c=articulo&a=buscar&q=" . rawurlencode($q);
        require __DIR__ . "/../views/articulo/buscar.php";
    }

    /** Prepara los datos de la Vista del artículo (lectura + comentarios). */
    private function mostrarArticulo(Articulo $articulo, array $errores, array $datosComentario)
    {
        $tituloPagina    = $articulo->getTitulo();
        $seccionActiva   = $articulo->getSeccionSlug();
        $recienPublicado = isset($_GET["nuevo"]);
        $comentado       = isset($_GET["comentado"]);
        $comentarios     = $this->comentarios->listarPorArticulo($articulo->getId());
        $relacionados    = $this->articulos->listarRelacionados($articulo->getSeccionSlug(), $articulo->getId(), 3);
        $sitios          = $this->sitios->listar($articulo->getSeccionSlug(), 3);
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
          ->htmlPermitido("cuerpo", $datos["cuerpo"])
          ->requerido("etiqueta", $datos["etiqueta"], "Ingresa una etiqueta.")
          ->largo("etiqueta", $datos["etiqueta"], 3, 40)
          ->enLista("id_seccion", $datos["id_seccion"], $idsSeccion, "Selecciona una sección.")
          ->enLista("id_autor", $datos["id_autor"], $idsAutor, "Selecciona un autor.")
          ->enLista("imagen", $datos["imagen"], self::IMAGENES, "Selecciona una imagen válida.");

        if (!$v->esValido()) {
            $this->mostrarFormulario($datos, $v->errores());
            return;
        }

        // El cuerpo se guarda ya filtrado por la política de HTML (si trae etiquetas).
        if (PoliticaHtml::tieneHtml($datos["cuerpo"])) {
            $datos["cuerpo"] = (new PoliticaHtml())->limpiar($datos["cuerpo"]);
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
