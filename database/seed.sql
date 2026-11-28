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

-- ------------------------------------------------------------
-- Data demo untuk hasil pencarian (idempoten: ID tetap + INSERT IGNORE).
-- Boleh diimpor ulang; baris yang sudah ada akan dilewati.
-- ------------------------------------------------------------
INSERT IGNORE INTO operators (id, name, slug, contact_email, rating, country_id, is_active, created_at, updated_at) VALUES
  (1, 'Baraya Travel', 'baraya-travel', 'cs@barayatravel.example', 4.70, 1, 1, NOW(), NOW()),
  (2, 'DayTrans',      'daytrans',      'cs@daytrans.example',      4.40, 1, 1, NOW(), NOW()),
  (3, 'Sinar Jaya',    'sinar-jaya',    'cs@sinarjaya.example',     4.90, 1, 1, NOW(), NOW());

-- Pastikan rating demo ikut terisi saat seed diimpor ulang ke database yang sudah ada.
UPDATE operators SET rating = CASE id WHEN 1 THEN 4.70 WHEN 2 THEN 4.40 WHEN 3 THEN 4.90 ELSE rating END
WHERE id IN (1, 2, 3);

INSERT IGNORE INTO bus_layouts (id, name, decks, total_seats, seat_map, created_at, updated_at) VALUES
  (1, '2-2 Executive 40 kursi', 1, 40,
   '{"rows":2,"cols":2,"seats":[{"label":"1A","row":1,"col":1,"deck":1,"type":"regular"},{"label":"1B","row":1,"col":2,"deck":1,"type":"regular"},{"label":"2A","row":2,"col":1,"deck":1,"type":"regular"},{"label":"2B","row":2,"col":2,"deck":1,"type":"regular"}]}',
   NOW(), NOW()),
  (2, '2-1 Sleeper 24 kursi', 1, 24,
   '{"rows":2,"cols":2,"seats":[{"label":"L1","row":1,"col":1,"deck":1,"type":"sleeper"},{"label":"L2","row":1,"col":2,"deck":1,"type":"sleeper"},{"label":"U1","row":2,"col":1,"deck":2,"type":"sleeper"},{"label":"U2","row":2,"col":2,"deck":2,"type":"sleeper"}]}',
   NOW(), NOW());

INSERT IGNORE INTO buses (id, operator_id, bus_layout_id, plate_number, bus_type, amenities, is_active, created_at, updated_at) VALUES
  (1, 1, 1, 'B 1111 BAR', 'seater',       '["ac","wifi","charging"]', 1, NOW(), NOW()),
  (2, 2, 1, 'B 2222 DAY', 'seater',       '["ac","wifi","toilet"]',   1, NOW(), NOW()),
  (3, 3, 2, 'B 3333 SJY', 'sleeper',      '["ac","charging","blanket"]', 1, NOW(), NOW()),
  (4, 1, 2, 'B 4444 BAR', 'semi_sleeper', '["ac","wifi","blanket"]',  1, NOW(), NOW());

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

INSERT IGNORE INTO trips (id, route_id, bus_id, departure_at, arrival_at, base_price, currency, status, created_at, updated_at) VALUES
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
   105000, 'IDR', 'scheduled', NOW(), NOW());

INSERT IGNORE INTO trip_seats (id, trip_id, seat_label, deck, price, status, created_at, updated_at) VALUES
  (1,  1, '1A', 1, 95000,  'available', NOW(), NOW()),
  (2,  1, '1B', 1, 95000,  'available', NOW(), NOW()),
  (3,  1, '2A', 1, 95000,  'available', NOW(), NOW()),
  (4,  1, '2B', 1, 95000,  'available', NOW(), NOW()),
  (5,  2, '1A', 1, 120000, 'available', NOW(), NOW()),
  (6,  2, '1B', 1, 120000, 'available', NOW(), NOW()),
  (7,  2, '2A', 1, 120000, 'available', NOW(), NOW()),
  (8,  2, '2B', 1, 120000, 'available', NOW(), NOW()),
  (9,  3, '1A', 1, 78000,  'available', NOW(), NOW()),
  (10, 3, '1B', 1, 78000,  'available', NOW(), NOW()),
  (11, 3, '2A', 1, 78000,  'available', NOW(), NOW()),
  (12, 3, '2B', 1, 78000,  'available', NOW(), NOW()),
  (13, 4, 'L1', 1, 250000, 'available', NOW(), NOW()),
  (14, 4, 'L2', 1, 250000, 'available', NOW(), NOW()),
  (15, 4, 'U1', 2, 250000, 'available', NOW(), NOW()),
  (16, 4, 'U2', 2, 250000, 'available', NOW(), NOW()),
  (17, 5, '1A', 1, 105000, 'available', NOW(), NOW()),
  (18, 5, '1B', 1, 105000, 'available', NOW(), NOW()),
  (19, 5, '2A', 1, 105000, 'available', NOW(), NOW()),
  (20, 5, '2B', 1, 105000, 'available', NOW(), NOW());

-- Booking demo untuk halaman "Cek Tiket Saya": DEMO2026 / pemesan@example.com
INSERT IGNORE INTO bookings (id, code, user_id, trip_id, boarding_stop_id, dropping_stop_id, contact_name, contact_email, contact_phone, total_amount, currency, status, expires_at, created_at, updated_at) VALUES
  (1, 'DEMO2026', NULL, 4, 1, 5, 'Pemesan Demo', 'pemesan@example.com', '081234567890', 250000, 'IDR', 'paid', DATE_ADD(NOW(), INTERVAL 1 DAY), NOW(), NOW());

INSERT IGNORE INTO booking_passengers (id, booking_id, trip_seat_id, name, age, gender) VALUES
  (1, 1, 13, 'Penumpang Demo', 30, 'male');

INSERT IGNORE INTO payments (id, booking_id, provider, provider_ref, method, amount, currency, status, paid_at, payload, created_at, updated_at) VALUES
  (1, 1, 'midtrans', 'demo-2026', 'qris', 250000, 'IDR', 'paid', NOW(), '{}', NOW(), NOW());

UPDATE trip_seats SET status = 'booked', locked_until = NULL, locked_booking_id = NULL WHERE id = 13;
