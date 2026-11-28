# Skema Database

## 1. Relasi antar tabel

```
countries 1─* cities 1─* bus_stops
operators 1─* buses *─1 bus_layouts
operators 1─* routes (origin_city, destination_city) 1─* route_stops *─1 bus_stops
routes 1─* trips *─1 buses
trips 1─* trip_seats
users 1─* bookings *─1 trips
bookings 1─* booking_passengers *─1 trip_seats
bookings 1─* payments
```

## 2. Keputusan desain penting

| Isu | Keputusan |
|---|---|
| Uang | Disimpan sebagai integer satuan terkecil + kolom `currency` (hindari float; siap multi-mata uang). |
| Waktu | `departure_at` / `arrival_at` disimpan UTC. `cities.timezone` dipakai saat menampilkan. |
| Kursi | `bus_layouts.seat_map` = templat; `trip_seats` = status nyata per perjalanan (dibuat saat trip dibuat). |
| Dobel booking | `unique(trip_id, seat_label)` + `lockForUpdate()` di `SeatLockService::reserve()`. |
| Kursi tertahan | Status `locked` + `locked_until` (15 menit); command per menit melepas yang kedaluwarsa. |
| Guest checkout | `bookings.user_id` nullable; kontak disimpan langsung di booking. |
| Audit pembayaran | `payments.payload` menyimpan respons/webhook mentah penyedia. |
| Idempoten webhook | `UNIQUE (provider, provider_ref)` mencegah pembayaran ganda; `SeatLockService::confirm()` mengunci booking dan aman dipanggil ulang. |
| Zona waktu | `app.timezone = UTC`, sehingga `now()` menghasilkan UTC sesuai format `DATETIME` di skema. Form memakai `APP_DISPLAY_TIMEZONE` (default `Asia/Jakarta`), sedangkan validasi tanggal memakai `cities.timezone` kota asal. |
| Transisi booking | `confirm()` pending→paid; `releaseExpired()` pending→expired; `cancel()` →cancelled; `refund()` →refunded sekaligus menandai payment `paid` menjadi `refunded`. Semua lepas/mengunci kursi di dalam transaksi + `lockForUpdate()`. |

## 3. Contoh format `bus_layouts.seat_map`

```json
{
  "rows": 10,
  "cols": 5,
  "seats": [
    {"label": "1A", "row": 1, "col": 1, "deck": 1, "type": "regular"},
    {"label": "1B", "row": 1, "col": 2, "deck": 1, "type": "regular"},
    {"label": "1C", "row": 1, "col": 4, "deck": 1, "type": "regular"},
    {"label": "1D", "row": 1, "col": 5, "deck": 1, "type": "premium"}
  ]
}
```
Kolom yang tidak dipakai (misalnya kolom 3) otomatis menjadi lorong saat denah digambar di frontend.

## 4. Catatan implementasi SQL

- Kolom status memakai `ENUM` (misalnya `trip_seats.status`: `available`, `locked`, `booked`, `blocked`).
  Menambah nilai baru butuh `ALTER TABLE`.
- `trip_seats.locked_booking_id` berelasi ke `bookings` (`ON DELETE SET NULL`).
- `booking_passengers.trip_seat_id` sengaja **tidak** unik. Kursi yang booking-nya kedaluwarsa bisa dipesan
  lagi, dan baris penumpang lama tetap tersimpan sebagai riwayat. Keunikan kursi dijaga oleh
  `UNIQUE (trip_id, seat_label)` pada `trip_seats` dan penguncian di `SeatLockService`.
- `CHECK` mencegah rute dengan kota asal = tujuan dan trip dengan waktu tiba sebelum berangkat
  (ditegakkan di MySQL 8.0.16+ dan MariaDB 10.2+).
