<?php
// get_missions.php — отримання взятих місій гравця (доступ по access_code)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$code = trim($_GET['code'] ?? '');
if (!$code) {
    echo json_encode([]);
    exit;
}

$file = __DIR__ . '/missions_state.json';
$data = [];
if (file_exists($file)) {
    $raw = file_get_contents($file);
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = [];
}

$list = isset($data[$code]) && is_array($data[$code]) ? $data[$code] : [];
echo json_encode($list);
exit;
