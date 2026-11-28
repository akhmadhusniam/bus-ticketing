-- ============================================================
-- Data awal: negara dan kota (cukup untuk halaman utama).
-- Jalankan SETELAH schema.sql dan upgrade-service-mode.sql:
--   mysql -u root < database/seed.sql
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

-- ------------------------------------------------------------
-- Data demo untuk hasil pencarian (idempoten: ID tetap + INSERT IGNORE).
-- ------------------------------------------------------------
INSERT IGNORE INTO operators (id, name, slug, contact_email, rating, country_id, is_active, created_at, updated_at) VALUES
  (1, 'Baraya Travel', 'baraya-travel', 'cs@barayatravel.example', 4.70, 1, 1, NOW(), NOW()),
  (2, 'DayTrans',      'daytrans',      'cs@daytrans.example',      4.40, 1, 1, NOW(), NOW()),
  (3, 'Sinar Jaya',    'sinar-jaya',    'cs@sinarjaya.example',     4.90, 1, 1, NOW(), NOW());

UPDATE operators SET rating = CASE id WHEN 1 THEN 4.70 WHEN 2 THEN 4.40 WHEN 3 THEN 4.90 ELSE rating END
WHERE id IN (1, 2, 3);

INSERT IGNORE INTO bus_layouts (id, name, decks, total_seats, seat_map, created_at, updated_at) VALUES
  (1, '2-2 Executive 40 kursi', 1, 40, '{"rows":10,"cols":4,"aisle_after":2,"seats":[{"label":"1A","row":1,"col":1,"deck":1,"type":"regular"},{"label":"1B","row":1,"col":2,"deck":1,"type":"regular"},{"label":"1C","row":1,"col":3,"deck":1,"type":"regular"},{"label":"1D","row":1,"col":4,"deck":1,"type":"regular"},{"label":"2A","row":2,"col":1,"deck":1,"type":"regular"},{"label":"2B","row":2,"col":2,"deck":1,"type":"regular"},{"label":"2C","row":2,"col":3,"deck":1,"type":"regular"},{"label":"2D","row":2,"col":4,"deck":1,"type":"regular"},{"label":"3A","row":3,"col":1,"deck":1,"type":"regular"},{"label":"3B","row":3,"col":2,"deck":1,"type":"regular"},{"label":"3C","row":3,"col":3,"deck":1,"type":"regular"},{"label":"3D","row":3,"col":4,"deck":1,"type":"regular"},{"label":"4A","row":4,"col":1,"deck":1,"type":"regular"},{"label":"4B","row":4,"col":2,"deck":1,"type":"regular"},{"label":"4C","row":4,"col":3,"deck":1,"type":"regular"},{"label":"4D","row":4,"col":4,"deck":1,"type":"regular"},{"label":"5A","row":5,"col":1,"deck":1,"type":"regular"},{"label":"5B","row":5,"col":2,"deck":1,"type":"regular"},{"label":"5C","row":5,"col":3,"deck":1,"type":"regular"},{"label":"5D","row":5,"col":4,"deck":1,"type":"regular"},{"label":"6A","row":6,"col":1,"deck":1,"type":"regular"},{"label":"6B","row":6,"col":2,"deck":1,"type":"regular"},{"label":"6C","row":6,"col":3,"deck":1,"type":"regular"},{"label":"6D","row":6,"col":4,"deck":1,"type":"regular"},{"label":"7A","row":7,"col":1,"deck":1,"type":"regular"},{"label":"7B","row":7,"col":2,"deck":1,"type":"regular"},{"label":"7C","row":7,"col":3,"deck":1,"type":"regular"},{"label":"7D","row":7,"col":4,"deck":1,"type":"regular"},{"label":"8A","row":8,"col":1,"deck":1,"type":"regular"},{"label":"8B","row":8,"col":2,"deck":1,"type":"regular"},{"label":"8C","row":8,"col":3,"deck":1,"type":"regular"},{"label":"8D","row":8,"col":4,"deck":1,"type":"regular"},{"label":"9A","row":9,"col":1,"deck":1,"type":"regular"},{"label":"9B","row":9,"col":2,"deck":1,"type":"regular"},{"label":"9C","row":9,"col":3,"deck":1,"type":"regular"},{"label":"9D","row":9,"col":4,"deck":1,"type":"regular"},{"label":"10A","row":10,"col":1,"deck":1,"type":"regular"},{"label":"10B","row":10,"col":2,"deck":1,"type":"regular"},{"label":"10C","row":10,"col":3,"deck":1,"type":"regular"},{"label":"10D","row":10,"col":4,"deck":1,"type":"regular"}]}', NOW(), NOW()),
  (2, '2-1 Sleeper 24 kursi', 2, 24, '{"rows":6,"cols":2,"aisle_after":1,"seats":[{"label":"L1","row":1,"col":1,"deck":1,"type":"sleeper"},{"label":"L2","row":1,"col":2,"deck":1,"type":"sleeper"},{"label":"L3","row":2,"col":1,"deck":1,"type":"sleeper"},{"label":"L4","row":2,"col":2,"deck":1,"type":"sleeper"},{"label":"L5","row":3,"col":1,"deck":1,"type":"sleeper"},{"label":"L6","row":3,"col":2,"deck":1,"type":"sleeper"},{"label":"L7","row":4,"col":1,"deck":1,"type":"sleeper"},{"label":"L8","row":4,"col":2,"deck":1,"type":"sleeper"},{"label":"L9","row":5,"col":1,"deck":1,"type":"sleeper"},{"label":"L10","row":5,"col":2,"deck":1,"type":"sleeper"},{"label":"L11","row":6,"col":1,"deck":1,"type":"sleeper"},{"label":"L12","row":6,"col":2,"deck":1,"type":"sleeper"},{"label":"U1","row":1,"col":1,"deck":2,"type":"sleeper"},{"label":"U2","row":1,"col":2,"deck":2,"type":"sleeper"},{"label":"U3","row":2,"col":1,"deck":2,"type":"sleeper"},{"label":"U4","row":2,"col":2,"deck":2,"type":"sleeper"},{"label":"U5","row":3,"col":1,"deck":2,"type":"sleeper"},{"label":"U6","row":3,"col":2,"deck":2,"type":"sleeper"},{"label":"U7","row":4,"col":1,"deck":2,"type":"sleeper"},{"label":"U8","row":4,"col":2,"deck":2,"type":"sleeper"},{"label":"U9","row":5,"col":1,"deck":2,"type":"sleeper"},{"label":"U10","row":5,"col":2,"deck":2,"type":"sleeper"},{"label":"U11","row":6,"col":1,"deck":2,"type":"sleeper"},{"label":"U12","row":6,"col":2,"deck":2,"type":"sleeper"}]}', NOW(), NOW()),
  (3, 'Shuttle 14 kursi', 1, 14, '{"rows":4,"cols":4,"aisle_after":2,"seats":[{"label":"1A","row":1,"col":1,"deck":1,"type":"regular"},{"label":"1B","row":1,"col":2,"deck":1,"type":"regular"},{"label":"1C","row":1,"col":3,"deck":1,"type":"regular"},{"label":"1D","row":1,"col":4,"deck":1,"type":"regular"},{"label":"2A","row":2,"col":1,"deck":1,"type":"regular"},{"label":"2B","row":2,"col":2,"deck":1,"type":"regular"},{"label":"2C","row":2,"col":3,"deck":1,"type":"regular"},{"label":"2D","row":2,"col":4,"deck":1,"type":"regular"},{"label":"3A","row":3,"col":1,"deck":1,"type":"regular"},{"label":"3B","row":3,"col":2,"deck":1,"type":"regular"},{"label":"3C","row":3,"col":3,"deck":1,"type":"regular"},{"label":"3D","row":3,"col":4,"deck":1,"type":"regular"},{"label":"4A","row":4,"col":1,"deck":1,"type":"regular"},{"label":"4B","row":4,"col":2,"deck":1,"type":"regular"}]}', NOW(), NOW());

