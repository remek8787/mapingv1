<?php
if (!isset($_GET['q'])) {
    echo json_encode([]);
    exit;
}

$q = urlencode($_GET['q']);

// Batasi ke Indonesia (countrycodes=id), bisa tambah filter viewbox kalau mau spesifik Jawa Timur
$url = "https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&countrycodes=id&q=" . $q;

$opts = [
    "http" => [
        "header" => "User-Agent: network-mapping/1.0\r\n"
    ]
];
$context = stream_context_create($opts);
$result = file_get_contents($url, false, $context);
echo $result;
?>
