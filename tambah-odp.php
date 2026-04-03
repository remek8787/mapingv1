<?php include("config.php"); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Tambah ODP/ODC</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>#map{height:400px;}</style>
</head>
<body class="p-6 bg-gray-100">
  <h1 class="text-xl font-bold mb-4">➕ Tambah ODP/ODC</h1>
  
  <form method="POST">
    <label>Nama ODP/ODC</label>
    <input type="text" name="name" class="w-full border p-2 mb-2 rounded" required>

    <label>Kapasitas (port)</label>
    <input type="number" name="capacity" class="w-full border p-2 mb-2 rounded" required>

    <label>Deskripsi (Opsional)</label>
    <textarea name="description" class="w-full border p-2 mb-2 rounded"></textarea>

    <label>Pilih Server</label>
    <select name="server_id" class="w-full border p-2 mb-2 rounded" required>
      <option value="">-- Pilih Server --</option>
      <?php
        $q = mysqli_query($conn,"SELECT id,name FROM servers ORDER BY name");
        while($r=mysqli_fetch_assoc($q)){
          echo "<option value='{$r['id']}'>{$r['name']}</option>";
        }
      ?>
    </select>

    <label>Koordinat</label>
    <input id="lat" name="latitude" class="w-full border p-2 mb-2 rounded" readonly required>
    <input id="lng" name="longitude" class="w-full border p-2 mb-2 rounded" readonly required>
    
    <div id="map" class="mb-4"></div>

    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Simpan</button>
    <a href="map.php" class="ml-2 text-gray-600">Batal</a>
  </form>

  <script>
    var map = L.map('map').setView([<?= DEFAULT_CENTER_LAT ?>,<?= DEFAULT_CENTER_LNG ?>],<?= DEFAULT_ZOOM ?>);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    var marker;
    map.on('click',function(e){
      if(marker) map.removeLayer(marker);
      marker=L.marker(e.latlng).addTo(map);
      document.getElementById("lat").value=e.latlng.lat;
      document.getElementById("lng").value=e.latlng.lng;
    });
  </script>

  <?php
  if($_SERVER['REQUEST_METHOD']==='POST'){
      $name = mysqli_real_escape_string($conn,$_POST['name']);
      $capacity = intval($_POST['capacity']);
      $desc = mysqli_real_escape_string($conn,$_POST['description']);
      $server_id = intval($_POST['server_id']);
      $lat = $_POST['latitude'];
      $lng = $_POST['longitude'];

      $q = mysqli_query($conn,"INSERT INTO odp (name,capacity,description,server_id,latitude,longitude) 
                               VALUES ('$name','$capacity','$desc','$server_id','$lat','$lng')");
      if($q){
          echo "<script>alert('ODP berhasil ditambahkan');window.location='map.php';</script>";
      } else {
          echo "<script>alert('Gagal simpan data');</script>";
      }
  }
  ?>
</body>
</html>
