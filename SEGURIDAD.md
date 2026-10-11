# Diario El Faro — Seguridad, configuración y protección de datos

Documento que responde a las recomendaciones del docente y fija los
**criterios de revisión** del proyecto. Cada punto indica dónde está la
evidencia en el código y cómo se verifica.

Autor: Rodrigo Saavedra Ábalos — PRO301 Taller de Aplicaciones para Internet (AIEP)

---

## 1. Política de HTML para el cuerpo de los artículos

**Problema.** Hasta la semana 8 todo el cuerpo se escapaba con `e()`. Era seguro,
pero impedía publicar contenido enriquecido (listas, citas, imágenes, videos).

**Decisión.** Solo el **cuerpo del artículo** acepta HTML, y únicamente el que
define una **lista blanca** (`core/PoliticaHtml.php`). Todo lo demás —título,
resumen, etiqueta, comentarios, contacto, registro— sigue siendo texto plano escapado.

| Uso editorial | Etiquetas permitidas | Atributos | Restricciones |
|---|---|---|---|
| Párrafos y énfasis | `p`, `br`, `strong`, `em` | — | `b` → `strong`, `i` → `em` |
| Subtítulos | `h3`, `h4` | — | `h1`/`h2` se convierten en `h3` (los niveles 1 y 2 son del sitio) |
| Listas y citas | `ul`, `ol`, `li`, `blockquote` | — | — |
| Enlaces | `a` | `href` | solo `https://`, `http://` o `mailto:`; los externos reciben `target="_blank"` y `rel="noopener noreferrer nofollow"` |
| Imágenes | `figure`, `figcaption`, `img` | `src`, `alt`, `width`, `height` | `src`: archivo de `assets/img` (nombre simple `.jpg/.png/.webp`) o `https://`; `alt` siempre presente; `loading="lazy"` |
| Video | `iframe` | `src` | solo `youtube.com/embed/ID` (11 caracteres); se reescribe a `youtube-nocookie.com` con `sandbox` |

**Siempre se elimina:** `script`, `style`, `object`, `embed`, `form`, `input`, `button`,
`svg`, `math`, `template`, `link`, `meta`, `base` (con su contenido), cualquier atributo
`on…` (`onclick`, `onerror`…), `style`, `class`, `id`, enlaces `javascript:` y comentarios HTML.
Las etiquetas desconocidas (`div`, `span`…) se quitan pero se conserva su texto.

**Criterios funcionales.**
- Quien publica puede escribir texto plano (como antes) o HTML básico; el formulario
  muestra la lista de lo permitido.
- Si el texto trae algo fuera de la política, **se rechaza y se informa qué sobra**
  (`Validador::htmlPermitido`). No se pierde contenido sin aviso.
- Lo que se guarda en MySQL ya está filtrado, y **al mostrarse se vuelve a filtrar**
  (`Articulo::getCuerpoHtml`): defensa en profundidad si alguien modifica la base por fuera.
- La política de seguridad de contenido (CSP) bloquea scripts en línea y de otros
  dominios, y solo permite iframes de `youtube-nocookie.com`.

**Verificación.** `<script>`, `onclick`, `onerror`, `javascript:` y `<img src="x">`
son rechazados al publicar; un `<script>` insertado directamente en la tabla no aparece en la página.

---

## 2. Tipos de parámetros PDO

`Conexion::tipoPdo()` asigna el tipo **según el tipo PHP real** del valor:

| Valor PHP | Tipo PDO | Columna típica |
|---|---|---|
| `true` / `false` | `PDO::PARAM_BOOL` | `TINYINT(1)` (`destacado`, `activo`) |
| `int` | `PDO::PARAM_INT` | `INT`, `LIMIT`, `OFFSET` |
| `null` | `PDO::PARAM_NULL` | parámetros opcionales |
| `string` / `float` | `PDO::PARAM_STR` | `VARCHAR`, `TEXT`, `DATETIME`, `ENUM` |

**Cómo se distinguen lógicos de enteros:**
1. Los modelos entregan los lógicos como `bool` real (`Articulo::esDestacado()`), nunca `1/0` ni `"1"`.
2. `is_bool()` se evalúa **antes** que `is_int()`.
3. Identificadores y límites se convierten con `(int)` explícito en el repositorio.
4. Una cadena numérica sigue siendo texto: no se adivina su tipo.
5. `PDO::ATTR_EMULATE_PREPARES = false`: los tipos llegan al motor en sentencias preparadas reales.

---

## 3. Configuración sensible en `.env`

- Las credenciales **ya no están en el código**. Viven en `.env` (raíz del proyecto),
  que está en `.gitignore` y que `.htaccess` bloquea (`Require all denied`).
- `.env.example` documenta las variables: `DB_HOST`, `DB_PUERTO`, `DB_NOMBRE`, `DB_USUARIO`, `DB_CLAVE`.
- `config/database.php` usa **vlucas/phpdotenv** (Composer, carpeta `vendor/`):

```php
$dotenv = Dotenv\Dotenv::createImmutable($raiz);   // no pisa variables reales del servidor
$dotenv->safeLoad();                                // no falla si el servidor ya define las variables
$dotenv->required(["DB_HOST", "DB_NOMBRE", "DB_USUARIO"])->notEmpty();
$dotenv->required("DB_CLAVE");
$dotenv->ifPresent("DB_PUERTO")->isInteger();
```

