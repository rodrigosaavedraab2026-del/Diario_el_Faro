-- =====================================================================
--  Diario El Faro — Script de base de datos (MySQL / MariaDB)
--  PRO301 Taller de Aplicaciones para Internet — Semanas 7 y 8 (AIEP)
--  Autor: Rodrigo Saavedra Ábalos
--
--  Probado en MariaDB 10.11 (motor de XAMPP) importando con phpMyAdmin
--  y con el cliente mysql, en importación limpia y en reimportación.
--
--  CÓMO IMPORTAR (XAMPP):
--    1. Iniciar Apache y MySQL en el panel de XAMPP.
--    2. En phpMyAdmin, pestaña "SQL", ejecutar:
--         CREATE DATABASE IF NOT EXISTS el_faro
--           CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
--       (o bien "Nueva" > nombre el_faro > cotejamiento utf8mb4_general_ci)
--    3. Hacer clic en la base el_faro (panel izquierdo) para seleccionarla.
--    4. Pestaña "Importar" > seleccionar este archivo > "Importar".
--  Importar con la base seleccionada evita los mensajes "#1046 Base de
--  datos no seleccionada" que phpMyAdmin muestra si no hay base activa.
--  El script se puede volver a importar las veces que se necesite:
--  borra y recrea sus propias tablas.
--
--  Contenido:
--    0. Preparación, limpieza y usuario de aplicación
--    1. Tablas (CREATE TABLE) con llaves primarias
--    2. Índices y llaves foráneas (ALTER TABLE ... ADD CONSTRAINT fk_...)
--    3. Vista de consulta para la capa Modelo
--    4. Trabajos automáticos: triggers y evento programado
--    4b. Procedimientos almacenados (semana 8): consultas e inserciones
--        que el sitio ejecuta con PDO desde la clase Conexion
--    5. Poblar tablas (INSERT)
--    6. Consultas de verificación (comentadas, para la pestaña SQL)
--
--  Codificación: utf8mb4 / utf8mb4_general_ci (admite tildes y ñ).
-- =====================================================================

-- ---------------------------------------------------------------------
-- 0. PREPARACIÓN, LIMPIEZA Y USUARIO DE APLICACIÓN
-- ---------------------------------------------------------------------
-- Codificación de la conexión: se fija ANTES de crear cualquier objeto.
SET NAMES utf8mb4;

-- Oculta los avisos informativos ("Note") de las sentencias IF EXISTS /
-- IF NOT EXISTS durante la importación (igual que hace mysqldump).
-- Se restaura al final del script.
SET @OLD_SQL_NOTES = @@SQL_NOTES;
SET SQL_NOTES = 0;

CREATE DATABASE IF NOT EXISTS `el_faro`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `el_faro`;

-- Limpieza: permite reimportar el script sin errores de "ya existe".
-- (Los triggers se eliminan junto con su tabla.)
DROP EVENT IF EXISTS `ev_archivar_contactos`;
DROP VIEW  IF EXISTS `vw_articulo_detalle`;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `comentario`, `sitio_asociado`, `bitacora`, `contacto`, `usuario`, `localidad`,
                     `articulo`, `autor`, `seccion`;
SET FOREIGN_KEY_CHECKS = 1;

-- Cuenta exclusiva para el sitio (solo privilegios sobre el_faro).
-- Se crea para los tres orígenes locales (igual que root en XAMPP):
-- 'localhost' (socket), '127.0.0.1' (TCP IPv4) y '::1' (TCP IPv6).
-- Así PHP puede conectarse sin importar cómo resuelva "localhost".
CREATE USER IF NOT EXISTS 'el_faro_app'@'localhost' IDENTIFIED BY 'ElFaro_2026';
CREATE USER IF NOT EXISTS 'el_faro_app'@'127.0.0.1' IDENTIFIED BY 'ElFaro_2026';
CREATE USER IF NOT EXISTS 'el_faro_app'@'::1'       IDENTIFIED BY 'ElFaro_2026';
-- EXECUTE (semana 8): permite al sitio ejecutar los procedimientos almacenados.
GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON `el_faro`.* TO 'el_faro_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON `el_faro`.* TO 'el_faro_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON `el_faro`.* TO 'el_faro_app'@'::1';

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
-- 4. TRABAJOS AUTOMÁTICOS
-- ---------------------------------------------------------------------
DELIMITER $$

