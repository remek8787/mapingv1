<?php
include("config.php");

// simpan jika form di-submit
if($_SERVER['REQUEST_METHOD']==='POST'){
    $secret = $_POST['secret_mode']==="manual" ? $_POST['secret_username'] : $_POST['secret_select'];

    $q=mysqli_query($conn,"INSERT INTO clients 
        (name,address,description,secret_username,odp_id,latitude,longitude,created_at) 
        VALUES (
            '".mysqli_real_escape_string($conn,$_POST['name'])."',
            '".mysqli_real_escape_string($conn,$_POST['address'])."',
            '".mysqli_real_escape_string($conn,$_POST['description'])."',
            '".mysqli_real_escape_string($conn,$secret)."',
            '".intval($_POST['odp_id'])."',
            '".mysqli_real_escape_string($conn,$_POST['latitude'])."',
            '".mysqli_real_escape_string($conn,$_POST['longitude'])."',
            NOW()
        )");
    if($q){
        echo "<script>alert('Client berhasil ditambahkan');window.location='map.php';</script>";
    }else{
        echo "<script>alert('Gagal simpan: ".mysqli_error($conn)."');</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Tambah Client</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss/dist/tailwind.min.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>
<body class="p-6 bg-gray-100">
  <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">➕ Tambah Client</h2>
    <form method="POST">
      <label class="block mb-2">Nama Client</label>
      <input type="text" name="name" class="w-full border p-2 mb-3 rounded" required>

      <label class="block mb-2">Alamat</label>
      <textarea name="address" class="w-full border p-2 mb-3 rounded"></textarea>

      <label class="block mb-2">Deskripsi (opsional)</label>
      <textarea name="description" class="w-full border p-2 mb-3 rounded"></textarea>

      <!-- Secret mode -->
      <label class="block mb-2">Secret Username</label>
      <select name="secret_mode" id="secret_mode" class="w-full border p-2 mb-3 rounded" onchange="toggleSecretInput()">
        <option value="manual">✍️ Input Manual</option>
        <option value="mikrotik">📡 Pilih dari Mikrotik</option>
      </select>

      <div id="secret_manual">
        <input type="text" name="secret_username" placeholder="Ketik secret..." class="w-full border p-2 mb-3 rounded">
      </div>

      <div id="secret_mikrotik" class="hidden">
        <select name="secret_select" id="secret_select" class="w-full border p-2 mb-3 rounded">
          <option value="">Loading...</option>
        </select>
      </div>

      <label class="block mb-2">Pilih ODP</label>
      <select name="odp_id" class="w-full border p-2 mb-3 rounded">
        <?php
          $q=mysqli_query($conn,"SELECT id,name FROM odp ORDER BY name");
          while($r=mysqli_fetch_assoc($q)){
              echo "<option value='{$r['id']}'>{$r['name']}</option>";
          }
        ?>
      </select>

      <label class="block mb-2">Koordinat (klik di peta)</label>
      <input type="text" id="lat" name="latitude" class="w-full border p-2 mb-2 rounded" readonly required>
      <input type="text" id="lng" name="longitude" class="w-full border p-2 mb-3 rounded" readonly required>

      <div id="map" class="h-64 mb-3 rounded border"></div>

      <button class="bg-purple-600 text-white px-4 py-2 rounded">Simpan Client</button>
    </form>
  </div>

  <script>
    function toggleSecretInput(){
      let mode = document.getElementById("secret_mode").value;
      document.getElementById("secret_manual").classList.toggle("hidden", mode!=="manual");
      document.getElementById("secret_mikrotik").classList.toggle("hidden", mode!=="mikrotik");
    }

    // load secret dari Mikrotik (limit 5 default)
    fetch("api/secrets.php")
      .then(res=>res.json())
      .then(data=>{
        let select=document.getElementById("secret_select");
        select.innerHTML="";
        data.slice(0,5).forEach(s=>{
          let opt=document.createElement("option");
          opt.value=s.secret_username;
          opt.textContent=s.secret_username + (s.description ? " ("+s.description+")" : "");
          select.appendChild(opt);
        });
        if(data.length>5){
          let opt=document.createElement("option");
          opt.disabled=true;
          opt.textContent="...dan "+(data.length-5)+" lainnya";
          select.appendChild(opt);
        }
      });

    // peta pilih koordinat
    var map = L.map('map').setView([<?= DEFAULT_CENTER_LAT ?>, <?= DEFAULT_CENTER_LNG ?>], <?= DEFAULT_ZOOM ?>);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    var marker;
    map.on("click", function(e){
      if(marker) map.removeLayer(marker);
      marker = L.marker(e.latlng).addTo(map);
      document.getElementById("lat").value = e.latlng.lat;
      document.getElementById("lng").value = e.latlng.lng;
    });
  </script>
</body>
</html>
