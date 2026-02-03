<?php
// get_my_analysis_requests.php — список запитів на аналіз поточного гравця (за access_code)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$code = trim((string)($_GET['code'] ?? ''));
if ($code === '') {
    echo json_encode(['ok' => false, 'requests' => [], 'error' => 'Missing code']);
    exit;
}

$requestsFile = __DIR__ . '/analysis_requests.json';
$usersFile = __DIR__ . '/users.json';

$list = [];
if (file_exists($requestsFile)) {
    $raw = @file_get_contents($requestsFile);
    $list = $raw ? (json_decode($raw, true) ?: []) : [];
}
if (!is_array($list)) $list = [];

$usersById = [];
if (file_exists($usersFile)) {
    $users = json_decode(file_get_contents($usersFile), true);
    if (is_array($users)) {
        foreach ($users as $u) {
            $c = $u['access_code'] ?? $u['id'] ?? '';
            if ($c !== '') $usersById[$c] = $u;
        }
    }
}

$mine = [];
foreach ($list as $req) {
    if (($req['requester_code'] ?? '') !== $code) continue;
    $tCode = $req['target_code'] ?? '';
    $tu = $usersById[$tCode] ?? null;
    $tName = $tu ? ($tu['name'] ?? $tCode) : $tCode;
    $mine[] = [
        'id' => $req['id'] ?? '',
        'created_at' => $req['created_at'] ?? '',
        'status' => $req['status'] ?? 'pending',
        'target_code' => $tCode,
        'target_name' => $tName,
    ];
}

// Нові запити зверху
$mine = array_reverse($mine);

echo json_encode(['ok' => true, 'requests' => $mine]);
