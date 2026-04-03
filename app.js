const STORAGE_KEY = "mapingv1_static_data_v1";
const THEME_KEY = "mapingv1_theme";

const defaults = {
  servers: [],
  odc: [],
  odp: [],
  clients: []
};

const state = {
  data: structuredClone(defaults),
  map: null,
  picking: false,
  layers: {
    server: L.layerGroup(),
    odc: L.layerGroup(),
    odp: L.layerGroup(),
    client: L.layerGroup(),
    links: L.layerGroup()
  }
};

const el = (id) => document.getElementById(id);

const ui = {
  nodeType: el("nodeType"),
  editId: el("editId"),
  name: el("name"),
  description: el("description"),
  capacity: el("capacity"),
  address: el("address"),
  secretUsername: el("secretUsername"),
  clientStatus: el("clientStatus"),
  serverId: el("serverId"),
  odcId: el("odcId"),
  odpId: el("odpId"),
  latitude: el("latitude"),
  longitude: el("longitude"),
  saveBtn: el("saveBtn"),
  resetBtn: el("resetBtn"),
  pickBtn: el("pickBtn"),
  themeBtn: el("themeBtn"),
  geoSearch: el("geoSearch"),
  geoBtn: el("geoBtn"),
  geoList: el("geoList"),
  exportBtn: el("exportBtn"),
  importFile: el("importFile"),
  demoBtn: el("demoBtn"),
  wipeBtn: el("wipeBtn"),
  status: el("status"),
  mServer: el("mServer"),
  mOdc: el("mOdc"),
  mOdp: el("mOdp"),
  mClient: el("mClient")
};

function notify(msg) {
  ui.status.textContent = msg;
}

function uid(prefix) {
  return `${prefix}_${Date.now()}_${Math.random().toString(16).slice(2, 7)}`;
}

function saveData() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(state.data));
}

function loadData() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return;
    const parsed = JSON.parse(raw);
    state.data = {
      servers: Array.isArray(parsed.servers) ? parsed.servers : [],
      odc: Array.isArray(parsed.odc) ? parsed.odc : [],
      odp: Array.isArray(parsed.odp) ? parsed.odp : [],
      clients: Array.isArray(parsed.clients) ? parsed.clients : []
    };
  } catch {
    state.data = structuredClone(defaults);
  }
}

function applyTheme(theme) {
  const isLight = theme === "light";
  document.body.classList.toggle("light", isLight);
  ui.themeBtn.textContent = isLight ? "☀️ Light" : "🌙 Dark";
}

function initTheme() {
  const saved = localStorage.getItem(THEME_KEY) || "dark";
  applyTheme(saved);
  ui.themeBtn.addEventListener("click", () => {
    const next = document.body.classList.contains("light") ? "dark" : "light";
    localStorage.setItem(THEME_KEY, next);
    applyTheme(next);
  });
}

function parseNum(v) {
  const n = Number(v);
  return Number.isFinite(n) ? n : null;
}

function haversine(lat1, lon1, lat2, lon2) {
  const toRad = (d) => (d * Math.PI) / 180;
  const R = 6371;
  const dLat = toRad(lat2 - lat1);
  const dLon = toRad(lon2 - lon1);
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
  return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
}

function distanceText(km) {
  return km < 1 ? `${Math.round(km * 1000)} m` : `${km.toFixed(2)} km`;
}

function initMap() {
  state.map = L.map("map", { zoomControl: true }).setView([-6.2, 106.8], 12);

  const osm = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { maxZoom: 20 });
  const sat = L.tileLayer("https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}", { maxZoom: 20 });
  const hyb = L.tileLayer("https://mt1.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}", { maxZoom: 20 });

  osm.addTo(state.map);

  Object.values(state.layers).forEach((layer) => layer.addTo(state.map));

  L.control
    .layers(
      { OSM: osm, Satellite: sat, Hybrid: hyb },
      {
        Server: state.layers.server,
        ODC: state.layers.odc,
        ODP: state.layers.odp,
        Client: state.layers.client,
        Links: state.layers.links
      }
    )
    .addTo(state.map);

  state.map.on("click", (ev) => {
    if (!state.picking) return;
    ui.latitude.value = ev.latlng.lat.toFixed(6);
    ui.longitude.value = ev.latlng.lng.toFixed(6);
    state.picking = false;
    ui.pickBtn.classList.remove("btn-info");
    ui.pickBtn.classList.add("btn-outline-info");
    notify("Koordinat dipilih dari map.");
  });
}

