/* ============================================================
   El Faro — Lógica del sitio (JavaScript externo)
   Autor: Rodrigo Saavedra Ábalos — PRO301 AIEP
   Semana 9: aviso cerrable, validación HTML5 + mensajes en vivo,
   contador de caracteres y vista previa de imagen.

   Buenas prácticas aplicadas (recomendaciones del docente):
   - Sin manejadores "inline" (onclick/onerror): todo se enlaza
     con addEventListener desde este archivo.
   - Elemento de fecha-hora identificado con un id único (#fecha-hora).
   - Manejo de error de imágenes centralizado aquí.
   - Formularios accesibles: validación, mensajes asociados al campo
     (aria-describedby) y gestión del foco al primer error.
   - Contadores con singularización gramatical (1 artículo / 2 artículos).
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
  activarAvisoCerrable();      // semana 9
  activarMenuResponsivo();
  iniciarReloj();
  activarManejoDeImagenes();
  activarValidacionFormularios();
  activarContadorCaracteres(); // semana 9
  activarVistaPreviaImagen();  // semana 9
  actualizarContadores();
});

/* ------------------------------------------------------------
   SEMANA 9 — AVISO SUPERIOR QUE SE PUEDE CERRAR
   ------------------------------------------------------------
   El aviso ocupaba espacio fijo en todas las páginas. Ahora tiene un
   botón para cerrarlo y el navegador recuerda la elección (por cada
   aviso distinto, según data-aviso). Si el almacenamiento local no
   está disponible, el aviso simplemente se cierra en esa página.
   ------------------------------------------------------------ */
function activarAvisoCerrable() {
  const aviso = document.getElementById('aviso-superior');
  if (!aviso) return;
  const clave = 'faro-aviso-cerrado-' + (aviso.dataset.aviso || 'general');
  try {
    if (localStorage.getItem(clave) === '1') { aviso.hidden = true; return; }
  } catch (e) { /* sin almacenamiento: se muestra el aviso */ }

  const boton = aviso.querySelector('.aviso-cerrar');
  if (!boton) return;
  boton.addEventListener('click', function () {
    aviso.hidden = true;
    try { localStorage.setItem(clave, '1'); } catch (e) { /* ignorar */ }
    const principal = document.getElementById('contenido');
    if (principal) principal.focus();   // el foco no queda "perdido"
  });
}

/* ------------------------------------------------------------
   SEMANA 9 — CONTADOR DE CARACTERES EN CAMPOS LARGOS
   ------------------------------------------------------------
   Para campos con [data-contador-caracteres] y maxlength, muestra
   "120 / 600" bajo el campo. Mantiene a la persona informada antes de
   llegar al límite (el navegador corta el texto sin avisar).
   ------------------------------------------------------------ */
function activarContadorCaracteres() {
  document.querySelectorAll('[data-contador-caracteres][maxlength]').forEach(function (campo) {
    const max = Number(campo.getAttribute('maxlength'));
    const min = Number(campo.getAttribute('minlength') || 0);
    const salida = document.createElement('p');
    salida.className = 'help contador-caracteres';
    salida.id = campo.id + '-contador';
    campo.closest('.field').appendChild(salida);
    campo.setAttribute('aria-describedby', (campo.getAttribute('aria-describedby') || '') + ' ' + salida.id);

    function actualizar() {
      const n = campo.value.length;
      let texto = n + ' / ' + max + ' caracteres';
      if (min && n < min) texto += ' · mínimo ' + min;
      salida.textContent = texto;
      salida.classList.toggle('esta-cerca', n >= max * 0.9);
    }
    campo.addEventListener('input', actualizar);
    actualizar();
  });
}

/* ------------------------------------------------------------
   SEMANA 9 — VISTA PREVIA DE LA IMAGEN (Publicar artículo)
   ------------------------------------------------------------
   El selector solo mostraba nombres de archivo ("nota3.jpg"). Ahora la
   foto elegida se muestra al lado, sin recargar la página.
   ------------------------------------------------------------ */
