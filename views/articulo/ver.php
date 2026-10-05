<?php require __DIR__ . "/../layouts/header.php"; ?>

  <section class="section">
    <div class="container">
      <nav class="breadcrumb" aria-label="ruta de navegación">
        <ul>
          <li><a href="<?php echo base_url('index.php'); ?>">Inicio</a></li>
          <li><a href="<?php echo base_url('index.php?c=seccion&a=ver&s=' . rawurlencode($articulo->getSeccionSlug())); ?>"><?php echo e($articulo->getSeccion()); ?></a></li>
          <li class="is-active"><a href="#" aria-current="page">Artículo</a></li>
        </ul>
      </nav>

      <?php if ($recienPublicado): ?>
        <div class="notification is-success" role="status">
          <span class="icon-text">
            <span class="icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
            <span>Artículo registrado en la base de datos con el identificador <strong>#<?php echo (int) $articulo->getId(); ?></strong>.</span>
          </span>
        </div>
      <?php endif; ?>

      <div class="columns">
        <article class="column is-8">
          <span class="tag <?php echo e($articulo->getTagColor()); ?> is-light mb-2"><?php echo e($articulo->getEtiqueta()); ?></span>
          <h1 class="title is-2"><?php echo e($articulo->getTitulo()); ?></h1>
          <p class="subtitle is-5"><?php echo e($articulo->getResumen()); ?></p>
          <p class="is-size-7 has-text-grey mb-4">
            <span class="icon-text">
              <span class="icon"><i class="fa-solid fa-user-pen" aria-hidden="true"></i></span>
              <span><?php echo e($articulo->getAutor()); ?></span>
            </span>
            ·
            <time datetime="<?php echo e(date('c', strtotime($articulo->getFechaPublicacion()))); ?>"><?php echo e(fecha_legible($articulo->getFechaPublicacion())); ?></time>
          </p>
          <figure class="image is-16by9 mb-5">
            <img src="<?php echo base_url('assets/img/' . e($articulo->getImagen())); ?>" alt="">
          </figure>
          <div class="content is-medium">
            <?php foreach ($articulo->getParrafos() as $parrafo): ?>
              <p><?php echo nl2br(e($parrafo)); ?></p>
            <?php endforeach; ?>
          </div>
        </article>

        <aside class="column is-4">
          <div class="box">
            <h2 class="title is-6">Más de <?php echo e($articulo->getSeccion()); ?></h2>
            <a class="button is-light is-fullwidth mb-2" href="<?php echo base_url('index.php?c=seccion&a=ver&s=' . rawurlencode($articulo->getSeccionSlug())); ?>">Ver sección</a>
            <a class="button is-light is-fullwidth" href="<?php echo base_url('index.php?c=articulo&a=index'); ?>">Todas las noticias</a>
          </div>
        </aside>
      </div>
    </div>
  </section>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
