<?php require __DIR__ . "/../layouts/header.php"; ?>

  <section class="section">
    <div class="container">
      <nav class="breadcrumb" aria-label="ruta de navegación">
        <ul>
          <li><a href="<?php echo base_url('index.php'); ?>">Inicio</a></li>
          <li class="is-active"><a href="#" aria-current="page">Noticias</a></li>
        </ul>
      </nav>

      <div class="level mb-3">
        <div class="level-left">
          <div>
            <h1 class="title is-3 mb-2">Todas las noticias</h1>
            <p class="has-text-grey">Artículos almacenados en la base de datos, del más reciente al más antiguo.</p>
          </div>
        </div>
        <div class="level-right">
          <a class="button is-primary" href="<?php echo base_url('index.php?c=articulo&a=nuevo'); ?>">
            <span class="icon"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></span>
            <span>Publicar artículo</span>
          </a>
        </div>
      </div>

      <div class="columns is-multiline" data-seccion="noticias">
        <?php $claseColumna = "is-one-third-desktop is-half-tablet"; ?>
        <?php foreach ($articulos as $a) { require __DIR__ . "/../layouts/tarjeta_articulo.php"; } ?>
      </div>

      <?php require __DIR__ . "/../layouts/paginacion.php"; ?>
    </div>
  </section>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
