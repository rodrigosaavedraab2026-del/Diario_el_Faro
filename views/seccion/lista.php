<?php require __DIR__ . "/../layouts/header.php"; ?>

  <section class="section pt-5 seccion-<?php echo e($seccionActiva); ?>">
    <div class="container">
      <nav class="breadcrumb" aria-label="ruta de navegación">
        <ul>
          <li><a href="<?php echo base_url('index.php'); ?>">Portada</a></li>
          <li class="is-active"><a href="#" aria-current="page"><?php echo e($nombreSeccion); ?></a></li>
        </ul>
      </nav>

      <div class="encabezado-bloque mb-4">
        <h1 class="titulo-bloque is-size-3"><?php echo e($nombreSeccion); ?></h1>
        <span class="tag is-info is-light contador-seccion">
          <?php echo $paginador->total === 1 ? "1 artículo" : (int) $paginador->total . " artículos"; ?>
        </span>
      </div>

      <div class="columns is-variable is-5">
        <div class="column is-9-desktop">
          <?php if (empty($articulos)): ?>
            <div class="notification is-light">Aún no hay artículos en esta sección.</div>
          <?php endif; ?>

          <div class="columns is-multiline" data-seccion="<?php echo e($seccionActiva); ?>">
            <?php $claseColumna = "is-one-third-desktop is-half-tablet"; ?>
            <?php foreach ($articulos as $a) { require __DIR__ . "/../layouts/tarjeta_articulo.php"; } ?>
          </div>

          <?php require __DIR__ . "/../layouts/paginacion.php"; ?>
        </div>

        <?php if (!empty($sitios)): ?>
        <aside class="column is-3-desktop">
          <div class="box">
            <h2 class="titulo-bloque is-size-6">Sitios asociados</h2>
            <?php $sitiosCompacto = true; require __DIR__ . "/../layouts/sitios_asociados.php"; ?>
          </div>
        </aside>
        <?php endif; ?>
      </div>
    </div>
  </section>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
