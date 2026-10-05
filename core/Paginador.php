<?php
/**
 * Paginador: calcula LIMIT/OFFSET y los datos que necesita la Vista
 * para mostrar "uno o más artículos por página".
 */
class Paginador
{
    public $pagina;
    public $porPagina;
    public $total;
    public $totalPaginas;

    public function __construct($total, $pagina, $porPagina)
    {
        $this->total        = max(0, (int) $total);
        $this->porPagina    = max(1, (int) $porPagina);
        $this->totalPaginas = max(1, (int) ceil($this->total / $this->porPagina));
        $this->pagina       = min(max(1, (int) $pagina), $this->totalPaginas);
    }

    public function offset()
    {
        return ($this->pagina - 1) * $this->porPagina;
    }

    /** Lee ?pp= y ?p= de la URL, aceptando solo valores permitidos. */
    public static function leerParametros()
    {
        $pp = (int) ($_GET["pp"] ?? POR_PAGINA_DEFECTO);
        if (!in_array($pp, POR_PAGINA_OPCIONES, true)) {
            $pp = POR_PAGINA_DEFECTO;
        }
        $p = (int) ($_GET["p"] ?? 1);
        return [$p < 1 ? 1 : $p, $pp];
    }
}