function markerIcon(label, color) {
  return L.divIcon({
    className: "",
    html: `<div class="marker-badge" style="background:${color}">${label}</div>`,
    iconSize: [36, 28],
    iconAnchor: [18, 14]
  });
}

function getServerById(id) {
  return state.data.servers.find((x) => String(x.id) === String(id));
}

function getOdcById(id) {
  return state.data.odc.find((x) => String(x.id) === String(id));
}

function getOdpById(id) {
  return state.data.odp.find((x) => String(x.id) === String(id));
}

function safeLL(item) {
  return Number.isFinite(item?.latitude) && Number.isFinite(item?.longitude);
}

function clearLayers() {
  Object.values(state.layers).forEach((layer) => layer.clearLayers());
}

function drawLinks() {
  state.data.odc.forEach((odc) => {
    if (!safeLL(odc) || !odc.serverId) return;
    const s = getServerById(odc.serverId);
    if (!safeLL(s)) return;
    L.polyline(
      [
        [odc.latitude, odc.longitude],
        [s.latitude, s.longitude]
      ],
      { color: "#f59e0b", weight: 2, opacity: 0.75 }
    ).addTo(state.layers.links);
  });

  state.data.odp.forEach((odp) => {
    if (!safeLL(odp)) return;
    if (odp.odcId) {
      const odc = getOdcById(odp.odcId);
      if (safeLL(odc)) {
        L.polyline(
          [
            [odp.latitude, odp.longitude],
            [odc.latitude, odc.longitude]
          ],
          { color: "#34d399", weight: 2, opacity: 0.75 }
        ).addTo(state.layers.links);
      }
    }
  });

  state.data.clients.forEach((c) => {
    if (!safeLL(c) || !c.odpId) return;
    const odp = getOdpById(c.odpId);
    if (!safeLL(odp)) return;
    L.polyline(
      [
        [c.latitude, c.longitude],
        [odp.latitude, odp.longitude]
      ],
      { color: "#7c3aed", weight: 2, opacity: 0.75, dashArray: "5,6" }
    ).addTo(state.layers.links);
  });
}

