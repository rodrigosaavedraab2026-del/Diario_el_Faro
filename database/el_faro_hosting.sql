-- =====================================================================
--  Diario El Faro — Script para HOSTING (base de datos ya creada)
--  PRO301 Taller de Aplicaciones para Internet — Semanas 7 y 8 (AIEP)
--  Autor: Rodrigo Saavedra Ábalos
--
--  Usar este archivo cuando el hosting entrega una base de datos con
--  nombre propio (por ejemplo if0_12345678_elfaro) y NO permite crear
--  bases, usuarios, triggers ni eventos. No contiene CREATE DATABASE,
--  USE, CREATE USER, GRANT, TRIGGER ni EVENT.
--
--  CÓMO IMPORTAR:
--    1. En phpMyAdmin del hosting, seleccionar la base de datos asignada.
--    2. Pestaña "Importar" > seleccionar este archivo > "Importar".
--    3. En config/database.php del sitio, poner host, nombre de base,
--       usuario y contraseña que entrega el hosting.
--    4. (Opcional) Si el hosting permite triggers, importar después
--       el_faro_triggers.sql para llenar la tabla bitacora.
--    5. (Semana 8) Si el hosting permite crear procedimientos
--       almacenados, importar después el_faro_procedimientos.sql.
--       Si no los permite (por ejemplo, hosting gratuito sin rutinas),
--       el sitio detecta que no existen y usa sentencias preparadas
--       equivalentes, por lo que sigue funcionando.
--  Se puede reimportar: borra y recrea sus propias tablas.
-- =====================================================================

SET NAMES utf8mb4;
SET @OLD_SQL_NOTES = @@SQL_NOTES;
SET SQL_NOTES = 0;

-- Limpieza: permite reimportar el script sin errores de "ya existe".
DROP VIEW IF EXISTS `vw_articulo_detalle`;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `bitacora`, `contacto`, `usuario`, `localidad`,
                     `articulo`, `autor`, `seccion`;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. TABLAS
-- ---------------------------------------------------------------------

