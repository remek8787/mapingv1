<?php include("config.php"); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Tambah Server</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    #map { height: 400px; }
  </style>
</head>
<body class="p-6 bg-gray-100">
  <h1 class="text-xl font-bold mb-4">➕ Tambah Server</h1>

  <form method="POST" action="">
    <label class="block mb-2">Nama Server</label>
    <input type="text" name="name" class="border rounded w-full p-2 mb-4" required>

    <label class="block mb-2">Deskripsi</label>
    <textarea name="description" class="border rounded w-full p-2 mb-4"></textarea>

    <label class="block mb-2">Koordinat Lokasi</label>
    <input type="text" id="lat" name="latitude" class="border rounded w-full p-2 mb-2" readonly required>
    <input type="text" id="lng" name="longitude" class="border rounded w-full p-2 mb-4" readonly required>

    <div id="map" class="mb-4"></div>

    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan</button>
    <a href="map.php" class="ml-2 text-gray-600">Batal</a>
  </form>

  <script>
    var map = L.map('map').setView([<?= DEFAULT_CENTER_LAT ?>, <?= DEFAULT_CENTER_LNG ?>], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    var marker;
    map.on('click', function(e) {
      if (marker) map.removeLayer(marker);
      marker = L.marker(e.latlng).addTo(map);
      document.getElementById("lat").value = e.latlng.lat;
      document.getElementById("lng").value = e.latlng.lng;
    });
  </script>

  <?php
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $name = mysqli_real_escape_string($conn, $_POST['name']);
      $desc = mysqli_real_escape_string($conn, $_POST['description']);
      $lat = $_POST['latitude'];
      $lng = $_POST['longitude'];

      $q = mysqli_query($conn, "INSERT INTO servers (name, description, latitude, longitude) 
                                VALUES ('$name','$desc','$lat','$lng')");
      if ($q) {
          echo "<script>alert('Server berhasil ditambahkan');window.location='map.php';</script>";
      } else {
          echo "<script>alert('Gagal simpan data');</script>";
      }
  }
  ?>
</body>
</html>
