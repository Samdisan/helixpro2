<?php
// submit_themis_leader_confirm.php — THEMIS: підтвердження повноважень лідера Олімпа (Іларія)
// 3 спроби; невірно — рівень до 1; правильно — +1 рівень
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
$code = trim((string)($input['access_code'] ?? $_POST['access_code'] ?? ''));
$candidateCode = trim((string)($input['candidate_code'] ?? $_POST['candidate_code'] ?? ''));

if ($code === '' || $candidateCode === '') {
    echo json_encode(['ok' => false, 'error' => 'Missing access_code or candidate_code']);
    exit;
}

$usersFile = __DIR__ . '/users.json';
$votesFile = __DIR__ . '/act1_leader_votes.json';
$themisFile = __DIR__ . '/themis_leader_confirm.json';

if (!file_exists($usersFile)) {
    echo json_encode(['ok' => false, 'error' => 'Data not found']);
    exit;
}
$users = json_decode(file_get_contents($usersFile), true);
if (!is_array($users)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid data']);
    exit;
}

$themisUser = null;
$userIndex = null;
foreach ($users as $i => $u) {
    if (($u['access_code'] ?? '') === $code) {
        if (($u['faction'] ?? '') !== 'THEMIS' || ($u['chapter'] ?? '') !== 'ch2') {
            echo json_encode(['ok' => false, 'error' => 'Only THEMIS ch2 can confirm leader']);
            exit;
        }
        if (($u['role'] ?? '') === 'GAMEMASTER' || ($u['chapter'] ?? '') === 'admin') {
            echo json_encode(['ok' => false, 'error' => 'Not allowed']);
            exit;
        }
        $themisUser = $u;
        $userIndex = $i;
        break;
    }
}
if ($themisUser === null || $userIndex === null) {
    echo json_encode(['ok' => false, 'error' => 'User not found']);
    exit;
}

$leader = null;
if (file_exists($votesFile)) {
    $data = json_decode(file_get_contents($votesFile), true);
    if (is_array($data)) {
        $leader = $data['leader'] ?? null;
    }
}
if ($leader === null || $leader === '') {
    echo json_encode(['ok' => false, 'error' => 'Leader not yet chosen']);
    exit;
}

$attempts = [];
$confirmed = [];
$level_bonus_given = [];
if (file_exists($themisFile)) {
    $tData = json_decode(file_get_contents($themisFile), true);
    if (is_array($tData)) {
        $attempts = $tData['attempts'] ?? [];
        $confirmed = $tData['confirmed'] ?? [];
        $level_bonus_given = $tData['level_bonus_given'] ?? [];
    }
}

if (!empty($confirmed[$code])) {
    echo json_encode(['ok' => false, 'error' => 'Ви вже підтвердили лідера.']);
    exit;
}

$used = (int)($attempts[$code] ?? 0);
if ($used >= 3) {
    echo json_encode(['ok' => false, 'error' => 'Усього 3 спроби. Ви вже вичерпали ліміт.']);
    exit;
}

$correct = ($candidateCode === $leader);
$attempts[$code] = $used + 1;

if ($correct) {
    $confirmed[$code] = true;
    // Підвищення рівня тільки один раз для цього гравця
    if (empty($level_bonus_given[$code])) {
        $level_bonus_given[$code] = true;
        $currentLevel = (int)($users[$userIndex]['level'] ?? 1);
        $users[$userIndex]['level'] = (string) min(5, $currentLevel + 1);
    }
} else {
    $users[$userIndex]['level'] = '1';
}

file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents($themisFile, json_encode([
    'attempts' => $attempts,
    'confirmed' => $confirmed,
    'level_bonus_given' => $level_bonus_given
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$attemptsLeft = max(0, 3 - $attempts[$code]);

echo json_encode([
    'ok' => true,
    'correct' => $correct,
    'level' => $users[$userIndex]['level'],
    'attempts_left' => $attemptsLeft,
    'message' => $correct
        ? 'Правильно. Ваш рівень доступу +1.'
        : 'Невірно. Рівень доступу встановлено на 1. Залишилось спроб: ' . $attemptsLeft . '.',
]);
exit;
