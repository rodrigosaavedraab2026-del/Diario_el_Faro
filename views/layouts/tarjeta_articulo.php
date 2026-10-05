<?php
/**
 * VISTA (parcial): tarjeta de un artículo. Espera $a (Articulo) y
 * opcionalmente $claseColumna (ancho Bulma de la columna).
 */
$claseColumna = $claseColumna ?? "is-one-third-desktop is-half-tablet";
$urlArticulo  = base_url("index.php?c=articulo&a=ver&id=" . (int) $a->getId());
?>
<div class="column <?php echo e($claseColumna); ?>">
  <article class="card card-articulo">
    <div class="card-image">
      <figure class="image is-4by3">
        <img src="<?php echo base_url('assets/img/' . e($a->getImagen())); ?>" alt="" loading="lazy">
      </figure>
    </div>
    <div class="card-content">
      <span class="tag <?php echo e($a->getTagColor()); ?> is-light mb-2"><?php echo e($a->getEtiqueta()); ?></span>
      <h3 class="title is-6">
        <a class="enlace-tarjeta" href="<?php echo $urlArticulo; ?>"><?php echo e($a->getTitulo()); ?></a>
      </h3>
      <p class="is-size-7 mb-2"><?php echo e($a->getResumen()); ?></p>
      <p class="subtitle is-7 has-text-grey">
        <time datetime="<?php echo e(date('c', strtotime($a->getFechaPublicacion()))); ?>"><?php echo e(fecha_legible($a->getFechaPublicacion())); ?></time>
        · <?php echo e($a->getSeccion()); ?>
      </p>
    </div>
  </article>
</div>
