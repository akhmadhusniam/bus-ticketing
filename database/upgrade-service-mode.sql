-- Upgrade database yang sudah ada tanpa php artisan migrate.
-- Jalankan pada bus_ticketing dan bus_ticketing_test.

SET @column_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'buses'
    AND COLUMN_NAME = 'service_mode'
);
SET @sql = IF(
  @column_exists = 0,
  'ALTER TABLE buses ADD COLUMN service_mode ENUM(''bus'',''shuttle'') NOT NULL DEFAULT ''bus'' AFTER bus_type',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'buses'
    AND INDEX_NAME = 'buses_mode_idx'
);
SET @sql = IF(
  @index_exists = 0,
  'ALTER TABLE buses ADD KEY buses_mode_idx (service_mode, is_active)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE buses SET service_mode = 'shuttle' WHERE plate_number = 'B 5555 SHY';