-- Setiap import menyelaraskan kapasitas dengan daftar kursi pada templat.
UPDATE bus_layouts SET name = '2-2 Executive 40 kursi', decks = 1, total_seats = 40, seat_map = '{"rows":10,"cols":4,"aisle_after":2,"seats":[{"label":"1A","row":1,"col":1,"deck":1,"type":"regular"},{"label":"1B","row":1,"col":2,"deck":1,"type":"regular"},{"label":"1C","row":1,"col":3,"deck":1,"type":"regular"},{"label":"1D","row":1,"col":4,"deck":1,"type":"regular"},{"label":"2A","row":2,"col":1,"deck":1,"type":"regular"},{"label":"2B","row":2,"col":2,"deck":1,"type":"regular"},{"label":"2C","row":2,"col":3,"deck":1,"type":"regular"},{"label":"2D","row":2,"col":4,"deck":1,"type":"regular"},{"label":"3A","row":3,"col":1,"deck":1,"type":"regular"},{"label":"3B","row":3,"col":2,"deck":1,"type":"regular"},{"label":"3C","row":3,"col":3,"deck":1,"type":"regular"},{"label":"3D","row":3,"col":4,"deck":1,"type":"regular"},{"label":"4A","row":4,"col":1,"deck":1,"type":"regular"},{"label":"4B","row":4,"col":2,"deck":1,"type":"regular"},{"label":"4C","row":4,"col":3,"deck":1,"type":"regular"},{"label":"4D","row":4,"col":4,"deck":1,"type":"regular"},{"label":"5A","row":5,"col":1,"deck":1,"type":"regular"},{"label":"5B","row":5,"col":2,"deck":1,"type":"regular"},{"label":"5C","row":5,"col":3,"deck":1,"type":"regular"},{"label":"5D","row":5,"col":4,"deck":1,"type":"regular"},{"label":"6A","row":6,"col":1,"deck":1,"type":"regular"},{"label":"6B","row":6,"col":2,"deck":1,"type":"regular"},{"label":"6C","row":6,"col":3,"deck":1,"type":"regular"},{"label":"6D","row":6,"col":4,"deck":1,"type":"regular"},{"label":"7A","row":7,"col":1,"deck":1,"type":"regular"},{"label":"7B","row":7,"col":2,"deck":1,"type":"regular"},{"label":"7C","row":7,"col":3,"deck":1,"type":"regular"},{"label":"7D","row":7,"col":4,"deck":1,"type":"regular"},{"label":"8A","row":8,"col":1,"deck":1,"type":"regular"},{"label":"8B","row":8,"col":2,"deck":1,"type":"regular"},{"label":"8C","row":8,"col":3,"deck":1,"type":"regular"},{"label":"8D","row":8,"col":4,"deck":1,"type":"regular"},{"label":"9A","row":9,"col":1,"deck":1,"type":"regular"},{"label":"9B","row":9,"col":2,"deck":1,"type":"regular"},{"label":"9C","row":9,"col":3,"deck":1,"type":"regular"},{"label":"9D","row":9,"col":4,"deck":1,"type":"regular"},{"label":"10A","row":10,"col":1,"deck":1,"type":"regular"},{"label":"10B","row":10,"col":2,"deck":1,"type":"regular"},{"label":"10C","row":10,"col":3,"deck":1,"type":"regular"},{"label":"10D","row":10,"col":4,"deck":1,"type":"regular"}]}', updated_at = NOW() WHERE id = 1;
UPDATE bus_layouts SET name = '2-1 Sleeper 24 kursi', decks = 2, total_seats = 24, seat_map = '{"rows":6,"cols":2,"aisle_after":1,"seats":[{"label":"L1","row":1,"col":1,"deck":1,"type":"sleeper"},{"label":"L2","row":1,"col":2,"deck":1,"type":"sleeper"},{"label":"L3","row":2,"col":1,"deck":1,"type":"sleeper"},{"label":"L4","row":2,"col":2,"deck":1,"type":"sleeper"},{"label":"L5","row":3,"col":1,"deck":1,"type":"sleeper"},{"label":"L6","row":3,"col":2,"deck":1,"type":"sleeper"},{"label":"L7","row":4,"col":1,"deck":1,"type":"sleeper"},{"label":"L8","row":4,"col":2,"deck":1,"type":"sleeper"},{"label":"L9","row":5,"col":1,"deck":1,"type":"sleeper"},{"label":"L10","row":5,"col":2,"deck":1,"type":"sleeper"},{"label":"L11","row":6,"col":1,"deck":1,"type":"sleeper"},{"label":"L12","row":6,"col":2,"deck":1,"type":"sleeper"},{"label":"U1","row":1,"col":1,"deck":2,"type":"sleeper"},{"label":"U2","row":1,"col":2,"deck":2,"type":"sleeper"},{"label":"U3","row":2,"col":1,"deck":2,"type":"sleeper"},{"label":"U4","row":2,"col":2,"deck":2,"type":"sleeper"},{"label":"U5","row":3,"col":1,"deck":2,"type":"sleeper"},{"label":"U6","row":3,"col":2,"deck":2,"type":"sleeper"},{"label":"U7","row":4,"col":1,"deck":2,"type":"sleeper"},{"label":"U8","row":4,"col":2,"deck":2,"type":"sleeper"},{"label":"U9","row":5,"col":1,"deck":2,"type":"sleeper"},{"label":"U10","row":5,"col":2,"deck":2,"type":"sleeper"},{"label":"U11","row":6,"col":1,"deck":2,"type":"sleeper"},{"label":"U12","row":6,"col":2,"deck":2,"type":"sleeper"}]}', updated_at = NOW() WHERE id = 2;
UPDATE bus_layouts SET name = 'Shuttle 14 kursi', decks = 1, total_seats = 14, seat_map = '{"rows":4,"cols":4,"aisle_after":2,"seats":[{"label":"1A","row":1,"col":1,"deck":1,"type":"regular"},{"label":"1B","row":1,"col":2,"deck":1,"type":"regular"},{"label":"1C","row":1,"col":3,"deck":1,"type":"regular"},{"label":"1D","row":1,"col":4,"deck":1,"type":"regular"},{"label":"2A","row":2,"col":1,"deck":1,"type":"regular"},{"label":"2B","row":2,"col":2,"deck":1,"type":"regular"},{"label":"2C","row":2,"col":3,"deck":1,"type":"regular"},{"label":"2D","row":2,"col":4,"deck":1,"type":"regular"},{"label":"3A","row":3,"col":1,"deck":1,"type":"regular"},{"label":"3B","row":3,"col":2,"deck":1,"type":"regular"},{"label":"3C","row":3,"col":3,"deck":1,"type":"regular"},{"label":"3D","row":3,"col":4,"deck":1,"type":"regular"},{"label":"4A","row":4,"col":1,"deck":1,"type":"regular"},{"label":"4B","row":4,"col":2,"deck":1,"type":"regular"}]}', updated_at = NOW() WHERE id = 3;

