-- ============================================================================
-- configuracion.sql — Tabla clave/valor de la consola de tema.
-- Idempotente: correr una vez en la base de cada sistema (IP ya la tiene).
--   mysql -u USUARIO -p NOMBRE_BASE < ui/theme/sql/configuracion.sql
-- ============================================================================

CREATE TABLE IF NOT EXISTS configuracion (
    clave VARCHAR(60)  NOT NULL PRIMARY KEY,
    valor TEXT         NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- No hace falta sembrar valores: cuando una clave no está en la tabla,
-- tema()/tema_todos() (ui/theme/tema.php) devuelve el valor de fábrica
-- de tema_defaults(), que reproduce el Manual de Imagen de la Facultad.
--
-- Ejemplo de override manual (opcional):
--   INSERT INTO configuracion (clave, valor) VALUES
--     ('nombre_sistema', 'Inventario de Red'),
--     ('color_primario', '#990000')
--   ON DUPLICATE KEY UPDATE valor = VALUES(valor);