function renderMapData() {
  clearLayers();

  state.data.servers.forEach((s) => {
    if (!safeLL(s)) return;
    const m = L.marker([s.latitude, s.longitude], { icon: markerIcon("SRV", "#3b82f6") }).addTo(state.layers.server);
    m.bindTooltip(s.name, { permanent: true, direction: "top" });
    m.bindPopup(`
      <b>Server:</b> ${escapeHtml(s.name)}<br>
      ${escapeHtml(s.description || "-")}<br><br>
      <button onclick="window.startEdit('server','${s.id}')">✏️ Edit</button>
      <button onclick="window.removeNode('server','${s.id}')">🗑️ Hapus</button>
    `);
  });

  state.data.odc.forEach((o) => {
    if (!safeLL(o)) return;
    const m = L.marker([o.latitude, o.longitude], { icon: markerIcon("ODC", "#f59e0b") }).addTo(state.layers.odc);
    const s = getServerById(o.serverId);
    const dist = s && safeLL(s) ? distanceText(haversine(o.latitude, o.longitude, s.latitude, s.longitude)) : "-";
    m.bindTooltip(o.name, { permanent: true, direction: "top" });
    m.bindPopup(`
      <b>ODC:</b> ${escapeHtml(o.name)}<br>
      Kapasitas: ${escapeHtml(String(o.capacity ?? "-"))}<br>
      Server: ${escapeHtml(s?.name || "-")}<br>
      Jarak ke Server: ${dist}<br>
      ${escapeHtml(o.description || "-")}<br><br>
      <button onclick="window.startEdit('odc','${o.id}')">✏️ Edit</button>
      <button onclick="window.removeNode('odc','${o.id}')">🗑️ Hapus</button>
    `);
  });

  state.data.odp.forEach((o) => {
    if (!safeLL(o)) return;
    const m = L.marker([o.latitude, o.longitude], { icon: markerIcon("ODP", "#16a34a") }).addTo(state.layers.odp);
    const odc = getOdcById(o.odcId);
    const server = getServerById(o.serverId) || getServerById(odc?.serverId);
    const d1 = odc && safeLL(odc) ? haversine(o.latitude, o.longitude, odc.latitude, odc.longitude) : null;
    const d2 = odc && server && safeLL(odc) && safeLL(server)
      ? haversine(odc.latitude, odc.longitude, server.latitude, server.longitude)
      : null;

    m.bindTooltip(o.name, { permanent: true, direction: "top" });
    m.bindPopup(`
      <b>ODP:</b> ${escapeHtml(o.name)}<br>
      Kapasitas: ${escapeHtml(String(o.capacity ?? "-"))}<br>
      ODC: ${escapeHtml(odc?.name || "-")}<br>
      Server: ${escapeHtml(server?.name || "-")}<br>
      ${d1 != null ? `Jarak ODP→ODC: ${distanceText(d1)}<br>` : ""}
      ${d2 != null ? `Jarak ODC→Server: ${distanceText(d2)}<br>` : ""}
      ${escapeHtml(o.description || "-")}<br><br>
      <button onclick="window.startEdit('odp','${o.id}')">✏️ Edit</button>
      <button onclick="window.removeNode('odp','${o.id}')">🗑️ Hapus</button>
    `);
  });

  state.data.clients.forEach((c) => {
    if (!safeLL(c)) return;
    const color = c.status === "active" ? "#22c55e" : "#ef4444";
    const marker = L.circleMarker([c.latitude, c.longitude], {
      radius: 8,
      color: "#111",
      weight: 1,
      fillColor: color,
      fillOpacity: 0.95
    }).addTo(state.layers.client);

    const odp = getOdpById(c.odpId);
    const odc = getOdcById(odp?.odcId);
    const srv = getServerById(odp?.serverId) || getServerById(odc?.serverId);

    const seg1 = odp && safeLL(odp) ? haversine(c.latitude, c.longitude, odp.latitude, odp.longitude) : null;
    const seg2 = odp && odc && safeLL(odp) && safeLL(odc)
      ? haversine(odp.latitude, odp.longitude, odc.latitude, odc.longitude)
      : null;
    const seg3 = odc && srv && safeLL(odc) && safeLL(srv)
      ? haversine(odc.latitude, odc.longitude, srv.latitude, srv.longitude)
      : null;

    const total = [seg1, seg2, seg3].every((v) => v != null) ? seg1 + seg2 + seg3 : null;

    marker.bindTooltip(c.name || c.secretUsername || "Client", { permanent: true, direction: "top" });
    marker.bindPopup(`
      <b>Client:</b> ${escapeHtml(c.name || "-")}<br>
      Alamat: ${escapeHtml(c.address || "-")}<br>
      Secret: ${escapeHtml(c.secretUsername || "-")}<br>
      Status: <b>${escapeHtml(c.status || "inactive")}</b><br>
      ODP: ${escapeHtml(odp?.name || "-")}<br>
      ${seg1 != null ? `Jarak Client→ODP: ${distanceText(seg1)}<br>` : ""}
      ${seg2 != null ? `Jarak ODP→ODC: ${distanceText(seg2)}<br>` : ""}
      ${seg3 != null ? `Jarak ODC→Server: ${distanceText(seg3)}<br>` : ""}
      ${total != null ? `<b>Total ke Server: ${distanceText(total)}</b><br>` : ""}
      ${escapeHtml(c.description || "-")}<br><br>
      <button onclick="window.startEdit('client','${c.id}')">✏️ Edit</button>
      <button onclick="window.removeNode('client','${c.id}')">🗑️ Hapus</button>
    `);
  });

  drawLinks();

  ui.mServer.textContent = String(state.data.servers.length);
  ui.mOdc.textContent = String(state.data.odc.length);
  ui.mOdp.textContent = String(state.data.odp.length);
  ui.mClient.textContent = String(state.data.clients.length);
}

function repopulateSelects() {
  ui.serverId.innerHTML = `<option value="">(opsional)</option>` +
    state.data.servers.map((s) => `<option value="${s.id}">${escapeHtml(s.name)}</option>`).join("");

  ui.odcId.innerHTML = `<option value="">(opsional)</option>` +
    state.data.odc.map((o) => `<option value="${o.id}">${escapeHtml(o.name)}</option>`).join("");

  ui.odpId.innerHTML = `<option value="">(opsional)</option>` +
    state.data.odp.map((o) => `<option value="${o.id}">${escapeHtml(o.name)}</option>`).join("");
}

function currentCollection(type) {
  if (type === "server") return state.data.servers;
  if (type === "odc") return state.data.odc;
  if (type === "odp") return state.data.odp;
  return state.data.clients;
}

