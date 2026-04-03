<?php
require_once __DIR__ . '/auth.php';

if (is_logged_in()) {
  header('Location: dashboard.php');
  exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($username === ADMIN_USER && $password === ADMIN_PASS) {
    $_SESSION['logged_in'] = true;
    $_SESSION['username']  = $username;
    header('Location: dashboard.php');
    exit;
  } else {
    $error = 'Username atau password salah.';
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-sm-10 col-md-6 col-lg-4">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h1 class="h4 mb-3 text-center">Masuk</h1>
            <?php if ($error): ?>
              <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input name="username" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input name="password" type="password" class="form-control" required>
              </div>
              <button class="btn btn-primary w-100">Login</button>
            </form>
          </div>
        </div>
        <p class="text-center text-muted small mt-3">&copy; <?= date('Y') ?></p>
      </div>
    </div>
  </div>
</body>
</html>
