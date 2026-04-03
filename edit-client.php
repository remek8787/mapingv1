<?php
include("config.php");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$q = mysqli_query($conn,"SELECT * FROM clients WHERE id=$id");
$client = mysqli_fetch_assoc($q);

if(!$client){
    echo "<script>alert('Client tidak ditemukan');window.location='map.php';</script>";
    exit;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $secret = $_POST['secret_mode']==="manual" ? $_POST['secret_username'] : $_POST['secret_select'];

    $q=mysqli_query($conn,"UPDATE clients SET 
        name='".mysqli_real_escape_string($conn,$_POST['name'])."',
        address='".mysqli_real_escape_string($conn,$_POST['address'])."',
        description='".mysqli_real_escape_string($conn,$_POST['description'])."',
        secret_username='".mysqli_real_escape_string($conn,$secret)."',
        odp_id='".intval($_POST['odp_id'])."',
        latitude='".mysqli_real_escape_string($conn,$_POST['latitude'])."',
        longitude='".mysqli_real_escape_string($conn,$_POST['longitude'])."'
        WHERE id=$id
    ");
    if($q){
        echo "<script>alert('Client berhasil diperbarui');window.location='map.php';</script>";
    }else{
        echo "<script>alert('Gagal update: ".mysqli_error($conn)."');</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Client</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss/dist/tailwind.min.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>
<body class="p-6 bg-gray-100">
  <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">✏️ Edit Client</h2>
    <form method="POST">
      <label class="block mb-2">Nama Client</label>
      <input type="text" name="name" class="w-full border p-2 mb-3 rounded" value="<?=htmlspecialchars($client['name'])?>" required>

      <label class="block mb-2">Alamat</label>
      <textarea name="address" class="w-full border p-2 mb-3 rounded"><?=htmlspecialchars($client['address'])?></textarea>

      <label class="block mb-2">Deskripsi (opsional)</label>
      <textarea name="description" class="w-full border p-2 mb-3 rounded"><?=htmlspecialchars($client['description'])?></textarea>

      <!-- Secret mode -->
      <label class="block mb-2">Secret Username</label>
      <select name="secret_mode" id="secret_mode" class="w-full border p-2 mb-3 rounded" onchange="toggleSecretInput()">
        <option value="manual">✍️ Input Manual</option>
        <option value="mikrotik">📡 Pilih dari Mikrotik</option>
      </select>

      <div id="secret_manual">
        <input type="text" name="secret_username" placeholder="Ketik secret..." class="w-full border p-2 mb-3 rounded" value="<?=htmlspecialchars($client['secret_username'])?>">
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
              $sel = $client['odp_id']==$r['id'] ? "selected" : "";
              echo "<option value='{$r['id']}' $sel>{$r['name']}</option>";
          }
        ?>
      </select>

      <label class="block mb-2">Koordinat (klik di peta)</label>
      <input type="text" id="lat" name="latitude" class="w-full border p-2 mb-2 rounded" value="<?=htmlspecialchars($client['latitude'])?>" readonly required>
      <input type="text" id="lng" name="longitude" class="w-full border p-2 mb-3 rounded" value="<?=htmlspecialchars($client['longitude'])?>" readonly required>

      <div id="map" class="h-64 mb-3 rounded border"></div>

      <button class="bg-green-600 text-white px-4 py-2 rounded">Update Client</button>
    </form>
  </div>

  <script>
    function toggleSecretInput(){
      let mode = document.getElementById("secret_mode").value;
      document.getElementById("secret_manual").classList.toggle("hidden", mode!=="manual");
      document.getElementById("secret_mikrotik").classList.toggle("hidden", mode!=="mikrotik");
    }

    // set secret mode default = manual
    document.getElementById("secret_mode").value = "manual";
    toggleSecretInput();

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
          if("<?= $client['secret_username']?>".trim()===s.secret_username.trim()){
            opt.selected=true;
            document.getElementById("secret_mode").value="mikrotik";
            toggleSecretInput();
          }
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

    var marker = L.marker([<?= $client['latitude']?:DEFAULT_CENTER_LAT ?>, <?= $client['longitude']?:DEFAULT_CENTER_LNG ?>]).addTo(map);
    document.getElementById("lat").value = marker.getLatLng().lat;
    document.getElementById("lng").value = marker.getLatLng().lng;

    map.on("click", function(e){
      if(marker) map.removeLayer(marker);
      marker = L.marker(e.latlng).addTo(map);
      document.getElementById("lat").value = e.latlng.lat;
      document.getElementById("lng").value = e.latlng.lng;
    });
  </script>
</body>
</html>