INSERT IGNORE INTO buses (id, operator_id, bus_layout_id, plate_number, bus_type, service_mode, amenities, is_active, created_at, updated_at) VALUES
  (1, 1, 1, 'B 1111 BAR', 'seater',       'bus',     '["ac","wifi","charging"]', 1, NOW(), NOW()),
  (2, 2, 1, 'B 2222 DAY', 'seater',       'bus',     '["ac","wifi","toilet"]',   1, NOW(), NOW()),
  (3, 3, 2, 'B 3333 SJY', 'sleeper',      'bus',     '["ac","charging","blanket"]', 1, NOW(), NOW()),
  (4, 1, 2, 'B 4444 BAR', 'semi_sleeper', 'bus',     '["ac","wifi","blanket"]',  1, NOW(), NOW()),
  (5, 1, 3, 'B 5555 SHY', 'seater',       'shuttle', '["ac","wifi","charging"]', 1, NOW(), NOW());

UPDATE buses SET service_mode = CASE id WHEN 5 THEN 'shuttle' ELSE 'bus' END WHERE id BETWEEN 1 AND 5;

INSERT IGNORE INTO routes (id, operator_id, origin_city_id, destination_city_id, distance_km, duration_minutes, is_active, created_at, updated_at) VALUES
  (1, 1, 1, 2, 150, 180, 1, NOW(), NOW()),
  (2, 2, 1, 2, 157, 170, 1, NOW(), NOW()),
  (3, 3, 1, 5, 445, 480, 1, NOW(), NOW());