function activarVistaPreviaImagen() {
  const base = (document.body.dataset.base || '/').replace(/\/?$/, '/');
  document.querySelectorAll('select[data-vista-previa]').forEach(function (selector) {
    const img = document.getElementById(selector.dataset.vistaPrevia);
    if (!img) return;
    selector.addEventListener('change', function () {
      delete img.dataset.fallbackAplicado;
      img.src = base + 'assets/img/' + encodeURIComponent(selector.value);
    });
  });
}

/* ------------------------------------------------------------
   MENÚ RESPONSIVO (navbar-burger de Bulma)
   ------------------------------------------------------------ */
function activarMenuResponsivo() {
  // Semana 9: en móviles la lupa abre el menú y lleva el foco al buscador.
  const lupa = document.querySelector('.boton-buscar-movil');
  if (lupa) {
    lupa.addEventListener('click', function (evento) {
      evento.preventDefault();
      const burger = document.querySelector('.navbar-burger');
      const menu = document.getElementById('menuPrincipal');
      if (burger && menu && !menu.classList.contains('is-active')) burger.click();
      const campo = document.getElementById('buscador-q');
      if (campo) campo.focus();
    });
  }
  const burgers = document.querySelectorAll('.navbar-burger');
  burgers.forEach(function (burger) {
    burger.addEventListener('click', function () {
      const target = document.getElementById(burger.dataset.target);
      const abierto = burger.classList.toggle('is-active');
      burger.setAttribute('aria-expanded', abierto ? 'true' : 'false');
      if (target) target.classList.toggle('is-active', abierto);
    });
  });
}

/* ------------------------------------------------------------
   RELOJ EN VIVO
   ------------------------------------------------------------
   Semana 9: muestra fecha y hora sin segundos, sobre el elemento único
   #fecha-hora (sin aria-live, para no interrumpir al lector de pantalla).

   Zona horaria: se usa la zona horaria local del navegador de la
   persona usuaria (la que tenga configurada su sistema operativo).
   No se fija una zona fija para que la hora mostrada coincida con
   la del dispositivo desde donde se visita el sitio.

   Formato: se emplea Intl.DateTimeFormat con configuración regional
   'es-CL' (español de Chile). La fecha se muestra en formato largo
   (día de la semana, día, mes y año) y la hora en formato de 24 h
   con horas y minutos.
   ------------------------------------------------------------ */
function iniciarReloj() {
  const contenedor = document.getElementById('fecha-hora');
  if (!contenedor) return; // la página puede no tener reloj

  const opciones = {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
    hour12: false
  };
  const formateador = new Intl.DateTimeFormat('es-CL', opciones);

  function tick() {
    const ahora = new Date();
    const texto = formateador.format(ahora);
    // Primera letra en mayúscula (es-CL entrega el día en minúscula)
    contenedor.textContent = texto.charAt(0).toUpperCase() + texto.slice(1);
    // Atributo datetime en formato ISO 8601 (buena práctica semántica de <time>)
    if (contenedor.tagName === 'TIME') {
      contenedor.setAttribute('datetime', ahora.toISOString());
    }
  }

  tick();                 // muestra de inmediato
  setInterval(tick, 30000); // semana 9: sin segundos (distraían); se actualiza cada 30 s
}

/* ------------------------------------------------------------
   MANEJO DE ERROR DE IMÁGENES (sin onerror inline)
   ------------------------------------------------------------
   Si una imagen no carga, se reemplaza por una imagen alternativa
   (placeholder) definida aquí, en la lógica externa.
   ------------------------------------------------------------ */
