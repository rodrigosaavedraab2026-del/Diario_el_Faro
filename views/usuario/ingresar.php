<?php require __DIR__ . "/../layouts/header.php"; ?>

  <section class="section pt-5">
    <div class="container">
      <nav class="breadcrumb" aria-label="ruta de navegación">
        <ul>
          <li><a href="<?php echo base_url('index.php'); ?>">Portada</a></li>
          <li class="is-active"><a href="#" aria-current="page">Ingresar</a></li>
        </ul>
      </nav>

      <div class="columns is-centered">
        <div class="column is-5-desktop is-8-tablet">
          <h1 class="title is-3">Ingresa a tu cuenta</h1>
          <p class="subtitle is-6">Usa el correo y la contraseña con que creaste tu cuenta de lector.</p>

          <div class="box">
            <p class="is-size-7 mb-4 has-text-grey">Los campos con <span class="marca-requerido" aria-hidden="true">*</span> son obligatorios.</p>
            <div id="mensaje-ingresar" class="notification is-danger <?php echo empty($errores) ? 'is-hidden' : ''; ?>" role="alert">
              <?php echo e($errores['general'] ?? 'Revisa los campos marcados en rojo.'); ?>
            </div>

            <form id="form-ingresar" method="post" action="<?php echo base_url('index.php?c=usuario&a=ingresar'); ?>" data-validar-form data-mensaje="mensaje-ingresar">
              <?php echo csrf_campo(); ?>
              <div class="field">
                <label class="label es-requerido" for="ingresar-email">Correo electrónico</label>
                <div class="control has-icons-left">
                  <input class="input <?php echo clase_error('email', $errores); ?>" type="email" <?php echo attrs_campo('ingresar', 'email', $errores); ?>
                         autocomplete="username" maxlength="150" required title="Ejemplo: nombre@correo.cl"
                         data-validar="requerido email" value="<?php echo e($datos['email']); ?>">
                  <span class="icon is-small is-left"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span>
                </div>
                <?php echo ayuda_campo('ingresar', 'email', $errores); ?>
              </div>
              <div class="field">
                <label class="label es-requerido" for="ingresar-password">Contraseña</label>
                <div class="control has-icons-left">
                  <input class="input <?php echo clase_error('password', $errores); ?>" type="password" <?php echo attrs_campo('ingresar', 'password', $errores); ?>
                         autocomplete="current-password" maxlength="72" required data-validar="requerido">
                  <span class="icon is-small is-left"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                </div>
                <?php echo ayuda_campo('ingresar', 'password', $errores); ?>
              </div>
              <button class="button is-primary is-fullwidth" type="submit">
                <span class="icon"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i></span>
                <span>Ingresar</span>
              </button>
            </form>
            <p class="has-text-centered mt-4 is-size-7">
              ¿No tienes cuenta? <a href="<?php echo base_url('index.php?c=usuario&a=registro'); ?>">Crea una gratis</a>
            </p>
          </div>
          <p class="is-size-7 has-text-grey">
            <span class="icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span>
            Tu contraseña se compara contra un hash bcrypt; nunca se guarda ni se muestra en texto plano.
            Tras 5 intentos fallidos el formulario se bloquea por 60 segundos.
          </p>
        </div>
      </div>
    </div>
  </section>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
