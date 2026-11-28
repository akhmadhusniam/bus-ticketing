# Peta File

Ringkasan cepat letak fitur dan berkas yang paling sering disentuh. Struktur Laravel sengaja tidak digabung agar tetap mengikuti konvensi.

## Alur utama

| Langkah | URL | Controller | View |
|---|---|---|---|
| Beranda / pencarian | `/`, `/cari` | `HomeController`, `SearchController` | `home.blade.php`, `search/results.blade.php` |
| Detail perjalanan | `/trip/{trip}` | `TripDetailController` | `trips/show.blade.php` |
| Pilih kursi & penumpang | `/booking/{trip}` | `BookingController` | `trips/booking.blade.php` |
| Pembayaran simulasi | `/pembayaran/{code}` | `PaymentController` | `tickets/payment.blade.php` |
| E-tiket | `/tiket/{code}` | `TicketLookupController` | `tickets/show.blade.php` |
| Cek tiket | `/cek-tiket` | `TicketLookupController` | `tickets/lookup.blade.php` |
| Halaman rute (SEO) | `/rute/{origin}/{destination}` | `RouteLandingController` | `routes/landing.blade.php` |

## Backend penting

| Berkas | Fungsi |
|---|---|
| `app/Http/Controllers/BookingController.php` | Validasi kursi/penumpang/stop, hitung ringkasan, panggil `reserve()`, dan menyiapkan view model denah kursi. |
| `app/Support/SeatMap.php` | View model denah skematis: grouping dek/baris, lokasi gang, status tekstual, simbol, rentang harga, kapasitas, dan jumlah kursi templat. |
| `app/Http/Controllers/PaymentController.php` | Halaman bayar, simulasi `Payment`, konfirmasi kursi. |
| `app/Http/Controllers/TicketLookupController.php` | Cari tiket dengan kode + email, tampilkan e-tiket. |
| `app/Http/Controllers/SearchResultsController.php` | Filter moda (`bus`/`shuttle`), operator, waktu, tipe bus, fasilitas, titik naik/turun, rating, sorting. |
| `app/Services/Booking/SeatLockService.php` | `reserve`, `confirm`, `cancel`, `refund`, `releaseExpired` dengan lock database. |
| `app/Support/Schedule.php` | Konversi waktu jadwal ke timezone kota titik. |
| `app/Models/` | Model Eloquent + relasi (`Booking`, `BookingPassenger`, `Payment`, `TripSeat`, dll). |
| `routes/web.php` | Semua route web, termasuk alias rute dan sitemap. |

## Database (SQL murni, tanpa migration)

| Berkas | Fungsi |
|---|---|
| `database/schema.sql` | Satu sumber kebenaran skema tabel, termasuk `buses.service_mode`. |
| `database/upgrade-service-mode.sql` | SQL idempoten untuk menambah kolom/index `service_mode` pada DB yang sudah ada (tanpa `migrate`). |
| `database/seed.sql` | Data demo: layout 40/24/14 kursi, bus + shuttle, trip 1-6, `trip_seats` sesuai kapasitas, dan booking `DEMO2026`. Jadwal trip diperbarui otomatis saat seed diimpor ulang. |
| `docs/skema-database.md` | Penjelasan hubungan tabel. |

## Frontend

| Berkas | Fungsi |
|---|---|
| `resources/views/layouts/app.blade.php` | Header, switcher Bus/Shuttle, footer, meta/JSON-LD, skeleton navigasi, dan toast global. |
| `resources/views/home.blade.php` | Hero, search bar ringkas (tanggal pulang opsional lewat toggle), promo voucher, trust band, testimoni, FAQ. |
| `resources/views/search/results.blade.php` | Daftar hasil (label moda + kapasitas/sisa kursi) + panel filter kiri (drawer bottom-sheet di mobile) + chip filter aktif. |
| `resources/views/routes/show.blade.php` | Halaman rute dengan jadwal bus/shuttle, kapasitas, dan tautan detail yang membawa `mode`. |
| `resources/views/trips/show.blade.php` | Detail trip dengan badge moda, kapasitas, dan kursi tersedia. |
| `resources/views/trips/booking.blade.php` | Denah skematis sesuai kapasitas (depan/belakang, gang, dek/baris), checkbox `seats[]` sebagai source of truth, form penumpang, titik naik/turun. |
| `resources/views/tickets/payment.blade.php` | Metode bayar dan countdown 15 menit. |
| `resources/views/tickets/show.blade.php` | E-tiket cetak. |
| `public/css/app.css` | Satu-satunya sumber token desain (`:root`, termasuk `--seat-tracks`) dan komponen CSS: `.btn`, `.card`, `.badge`, `.status`, `.seat-*`, `.bus-map`, `.bus-front`, `.bus-aisle`, `.bus-rear`, `.payment-*`, `.booking-layout`, `.page-skeleton`, `.toast`. |

## Test

| Berkas | Fungsi |
|---|---|
| `tests/Feature/BookingFlowTest.php` | Halaman booking (denah, legenda, counter, `seats[]`), pending booking, pembayaran, konflik kursi. |
| `tests/Unit/SeatMapTest.php` | Grouping dek/baris, gang, status/simbol, fallback kursi, kapasitas 40/24/14, dan rentang harga. |
| `tests/Feature/ModeFilterTest.php` | Pencarian `mode=bus` vs `mode=shuttle` beserta kapasitas yang tampil. |
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
- Invariansi kapasitas: `bus_layouts.total_seats` = jumlah `seat_map.seats` = jumlah `trip_seats` per trip (40/24/14 untuk data demo).
- `mode` pada pencarian menyaring `buses.service_mode`; switcher kini bukan sekadar label.
- `SeatLockService`, rute booking, dan format `seats[]` tidak berubah saat denah/kapasitas diperbarui.
- `http://localhost/bus-ticketing` adalah alamat kanonik; `http://localhost/` tetap aktif.