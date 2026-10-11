<?php require __DIR__ . "/../layouts/header.php"; ?>

  <section class="section pt-5">
    <div class="container">
      <nav class="breadcrumb" aria-label="ruta de navegación">
        <ul>
          <li><a href="<?php echo base_url('index.php'); ?>">Portada</a></li>
          <li class="is-active"><a href="#" aria-current="page">Buscar</a></li>
        </ul>
      </nav>

      <h1 class="title is-3 mb-2">
        <?php if ($q === ""): ?>Buscar noticias<?php else: ?>Resultados para «<?php echo e($q); ?>»<?php endif; ?>
      </h1>
      <p class="has-text-grey mb-5" role="status">
        <?php if (!$consultaValida): ?>
          Escribe al menos 3 caracteres en el buscador para ver resultados.
        <?php else: ?>
          <?php echo $paginador->total === 1 ? "Se encontró 1 artículo." : "Se encontraron " . (int) $paginador->total . " artículos."; ?>
        <?php endif; ?>
      </p>

      <?php if ($consultaValida && empty($articulos)): ?>
        <div class="box estado-vacio">
          <span class="icon is-large has-text-grey-light"><i class="fa-solid fa-magnifying-glass fa-2x" aria-hidden="true"></i></span>
          <p class="mb-3">No encontramos noticias con ese texto. Prueba con otra palabra o revisa una sección:</p>
          <div class="tags is-centered">
            <?php foreach (layout("secciones") ?? [] as [$sec, $totalSec]): ?>
              <a class="tag is-medium is-light" href="<?php echo base_url('index.php?c=seccion&a=ver&s=' . rawurlencode($sec->getSlug())); ?>"><?php echo e($sec->getNombre()); ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="columns is-multiline" data-seccion="busqueda">
        <?php $claseColumna = "is-one-third-desktop is-half-tablet"; ?>
        <?php foreach ($articulos as $a) { require __DIR__ . "/../layouts/tarjeta_articulo.php"; } ?>
      </div>

      <?php if ($consultaValida && $paginador->total > 0) { require __DIR__ . "/../layouts/paginacion.php"; } ?>
    </div>
  </section>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