INSERT IGNORE INTO bus_stops (id, city_id, name, address, created_at, updated_at) VALUES
  (1, 1, 'Terminal Pulo Gebang',      'Jakarta Timur', NOW(), NOW()),
  (2, 1, 'Terminal Kampung Rambutan', 'Jakarta Timur', NOW(), NOW()),
  (3, 2, 'Terminal Leuwipanjang',     'Bandung', NOW(), NOW()),
  (4, 2, 'Terminal Caringin',         'Bandung', NOW(), NOW()),
  (5, 5, 'Terminal Terboyo',          'Semarang', NOW(), NOW()),
  (6, 5, 'Pool Sampangan',            'Semarang', NOW(), NOW());

INSERT IGNORE INTO route_stops (id, route_id, bus_stop_id, stop_order, offset_minutes, is_boarding, is_dropping) VALUES
  (1, 1, 1, 1, 0,   1, 0),
  (2, 1, 3, 2, 180, 0, 1),
  (3, 2, 2, 1, 0,   1, 0),
  (4, 2, 4, 2, 170, 0, 1),
  (5, 3, 1, 1, 0,   1, 0),
  (6, 3, 5, 2, 480, 0, 1);

INSERT INTO trips (id, route_id, bus_id, departure_at, arrival_at, base_price, currency, status, created_at, updated_at) VALUES
  (1, 1, 1,
   TIMESTAMP(CURDATE(), '23:00:00') - INTERVAL 7 HOUR,
   TIMESTAMP(CURDATE(), '23:00:00') - INTERVAL 7 HOUR + INTERVAL 180 MINUTE,
   95000, 'IDR', 'scheduled', NOW(), NOW()),
  (2, 1, 2,
   TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00') - INTERVAL 7 HOUR,
   TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00') - INTERVAL 7 HOUR + INTERVAL 180 MINUTE,
   120000, 'IDR', 'scheduled', NOW(), NOW()),
  (3, 2, 4,
   TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00') - INTERVAL 7 HOUR,
   TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00') - INTERVAL 7 HOUR + INTERVAL 170 MINUTE,
   78000, 'IDR', 'scheduled', NOW(), NOW()),
  (4, 3, 3,
   TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '12:00:00') - INTERVAL 7 HOUR,
   TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '12:00:00') - INTERVAL 7 HOUR + INTERVAL 480 MINUTE,
   250000, 'IDR', 'scheduled', NOW(), NOW()),
  (5, 1, 1,
   TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '15:00:00') - INTERVAL 7 HOUR,
   TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '15:00:00') - INTERVAL 7 HOUR + INTERVAL 180 MINUTE,
   105000, 'IDR', 'scheduled', NOW(), NOW()),
  (6, 1, 5,
   TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '17:00:00') - INTERVAL 7 HOUR,
   TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '17:00:00') - INTERVAL 7 HOUR + INTERVAL 180 MINUTE,
   65000, 'IDR', 'scheduled', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  departure_at = VALUES(departure_at),
  arrival_at   = VALUES(arrival_at),
  base_price   = VALUES(base_price),
  status       = VALUES(status),
  updated_at   = NOW();

