<?php require __DIR__ . "/../layouts/header.php"; ?>

  <section class="section">
    <div class="container">
      <nav class="breadcrumb" aria-label="ruta de navegación">
        <ul>
          <li><a href="<?php echo base_url('index.php'); ?>">Portada</a></li>
          <li class="is-active"><a href="#" aria-current="page">Contacto</a></li>
        </ul>
      </nav>

      <h1 class="title is-3">Contacto</h1>
      <p class="subtitle is-5">¿Tienes una noticia o comentario? Escríbenos.</p>

      <div class="columns">
        <div class="column is-7">
          <div class="box">
            <p class="is-size-7 mb-4 has-text-grey">Los campos con <span class="marca-requerido" aria-hidden="true">*</span> son obligatorios.</p>
            <div id="mensaje-contacto" class="notification is-danger <?php echo empty($errores) ? 'is-hidden' : ''; ?>" role="alert">
              <?php echo e($errores['general'] ?? 'Revisa los campos marcados en rojo.'); ?>
            </div>

            <!-- El action apunta al Controlador (acción enviar); el mensaje se guarda en la tabla contacto -->
            <form id="form-contacto" method="post" action="<?php echo base_url('index.php?c=contacto&a=enviar'); ?>" data-validar-form data-mensaje="mensaje-contacto">
              <?php echo csrf_campo(); ?>

              <div class="field">
                <label class="label es-requerido" for="contacto-nombre">Nombre</label>
                <div class="control has-icons-left">
                  <input class="input <?php echo clase_error('nombre', $errores); ?>" type="text" <?php echo attrs_campo('contacto', 'nombre', $errores); ?>
                         placeholder="Tu nombre" autocomplete="name" minlength="2" maxlength="120" required
                         pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ' \-]{2,120}" title="Solo letras y espacios (mínimo 2)"
                         data-validar="requerido min:2 texto" value="<?php echo e($datos['nombre']); ?>">
                  <span class="icon is-small is-left"><i class="fa-solid fa-user" aria-hidden="true"></i></span>
                </div>
                <?php echo ayuda_campo('contacto', 'nombre', $errores); ?>
              </div>

              <div class="field">
                <label class="label es-requerido" for="contacto-email">Correo electrónico</label>
                <div class="control has-icons-left">
                  <input class="input <?php echo clase_error('email', $errores); ?>" type="email" <?php echo attrs_campo('contacto', 'email', $errores); ?>
                         placeholder="tucorreo@ejemplo.cl" autocomplete="email" maxlength="150" required title="Ejemplo: nombre@correo.cl"
                         data-validar="requerido email" value="<?php echo e($datos['email']); ?>">
                  <span class="icon is-small is-left"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span>
                </div>
                <?php echo ayuda_campo('contacto', 'email', $errores); ?>
              </div>

              <div class="field">
                <label class="label" for="contacto-asunto">Asunto</label>
                <div class="control">
                  <div class="select is-fullwidth <?php echo clase_error('asunto', $errores); ?>">
                    <select <?php echo attrs_campo('contacto', 'asunto', $errores); ?>>
                      <?php foreach (Contacto::ASUNTOS as $op): ?>
                        <option value="<?php echo e($op); ?>" <?php echo $datos['asunto'] === $op ? 'selected' : ''; ?>><?php echo e($op); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <?php echo ayuda_campo('contacto', 'asunto', $errores); ?>
              </div>

              <div class="field">
                <label class="label es-requerido" for="contacto-mensaje">Mensaje</label>
                <div class="control">
                  <textarea class="textarea <?php echo clase_error('mensaje', $errores); ?>" <?php echo attrs_campo('contacto', 'mensaje', $errores); ?>
                            placeholder="Escribe tu mensaje..." minlength="10" maxlength="2000" rows="5" required
                            data-validar="requerido min:10" data-contador-caracteres><?php echo e($datos['mensaje']); ?></textarea>
                </div>
                <?php echo ayuda_campo('contacto', 'mensaje', $errores); ?>
              </div>

              <div class="field">
                <div class="control">
                  <button class="button is-primary" type="submit">Enviar mensaje</button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <div class="column is-5">
          <div class="box">
            <h2 class="title is-5">Datos de contacto</h2>
            <p class="mb-3"><span class="icon-text"><span class="icon has-text-primary"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span><span>24 Norte, Viña del Mar, Chile</span></span></p>
            <p class="mb-3"><span class="icon-text"><span class="icon has-text-primary"><i class="fa-solid fa-phone" aria-hidden="true"></i></span><span>+56 32 000 0000</span></span></p>
            <p class="mb-3"><span class="icon-text"><span class="icon has-text-primary"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span><span>contacto@elfaro.cl</span></span></p>
            <p><span class="icon-text"><span class="icon has-text-primary"><i class="fa-solid fa-clock" aria-hidden="true"></i></span><span>Lunes a viernes, 9:00 a 18:00</span></span></p>
          </div>
        </div>
      </div>
    </div>
  </section>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