function clearForm() {
  ui.editId.value = "";
  ui.name.value = "";
  ui.description.value = "";
  ui.capacity.value = "";
  ui.address.value = "";
  ui.secretUsername.value = "";
  ui.latitude.value = "";
  ui.longitude.value = "";
  ui.serverId.value = "";
  ui.odcId.value = "";
  ui.odpId.value = "";
  ui.clientStatus.value = "active";
}

function updateTypeFields() {
  const type = ui.nodeType.value;
  document.querySelectorAll(".d-field").forEach((x) => (x.style.display = "none"));
  document.querySelectorAll(`.d-${type}`).forEach((x) => (x.style.display = "block"));
}

function collectForm() {
  return {
    id: ui.editId.value || uid(ui.nodeType.value),
    name: ui.name.value.trim(),
    description: ui.description.value.trim(),
    capacity: parseNum(ui.capacity.value),
    address: ui.address.value.trim(),
    secretUsername: ui.secretUsername.value.trim(),
    status: ui.clientStatus.value,
    serverId: ui.serverId.value || null,
    odcId: ui.odcId.value || null,
    odpId: ui.odpId.value || null,
    latitude: parseNum(ui.latitude.value),
    longitude: parseNum(ui.longitude.value)
  };
}

function validate(type, payload) {
  if (!payload.name) return "Nama wajib diisi.";
  if (!Number.isFinite(payload.latitude) || !Number.isFinite(payload.longitude)) {
    return "Latitude & longitude wajib valid.";
  }
  if (type === "client" && !payload.secretUsername) return "Secret Username wajib diisi untuk client.";
  return "";
}

function saveNode() {
  const type = ui.nodeType.value;
  const payload = collectForm();
  const err = validate(type, payload);
  if (err) {
    notify(err);
    return;
  }

  const list = currentCollection(type);
  const idx = list.findIndex((x) => String(x.id) === String(payload.id));
  if (idx >= 0) list[idx] = payload;
  else list.push(payload);

  saveData();
  repopulateSelects();
  renderMapData();
  clearForm();
  notify("Data berhasil disimpan (static localStorage).");
}

window.startEdit = function startEdit(type, id) {
  ui.nodeType.value = type;
  updateTypeFields();
  const list = currentCollection(type);
  const item = list.find((x) => String(x.id) === String(id));
  if (!item) return;

  ui.editId.value = item.id || "";
  ui.name.value = item.name || "";
  ui.description.value = item.description || "";
  ui.capacity.value = item.capacity ?? "";
  ui.address.value = item.address || "";
  ui.secretUsername.value = item.secretUsername || "";
  ui.clientStatus.value = item.status || "active";
  ui.serverId.value = item.serverId || "";
  ui.odcId.value = item.odcId || "";
  ui.odpId.value = item.odpId || "";
  ui.latitude.value = item.latitude ?? "";
  ui.longitude.value = item.longitude ?? "";

  notify(`Mode edit: ${type} (${item.name}).`);
};

window.removeNode = function removeNode(type, id) {
  if (!confirm("Yakin hapus data ini?")) return;
  const list = currentCollection(type);
  const next = list.filter((x) => String(x.id) !== String(id));
  if (type === "server") state.data.servers = next;
  if (type === "odc") state.data.odc = next;
  if (type === "odp") state.data.odp = next;
  if (type === "client") state.data.clients = next;

  saveData();
  repopulateSelects();
  renderMapData();
  notify("Data dihapus.");
};

function exportJson() {
  const blob = new Blob([JSON.stringify(state.data, null, 2)], { type: "application/json" });
  const a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  a.download = `mapingv1-backup-${new Date().toISOString().slice(0, 10)}.json`;
  a.click();
  URL.revokeObjectURL(a.href);
}

function importJson(file) {
  const reader = new FileReader();
  reader.onload = () => {
    try {
      const parsed = JSON.parse(reader.result);
      state.data = {
        servers: Array.isArray(parsed.servers) ? parsed.servers : [],
        odc: Array.isArray(parsed.odc) ? parsed.odc : [],
        odp: Array.isArray(parsed.odp) ? parsed.odp : [],
        clients: Array.isArray(parsed.clients) ? parsed.clients : []
      };
      saveData();
      repopulateSelects();
      renderMapData();
      notify("Import JSON berhasil.");
    } catch {
      notify("Import gagal: format JSON tidak valid.");
    }
  };
  reader.readAsText(file);
}

