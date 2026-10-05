<?php require __DIR__ . "/../layouts/header.php"; ?>

  <section class="section">
    <div class="container">
      <nav class="breadcrumb" aria-label="ruta de navegación">
        <ul>
          <li><a href="<?php echo base_url('index.php'); ?>">Inicio</a></li>
          <li class="is-active"><a href="#" aria-current="page">Lectores registrados</a></li>
        </ul>
      </nav>

      <div class="level mb-4">
        <div class="level-left">
          <div>
            <h1 class="title is-3 mb-2">Lectores registrados</h1>
            <p class="has-text-grey">Usuarios creados desde el formulario «Crear cuenta» (tablas <code>usuario</code> y <code>localidad</code>).</p>
          </div>
        </div>
        <div class="level-right">
          <a class="button is-warning" href="<?php echo base_url('index.php?c=usuario&a=registro'); ?>">
            <span class="icon"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></span>
            <span>Crear cuenta</span>
          </a>
        </div>
      </div>

      <?php if (empty($usuarios)): ?>
        <div class="notification is-light">Todavía no hay lectores registrados.</div>
      <?php else: ?>
      <div class="table-container box p-0">
        <table class="table is-fullwidth is-striped is-hoverable tabla-lectores">
          <caption class="is-sr-only">Lectores registrados en El Faro</caption>
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Nombre</th>
              <th scope="col">Correo</th>
              <th scope="col">Localidad</th>
              <th scope="col">Plan</th>
              <th scope="col">Mensajes</th>
              <th scope="col">Registro</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usuarios as $u): $loc = $u->getLocalidad(); ?>
            <tr>
              <td data-titulo="#"><?php echo (int) $u->getId(); ?></td>
              <td data-titulo="Nombre"><?php echo e($u->getNombreCompleto()); ?></td>
              <td data-titulo="Correo"><?php echo e($u->getEmail()); ?></td>
              <td data-titulo="Localidad"><?php echo $loc ? e($loc->getCiudad() . ", " . $loc->getPais()) : "—"; ?></td>
              <td data-titulo="Plan"><span class="tag is-light"><?php echo e($planes[$u->getPlan()] ?? $u->getPlan()); ?></span></td>
              <td data-titulo="Mensajes"><?php echo (int) $u->getMensajes(); ?></td>
              <td data-titulo="Registro"><?php echo e(fecha_legible($u->getFechaRegistro())); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <?php $etiquetaElementos = "lectores"; require __DIR__ . "/../layouts/paginacion.php"; ?>
      <p class="is-size-7 has-text-grey mt-3">
        <span class="icon-text"><span class="icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span>
        <span>Las contraseñas se guardan cifradas (bcrypt) y nunca se muestran ni se consultan en este listado.</span></span>
      </p>
    </div>
  </section>

<?php require __DIR__ . "/../layouts/footer.php"; ?>