-- 4.1 Trigger: al registrar un usuario se deja constancia en la bitácora
--     y se le asocian los mensajes de contacto que envió antes con el
--     mismo correo.
CREATE TRIGGER `trg_usuario_ai` AFTER INSERT ON `usuario`
FOR EACH ROW
BEGIN
  INSERT INTO `bitacora` (`tabla`, `accion`, `detalle`, `id_usuario`)
  VALUES ('usuario', 'INSERT',
          CONCAT('Nuevo lector: ', NEW.`email`, ' (plan ', NEW.`plan`, ')'),
          NEW.`id_usuario`);
  UPDATE `contacto`
     SET `id_usuario` = NEW.`id_usuario`
   WHERE `email` = NEW.`email` AND `id_usuario` IS NULL;
END$$

-- 4.2 Trigger: al publicar un artículo se registra en la bitácora
CREATE TRIGGER `trg_articulo_ai` AFTER INSERT ON `articulo`
FOR EACH ROW
BEGIN
  INSERT INTO `bitacora` (`tabla`, `accion`, `detalle`, `id_articulo`)
  VALUES ('articulo', 'INSERT',
          CONCAT('Artículo publicado: ', LEFT(NEW.`titulo`, 200)),
          NEW.`id_articulo`);
END$$

-- 4.3 Trigger: antes de guardar un mensaje de contacto, si el correo
--     pertenece a un lector registrado, se completa id_usuario.
CREATE TRIGGER `trg_contacto_bi` BEFORE INSERT ON `contacto`
FOR EACH ROW
BEGIN
  IF NEW.`id_usuario` IS NULL THEN
    SET NEW.`id_usuario` = (SELECT `id_usuario` FROM `usuario`
                             WHERE `email` = NEW.`email` LIMIT 1);
  END IF;
END$$

-- 4.4 Trigger: al recibir un contacto se registra en la bitácora
CREATE TRIGGER `trg_contacto_ai` AFTER INSERT ON `contacto`
FOR EACH ROW
BEGIN
  INSERT INTO `bitacora` (`tabla`, `accion`, `detalle`, `id_contacto`)
  VALUES ('contacto', 'INSERT',
          CONCAT('Mensaje de ', NEW.`email`, ' — ', NEW.`asunto`),
          NEW.`id_contacto`);
END$$

-- 4.5 Trigger: antes de guardar un artículo se normaliza el título
--     (se quitan espacios sobrantes al inicio y al final).
CREATE TRIGGER `trg_articulo_bi` BEFORE INSERT ON `articulo`
FOR EACH ROW
BEGIN
  SET NEW.`titulo` = TRIM(NEW.`titulo`);
END$$

-- 4.6 Evento programado: cada día archiva los mensajes de contacto
--     con más de 90 días. Se activa el planificador de eventos (en
--     XAMPP viene apagado). Para que quede activo aun después de
--     reiniciar MySQL, agregar event_scheduler=ON bajo [mysqld] en my.ini.
DELIMITER ;
SET GLOBAL event_scheduler = ON;
DELIMITER $$
CREATE EVENT `ev_archivar_contactos`
ON SCHEDULE EVERY 1 DAY
STARTS (CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 3 HOUR)
DO
BEGIN
  UPDATE `contacto`
     SET `estado` = 'archivado'
   WHERE `estado` <> 'archivado'
     AND `fecha_envio` < NOW() - INTERVAL 90 DAY;
END$$

DELIMITER ;

