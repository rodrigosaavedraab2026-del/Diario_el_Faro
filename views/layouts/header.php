<?php
/** VISTA (parcial): cabecera común — aviso superior + navbar responsivo (Bulma). */
$sa = $seccionActiva ?? "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo e($tituloPagina ?? ""); ?> — <?php echo NOMBRE_SITIO; ?></title>
  <!-- Framework UI: Bulma + Font Awesome -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?php echo base_url('assets/css/estilos.css'); ?>">
</head>
<body data-base="<?php echo e(base_url()); ?>">
  <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>

  <!-- Aviso superior + reloj en vivo -->
  <div class="notification is-warning mb-0 aviso-superior">
    <div class="container">
      <div class="level is-mobile">
        <div class="level-left">
          <span class="icon-text">
            <span class="icon"><i class="fa-solid fa-bullhorn"></i></span>
            <span><strong>Aviso:</strong> Edición especial de aniversario disponible este fin de semana.</span>
          </span>
        </div>
        <div class="level-right">
          <span class="icon-text reloj">
            <span class="icon"><i class="fa-solid fa-clock"></i></span>
            <time id="fecha-hora" datetime="2026-01-01T00:00:00" aria-live="polite">Cargando hora...</time>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Navbar responsivo -->
  <nav class="navbar is-dark" role="navigation" aria-label="menú principal">
    <div class="container">
      <div class="navbar-brand">
        <a class="navbar-item" href="<?php echo base_url('index.php'); ?>">
          <img src="<?php echo base_url('assets/media/logo-faro.svg'); ?>" alt="Logotipo El Faro" width="32" height="32">
          <span class="title is-4 has-text-white mb-0 ml-2">El Faro</span>
        </a>
        <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="menuPrincipal">
          <span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span>
        </a>
      </div>
      <div id="menuPrincipal" class="navbar-menu">
        <div class="navbar-start">
          <a class="navbar-item <?php echo $sa==='inicio'?'is-active':''; ?>" href="<?php echo base_url('index.php'); ?>" <?php echo $sa==='inicio'?'aria-current="page"':''; ?>>Inicio</a>
          <a class="navbar-item <?php echo $sa==='noticias'?'is-active':''; ?>" href="<?php echo base_url('index.php?c=articulo&a=index'); ?>" <?php echo $sa==='noticias'?'aria-current="page"':''; ?>>Noticias</a>
          <a class="navbar-item <?php echo $sa==='deporte'?'is-active':''; ?>" href="<?php echo base_url('index.php?c=seccion&a=deporte'); ?>" <?php echo $sa==='deporte'?'aria-current="page"':''; ?>>Deporte</a>
          <a class="navbar-item <?php echo $sa==='negocios'?'is-active':''; ?>" href="<?php echo base_url('index.php?c=seccion&a=negocios'); ?>" <?php echo $sa==='negocios'?'aria-current="page"':''; ?>>Negocios</a>
          <a class="navbar-item <?php echo $sa==='contacto'?'is-active':''; ?>" href="<?php echo base_url('index.php?c=contacto&a=index'); ?>" <?php echo $sa==='contacto'?'aria-current="page"':''; ?>>Contacto</a>
          <div class="navbar-item has-dropdown is-hoverable">
            <a class="navbar-link" href="#" role="button" aria-haspopup="true">Más</a>
            <div class="navbar-dropdown">
              <a class="navbar-item" href="<?php echo base_url('index.php?c=seccion&a=ver&s=actualidad'); ?>">Actualidad</a>
              <a class="navbar-item" href="<?php echo base_url('index.php?c=seccion&a=ver&s=cultura'); ?>">Cultura</a>
              <hr class="navbar-divider">
              <a class="navbar-item <?php echo $sa==='publicar'?'is-active':''; ?>" href="<?php echo base_url('index.php?c=articulo&a=nuevo'); ?>">
                <span class="icon-text"><span class="icon"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></span><span>Publicar artículo</span></span>
              </a>
              <a class="navbar-item <?php echo $sa==='lectores'?'is-active':''; ?>" href="<?php echo base_url('index.php?c=usuario&a=listado'); ?>">
                <span class="icon-text"><span class="icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span><span>Lectores registrados</span></span>
              </a>
            </div>
          </div>
        </div>
        <div class="navbar-end">
          <div class="navbar-item">
            <a class="button is-warning is-rounded" href="<?php echo base_url('index.php?c=usuario&a=registro'); ?>">
              <span class="icon"><i class="fa-solid fa-user-plus"></i></span>
              <span>Crear cuenta</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <main id="contenido" tabindex="-1">
