# Maping V1 (Static Recode)

Versi ini sudah direcode supaya **jalan di GitHub Pages** (tanpa PHP/MySQL).

## Yang berubah

- Entry point sekarang: `index.html`
- Semua data disimpan di browser (`localStorage`)
- CRUD untuk:
  - Server
  - ODC
  - ODP
  - Client
- Leaflet map + marker + garis relasi
- Search lokasi via Nominatim
- Export/Import JSON backup
- Demo data one-click
- Dark/Light theme toggle

## Catatan penting

Karena ini static app:

- File PHP lama (`login.php`, `map.php`, `api/*.php`, dll) **tidak dipakai** oleh GitHub Pages.
- Database MySQL dan integrasi Mikrotik tidak aktif di versi static.

## Deploy GitHub Pages

1. Push ke `main`
2. Repo Settings → Pages
3. Source: `Deploy from a branch`
4. Branch: `main` + `/ (root)`
5. Save

Selesai. URL Pages akan load `index.html`.
