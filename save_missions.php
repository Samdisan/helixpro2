<?php
// save_missions.php — збереження взятих місій гравця (макс. 2, валідація по users.json)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$code = trim($input['code'] ?? $_POST['code'] ?? '');
$missions = $input['missions'] ?? $_POST['missions'] ?? [];
if (is_string($missions)) $missions = json_decode($missions, true) ?: [];

if (!$code) {
    echo json_encode(['ok' => false, 'error' => 'Missing code']);
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
foreach ($users as $u) {
    if (($u['access_code'] ?? '') === $code) {
        $found = true;
        break;
    }
}
if (!$found) {
    echo json_encode(['ok' => false, 'error' => 'User not found']);
    exit;
}

if (!is_array($missions)) $missions = [];
$missions = array_slice(array_unique(array_map('strval', $missions)), 0, 2);

$stateFile = __DIR__ . '/missions_state.json';
$data = [];
if (file_exists($stateFile)) {
    $raw = file_get_contents($stateFile);
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = [];
}
$data[$code] = $missions;
file_put_contents($stateFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(['ok' => true, 'missions' => $missions]);
exit;
