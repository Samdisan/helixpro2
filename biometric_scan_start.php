<?php
// biometric_scan_start.php — при вході на термінал: рівень → 1 (тільки OLYMPOS)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$code = trim((string)($input['access_code'] ?? $_POST['access_code'] ?? ''));

if (!$code) {
    echo json_encode(['ok' => false, 'error' => 'Missing access_code']);
    exit;
}

$usersFile = __DIR__ . '/users.json';
if (!file_exists($usersFile)) {
    echo json_encode(['ok' => false, 'error' => 'Data not found']);
    exit;
}
$users = json_decode(file_get_contents($usersFile), true);
if (!is_array($users)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid data']);
    exit;
}

$found = false;
foreach ($users as $i => $u) {
    if (($u['access_code'] ?? '') !== $code) continue;
    if (($u['faction'] ?? '') !== 'OLYMPOS') {
        echo json_encode(['ok' => false, 'error' => 'Only OLYMPOS']);
        exit;
    }
    $users[$i]['level'] = '1';
    $users[$i]['biometric_scan_started'] = true;
    $found = true;
    break;
}

if (!$found) {
    echo json_encode(['ok' => false, 'error' => 'User not found']);
    exit;
}

file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode(['ok' => true]);
