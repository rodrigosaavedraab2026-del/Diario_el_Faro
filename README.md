# Diario El Faro — PDO en todo nivel y procedimientos almacenados (Semana 8, PRO301 AIEP)

Autor: Rodrigo Saavedra Ábalos

Evolución del sitio "El Faro" (semana 6: PHP + MVC con archivos de texto;
semana 7: base de datos **MySQL** con **PDO**). En la semana 8 toda la capa de
datos pasa por una **clase única de conexión** y las consultas e inserciones
principales se encapsulan en **procedimientos almacenados**, manteniendo el
patrón Modelo–Vista–Controlador, los layouts con CSS (Bulma + estilos propios),
Font Awesome y la responsividad del sitio.

## Semana 8: PDO en todo nivel

| Requisito | Implementación |
|---|---|
| 1. Clase única para las conexiones | `models/Conexion.php` — Singleton (`obtenerInstancia()`), único lugar donde se crea el objeto `PDO`. |
| 2. Métodos de conexión, consulta e inserción | `conectar()`, `desconectar()`, `consultar()`, `consultarFila()`, `consultarValor()`, `insertar()`, `ejecutar()`, `llamarProcedimiento()` y transacciones. |
| 3. Sentencias preparadas | Todos los métodos usan `prepare()` + `bindValue()` + `execute()`; se eliminaron los `query()` y `exec()` directos. |
| 4. Procedimientos almacenados | Consultas: `sp_articulo_listar`, `sp_articulo_contar` (OUT), `sp_articulo_buscar`, `sp_usuario_listar`. Inserciones: `sp_articulo_registrar`, `sp_usuario_registrar`, `sp_contacto_registrar` (OUT). Archivo `database/el_faro_procedimientos.sql` (incluido en `el_faro.sql`). |
| 5. Archivo con datos de conexión | `config/database.php` (constantes `DB_*`, sobrescribibles por variables de entorno). |
| 6. Clases por entidad que usan la conexión | `models/Repositorio.php` (clase base abstracta) → `ArticuloRepositorio`, `SeccionRepositorio`, `AutorRepositorio`, `UsuarioRepositorio`, `ContactoRepositorio`. |

Verificación por consola: `C:\xampp\php\php.exe herramientas\verificar_pdo.php`.

**Modo compatibilidad:** si la base de datos no tiene los procedimientos (hosting
gratuito que no permite crear rutinas), `Conexion` detecta el error 1305 y el
repositorio ejecuta la sentencia preparada equivalente. En XAMPP siempre se usan
los procedimientos almacenados.

## Requisitos funcionales implementados

| Requisito | Ruta | Controlador → Modelo | Tabla(s) |
|---|---|---|---|
| a) Registrar un nuevo artículo | `?c=articulo&a=nuevo` → `guardar` (POST) | `ArticuloController` → `ArticuloRepositorio::registrar()` | `articulo` |
| b) Presentar uno o más artículos por página | `index.php`, `?c=articulo&a=index&pp=3&p=2`, `?c=articulo&a=ver&id=1` | `HomeController`, `ArticuloController`, `SeccionController` → `listarPagina()` / `buscarPorId()` | `vw_articulo_detalle` |
| c) Registrar datos del formulario de contacto | `?c=contacto&a=index` → `enviar` (POST) | `ContactoController` → `ContactoRepositorio::registrar()` | `contacto` (vinculado a `usuario`) |
| d) Presentar los usuarios registrados | `?c=usuario&a=listado` | `UsuarioController::listado()` → `UsuarioRepositorio::listarPagina()` | `usuario` + `localidad` + `contacto` |

## Instalación con XAMPP

1. Copiar la carpeta `el-faro-mvc` en `C:\xampp\htdocs\`.
2. En el panel de XAMPP iniciar **Apache** y **MySQL**.
3. Abrir `http://localhost/phpmyadmin` → **Nueva** → nombre `el_faro`,
   cotejamiento `utf8mb4_general_ci` → **Crear**.