-- Sección del diario (Actualidad, Deporte, Negocios, Cultura)
CREATE TABLE `seccion` (
  `id_seccion`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`         VARCHAR(60)  NOT NULL,
  `slug`           VARCHAR(60)  NOT NULL,
  `color_etiqueta` VARCHAR(20)  NOT NULL DEFAULT 'is-link',
  PRIMARY KEY (`id_seccion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Autor o periodista que firma los artículos
CREATE TABLE `autor` (
  `id_autor` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`   VARCHAR(120) NOT NULL,
  `email`    VARCHAR(150) NOT NULL,
  PRIMARY KEY (`id_autor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Artículo (noticia)
CREATE TABLE `articulo` (
  `id_articulo`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titulo`            VARCHAR(200) NOT NULL,
  `resumen`           VARCHAR(300) NOT NULL,
  `cuerpo`            TEXT         NOT NULL,
  `etiqueta`          VARCHAR(40)  NOT NULL,
  `imagen`            VARCHAR(120) NOT NULL DEFAULT 'destacada.jpg',
  `destacado`         TINYINT(1)   NOT NULL DEFAULT 0,
  `fecha_publicacion` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_seccion`        INT UNSIGNED NOT NULL,
  `id_autor`          INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id_articulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Localidad (país + ciudad) de los lectores
CREATE TABLE `localidad` (
  `id_localidad` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pais`         VARCHAR(80)  NOT NULL,
  `ciudad`       VARCHAR(80)  NOT NULL,
  PRIMARY KEY (`id_localidad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Usuario (lector registrado desde el formulario "Crear cuenta")
CREATE TABLE `usuario` (
  `id_usuario`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`         VARCHAR(80)  NOT NULL,
  `apellido`       VARCHAR(80)  NOT NULL,
  `email`          VARCHAR(150) NOT NULL,
  `password_hash`  VARCHAR(255) NOT NULL,
  `plan`           ENUM('gratuito','mensual','anual') NOT NULL DEFAULT 'gratuito',
  `fecha_registro` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_localidad`   INT UNSIGNED NULL,
  PRIMARY KEY (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Mensaje recibido desde el formulario de contacto.
-- id_usuario: lector registrado que envió el mensaje (NULL si quien
-- escribe es un visitante sin cuenta). Lo completa un trigger.
CREATE TABLE `contacto` (
  `id_contacto` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(120) NOT NULL,
  `email`       VARCHAR(150) NOT NULL,
  `asunto`      ENUM('Enviar una noticia','Comentario','Publicidad','Otro') NOT NULL DEFAULT 'Enviar una noticia',
  `mensaje`     VARCHAR(2000) NOT NULL,
  `estado`      ENUM('nuevo','leido','archivado') NOT NULL DEFAULT 'nuevo',
  `fecha_envio` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_usuario`  INT UNSIGNED NULL,
  PRIMARY KEY (`id_contacto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Bitácora: registro automático de altas (alimentada por triggers).
-- Cada fila apunta, mediante su llave foránea, al artículo, usuario o
-- mensaje de contacto que la originó (solo una de las tres se completa).
CREATE TABLE `bitacora` (
  `id_bitacora` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tabla`       VARCHAR(40)  NOT NULL,
  `accion`      VARCHAR(20)  NOT NULL,
  `detalle`     VARCHAR(255) NOT NULL,
  `fecha`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_articulo` INT UNSIGNED NULL,
  `id_usuario`  INT UNSIGNED NULL,
  `id_contacto` INT UNSIGNED NULL,
  PRIMARY KEY (`id_bitacora`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 2. ÍNDICES Y LLAVES FORÁNEAS
-- ---------------------------------------------------------------------
ALTER TABLE `seccion`   ADD UNIQUE KEY `uq_seccion_slug` (`slug`);
ALTER TABLE `autor`     ADD UNIQUE KEY `uq_autor_email` (`email`);
ALTER TABLE `usuario`   ADD UNIQUE KEY `uq_usuario_email` (`email`);
ALTER TABLE `localidad` ADD UNIQUE KEY `uq_localidad` (`pais`, `ciudad`);
ALTER TABLE `articulo`  ADD KEY `ix_articulo_fecha` (`fecha_publicacion`);
ALTER TABLE `contacto`  ADD KEY `ix_contacto_estado_fecha` (`estado`, `fecha_envio`);

-- Un artículo pertenece a UNA sección; una sección tiene MUCHOS artículos (1:N)
ALTER TABLE `articulo` ADD CONSTRAINT `fk_articulo_seccion`
  FOREIGN KEY (`id_seccion`) REFERENCES `seccion` (`id_seccion`)
  ON DELETE RESTRICT ON UPDATE RESTRICT;

-- Un artículo es escrito por UN autor; un autor escribe MUCHOS artículos (1:N)
ALTER TABLE `articulo` ADD CONSTRAINT `fk_articulo_autor`
  FOREIGN KEY (`id_autor`) REFERENCES `autor` (`id_autor`)
  ON DELETE RESTRICT ON UPDATE RESTRICT;

-- Un usuario vive en UNA localidad; una localidad agrupa MUCHOS usuarios (1:N)
-- SET NULL: si se elimina una localidad, el usuario se conserva sin ella.
ALTER TABLE `usuario` ADD CONSTRAINT `fk_usuario_localidad`
  FOREIGN KEY (`id_localidad`) REFERENCES `localidad` (`id_localidad`)
  ON DELETE SET NULL ON UPDATE RESTRICT;

-- Un usuario puede enviar MUCHOS mensajes de contacto; cada mensaje
-- pertenece como máximo a UN usuario (1:N, opcional).
-- SET NULL: si se elimina el usuario, su mensaje se conserva.
ALTER TABLE `contacto` ADD CONSTRAINT `fk_contacto_usuario`
  FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
  ON DELETE SET NULL ON UPDATE RESTRICT;

-- La bitácora registra MUCHOS eventos por artículo, usuario o contacto
-- (tres relaciones 1:N). CASCADE: si se elimina el registro de origen,
-- se eliminan también sus entradas de bitácora.
ALTER TABLE `bitacora` ADD CONSTRAINT `fk_bitacora_articulo`
  FOREIGN KEY (`id_articulo`) REFERENCES `articulo` (`id_articulo`)
  ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `bitacora` ADD CONSTRAINT `fk_bitacora_usuario`
  FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
  ON DELETE CASCADE ON UPDATE RESTRICT;

ALTER TABLE `bitacora` ADD CONSTRAINT `fk_bitacora_contacto`
  FOREIGN KEY (`id_contacto`) REFERENCES `contacto` (`id_contacto`)
  ON DELETE CASCADE ON UPDATE RESTRICT;

-- ---------------------------------------------------------------------
-- 3. VISTA DE CONSULTA (une artículo + sección + autor)
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW `vw_articulo_detalle` AS
SELECT a.`id_articulo`, a.`titulo`, a.`resumen`, a.`cuerpo`, a.`etiqueta`,
       a.`imagen`, a.`destacado`, a.`fecha_publicacion`,
       s.`id_seccion`, s.`nombre` AS `seccion`, s.`slug` AS `seccion_slug`,
       s.`color_etiqueta`,
       au.`id_autor`, au.`nombre` AS `autor`
FROM `articulo` a
JOIN `seccion` s ON s.`id_seccion` = a.`id_seccion`
JOIN `autor`  au ON au.`id_autor`  = a.`id_autor`;

-- ---------------------------------------------------------------------
-- 4. POBLAR TABLAS
--    Orden: primero las tablas "padre" (seccion, autor, localidad),
--    luego las "hijas" (articulo, usuario), por las llaves foráneas.
-- ---------------------------------------------------------------------
INSERT INTO `seccion` (`id_seccion`, `nombre`, `slug`, `color_etiqueta`) VALUES
  (1, 'Actualidad', 'actualidad', 'is-primary'),
  (2, 'Deporte',    'deporte',    'is-link'),
  (3, 'Negocios',   'negocios',   'is-success'),
  (4, 'Cultura',    'cultura',    'is-warning');

INSERT INTO `autor` (`id_autor`, `nombre`, `email`) VALUES
  (1, 'Redacción El Faro', 'redaccion@elfaro.cl'),
  (2, 'Camila Rojas',      'crojas@elfaro.cl'),
  (3, 'Matías Fuentes',    'mfuentes@elfaro.cl');

INSERT INTO `localidad` (`id_localidad`, `pais`, `ciudad`) VALUES
  (1, 'Chile', 'Viña del Mar'),
  (2, 'Chile', 'Valparaíso');

INSERT INTO `articulo`
  (`titulo`, `resumen`, `cuerpo`, `etiqueta`, `imagen`, `destacado`, `fecha_publicacion`, `id_seccion`, `id_autor`)
VALUES
  ('Viña del Mar renueva su borde costero con nuevo paseo peatonal',
   'El municipio inauguró la primera etapa de un proyecto que moderniza la costanera y potencia el turismo local.',
   'El municipio inauguró la primera etapa de un proyecto que busca modernizar la costanera y potenciar el turismo local.

Vecinos y comerciantes valoran la iniciativa, que incorpora ciclovías, áreas verdes y accesos inclusivos a lo largo de dos kilómetros de borde costero.',
   'Actualidad', 'destacada.jpg', 1, '2026-09-26 09:00:00', 1, 1),
  ('Everton prepara su pretemporada en Sausalito',
   'El plantel ruletero inició los trabajos físicos con miras al próximo campeonato.',
   'El plantel de Everton inició su pretemporada en el Estadio Sausalito con trabajos físicos y tácticos.

El cuerpo técnico espera contar con tres refuerzos antes del debut.',
   'Fútbol', 'nota1.jpg', 0, '2026-09-26 08:00:00', 2, 3),
  ('Festival de teatro llega a los cerros de Valparaíso',
   'Compañías nacionales presentarán obras gratuitas en plazas y miradores del puerto.',
   'Durante dos semanas, compañías de todo el país presentarán obras gratuitas en plazas y miradores de los cerros porteños.

La programación completa está disponible en el sitio del festival.',
   'Teatro', 'nota2.jpg', 0, '2026-09-26 06:30:00', 4, 2),
  ('Emprendedores locales crean app para reciclaje',
   'Una aplicación desarrollada en la región conecta a vecinos con puntos limpios cercanos.',
   'Un grupo de emprendedores de la región lanzó una aplicación que conecta a vecinos con puntos limpios y recicladores de base.

La app ya suma más de dos mil descargas en su primer mes.',
   'Tecnología', 'nota3.jpg', 0, '2026-09-26 04:00:00', 3, 2),
  ('Nueva ruta de transporte conecta Reñaca y el centro',
   'El recorrido reduce en quince minutos el viaje entre el sector norte y el plan de Viña.',
   'La nueva ruta de transporte público conecta Reñaca con el centro de Viña del Mar y reduce en quince minutos el tiempo de viaje.

Funcionará de lunes a domingo entre las 6:00 y las 23:00 horas.',
   'Transporte', 'nota4.jpg', 0, '2026-09-25 19:00:00', 1, 1),
  ('Regata internacional zarpa desde el puerto',
   'Más de cuarenta embarcaciones participan en la tradicional competencia náutica.',
   'Más de cuarenta embarcaciones de cinco países zarparon desde el puerto de Valparaíso en la tradicional regata internacional.

La llegada está prevista para el próximo domingo.',
   'Náutica', 'nota5.jpg', 0, '2026-09-25 17:00:00', 2, 3),
  ('Feria de emprendimiento reúne a pymes de la región',
   'Ochenta expositores muestran sus productos en la Quinta Vergara.',
   'Ochenta pymes de la región participan en la feria de emprendimiento que se realiza en la Quinta Vergara.

La entrada es liberada y la feria estará abierta hasta el domingo.',
   'Pymes', 'nota6.jpg', 0, '2026-09-25 12:00:00', 3, 2),
  ('Corrida familiar recorre la costanera de Viña',
   'Miles de personas participaron en la corrida de 5 y 10 kilómetros.',
   'Miles de personas participaron en la corrida familiar que recorrió la costanera de Viña del Mar en distancias de 5 y 10 kilómetros.',
   'Atletismo', 'destacada.jpg', 0, '2026-09-24 10:00:00', 2, 3),
  ('Nuevo complejo deportivo abre en Reñaca',
   'El recinto cuenta con canchas de pasto sintético, gimnasio y piscina temperada.',
   'El nuevo complejo deportivo de Reñaca abrió sus puertas con canchas de pasto sintético, gimnasio y piscina temperada para la comunidad.',
   'Deporte', 'nota4.jpg', 0, '2026-09-24 09:00:00', 2, 1),
  ('Comercio del centro proyecta un buen fin de semana largo',
   'La cámara de comercio espera un alza de ventas cercana al 15 %.',
   'La cámara de comercio de Viña del Mar proyecta un alza de ventas cercana al 15 % durante el fin de semana largo.',
   'Comercio', 'nota2.jpg', 0, '2026-09-23 18:00:00', 3, 2),
  ('Turismo regional crece impulsado por el borde costero',
   'La ocupación hotelera superó el 80 % durante el último mes.',
   'La ocupación hotelera de la región superó el 80 % durante el último mes, impulsada por las mejoras en el borde costero.',
   'Turismo', 'destacada.jpg', 0, '2026-09-23 11:00:00', 3, 1);

-- Usuario de ejemplo. La contraseña "lector123" se guarda cifrada
-- con password_hash() de PHP (algoritmo bcrypt), nunca en texto plano.
INSERT INTO `usuario` (`nombre`, `apellido`, `email`, `password_hash`, `plan`, `fecha_registro`, `id_localidad`) VALUES
  ('Lector', 'Demo', 'lector.demo@elfaro.cl',
   '$2y$12$Ra9bdnd6cAcR0X1bJOBod.8ZSz.JskqYNDy1rsTI6eX0p08ogReAO', 'gratuito', '2026-09-20 10:00:00', 1);

-- Mensajes de contacto de ejemplo: el primero lo envía el lector
-- registrado (id_usuario = 1); el segundo, un visitante sin cuenta.
-- (En este archivo sin triggers, id_usuario se indica directamente.)
INSERT INTO `contacto` (`nombre`, `email`, `asunto`, `mensaje`, `fecha_envio`, `id_usuario`) VALUES
  ('Lector Demo', 'lector.demo@elfaro.cl', 'Comentario',
   'Excelente la cobertura del nuevo paseo costero.', '2026-09-21 12:00:00', 1),
  ('Josefina Muñoz', 'jmunoz@correo.cl', 'Enviar una noticia',
   'Quiero informar sobre la feria navideña del cerro Castillo.', '2026-09-22 16:30:00', NULL);

-- ---------------------------------------------------------------------
-- 5. CONSULTAS DE VERIFICACIÓN
-- ---------------------------------------------------------------------
-- Artículos por página (página 1, 6 por página), más recientes primero:
--   SELECT * FROM vw_articulo_detalle ORDER BY fecha_publicacion DESC LIMIT 6 OFFSET 0;
-- Artículos de una sección:
--   SELECT * FROM vw_articulo_detalle WHERE seccion_slug = 'deporte';
-- Usuarios registrados con su localidad (sin exponer el hash):
--   SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.plan, u.fecha_registro, l.ciudad, l.pais
--   FROM usuario u LEFT JOIN localidad l ON l.id_localidad = u.id_localidad
--   ORDER BY u.fecha_registro DESC;
-- Mensajes de contacto con el lector que los envió (si está registrado):
--   SELECT c.id_contacto, c.nombre, c.email, c.asunto, c.estado,
--          u.id_usuario, CONCAT(u.nombre, ' ', u.apellido) AS lector
--   FROM contacto c LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
--   ORDER BY c.fecha_envio DESC;
-- Bitácora generada por los triggers:
--   SELECT * FROM bitacora ORDER BY fecha DESC;

-- Restaura el valor original de SQL_NOTES (última sentencia del script).
SET SQL_NOTES = @OLD_SQL_NOTES;