function activarManejoDeImagenes() {
  // Imagen de respaldo propia (media/sin-imagen.svg). Se define aquí, en la
  // lógica externa, en lugar de usar el atributo onerror en cada <img>.
  // La ruta base la entrega el layout en <body data-base="..."> (evita rutas rotas).
  const base = (document.body.dataset.base || '/').replace(/\/?$/, '/');
  const IMAGEN_ALTERNATIVA = base + 'assets/media/sin-imagen.svg';

  function aplicarFallback(img) {
    if (img.dataset.fallbackAplicado) return; // evita bucle
    img.dataset.fallbackAplicado = 'true';
    img.src = IMAGEN_ALTERNATIVA;
  }

  document.querySelectorAll('img').forEach(function (img) {
    // 1) Enlaza el manejador para errores futuros (sin onerror inline)
    img.addEventListener('error', function () {
      aplicarFallback(img);
    });
    // 2) Cubre las imágenes que ya terminaron de cargar y fallaron
    //    antes de que se registrara el listener (complete + sin ancho real)
    if (img.complete && img.naturalWidth === 0 && img.getAttribute('src')) {
      aplicarFallback(img);
    }
  });
}

/* ------------------------------------------------------------
   UTILIDAD: singularización gramatical de contadores
   ------------------------------------------------------------ */
function textoCantidad(n, singular, plural) {
  return n + ' ' + (n === 1 ? singular : plural);
}

/* ------------------------------------------------------------
   CONTADORES POR SECCIÓN
   ------------------------------------------------------------
   Cuenta las tarjetas de artículo dentro de cada bloque marcado
   con [data-seccion] y muestra el total en su [data-contador],
   respetando singular/plural.
   ------------------------------------------------------------ */
function actualizarContadores() {
  document.querySelectorAll('[data-seccion]').forEach(function (bloque) {
    const total = bloque.querySelectorAll('.card-articulo').length;
    const nombre = bloque.getAttribute('data-seccion');
    // El contador puede estar dentro del bloque o asociado por data-contador="<seccion>"
    let salida = bloque.querySelector('[data-contador]');
    if (!salida) {
      salida = document.querySelector('[data-contador="' + nombre + '"]');
    }
    if (salida) {
      salida.textContent = textoCantidad(total, 'artículo', 'artículos');
    }
  });
}

/* ------------------------------------------------------------
   VALIDACIÓN ACCESIBLE (utilidades comunes)
   ------------------------------------------------------------ */
function mostrarError(campo, ayudaId, mensaje) {
  const ayuda = document.getElementById(ayudaId);
  campo.classList.remove('is-success');
  campo.classList.add('is-danger');
  campo.setAttribute('aria-invalid', 'true');
  if (ayuda) {
    ayuda.textContent = mensaje;
    ayuda.classList.remove('is-hidden');
  }
}

function limpiarError(campo, ayudaId) {
  const ayuda = document.getElementById(ayudaId);
  campo.classList.remove('is-danger');
  campo.removeAttribute('aria-invalid');
  if (ayuda) {
    ayuda.textContent = '';
    ayuda.classList.add('is-hidden');
  }
}

function esCorreoValido(valor) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor);
}

function mostrarMensaje(cajaId, texto, tipo) {
  const caja = document.getElementById(cajaId);
  if (!caja) return;
  caja.textContent = texto;
  caja.className = 'notification ' + tipo; // is-success / is-danger
  caja.classList.remove('is-hidden');
  caja.setAttribute('role', 'status');
}

/* ------------------------------------------------------------
   VALIDACIÓN DE FORMULARIOS (contacto, registro y artículo)
   ------------------------------------------------------------
   Corrige la observación de la semana 6: los selectores ahora
   coinciden con los id reales generados por la Vista, porque todos
   siguen la misma convención:
     form ......... #form-{formulario}
     campo ........ #{formulario}-{campo}
     ayuda/error .. #ayuda-{formulario}-{campo}
   Las reglas se leen del atributo data-validar de cada campo:
     requerido | email | texto | min:N | igual:{id-de-otro-campo}
   Si todo es válido, el formulario se ENVÍA al Controlador PHP,
   que vuelve a validar en el servidor antes de guardar en MySQL.
   ------------------------------------------------------------ */
