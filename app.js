const MAP_KEY_STORAGE = "mappingv1-google-maps-key";
const TOPO_STORAGE = "mappingv1-topology";

let map;
let infoWindow;
let markers = [];
let polylines = [];
let selectedNodeId = null;

const state = {
  nodes: [],
  links: []
};

const typeColor = {
  SERVER: "#ef4444",
  MIKROTIK: "#f59e0b",
  ODC: "#10b981",
  ODP: "#3b82f6",
  SPLITTER: "#8b5cf6",
  CLIENT: "#e11d48"
};

function $(id) {
  return document.getElementById(id);
}

function setResult(msg, ok = true) {
  const el = $("searchResult");
  el.className = `result ${ok ? "" : "muted"}`;
  el.textContent = msg;
}

function saveTopology() {
  localStorage.setItem(TOPO_STORAGE, JSON.stringify(state));
}

function loadTopology() {
  const raw = localStorage.getItem(TOPO_STORAGE);
  if (!raw) return;
  try {
    const parsed = JSON.parse(raw);
    state.nodes = Array.isArray(parsed.nodes) ? parsed.nodes : [];
    state.links = Array.isArray(parsed.links) ? parsed.links : [];
  } catch {
    state.nodes = [];
    state.links = [];
  }
}

function saveApiKey() {
  const key = $("apiKeyInput").value.trim();
  if (!key) return alert("Masukkan API key dulu.");
  localStorage.setItem(MAP_KEY_STORAGE, key);
  alert("API key tersimpan. Map akan di-load.");
  loadGoogleMaps(key);
}

