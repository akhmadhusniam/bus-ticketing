# Peta File

Ringkasan cepat letak fitur dan berkas yang paling sering disentuh. Struktur Laravel sengaja tidak digabung agar tetap mengikuti konvensi.

## Alur utama

| Langkah | URL | Controller | View |
|---|---|---|---|
| Beranda / pencarian | `/`, `/cari` | `HomeController`, `SearchController` | `pages/home.blade.php`, `search/results.blade.php` |
| Detail perjalanan | `/trip/{trip}` | `TripDetailController` | `trips/show.blade.php` |
| Pilih kursi & penumpang | `/booking/{trip}` | `BookingController` | `trips/booking.blade.php` |
| Pembayaran simulasi | `/pembayaran/{code}` | `PaymentController` | `tickets/payment.blade.php` |
| E-tiket | `/tiket/{code}` | `TicketLookupController` | `tickets/show.blade.php` |
| Cek tiket | `/cek-tiket` | `TicketLookupController` | `tickets/lookup.blade.php` |
| Halaman rute (SEO) | `/rute/{origin}/{destination}` | `RouteLandingController` | `routes/landing.blade.php` |

## Backend penting

| Berkas | Fungsi |
|---|---|
| `app/Http/Controllers/BookingController.php` | Validasi kursi/penumpang/stop, hitung ringkasan, panggil `reserve()`. |
| `app/Http/Controllers/PaymentController.php` | Halaman bayar, simulasi `Payment`, konfirmasi kursi. |
| `app/Http/Controllers/TicketLookupController.php` | Cari tiket dengan kode + email, tampilkan e-tiket. |
| `app/Http/Controllers/SearchResultsController.php` | Filter operator, waktu, tipe bus, fasilitas, titik naik/turun, rating, sorting. |
| `app/Services/Booking/SeatLockService.php` | `reserve`, `confirm`, `cancel`, `refund`, `releaseExpired` dengan lock database. |
| `app/Support/Schedule.php` | Konversi waktu jadwal ke timezone kota titik. |
| `app/Models/` | Model Eloquent + relasi (`Booking`, `BookingPassenger`, `Payment`, `TripSeat`, dll). |
| `routes/web.php` | Semua route web, termasuk alias rute dan sitemap. |

## Database (SQL murni, tanpa migration)

| Berkas | Fungsi |
|---|---|
| `database/schema.sql` | Satu sumber kebenaran skema tabel. |
| `database/seed.sql` | Data demo: kota, operator, rute, bus, trip, kursi, booking `DEMO2026`. |
| `docs/skema-database.md` | Penjelasan hubungan tabel. |

## Frontend

| Berkas | Fungsi |
|---|---|
| `resources/views/layouts/app.blade.php` | Header, switcher Bus/Shuttle, footer, meta/JSON-LD. |
| `resources/views/pages/home.blade.php` | Hero, search bar, promo voucher, trust band, testimoni, FAQ. |
| `resources/views/search/results.blade.php` | Daftar hasil + panel filter kiri + chip filter aktif. |
| `resources/views/trips/booking.blade.php` | Seat map, form penumpang, titik naik/turun. |
| `resources/views/tickets/payment.blade.php` | Metode bayar dan countdown 15 menit. |
| `resources/views/tickets/show.blade.php` | E-tiket cetak. |
| `public/css/app.css` | Seluruh gaya, termasuk kelas `.seat-*`, `.payment-*`, `.booking-layout`. |

## Test

| Berkas | Fungsi |
|---|---|
| `tests/Feature/BookingFlowTest.php` | Halaman booking, pending booking, pembayaran, konflik kursi. |
| `tests/Feature/SeatReservationTest.php` | Validasi reservation, stop order, batal/refund. |
| `tests/Feature/SeatExpiryTest.php` | Kedaluwarsa kursi dan idempotensi. |
| `tests/Feature/SearchFilterTest.php` | Filter dan sorting hasil pencarian. |
| `tests/Feature/SearchValidationTest.php` | Validasi tanggal berdasarkan timezone kota asal. |
| `tests/Feature/TripDetailTest.php` | Detail trip dan 404. |
| `tests/Feature/TicketLookupTest.php` | Cek tiket dengan kode + email. |
| `tests/Feature/LandingPageTest.php`, `RouteLandingTest.php`, `ExampleTest.php` | Smoke test beranda, SEO rute, root. |

## Pengetahuan penting

- `APP_TIMEZONE=UTC` untuk penyimpanan; `APP_DISPLAY_TIMEZONE=Asia/Jakarta` untuk tampilan.
- Jangan menjalankan `php artisan migrate`.
- Test memakai `bus_ticketing_test` dan `DatabaseTransactions`.
- `http://localhost/bus-ticketing` adalah alamat kanonik; `http://localhost/` tetap aktif.