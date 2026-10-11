<?php require __DIR__ . "/../layouts/header.php"; ?>
<?php
  $urlSeccion = base_url('index.php?c=seccion&a=ver&s=' . rawurlencode($articulo->getSeccionSlug()));
  $totalComentarios = count($comentarios);
  [$minN, $maxN] = Comentario::LARGO_NOMBRE;
  [$minT, $maxT] = Comentario::LARGO_TEXTO;
?>

  <section class="section pt-5">
    <div class="container">
      <nav class="breadcrumb" aria-label="ruta de navegación">
        <ul>
          <li><a href="<?php echo base_url('index.php'); ?>">Portada</a></li>
          <li><a href="<?php echo $urlSeccion; ?>"><?php echo e($articulo->getSeccion()); ?></a></li>
          <li class="is-active"><a href="#" aria-current="page" class="miga-titulo"><?php echo e($articulo->getTitulo()); ?></a></li>
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

      <div class="columns is-variable is-6">
        <article class="column is-8 nota">
          <span class="tag <?php echo e($articulo->getTagColor()); ?> is-light mb-2"><?php echo e($articulo->getEtiqueta()); ?></span>
          <h1 class="title is-2 titulo-nota"><?php echo e($articulo->getTitulo()); ?></h1>
          <p class="bajada-nota"><?php echo e($articulo->getResumen()); ?></p>
          <p class="meta meta-nota">
            <span class="icon-text">
              <span class="icon"><i class="fa-solid fa-user-pen" aria-hidden="true"></i></span>
              <span><?php echo e($articulo->getAutor()); ?></span>
            </span>
            <span class="icon-text">
              <span class="icon"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span>
              <time datetime="<?php echo e(date('c', strtotime($articulo->getFechaPublicacion()))); ?>"><?php echo e(fecha_legible($articulo->getFechaPublicacion())); ?></time>
            </span>
            <span class="icon-text">
              <span class="icon"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
              <span><?php echo (int) $articulo->getMinutosLectura(); ?> min de lectura</span>
            </span>
            <a class="icon-text enlace-comentarios" href="#comentarios">
              <span class="icon"><i class="fa-regular fa-comments" aria-hidden="true"></i></span>
              <span><?php echo $totalComentarios === 1 ? '1 comentario' : $totalComentarios . ' comentarios'; ?></span>
            </a>
          </p>
          <figure class="image is-16by9 mb-5">
            <img src="<?php echo base_url('assets/img/' . e($articulo->getImagen())); ?>" alt="">
          </figure>
          <div class="content cuerpo-nota">
            <?php // Único HTML sin e() del sitio: ya pasó por la lista blanca de PoliticaHtml (core/PoliticaHtml.php)
                  echo $articulo->getCuerpoHtml(); ?>
          </div>

          <!-- ===== Comentarios (semana 9) ===== -->
          <section id="comentarios" class="comentarios" aria-labelledby="titulo-comentarios" tabindex="-1">
            <h2 class="titulo-bloque" id="titulo-comentarios">
              <span class="icon"><i class="fa-regular fa-comments" aria-hidden="true"></i></span>
              Comentarios <span class="tag is-light is-rounded ml-1"><?php echo $totalComentarios; ?></span>
            </h2>

            <?php if ($comentado): ?>
              <div class="notification is-success is-light" role="status">
                <span class="icon-text">
                  <span class="icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
                  <span>¡Gracias! Tu comentario ya está publicado.</span>
                </span>
              </div>
            <?php endif; ?>

            <?php if ($totalComentarios === 0): ?>
              <p class="vacio">Aún no hay comentarios. Sé la primera persona en opinar.</p>
            <?php else: ?>
              <ol class="lista-comentarios">
                <?php foreach ($comentarios as $c): ?>
                <li class="comentario">
                  <span class="avatar" aria-hidden="true"><?php echo e($c->getIniciales()); ?></span>
                  <div>
                    <p class="comentario-cabecera">
                      <strong><?php echo e($c->getNombre()); ?></strong>
                      <time datetime="<?php echo e(date('c', strtotime($c->getFecha()))); ?>"><?php echo e(hace_cuanto($c->getFecha())); ?></time>
                    </p>
                    <p><?php echo nl2br(e($c->getTexto())); ?></p>
                  </div>
                </li>
                <?php endforeach; ?>
              </ol>
            <?php endif; ?>

            <div class="box caja-comentar">
              <h3 class="title is-5 mb-2">Deja tu comentario</h3>
              <p class="is-size-7 mb-4 has-text-grey">Los campos con <span class="marca-requerido" aria-hidden="true">*</span> son obligatorios. Tu correo no se publica.</p>

              <div id="mensaje-comentario" class="notification is-danger <?php echo empty($errores) ? 'is-hidden' : ''; ?>" role="alert">
                <?php echo e($errores['general'] ?? 'Revisa los campos marcados en rojo.'); ?>
              </div>

              <!-- Validación HTML5 (required, minlength, maxlength, pattern, title, type=email)
                   + mensajes accesibles de faro.js + validación del Controlador en PHP -->
              <form id="form-comentario" method="post" action="<?php echo base_url('index.php?c=articulo&a=comentar'); ?>#comentarios" data-validar-form data-mensaje="mensaje-comentario">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="id_articulo" value="<?php echo (int) $articulo->getId(); ?>">

                <div class="columns mb-0">
                  <div class="column is-6 pb-0">
                    <div class="field">
                      <label class="label es-requerido" for="comentario-nombre">Nombre</label>
                      <div class="control has-icons-left">
                        <input class="input <?php echo clase_error('nombre', $errores); ?>" type="text" <?php echo attrs_campo('comentario', 'nombre', $errores); ?>
                               autocomplete="name" minlength="<?php echo $minN; ?>" maxlength="<?php echo $maxN; ?>" required
                               pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ' \-]{<?php echo $minN; ?>,<?php echo $maxN; ?>}"
                               title="Solo letras y espacios (<?php echo $minN; ?> a <?php echo $maxN; ?> caracteres)"
                               data-validar="requerido min:<?php echo $minN; ?> texto" value="<?php echo e($datosComentario['nombre']); ?>">
                        <span class="icon is-small is-left"><i class="fa-solid fa-user" aria-hidden="true"></i></span>
                      </div>
                      <?php echo ayuda_campo('comentario', 'nombre', $errores); ?>
                    </div>
                  </div>
                  <div class="column is-6 pb-0">
                    <div class="field">
                      <label class="label es-requerido" for="comentario-email">Correo electrónico</label>
                      <div class="control has-icons-left">
                        <input class="input <?php echo clase_error('email', $errores); ?>" type="email" <?php echo attrs_campo('comentario', 'email', $errores); ?>
                               autocomplete="email" maxlength="150" required title="Ejemplo: nombre@correo.cl"
                               data-validar="requerido email" value="<?php echo e($datosComentario['email']); ?>">
                        <span class="icon is-small is-left"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span>
                      </div>
                      <?php echo ayuda_campo('comentario', 'email', $errores); ?>
                    </div>
                  </div>
                </div>

                <div class="field">
                  <label class="label es-requerido" for="comentario-texto">Comentario</label>
                  <div class="control">
                    <textarea class="textarea <?php echo clase_error('texto', $errores); ?>" rows="4" <?php echo attrs_campo('comentario', 'texto', $errores); ?>
                              minlength="<?php echo $minT; ?>" maxlength="<?php echo $maxT; ?>" required
                              placeholder="Escribe tu opinión con respeto…"
                              data-validar="requerido min:<?php echo $minT; ?>" data-contador-caracteres><?php echo e($datosComentario['texto']); ?></textarea>
                  </div>
                  <?php echo ayuda_campo('comentario', 'texto', $errores); ?>
                </div>

                <div class="field">
                  <label class="checkbox" for="comentario-normas">
                    <input type="checkbox" id="comentario-normas" name="normas" value="1" required aria-describedby="ayuda-comentario-normas"
                           data-validar="marcado" <?php echo isset($_POST['normas']) ? 'checked' : ''; ?>>
                    Acepto publicar con respeto, sin insultos ni datos personales de terceros.
                  </label>
                  <?php echo ayuda_campo('comentario', 'normas', $errores); ?>
                </div>

                <button class="button is-primary" type="submit">
                  <span class="icon"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></span>
                  <span>Publicar comentario</span>
                </button>
              </form>
            </div>
          </section>
        </article>

        <aside class="column is-4">
          <div class="barra-lateral">
            <?php if (!empty($relacionados)): ?>
            <div class="box">
              <h2 class="titulo-bloque is-size-6">Más de <?php echo e($articulo->getSeccion()); ?></h2>
              <ul class="lista-relacionados">
                <?php foreach ($relacionados as $r): ?>
                <li>
                  <a href="<?php echo base_url('index.php?c=articulo&a=ver&id=' . (int) $r->getId()); ?>">
                    <img src="<?php echo base_url('assets/img/' . e($r->getImagen())); ?>" alt="" width="88" height="66" loading="lazy">
                    <span><?php echo e($r->getTitulo()); ?></span>
                  </a>
                </li>
                <?php endforeach; ?>
              </ul>
              <a class="button is-light is-fullwidth mt-3" href="<?php echo $urlSeccion; ?>">Ver toda la sección <?php echo e($articulo->getSeccion()); ?></a>
            </div>
            <?php endif; ?>

            <?php if (!empty($sitios)): ?>
            <div class="box">
              <h2 class="titulo-bloque is-size-6">Sitios asociados</h2>
              <?php $sitiosCompacto = true; require __DIR__ . "/../layouts/sitios_asociados.php"; ?>
            </div>
            <?php endif; ?>
          </div>
        </aside>
      </div>
    </div>
  </section>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