function loadGoogleMaps(key) {
  if (window.google?.maps) {
    initMap();
    return;
  }

  const prev = document.getElementById("gmaps-script");
  if (prev) prev.remove();

  window.initMap = initMap;
  const script = document.createElement("script");
  script.id = "gmaps-script";
  script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}&callback=initMap`;
  script.async = true;
  script.defer = true;
  script.onerror = () => {
    alert("Gagal load Google Maps. Cek API key / billing / domain restriction.");
  };
  document.body.appendChild(script);
}

function initMap() {
  const start = { lat: -8.1858, lng: 112.4454 }; // area Malang

  map = new google.maps.Map($("map"), {
    center: start,
    zoom: 13,
    mapTypeId: "roadmap",
    streetViewControl: true,
    mapTypeControl: true,
    fullscreenControl: true
  });

  infoWindow = new google.maps.InfoWindow();

  map.addListener("click", (e) => {
    const lat = Number(e.latLng.lat().toFixed(7));
    const lng = Number(e.latLng.lng().toFixed(7));
    $("latInput").value = lat;
    $("lngInput").value = lng;
  });

  renderAll();
}

function makeId(prefix = "N") {
  return `${prefix}${Date.now().toString(36)}${Math.random().toString(36).slice(2, 6)}`;
}

function addNode() {
  const type = $("nodeType").value;
  const name = $("nodeName").value.trim();
  const parentId = $("parentNode").value || null;
  const secret = $("nodeSecret").value.trim();
  const splitterRatio = $("splitterRatio").value.trim();
  const lat = Number($("latInput").value);
  const lng = Number($("lngInput").value);

  if (!name) return alert("Nama node wajib diisi.");
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return alert("Koordinat belum valid.");

  const node = {
    id: makeId(),
    type,
    name,
    lat,
    lng,
    parentId,
    secret: type === "CLIENT" ? secret : "",
    splitterRatio: splitterRatio || ""
  };

  state.nodes.push(node);

  if (parentId) {
    state.links.push({
      id: makeId("L"),
      from: parentId,
      to: node.id
    });
  }

  saveTopology();
  clearNodeForm();
  renderAll();
}

function clearNodeForm() {
  $("nodeName").value = "";
  $("nodeSecret").value = "";
  $("splitterRatio").value = "";
}

function renderAll() {
  renderParentSelect();
  renderNodeList();
  if (map) {
    drawMapObjects();
  }
}

function renderParentSelect() {
  const current = $("parentNode").value;
  const options = ['<option value="">-- tanpa parent --</option>'];
  state.nodes.forEach((n) => {
    options.push(`<option value="${n.id}">${n.type} • ${n.name}</option>`);
  });
  $("parentNode").innerHTML = options.join("");
  $("parentNode").value = current;
}

function renderNodeList() {
  const list = $("nodeList");
  if (!state.nodes.length) {
    list.innerHTML = '<div class="muted">Belum ada node.</div>';
    return;
  }

  list.innerHTML = state.nodes
    .map((n) => {
      const active = n.id === selectedNodeId ? "active" : "";
      return `<div class="node-item ${active}" data-id="${n.id}">
        <strong>${n.type}</strong> • ${n.name}<br/>
        <span class="muted">${n.lat.toFixed(5)}, ${n.lng.toFixed(5)}</span>
      </div>`;
    })
    .join("");

  list.querySelectorAll(".node-item").forEach((el) => {
    el.addEventListener("click", () => {
      const id = el.getAttribute("data-id");
      focusNode(id);
    });
  });
}

function drawMapObjects() {
  markers.forEach((m) => m.setMap(null));
  polylines.forEach((l) => l.setMap(null));
  markers = [];
  polylines = [];

  const nodeMap = new Map(state.nodes.map((n) => [n.id, n]));
  const pathNodeIds = selectedNodeId ? traceNodeIds(selectedNodeId) : [];

  state.links.forEach((l) => {
    const from = nodeMap.get(l.from);
    const to = nodeMap.get(l.to);
    if (!from || !to) return;

    const highlighted = pathNodeIds.includes(from.id) && pathNodeIds.includes(to.id);
    const line = new google.maps.Polyline({
      path: [
        { lat: from.lat, lng: from.lng },
        { lat: to.lat, lng: to.lng }
      ],
      geodesic: true,
      strokeColor: highlighted ? "#22c55e" : "#94a3b8",
      strokeOpacity: 0.9,
      strokeWeight: highlighted ? 4 : 2,
      map
    });
    polylines.push(line);
  });

  state.nodes.forEach((n) => {
    const highlighted = pathNodeIds.includes(n.id);
    const marker = new google.maps.Marker({
      position: { lat: n.lat, lng: n.lng },
      map,
      title: `${n.type} - ${n.name}`,
      icon: {
        path: google.maps.SymbolPath.CIRCLE,
        scale: highlighted ? 9 : 7,
        fillColor: typeColor[n.type] || "#64748b",
        fillOpacity: 1,
        strokeColor: highlighted ? "#22c55e" : "#0f172a",
        strokeWeight: highlighted ? 3 : 2
      },
      label: {
        text: n.type[0],
        color: "#fff",
        fontSize: "10px",
        fontWeight: "700"
      }
    });

    marker.addListener("click", () => {
      selectedNodeId = n.id;
      const trace = traceNodeNames(n.id).join(" → ");
      infoWindow.setContent(`
        <div style="min-width:220px;line-height:1.4">
          <strong>${n.type} • ${n.name}</strong><br/>
          <small>${n.lat.toFixed(6)}, ${n.lng.toFixed(6)}</small><br/>
          ${n.secret ? `<small><b>Secret:</b> ${n.secret}</small><br/>` : ""}
          ${n.splitterRatio ? `<small><b>Splitter:</b> ${n.splitterRatio}</small><br/>` : ""}
          <small><b>Trace:</b> ${trace || "-"}</small>
        </div>
      `);
      infoWindow.open(map, marker);
      renderAll();
    });

    markers.push(marker);
  });
}

function traceNodeIds(nodeId) {
  const nodeMap = new Map(state.nodes.map((n) => [n.id, n]));
  const seen = new Set();
  const path = [];
  let current = nodeMap.get(nodeId);

  while (current && !seen.has(current.id)) {
    path.push(current.id);
    seen.add(current.id);
    current = current.parentId ? nodeMap.get(current.parentId) : null;
  }

  return path.reverse();
}

function traceNodeNames(nodeId) {
  const ids = traceNodeIds(nodeId);
  const nodeMap = new Map(state.nodes.map((n) => [n.id, n]));
  return ids.map((id) => {
    const n = nodeMap.get(id);
    return n ? `${n.name}` : id;
  });
}

function focusNode(nodeId) {
  const node = state.nodes.find((n) => n.id === nodeId);
  if (!node) return;
  selectedNodeId = node.id;
  if (map) map.panTo({ lat: node.lat, lng: node.lng });
  renderAll();
}

function searchBySecret() {
  const q = $("secretSearch").value.trim().toLowerCase();
  if (!q) return setResult("Isi secret dulu.", false);

  const found = state.nodes.find(
    (n) => n.type === "CLIENT" && n.secret && n.secret.toLowerCase().includes(q)
  );

  if (!found) {
    setResult("Secret tidak ditemukan.", false);
    return;
  }

  selectedNodeId = found.id;
  const trace = traceNodeNames(found.id).join(" → ");
  setResult(`Ditemukan: ${found.name}\nTrace: ${trace}`);

  if (map) {
    map.panTo({ lat: found.lat, lng: found.lng });
    map.setZoom(16);
  }

  renderAll();
}

function exportJson() {
  const blob = new Blob([JSON.stringify(state, null, 2)], { type: "application/json" });
  const a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  a.download = `topologi-ftth-${new Date().toISOString().slice(0, 10)}.json`;
  a.click();
  URL.revokeObjectURL(a.href);
}

function importJson(file) {
  const reader = new FileReader();
  reader.onload = (e) => {
    try {
      const parsed = JSON.parse(String(e.target.result || "{}"));
      state.nodes = Array.isArray(parsed.nodes) ? parsed.nodes : [];
      state.links = Array.isArray(parsed.links) ? parsed.links : [];
      selectedNodeId = null;
      saveTopology();
      renderAll();
      setResult("Import berhasil.");
    } catch {
      alert("File JSON tidak valid.");
    }
  };
  reader.readAsText(file);
}

function loadSample() {
  const server = { id: "n1", type: "SERVER", name: "SERVER SMKL", lat: -8.1865, lng: 112.445, parentId: null, secret: "", splitterRatio: "" };
  const mik = { id: "n2", type: "MIKROTIK", name: "Mikrotik 1", lat: -8.1862, lng: 112.4482, parentId: "n1", secret: "", splitterRatio: "" };
  const odc = { id: "n3", type: "ODC", name: "ODC 1", lat: -8.1829, lng: 112.4517, parentId: "n2", secret: "", splitterRatio: "" };
  const splitter = { id: "n4", type: "SPLITTER", name: "Splitter ODC 1", lat: -8.1814, lng: 112.453, parentId: "n3", secret: "", splitterRatio: "1:8" };
  const odp = { id: "n5", type: "ODP", name: "ODP 1", lat: -8.1795, lng: 112.4554, parentId: "n4", secret: "", splitterRatio: "" };
  const client = { id: "n6", type: "CLIENT", name: "Client A", lat: -8.1776, lng: 112.4584, parentId: "n5", secret: "client-a", splitterRatio: "" };

  state.nodes = [server, mik, odc, splitter, odp, client];
  state.links = [
    { id: "l1", from: "n1", to: "n2" },
    { id: "l2", from: "n2", to: "n3" },
    { id: "l3", from: "n3", to: "n4" },
    { id: "l4", from: "n4", to: "n5" },
    { id: "l5", from: "n5", to: "n6" }
  ];

  selectedNodeId = "n6";
  saveTopology();
  renderAll();
  setResult("Sample topologi dimuat.");

  if (map) {
    map.panTo({ lat: client.lat, lng: client.lng });
    map.setZoom(14);
  }
}

function bindEvents() {
  $("saveApiKeyBtn").addEventListener("click", saveApiKey);
  $("addNodeBtn").addEventListener("click", addNode);
  $("searchBtn").addEventListener("click", searchBySecret);
  $("exportBtn").addEventListener("click", exportJson);
  $("sampleBtn").addEventListener("click", loadSample);

  $("importInput").addEventListener("change", (e) => {
    const file = e.target.files?.[0];
    if (file) importJson(file);
    e.target.value = "";
  });
}

function bootstrap() {
  loadTopology();
  bindEvents();
  renderAll();

  const savedKey = localStorage.getItem(MAP_KEY_STORAGE) || "";
  $("apiKeyInput").value = savedKey;

  if (savedKey) {
    loadGoogleMaps(savedKey);
  } else {
    setResult("Isi Google Maps API key dulu untuk menampilkan peta.", false);
  }

  $("latInput").value = -8.1858;
  $("lngInput").value = 112.4454;
}

bootstrap();