function activarValidacionFormularios() {
  document.querySelectorAll('form[data-validar-form]').forEach(function (form) {
    // Semana 9 — mejora progresiva: los campos traen validación HTML5
    // (required, minlength, maxlength, pattern, title, type=email). Si el
    // JavaScript no carga, el navegador valida por sí solo. Con JavaScript
    // se desactivan las burbujas nativas (novalidate) y se muestran
    // mensajes en español junto a cada campo, accesibles y consistentes.
    form.setAttribute('novalidate', '');

    const campos = form.querySelectorAll('[data-validar]');

    function revisar(campo) {
      const mensaje = validarCampo(campo);
      const ayudaId = 'ayuda-' + campo.id;
      if (mensaje) {
        mostrarError(campo, ayudaId, mensaje);
      } else {
        limpiarError(campo, ayudaId);
        if (campo.value.trim() !== '' && campo.type !== 'checkbox') campo.classList.add('is-success');
      }
      return mensaje;
    }

    // Validación en vivo: al salir del campo y, si ya tenía error, mientras se corrige.
    campos.forEach(function (campo) {
      campo.addEventListener('blur', function () {
        if (campo.value.trim() !== '' || campo.getAttribute('aria-invalid') === 'true') revisar(campo);
      });
      campo.addEventListener(campo.type === 'checkbox' || campo.tagName === 'SELECT' ? 'change' : 'input', function () {
        campo.classList.remove('is-success');
        if (campo.getAttribute('aria-invalid') === 'true') revisar(campo);
      });
    });

    form.addEventListener('submit', function (evento) {
      let primerError = null;
      campos.forEach(function (campo) {
        if (revisar(campo)) primerError = primerError || campo;
      });

      if (primerError) {
        evento.preventDefault();               // no se envía al servidor
        primerError.focus();                    // gestión del foco
        mostrarMensaje(form.dataset.mensaje, 'Revisa los campos marcados en rojo.', 'is-danger');
      } else {
        // Evita el doble envío y avisa que se está procesando
        const boton = form.querySelector('button[type="submit"]');
        if (boton) { boton.classList.add('is-loading'); boton.setAttribute('aria-disabled', 'true'); }
      }
    });
  });
}

function validarCampo(campo) {
  const valor = campo.value.trim();
  const reglas = campo.dataset.validar.split(/\s+/);
  for (const regla of reglas) {
    const [nombre, parametro] = regla.split(':');
    if (nombre === 'marcado' && !campo.checked) {
      return 'Debes marcar esta casilla para continuar.';
    }
    if (nombre === 'requerido' && valor === '') {
      return 'Este campo es obligatorio.';
    }
    if (valor === '') continue;               // el resto solo aplica si hay valor
    if (nombre === 'email' && !esCorreoValido(valor)) {
      return 'Ingresa un correo válido (ejemplo: nombre@correo.cl).';
    }
    if (nombre === 'texto' && !/^[\p{L} '\-]+$/u.test(valor)) {
      return 'Usa solo letras y espacios.';
    }
    if (nombre === 'min' && valor.length < Number(parametro)) {
      return 'Debe tener al menos ' + parametro + ' caracteres (llevas ' + valor.length + ').';
    }
    if (nombre === 'igual') {
      const otro = document.getElementById(parametro);
      if (otro && otro.value !== campo.value) {
        return 'Las contraseñas no coinciden.';
      }
    }
  }
  // Restricciones HTML5 que no cubre data-validar (pattern, maxlength…):
  // se usa la API de validación del navegador y el atributo title.
  if (valor !== '' && campo.validity && !campo.validity.valid) {
    if (campo.validity.patternMismatch) return campo.title || 'El formato no es válido.';
    if (campo.validity.tooLong) return 'Supera el largo máximo permitido.';
    if (campo.validity.typeMismatch) return campo.title || 'El formato no es válido.';
  }
  return '';
}
