<?php require __DIR__ . "/../layouts/header.php"; ?>

  <section class="section">
    <div class="container">
      <nav class="breadcrumb" aria-label="ruta de navegación">
        <ul>
          <li><a href="<?php echo base_url('index.php'); ?>">Portada</a></li>
          <li class="is-active"><a href="#" aria-current="page">Crear cuenta</a></li>
        </ul>
      </nav>

      <h1 class="title is-3">Crea tu cuenta de lector</h1>
      <p class="subtitle is-5">Regístrate gratis y accede a las suscripciones del diario virtual.</p>

      <div class="columns">
        <div class="column is-8">
          <div class="box">
            <p class="is-size-7 mb-4 has-text-grey">Los campos con <span class="marca-requerido" aria-hidden="true">*</span> son obligatorios.</p>
            <div id="mensaje-registro" class="notification is-danger <?php echo empty($errores) ? 'is-hidden' : ''; ?>" role="alert">
              <?php echo e($errores['general'] ?? 'Revisa los campos marcados en rojo.'); ?>
            </div>

            <form id="form-registro" method="post" action="<?php echo base_url('index.php?c=usuario&a=guardar'); ?>" data-validar-form data-mensaje="mensaje-registro">
              <?php echo csrf_campo(); ?>
              <div class="columns">
                <div class="column is-6">
                  <div class="field">
                    <label class="label es-requerido" for="registro-nombre">Nombre</label>
                    <div class="control has-icons-left">
                      <input class="input <?php echo clase_error('nombre', $errores); ?>" type="text" <?php echo attrs_campo('registro', 'nombre', $errores); ?>
                             autocomplete="given-name" minlength="2" maxlength="80" required pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ' \-]{2,80}" title="Solo letras y espacios (mínimo 2)" data-validar="requerido min:2 texto"
                             value="<?php echo e($datos['nombre']); ?>">
                      <span class="icon is-small is-left"><i class="fa-solid fa-user" aria-hidden="true"></i></span>
                    </div>
                    <?php echo ayuda_campo('registro', 'nombre', $errores); ?>
                  </div>
                </div>
                <div class="column is-6">
                  <div class="field">
                    <label class="label es-requerido" for="registro-apellido">Apellido</label>
                    <div class="control has-icons-left">
                      <input class="input <?php echo clase_error('apellido', $errores); ?>" type="text" <?php echo attrs_campo('registro', 'apellido', $errores); ?>
                             autocomplete="family-name" minlength="2" maxlength="80" required pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ' \-]{2,80}" title="Solo letras y espacios (mínimo 2)" data-validar="requerido min:2 texto"
                             value="<?php echo e($datos['apellido']); ?>">
                      <span class="icon is-small is-left"><i class="fa-solid fa-user" aria-hidden="true"></i></span>
                    </div>
                    <?php echo ayuda_campo('registro', 'apellido', $errores); ?>
                  </div>
                </div>
              </div>

              <div class="field">
                <label class="label es-requerido" for="registro-email">Correo electrónico</label>
                <div class="control has-icons-left">
                  <input class="input <?php echo clase_error('email', $errores); ?>" type="email" <?php echo attrs_campo('registro', 'email', $errores); ?>
                         autocomplete="email" maxlength="150" required title="Ejemplo: nombre@correo.cl" data-validar="requerido email"
                         value="<?php echo e($datos['email']); ?>">
                  <span class="icon is-small is-left"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span>
                </div>
                <?php echo ayuda_campo('registro', 'email', $errores); ?>
              </div>

              <div class="columns">
                <div class="column is-6">
                  <div class="field">
                    <label class="label es-requerido" for="registro-password">Contraseña</label>
                    <div class="control has-icons-left">
                      <input class="input <?php echo clase_error('password', $errores); ?>" type="password" <?php echo attrs_campo('registro', 'password', $errores); ?>
                             autocomplete="new-password" minlength="8" maxlength="72" required title="Mínimo 8 caracteres" data-validar="requerido min:8">
                      <span class="icon is-small is-left"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                    </div>
                    <p class="help">Mínimo 8 caracteres.</p>
                    <?php echo ayuda_campo('registro', 'password', $errores); ?>
                  </div>
                </div>
                <div class="column is-6">
                  <div class="field">
                    <label class="label es-requerido" for="registro-password2">Repetir contraseña</label>
                    <div class="control has-icons-left">
                      <input class="input <?php echo clase_error('password2', $errores); ?>" type="password" <?php echo attrs_campo('registro', 'password2', $errores); ?>
                             autocomplete="new-password" maxlength="72" required data-validar="requerido igual:registro-password">
                      <span class="icon is-small is-left"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                    </div>
                    <?php echo ayuda_campo('registro', 'password2', $errores); ?>
                  </div>
                </div>
              </div>

              <div class="columns">
                <div class="column is-4">
                  <div class="field">
                    <label class="label" for="registro-pais">País</label>
                    <div class="control">
                      <input class="input <?php echo clase_error('pais', $errores); ?>" type="text" <?php echo attrs_campo('registro', 'pais', $errores); ?>
                             autocomplete="country-name" maxlength="80" pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ' \-]{2,80}" title="Solo letras y espacios" data-validar="texto" value="<?php echo e($datos['pais']); ?>">
                    </div>
                    <?php echo ayuda_campo('registro', 'pais', $errores); ?>
                  </div>
                </div>
                <div class="column is-4">
                  <div class="field">
                    <label class="label" for="registro-ciudad">Ciudad</label>
                    <div class="control">
                      <input class="input <?php echo clase_error('ciudad', $errores); ?>" type="text" <?php echo attrs_campo('registro', 'ciudad', $errores); ?>
                             autocomplete="address-level2" maxlength="80" pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ' \-]{2,80}" title="Solo letras y espacios" data-validar="texto" value="<?php echo e($datos['ciudad']); ?>">
                    </div>
                    <?php echo ayuda_campo('registro', 'ciudad', $errores); ?>
                  </div>
                </div>
                <div class="column is-4">
                  <div class="field">
                    <label class="label" for="registro-plan">Plan</label>
                    <div class="control">
                      <div class="select is-fullwidth">
                        <select <?php echo attrs_campo('registro', 'plan', $errores); ?>>
                          <?php foreach (UsuarioController::PLANES as $k => $v): ?>
                            <option value="<?php echo e($k); ?>" <?php echo $datos['plan'] === $k ? 'selected' : ''; ?>><?php echo e($v); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <?php echo ayuda_campo('registro', 'plan', $errores); ?>
                  </div>
                </div>
              </div>

              <div class="field">
                <div class="control">
                  <button class="button is-primary" type="submit">
                    <span class="icon"><i class="fa-solid fa-user-check" aria-hidden="true"></i></span>
                    <span>Registrarme</span>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <div class="column is-4">
          <div class="box">
            <h2 class="title is-5">Beneficios de tu cuenta</h2>
            <p class="mb-2"><span class="icon-text"><span class="icon has-text-primary"><i class="fa-solid fa-check" aria-hidden="true"></i></span><span>Guarda tus noticias favoritas</span></span></p>
            <p class="mb-2"><span class="icon-text"><span class="icon has-text-primary"><i class="fa-solid fa-check" aria-hidden="true"></i></span><span>Boletín con lo más importante</span></span></p>
            <p><span class="icon-text"><span class="icon has-text-primary"><i class="fa-solid fa-check" aria-hidden="true"></i></span><span>Acceso a suscripciones y planes</span></span></p>
          </div>
        </div>
      </div>
    </div>
  </section>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
