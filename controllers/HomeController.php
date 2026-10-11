<?php
require_once __DIR__ . "/../models/ArticuloRepositorio.php";
require_once __DIR__ . "/../models/SeccionRepositorio.php";
require_once __DIR__ . "/../models/SitioAsociadoRepositorio.php";

/**
 * CONTROLADOR: HomeController
 * Portada reorganizada en la semana 9 (usabilidad y UX):
 *   1. Artículo destacado + columna "Lo último" (5 titulares).
 *   2. Un bloque por sección con sus 3 notas más recientes y acceso
 *      "Ver todo", en lugar de una sola lista que crece sin orden.
 *   3. Franja de sitios asociados (tabla sitio_asociado).
 * Todo se lee desde MySQL a través del Modelo.
 */
class HomeController
{
    private $articulos;
    private $secciones;
    private $sitios;

    public function __construct()
    {
        $this->articulos = new ArticuloRepositorio();
        $this->secciones = new SeccionRepositorio();
        $this->sitios    = new SitioAsociadoRepositorio();
    }

    public function index()
    {
        $tituloPagina  = "Portada";
        $seccionActiva = "inicio";

        $destacado = $this->articulos->obtenerDestacado();
        $ultimos   = $this->articulos->listarPagina(5, 0, null, true);

        // Bloques por sección: [Seccion, total, Articulo[]]
        $bloques = [];
        foreach ($this->secciones->listarConTotales() as [$seccion, $total]) {
            if ($total > 0) {
                $bloques[] = [$seccion, $total, $this->articulos->listarPagina(3, 0, $seccion->getSlug())];
            }
        }
        $sitios = $this->sitios->listar(null, 6);

        require __DIR__ . "/../views/home/index.php";
    }
}
