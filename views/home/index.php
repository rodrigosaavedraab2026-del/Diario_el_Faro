<?php require __DIR__ . "/../layouts/header.php"; ?>

  <h1 class="is-sr-only">Diario El Faro — Portada</h1>

  <?php if (isset($_GET["bienvenida"]) && ($lectorActual = Sesion::usuario()) !== null): ?>
  <div class="container px-4 pt-4">
    <div class="notification is-success is-light mb-0" role="status">
      <span class="icon-text"><span class="icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
      <span>Hola, <?php echo e($lectorActual["nombre"]); ?>. Iniciaste sesión correctamente.</span></span>
    </div>
  </div>
  <?php endif; ?>

  <!-- 1. Destacado + "Lo último" (sin hero: el contenido aparece de inmediato) -->
  <section class="section pt-5 pb-4">
    <div class="container">
      <div class="columns is-variable is-5">
        <?php if ($destacado !== null): ?>
        <div class="column is-8-desktop is-7-tablet">
          <article class="card card-destacada">
            <div class="card-image">
              <figure class="image is-16by9">
                <img src="<?php echo base_url('assets/img/' . e($destacado->getImagen())); ?>" alt="">
              </figure>
            </div>
            <div class="card-content">
              <p class="mb-2">
                <span class="tag is-warning has-text-weight-semibold">Destacado</span>
                <span class="tag <?php echo e($destacado->getTagColor()); ?> is-light"><?php echo e($destacado->getSeccion()); ?></span>
              </p>
              <h2 class="title is-3 titulo-destacado">
                <a class="enlace-tarjeta" href="<?php echo base_url('index.php?c=articulo&a=ver&id=' . (int) $destacado->getId()); ?>"><?php echo e($destacado->getTitulo()); ?></a>
              </h2>
              <p class="bajada"><?php echo e($destacado->getResumen()); ?></p>
              <p class="meta mt-3">
                <time datetime="<?php echo e(date('c', strtotime($destacado->getFechaPublicacion()))); ?>"><?php echo e(fecha_legible($destacado->getFechaPublicacion())); ?></time>
                · <?php echo e($destacado->getAutor()); ?>
                · <?php echo (int) $destacado->getMinutosLectura(); ?> min de lectura
              </p>
            </div>
          </article>
        </div>
        <?php endif; ?>

        <aside class="column" aria-labelledby="titulo-lo-ultimo">
          <div class="box lo-ultimo">
            <h2 class="titulo-bloque" id="titulo-lo-ultimo">
              <span class="icon"><i class="fa-solid fa-bolt" aria-hidden="true"></i></span> Lo último
            </h2>
            <ol>
              <?php foreach ($ultimos as $u): ?>
              <li>
                <a href="<?php echo base_url('index.php?c=articulo&a=ver&id=' . (int) $u->getId()); ?>"><?php echo e($u->getTitulo()); ?></a>
                <span class="meta"><?php echo e($u->getSeccion()); ?> · <time datetime="<?php echo e(date('c', strtotime($u->getFechaPublicacion()))); ?>"><?php echo e(hace_cuanto($u->getFechaPublicacion())); ?></time></span>
              </li>
              <?php endforeach; ?>
            </ol>
            <a class="button is-primary is-outlined is-fullwidth mt-2" href="<?php echo base_url('index.php?c=articulo&a=index'); ?>">
              Ver todas las noticias
            </a>
          </div>
        </aside>
      </div>
    </div>
  </section>

  <!-- 2. Un bloque por sección: la portada crece ordenada aunque aumenten las notas -->
  <?php foreach ($bloques as [$seccion, $totalSeccion, $notas]): $urlSeccion = base_url('index.php?c=seccion&a=ver&s=' . rawurlencode($seccion->getSlug())); ?>
  <section class="section py-4 bloque-seccion seccion-<?php echo e($seccion->getSlug()); ?>" aria-labelledby="bloque-<?php echo e($seccion->getSlug()); ?>">
    <div class="container">
      <div class="encabezado-bloque">
        <h2 class="titulo-bloque" id="bloque-<?php echo e($seccion->getSlug()); ?>"><?php echo e($seccion->getNombre()); ?></h2>
        <a class="ver-todo" href="<?php echo $urlSeccion; ?>">
          Ver todo <span class="is-sr-only">de <?php echo e($seccion->getNombre()); ?></span>(<?php echo (int) $totalSeccion; ?>)
          <span class="icon is-small"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
        </a>
      </div>
      <div class="columns is-multiline">
        <?php $claseColumna = "is-one-third-desktop is-half-tablet"; ?>
        <?php foreach ($notas as $a) { require __DIR__ . "/../layouts/tarjeta_articulo.php"; } ?>
      </div>
    </div>
  </section>
  <?php endforeach; ?>

  <!-- 3. Sitios asociados (tabla sitio_asociado) -->
  <?php if (!empty($sitios)): ?>
  <section class="section py-5 franja-sitios" aria-labelledby="titulo-sitios">
    <div class="container">
      <div class="encabezado-bloque">
        <h2 class="titulo-bloque" id="titulo-sitios">
          <span class="icon"><i class="fa-solid fa-link" aria-hidden="true"></i></span> Sitios asociados
        </h2>
      </div>
      <?php require __DIR__ . "/../layouts/sitios_asociados.php"; ?>
    </div>
  </section>
  <?php endif; ?>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
