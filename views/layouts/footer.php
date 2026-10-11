  </main>

  <!-- Footer (semana 9: secciones desde MySQL, área «Redacción» y boletín funcional) -->
  <?php $seccionesPie = layout("secciones") ?? []; ?>
  <footer class="footer footer-grande has-background-dark has-text-white">
    <div class="container">
      <div class="columns is-multiline">
        <div class="column is-4-desktop is-12-tablet">
          <p class="title is-4 has-text-white">
            <img src="<?php echo base_url('assets/media/logo-faro.svg'); ?>" alt="" width="28" height="28" class="mr-2 logo-pie">
            El Faro
          </p>
          <p>Diario digital de Viña del Mar. Información local, regional y nacional con foco en la comunidad.</p>
          <div class="buttons mt-4">
            <a class="button is-light is-outlined" href="#" aria-label="Facebook de El Faro"><span class="icon"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></span></a>
            <a class="button is-light is-outlined" href="#" aria-label="Instagram de El Faro"><span class="icon"><i class="fa-brands fa-instagram" aria-hidden="true"></i></span></a>
            <a class="button is-light is-outlined" href="#" aria-label="X de El Faro"><span class="icon"><i class="fa-brands fa-x-twitter" aria-hidden="true"></i></span></a>
          </div>
        </div>
        <div class="column is-2-desktop is-4-tablet is-6-mobile">
          <h2 class="title is-6 has-text-white">Secciones</h2>
          <ul class="lista-pie">
            <?php foreach ($seccionesPie as [$sec, $totalSec]): ?>
              <li><a href="<?php echo base_url('index.php?c=seccion&a=ver&s=' . rawurlencode($sec->getSlug())); ?>"><?php echo e($sec->getNombre()); ?></a></li>
            <?php endforeach; ?>
            <li><a href="<?php echo base_url('index.php?c=articulo&a=index'); ?>">Todas las noticias</a></li>
          </ul>
        </div>
        <div class="column is-2-desktop is-4-tablet is-6-mobile">
          <h2 class="title is-6 has-text-white">Redacción</h2>
          <ul class="lista-pie">
            <li><a href="<?php echo base_url('index.php?c=articulo&a=nuevo'); ?>">Publicar artículo</a></li>
            <li><a href="<?php echo base_url('index.php?c=usuario&a=listado'); ?>">Lectores registrados</a></li>
            <li><a href="<?php echo base_url('index.php?c=contacto&a=index'); ?>">Contacto</a></li>
          </ul>
        </div>
        <div class="column is-4-desktop is-4-tablet is-12-mobile">
          <h2 class="title is-6 has-text-white" id="boletin-titulo">Boletín semanal</h2>
          <p class="is-size-7 mb-2">Lo más importante de la semana en tu correo. Te pediremos crear una cuenta gratuita.</p>
          <form method="get" action="<?php echo base_url('index.php'); ?>" aria-labelledby="boletin-titulo">
            <input type="hidden" name="c" value="usuario">
            <input type="hidden" name="a" value="registro">
            <div class="field has-addons">
              <div class="control is-expanded">
                <label class="is-sr-only" for="boletin-email">Correo para el boletín</label>
                <input class="input" type="email" id="boletin-email" name="email" placeholder="tucorreo@ejemplo.cl"
                       maxlength="150" required title="Ingresa un correo válido, por ejemplo nombre@correo.cl">
              </div>
              <div class="control">
                <button class="button is-warning" type="submit">Suscribir</button>
              </div>
            </div>
          </form>
        </div>
      </div>
      <hr class="has-background-grey">
      <div class="has-text-centered">
        <p>&copy; 2026 Diario El Faro · Viña del Mar, Chile. Todos los derechos reservados.</p>
      </div>
    </div>
  </footer>

  <!-- Lógica del sitio: aviso, menú, imágenes, validaciones, contadores -->
  <script src="<?php echo base_url('assets/js/faro.js'); ?>"></script>
</body>
</html>
