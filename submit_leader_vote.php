<?php
// submit_leader_vote.php — акт 1, Олімп: подати голос за лідера; при 2/3 — обраний лідер, у всіх Олімпа +1 рівень
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
$voterCode = trim((string)($input['access_code'] ?? $_POST['access_code'] ?? ''));
$candidateCode = trim((string)($input['candidate_code'] ?? $_POST['candidate_code'] ?? ''));

if ($voterCode === '' || $candidateCode === '') {
    echo json_encode(['ok' => false, 'error' => 'Missing access_code or candidate_code']);
    exit;
}

$usersFile = __DIR__ . '/users.json';
$votesFile = __DIR__ . '/act1_leader_votes.json';

if (!file_exists($usersFile)) {
    echo json_encode(['ok' => false, 'error' => 'Data not found']);
    exit;
}
$users = json_decode(file_get_contents($usersFile), true);
if (!is_array($users)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid data']);
    exit;
}

$olympos = array_values(array_filter($users, function ($u) {
    return ($u['faction'] ?? '') === 'OLYMPOS' && ($u['chapter'] ?? '') === 'ch2'
        && ($u['role'] ?? '') !== 'GAMEMASTER' && ($u['chapter'] ?? '') !== 'admin';
}));
$totalOlympos = count($olympos);
$threshold = (int) ceil($totalOlympos * 2 / 3);
if ($threshold < 1) {
    $threshold = 1;
}
// Мінімум гравців, які мають проголосувати, щоб лідер міг бути обраний (напр. 50% OLYMPOS)
$minVotersRatio = 0.5;
$minVoters = max(1, (int) ceil($totalOlympos * $minVotersRatio));

$voterValid = false;
$candidateInOlympos = false;
foreach ($olympos as $u) {
    if (($u['access_code'] ?? '') === $voterCode) $voterValid = true;
    if (($u['access_code'] ?? '') === $candidateCode) $candidateInOlympos = true;
}
if (!$voterValid) {
    echo json_encode(['ok' => false, 'error' => 'Only OLYMPOS can vote']);
    exit;
}
if (!$candidateInOlympos) {
    echo json_encode(['ok' => false, 'error' => 'Candidate must be OLYMPOS']);
    exit;
}

$votes = [];
$vote_changes = [];
$leader = null;
$level_bonus_applied = false;
if (file_exists($votesFile)) {
    $data = json_decode(file_get_contents($votesFile), true);
    if (is_array($data)) {
        $votes = $data['votes'] ?? [];
        $vote_changes = $data['vote_changes'] ?? [];
        $leader = $data['leader'] ?? null;
        $level_bonus_applied = !empty($data['level_bonus_applied']);
    }
}

if ($leader !== null) {
    echo json_encode(['ok' => false, 'error' => 'Leader already chosen', 'leader' => $leader]);
    exit;
}

$currentVote = $votes[$voterCode] ?? null;
if ($currentVote !== null && $currentVote !== $candidateCode) {
    $changes = (int) ($vote_changes[$voterCode] ?? 0);
    if ($changes >= 2) {
        echo json_encode(['ok' => false, 'error' => 'Змінити голос можна лише двічі. Ви вже вичерпали ліміт.']);
        exit;
    }
    $vote_changes[$voterCode] = $changes + 1;
}

$votes[$voterCode] = $candidateCode;

$counts = [];
foreach ($votes as $c) {
    $counts[$c] = ($counts[$c] ?? 0) + 1;
}
$newLeader = null;
$votersCount = count($votes);
foreach ($counts as $code => $num) {
    if ($num >= $threshold && $votersCount >= $minVoters) {
        $newLeader = $code;
        break;
    }
}

if ($newLeader !== null) {
    $leaderName = '';
    foreach ($users as &$u) {
        if (($u['access_code'] ?? '') === $newLeader) {
            $leaderName = $u['name'] ?? $newLeader;
            break;
        }
    }
    // Позначити лідера Олімпа в users (бейдж у профілі)
    foreach ($users as $i => $u) {
        if (($u['faction'] ?? '') === 'OLYMPOS' && ($u['chapter'] ?? '') === 'ch2') {
            $users[$i]['act1_leader'] = (($u['access_code'] ?? '') === $newLeader);
        }
    }
    // Підвищення рівня у всіх OLYMPOS тільки один раз
    if (!$level_bonus_applied) {
        foreach ($users as $i => $u) {
            if (($u['faction'] ?? '') === 'OLYMPOS' && ($u['chapter'] ?? '') === 'ch2') {
                $l = (int) ($u['level'] ?? 1);
                $users[$i]['level'] = (string) min(5, $l + 1);
            }
        }
    }
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$data = ['votes' => $votes, 'vote_changes' => $vote_changes, 'leader' => $newLeader, 'level_bonus_applied' => ($newLeader !== null) ? true : $level_bonus_applied];
file_put_contents($votesFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$leaderName = '';
if ($newLeader !== null) {
    foreach ($users as $u) {
        if (($u['access_code'] ?? '') === $newLeader) {
            $leaderName = $u['name'] ?? $newLeader;
            break;
        }
    }
}

echo json_encode([
    'ok' => true,
    'leader' => $newLeader,
    'leader_name' => $leaderName,
    'message' => $newLeader !== null ? 'Лідер обрано. У всіх Олімпа +1 рівень доступу.' : 'Голос прийнято.',
]);
