<?php
require_once __DIR__ . "/../models/ArticuloRepositorio.php";

/**
 * CONTROLADOR: HomeController
 * Arma la portada: artículo destacado + resto de noticias paginadas,
 * leídos desde MySQL a través del Modelo (ArticuloRepositorio).
 */
class HomeController
{
    private $repositorio;

    public function __construct()
    {
        $this->repositorio = new ArticuloRepositorio();
    }

    public function index()
    {
        $tituloPagina  = "Portada";
        $seccionActiva = "inicio";

        $destacado = $this->repositorio->obtenerDestacado();

        [$pagina, $porPagina] = Paginador::leerParametros();
        $paginador = new Paginador($this->repositorio->contar(null, true), $pagina, $porPagina);
        $articulos = $this->repositorio->listarPagina($paginador->porPagina, $paginador->offset(), null, true);
        $rutaPaginacion = "index.php?c=home&a=index";

        require __DIR__ . "/../views/home/index.php";
    }
}
