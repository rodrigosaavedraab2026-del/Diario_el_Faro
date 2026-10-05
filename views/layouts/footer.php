  </main>

  <!-- Footer -->
  <footer class="footer footer-grande has-background-dark has-text-white">
    <div class="container">
      <div class="columns">
        <div class="column is-4">
          <p class="title is-4 has-text-white">
            <img src="<?php echo base_url('assets/media/logo-faro.svg'); ?>" alt="" width="28" height="28" class="mr-2" style="vertical-align:middle">
            El Faro
          </p>
          <p>Diario digital de Viña del Mar. Información local, regional y nacional con foco en la comunidad.</p>
        </div>
        <div class="column is-2">
          <h2 class="title is-6 has-text-white">Secciones</h2>
          <ul>
            <li><a class="has-text-grey-light" href="<?php echo base_url('index.php?c=seccion&a=deporte'); ?>">Deporte</a></li>
            <li><a class="has-text-grey-light" href="<?php echo base_url('index.php?c=seccion&a=negocios'); ?>">Negocios</a></li>
            <li><a class="has-text-grey-light" href="<?php echo base_url('index.php?c=articulo&a=index'); ?>">Todas las noticias</a></li>
            <li><a class="has-text-grey-light" href="<?php echo base_url('index.php?c=contacto&a=index'); ?>">Contacto</a></li>
          </ul>
        </div>
        <div class="column is-2">
          <h2 class="title is-6 has-text-white">Institucional</h2>
          <ul>
            <li><a class="has-text-grey-light" href="#">Quiénes somos</a></li>
            <li><a class="has-text-grey-light" href="#">Política de cookies</a></li>
            <li><a class="has-text-grey-light" href="#">Avisos legales</a></li>
          </ul>
        </div>
        <div class="column is-4">
          <h2 class="title is-6 has-text-white">Síguenos</h2>
          <div class="buttons">
            <a class="button is-light is-outlined" href="#" aria-label="Facebook"><span class="icon"><i class="fa-brands fa-facebook-f"></i></span></a>
            <a class="button is-light is-outlined" href="#" aria-label="Instagram"><span class="icon"><i class="fa-brands fa-instagram"></i></span></a>
            <a class="button is-light is-outlined" href="#" aria-label="X"><span class="icon"><i class="fa-brands fa-x-twitter"></i></span></a>
          </div>
          <p class="mt-3" id="boletin-texto">Suscríbete al boletín:</p>
          <div class="field has-addons mt-2">
            <div class="control is-expanded">
              <input class="input" type="email" placeholder="tucorreo@ejemplo.cl" aria-label="Correo para el boletín">
            </div>
            <div class="control">
              <button class="button is-warning" type="button">Suscribir</button>
            </div>
          </div>
        </div>
      </div>
      <hr class="has-background-grey">
      <div class="has-text-centered">
        <p>&copy; 2026 Diario El Faro · Viña del Mar, Chile. Todos los derechos reservados.</p>
      </div>
    </div>
  </footer>

  <!-- Lógica del sitio: reloj, menú responsivo, manejo de imágenes, contadores -->
  <script src="<?php echo base_url('assets/js/faro.js'); ?>"></script>
</body>
</html>
