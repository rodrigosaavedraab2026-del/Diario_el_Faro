<?php require __DIR__ . "/../layouts/header.php"; ?>

  <section class="section">
    <div class="container">
      <nav class="breadcrumb" aria-label="ruta de navegación">
        <ul>
          <li><a href="<?php echo base_url('index.php'); ?>">Inicio</a></li>
          <li class="is-active"><a href="#" aria-current="page"><?php echo e($nombreSeccion); ?></a></li>
        </ul>
      </nav>

      <div class="level is-mobile mb-3">
        <div class="level-left">
          <h1 class="title is-3 mb-0"><?php echo e($nombreSeccion); ?></h1>
        </div>
        <div class="level-right">
          <span class="tag is-info is-light contador-seccion" data-contador="<?php echo e($seccionActiva); ?>" aria-live="polite"></span>
        </div>
      </div>

      <?php if (empty($articulos)): ?>
        <div class="notification is-light">Aún no hay artículos en esta sección.</div>
      <?php endif; ?>

      <div class="columns is-multiline" data-seccion="<?php echo e($seccionActiva); ?>">
        <?php $claseColumna = "is-one-quarter-desktop is-half-tablet"; ?>
        <?php foreach ($articulos as $a) { require __DIR__ . "/../layouts/tarjeta_articulo.php"; } ?>
      </div>

      <?php require __DIR__ . "/../layouts/paginacion.php"; ?>
    </div>
  </section>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
