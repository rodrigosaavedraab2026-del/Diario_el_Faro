-- =====================================================================
--  Diario El Faro — PROCEDIMIENTOS ALMACENADOS (semana 8)
--  PRO301 Taller de Aplicaciones para Internet (AIEP)
--  Autor: Rodrigo Saavedra Ábalos
--
--  Encapsula en la base de datos las consultas e inserciones del sitio.
--  PHP los ejecuta con sentencias preparadas desde la clase Conexion:
--      CALL sp_articulo_registrar(?, ?, ?, ?, ?, ?, ?, ?, @p_id);
--      SELECT @p_id AS p_id;
--
--    CONSULTAS                         INSERCIONES
--    sp_articulo_listar   (IN)         sp_articulo_registrar (IN + OUT)
--    sp_articulo_contar   (IN + OUT)   sp_usuario_registrar  (IN + OUT)
--    sp_articulo_buscar   (IN)         sp_contacto_registrar (IN + OUT)
--    sp_usuario_listar    (IN)
--
--  Este archivo ya está incluido en el_faro.sql (XAMPP). Importarlo por
--  separado solo en un hosting que permita crear rutinas, después de
--  el_faro_hosting.sql y con la base de datos seleccionada.
--  Se puede reimportar: elimina y recrea sus propios procedimientos.
-- =====================================================================

SET NAMES utf8mb4;

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
-- PRUEBAS MANUALES (pestaña SQL de phpMyAdmin)
-- ---------------------------------------------------------------------
--   CALL sp_articulo_listar(NULL, 0, 3, 0);
--   CALL sp_articulo_listar('deporte', 0, 6, 0);
--   CALL sp_articulo_contar(NULL, 0, @p_total);  SELECT @p_total;
--   CALL sp_articulo_buscar(1);
--   CALL sp_usuario_listar(6, 0);
--   CALL sp_contacto_registrar('Ana Pérez', 'ana@correo.cl', 'Comentario',
--        'Mensaje de prueba desde phpMyAdmin.', @p_id, @p_id_usuario);
--   SELECT @p_id, @p_id_usuario;
