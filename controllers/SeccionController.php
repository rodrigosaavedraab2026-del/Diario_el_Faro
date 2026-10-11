<?php
require_once __DIR__ . "/../models/ArticuloRepositorio.php";
require_once __DIR__ . "/../models/SeccionRepositorio.php";
require_once __DIR__ . "/../models/SitioAsociadoRepositorio.php";

/**
 * CONTROLADOR: SeccionController
 * Muestra las secciones del diario reutilizando una misma Vista de lista
 * paginada, alimentada por el Modelo (tablas seccion + articulo).
 */
class SeccionController
{
    private $articulos;
    private $secciones;

    public function __construct()
    {
        $this->articulos = new ArticuloRepositorio();
        $this->secciones = new SeccionRepositorio();
    }

    public function deporte()  { $this->mostrar("deporte"); }
    public function negocios() { $this->mostrar("negocios"); }

    /** Ruta genérica: index.php?c=seccion&a=ver&s=cultura */
    public function ver()
    {
        $this->mostrar((string) get_slug("s"));
    }

    private function mostrar($slug)
    {
        $seccion = $this->secciones->buscarPorSlug($slug);
        if ($seccion === null) {
            redirigir("index.php");
        }

        $tituloPagina  = $seccion->getNombre();
        $seccionActiva = $seccion->getSlug();
        $nombreSeccion = $seccion->getNombre();

        [$pagina, $porPagina] = Paginador::leerParametros();
        $paginador = new Paginador($this->articulos->contar($slug), $pagina, $porPagina);
        $articulos = $this->articulos->listarPagina($paginador->porPagina, $paginador->offset(), $slug);
        $rutaPaginacion = "index.php?c=seccion&a=ver&s=" . rawurlencode($slug);
        $sitios = (new SitioAsociadoRepositorio())->listar($slug, 4);

        require __DIR__ . "/../views/seccion/lista.php";
    }
}
