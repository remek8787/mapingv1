<?php
require_once __DIR__ . '/auth.php';
require_login();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>PPP Secrets</title>

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Inter font (optional, biar lebih modern) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Flowbite (komponen siap pakai untuk button, input group, dll) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" />
  <style>
    html,body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif}
    th.sticky{position:sticky;top:0;background:#fff;z-index:10}
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <!-- Topbar -->
  <header class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-3 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-blue-600 text-white font-semibold">M</span>
        <h1 class="text-lg md:text-xl font-semibold">PPP Secrets</h1>
      </div>
      <div class="flex items-center gap-2">
        <a href="activeconnection.php" class="hidden sm:inline px-3 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-sm">Active Connections</a>
        <a href="logout.php" class="px-3 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm">Logout</a>
      </div>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 md:px-6 py-5">
    <!-- Card -->
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100">
      <!-- Toolbar -->
      <div class="p-4 md:p-5 border-b flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
        <div class="w-full sm:w-96">
          <label for="search" class="sr-only">Cari user…</label>
          <div class="relative">
            <input id="search" type="text" placeholder="Cari user / caller-id…" class="block w-full rounded-xl border-gray-300 pl-10 pr-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
            <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M10 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/></svg>
          </div>
        </div>
        <div class="flex gap-2">
          <button id="refreshBtn" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white text-sm">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5 15a7 7 0 0 0 12 2M19 9a7 7 0 0 0-12-2"/></svg>
            Refresh
          </button>
          <a href="activeconnection.php" class="sm:hidden inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-gray-200 hover:bg-gray-300 text-sm">Active</a>
        </div>
      </div>

      <!-- Table wrapper -->
      <div class="relative">
        <!-- Loading overlay -->
        <div id="loading" class="hidden absolute inset-0 z-20 flex items-center justify-center bg-white/70">
          <div class="animate-spin h-10 w-10 rounded-full border-4 border-blue-600 border-t-transparent"></div>
        </div>

        <div id="table-data" class="overflow-x-auto">
          <!-- diisi via fetch -->
        </div>
      </div>
    </div>
  </main>

  <!-- Flowbite JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>

  <script>
    let currentPage = 1;
    const $loading = document.getElementById('loading');
    const $search  = document.getElementById('search');
    const $table   = document.getElementById('table-data');

    function showLoading(state){ $loading.classList.toggle('hidden', !state); }

    function loadData(page=1){
      currentPage = page;
      const q = encodeURIComponent($search.value);
      showLoading(true);
      fetch(`data.php?type=secrets&page=${page}&q=${q}`)
        .then(r => r.text())
        .then(html => { $table.innerHTML = html; })
        .finally(() => showLoading(false));
    }

    document.getElementById('refreshBtn').addEventListener('click', () => loadData(currentPage));
    $search.addEventListener('input', () => loadData(1));

    // first load
    loadData();
  </script>
</body>
</html>
