<?php
// get_biometric_profile.php — дані для терміналу біометричної верифікації (KIRA + бункер), тільки OLYMPOS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$code = trim($_GET['code'] ?? '');
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

foreach ($users as $u) {
    if (($u['access_code'] ?? '') !== $code) continue;
    if (($u['faction'] ?? '') !== 'OLYMPOS') {
        echo json_encode(['ok' => false, 'error' => 'Only OLYMPOS']);
        exit;
    }
    $kiraType = isset($u['kira_result']['type']) ? trim((string)$u['kira_result']['type']) : '';
    $bunkerType = isset($u['bunker_test']['result_type']) ? trim((string)$u['bunker_test']['result_type']) : '';
    $level = isset($u['level']) ? (int)$u['level'] : 1;
    $passed = !empty($u['biometric_scan_passed']);
    echo json_encode([
        'ok' => true,
        'kira_type' => $kiraType,
        'bunker_result_type' => $bunkerType,
        'level' => $level,
        'biometric_scan_passed' => $passed
    ]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'User not found']);
