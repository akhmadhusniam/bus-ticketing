# Bus Ticketing

Kerangka platform pemesanan tiket bus (Laravel). Path di folder ini **sama persis** dengan path di
proyek Laravel, jadi berkas yang Anda edit di sini = berkas yang akan dipakai.
Database memakai **SQL murni**, bukan migration Laravel.

## Mulai

```bash
./setup.sh                 # Windows: .\setup.ps1
cd ../bus-ticketing-app
mysql -u root -p < database/schema.sql   # atau Import lewat phpMyAdmin
mysql -u root -p < database/seed.sql     # kota & negara untuk form pencarian
code .
php artisan serve                        # buka http://127.0.0.1:8000
```

Setelah setup, **kerjakan di folder hasil setup**; folder kerangka ini tidak perlu dipakai lagi.
Saat VS Code menawarkan "Install recommended extensions", terima saja.
Tombol jalan cepat ada di `Terminal → Run Task…` (serve, impor schema/seed, dst).

## Konfigurasi .env

Tanpa migration, tabel `sessions`, `cache`, dan `jobs` bawaan Laravel tidak dibuat:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bus_ticketing
DB_USERNAME=root
DB_PASSWORD=

APP_LOCALE=id
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

**Jangan** menjalankan `php artisan migrate`.

## Halaman utama

| Berkas | Fungsi |
|---|---|
| `routes/web.php` | URL `/` (beranda) dan `/cari` (hasil pencarian) |
| `app/Http/Controllers/HomeController.php` | Mengambil daftar kota untuk form |
| `resources/views/home.blade.php` | **Halaman utama**: form cari + tujuan populer + 4 langkah |
| `resources/views/layouts/app.blade.php` | Kerangka umum (header, footer) |
| `public/css/app.css` | Seluruh gaya tampilan (tanpa npm/Vite) |
| `app/Http/Controllers/SearchController.php` | Validasi form pencarian |
| `resources/views/search/results.blade.php` | Hasil pencarian (masih placeholder) |

## Peta folder

```
.vscode/                      pengaturan editor, ekstensi, dan task
app/
  Console/Commands/           perintah artisan (ReleaseExpiredBookings)
  Exceptions/                 SeatUnavailableException
  Http/Controllers/           HomeController, SearchController
  Models/                     13 model Eloquent + relasi
  Services/Booking/           SeatLockService (kunci kursi, konfirmasi, pelepasan)
database/
  schema.sql                  seluruh skema (15 tabel)
  seed.sql                    negara & kota awal
docs/skema-database.md        relasi tabel, keputusan desain, format seat_map
public/css/app.css
resources/views/              layouts/, home, search/
routes/web.php
```

## Di mana mengedit apa

| Ingin mengubah… | Buka |
|---|---|
| Teks / isi halaman utama | `resources/views/home.blade.php` |
| Warna, ukuran, tampilan | `public/css/app.css` (warna ada di `:root` paling atas) |
| Nama situs | `APP_NAME` di `.env` |
| Kolom/tabel | `database/schema.sql`; untuk database yang sudah berisi, pakai `ALTER TABLE` |
| Relasi / tipe data kolom | `app/Models/` |
| Lama kunci kursi (15 menit) | `SeatLockService::LOCK_MINUTES` |

## Jadwalkan pelepasan kursi

Di `routes/console.php` pada proyek Laravel:

```php
use Illuminate\Support\Facades\Schedule;
Schedule::command('bookings:release-expired')->everyMinute();
```

## Langkah berikutnya

Data trip dummy (operator, bus, layout, jadwal) → `TripGenerator` (buat `trip_seats` dari `seat_map`)
→ daftar bus di `/cari` → denah kursi interaktif → checkout & pembayaran.
