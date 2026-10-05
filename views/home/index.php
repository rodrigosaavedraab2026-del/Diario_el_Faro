<?php require __DIR__ . "/../layouts/header.php"; ?>

  <!-- Hero de portada -->
  <section class="hero is-primary">
    <div class="hero-body">
      <div class="container">
        <h1 class="title is-2">Diario El Faro</h1>
        <p class="subtitle is-5">Las noticias de Viña del Mar y la región, al día.</p>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">

      <?php if ($destacado !== null && $paginador->pagina === 1): ?>
      <!-- Artículo destacado (tabla articulo, destacado = 1) -->
      <h2 class="title is-4 mb-4">
        <span class="icon-text">
          <span class="icon has-text-primary"><i class="fa-solid fa-star" aria-hidden="true"></i></span>
          <span>Lo más reciente</span>
        </span>
      </h2>

      <article class="card mb-6 card-destacada">
        <div class="columns is-gapless">
          <div class="column is-half">
            <figure class="image is-4by3">
              <img src="<?php echo base_url('assets/img/' . e($destacado->getImagen())); ?>" alt="">
            </figure>
          </div>
          <div class="column is-half">
            <div class="card-content">
              <span class="tag <?php echo e($destacado->getTagColor()); ?> mb-2"><?php echo e($destacado->getEtiqueta()); ?></span>
              <h3 class="title is-3"><?php echo e($destacado->getTitulo()); ?></h3>
              <p class="subtitle is-6 has-text-grey">
                <?php echo e(fecha_legible($destacado->getFechaPublicacion())); ?> · <?php echo e($destacado->getAutor()); ?>
              </p>
              <div class="content"><?php echo e($destacado->getResumen()); ?></div>
              <a href="<?php echo base_url('index.php?c=articulo&a=ver&id=' . (int) $destacado->getId()); ?>" class="button is-primary">
                Leer nota completa<span class="is-sr-only">: <?php echo e($destacado->getTitulo()); ?></span>
              </a>
            </div>
          </div>
        </div>
      </article>
      <?php endif; ?>

      <!-- Otras noticias (paginadas desde MySQL) -->
      <div class="level is-mobile mb-4">
        <div class="level-left">
          <h2 class="title is-4 mb-0">
            <span class="icon-text">
              <span class="icon has-text-info"><i class="fa-solid fa-newspaper" aria-hidden="true"></i></span>
              <span>Otras noticias</span>
            </span>
          </h2>
        </div>
        <div class="level-right">
          <span class="tag is-info is-light contador-seccion" data-contador="otras-noticias" aria-live="polite"></span>
        </div>
      </div>

      <div class="columns is-multiline" data-seccion="otras-noticias">
        <?php foreach ($articulos as $a) { require __DIR__ . "/../layouts/tarjeta_articulo.php"; } ?>
      </div>

      <?php require __DIR__ . "/../layouts/paginacion.php"; ?>
    </div>
  </section>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