-- ---------------------------------------------------------------------
-- 4b. PROCEDIMIENTOS ALMACENADOS (semana 8)
--     Mismo contenido que database/el_faro_procedimientos.sql.
--     CONSULTAS:   sp_articulo_listar, sp_articulo_contar,
--                  sp_articulo_buscar, sp_usuario_listar
--     INSERCIONES: sp_articulo_registrar, sp_usuario_registrar,
--                  sp_contacto_registrar
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_articulo_listar`;
DROP PROCEDURE IF EXISTS `sp_articulo_contar`;
DROP PROCEDURE IF EXISTS `sp_articulo_buscar`;
DROP PROCEDURE IF EXISTS `sp_articulo_registrar`;
DROP PROCEDURE IF EXISTS `sp_usuario_listar`;
DROP PROCEDURE IF EXISTS `sp_usuario_registrar`;
DROP PROCEDURE IF EXISTS `sp_contacto_registrar`;

DELIMITER $$

-- ---------------------------------------------------------------------
-- CONSULTA: una página de artículos, del más reciente al más antiguo.
--   p_slug              sección a mostrar (NULL = todas)
--   p_excluir_destacado 1 = omite el artículo destacado (portada)
--   p_limite, p_offset  paginación. El límite se acota a 50 filas para
--                       evitar consultas que sobrecarguen el servidor.
-- ---------------------------------------------------------------------
CREATE PROCEDURE `sp_articulo_listar`(
  IN p_slug              VARCHAR(60),
  IN p_excluir_destacado TINYINT,
  IN p_limite            INT,
  IN p_offset            INT
)
READS SQL DATA
BEGIN
  DECLARE v_limite INT DEFAULT LEAST(GREATEST(IFNULL(p_limite, 6), 1), 50);
  DECLARE v_offset INT DEFAULT GREATEST(IFNULL(p_offset, 0), 0);

  SELECT *
    FROM `vw_articulo_detalle`
   WHERE (p_slug IS NULL OR `seccion_slug` = p_slug)
     AND (IFNULL(p_excluir_destacado, 0) = 0 OR `destacado` = 0)
   ORDER BY `fecha_publicacion` DESC, `id_articulo` DESC
   LIMIT v_offset, v_limite;
END$$

-- ---------------------------------------------------------------------
-- CONSULTA con parámetro de salida: cantidad de artículos.
--   CALL sp_articulo_contar('deporte', 0, @p_total); SELECT @p_total;
-- ---------------------------------------------------------------------
CREATE PROCEDURE `sp_articulo_contar`(
  IN  p_slug              VARCHAR(60),
  IN  p_excluir_destacado TINYINT,
  OUT p_total             INT
)
READS SQL DATA
BEGIN
  SELECT COUNT(*)
    INTO p_total
    FROM `vw_articulo_detalle`
   WHERE (p_slug IS NULL OR `seccion_slug` = p_slug)
     AND (IFNULL(p_excluir_destacado, 0) = 0 OR `destacado` = 0);
END$$

-- ---------------------------------------------------------------------
-- CONSULTA: un artículo completo por su id.
-- ---------------------------------------------------------------------
CREATE PROCEDURE `sp_articulo_buscar`(
  IN p_id INT
)
READS SQL DATA
BEGIN
  SELECT *
    FROM `vw_articulo_detalle`
   WHERE `id_articulo` = p_id;
END$$

-- ---------------------------------------------------------------------
-- INSERCIÓN: registra un artículo y devuelve su id (OUT p_id).
-- Trabaja en una TRANSACCIÓN: si el artículo es destacado, primero
-- quita la marca a los anteriores. Ante cualquier error se deshace todo
-- (ROLLBACK) y el error se devuelve a PHP (RESIGNAL -> PDOException).
-- ---------------------------------------------------------------------
CREATE PROCEDURE `sp_articulo_registrar`(
  IN  p_titulo     VARCHAR(200),
  IN  p_resumen    VARCHAR(300),
  IN  p_cuerpo     TEXT,
  IN  p_etiqueta   VARCHAR(40),
  IN  p_imagen     VARCHAR(120),
  IN  p_destacado  TINYINT,
  IN  p_id_seccion INT,
  IN  p_id_autor   INT,
  OUT p_id         INT
)
MODIFIES SQL DATA
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;
    IF p_destacado = 1 THEN
      UPDATE `articulo` SET `destacado` = 0 WHERE `destacado` = 1;
    END IF;

    INSERT INTO `articulo`
      (`titulo`, `resumen`, `cuerpo`, `etiqueta`, `imagen`, `destacado`, `id_seccion`, `id_autor`)
    VALUES
      (p_titulo, p_resumen, p_cuerpo, p_etiqueta, p_imagen, IFNULL(p_destacado, 0), p_id_seccion, p_id_autor);

    SET p_id = LAST_INSERT_ID();
  COMMIT;
END$$

-- ---------------------------------------------------------------------
-- CONSULTA: una página de lectores con su localidad y la cantidad de
-- mensajes de contacto enviados. Nunca devuelve el hash de la clave.
-- ---------------------------------------------------------------------
CREATE PROCEDURE `sp_usuario_listar`(
  IN p_limite INT,
  IN p_offset INT
)
READS SQL DATA
BEGIN
  DECLARE v_limite INT DEFAULT LEAST(GREATEST(IFNULL(p_limite, 6), 1), 50);
  DECLARE v_offset INT DEFAULT GREATEST(IFNULL(p_offset, 0), 0);

  SELECT u.`id_usuario`, u.`nombre`, u.`apellido`, u.`email`, u.`plan`, u.`fecha_registro`,
         l.`id_localidad`, l.`pais`, l.`ciudad`,
         (SELECT COUNT(*) FROM `contacto` c WHERE c.`id_usuario` = u.`id_usuario`) AS `mensajes`
    FROM `usuario` u
    LEFT JOIN `localidad` l ON l.`id_localidad` = u.`id_localidad`
   ORDER BY u.`fecha_registro` DESC, u.`id_usuario` DESC
   LIMIT v_offset, v_limite;
END$$

-- ---------------------------------------------------------------------
-- INSERCIÓN: registra un lector y devuelve su id (OUT p_id).
-- En una TRANSACCIÓN busca la localidad (país + ciudad); si no existe la
-- crea, y luego inserta el usuario con la llave foránea id_localidad.
-- ---------------------------------------------------------------------
CREATE PROCEDURE `sp_usuario_registrar`(
  IN  p_nombre        VARCHAR(80),
  IN  p_apellido      VARCHAR(80),
  IN  p_email         VARCHAR(150),
  IN  p_password_hash VARCHAR(255),
  IN  p_plan          VARCHAR(10),
  IN  p_pais          VARCHAR(80),
  IN  p_ciudad        VARCHAR(80),
  OUT p_id            INT
)
MODIFIES SQL DATA
BEGIN
  DECLARE v_id_localidad INT DEFAULT NULL;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;
    IF IFNULL(p_ciudad, '') <> '' THEN
      SET v_id_localidad = (SELECT `id_localidad` FROM `localidad`
                             WHERE `pais` = p_pais AND `ciudad` = p_ciudad
                             LIMIT 1);

      IF v_id_localidad IS NULL THEN
        INSERT INTO `localidad` (`pais`, `ciudad`) VALUES (p_pais, p_ciudad);
        SET v_id_localidad = LAST_INSERT_ID();
      END IF;
    END IF;

    INSERT INTO `usuario` (`nombre`, `apellido`, `email`, `password_hash`, `plan`, `id_localidad`)
    VALUES (p_nombre, p_apellido, p_email, p_password_hash, p_plan, v_id_localidad);

    SET p_id = LAST_INSERT_ID();
  COMMIT;
END$$

-- ---------------------------------------------------------------------
-- INSERCIÓN: registra un mensaje de contacto. Si el correo pertenece a
-- un lector registrado, lo vincula (llave foránea id_usuario).
-- Devuelve el id del mensaje (OUT p_id) y el del lector (OUT p_id_usuario,
-- NULL si quien escribe no tiene cuenta).
-- ---------------------------------------------------------------------
CREATE PROCEDURE `sp_contacto_registrar`(
  IN  p_nombre     VARCHAR(120),
  IN  p_email      VARCHAR(150),
  IN  p_asunto     VARCHAR(40),
  IN  p_mensaje    VARCHAR(2000),
  OUT p_id         INT,
  OUT p_id_usuario INT
)
MODIFIES SQL DATA
BEGIN
  DECLARE v_id_usuario INT DEFAULT NULL;

  SET v_id_usuario = (SELECT `id_usuario` FROM `usuario`
                       WHERE `email` = p_email
                       LIMIT 1);

  INSERT INTO `contacto` (`nombre`, `email`, `asunto`, `mensaje`, `id_usuario`)
  VALUES (p_nombre, p_email, p_asunto, p_mensaje, v_id_usuario);

  SET p_id = LAST_INSERT_ID();
  SET p_id_usuario = v_id_usuario;
END$$

DELIMITER ;


-- ---------------------------------------------------------------------
-- 5. POBLAR TABLAS
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
   '<p>El municipio inauguró la primera etapa de un proyecto que busca modernizar la costanera y potenciar el turismo local.</p>
<p>Vecinos y comerciantes valoran la iniciativa, que incorpora:</p>
<ul><li><strong>ciclovías</strong> continuas,</li><li>nuevas áreas verdes y</li><li>accesos inclusivos a lo largo de dos kilómetros de borde costero.</li></ul>
<blockquote>«Queremos que la costanera sea un lugar para caminar, no solo para pasar en auto», señalaron desde el municipio.</blockquote>
<p>Más información en el sitio de la <a href="https://www.vinadelmar.cl">Municipalidad de Viña del Mar</a>.</p>',
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

-- Usuario de ejemplo. La contraseña "lector123" se guarda como HASH (no cifrada)
-- con password_hash() de PHP (algoritmo bcrypt), nunca en texto plano.
INSERT INTO `usuario` (`nombre`, `apellido`, `email`, `password_hash`, `plan`, `fecha_registro`, `id_localidad`) VALUES
  ('Lector', 'Demo', 'lector.demo@elfaro.cl',
   '$2y$12$Ra9bdnd6cAcR0X1bJOBod.8ZSz.JskqYNDy1rsTI6eX0p08ogReAO', 'gratuito', '2026-09-20 10:00:00', 1);

-- Mensajes de contacto de ejemplo: el primero lo envía el lector
-- registrado (el trigger trg_contacto_bi completa id_usuario = 1);
-- el segundo, un visitante sin cuenta (id_usuario queda en NULL).
INSERT INTO `contacto` (`nombre`, `email`, `asunto`, `mensaje`, `fecha_envio`) VALUES
  ('Lector Demo', 'lector.demo@elfaro.cl', 'Comentario',
   'Excelente la cobertura del nuevo paseo costero.', '2026-09-21 12:00:00'),
  ('Josefina Muñoz', 'jmunoz@correo.cl', 'Enviar una noticia',
   'Quiero informar sobre la feria navideña del cerro Castillo.', '2026-09-22 16:30:00');

-- ---------------------------------------------------------------------
-- 5b. SEMANA 9: COMENTARIOS Y SITIOS ASOCIADOS (mismo contenido que
--     database/el_faro_semana9.sql)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `comentario`, `sitio_asociado`;

-- Comentario de un lector sobre un artículo (1 artículo : N comentarios)
CREATE TABLE `comentario` (
  `id_comentario`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_articulo`      INT UNSIGNED NOT NULL,
  `nombre`           VARCHAR(60)  NOT NULL,
  `email`            VARCHAR(150) NOT NULL,
  `texto`            VARCHAR(600) NOT NULL,
  `estado`           ENUM('publicado','oculto') NOT NULL DEFAULT 'publicado',
  `fecha_comentario` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_comentario`),
  KEY `ix_comentario_articulo` (`id_articulo`, `estado`, `fecha_comentario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- CASCADE: si se elimina el artículo, se eliminan sus comentarios.
