<?php
// save_kira.php — збереження результатів тесту K.I.R.A. на сервері, зв'язок з профілем гравця
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$code = trim($input['access_code'] ?? $_POST['access_code'] ?? '');
$kiraType = trim($input['kira_type'] ?? $_POST['kira_type'] ?? '');
$scores = $input['scores'] ?? $_POST['scores'] ?? null;
if (is_string($scores)) $scores = json_decode($scores, true);

if (!$code || !$kiraType) {
    echo json_encode(['ok' => false, 'error' => 'Missing access_code or kira_type']);
    exit;
}

$usersFile = __DIR__ . '/users.json';
if (!file_exists($usersFile)) {
    echo json_encode(['ok' => false, 'error' => 'Data file not found']);
    exit;
}

$users = json_decode(file_get_contents($usersFile), true);
if (!is_array($users)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid data']);
    exit;
}

$found = false;
foreach ($users as $i => $u) {
    if (($u['access_code'] ?? '') === $code || ($u['id'] ?? '') === $code) {
        $users[$i]['kira_result'] = [
            'type' => $kiraType,
            'scores' => is_array($scores) ? $scores : [],
            'saved_at' => time()
        ];
        $found = true;
        break;
    }
}

if (!$found) {
    echo json_encode(['ok' => false, 'error' => 'User not found']);
    exit;
}

file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode(['ok' => true, 'kira_type' => $kiraType]);
exit;
