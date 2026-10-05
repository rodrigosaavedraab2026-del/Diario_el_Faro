/* ============================================================
   El Faro — Lógica del sitio (JavaScript externo)
   Autor: Rodrigo Saavedra Ábalos — PRO301 AIEP

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
  activarMenuResponsivo();
  iniciarReloj();
  activarManejoDeImagenes();
  activarValidacionFormularios();
  actualizarContadores();
});

/* ------------------------------------------------------------
   MENÚ RESPONSIVO (navbar-burger de Bulma)
   ------------------------------------------------------------ */
function activarMenuResponsivo() {
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
   Se actualiza cada segundo sobre el elemento único #fecha-hora.

   Zona horaria: se usa la zona horaria local del navegador de la
   persona usuaria (la que tenga configurada su sistema operativo).
   No se fija una zona fija para que la hora mostrada coincida con
   la del dispositivo desde donde se visita el sitio.

   Formato: se emplea Intl.DateTimeFormat con configuración regional
   'es-CL' (español de Chile). La fecha se muestra en formato largo
   (día de la semana, día, mes y año) y la hora en formato de 24 h
   con horas, minutos y segundos.
   ------------------------------------------------------------ */
function iniciarReloj() {
  const contenedor = document.getElementById('fecha-hora');
  if (!contenedor) return; // la página puede no tener reloj

  const opciones = {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit', second: '2-digit',
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
  setInterval(tick, 1000); // actualiza cada segundo
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
    form.addEventListener('submit', function (evento) {
      let primerError = null;

      form.querySelectorAll('[data-validar]').forEach(function (campo) {
        const mensaje = validarCampo(campo);
        const ayudaId = 'ayuda-' + campo.id;
        if (mensaje) {
          mostrarError(campo, ayudaId, mensaje);
          primerError = primerError || campo;
        } else {
          limpiarError(campo, ayudaId);
        }
      });

      if (primerError) {
        evento.preventDefault();               // no se envía al servidor
        primerError.focus();                    // gestión del foco
        mostrarMensaje(form.dataset.mensaje, 'Revisa los campos marcados en rojo.', 'is-danger');
      }
    });
  });
}

function validarCampo(campo) {
  const valor = campo.value.trim();
  const reglas = campo.dataset.validar.split(/\s+/);
  for (const regla of reglas) {
    const [nombre, parametro] = regla.split(':');
    if (nombre === 'requerido' && valor === '') {
      return 'Este campo es obligatorio.';
    }
    if (valor === '') continue;               // el resto solo aplica si hay valor
    if (nombre === 'email' && !esCorreoValido(valor)) {
      return 'Ingresa un correo válido.';
    }
    if (nombre === 'texto' && !/^[\p{L} '\-]+$/u.test(valor)) {
      return 'Usa solo letras y espacios.';
    }
    if (nombre === 'min' && valor.length < Number(parametro)) {
      return 'Debe tener al menos ' + parametro + ' caracteres.';
    }
    if (nombre === 'igual') {
      const otro = document.getElementById(parametro);
      if (otro && otro.value !== campo.value) {
        return 'Las contraseñas no coinciden.';
      }
    }
  }
  return '';
}