- Si falta una variable, se lanza una excepción que el controlador frontal convierte
  en la página «Servicio no disponible», sin mostrar la clave ni la ruta.
- Si el hosting no tiene `vendor/`, un cargador mínimo equivalente lee el mismo `.env`.
- La cuenta de base de datos de la aplicación (`el_faro_app`) tiene solo permisos sobre `el_faro`, nunca `root`.

---

## 4. Contraseñas y sesiones

**Hashing no es cifrado.**

| | Hash (bcrypt) | Cifrado (p. ej. AES) |
|---|---|---|
| Dirección | Una vía: no se puede revertir | Dos vías: se descifra con la clave |
| Uso correcto | Contraseñas | Datos que deben volver a leerse |
| Si roban la base | No obtienen las contraseñas | Con la clave, obtienen todo |

**Ejemplo explicativo (código real del proyecto):**

```php
// Registro (models/Usuario.php)
$hash = password_hash($clave, PASSWORD_BCRYPT, ["cost" => 12]);
// $2y$12$<22 caracteres de sal aleatoria><31 de hash> — la sal va dentro del hash

// Inicio de sesión (controllers/UsuarioController.php)
$usuario = $repo->buscarParaLogin($email);                       // sentencia preparada
$hash    = $usuario ? $usuario->getPasswordHash() : HASH_RELLENO; // mismo tiempo si el correo no existe
if (password_verify($clave, $hash) && $usuario) {                // comparación en tiempo constante
    if ($usuario->necesitaRehash()) { /* password_needs_rehash: se renueva el hash */ }
    Sesion::iniciarUsuario(...);                                   // session_regenerate_id(true)
} else {
    Sesion::registrarFallo();                                      // 5 fallos = 60 s de bloqueo
    $error = "Correo o contraseña incorrectos.";                    // mensaje genérico
}
```

**Sesión (`core/Sesion.php`):** cookie `HttpOnly`, `SameSite=Lax` y `Secure` en HTTPS;
`session.use_strict_mode` y `use_only_cookies`; nuevo identificador al iniciar sesión;
cierre por inactividad (30 min); cierre de sesión solo por POST con token CSRF; en la
sesión se guarda id, nombre y correo, nunca la contraseña ni su hash.

---

## 5. Vistas: escape de salida y validación de entrada (XSS)

- **Salida:** todo dato variable se imprime con `e()` =
  `htmlspecialchars(…, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, "UTF-8")`.
  `<script>` se muestra como texto y no se ejecuta. Única excepción documentada:
  el cuerpo del artículo, filtrado por la política de la sección 1.
- **Entrada:** los parámetros de la URL se aceptan solo con su formato exacto
  (`get_entero`, `get_slug`, `get_busqueda` en `config/config.php`); controlador y
  acción, solo letras minúsculas y además en lista blanca. Los formularios se validan
  en el navegador (HTML5) y **siempre** en el servidor (`core/Validador.php`), con
  largos iguales a las columnas, listas blancas (`enLista`), solo letras (`soloTexto`)
  y sin etiquetas en comentarios (`sinEtiquetas`).
- **Cabeceras:** `Content-Security-Policy` (solo `script-src 'self'`),
  `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`.
- **Consistencia:** se valida al entrar y se escapa al salir. No se «sanitiza» guardando
  texto modificado: el dato se guarda tal cual (o se rechaza) y se escapa al mostrarlo.

---

## 6. Criterios de revisión (lista de control)

| # | Criterio | Evidencia | Cómo verificar |
|---|---|---|---|
| 1 | Toda consulta usa sentencias preparadas, sin concatenar datos | `models/Conexion.php::preparar` | Buscar `query(` / `exec(` en `models/`: no hay |
| 2 | Cada parámetro tiene tipo PDO explícito | `Conexion::tipoPdo` | `herramientas/verificar_pdo.php` |
| 3 | Lógicos viajan como `bool` → `PARAM_BOOL` | `ArticuloRepositorio` | Publicar un destacado: solo queda 1 con `destacado = 1` |
| 4 | Sin credenciales en el código | `config/database.php`, `.env.example` | Buscar la clave en el código: solo está en `.env` |
| 5 | `.env`, `vendor/` y carpetas internas no son públicas | `.htaccess` | Abrir `/.env` en el navegador: 403 |
| 6 | Contraseñas solo como hash bcrypt | `Usuario::setPassword` | `SELECT password_hash FROM usuario` → `$2y$12$…` |
| 7 | Login con `password_verify`, mensaje genérico y límite de intentos | `UsuarioController::ingresar` | 5 claves erróneas → bloqueo de 60 s |
| 8 | Sesión renovada al ingresar; cookie HttpOnly/SameSite | `core/Sesion.php` | Herramientas del navegador → Cookies |
| 9 | Toda salida variable usa `e()` | `views/**` | Buscar `echo $` sin `e(` en vistas |
| 10 | Cuerpo HTML filtrado al guardar y al mostrar | `PoliticaHtml`, `Articulo::getCuerpoHtml` | Publicar `<script>`: se rechaza |
| 11 | Parámetros GET con formato estricto | `get_entero`, `get_slug`, `get_busqueda` | `?id=1abc` redirige; `?s=<x>` vuelve a la portada |
| 12 | Formularios POST con token CSRF | `csrf_campo` / `csrf_valido` | Enviar sin token: «La sesión expiró» |
| 13 | CSP sin scripts en línea | `index.php` | Consola del navegador sin violaciones |
