<?php
// submit_analysis_request.php — збереження запиту гравця на проведення дослідження (аналізу) об'єкта
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$requesterCode = trim((string)($input['requester_code'] ?? $_POST['requester_code'] ?? ''));
$targetCode = trim((string)($input['target_code'] ?? $_POST['target_code'] ?? ''));

if (!$requesterCode || !$targetCode) {
    echo json_encode(['ok' => false, 'error' => 'Missing requester_code or target_code']);
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

$requester = null;
$target = null;
foreach ($users as $u) {
    if (($u['access_code'] ?? '') === $requesterCode) $requester = $u;
    if (($u['access_code'] ?? '') === $targetCode) $target = $u;
}

if (!$requester || !$target) {
    echo json_encode(['ok' => false, 'error' => 'User not found']);
    exit;
}

if (($requester['role'] ?? '') === 'GAMEMASTER' || ($requester['chapter'] ?? '') === 'admin') {
    echo json_encode(['ok' => false, 'error' => 'Not allowed']);
    exit;
}
if (($target['chapter'] ?? '') !== 'ch2' || ($requester['chapter'] ?? '') !== 'ch2') {
    echo json_encode(['ok' => false, 'error' => 'Chapter mismatch']);
    exit;
}

$myLevel = (int)($requester['level'] ?? 0);
$targetLevel = (int)($target['level'] ?? 0);
if ($targetLevel > $myLevel) {
    echo json_encode(['ok' => false, 'error' => 'Insufficient clearance level']);
    exit;
}

$requestsFile = __DIR__ . '/analysis_requests.json';
$list = [];
if (file_exists($requestsFile)) {
    $raw = file_get_contents($requestsFile);
    $list = json_decode($raw, true);
    if (!is_array($list)) $list = [];
}

$list[] = [
    'id' => 'req_' . uniqid('', true),
    'requester_code' => $requesterCode,
    'target_code' => $targetCode,
    'created_at' => date('c'),
    'status' => 'pending',
];

file_put_contents($requestsFile, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$targetSymptoms = [
    'black_capillaries_in_eyes' => !empty($target['black_capillaries_in_eyes']),
    'hot_breathing' => !empty($target['hot_breathing']),
];

echo json_encode([
    'ok' => true,
    'target_symptoms' => $targetSymptoms,
]);
