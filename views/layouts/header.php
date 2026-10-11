<?php
/**
 * VISTA (parcial): cabecera común.
 * Semana 9 (usabilidad y UX):
 *   - Aviso superior que se puede cerrar (recuerda la elección del lector).
 *   - Barra principal con buscador siempre visible y solo dos acciones
 *     (Contacto y Crear cuenta).
 *   - Barra de secciones generada desde MySQL: todas las secciones a un
 *     clic (antes Actualidad y Cultura estaban ocultas en «Más»).
 *   - Las funciones internas (Publicar, Lectores) salen del menú público
 *     y pasan al pie de página, bajo «Redacción».
 */
$sa = $seccionActiva ?? "";
$seccionesMenu = layout("secciones") ?? [];
$lector = Sesion::usuario();
$qActual = get_busqueda("q");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo e($tituloPagina ?? ""); ?> — <?php echo NOMBRE_SITIO; ?></title>
  <meta name="description" content="Diario El Faro: noticias de Viña del Mar y la región de Valparaíso.">
  <!-- Framework UI: Bulma + Font Awesome -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?php echo base_url('assets/css/estilos.css'); ?>">
</head>
<body data-base="<?php echo e(base_url()); ?>">
  <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>

  <!-- Aviso superior: se puede cerrar (faro.js recuerda la elección) -->
  <div class="aviso-superior" id="aviso-superior" data-aviso="aniversario-2026">
    <div class="container aviso-contenido">
      <p class="aviso-texto">
        <span class="icon"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i></span>
        <span><strong>Aviso:</strong> Edición especial de aniversario disponible este fin de semana.</span>
      </p>
      <p class="aviso-fecha is-hidden-mobile">
        <span class="icon"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span>
        <time id="fecha-hora" datetime="<?php echo date('c'); ?>"><?php echo e(fecha_legible(date('Y-m-d H:i:s'))); ?></time>
      </p>
      <button class="delete is-small aviso-cerrar" type="button" aria-label="Cerrar aviso"></button>
    </div>
  </div>

  <!-- Barra principal: marca + buscador + acciones -->
  <header class="cabecera">
    <nav class="navbar is-dark" role="navigation" aria-label="menú principal">
      <div class="container">
        <div class="navbar-brand">
          <a class="navbar-item marca" href="<?php echo base_url('index.php'); ?>">
            <img src="<?php echo base_url('assets/media/logo-faro.svg'); ?>" alt="" width="34" height="34">
            <span class="marca-texto">El Faro<small>Viña del Mar</small></span>
          </a>
          <a class="navbar-item is-hidden-desktop boton-buscar-movil" href="#buscador" aria-label="Buscar noticias">
            <span class="icon"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span>
          </a>
          <a role="button" class="navbar-burger" aria-label="Abrir menú" aria-expanded="false" aria-controls="menuPrincipal" data-target="menuPrincipal">
            <span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span>
          </a>
        </div>
        <div id="menuPrincipal" class="navbar-menu">
          <div class="navbar-start">
            <form class="navbar-item buscador" id="buscador" role="search" method="get" action="<?php echo base_url('index.php'); ?>">
              <input type="hidden" name="c" value="articulo">
              <input type="hidden" name="a" value="buscar">
              <label class="is-sr-only" for="buscador-q">Buscar noticias</label>
              <div class="field has-addons mb-0">
                <div class="control has-icons-left is-expanded">
                  <input class="input" type="search" id="buscador-q" name="q" value="<?php echo e($qActual); ?>"
                         placeholder="Buscar noticias, temas o etiquetas" minlength="3" maxlength="60" required
                         title="Escribe al menos 3 caracteres" autocomplete="off">
                  <span class="icon is-left"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span>
                </div>
                <div class="control">
                  <button class="button is-warning" type="submit">Buscar</button>
                </div>
              </div>
            </form>
          </div>
          <div class="navbar-end">
            <a class="navbar-item <?php echo $sa==='contacto'?'is-active':''; ?>" href="<?php echo base_url('index.php?c=contacto&a=index'); ?>" <?php echo $sa==='contacto'?'aria-current="page"':''; ?>>
              <span class="icon-text"><span class="icon"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span><span>Contacto</span></span>
            </a>
            <?php if ($lector !== null): ?>
            <div class="navbar-item lector-activo">
              <span class="icon-text has-text-white">
                <span class="icon"><i class="fa-solid fa-circle-user" aria-hidden="true"></i></span>
                <span>Hola, <?php echo e($lector["nombre"]); ?></span>
              </span>
            </div>
            <div class="navbar-item">
              <!-- Cerrar sesión: POST + token CSRF (no un enlace GET) -->
              <form method="post" action="<?php echo base_url('index.php?c=usuario&a=salir'); ?>">
                <?php echo csrf_campo(); ?>
                <button class="button is-light is-outlined is-rounded" type="submit">
                  <span class="icon"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i></span>
                  <span>Salir</span>
                </button>
              </form>
            </div>
            <?php else: ?>
            <a class="navbar-item <?php echo $sa==='ingresar'?'is-active':''; ?>" href="<?php echo base_url('index.php?c=usuario&a=ingresar'); ?>">
              <span class="icon-text"><span class="icon"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i></span><span>Ingresar</span></span>
            </a>
            <div class="navbar-item">
              <a class="button is-warning is-rounded" href="<?php echo base_url('index.php?c=usuario&a=registro'); ?>">
                <span class="icon"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></span>
                <span>Crear cuenta</span>
              </a>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </nav>

    <!-- Barra de secciones (desde la tabla seccion). En móviles se desliza horizontalmente. -->
    <nav class="barra-secciones" aria-label="secciones del diario">
      <div class="container">
        <ul>
          <li><a class="<?php echo $sa==='inicio'?'is-active':''; ?>" href="<?php echo base_url('index.php'); ?>" <?php echo $sa==='inicio'?'aria-current="page"':''; ?>>
            <span class="icon is-small"><i class="fa-solid fa-house" aria-hidden="true"></i></span> Portada</a></li>
          <li><a class="<?php echo $sa==='noticias'?'is-active':''; ?>" href="<?php echo base_url('index.php?c=articulo&a=index'); ?>" <?php echo $sa==='noticias'?'aria-current="page"':''; ?>>Todas</a></li>
          <?php foreach ($seccionesMenu as [$sec, $totalSec]): $activa = $sa === $sec->getSlug(); ?>
          <li><a class="seccion-<?php echo e($sec->getSlug()); ?> <?php echo $activa?'is-active':''; ?>"
                 href="<?php echo base_url('index.php?c=seccion&a=ver&s=' . rawurlencode($sec->getSlug())); ?>"
                 <?php echo $activa?'aria-current="page"':''; ?>><?php echo e($sec->getNombre()); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </nav>
  </header>

  <main id="contenido" tabindex="-1">