function seedDemo() {
  state.data = {
    servers: [
      { id: uid("server"), name: "Server Pusat", description: "Core server", latitude: -6.175392, longitude: 106.827153 }
    ],
    odc: [
      { id: uid("odc"), name: "ODC-01", description: "Distribusi barat", capacity: 144, serverId: null, latitude: -6.188, longitude: 106.818 }
    ],
    odp: [
      { id: uid("odp"), name: "ODP-01", description: "Cabang A", capacity: 16, serverId: null, odcId: null, latitude: -6.196, longitude: 106.81 }
    ],
    clients: [
      {
        id: uid("client"),
        name: "Client Demo",
        address: "Jakarta",
        description: "Data contoh",
        secretUsername: "1/1/1:1_DEMO",
        status: "active",
        odpId: null,
        latitude: -6.203,
        longitude: 106.806
      }
    ]
  };

  // set relation defaults
  const sId = state.data.servers[0].id;
  const odcId = state.data.odc[0].id;
  const odpId = state.data.odp[0].id;
  state.data.odc[0].serverId = sId;
  state.data.odp[0].serverId = sId;
  state.data.odp[0].odcId = odcId;
  state.data.clients[0].odpId = odpId;

  saveData();
  repopulateSelects();
  renderMapData();
  notify("Demo data dimuat.");
}

async function searchGeo() {
  const q = ui.geoSearch.value.trim();
  ui.geoList.innerHTML = "";
  if (q.length < 2) return;

  notify(`Mencari lokasi: ${q}...`);

  try {
    const url = `https://nominatim.openstreetmap.org/search?format=json&limit=8&q=${encodeURIComponent(q)}`;
    const res = await fetch(url, { headers: { "Accept-Language": "id,en" } });
    const data = await res.json();

    if (!Array.isArray(data) || !data.length) {
      notify("Lokasi tidak ditemukan.");
      return;
    }

    data.forEach((item) => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "list-group-item list-group-item-action";
      btn.textContent = item.display_name;
      btn.addEventListener("click", () => {
        const lat = Number(item.lat);
        const lon = Number(item.lon);
        state.map.setView([lat, lon], 17);
        L.marker([lat, lon]).addTo(state.map).bindPopup(item.display_name).openPopup();
        ui.latitude.value = lat.toFixed(6);
        ui.longitude.value = lon.toFixed(6);
        notify("Koordinat diisi dari hasil pencarian lokasi.");
      });
      ui.geoList.appendChild(btn);
    });

    notify(`Ditemukan ${data.length} lokasi.`);
  } catch {
    notify("Pencarian lokasi gagal.");
  }
}

function bindEvents() {
  ui.nodeType.addEventListener("change", updateTypeFields);
  ui.saveBtn.addEventListener("click", saveNode);
  ui.resetBtn.addEventListener("click", () => {
    clearForm();
    notify("Form direset.");
  });
  ui.pickBtn.addEventListener("click", () => {
    state.picking = !state.picking;
    ui.pickBtn.classList.toggle("btn-info", state.picking);
    ui.pickBtn.classList.toggle("btn-outline-info", !state.picking);
    notify(state.picking ? "Mode pick aktif: klik map untuk isi koordinat." : "Mode pick dimatikan.");
  });

  ui.exportBtn.addEventListener("click", exportJson);
  ui.importFile.addEventListener("change", (e) => {
    const file = e.target.files?.[0];
    if (file) importJson(file);
    e.target.value = "";
  });
  ui.demoBtn.addEventListener("click", seedDemo);
  ui.wipeBtn.addEventListener("click", () => {
    if (!confirm("Yakin kosongkan semua data lokal?")) return;
    state.data = structuredClone(defaults);
    saveData();
    repopulateSelects();
    renderMapData();
    clearForm();
    notify("Semua data lokal dikosongkan.");
  });

  ui.geoBtn.addEventListener("click", searchGeo);
  ui.geoSearch.addEventListener("keydown", (e) => {
    if (e.key === "Enter") searchGeo();
  });
}

function escapeHtml(str) {
  return String(str ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function init() {
  initTheme();
  loadData();
  initMap();
  repopulateSelects();
  updateTypeFields();
  renderMapData();
  bindEvents();
  notify("Maping V1 static siap dipakai.");
}

init();
