<?php
/**
 * VISTA (parcial): tarjetas de sitios asociados. Espera $sitios (SitioAsociado[])
 * y opcionalmente $sitiosCompacto (true = lista en una columna, para barras laterales).
 * Los enlaces externos se abren en otra pestaña y lo anuncian al lector de pantalla.
 */
$sitiosCompacto = $sitiosCompacto ?? false;
?>
<ul class="lista-sitios <?php echo $sitiosCompacto ? 'es-compacta' : ''; ?>">
  <?php foreach ($sitios as $sitio): ?>
  <li>
    <a class="sitio" href="<?php echo e($sitio->getUrl()); ?>" target="_blank" rel="noopener noreferrer">
      <span class="sitio-icono" aria-hidden="true"><i class="fa-solid <?php echo e($sitio->getIcono()); ?>"></i></span>
      <span class="sitio-texto">
        <strong><?php echo e($sitio->getNombre()); ?></strong>
        <span class="sitio-descripcion"><?php echo e($sitio->getDescripcion()); ?></span>
        <span class="sitio-dominio"><?php echo e($sitio->getDominio()); ?>
          <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
          <span class="is-sr-only">(se abre en una pestaña nueva)</span></span>
      </span>
    </a>
  </li>
  <?php endforeach; ?>
</ul>
