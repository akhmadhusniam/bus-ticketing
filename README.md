# Bus Ticketing

Aplikasi pemesanan tiket bus berbasis **Laravel 12** dengan skema **SQL murni** (tanpa migration).

Semua yang dibutuhkan ada di folder ini. Tidak ada lagi folder "template" dan "aplikasi" terpisah.

## Menjalankan

Pastikan MySQL/XAMPP aktif, lalu impor skema dan data awal sekali saja:

```powershell
Get-Content database/schema.sql -Raw | mysql -u root -p
Get-Content database/seed.sql -Raw | mysql -u root -p
```

Buka aplikasi di:

```text
http://localhost/bus-ticketing/
```

Alamat `http://localhost/` juga masih aktif sebagai alias ke aplikasi yang sama.

Jika Apache belum mengarah ke folder ini, jalankan:

```powershell
.\setup-localhost.ps1
```

Script tersebut menambahkan VirtualHost `localhost` ke `public/` dan `Alias /bus-ticketing`, menyetel `APP_URL=http://localhost/bus-ticketing`, lalu me-restart Apache.

Alternatif tanpa VirtualHost (server bawaan Laravel):

```powershell
php artisan serve
# http://127.0.0.1:8000
```

## Konfigurasi `.env`

`.env` sudah disalin dari `.env.example`. Nilai penting:

```dotenv
APP_URL=http://localhost/bus-ticketing
APP_TIMEZONE=UTC
APP_DISPLAY_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bus_ticketing
DB_USERNAME=root
DB_PASSWORD=

APP_LOCALE=id
SESSION_DRIVER=file
CACHE_STORE=file
```

`APP_TIMEZONE=UTC` dipakai untuk penyimpanan `DATETIME`. `APP_DISPLAY_TIMEZONE` dipakai form tanggal, sedangkan validasi tanggal memakai `cities.timezone` kota asal.

**Jangan** menjalankan `php artisan migrate`. Tabel `sessions`, `cache`, dan `jobs` bawaan Laravel tidak dipakai.

## Penjadwalan pelepasan kursi

`routes/console.php` mendaftarkan:

```php
Schedule::command(ReleaseExpiredBookings::class)->everyMinute();
```

Cek dengan:

```powershell
php artisan schedule:list
```

Agar jalan otomatis, daftarkan ke Task Scheduler Windows:

```text
Program: C:\xampp\php\php.exe
Arguments: artisan schedule:run
Start in: C:\xampp\htdocs\bus-ticketing
```

## Test

PHPUnit memakai database terpisah `bus_ticketing_test` agar data utama tidak tersentuh.

```powershell
& 'C:\xampp\mysql\bin\mysql.exe' -u root -e "CREATE DATABASE IF NOT EXISTS bus_ticketing_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
Get-Content database/schema.sql -Raw | & 'C:\xampp\mysql\bin\mysql.exe' -u root bus_ticketing_test
Get-Content database/seed.sql -Raw | & 'C:\xampp\mysql\bin\mysql.exe' -u root bus_ticketing_test
php artisan test
php artisan test --filter=SeatExpiryTest
```

| Berkas | Fungsi |
|---|---|
| `tests/Feature/SeatExpiryTest.php` | Expire kursi, idempotensi, pesan ulang, webhook setelah expire, konfirmasi duplikat. |
| `tests/Feature/SeatReservationTest.php` | Validasi kursi/penumpang/stop, batal, refund, selisih jumlah pembayaran. |
| `tests/Feature/SearchValidationTest.php` | Validasi tanggal berdasarkan timezone kota asal. |
| `tests/Feature/ExampleTest.php` | Smoke test halaman utama `/`. |
| `tests/Feature/BookingFlowTest.php` | Alur booking → bayar → e-tiket dan konflik kursi. |
| `tests/Feature/SearchFilterTest.php` | Filter dan sorting hasil pencarian. |
| `tests/Feature/TripDetailTest.php` | Detail trip dan halaman 404. |
| `tests/Feature/TicketLookupTest.php` | Cek tiket dengan kode + email. |
| `tests/Feature/LandingPageTest.php`, `RouteLandingTest.php` | Beranda dan halaman rute/SEO. |

## Peta folder

```text
app/
  Console/Commands/     ReleaseExpiredBookings
  Exceptions/           SeatUnavailableException
  Http/Controllers/     HomeController, SearchController, SearchResultsController,
                        TripDetailController, BookingController, PaymentController,
                        TicketLookupController, RouteLandingController
  Models/               14 model Eloquent + relasi
  Services/Booking/     SeatLockService (inti booking)
bootstrap/              app.php, providers.php, cache/
config/                 konfigurasi Laravel
database/               schema.sql, seed.sql
docs/                   skema-database.md
public/                 index.php, .htaccess, css/app.css
resources/              views/, css/
routes/                 web.php, console.php
storage/                logs/ dan framework/ (diabaikan git)
tests/                  Concerns/, Feature/, TestCase.php
vendor/                 dependency Composer (diabaikan git)
.env                    konfigurasi lokal (diabaikan git)
artisan                 entry point CLI
composer.json           dependensi PHP
phpunit.xml             konfigurasi test
setup-localhost.ps1     bantu setup VirtualHost Apache
```

## Catatan arsitektur

- Skema database satu sumber kebenaran: `database/schema.sql`.
- `SeatLockService` mengunci booking dan kursi dengan `lockForUpdate()` saat reserve/confirm/release/cancel/refund.
- `payments` memiliki `UNIQUE (provider, provider_ref)` untuk idempotensi webhook.
- Kode booking dibuat dengan retry agar tidak tabrakan.

## Alur pemesanan

1. Cari di `/cari` lalu pilih jadwal.
2. Di `/trip/{trip}`, klik **Lanjut pesan**.
3. Di `/booking/{trip}`, pilih kursi, isi penumpang, titik naik/turun, dan kontak.
4. Kursi dikunci 15 menit; lanjut ke `/pembayaran/{code}` untuk pembayaran simulasi.
5. Setelah bayar, e-tiket langsung tersedia di `/tiket/{code}`.

Data demo yang bisa dipakai:

- Trip bookable: `id=5`, Jakarta → Bandung, **H+1 15:00 WIB** (mengikuti tanggal saat seed diimpor ulang), 4 kursi.
- Booking sudah lunas: `DEMO2026` / `pemesan@example.com`.
- Contoh alur: `/trip/5?origin=1&destination=2&date=2026-11-29&passengers=1` (sesuaikan tanggal setelah import seed).

Import ulang `database/seed.sql` kapan saja untuk menggeser jadwal demo ke tanggal depan. Query memakai `ON DUPLICATE KEY UPDATE` pada tabel `trips`, sehingga booking yang sudah ada tidak dihapus.

Peta berkas ringkas ada di `PETA-FILE.md`. Pengaturan VS Code untuk menyembunyikan folder generated ada di `.vscode/settings.json`.
