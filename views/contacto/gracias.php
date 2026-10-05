<?php require __DIR__ . "/../layouts/header.php"; ?>

  <section class="section">
    <div class="container">
      <div class="columns is-centered">
        <div class="column is-7">
          <div class="box has-text-centered">
            <span class="icon has-text-success" style="font-size:3rem;"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
            <h1 class="title is-4 mt-3">¡Gracias por escribirnos, <?php echo e($datos['nombre']); ?>!</h1>
            <p class="subtitle is-6">
              Recibimos tu mensaje sobre <strong><?php echo e($datos['asunto']); ?></strong>
              y responderemos a <strong><?php echo e($datos['email']); ?></strong> a la brevedad.
            </p>
            <p class="is-size-7 has-text-grey mb-3">
              Mensaje registrado con el número #<?php echo (int) $contacto->getId(); ?>.
              <?php if ($contacto->getIdUsuario()): ?>Quedó asociado a tu cuenta de lector.<?php endif; ?>
            </p>
            <a href="<?php echo base_url('index.php'); ?>" class="button is-primary">Volver a la portada</a>
          </div>
        </div>
      </div>
    </div>
  </section>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
