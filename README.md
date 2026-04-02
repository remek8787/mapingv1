# Mapping FTTH V1

Aplikasi web sederhana untuk pemetaan jaringan FTTH dengan alur:

`SERVER -> MIKROTIK -> ODC -> ODP/SPLITTER -> CLIENT`

Fitur utama:
- Tampilan **Google Maps** (roadmap)
- Tambah node berdasarkan koordinat
- Parent-child topology (jalur otomatis)
- Visual jalur per node
- Pencarian **secret PPPoE** (client)
- Trace route dari client ke server
- Export / Import JSON topologi
- Sample data untuk demo cepat

## Jalankan Lokal

Cukup buka `index.html` di browser modern.

> Rekomendasi: pakai live server (VSCode Live Server / `python -m http.server`) agar lebih stabil.

## Setup Google Maps

1. Buat API Key di Google Cloud (Maps JavaScript API aktif)
2. Buka aplikasi
3. Masukkan API key di panel kiri
4. Klik **Simpan**

API key disimpan di browser (`localStorage`), jadi tidak hardcoded ke repo.

## Struktur Data

```json
{
  "nodes": [
    {
      "id": "n1",
      "type": "SERVER|MIKROTIK|ODC|ODP|SPLITTER|CLIENT",
      "name": "Nama Node",
      "lat": -8.18,
      "lng": 112.44,
      "parentId": null,
      "secret": "(khusus client)",
      "splitterRatio": "1:8"
    }
  ],
  "links": [
    { "id": "l1", "from": "n1", "to": "n2" }
  ]
}
```

## Next Step (opsional)

- Integrasi Mikrotik RouterOS API langsung (search secret real-time)
- Multi-user + backend database (PostgreSQL)
- Layer status (up/down) dari monitoring
- Validasi kapasitas splitter/ODP port
