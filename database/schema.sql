-- ============================================================
-- Bus Ticketing — skema database (MySQL 8 / MariaDB 10.4+)
-- Impor:  mysql -u root -p < database/schema.sql
--         (atau lewat phpMyAdmin: tab Import)
-- Uang disimpan sebagai bilangan bulat satuan terkecil (minor unit).
-- Waktu perjalanan disimpan dalam UTC.
-- ============================================================

CREATE DATABASE IF NOT EXISTS bus_ticketing
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bus_ticketing;

SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- Pengguna (kompatibel dengan autentikasi bawaan Laravel)
-- ------------------------------------------------------------
CREATE TABLE users (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name               VARCHAR(255) NOT NULL,
  email              VARCHAR(255) NOT NULL,
  email_verified_at  TIMESTAMP NULL DEFAULT NULL,
  password           VARCHAR(255) NOT NULL,
  phone              VARCHAR(30) NULL,
  role               ENUM('customer','operator_admin','super_admin') NOT NULL DEFAULT 'customer',
  preferred_locale   VARCHAR(10) NOT NULL DEFAULT 'id',
  preferred_currency CHAR(3) NOT NULL DEFAULT 'IDR',
  remember_token     VARCHAR(100) NULL,
  created_at         TIMESTAMP NULL DEFAULT NULL,
  updated_at         TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB;

CREATE TABLE password_reset_tokens (
  email      VARCHAR(255) NOT NULL,
  token      VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (email)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Lokasi
-- ------------------------------------------------------------
CREATE TABLE countries (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code          CHAR(2) NOT NULL,                -- ISO 3166-1 alpha-2
  name          VARCHAR(255) NOT NULL,
  currency_code CHAR(3) NOT NULL,                -- ISO 4217
  phone_prefix  VARCHAR(8) NULL,
  created_at    TIMESTAMP NULL DEFAULT NULL,
  updated_at    TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY countries_code_unique (code)
) ENGINE=InnoDB;

CREATE TABLE cities (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  country_id BIGINT UNSIGNED NOT NULL,
  name       VARCHAR(255) NOT NULL,
  slug       VARCHAR(255) NOT NULL,
  timezone   VARCHAR(64) NOT NULL,               -- contoh: Asia/Jakarta
  latitude   DECIMAL(10,7) NULL,
  longitude  DECIMAL(10,7) NULL,
  is_popular TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY cities_slug_unique (slug),
  KEY cities_country_name_idx (country_id, name),
  CONSTRAINT cities_country_fk FOREIGN KEY (country_id) REFERENCES countries (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE bus_stops (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  city_id    BIGINT UNSIGNED NOT NULL,
  name       VARCHAR(255) NOT NULL,              -- terminal / pool / agen
  address    VARCHAR(255) NULL,
  latitude   DECIMAL(10,7) NULL,
  longitude  DECIMAL(10,7) NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY bus_stops_city_idx (city_id),
  CONSTRAINT bus_stops_city_fk FOREIGN KEY (city_id) REFERENCES cities (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Operator & armada
-- ------------------------------------------------------------
CREATE TABLE operators (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(255) NOT NULL,
  slug          VARCHAR(255) NOT NULL,
  logo_path     VARCHAR(255) NULL,
  contact_email VARCHAR(255) NULL,
  contact_phone VARCHAR(30) NULL,
  rating        DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  country_id    BIGINT UNSIGNED NULL,
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP NULL DEFAULT NULL,
  updated_at    TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY operators_slug_unique (slug),
  CONSTRAINT operators_country_fk FOREIGN KEY (country_id) REFERENCES countries (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Templat denah kursi, dipakai ulang oleh banyak bus (format seat_map: lihat docs/skema-database.md)
CREATE TABLE bus_layouts (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(255) NOT NULL,             -- contoh: 2-2 Executive 40 kursi
  decks       TINYINT UNSIGNED NOT NULL DEFAULT 1,
  total_seats SMALLINT UNSIGNED NOT NULL,
  seat_map    JSON NOT NULL,
  created_at  TIMESTAMP NULL DEFAULT NULL,
  updated_at  TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE buses (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  operator_id   BIGINT UNSIGNED NOT NULL,
  bus_layout_id BIGINT UNSIGNED NOT NULL,
  plate_number  VARCHAR(255) NULL,
  bus_type      ENUM('seater','semi_sleeper','sleeper') NOT NULL,
  amenities     JSON NULL,                       -- ["ac","wifi","toilet","charging"]
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP NULL DEFAULT NULL,
  updated_at    TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY buses_operator_idx (operator_id),
  KEY buses_layout_idx (bus_layout_id),
  CONSTRAINT buses_operator_fk FOREIGN KEY (operator_id)   REFERENCES operators (id)   ON DELETE CASCADE,
  CONSTRAINT buses_layout_fk   FOREIGN KEY (bus_layout_id) REFERENCES bus_layouts (id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Rute
-- ------------------------------------------------------------
CREATE TABLE routes (
  id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  operator_id         BIGINT UNSIGNED NOT NULL,
  origin_city_id      BIGINT UNSIGNED NOT NULL,
  destination_city_id BIGINT UNSIGNED NOT NULL,
  distance_km         INT UNSIGNED NULL,
  duration_minutes    INT UNSIGNED NOT NULL,
  is_active           TINYINT(1) NOT NULL DEFAULT 1,
  created_at          TIMESTAMP NULL DEFAULT NULL,
  updated_at          TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY routes_operator_idx (operator_id),
  KEY routes_origin_dest_idx (origin_city_id, destination_city_id),
  CONSTRAINT routes_operator_fk    FOREIGN KEY (operator_id)         REFERENCES operators (id) ON DELETE CASCADE,
  CONSTRAINT routes_origin_fk      FOREIGN KEY (origin_city_id)      REFERENCES cities (id),
  CONSTRAINT routes_destination_fk FOREIGN KEY (destination_city_id) REFERENCES cities (id),
  CONSTRAINT routes_cities_differ  CHECK (origin_city_id <> destination_city_id)
) ENGINE=InnoDB;

-- Titik naik/turun sepanjang rute, berurutan
CREATE TABLE route_stops (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  route_id       BIGINT UNSIGNED NOT NULL,
  bus_stop_id    BIGINT UNSIGNED NOT NULL,
  stop_order     SMALLINT UNSIGNED NOT NULL,
  offset_minutes INT UNSIGNED NOT NULL DEFAULT 0,   -- menit sejak keberangkatan awal
  is_boarding    TINYINT(1) NOT NULL DEFAULT 1,
  is_dropping    TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY route_stops_order_unique (route_id, stop_order),
  KEY route_stops_stop_idx (bus_stop_id),
  CONSTRAINT route_stops_route_fk FOREIGN KEY (route_id)    REFERENCES routes (id) ON DELETE CASCADE,
  CONSTRAINT route_stops_stop_fk  FOREIGN KEY (bus_stop_id) REFERENCES bus_stops (id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Jadwal perjalanan
-- ------------------------------------------------------------
CREATE TABLE trips (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  route_id     BIGINT UNSIGNED NOT NULL,
  bus_id       BIGINT UNSIGNED NOT NULL,
  departure_at DATETIME NOT NULL,                -- UTC
  arrival_at   DATETIME NOT NULL,                -- UTC
  base_price   BIGINT UNSIGNED NOT NULL,         -- minor unit
  currency     CHAR(3) NOT NULL,
  status       ENUM('scheduled','departed','cancelled','completed') NOT NULL DEFAULT 'scheduled',
  created_at   TIMESTAMP NULL DEFAULT NULL,
  updated_at   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY trips_route_departure_idx (route_id, departure_at),
  KEY trips_bus_idx (bus_id),
  CONSTRAINT trips_route_fk FOREIGN KEY (route_id) REFERENCES routes (id) ON DELETE CASCADE,
  CONSTRAINT trips_bus_fk   FOREIGN KEY (bus_id)   REFERENCES buses (id),
  CONSTRAINT trips_time_order CHECK (arrival_at > departure_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Booking
-- ------------------------------------------------------------
CREATE TABLE bookings (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code             VARCHAR(12) NOT NULL,         -- kode booking di e-tiket
  user_id          BIGINT UNSIGNED NULL,         -- NULL = guest checkout
  trip_id          BIGINT UNSIGNED NOT NULL,
  boarding_stop_id BIGINT UNSIGNED NOT NULL,
  dropping_stop_id BIGINT UNSIGNED NOT NULL,
  contact_name     VARCHAR(255) NOT NULL,
  contact_email    VARCHAR(255) NOT NULL,
  contact_phone    VARCHAR(30) NOT NULL,
  total_amount     BIGINT UNSIGNED NOT NULL,
  currency         CHAR(3) NOT NULL,
  status           ENUM('pending','paid','expired','cancelled','refunded') NOT NULL DEFAULT 'pending',
  expires_at       DATETIME NOT NULL,            -- batas bayar = masa kunci kursi
  created_at       TIMESTAMP NULL DEFAULT NULL,
  updated_at       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY bookings_code_unique (code),
  KEY bookings_status_expires_idx (status, expires_at),
  KEY bookings_user_idx (user_id),
  KEY bookings_trip_idx (trip_id),
  CONSTRAINT bookings_user_fk     FOREIGN KEY (user_id)          REFERENCES users (id)     ON DELETE SET NULL,
  CONSTRAINT bookings_trip_fk     FOREIGN KEY (trip_id)          REFERENCES trips (id),
  CONSTRAINT bookings_boarding_fk FOREIGN KEY (boarding_stop_id) REFERENCES bus_stops (id),
  CONSTRAINT bookings_dropping_fk FOREIGN KEY (dropping_stop_id) REFERENCES bus_stops (id)
) ENGINE=InnoDB;

-- Satu baris per kursi per perjalanan; dibuat dari seat_map saat trip dibuat
CREATE TABLE trip_seats (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  trip_id           BIGINT UNSIGNED NOT NULL,
  seat_label        VARCHAR(8) NOT NULL,         -- 1A, 1B, L1, U3 ...
  deck              TINYINT UNSIGNED NOT NULL DEFAULT 1,
  price             BIGINT UNSIGNED NOT NULL,    -- bisa beda per kursi
  status            ENUM('available','locked','booked','blocked') NOT NULL DEFAULT 'available',
  locked_booking_id BIGINT UNSIGNED NULL,
  locked_until      DATETIME NULL,
  created_at        TIMESTAMP NULL DEFAULT NULL,
  updated_at        TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY trip_seats_label_unique (trip_id, seat_label),   -- mencegah dobel kursi
  KEY trip_seats_status_idx (trip_id, status),
  KEY trip_seats_booking_idx (locked_booking_id),
  CONSTRAINT trip_seats_trip_fk    FOREIGN KEY (trip_id)           REFERENCES trips (id)    ON DELETE CASCADE,
  CONSTRAINT trip_seats_booking_fk FOREIGN KEY (locked_booking_id) REFERENCES bookings (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE booking_passengers (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id   BIGINT UNSIGNED NOT NULL,
  trip_seat_id BIGINT UNSIGNED NOT NULL,
  name         VARCHAR(255) NOT NULL,
  age          TINYINT UNSIGNED NULL,
  gender       VARCHAR(10) NULL,
  PRIMARY KEY (id),
  KEY booking_passengers_booking_idx (booking_id),
  KEY booking_passengers_seat_idx (trip_seat_id),
  CONSTRAINT booking_passengers_booking_fk FOREIGN KEY (booking_id)   REFERENCES bookings (id)   ON DELETE CASCADE,
  CONSTRAINT booking_passengers_seat_fk    FOREIGN KEY (trip_seat_id) REFERENCES trip_seats (id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Pembayaran
-- ------------------------------------------------------------
CREATE TABLE payments (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id   BIGINT UNSIGNED NOT NULL,
  provider     VARCHAR(30) NOT NULL,             -- midtrans | xendit | stripe
  provider_ref VARCHAR(255) NULL,
  method       VARCHAR(30) NULL,                 -- va_bca | qris | gopay | card ...
  amount       BIGINT UNSIGNED NOT NULL,
  currency     CHAR(3) NOT NULL,
  status       ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  paid_at      DATETIME NULL,
  payload      JSON NULL,                        -- respons/webhook mentah untuk audit
  created_at   TIMESTAMP NULL DEFAULT NULL,
  updated_at   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY payments_booking_idx (booking_id),
  UNIQUE KEY payments_provider_ref_unique (provider, provider_ref),  -- idempoten webhook; NULL diperbolehkan saat payment masih pending
  KEY payments_provider_ref_idx (provider_ref),
  CONSTRAINT payments_booking_fk FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE
) ENGINE=InnoDB;
