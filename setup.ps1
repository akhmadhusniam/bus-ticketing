# Membuat proyek Laravel baru lalu menyalin berkas kerangka ini ke dalamnya.
# Pemakaian: .\setup.ps1 [-Dest ..\bus-ticketing-app]
param([string]$Dest = "..\bus-ticketing-app")
composer create-project laravel/laravel $Dest
Remove-Item "$Dest\database\migrations\*.php" -ErrorAction SilentlyContinue   # skema memakai schema.sql
foreach ($item in ".vscode", "app", "database", "routes", "resources", "public") {
    Copy-Item -Recurse -Force $item $Dest
}
Write-Host ""
Write-Host "Selesai. Langkah berikutnya:"
Write-Host "  1. Impor database\schema.sql lalu database\seed.sql  (mysql atau phpMyAdmin)"
Write-Host "  2. Edit $Dest\.env  (lihat README bagian 'Konfigurasi .env')"
Write-Host "  3. Buka di VS Code:  code $Dest"