-- Samakan label lama dengan templat sebelum baris baru disisipkan.
UPDATE trip_seats SET seat_label = '1A', deck = 1, updated_at = NOW() WHERE id = 5;
UPDATE trip_seats SET seat_label = '1B', deck = 1, updated_at = NOW() WHERE id = 6;
UPDATE trip_seats SET seat_label = '1C', deck = 1, updated_at = NOW() WHERE id = 3;
UPDATE trip_seats SET seat_label = '1D', deck = 1, updated_at = NOW() WHERE id = 4;
UPDATE trip_seats SET seat_label = '1C', deck = 1, updated_at = NOW() WHERE id = 7;
UPDATE trip_seats SET seat_label = '1D', deck = 1, updated_at = NOW() WHERE id = 8;
UPDATE trip_seats SET seat_label = '1C', deck = 1, updated_at = NOW() WHERE id = 19;
UPDATE trip_seats SET seat_label = '1D', deck = 1, updated_at = NOW() WHERE id = 20;
UPDATE trip_seats SET seat_label = 'L1', deck = 1, updated_at = NOW() WHERE id = 9;
UPDATE trip_seats SET seat_label = 'L2', deck = 1, updated_at = NOW() WHERE id = 10;
UPDATE trip_seats SET seat_label = 'L3', deck = 1, updated_at = NOW() WHERE id = 11;
UPDATE trip_seats SET seat_label = 'L4', deck = 1, updated_at = NOW() WHERE id = 12;
UPDATE trip_seats SET seat_label = 'L3', deck = 1, updated_at = NOW() WHERE id = 15;
UPDATE trip_seats SET seat_label = 'L4', deck = 1, updated_at = NOW() WHERE id = 16;

