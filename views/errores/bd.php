<?php require __DIR__ . "/../layouts/header.php"; ?>

  <section class="section">
    <div class="container">
      <div class="columns is-centered">
        <div class="column is-7">
          <div class="box has-text-centered">
            <span class="icon has-text-danger" style="font-size:3rem;"><i class="fa-solid fa-database" aria-hidden="true"></i></span>
            <h1 class="title is-4 mt-3">No pudimos conectar con la base de datos</h1>
            <p class="subtitle is-6">
              Verifica que MySQL esté iniciado en el panel de XAMPP y que la base
              <code>el_faro</code> haya sido importada desde <code>database/el_faro.sql</code>.
            </p>
            <?php if (!empty($detalleError)): ?>
              <div class="notification is-warning is-light has-text-left">
                <p class="has-text-weight-bold mb-1">Detalle técnico (visible solo en este equipo)</p>
                <p class="is-size-7 mb-2"><code>Error <?php echo e($detalleError["codigo"]); ?>: <?php echo e($detalleError["mensaje"]); ?></code></p>
                <?php if ($detalleError["pista"]): ?>
                  <p class="is-size-6"><strong>Qué hacer:</strong> <?php echo e($detalleError["pista"]); ?></p>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <a href="<?php echo base_url('index.php'); ?>" class="button is-primary">Reintentar</a>
          </div>
        </div>
      </div>
    </div>
  </section>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
