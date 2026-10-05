-- =====================================================================
--  Diario El Faro — Triggers opcionales para HOSTING
--  Importar DESPUÉS de el_faro_hosting.sql, con la base seleccionada,
--  solo si el hosting permite crear triggers (privilegio TRIGGER).
--  Los triggers registran en la tabla bitacora cada nuevo artículo,
--  usuario y mensaje de contacto. El sitio funciona igual sin ellos.
-- =====================================================================

SET NAMES utf8mb4;
SET @OLD_SQL_NOTES = @@SQL_NOTES;
SET SQL_NOTES = 0;

DROP TRIGGER IF EXISTS `trg_usuario_ai`;
DROP TRIGGER IF EXISTS `trg_articulo_ai`;
DROP TRIGGER IF EXISTS `trg_contacto_ai`;
DROP TRIGGER IF EXISTS `trg_articulo_bi`;
DROP TRIGGER IF EXISTS `trg_contacto_bi`;

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

DELIMITER ;

SET SQL_NOTES = @OLD_SQL_NOTES;