INSERT IGNORE INTO trip_seats (id, trip_id, seat_label, deck, price, status, created_at, updated_at) VALUES
  (1, 1, '1A', 1, 95000, 'available', NOW(), NOW()),
  (2, 1, '1B', 1, 95000, 'available', NOW(), NOW()),
  (3, 1, '1C', 1, 95000, 'available', NOW(), NOW()),
  (4, 1, '1D', 1, 95000, 'available', NOW(), NOW()),
  (5, 2, '1A', 1, 120000, 'available', NOW(), NOW()),
  (6, 2, '1B', 1, 120000, 'available', NOW(), NOW()),
  (7, 2, '1C', 1, 120000, 'available', NOW(), NOW()),
  (8, 2, '1D', 1, 120000, 'available', NOW(), NOW()),
  (9, 3, 'L1', 1, 78000, 'available', NOW(), NOW()),
  (10, 3, 'L2', 1, 78000, 'available', NOW(), NOW()),
  (11, 3, 'L3', 1, 78000, 'available', NOW(), NOW()),
  (12, 3, 'L4', 1, 78000, 'available', NOW(), NOW()),
  (13, 4, 'L1', 1, 250000, 'booked', NOW(), NOW()),
  (14, 4, 'L2', 1, 250000, 'available', NOW(), NOW()),
  (15, 4, 'L3', 1, 250000, 'available', NOW(), NOW()),
  (16, 4, 'L4', 1, 250000, 'available', NOW(), NOW()),
  (17, 5, '1A', 1, 105000, 'available', NOW(), NOW()),
  (18, 5, '1B', 1, 105000, 'available', NOW(), NOW()),
  (19, 5, '1C', 1, 105000, 'available', NOW(), NOW()),
  (20, 5, '1D', 1, 105000, 'available', NOW(), NOW()),
  (21, 1, '2A', 1, 95000, 'available', NOW(), NOW()),
  (22, 1, '2B', 1, 95000, 'available', NOW(), NOW()),
  (23, 1, '2C', 1, 95000, 'available', NOW(), NOW()),
  (24, 1, '2D', 1, 95000, 'available', NOW(), NOW()),
  (25, 1, '3A', 1, 95000, 'available', NOW(), NOW()),
  (26, 1, '3B', 1, 95000, 'available', NOW(), NOW()),
  (27, 1, '3C', 1, 95000, 'available', NOW(), NOW()),
  (28, 1, '3D', 1, 95000, 'available', NOW(), NOW()),
  (29, 1, '4A', 1, 95000, 'available', NOW(), NOW()),
  (30, 1, '4B', 1, 95000, 'available', NOW(), NOW()),
  (31, 1, '4C', 1, 95000, 'available', NOW(), NOW()),
  (32, 1, '4D', 1, 95000, 'available', NOW(), NOW()),
  (33, 1, '5A', 1, 95000, 'available', NOW(), NOW()),
  (34, 1, '5B', 1, 95000, 'available', NOW(), NOW()),
  (35, 1, '5C', 1, 95000, 'available', NOW(), NOW()),
  (36, 1, '5D', 1, 95000, 'available', NOW(), NOW()),
  (37, 1, '6A', 1, 95000, 'available', NOW(), NOW()),
  (38, 1, '6B', 1, 95000, 'available', NOW(), NOW()),
  (39, 1, '6C', 1, 95000, 'available', NOW(), NOW()),
  (40, 1, '6D', 1, 95000, 'available', NOW(), NOW()),
  (41, 1, '7A', 1, 95000, 'available', NOW(), NOW()),
  (42, 1, '7B', 1, 95000, 'available', NOW(), NOW()),
  (43, 1, '7C', 1, 95000, 'available', NOW(), NOW()),
  (44, 1, '7D', 1, 95000, 'available', NOW(), NOW()),
  (45, 1, '8A', 1, 95000, 'available', NOW(), NOW()),
  (46, 1, '8B', 1, 95000, 'available', NOW(), NOW()),
  (47, 1, '8C', 1, 95000, 'available', NOW(), NOW()),
  (48, 1, '8D', 1, 95000, 'available', NOW(), NOW()),
  (49, 1, '9A', 1, 95000, 'available', NOW(), NOW()),
  (50, 1, '9B', 1, 95000, 'available', NOW(), NOW()),
  (51, 1, '9C', 1, 95000, 'available', NOW(), NOW()),
  (52, 1, '9D', 1, 95000, 'available', NOW(), NOW()),
  (53, 1, '10A', 1, 95000, 'available', NOW(), NOW()),
  (54, 1, '10B', 1, 95000, 'available', NOW(), NOW()),
  (55, 1, '10C', 1, 95000, 'available', NOW(), NOW()),
  (56, 1, '10D', 1, 95000, 'available', NOW(), NOW()),
  (57, 2, '2A', 1, 120000, 'available', NOW(), NOW()),
  (58, 2, '2B', 1, 120000, 'available', NOW(), NOW()),
  (59, 2, '2C', 1, 120000, 'available', NOW(), NOW()),
  (60, 2, '2D', 1, 120000, 'available', NOW(), NOW()),
  (61, 2, '3A', 1, 120000, 'available', NOW(), NOW()),
  (62, 2, '3B', 1, 120000, 'available', NOW(), NOW()),
  (63, 2, '3C', 1, 120000, 'available', NOW(), NOW()),
  (64, 2, '3D', 1, 120000, 'available', NOW(), NOW()),
  (65, 2, '4A', 1, 120000, 'available', NOW(), NOW()),
  (66, 2, '4B', 1, 120000, 'available', NOW(), NOW()),
  (67, 2, '4C', 1, 120000, 'available', NOW(), NOW()),
  (68, 2, '4D', 1, 120000, 'available', NOW(), NOW()),
  (69, 2, '5A', 1, 120000, 'available', NOW(), NOW()),
  (70, 2, '5B', 1, 120000, 'available', NOW(), NOW()),
  (71, 2, '5C', 1, 120000, 'available', NOW(), NOW()),
  (72, 2, '5D', 1, 120000, 'available', NOW(), NOW()),
  (73, 2, '6A', 1, 120000, 'available', NOW(), NOW()),
  (74, 2, '6B', 1, 120000, 'available', NOW(), NOW()),
  (75, 2, '6C', 1, 120000, 'available', NOW(), NOW()),
  (76, 2, '6D', 1, 120000, 'available', NOW(), NOW()),
  (77, 2, '7A', 1, 120000, 'available', NOW(), NOW()),
  (78, 2, '7B', 1, 120000, 'available', NOW(), NOW()),
  (79, 2, '7C', 1, 120000, 'available', NOW(), NOW()),
  (80, 2, '7D', 1, 120000, 'available', NOW(), NOW()),
  (81, 2, '8A', 1, 120000, 'available', NOW(), NOW()),
  (82, 2, '8B', 1, 120000, 'available', NOW(), NOW()),
  (83, 2, '8C', 1, 120000, 'available', NOW(), NOW()),
  (84, 2, '8D', 1, 120000, 'available', NOW(), NOW()),
  (85, 2, '9A', 1, 120000, 'available', NOW(), NOW()),
  (86, 2, '9B', 1, 120000, 'available', NOW(), NOW()),
  (87, 2, '9C', 1, 120000, 'available', NOW(), NOW()),
  (88, 2, '9D', 1, 120000, 'available', NOW(), NOW()),
  (89, 2, '10A', 1, 120000, 'available', NOW(), NOW()),
  (90, 2, '10B', 1, 120000, 'available', NOW(), NOW()),
  (91, 2, '10C', 1, 120000, 'available', NOW(), NOW()),
  (92, 2, '10D', 1, 120000, 'available', NOW(), NOW()),
  (93, 3, 'L5', 1, 78000, 'available', NOW(), NOW()),
  (94, 3, 'L6', 1, 78000, 'available', NOW(), NOW()),
  (95, 3, 'L7', 1, 78000, 'available', NOW(), NOW()),
  (96, 3, 'L8', 1, 78000, 'available', NOW(), NOW()),
  (97, 3, 'L9', 1, 78000, 'available', NOW(), NOW()),
  (98, 3, 'L10', 1, 78000, 'available', NOW(), NOW()),
  (99, 3, 'L11', 1, 78000, 'available', NOW(), NOW()),
  (100, 3, 'L12', 1, 78000, 'available', NOW(), NOW()),
  (101, 3, 'U1', 2, 78000, 'available', NOW(), NOW()),
  (102, 3, 'U2', 2, 78000, 'available', NOW(), NOW()),
  (103, 3, 'U3', 2, 78000, 'available', NOW(), NOW()),
  (104, 3, 'U4', 2, 78000, 'available', NOW(), NOW()),
  (105, 3, 'U5', 2, 78000, 'available', NOW(), NOW()),
  (106, 3, 'U6', 2, 78000, 'available', NOW(), NOW()),
  (107, 3, 'U7', 2, 78000, 'available', NOW(), NOW()),
  (108, 3, 'U8', 2, 78000, 'available', NOW(), NOW()),
  (109, 3, 'U9', 2, 78000, 'available', NOW(), NOW()),
  (110, 3, 'U10', 2, 78000, 'available', NOW(), NOW()),
  (111, 3, 'U11', 2, 78000, 'available', NOW(), NOW()),
  (112, 3, 'U12', 2, 78000, 'available', NOW(), NOW()),
  (113, 4, 'L5', 1, 250000, 'available', NOW(), NOW()),
  (114, 4, 'L6', 1, 250000, 'available', NOW(), NOW()),
  (115, 4, 'L7', 1, 250000, 'available', NOW(), NOW()),
  (116, 4, 'L8', 1, 250000, 'available', NOW(), NOW()),
  (117, 4, 'L9', 1, 250000, 'available', NOW(), NOW()),
  (118, 4, 'L10', 1, 250000, 'available', NOW(), NOW()),
  (119, 4, 'L11', 1, 250000, 'available', NOW(), NOW()),
  (120, 4, 'L12', 1, 250000, 'available', NOW(), NOW()),
  (121, 4, 'U1', 2, 250000, 'available', NOW(), NOW()),
  (122, 4, 'U2', 2, 250000, 'available', NOW(), NOW()),
  (123, 4, 'U3', 2, 250000, 'available', NOW(), NOW()),
  (124, 4, 'U4', 2, 250000, 'available', NOW(), NOW()),
  (125, 4, 'U5', 2, 250000, 'available', NOW(), NOW()),
  (126, 4, 'U6', 2, 250000, 'available', NOW(), NOW()),
  (127, 4, 'U7', 2, 250000, 'available', NOW(), NOW()),
  (128, 4, 'U8', 2, 250000, 'available', NOW(), NOW()),
  (129, 4, 'U9', 2, 250000, 'available', NOW(), NOW()),
  (130, 4, 'U10', 2, 250000, 'available', NOW(), NOW()),
  (131, 4, 'U11', 2, 250000, 'available', NOW(), NOW()),
  (132, 4, 'U12', 2, 250000, 'available', NOW(), NOW()),
  (133, 5, '2A', 1, 105000, 'available', NOW(), NOW()),
  (134, 5, '2B', 1, 105000, 'available', NOW(), NOW()),
  (135, 5, '2C', 1, 105000, 'available', NOW(), NOW()),
  (136, 5, '2D', 1, 105000, 'available', NOW(), NOW()),
  (137, 5, '3A', 1, 105000, 'available', NOW(), NOW()),
  (138, 5, '3B', 1, 105000, 'available', NOW(), NOW()),
  (139, 5, '3C', 1, 105000, 'available', NOW(), NOW()),
  (140, 5, '3D', 1, 105000, 'available', NOW(), NOW()),
  (141, 5, '4A', 1, 105000, 'available', NOW(), NOW()),
  (142, 5, '4B', 1, 105000, 'available', NOW(), NOW()),
  (143, 5, '4C', 1, 105000, 'available', NOW(), NOW()),
  (144, 5, '4D', 1, 105000, 'available', NOW(), NOW()),
  (145, 5, '5A', 1, 105000, 'available', NOW(), NOW()),
  (146, 5, '5B', 1, 105000, 'available', NOW(), NOW()),
  (147, 5, '5C', 1, 105000, 'available', NOW(), NOW()),
  (148, 5, '5D', 1, 105000, 'available', NOW(), NOW()),
  (149, 5, '6A', 1, 105000, 'available', NOW(), NOW()),
  (150, 5, '6B', 1, 105000, 'available', NOW(), NOW()),
  (151, 5, '6C', 1, 105000, 'available', NOW(), NOW()),
  (152, 5, '6D', 1, 105000, 'available', NOW(), NOW()),
  (153, 5, '7A', 1, 105000, 'available', NOW(), NOW()),
  (154, 5, '7B', 1, 105000, 'available', NOW(), NOW()),
  (155, 5, '7C', 1, 105000, 'available', NOW(), NOW()),
  (156, 5, '7D', 1, 105000, 'available', NOW(), NOW()),
  (157, 5, '8A', 1, 105000, 'available', NOW(), NOW()),
  (158, 5, '8B', 1, 105000, 'available', NOW(), NOW()),
  (159, 5, '8C', 1, 105000, 'available', NOW(), NOW()),
  (160, 5, '8D', 1, 105000, 'available', NOW(), NOW()),
  (161, 5, '9A', 1, 105000, 'available', NOW(), NOW()),
  (162, 5, '9B', 1, 105000, 'available', NOW(), NOW()),
  (163, 5, '9C', 1, 105000, 'available', NOW(), NOW()),
  (164, 5, '9D', 1, 105000, 'available', NOW(), NOW()),
  (165, 5, '10A', 1, 105000, 'available', NOW(), NOW()),
  (166, 5, '10B', 1, 105000, 'available', NOW(), NOW()),
  (167, 5, '10C', 1, 105000, 'available', NOW(), NOW()),
  (168, 5, '10D', 1, 105000, 'available', NOW(), NOW()),
  (169, 6, '1A', 1, 65000, 'available', NOW(), NOW()),
  (170, 6, '1B', 1, 65000, 'available', NOW(), NOW()),
  (171, 6, '1C', 1, 65000, 'available', NOW(), NOW()),
  (172, 6, '1D', 1, 65000, 'available', NOW(), NOW()),
  (173, 6, '2A', 1, 65000, 'available', NOW(), NOW()),
  (174, 6, '2B', 1, 65000, 'available', NOW(), NOW()),
  (175, 6, '2C', 1, 65000, 'available', NOW(), NOW()),
  (176, 6, '2D', 1, 65000, 'available', NOW(), NOW()),
  (177, 6, '3A', 1, 65000, 'available', NOW(), NOW()),
  (178, 6, '3B', 1, 65000, 'available', NOW(), NOW()),
  (179, 6, '3C', 1, 65000, 'available', NOW(), NOW()),
  (180, 6, '3D', 1, 65000, 'available', NOW(), NOW()),
  (181, 6, '4A', 1, 65000, 'available', NOW(), NOW()),
  (182, 6, '4B', 1, 65000, 'available', NOW(), NOW());

-- Booking demo untuk halaman "Cek Tiket Saya": DEMO2026 / pemesan@example.com
INSERT IGNORE INTO bookings (id, code, user_id, trip_id, boarding_stop_id, dropping_stop_id, contact_name, contact_email, contact_phone, total_amount, currency, status, expires_at, created_at, updated_at) VALUES
  (1, 'DEMO2026', NULL, 4, 1, 5, 'Pemesan Demo', 'pemesan@example.com', '081234567890', 250000, 'IDR', 'paid', DATE_ADD(NOW(), INTERVAL 1 DAY), NOW(), NOW());

INSERT IGNORE INTO booking_passengers (id, booking_id, trip_seat_id, name, age, gender) VALUES
  (1, 1, 13, 'Penumpang Demo', 30, 'male');

INSERT IGNORE INTO payments (id, booking_id, provider, provider_ref, method, amount, currency, status, paid_at, payload, created_at, updated_at) VALUES
  (1, 1, 'midtrans', 'demo-2026', 'qris', 250000, 'IDR', 'paid', NOW(), '{}', NOW(), NOW());

UPDATE trip_seats SET status = 'booked', locked_until = NULL, locked_booking_id = NULL WHERE id = 13;
