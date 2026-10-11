<?php require __DIR__ . "/../layouts/header.php"; ?>

  <section class="section">
    <div class="container">
      <div class="columns is-centered">
        <div class="column is-7">
          <div class="box has-text-centered">
            <span class="icon has-text-success icono-grande"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
            <h1 class="title is-4 mt-3">¡Cuenta creada con éxito!</h1>
            <p class="subtitle is-6">
              Bienvenido/a <strong><?php echo e($usuario->getNombreCompleto()); ?></strong>.
              Registramos tu cuenta con el correo <strong><?php echo e($usuario->getEmail()); ?></strong>
              en el plan <strong><?php echo e(ucfirst($usuario->getPlan())); ?></strong>.
            </p>
            <p class="is-size-7 has-text-grey">Tu cuenta quedó guardada en la base de datos y tu contraseña se almacenó como hash (nunca en texto plano).</p>
            <div class="buttons is-centered mt-2">
              <a href="<?php echo base_url('index.php'); ?>" class="button is-primary">Ir a la portada</a>
              <a href="<?php echo base_url('index.php?c=usuario&a=listado'); ?>" class="button is-light">Ver lectores registrados</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
