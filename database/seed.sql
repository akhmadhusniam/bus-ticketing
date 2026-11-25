-- ============================================================
-- Data awal: negara dan kota (cukup untuk halaman utama).
-- Jalankan SETELAH schema.sql:
--   mysql -u root -p < database/seed.sql
-- Aman diimpor ulang (INSERT IGNORE + kunci unik code/slug).
-- ============================================================
USE bus_ticketing;

INSERT IGNORE INTO countries (code, name, currency_code, phone_prefix, created_at, updated_at) VALUES
  ('ID', 'Indonesia', 'IDR', '+62', NOW(), NOW()),
  ('MY', 'Malaysia',  'MYR', '+60', NOW(), NOW()),
  ('SG', 'Singapura', 'SGD', '+65', NOW(), NOW()),
  ('TH', 'Thailand',  'THB', '+66', NOW(), NOW()),
  ('VN', 'Vietnam',   'VND', '+84', NOW(), NOW());

INSERT IGNORE INTO cities (country_id, name, slug, timezone, is_popular, created_at, updated_at)
SELECT c.id, v.name, v.slug, v.tz, v.pop, NOW(), NOW()
FROM (
  SELECT 'ID' AS code, 'Jakarta' AS name,       'jakarta'      AS slug, 'Asia/Jakarta'      AS tz, 1 AS pop
  UNION ALL SELECT 'ID', 'Bandung',             'bandung',             'Asia/Jakarta',      1
  UNION ALL SELECT 'ID', 'Purwokerto',          'purwokerto',          'Asia/Jakarta',      1
  UNION ALL SELECT 'ID', 'Yogyakarta',          'yogyakarta',          'Asia/Jakarta',      1
  UNION ALL SELECT 'ID', 'Semarang',            'semarang',            'Asia/Jakarta',      1
  UNION ALL SELECT 'ID', 'Surabaya',            'surabaya',            'Asia/Jakarta',      1
  UNION ALL SELECT 'ID', 'Malang',              'malang',              'Asia/Jakarta',      0
  UNION ALL SELECT 'ID', 'Cirebon',             'cirebon',             'Asia/Jakarta',      0
  UNION ALL SELECT 'ID', 'Medan',               'medan',               'Asia/Jakarta',      0
  UNION ALL SELECT 'ID', 'Palembang',           'palembang',           'Asia/Jakarta',      0
  UNION ALL SELECT 'ID', 'Denpasar',            'denpasar',            'Asia/Makassar',     0
  UNION ALL SELECT 'MY', 'Kuala Lumpur',        'kuala-lumpur',        'Asia/Kuala_Lumpur', 1
  UNION ALL SELECT 'MY', 'Johor Bahru',         'johor-bahru',         'Asia/Kuala_Lumpur', 0
  UNION ALL SELECT 'SG', 'Singapura',           'singapura',           'Asia/Singapore',    1
  UNION ALL SELECT 'TH', 'Bangkok',             'bangkok',             'Asia/Bangkok',      0
  UNION ALL SELECT 'VN', 'Ho Chi Minh City',    'ho-chi-minh-city',    'Asia/Ho_Chi_Minh',  0
) v
JOIN countries c ON c.code = v.code;
