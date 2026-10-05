<?php
/**
 * VISTA (parcial): paginación Bulma + selector "artículos por página".
 * Espera $paginador (Paginador), $rutaPaginacion (string) y opcionalmente
 * $etiquetaElementos (texto para el selector, por defecto "artículos").
 */
$etiquetaElementos = $etiquetaElementos ?? "artículos";
$urlPagina = function ($p, $pp = null) use ($rutaPaginacion, $paginador) {
    return base_url($rutaPaginacion . "&p=" . (int) $p . "&pp=" . (int) ($pp ?? $paginador->porPagina));
};
?>
<div class="barra-paginacion mt-5">
  <p class="mb-3 is-size-7 has-text-grey">
    Mostrando página <?php echo $paginador->pagina; ?> de <?php echo $paginador->totalPaginas; ?>
    (<?php echo $paginador->total; ?> <?php echo e($etiquetaElementos); ?> en total).
    <span class="ml-2">Por página:</span>
    <?php foreach (POR_PAGINA_OPCIONES as $opcion): ?>
      <a class="tag <?php echo $opcion === $paginador->porPagina ? 'is-dark' : 'is-light'; ?>"
         href="<?php echo $urlPagina(1, $opcion); ?>"
         <?php echo $opcion === $paginador->porPagina ? 'aria-current="true"' : ''; ?>
         aria-label="Ver <?php echo $opcion; ?> <?php echo e($etiquetaElementos); ?> por página"><?php echo $opcion; ?></a>
    <?php endforeach; ?>
  </p>

  <?php if ($paginador->totalPaginas > 1): ?>
  <nav class="pagination is-centered" role="navigation" aria-label="paginación">
    <?php if ($paginador->pagina > 1): ?>
      <a class="pagination-previous" href="<?php echo $urlPagina($paginador->pagina - 1); ?>">Anterior</a>
    <?php else: ?>
      <span class="pagination-previous is-disabled" aria-disabled="true">Anterior</span>
    <?php endif; ?>
    <?php if ($paginador->pagina < $paginador->totalPaginas): ?>
      <a class="pagination-next" href="<?php echo $urlPagina($paginador->pagina + 1); ?>">Siguiente</a>
    <?php else: ?>
      <span class="pagination-next is-disabled" aria-disabled="true">Siguiente</span>
    <?php endif; ?>
    <ul class="pagination-list">
      <?php for ($i = 1; $i <= $paginador->totalPaginas; $i++): ?>
        <li>
          <a class="pagination-link <?php echo $i === $paginador->pagina ? 'is-current' : ''; ?>"
             href="<?php echo $urlPagina($i); ?>"
             aria-label="<?php echo $i === $paginador->pagina ? 'Página ' . $i . ', actual' : 'Ir a la página ' . $i; ?>"
             <?php echo $i === $paginador->pagina ? 'aria-current="page"' : ''; ?>><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
</div>