4. Hacer clic en `el_faro` en el panel izquierdo para **seleccionarla**
   (si se importa sin base seleccionada, phpMyAdmin muestra mensajes
   `#1046 Base de datos no seleccionada`).
5. Pestaña **Importar** → elegir `database/el_faro.sql` → **Importar**.
   Se crean 7 tablas, la vista, los triggers, el evento, los 7
   procedimientos almacenados y el usuario `el_faro_app` (con permiso
   EXECUTE), y se activa el planificador de eventos.
6. Abrir `http://localhost/el-faro-mvc/index.php`.

El script se puede reimportar cuantas veces se quiera (borra y recrea sus
propias tablas). Para que el evento siga activo después de reiniciar MySQL,
agregar `event_scheduler=ON` bajo `[mysqld]` en `C:\xampp\mysql\bin\my.ini`.

Credenciales de conexión: `config/database.php` (por defecto usuario
`el_faro_app`, contraseña `ElFaro_2026`).

### Archivos SQL

| Archivo | Cuándo usarlo |
|---|---|
| `database/el_faro.sql` | XAMPP / servidor propio (crea base, usuario, triggers y evento). |
| `database/el_faro_hosting.sql` | Hosting que entrega una base ya creada y no permite crear bases, usuarios, triggers ni eventos. Importar con la base del hosting seleccionada. |
| `database/el_faro_triggers.sql` | Opcional en hosting: importar después del anterior si el hosting permite triggers. |
| `database/el_faro_procedimientos.sql` | Opcional en hosting: importar si el hosting permite crear procedimientos almacenados. |

En hosting, configurar `DB_HOST`, `DB_NOMBRE`, `DB_USUARIO` y `DB_CLAVE` en
`config/database.php` con los datos que entrega el proveedor.

## Estructura

```
index.php                 Front Controller (enrutador ?c=&a=) + manejo de errores de BD
config/config.php         helpers: base_url, e, redirigir, CSRF, formularios
config/database.php       credenciales MySQL (sobrescribibles por variables de entorno)
core/Paginador.php        cálculo de LIMIT/OFFSET y parámetros ?p= y ?pp=
core/Validador.php        validaciones de servidor (largo, correo, listas blancas)
controllers/              Home, Seccion, Articulo, Contacto, Usuario
models/                   Conexion (clase única PDO) + Repositorio (base) + entidades + repositorios
herramientas/             verificar_pdo.php (pruebas por consola, sin acceso web)
views/                    layouts (header, footer, tarjeta, paginación), home,
                          seccion, articulo, contacto, usuario, errores
assets/                   css, js (faro.js), img, media
database/                 el_faro.sql (XAMPP) · el_faro_hosting.sql · el_faro_triggers.sql · el_faro_procedimientos.sql
```

## Capa Modelo

- **Infraestructura:** `Conexion` (clase única PDO: conexión, consulta, inserción, procedimientos almacenados y transacciones) y `Repositorio` (clase base que entrega la conexión a cada repositorio).
- **Entidades:** `Articulo`, `Seccion`, `Autor`, `Usuario`, `Localidad`, `Contacto`.
- **Repositorios (acceso a datos):** `ArticuloRepositorio`, `SeccionRepositorio`,
  `AutorRepositorio`, `UsuarioRepositorio`, `ContactoRepositorio`.

## Modelo relacional (7 tablas, 7 llaves foráneas)

| Relación | Llave foránea | Al borrar el registro padre |
|---|---|---|
| seccion 1:N articulo | `articulo.id_seccion` → `fk_articulo_seccion` | RESTRICT |
| autor 1:N articulo | `articulo.id_autor` → `fk_articulo_autor` | RESTRICT |
| localidad 1:N usuario | `usuario.id_localidad` → `fk_usuario_localidad` | SET NULL |
| usuario 1:N contacto (opcional) | `contacto.id_usuario` → `fk_contacto_usuario` | SET NULL |
| articulo 1:N bitacora | `bitacora.id_articulo` → `fk_bitacora_articulo` | CASCADE |
| usuario 1:N bitacora | `bitacora.id_usuario` → `fk_bitacora_usuario` | CASCADE |
| contacto 1:N bitacora | `bitacora.id_contacto` → `fk_bitacora_contacto` | CASCADE |

