<?php
require_once __DIR__ . '/config.php';
ini_set('display_errors', 0);

// Fallback konstanta map
$CENTER_LAT   = defined('DEFAULT_CENTER_LAT') ? DEFAULT_CENTER_LAT : -6.175392;
$CENTER_LNG   = defined('DEFAULT_CENTER_LNG') ? DEFAULT_CENTER_LNG : 106.827153;
$DEFAULT_ZOOM = defined('DEFAULT_ZOOM') ? DEFAULT_ZOOM : 12;

/* =================== SAVE / UPDATE (POST) =================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  try {
    $type = $_POST['type'] ?? '';
    $id   = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;

    if ($type === 'server') {
      $name=$_POST['name']; $desc=$_POST['description']; $lat=$_POST['latitude']; $lng=$_POST['longitude'];
      if ($id) { $stmt=$conn->prepare("UPDATE servers SET name=?,description=?,latitude=?,longitude=? WHERE id=?"); $stmt->bind_param("sssdi",$name,$desc,$lat,$lng,$id); }
      else { $stmt=$conn->prepare("INSERT INTO servers (name,description,latitude,longitude) VALUES (?,?,?,?)"); $stmt->bind_param("ssss",$name,$desc,$lat,$lng); }
      $stmt->execute();
    }

    if ($type === 'odc') {
      // Tambahan kapasitas_in & kapasitas_out
      $name=$_POST['name']; 
      $cap=(int)($_POST['capacity'] ?? 0); 
      $cap_in=(int)($_POST['kapasitas_in'] ?? 0);
      $cap_out=(int)($_POST['kapasitas_out'] ?? 0);
      $desc=$_POST['description']; 
      $server_id=(int)($_POST['server_id'] ?? 0);
      $lat=$_POST['latitude']; 
      $lng=$_POST['longitude']; 
      $status=$_POST['status'] ?? 'installed';

      if ($id) {
        $stmt=$conn->prepare("UPDATE odc 
          SET name=?, capacity=?, kapasitas_in=?, kapasitas_out=?, description=?, server_id=?, latitude=?, longitude=?, status=?
          WHERE id=?");
        $stmt->bind_param("siiisiddsi",$name,$cap,$cap_in,$cap_out,$desc,$server_id,$lat,$lng,$status,$id);
      } else {
        $stmt=$conn->prepare("INSERT INTO odc (name,capacity,kapasitas_in,kapasitas_out,description,server_id,latitude,longitude,status)
                              VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("siiisidds",$name,$cap,$cap_in,$cap_out,$desc,$server_id,$lat,$lng,$status);
      }
      $stmt->execute();
    }

    if ($type === 'odp') {
      // Tambahan relasi ke ODC & ODP Induk
      $name=$_POST['name']; 
      $cap=(int)($_POST['capacity'] ?? 0); 
      $desc=$_POST['description']; 
      $server_id=(int)($_POST['server_id'] ?? 0);
      $odc_id=(int)($_POST['odc_id'] ?? 0);
      $parent_odp_id = isset($_POST['parent_odp_id']) && $_POST['parent_odp_id']!=='' ? (int)$_POST['parent_odp_id'] : null;
      $lat=$_POST['latitude']; 
      $lng=$_POST['longitude']; 
      $status=$_POST['status'] ?? 'installed';

      if ($id) {
        $stmt=$conn->prepare("UPDATE odp 
          SET name=?, capacity=?, description=?, server_id=?, odc_id=?, parent_odp_id=?, latitude=?, longitude=?, status=?
          WHERE id=?");
        $stmt->bind_param("sisiiiddsi",$name,$cap,$desc,$server_id,$odc_id,$parent_odp_id,$lat,$lng,$status,$id);
      } else {
        $stmt=$conn->prepare("INSERT INTO odp (name,capacity,description,server_id,odc_id,parent_odp_id,latitude,longitude,status)
                              VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sisiiidds",$name,$cap,$desc,$server_id,$odc_id,$parent_odp_id,$lat,$lng,$status);
      }
      $stmt->execute();
    }

    if ($type === 'client') {
      $name=$_POST['name']; $addr=$_POST['address']; $desc=$_POST['description'];
      $secret = ($_POST['secret_mode']==='mikrotik') ? $_POST['secret_select'] : $_POST['secret_username'];
      $secret = mysqli_real_escape_string($conn, $secret);
      $odp_id=(int)$_POST['odp_id']; $lat=$_POST['latitude']; $lng=$_POST['longitude'];
      if ($id) { $stmt=$conn->prepare("UPDATE clients SET name=?,address=?,description=?,secret_username=?,odp_id=?,latitude=?,longitude=? WHERE id=?"); $stmt->bind_param("ssssissd",$name,$addr,$desc,$secret,$odp_id,$lat,$lng,$id); }
      else { $stmt=$conn->prepare("INSERT INTO clients (name,address,description,secret_username,odp_id,latitude,longitude) VALUES (?,?,?,?,?,?,?)"); $stmt->bind_param("ssssiss",$name,$addr,$desc,$secret,$odp_id,$lat,$lng); }
      $stmt->execute();
    }

    echo "<script>alert('Data berhasil disimpan');location.href='map.php';</script>"; exit;
  } catch (Throwable $e) {
    echo "<script>alert('Gagal simpan data: ".htmlspecialchars($e->getMessage())."');</script>";
  }
}

/* =================== DELETE (GET) =================== */
if (isset($_GET['delete'], $_GET['id'])) {
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  try {
    $type=$_GET['delete']; $id=(int)$_GET['id'];
    if ($type==='server'){ $stmt=$conn->prepare("DELETE FROM servers WHERE id=?"); $stmt->bind_param("i",$id); $stmt->execute(); }
    if ($type==='odc')   { $stmt=$conn->prepare("DELETE FROM odc WHERE id=?");     $stmt->bind_param("i",$id); $stmt->execute(); }
    if ($type==='odp')   { $stmt=$conn->prepare("DELETE FROM odp WHERE id=?");     $stmt->bind_param("i",$id); $stmt->execute(); }
    if ($type==='client'){ $stmt=$conn->prepare("DELETE FROM clients WHERE id=?");  $stmt->bind_param("i",$id); $stmt->execute(); }
    echo "<script>alert('Data berhasil dihapus');location.href='map.php';</script>"; exit;
  } catch (Throwable $e) {
    echo "<script>alert('Gagal hapus: ".htmlspecialchars($e->getMessage())."');</script>";
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Network Mapping</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    html,body{height:100%}
    #map { height: 100%; }
    #searchBar{position:absolute;top:12px;left:50%;transform:translateX(-50%);z-index:1000;background:#fff;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.3);width:min(520px,90vw)}
    #searchInput{width:100%;padding:10px;border:none;outline:none;border-radius:8px}
    #suggestions{background:#fff;border-top:1px solid #ddd;max-height:250px;overflow-y:auto;border-radius:0 0 8px 8px}
    #suggestions div{padding:10px;cursor:pointer}
    #suggestions div:hover{background:#f1f1f1}
    .btn{display:inline-flex;align-items:center;gap:.5rem;border:1px solid #e5e7eb;background:#fff;border-radius:.5rem;padding:.5rem .75rem;box-shadow:0 1px 2px rgba(0,0,0,.06)}
    .btn.active{background:#eef2ff;border-color:#6366f1}
  </style>
</head>
<body class="h-screen flex flex-col">

  <div class="md:hidden flex items-center justify-between px-3 py-2 border-b bg-white">
    <button id="toggleSidebar" class="px-3 py-2 rounded bg-gray-100 border">☰ Panel</button>
    <div class="font-semibold">Network Mapping</div>
  </div>

  <div class="flex-1 grid grid-cols-1 md:grid-cols-[380px_1fr] h-0 md:h-full">
    <!-- Sidebar -->
    <aside id="sidebar" class="bg-white shadow-xl flex flex-col md:static fixed inset-y-0 left-0 z-40 w-[85vw] max-w-sm -translate-x-full md:translate-x-0 transition-transform">
      <div class="p-4 border-b hidden md:block">
        <h1 class="text-lg font-bold text-gray-700">🌐 Network Mapping</h1>
      </div>

      <div class="p-4 flex-1 overflow-y-auto space-y-4">
        <!-- Search Data -->
        <div>
          <label class="block text-sm font-medium text-gray-600">Cari Data</label>
          <select id="searchType" class="w-full mb-2 px-3 py-2 border rounded-lg">
            <option value="server">Server</option>
            <option value="odc">ODC</option>
            <option value="odp">ODP</option>
            <option value="client">Client</option>
          </select>
          <input id="dataSearchInput" type="text" placeholder="Ketik nama..." class="w-full px-3 py-2 border rounded-lg"/>
          <div id="dataSuggestions" class="mt-1 border rounded-lg"></div>
        </div>

        <!-- Action -->
        <div>
          <label class="block text-sm font-medium text-gray-600">Tambah / Edit Data</label>
          <select id="actionSelect" class="w-full px-3 py-2 border rounded-lg">
            <option value="">-- Pilih Aksi --</option>
            <option value="server">➕ Tambah Server</option>
            <option value="odc">➕ Tambah ODC</option>
            <option value="odp">➕ Tambah ODP</option>
            <option value="client">➕ Tambah Client</option>
          </select>
          <div id="formContainer" class="mt-4"></div>
        </div>
      </div>
    </aside>

    <!-- Map -->
    <main class="relative">
      <div id="map" class="w-full h-full"></div>
      <div id="searchBar" class="hidden md:block">
        <input id="searchInput" type="text" placeholder="Cari lokasi..." />
        <div id="suggestions"></div>
      </div>

      <!-- Floating controls -->
      <div class="absolute right-3 bottom-3 z-[1000] flex flex-col gap-2">
        <button id="btnMeasure" class="btn" title="Ukur jarak antar node">
          📏 <span>Ukur Jarak</span>
        </button>
      </div>
    </main>
  </div>

  <script>
    // ---- sidebar mobile toggle ----
    const sidebar = document.getElementById('sidebar');
    const toggleSidebarBtn = document.getElementById('toggleSidebar');
    toggleSidebarBtn?.addEventListener('click', ()=> {
      const hidden = sidebar.classList.contains('-translate-x-full');
      sidebar.classList.toggle('-translate-x-full', !hidden);
    });
    document.getElementById('map').addEventListener('click', ()=>{ if (window.innerWidth < 768) sidebar.classList.add('-translate-x-full'); });

    // ---- Map defaults (from PHP) ----
    const MAP_DEFAULTS = <?= json_encode(['lat'=>$CENTER_LAT,'lng'=>$CENTER_LNG,'zoom'=>$DEFAULT_ZOOM], JSON_UNESCAPED_UNICODE); ?>;

    const MAX_ZOOM = 22;

    const LS_CENTER_KEY = 'nm_center';
    const LS_ZOOM_KEY   = 'nm_zoom';
    const LS_BASE_KEY   = 'nm_baselayer';

    function loadPersistedView() {
      try {
        const c = localStorage.getItem(LS_CENTER_KEY);
        const z = localStorage.getItem(LS_ZOOM_KEY);
        const center = c ? JSON.parse(c) : [MAP_DEFAULTS.lat, MAP_DEFAULTS.lng];
        const zoom   = z ? parseInt(z, 10) : MAP_DEFAULTS.zoom;
        return { center, zoom };
      } catch { return { center:[MAP_DEFAULTS.lat, MAP_DEFAULTS.lng], zoom: MAP_DEFAULT_ZOOM }; }
    }
    function saveCenterZoom(map) {
      try {
        const c = map.getCenter();
        localStorage.setItem(LS_CENTER_KEY, JSON.stringify([c.lat, c.lng]));
        localStorage.setItem(LS_ZOOM_KEY, String(map.getZoom()));
      } catch {}
    }

    // ---- Map init ----
    const { center, zoom } = loadPersistedView();
    const map = L.map('map', { maxZoom: MAX_ZOOM }).setView(center, zoom);

    const osm  = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: MAX_ZOOM });
    const gmap = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', { maxZoom: MAX_ZOOM });
    const gsat = L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', { maxZoom: MAX_ZOOM });
    const ghyb = L.tileLayer('https://mt1.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', { maxZoom: MAX_ZOOM });

    const baseLayers = { "OpenStreetMap": osm, "Google Roadmap": gmap, "Google Satellite": gsat, "Google Hybrid": ghyb };
    const savedBase = localStorage.getItem(LS_BASE_KEY);
    (baseLayers[savedBase] || osm).addTo(map);

    // ==== Layers ====
    const serverLayer = L.layerGroup().addTo(map);
    const odcLayer    = L.layerGroup().addTo(map);
    const odpLayer    = L.layerGroup().addTo(map);
    const clientLayer = L.layerGroup().addTo(map);

    L.control.layers(
      baseLayers,
      { "Server": serverLayer, "ODC": odcLayer, "ODP": odpLayer, "Clients": clientLayer }
    ).addTo(map);

    map.on('moveend zoomend', () => saveCenterZoom(map));
    map.on('baselayerchange', e => { try { localStorage.setItem(LS_BASE_KEY, e.name); } catch {} });

    // ---- data state ----
    let serverData = [], odcData = [], odpData = [], clientData = [];
    let odcIndex = {}, odpIndex = {};

    // ---- utils ----
    const qs = (s, r = document) => r.querySelector(s);
    const ce = (t) => document.createElement(t);
    const debounce = (fn, ms=220) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); } };

    // --- Distance helpers ---
    function haversine(lat1,lng1,lat2,lng2){
      const R=6371, toRad=d=>d*Math.PI/180;
      const dLat=toRad(lat2-lat1), dLng=toRad(lng2-lng1);
      const a=Math.sin(dLat/2)**2+Math.cos(toRad(lat1))*Math.cos(toRad(lat2))*Math.sin(dLng/2)**2;
      return R*2*Math.asin(Math.sqrt(a)); // km
    }
    function fmtDist(km){ return km<1 ? `${Math.round(km*1000)} m` : `${km.toFixed(2)} km`; }
    function hasLL(o){ return o && isFinite(o.latitude) && isFinite(o.longitude); }
    function distKm(a,b){ return haversine(a.latitude,a.longitude,b.latitude,b.longitude); }
    function serverById(id){ return serverData.find(s => String(s.id)===String(id)); }
    function nearestServer(p){
      if (!serverData.length || !hasLL(p)) return null;
      let best=null, dmin=Infinity;
      serverData.forEach(s=>{ if(hasLL(s)){ const d=distKm(p,s); if(d<dmin){dmin=d; best=s;} }});
      return best;
    }

    // ---- Nominatim search (desktop) ----
    qs('#searchInput')?.addEventListener('keyup', debounce(async e => {
      const q = e.target.value.trim();
      const box = qs('#suggestions'); box.innerHTML = '';
      if (q.length < 2) return;
      try {
        const res = await fetch('api/search.php?q=' + encodeURIComponent(q));
        const data = await res.json();
        data.slice(0, 8).forEach(item => {
          const div = ce('div');
          div.className = 'px-2 py-1 hover:bg-gray-200 cursor-pointer';
          div.textContent = item.display_name;
          div.onclick = () => {
            const lat = parseFloat(item.lat), lon = parseFloat(item.lon);
            map.setView([lat, lon], 16);
            L.marker([lat, lon]).addTo(map).bindPopup('📍 ' + item.display_name).openPopup();
            box.innerHTML = ''; e.target.value = item.display_name;
          };
          box.appendChild(div);
        });
      } catch {}
    }));

    // ---- Warna status ----
    function statusStroke(s){
      s = String(s||'').toLowerCase();
      if (s==='active') return '#16a34a';
      if (s==='maintenance' || s==='degraded') return '#f59e0b';
      if (s==='down' || s==='offline' || s==='inactive') return '#dc2626';
      if (s==='installed') return '#3b82f6';
      if (s==='planned') return '#9ca3af';
      return '#9ca3af';
    }
    function statusColorClient(statusText){
      const s = String(statusText||'').toLowerCase();
      if (s === 'active') return '#16a34a';
      if (s === 'inactive' || s === 'offline' || s === 'down') return '#dc2626';
      return '#9ca3af';
    }

    // ---- Icon helper (ODC/ODP) ----
    function nodeIcon(label, stroke){
      return L.divIcon({
        className: 'node',
        html: `
          <div style="
            min-width:34px;height:28px;border-radius:8px;padding:0 4px;
            background:#fff;border:3px solid ${stroke};
            display:grid;place-items:center;
            box-shadow:0 0 8px rgba(0,0,0,.25);
            font-weight:800;font-size:11px;color:#111">
            ${label}
          </div>`,
        iconSize:[34,28], iconAnchor:[17,14]
      });
    }

    // ---- Load markers ----
    async function loadServers(){
      serverLayer.clearLayers();
      const r = await fetch('api/servers.php'); serverData = await r.json();
      serverData.forEach(s => {
        const m = L.marker([s.latitude, s.longitude]).addTo(serverLayer);
        m.bindTooltip(s.name, {permanent:true, direction:'top'});
        m.bindPopup(`<b>Server:</b> ${s.name}<br>${s.description||''}<br>
          <button onclick="editData('server',${s.id})">✏️ Edit</button>
          <button onclick="deleteData('server',${s.id})">🗑️ Hapus</button>`);
      });
    }

    async function loadODC(){
      odcLayer.clearLayers();
      const r = await fetch('api/odc.php'); odcData = await r.json();
      odcIndex = {}; odcData.forEach(o=>{ odcIndex[o.id]=o; });

      odcData.forEach(o => {
        const icon = nodeIcon('ODC', statusStroke(o.status));
        const m = L.marker([o.latitude,o.longitude], { icon }).addTo(odcLayer);
        const srv = o.server_id ? serverById(o.server_id) : null;
        const dODCServer = (srv && hasLL(o) && hasLL(srv)) ? distKm(o, srv) : null;

        m.bindTooltip(o.name, {permanent:true, direction:'top'});
        m.bindPopup(`
          <b>ODC:</b> ${o.name}<br>
          Status: <b>${o.status||'-'}</b><br>
          Kapasitas Total: ${o.capacity??'-'}<br>
          Kapasitas IN: ${o.kapasitas_in ?? '-'}<br>
          Kapasitas OUT: ${o.kapasitas_out ?? '-'}<br>
          Server: ${o.server_name|| (srv ? srv.name : '-') }<br>
          ${dODCServer!=null ? `<b>Jarak ke Server:</b> ${fmtDist(dODCServer)}<br>` : ''}
          ${o.description?`Deskripsi: ${o.description}<br>`:''}
          <button onclick="editData('odc',${o.id})">✏️ Edit</button>
          <button onclick="deleteData('odc',${o.id})">🗑️ Hapus</button>
        `);
      });
    }

    async function loadODP(){
      odpLayer.clearLayers();
      const r = await fetch('api/odp.php'); odpData = await r.json();
      odpIndex = {}; odpData.forEach(o => { odpIndex[o.id] = o; });

      odpData.forEach(o => {
        const icon = nodeIcon('ODP', statusStroke(o.status));
        const m = L.marker([o.latitude,o.longitude], { icon }).addTo(odpLayer);

        const odc = o.odc_id ? odcIndex[o.odc_id] : null;
        const srv = o.server_id ? serverById(o.server_id) : (odc ? serverById(odc.server_id) : null);

        const dODP_ODC = (odc && hasLL(o) && hasLL(odc)) ? distKm(o, odc) : null;
        const dODC_SRV = (odc && srv && hasLL(odc) && hasLL(srv)) ? distKm(odc, srv) : null;
        const dODP_SRV = (srv && hasLL(o) && hasLL(srv)) ? distKm(o, srv) : null;
        const totalToSrv = [dODP_ODC, dODC_SRV].every(x=>x!=null) ? dODP_ODC + dODC_SRV : (dODP_SRV ?? null);

        m.bindTooltip(o.name, {permanent:true, direction:'top'});
        m.bindPopup(`
          <b>ODP:</b> ${o.name}<br>
          Status: <b>${o.status||'-'}</b><br>
          Kapasitas: ${o.capacity??'-'}<br>
          Server: ${o.server_name || (srv ? srv.name : '-') }<br>
          ODC: ${odc ? odc.name : (o.odc_name || '-')}<br>
          ${dODP_ODC!=null ? `<b>Jarak ke ODC:</b> ${fmtDist(dODP_ODC)}<br>` : ''}
          ${dODC_SRV!=null ? `<b>Jarak ODC→Server:</b> ${fmtDist(dODC_SRV)}<br>` : ''}
          ${totalToSrv!=null ? `<b>Total ke Server:</b> ${fmtDist(totalToSrv)}<br>` : ''}
          ${o.description?`Deskripsi: ${o.description}<br>`:''}
          <button onclick="editData('odp',${o.id})">✏️ Edit</button>
          <button onclick="deleteData('odp',${o.id})">🗑️ Hapus</button>
          ${o.odc_id?`<br><button onclick="(function(){const d=odcIndex[${o.odc_id}]; if(d){ map.setView([d.latitude,d.longitude], 18); }})()">📍 Zoom ke ODC</button>`:''}
        `);

        // Garis saat popup open (ODP→ODC→Server)
        let line=null;
        m.on('popupopen', ()=>{
          const pts=[];
          if (hasLL(o)) pts.push([o.latitude,o.longitude]);
          if (odc && hasLL(odc)) pts.push([odc.latitude,odc.longitude]);
          if (srv && hasLL(srv)) pts.push([srv.latitude,srv.longitude]);
          if (pts.length>=2) line=L.polyline(pts,{weight:3,opacity:.6}).addTo(map);
        });
        m.on('popupclose', ()=>{ if(line){map.removeLayer(line); line=null;} });
      });
    }

    async function loadClients(){
      clientLayer.clearLayers();
      const r = await fetch('api/clients.php'); clientData = await r.json();

      clientData.forEach(c => {
        if (!c.latitude || !c.longitude) return;

        const odp = c.odp_id ? odpIndex[c.odp_id] : null;
        const odc = (odp && odp.odc_id) ? odcIndex[odp.odc_id] : null;

        // Tentukan server: 1) dari ODP/ODC, 2) jika client punya server_id, 3) ambil server terdekat
        let srv = null;
        if (odp && odp.server_id) srv = serverById(odp.server_id);
        if (!srv && odc && odc.server_id) srv = serverById(odc.server_id);
        if (!srv && c.server_id) srv = serverById(c.server_id);
        if (!srv) srv = nearestServer(c); // fallback: server terdekat

        const dCliOdp = (odp && hasLL(c) && hasLL(odp)) ? distKm(c, odp) : null;
        const dOdpOdc = (odp && odc && hasLL(odp) && hasLL(odc)) ? distKm(odp, odc) : null;
        const dOdcSrv = (odc && srv && hasLL(odc) && hasLL(srv)) ? distKm(odc, srv) : null;
        const dCliSrv = (srv && hasLL(c) && hasLL(srv)) ? distKm(c, srv) : null;

        let totalChain = null;
        if (dCliOdp!=null && dOdpOdc!=null && dOdcSrv!=null) totalChain = dCliOdp + dOdpOdc + dOdcSrv;
        else if (dCliSrv!=null) totalChain = dCliSrv;

        const odpName = odp ? odp.name : (c.odp_name || '-');
        const odpCap  = odp ? (odp.capacity||'') : (c.odp_capacity || '');
        const odpLine = odp ? `<b>ODP:</b> ${odpName}${odpCap?` (kap: ${odpCap})`:''}<br>` : '';

        const col = statusColorClient(c.status);
        const marker = L.circleMarker([c.latitude, c.longitude], {
          radius: 9,
          color: '#111827',
          weight: 1,
          fillColor: col,
          fillOpacity: 0.95
        }).addTo(clientLayer);

        marker.bindTooltip(c.name || c.secret_username, {permanent:true, direction:'top'});

        const popup = `
          <b>Client:</b> ${c.name||'-'}<br>
          <b>Alamat:</b> ${c.address||''}<br>
          ${c.description?`<b>Deskripsi:</b> ${c.description}<br>`:''}
          ${odpLine}
          ${dCliOdp!=null ? `<b>Jarak ke ODP:</b> ${fmtDist(dCliOdp)}<br>` : ''}
          ${dOdpOdc!=null ? `<b>Jarak ODP→ODC:</b> ${fmtDist(dOdpOdc)}<br>` : ''}
          ${dOdcSrv!=null ? `<b>Jarak ODC→Server:</b> ${fmtDist(dOdcSrv)}<br>` : ''}
          ${dCliSrv!=null ? `<b>Jarak Client→Server${odp? ' (langsung)' : ''}:</b> ${fmtDist(dCliSrv)}<br>` : ''}
          ${totalChain!=null ? `<b>Total Rantai ke Server:</b> ${fmtDist(totalChain)}<br>` : ''}
          <b>Server:</b> ${srv ? srv.name : '-'}<br>
          <b>Secret:</b> ${c.secret_username||'-'}<br>
          <b>Status:</b> <b>${c.status||'-'}</b><br>
          ${c.ip?`<b>IP:</b> ${c.ip}<br>`:''}
          ${c.uptime?`<b>Uptime:</b> ${c.uptime}<br>`:''}
          ${c.service?`<b>Service:</b> ${c.service}<br>`:''}
          <br>
          <button onclick="editData('client',${c.id})">✏️ Edit</button>
          <button onclick="deleteData('client',${c.id})">🗑️ Hapus</button>
          ${c.odp_id?`<br><button onclick="(function(){const o=odpIndex[${c.odp_id}]; if(o){ map.setView([o.latitude,o.longitude], 18); }})()">📍 Zoom ke ODP</button>`:''}
        `;
        marker.bindPopup(popup);

        // Garis saat popup open (Client→ODP→ODC→Server atau langsung Client→Server)
        let chainLine=null;
        marker.on('popupopen', ()=>{
          const pts=[];
          if (hasLL(c)) pts.push([c.latitude,c.longitude]);
          if (odp && hasLL(odp)) pts.push([odp.latitude,odp.longitude]);
          if (odc && hasLL(odc)) pts.push([odc.latitude,odc.longitude]);
          if (srv && hasLL(srv)) pts.push([srv.latitude,srv.longitude]);
          if (pts.length>=2) chainLine = L.polyline(pts, {weight:3,opacity:.6}).addTo(map);
        });
        marker.on('popupclose', ()=>{ if(chainLine){ map.removeLayer(chainLine); chainLine=null; }});
      });
    }

    async function reloadAll(){ await Promise.all([loadServers(), loadODC(), loadODP(), loadClients()]); }
    reloadAll();
    setInterval(loadClients, 120000);

    // ---- Sidebar quick search ----
    qs('#dataSearchInput').addEventListener('keyup', e => {
      const type = qs('#searchType').value;
      const kw = e.target.value.toLowerCase();
      const box = qs('#dataSuggestions'); box.innerHTML = '';
      let data = [];
      if (type==='server') data = serverData;
      else if (type==='odc') data = odcData;
      else if (type==='odp') data = odpData;
      else data = clientData;

      data.filter(d => (d.name || d.secret_username || '').toLowerCase().includes(kw))
          .slice(0, 8).forEach(d => {
            const div = ce('div'); div.className = 'px-2 py-1 hover:bg-gray-200 cursor-pointer';
            div.textContent = d.name || d.secret_username;
            div.onclick = () => { if (d.latitude && d.longitude) map.setView([d.latitude, d.longitude], 18); };
            box.appendChild(div);
          });
    });

    // ---- Forms ----
    let markerPick = null;

    function selectStatusODC(selected='installed'){
      const opts = ['planned','installed','active','maintenance','down'];
      return `<label class="block text-sm">Status (ODC)</label>
      <select name="status" class="w-full border p-2 rounded">
        ${opts.map(o=>`<option value="${o}" ${o===selected?'selected':''}>${o}</option>`).join('')}
      </select>`;
    }
    function selectStatusODP(selected='installed'){
      const opts = ['planned','installed','active','degraded','down'];
      return `<label class="block text-sm">Status (ODP)</label>
      <select name="status" class="w-full border p-2 rounded">
        ${opts.map(o=>`<option value="${o}" ${o===selected?'selected':''}>${o}</option>`).join('')}
      </select>`;
    }

    function createForm(type, data={}){
      const lat=data.latitude||'', lng=data.longitude||'';
      const name=data.name||'', desc=data.description||'';
      const cap=data.capacity||'', addr=data.address||'';
      const secret=data.secret_username||'';
      const id=data.id||'';
      const server_id=data.server_id||'';
      const odc_id=data.odc_id||'';
      const parent_odp_id=data.parent_odp_id||'';
      const status=data.status||'installed';
      const kap_in=data.kapasitas_in||'';
      const kap_out=data.kapasitas_out||'';

      if (type==='server') return `
        <form method="POST" class="space-y-2">
          <input type="hidden" name="type" value="server"/>
          <input type="hidden" name="id" value="${id}"/>
          <label class="block text-sm">Nama Server</label>
          <input name="name" value="${name}" class="w-full border p-2 rounded" required/>
          <label class="block text-sm">Deskripsi</label>
          <textarea name="description" class="w-full border p-2 rounded">${desc}</textarea>
          <label class="block text-sm">Koordinat (klik peta)</label>
          <input id="lat" name="latitude" value="${lat}" class="w-full border p-2 rounded" readonly required/>
          <input id="lng" name="longitude" value="${lng}" class="w-full border p-2 rounded" readonly required/>
          <button class="bg-blue-600 text-white px-3 py-2 rounded w-full">Simpan Server</button>
        </form>`;

      if (type==='odc') return `
        <form method="POST" class="space-y-2">
          <input type="hidden" name="type" value="odc"/>
          <input type="hidden" name="id" value="${id}"/>
          <label class="block text-sm">Nama ODC</label>
          <input name="name" value="${name}" class="w-full border p-2 rounded" required/>

          <div class="grid grid-cols-3 gap-2">
            <div>
              <label class="block text-xs">Cap. Total</label>
              <input type="number" name="capacity" value="${cap}" class="w-full border p-2 rounded"/>
            </div>
            <div>
              <label class="block text-xs">Kabel IN</label>
              <input type="number" name="kapasitas_in" value="${kap_in}" class="w-full border p-2 rounded"/>
            </div>
            <div>
              <label class="block text-xs">Kabel OUT</label>
              <input type="number" name="kapasitas_out" value="${kap_out}" class="w-full border p-2 rounded"/>
            </div>
          </div>

          <label class="block text-sm">Deskripsi</label>
          <textarea name="description" class="w-full border p-2 rounded">${desc}</textarea>

          <label class="block text-sm">Pilih Server</label>
          <select name="server_id" id="server_select" class="w-full border p-2 rounded"></select>

          ${selectStatusODC(status)}
          <label class="block text-sm">Koordinat (klik peta)</label>
          <input id="lat" name="latitude" value="${lat}" class="w-full border p-2 rounded" readonly required/>
          <input id="lng" name="longitude" value="${lng}" class="w-full border p-2 rounded" readonly required/>
          <button class="bg-amber-600 text-white px-3 py-2 rounded w-full">Simpan ODC</button>
        </form>`;

      if (type==='odp') return `
        <form method="POST" class="space-y-2">
          <input type="hidden" name="type" value="odp"/>
          <input type="hidden" name="id" value="${id}"/>
          <label class="block text-sm">Nama ODP</label>
          <input name="name" value="${name}" class="w-full border p-2 rounded" required/>

          <label class="block text-sm">Kapasitas</label>
          <input type="number" name="capacity" value="${cap}" class="w-full border p-2 rounded" required/>

          <label class="block text-sm">Deskripsi</label>
          <textarea name="description" class="w-full border p-2 rounded">${desc}</textarea>

          <label class="block text-sm">Pilih Server (opsional)</label>
          <select name="server_id" id="server_select" class="w-full border p-2 rounded"></select>

          <label class="block text-sm">Pilih ODC</label>
          <select name="odc_id" id="odc_select" class="w-full border p-2 rounded"></select>

          <label class="block text-sm">ODP Induk (optional)</label>
          <select name="parent_odp_id" id="parent_odp_select" class="w-full border p-2 rounded"></select>

          ${selectStatusODP(status)}
          <label class="block text-sm">Koordinat (klik peta)</label>
          <input id="lat" name="latitude" value="${lat}" class="w-full border p-2 rounded" readonly required/>
          <input id="lng" name="longitude" value="${lng}" class="w-full border p-2 rounded" readonly required/>
          <button class="bg-green-600 text-white px-3 py-2 rounded w-full">Simpan ODP</button>
        </form>`;

      if (type==='client') return `
        <form method="POST" class="space-y-2">
          <input type="hidden" name="type" value="client"/>
          <input type="hidden" name="id" value="${id}"/>
          <label class="block text-sm">Nama Client</label>
          <input name="name" value="${name}" class="w-full border p-2 rounded" required/>
          <label class="block text-sm">Alamat</label>
          <textarea name="address" class="w-full border p-2 rounded">${addr}</textarea>
          <label class="block text-sm">Deskripsi</label>
          <textarea name="description" class="w-full border p-2 rounded">${desc}</textarea>

          <label class="block text-sm">Secret Username</label>
          <select name="secret_mode" id="secret_mode" class="w-full border p-2 rounded">
            <option value="manual">✍️ Input Manual</option>
            <option value="mikrotik">📡 Pilih dari Mikrotik (Find)</option>
          </select>

          <div id="secret_manual">
            <input type="text" name="secret_username" value="${secret}" class="w-full border p-2 rounded" placeholder="contoh: 1/1/3:1_ANANG@denta.net"/>
          </div>

          <div id="secret_mikrotik" class="hidden">
            <input id="secret_search" type="text" class="w-full border p-2 rounded mb-2" placeholder="Cari secret… (min. 2 huruf)"/>
            <select name="secret_select" id="secret_select" class="w-full border p-2 rounded" size="10">
              <option value="">Ketik di kolom cari untuk menampilkan hasil…</option>
            </select>
            <div class="text-xs text-gray-500 mt-1" id="secret_info"></div>
          </div>

          <label class="block text-sm">Pilih ODP</label>
          <select name="odp_id" id="odp_select" class="w-full border p-2 rounded"></select>

          <label class="block text-sm">Koordinat (klik peta)</label>
          <input id="lat" name="latitude" value="${lat}" class="w-full border p-2 rounded" readonly required/>
          <input id="lng" name="longitude" value="${lng}" class="w-full border p-2 rounded" readonly required/>

          <button class="bg-purple-600 text-white px-3 py-2 rounded w-full">Simpan Client</button>
        </form>`;
    }

    // ---- Populate selects ----
    async function populateServerSelect(selectedId=''){
      const sel = qs('#server_select'); if(!sel) return;
      sel.innerHTML = '<option value="">(Tidak ada/opsional)</option>';
      try { const r = await fetch('api/servers.php'); const rows = await r.json();
        sel.innerHTML += rows.map(s => `<option value="${s.id}" ${String(s.id)===String(selectedId)?'selected':''}>${s.name}</option>`).join('');
      } catch { sel.innerHTML = '<option value="">(gagal memuat)</option>'; }
    }
    async function populateODCSelect(selectedId=''){
      const sel = qs('#odc_select'); if(!sel) return;
      sel.innerHTML = '<option value="">Loading...</option>';
      try { const r = await fetch('api/odc.php'); const rows = await r.json();
        sel.innerHTML = rows.map(o => `<option value="${o.id}" ${String(o.id)===String(selectedId)?'selected':''}>${o.name}</option>`).join('');
      } catch { sel.innerHTML = '<option value="">(gagal memuat)</option>'; }
    }
    async function populateParentOdpSelect(selectedId=''){
      const sel = qs('#parent_odp_select'); if(!sel) return;
      sel.innerHTML = '<option value="">-- Tidak Ada --</option>';
      try { const r = await fetch('api/odp.php'); const rows = await r.json();
        sel.innerHTML += rows.map(o => `<option value="${o.id}" ${String(o.id)===String(selectedId)?'selected':''}>${o.name}</option>`).join('');
      } catch { sel.innerHTML = '<option value="">(gagal memuat)</option>'; }
    }
    async function populateOdpSelect(selectedId=''){
      const sel = qs('#odp_select'); if(!sel) return;
      sel.innerHTML = '<option value="">Loading...</option>';
      try { const r = await fetch('api/odp.php'); const rows = await r.json();
        sel.innerHTML = rows.map(o => `<option value="${o.id}" ${String(o.id)===String(selectedId)?'selected':''}>${o.name}</option>`).join('');
      } catch { sel.innerHTML = '<option value="">(gagal memuat)</option>'; }
    }

    // ---- Secret Mikrotik: searchable ----
    let secretsAll = [];
    function renderSecretOptions(list, limit=100){
      const sel = qs('#secret_select'); const info = qs('#secret_info');
      if (!sel) return;
      const shown = list.slice(0, limit);
      sel.innerHTML = shown.map(s => {
        const label = s.secret_username + (s.description ? ` (${s.description})` : '');
        return `<option value="${s.secret_username}">${label}</option>`;
      }).join('') || '<option value="">(tidak ada hasil)</option>';
      if (info) info.textContent = list.length > limit ? `Menampilkan ${shown.length} dari ${list.length} hasil. Saring lagi untuk mempersempit.` :
                            (list.length ? `${list.length} hasil` : '');
    }
    async function ensureSecrets(){
      if (secretsAll.length) return;
      try {
        const r = await fetch('api/secrets.php?limit=5000');
        const rows = await r.json();
        secretsAll = Array.isArray(rows) ? rows : [];
      } catch { secretsAll = []; }
    }
    async function populateSecretsFiltered(query){
      await ensureSecrets();
      const q = (query||'').toLowerCase().trim();
      if (q.length < 2) { renderSecretOptions([]); return; }
      const tokens = q.split(/\s+/).filter(Boolean);
      const filtered = secretsAll.filter(s => {
        const hay = ((s.secret_username||'')+' '+(s.description||'')).toLowerCase();
        return tokens.every(t => hay.includes(t));
      });
      renderSecretOptions(filtered, 100);
    }
    async function populateSecretsUI(){
      const search = qs('#secret_search');
      const sel = qs('#secret_select');
      if (!search || !sel) return;
      search.placeholder = 'Cari secret… (min. 2 huruf)';
      search.addEventListener('input', debounce(()=> populateSecretsFiltered(search.value), 180));
      search.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
          const first = sel.querySelector('option'); if (first) { first.selected = true; e.preventDefault(); }
        }
      });
    }

    // ---- Action select (create new) ----
    qs('#actionSelect').addEventListener('change', async e => {
      const val = e.target.value; const box = qs('#formContainer'); box.innerHTML = ''; if (!val) return;
      box.innerHTML = createForm(val, {});
      map.once('click', function onPick(ev){
        if (markerPick) map.removeLayer(markerPick);
        markerPick = L.marker(ev.latlng).addTo(map);
        qs('#lat').value = ev.latlng.lat; qs('#lng').value = ev.latlng.lng;
        map.on('click', onPick);
      });
      if (val==='odc' || val==='odp') await populateServerSelect();
      if (val==='odp') { await populateODCSelect(); await populateParentOdpSelect(); }
      if (val==='client') { await populateOdpSelect(); }
      toggleSecretInit();
    });

    // ---- Edit existing ----
    window.editData = async function(type, id){
      let data=null;
      if(type==='server') data = serverData.find(x=>x.id==id);
      if(type==='odc')    data = odcData.find(x=>x.id==id);
      if(type==='odp')    data = odpData.find(x=>x.id==id);
      if(type==='client') data = clientData.find(x=>x.id==id);
      if(!data) return;
      qs('#actionSelect').value = type;
      const box = qs('#formContainer'); box.innerHTML = createForm(type, data);

      map.once('click', function onPick(ev){
        if (markerPick) map.removeLayer(markerPick);
        markerPick = L.marker(ev.latlng).addTo(map);
        qs('#lat').value = ev.latlng.lat; qs('#lng').value = ev.latlng.lng;
        map.on('click', onPick);
      });

      if (type==='odc' || type==='odp') await populateServerSelect(data.server_id);
      if (type==='odp') { await populateODCSelect(data.odc_id); await populateParentOdpSelect(data.parent_odp_id); }
      if (type==='client') await populateOdpSelect(data.odp_id);
      toggleSecretInit();
    }

    window.deleteData = function(type, id){
      if (confirm('Yakin hapus data ini?')) location.href = `map.php?delete=${type}&id=${id}`;
    }

    // ---- Secret mode toggle ----
    function toggleSecretInit(){
      const mode = qs('#secret_mode');
      if (!mode) return;
      async function update(){
        const m = mode.value;
        qs('#secret_manual')?.classList.toggle('hidden', m!=='manual');
        qs('#secret_mikrotik')?.classList.toggle('hidden', m!=='mikrotik');
        if (m==='mikrotik') {
          await populateSecretsUI();
          renderSecretOptions([]);
        }
      }
      mode.addEventListener('change', update);
      update();
    }

    // =======================
    //   FITUR UKUR JARAK (manual)
    // =======================
    let measureEnabled=false, measureFrom=null, measureLine=null, measureTip=null;

    function attachMeasureHook(marker){
      marker.on('click', e=>{
        if (!measureEnabled) return;
        if (!measureFrom) { measureFrom = e.latlng; return; }
        const to = e.latlng;
        const km = haversine(measureFrom.lat, measureFrom.lng, to.lat, to.lng);
        if (measureLine) map.removeLayer(measureLine);
        if (measureTip) map.removeLayer(measureTip);
        measureLine = L.polyline([measureFrom, to], {weight:4, dashArray:'6,8'}).addTo(map);
        const mid = L.latLng((measureFrom.lat+to.lat)/2,(measureFrom.lng+to.lng)/2);
        measureTip = L.marker(mid,{opacity:0}).addTo(map)
          .bindTooltip(km<1?`${(km*1000).toFixed(0)} m`:`${km.toFixed(2)} km`,
                       {permanent:true, direction:'center'}).openTooltip();
        measureFrom = null;
      });
    }

    const btnMeasure = document.getElementById('btnMeasure');
    btnMeasure.addEventListener('click', ()=>{
      measureEnabled = !measureEnabled;
      btnMeasure.classList.toggle('active', measureEnabled);
      if (!measureEnabled) {
        measureFrom=null;
        if (measureLine) { map.removeLayer(measureLine); measureLine=null; }
        if (measureTip)  { map.removeLayer(measureTip);  measureTip=null; }
      }
    });
  </script>
</body>
</html>
