Network Mapping – README
Ringkasan

Aplikasi web untuk memetakan Server → ODP/ODC → Client di peta (Leaflet). Data diambil dari API lokal (PHP + MySQL).
Fitur utama:

Marker Server, ODP/ODC, dan Client di peta.

Client berwarna: hijau = active, merah = inactive/offline.

Auto-refresh data client tiap 2 menit.

Persist view: posisi/zoom/baselayer disimpan di localStorage.

Pencarian lokasi (Nominatim), quick search data, form Tambah/Edit/Hapus.

Struktur Berkas (intinya)
/mapping-client/
├─ map.php                # Halaman utama (UI + Leaflet + forms)
├─ config.php             # Koneksi DB & konstanta default center/zoom
└─ api/
   ├─ servers.php         # GET daftar server (JSON)
   ├─ odp.php             # GET daftar ODP/ODC (JSON)
   ├─ clients.php         # GET daftar client (JSON, +status/ip/uptime/service)
   └─ secrets.php         # GET daftar secret PPPoE (untuk select)

Database (skema minimal)

Sesuaikan dengan yang sudah ada. Berikut kolom yang dipakai map.php:

servers

id, name, description, latitude, longitude

odp

id, name, capacity, description, server_id, latitude, longitude

(opsional API bisa kembalikan server_name)

clients

id, name, address, description, secret_username, odp_id, latitude, longitude

(opsional dari integrasi Mikrotik di API clients.php):

status (active|inactive|offline|down)

ip, uptime, service

odp_name, odp_capacity (atau di-join di map lewat odp_id)

Konfigurasi

config.php

<?php
// Koneksi MySQL
$conn = new mysqli('localhost','user','pass','dbname');

// (opsional) default center/zoom peta
define('DEFAULT_CENTER_LAT', -7.9673);
define('DEFAULT_CENTER_LNG', 112.6326);
define('DEFAULT_ZOOM', 17);

Cara Jalanin

Pastikan PHP + MySQL aktif (XAMPP/laragon).

Pastikan tabel & data minimal ada.

Akses http://localhost/mapping-client/map.php.

API (yang dipakai map.php)

GET api/servers.php → [{id,name,description,latitude,longitude}]

GET api/odp.php → [{id,name,capacity,description,server_id,latitude,longitude,server_name?}]

GET api/clients.php → [{id,name,address,description,secret_username,odp_id,latitude,longitude,status?,ip?,uptime?,service?,odp_name?,odp_capacity?}]

GET api/secrets.php?limit=5000 → [{secret_username,description?}]

Catatan: map.php juga melakukan POST ke dirinya sendiri untuk tambah/edit dan GET ?delete=... untuk hapus (server/odp/client).

Alur Penting di map.php

Persist View

Simpan: localStorage['nm_center'], nm_zoom, nm_baselayer saat moveend/zoomend/baselayerchange.

Restore saat halaman dibuka.

Auto-Refresh Client

setInterval(loadClients, 120000);

Warna Client

active → #16a34a (hijau)

inactive/offline/down → #dc2626 (merah)

selain itu → abu-abu

Circle Marker

Client: circle radius 9, fillOpacity: 0.95, stroke tipis.

ODP: circle radius 8 (biru).

Server: marker default.

Cara Pakai di UI

Pilih Aksi di sidebar → Tambah Server/ODP/Client.

Klik peta untuk mengisi koordinat (lat/lng otomatis).

Client → Secret

Manual: ketik secret_username

Mikrotik (Find): pilih mode “Mikrotik”, ketik di kolom cari, pilih dari daftar.

Simpan → data tersimpan & peta refresh.

Klik marker untuk Edit/Hapus dari popup.

Troubleshooting

Peta putih / tidak muncul layer

Cek Console (F12) error JS.

Pastikan tidak ada variabel ganda (markerPick).

Jika tile Google tidak load, default sudah ke OSM.

Data tidak muncul

Cek api/*.php mengembalikan JSON valid.

Cek kolom latitude/longitude tidak null.

Status client tidak berubah warna

Pastikan API clients.php mengirim status (string “active”, “inactive”, dsb).

Keamanan / Best Practice

Semua query INSERT/UPDATE/DELETE sudah prepared statements.

Sanitasi tambahan disarankan untuk input teks panjang.

Untuk endpoint delete, pertimbangkan tambahkan CSRF token.

Batasi akses API (htaccess/token) jika server publik.

Roadmap (opsional)

 Simpan visibility layer (Server/ODP/Clients) ke localStorage.

 Cluster marker client saat zoom jauh.

 Filter client by status (Active/Inactive).

 Ekspor CSV daftar client yang terlihat di map.

 Panel detail ODP: list client yang terkait.

 Notifikasi kalau ada client yang status berubah (WebSocket/long polling).

Cheat-Sheet (Potongan Kode Penting)

Auto-refresh

reloadAll();
setInterval(loadClients, 120000);


Warna status

function statusColor(s){
  s = String(s||'').toLowerCase();
  if (s==='active') return '#16a34a';
  if (s==='inactive' || s==='offline' || s==='down') return '#dc2626';
  return '#9ca3af';
}


Client circle marker

const marker = L.circleMarker([c.latitude, c.longitude], {
  radius: 9, weight: 1, color: '#111827',
  fillColor: statusColor(c.status), fillOpacity: .95
}).addTo(clientLayer);
