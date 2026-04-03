<?php
require_once __DIR__ . '/auth.php';
require_login();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .app-card { border: 1px solid #e9ecef; border-radius: 1rem; transition: transform .15s, box-shadow .15s; }
    .app-card:hover { transform: translateY(-2px); box-shadow: 0 0.75rem 1.25rem rgba(0,0,0,.06); }
    .app-link { text-decoration: none; color: inherit; }
  </style>
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-md navbar-light bg-white border-bottom">
    <div class="container">
      <a class="navbar-brand fw-semibold" href="#">My Dashboard</a>
      <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-muted">👋 <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
        <a href="logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
      </div>
    </div>
  </nav>

  <main class="container py-4">
    <h1 class="h4 mb-3">Akses Cepat</h1>
    <div class="row g-3">
      <div class="col-12 col-md-4">
        <a class="app-link" href="secret.php">
          <div class="app-card bg-white p-4 h-100">
            <h2 class="h5 mb-1">Secret</h2>
            <p class="text-muted mb-0">Halaman rahasia (protected).</p>
          </div>
        </a>
      </div>
      <div class="col-12 col-md-4">
        <a class="app-link" href="activeconnection.php">
          <div class="app-card bg-white p-4 h-100">
            <h2 class="h5 mb-1">Active Connection</h2>
            <p class="text-muted mb-0">Status koneksi/layanan.</p>
          </div>
        </a>
      </div>
      <div class="col-12 col-md-4">
        <a class="app-link" href="map.php">
          <div class="app-card bg-white p-4 h-100">
            <h2 class="h5 mb-1">Map</h2>
            <p class="text-muted mb-0">Peta & lokasi.</p>
          </div>
        </a>
      </div>
    </div>
  </main>
</body>
</html>
