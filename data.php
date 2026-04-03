<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/routeros_api.class.php';

header('Content-Type: text/html; charset=utf-8');

function safe($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// ---- Query params ----
$type    = $_GET['type'] ?? 'active';                        // secrets | active
$page    = max(1, (int)($_GET['page'] ?? 1));
$q       = trim((string)($_GET['q'] ?? ''));
$perPage = max(1, (int)($_GET['perPage'] ?? PER_PAGE));

// ---- Ambil data dari MikroTik ----
$API = new RouterosAPI();
$API->port = MT_PORT;
// $API->ssl  = true; // aktifkan bila pakai api-ssl (8729)
$data = [];
$err  = '';

if ($API->connect(MT_HOST, MT_USER, MT_PASS)) {
    if ($type === 'secrets') {
        $data = $API->comm('/ppp/secret/print');
    } else if ($type === 'active') {
        $data = $API->comm('/ppp/active/print');
    } else {
        $err = 'Parameter type tidak valid.';
    }
    $API->disconnect();
} else {
    $err = 'Gagal konek ke MikroTik. Cek MT_HOST/USER/PASS/PORT & /ip service api.';
}

// ---- Filter (server-side) ----
$beforeCount = count($data);
if ($q !== '') {
    $needle = mb_strtolower($q, 'UTF-8');
    $data = array_filter($data, function($r) use ($needle) {
        $name = isset($r['name']) ? mb_strtolower($r['name'], 'UTF-8') : '';
        $cid  = isset($r['caller-id']) ? mb_strtolower($r['caller-id'], 'UTF-8') : '';
        return (strpos($name, $needle) !== false) || (strpos($cid, $needle) !== false);
    });
}
$total = count($data);

// ---- Pagination ----
$pages = max(1, (int)ceil($total / $perPage));
$page  = min($page, $pages); // clamp
$offset = ($page - 1) * $perPage;
$data = array_slice(array_values($data), $offset, $perPage);

// ---- Helper kolom & judul ----
$isSecrets = ($type === 'secrets');
$cols = $isSecrets
    ? ['User','Service','Profile','Last Logout']
    : ['User','Address','Caller ID','Uptime'];
$colspan = count($cols);

// ---- Render ----
?>
<!-- info bar -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-3 pt-3">
  <?php if ($err): ?>
    <div class="w-full rounded-lg bg-red-50 text-red-700 px-3 py-2 text-sm">
      <?= safe($err) ?>
    </div>
  <?php else: ?>
    <div class="text-sm text-gray-600">
      <span class="font-medium"><?= number_format($total) ?></span> hasil
      <?php if ($q !== ''): ?>
        (filter: "<span class="italic"><?= safe($q) ?></span>")
      <?php endif; ?>
      <?php if ($beforeCount && $beforeCount !== $total): ?>
        <span class="ml-1 text-gray-400">/ total <?= number_format($beforeCount) ?></span>
      <?php endif; ?>
    </div>
    <div class="text-sm text-gray-600">Halaman <span class="font-medium"><?= $page ?></span> dari <?= $pages ?></div>
  <?php endif; ?>
</div>

<div class="mt-2 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-gray-100">
      <tr>
        <?php foreach ($cols as $c): ?>
          <th class="p-2 sticky top-0 bg-gray-100 z-10 text-left"><?= safe($c) ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php if ($err): ?>
        <tr><td colspan="<?= $colspan ?>" class="p-3 text-center text-red-600"><?= safe($err) ?></td></tr>
      <?php elseif ($data): ?>
        <?php foreach ($data as $r): ?>
          <?php if ($isSecrets): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="p-2"><?= safe($r['name'] ?? '') ?></td>
              <td class="p-2"><?= safe($r['service'] ?? '') ?></td>
              <td class="p-2"><?= safe($r['profile'] ?? '') ?></td>
              <td class="p-2"><?= safe($r['last-logged-out'] ?? '') ?></td>
            </tr>
          <?php else: ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="p-2"><?= safe($r['name'] ?? '') ?></td>
              <td class="p-2"><?= safe($r['address'] ?? '') ?></td>
              <td class="p-2"><?= safe($r['caller-id'] ?? '') ?></td>
              <td class="p-2"><?= safe($r['uptime'] ?? '') ?></td>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="<?= $colspan ?>" class="p-3 text-center text-gray-500">Tidak ada data</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if (!$err && $pages > 1): ?>
  <!-- Pagination -->
  <div class="flex flex-wrap items-center justify-center gap-1 mt-4 mb-2 px-3">
    <?php
      $btn = function($label, $targetPage, $disabled=false, $primary=false) {
        $base = 'px-3 py-1.5 rounded-lg text-sm';
        $cls = $primary
          ? 'bg-blue-600 text-white'
          : 'bg-gray-200 hover:bg-gray-300';
        if ($disabled) $cls = 'bg-gray-100 text-gray-400 cursor-not-allowed';
        echo "<button ".($disabled?'disabled ':'')."onclick=\"loadData($targetPage)\" class=\"$base $cls\">$label</button>";
      };
      // Prev
      $btn('« Prev', max(1, $page-1), $page<=1, false);
      // Numbered (window kecil biar mobile-friendly)
      $start = max(1, $page-2);
      $end   = min($pages, $page+2);
      if ($start > 1) { echo '<span class="px-1 text-gray-500">…</span>'; }
      for ($i=$start; $i<=$end; $i++) {
        $btn($i, $i, false, $i==$page);
      }
      if ($end < $pages) { echo '<span class="px-1 text-gray-500">…</span>'; }
      // Next
      $btn('Next »', min($pages, $page+1), $page>=$pages, false);
    ?>
  </div>
<?php endif; ?>
