<?php require __DIR__ . "/../layouts/header.php"; ?>

  <section class="section">
    <div class="container">
      <nav class="breadcrumb" aria-label="ruta de navegación">
        <ul>
          <li><a href="<?php echo base_url('index.php'); ?>">Portada</a></li>
          <li><a href="<?php echo base_url('index.php?c=articulo&a=index'); ?>">Noticias</a></li>
          <li class="is-active"><a href="#" aria-current="page">Publicar artículo</a></li>
        </ul>
      </nav>

      <h1 class="title is-3">Publicar artículo</h1>
      <p class="subtitle is-5">El artículo se registra en la tabla <code>articulo</code> de MySQL.</p>

      <div class="columns">
        <div class="column is-8">
          <div class="box">
            <p class="is-size-7 mb-4 has-text-grey">Los campos con <span class="marca-requerido" aria-hidden="true">*</span> son obligatorios.</p>
            <div id="mensaje-articulo" class="notification is-danger <?php echo empty($errores) ? 'is-hidden' : ''; ?>" role="alert">
              <?php echo e($errores['general'] ?? 'Revisa los campos marcados en rojo.'); ?>
            </div>

            <form id="form-articulo" method="post" action="<?php echo base_url('index.php?c=articulo&a=guardar'); ?>" data-validar-form data-mensaje="mensaje-articulo">
              <?php echo csrf_campo(); ?>

              <div class="field">
                <label class="label es-requerido" for="articulo-titulo">Título</label>
                <div class="control">
                  <input class="input <?php echo clase_error('titulo', $errores); ?>" type="text" <?php echo attrs_campo('articulo', 'titulo', $errores); ?>
                         minlength="10" maxlength="200" required data-validar="requerido min:10" data-contador-caracteres value="<?php echo e($datos['titulo']); ?>">
                </div>
                <?php echo ayuda_campo('articulo', 'titulo', $errores); ?>
              </div>

              <div class="field">
                <label class="label es-requerido" for="articulo-resumen">Resumen (bajada)</label>
                <div class="control">
                  <input class="input <?php echo clase_error('resumen', $errores); ?>" type="text" <?php echo attrs_campo('articulo', 'resumen', $errores); ?>
                         minlength="20" maxlength="300" required data-validar="requerido min:20" data-contador-caracteres value="<?php echo e($datos['resumen']); ?>">
                </div>
                <?php echo ayuda_campo('articulo', 'resumen', $errores); ?>
              </div>

              <div class="field">
                <label class="label es-requerido" for="articulo-cuerpo">Cuerpo de la noticia</label>
                <div class="control">
                  <textarea class="textarea <?php echo clase_error('cuerpo', $errores); ?>" rows="8" <?php echo attrs_campo('articulo', 'cuerpo', $errores); ?>
                            minlength="50" maxlength="10000" required data-validar="requerido min:50" data-contador-caracteres><?php echo e($datos['cuerpo']); ?></textarea>
                </div>
                <p class="help">Puedes escribir texto plano (separa los párrafos con una línea en blanco) o usar HTML básico.</p>
                <details class="politica-html">
                  <summary>Ver el HTML permitido en el cuerpo</summary>
                  <ul>
                    <li><code>&lt;p&gt;</code>, <code>&lt;br&gt;</code>, <code>&lt;strong&gt;</code>, <code>&lt;em&gt;</code>, <code>&lt;h3&gt;</code>, <code>&lt;h4&gt;</code>, <code>&lt;ul&gt;</code>, <code>&lt;ol&gt;</code>, <code>&lt;li&gt;</code>, <code>&lt;blockquote&gt;</code></li>
                    <li><code>&lt;a href="https://…"&gt;</code> (también <code>mailto:</code>); los enlaces externos se abren en otra pestaña</li>
                    <li><code>&lt;figure&gt;</code> y <code>&lt;figcaption&gt;</code>; <code>&lt;img src="nota1.jpg" alt="Descripción"&gt;</code> (imágenes del sitio o https)</li>
                    <li><code>&lt;iframe src="https://www.youtube.com/embed/ID"&gt;</code> solo para videos de YouTube</li>
                    <li>No se permiten scripts, estilos, formularios, atributos <code>on…</code>, <code>style</code> ni <code>class</code>.</li>
                  </ul>
                </details>
                <?php echo ayuda_campo('articulo', 'cuerpo', $errores); ?>
              </div>

              <div class="columns">
                <div class="column is-4">
                  <div class="field">
                    <label class="label es-requerido" for="articulo-id_seccion">Sección</label>
                    <div class="control">
                      <div class="select is-fullwidth <?php echo clase_error('id_seccion', $errores); ?>">
                        <select <?php echo attrs_campo('articulo', 'id_seccion', $errores); ?> required data-validar="requerido">
                          <option value="">Selecciona…</option>
                          <?php foreach ($secciones as $s): ?>
                            <option value="<?php echo (int) $s->getId(); ?>" <?php echo (string) $datos['id_seccion'] === (string) $s->getId() ? 'selected' : ''; ?>><?php echo e($s->getNombre()); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <?php echo ayuda_campo('articulo', 'id_seccion', $errores); ?>
                  </div>
                </div>
                <div class="column is-4">
                  <div class="field">
                    <label class="label es-requerido" for="articulo-id_autor">Autor</label>
                    <div class="control">
                      <div class="select is-fullwidth <?php echo clase_error('id_autor', $errores); ?>">
                        <select <?php echo attrs_campo('articulo', 'id_autor', $errores); ?> required data-validar="requerido">
                          <option value="">Selecciona…</option>
                          <?php foreach ($autores as $au): ?>
                            <option value="<?php echo (int) $au->getId(); ?>" <?php echo (string) $datos['id_autor'] === (string) $au->getId() ? 'selected' : ''; ?>><?php echo e($au->getNombre()); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <?php echo ayuda_campo('articulo', 'id_autor', $errores); ?>
                  </div>
                </div>
                <div class="column is-4">
                  <div class="field">
                    <label class="label es-requerido" for="articulo-etiqueta">Etiqueta</label>
                    <div class="control">
                      <input class="input <?php echo clase_error('etiqueta', $errores); ?>" type="text" <?php echo attrs_campo('articulo', 'etiqueta', $errores); ?>
                             minlength="3" maxlength="40" placeholder="Ej.: Fútbol" required data-validar="requerido min:3" value="<?php echo e($datos['etiqueta']); ?>">
                    </div>
                    <?php echo ayuda_campo('articulo', 'etiqueta', $errores); ?>
                  </div>
                </div>
              </div>

              <div class="columns">
                <div class="column is-6">
                  <div class="field">
                    <label class="label" for="articulo-imagen">Imagen</label>
                    <div class="control">
                      <div class="select is-fullwidth <?php echo clase_error('imagen', $errores); ?>">
                        <select <?php echo attrs_campo('articulo', 'imagen', $errores); ?> data-vista-previa="vista-previa-imagen">
                          <?php foreach ($imagenes as $img): ?>
                            <option value="<?php echo e($img); ?>" <?php echo $datos['imagen'] === $img ? 'selected' : ''; ?>><?php echo e($img); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <?php echo ayuda_campo('articulo', 'imagen', $errores); ?>
                  </div>
                  <!-- Vista previa: el redactor ve la foto, no solo el nombre del archivo -->
                  <figure class="image is-16by9 vista-previa mt-2">
                    <img id="vista-previa-imagen" src="<?php echo base_url('assets/img/' . e($datos['imagen'])); ?>" alt="Vista previa de la imagen seleccionada">
                  </figure>
                </div>
                <div class="column is-6">
                  <label class="checkbox" for="articulo-destacado">
                    <input type="checkbox" id="articulo-destacado" name="destacado" value="1" <?php echo !empty($datos['destacado']) ? 'checked' : ''; ?>>
                    Mostrar como destacado en la portada
                  </label>
                </div>
              </div>

              <div class="field">
                <div class="control">
                  <button class="button is-primary" type="submit">
                    <span class="icon"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i></span>
                    <span>Publicar artículo</span>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <div class="column is-4">
          <div class="box">
            <h2 class="title is-5">¿Qué pasa al publicar?</h2>
            <ol class="ml-4">
              <li class="mb-2">El Controlador valida los datos y el token CSRF.</li>
              <li class="mb-2">El Modelo llama, con PDO, al procedimiento almacenado <code>sp_articulo_registrar</code>, que guarda el artículo dentro de una transacción.</li>
              <li class="mb-2">Un <em>trigger</em> registra la publicación en la tabla <code>bitacora</code>.</li>
              <li>Se abre el artículo recién creado.</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
  </section>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
