-- =====================================================================
--  Diario El Faro — Limpiar datos de prueba (semana 9)
--  Vacía SOLO lo que generan los visitantes: comentarios, mensajes de
--  contacto, lectores registrados, sus localidades y la bitácora.
--  Conserva secciones, autores, artículos y sitios asociados.
--  Uso: phpMyAdmin > seleccionar la base el_faro > pestaña SQL > pegar > Continuar.
-- =====================================================================
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `bitacora`;
TRUNCATE TABLE `contacto`;
TRUNCATE TABLE `usuario`;
TRUNCATE TABLE `localidad`;
-- La tabla comentario existe desde la semana 9 (el_faro.sql o el_faro_semana9.sql)
TRUNCATE TABLE `comentario`;
SET FOREIGN_KEY_CHECKS = 1;