Ninguna tabla queda aislada. La vista `vw_articulo_detalle` une artículo,
sección y autor para las consultas de la capa Modelo.

## Trabajos automáticos en la base de datos

- `trg_articulo_bi` (BEFORE INSERT): normaliza el título.
- `trg_contacto_bi` (BEFORE INSERT): vincula el mensaje con el lector registrado que tenga el mismo correo.
- `trg_articulo_ai`, `trg_usuario_ai`, `trg_contacto_ai` (AFTER INSERT): registran cada alta en `bitacora`
  (con la llave foránea al registro de origen). `trg_usuario_ai` además asocia al nuevo lector los
  mensajes que envió antes de crear su cuenta.
- `ev_archivar_contactos` (EVENT diario): archiva mensajes de contacto con más de 90 días.

## Convenciones de nombres

| Elemento | Convención | Ejemplo |
|---|---|---|
| Tablas y columnas | minúsculas, singular, `snake_case` | `articulo.fecha_publicacion` |
| Llave primaria | `id_{tabla}` | `id_usuario` |
| Llave foránea | mismo nombre que la PK referenciada | `usuario.id_localidad` |
| Restricción FK | `fk_{tabla}_{referencia}` | `fk_articulo_seccion` |
| Índice único / normal | `uq_…` / `ix_…` | `uq_usuario_email` |
| Trigger | `trg_{tabla}_{b/a}{i/u/d}` | `trg_usuario_ai` |
| Formulario | `form-{formulario}` | `form-contacto` |
| Campo (id) | `{formulario}-{campo}` | `contacto-email` |
| Campo (name) | igual a la columna MySQL | `email` |
| Mensaje de error | `ayuda-{formulario}-{campo}` | `ayuda-contacto-email` |
| Clases PHP | `PascalCase`; repositorio = `{Entidad}Repositorio` | `UsuarioRepositorio` |

## Seguridad aplicada (observaciones de la semana 6)

- Token **CSRF** por sesión en todos los formularios POST (`csrf_campo()` / `csrf_valido()`).
- **Sentencias preparadas** PDO en todas las consultas (sin concatenar datos del usuario), incluidas las llamadas `CALL` a procedimientos almacenados.
- Validación de servidor con largos iguales a las columnas y listas blancas (sección, autor, plan, asunto, imagen).
- Escape de salida con `e()` (`htmlspecialchars`) en todas las vistas.
- Contraseñas con `password_hash()`; el listado de lectores nunca consulta el hash.
- Usuario MySQL dedicado (`el_faro_app`) con permisos mínimos, en lugar de `root`.
- Patrón POST/Redirect/GET al publicar artículos.
- Selectores de `faro.js` alineados con los `id` reales de las vistas.

## Si aparece «No pudimos conectar con la base de datos»

Al abrir el sitio desde el mismo equipo (`http://localhost/...`), la página
muestra el código del error y qué hacer. Los casos más comunes en XAMPP:

| Código | Causa | Solución |
|---|---|---|
| 2002 | MySQL no está iniciado | Botón **Start** de MySQL en el panel de XAMPP. |
| 1045 | El usuario `el_faro_app` no existe o la clave no coincide | Importar `database/el_faro.sql`. |
| 1049 | No existe la base `el_faro` | Crearla e importar `database/el_faro.sql`. |
| 1370 | Falta el permiso EXECUTE (base importada con el script de la semana 7) | Volver a importar `database/el_faro.sql`. |
| 1558 | Tablas internas de MySQL desactualizadas | `C:\xampp\mysql\bin\mysql_upgrade.exe -u root`, reiniciar MySQL y reimportar. |

El mismo detalle queda registrado en `C:\xampp\apache\logs\error.log`.
