<?php require __DIR__ . "/../layouts/header.php"; ?>

  <section class="section">
    <div class="container">
      <nav class="breadcrumb" aria-label="ruta de navegación">
        <ul>
          <li><a href="<?php echo base_url('index.php'); ?>">Portada</a></li>
          <li class="is-active"><a href="#" aria-current="page">Noticias</a></li>
        </ul>
      </nav>

      <div class="encabezado-bloque mb-4">
        <div>
          <h1 class="titulo-bloque is-size-3">Todas las noticias</h1>
          <p class="has-text-grey">Del artículo más reciente al más antiguo.</p>
        </div>
        <span class="tag is-info is-light contador-seccion"><?php echo (int) $paginador->total; ?> artículos</span>
      </div>

      <div class="columns is-multiline" data-seccion="noticias">
        <?php $claseColumna = "is-one-third-desktop is-half-tablet"; ?>
        <?php foreach ($articulos as $a) { require __DIR__ . "/../layouts/tarjeta_articulo.php"; } ?>
      </div>

      <?php require __DIR__ . "/../layouts/paginacion.php"; ?>
    </div>
  </section>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