ALTER TABLE `comentario` ADD CONSTRAINT `fk_comentario_articulo`
  FOREIGN KEY (`id_articulo`) REFERENCES `articulo` (`id_articulo`)
  ON DELETE CASCADE ON UPDATE RESTRICT;

-- Sitio asociado (vínculo externo). id_seccion es opcional: si se indica,
-- el vínculo también aparece en esa sección y en sus artículos.
CREATE TABLE `sitio_asociado` (
  `id_sitio`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(80)  NOT NULL,
  `url`         VARCHAR(255) NOT NULL,
  `descripcion` VARCHAR(160) NOT NULL,
  `categoria`   ENUM('Gobierno','Cultura','Deporte','Economía','Servicios') NOT NULL DEFAULT 'Servicios',
  `orden`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `activo`      TINYINT(1)   NOT NULL DEFAULT 1,
  `id_seccion`  INT UNSIGNED NULL,
  PRIMARY KEY (`id_sitio`),
  UNIQUE KEY `uq_sitio_url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- SET NULL: si se elimina la sección, el vínculo queda como general.
ALTER TABLE `sitio_asociado` ADD CONSTRAINT `fk_sitio_seccion`
  FOREIGN KEY (`id_seccion`) REFERENCES `seccion` (`id_seccion`)
  ON DELETE SET NULL ON UPDATE RESTRICT;

-- ---------------------------------------------------------------------
-- PROCEDIMIENTOS ALMACENADOS
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_comentario_listar`;
DROP PROCEDURE IF EXISTS `sp_comentario_registrar`;
DROP PROCEDURE IF EXISTS `sp_sitio_listar`;

DELIMITER $$

-- CONSULTA: comentarios publicados de un artículo (más antiguos primero).
-- Nunca devuelve el correo de quien comenta.
CREATE PROCEDURE `sp_comentario_listar`(
  IN p_id_articulo INT
)
READS SQL DATA
BEGIN
  SELECT `id_comentario`, `id_articulo`, `nombre`, `texto`, `fecha_comentario`
    FROM `comentario`
   WHERE `id_articulo` = p_id_articulo
     AND `estado` = 'publicado'
   ORDER BY `fecha_comentario` ASC, `id_comentario` ASC;
END$$

-- INSERCIÓN: registra un comentario y devuelve su id (OUT p_id).
CREATE PROCEDURE `sp_comentario_registrar`(
  IN  p_id_articulo INT,
  IN  p_nombre      VARCHAR(60),
  IN  p_email       VARCHAR(150),
  IN  p_texto       VARCHAR(600),
  OUT p_id          INT
)
MODIFIES SQL DATA
BEGIN
  INSERT INTO `comentario` (`id_articulo`, `nombre`, `email`, `texto`)
  VALUES (p_id_articulo, TRIM(p_nombre), LOWER(TRIM(p_email)), TRIM(p_texto));
  SET p_id = LAST_INSERT_ID();
END$$

-- CONSULTA: sitios asociados activos. p_slug NULL = todos;
-- con una sección, devuelve primero los de esa sección y luego los generales.
CREATE PROCEDURE `sp_sitio_listar`(
  IN p_slug VARCHAR(60)
)
READS SQL DATA
BEGIN
  SELECT sa.`id_sitio`, sa.`nombre`, sa.`url`, sa.`descripcion`, sa.`categoria`,
         s.`slug` AS `seccion_slug`
    FROM `sitio_asociado` sa
    LEFT JOIN `seccion` s ON s.`id_seccion` = sa.`id_seccion`
   WHERE sa.`activo` = 1
     AND (p_slug IS NULL OR s.`slug` = p_slug OR sa.`id_seccion` IS NULL)
   ORDER BY (p_slug IS NOT NULL AND s.`slug` = p_slug) DESC, sa.`orden`, sa.`nombre`;
END$$

DELIMITER ;

-- ---------------------------------------------------------------------
-- DATOS DE EJEMPLO
-- ---------------------------------------------------------------------
INSERT INTO `sitio_asociado` (`nombre`, `url`, `descripcion`, `categoria`, `orden`, `id_seccion`) VALUES
  ('Municipalidad de Viña del Mar', 'https://www.vinadelmar.cl', 'Trámites, servicios y noticias del municipio.', 'Gobierno', 1, NULL),
  ('Gobierno Regional de Valparaíso', 'https://www.gorevalparaiso.cl', 'Proyectos e inversión pública en la región.', 'Gobierno', 2, 1),
  ('Dirección Meteorológica de Chile', 'https://www.meteochile.gob.cl', 'Pronóstico del tiempo y alertas meteorológicas.', 'Servicios', 3, NULL),
  ('EFE Valparaíso', 'https://www.efe.cl', 'Horarios y estado del tren Limache–Puerto.', 'Servicios', 4, 1),
  ('Instituto Nacional de Deportes', 'https://www.ind.cl', 'Programas deportivos y recintos públicos.', 'Deporte', 5, 2),
  ('Sercotec', 'https://www.sercotec.cl', 'Apoyo y fondos para micro y pequeñas empresas.', 'Economía', 6, 3),
  ('Patrimonio Cultural', 'https://www.patrimoniocultural.gob.cl', 'Museos, bibliotecas y patrimonio de Chile.', 'Cultura', 7, 4);

INSERT INTO `comentario` (`id_articulo`, `nombre`, `email`, `texto`, `fecha_comentario`) VALUES
  (1, 'Camila Rojas', 'camila.rojas@correo.cl', 'Por fin una ciclovía continua por la costanera. Ojalá se mantenga bien iluminada de noche.', '2026-09-26 11:20:00'),
  (1, 'Lector Demo', 'lector.demo@elfaro.cl', '¿Se sabe cuándo empieza la segunda etapa del proyecto?', '2026-09-26 13:05:00');

-- Verificación:
--   CALL sp_comentario_listar(1);
--   CALL sp_sitio_listar(NULL);
--   CALL sp_sitio_listar('deporte');

-- ---------------------------------------------------------------------
-- 6. CONSULTAS DE VERIFICACIÓN
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
-- Procedimientos almacenados (semana 8):
--   SHOW PROCEDURE STATUS WHERE Db = 'el_faro';
--   CALL sp_articulo_listar(NULL, 0, 3, 0);
--   CALL sp_articulo_contar('deporte', 0, @p_total);  SELECT @p_total;
--   CALL sp_articulo_buscar(1);
--   CALL sp_usuario_listar(6, 0);

-- Restaura el valor original de SQL_NOTES (última sentencia del script).
SET SQL_NOTES = @OLD_SQL_NOTES;
