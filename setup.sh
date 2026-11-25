#!/usr/bin/env bash
# Membuat proyek Laravel baru lalu menyalin berkas kerangka ini ke dalamnya.
# Pemakaian: ./setup.sh [folder-tujuan]   (default: ../bus-ticketing-app)
set -e
DEST="${1:-../bus-ticketing-app}"
composer create-project laravel/laravel "$DEST"
rm -f "$DEST"/database/migrations/*.php   # skema memakai database/schema.sql, bukan migration
for item in .vscode app database routes resources public; do
  cp -R "$item" "$DEST/"
done
echo
echo "Selesai. Langkah berikutnya:"
echo "  1. Impor skema + data awal:  mysql -u root -p < \"$DEST/database/schema.sql\"  lalu  seed.sql"
echo "  2. Edit $DEST/.env  (lihat README bagian 'Konfigurasi .env')"
echo "  3. Buka di VS Code:  code \"$DEST\""
