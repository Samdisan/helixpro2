<?php
// get_leader_votes.php — акт 1, Олімп: список гравців Олімпа, поточні голоси, обраний лідер
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$usersFile = __DIR__ . '/users.json';
$votesFile = __DIR__ . '/act1_leader_votes.json';

$users = [];
if (file_exists($usersFile)) {
    $users = json_decode(file_get_contents($usersFile), true) ?: [];
}
$olympos = array_values(array_filter($users, function ($u) {
    return ($u['faction'] ?? '') === 'OLYMPOS' && ($u['chapter'] ?? '') === 'ch2'
        && ($u['role'] ?? '') !== 'GAMEMASTER' && ($u['chapter'] ?? '') !== 'admin';
}));

$votes = [];
$vote_changes = [];
$leader = null;
if (file_exists($votesFile)) {
    $data = json_decode(file_get_contents($votesFile), true);
    if (is_array($data)) {
        $votes = $data['votes'] ?? [];
        $vote_changes = $data['vote_changes'] ?? [];
        $leader = $data['leader'] ?? null;
    }
}

$leaderName = null;
if ($leader !== null) {
    foreach ($users as $u) {
        if (($u['access_code'] ?? '') === $leader) {
            $leaderName = $u['name'] ?? $leader;
            break;
        }
    }
}
$out = [
    'olympos' => array_map(function ($u) {
        return ['access_code' => $u['access_code'] ?? '', 'name' => $u['name'] ?? $u['access_code'] ?? ''];
    }, $olympos),
    'votes' => $votes,
    'vote_changes' => $vote_changes,
    'leader' => $leader,
    'leader_name' => $leaderName,
    'total_olympos' => count($olympos),
];

// Для THEMIS: повертаємо спроби та статус підтвердження лідера (для квесту «Підтвердіть повноваження лідера Іларії»)
$code = trim($_GET['code'] ?? '');
if ($code !== '') {
    $themisUser = null;
    foreach ($users as $u) {
        if (($u['access_code'] ?? '') === $code && ($u['faction'] ?? '') === 'THEMIS' && ($u['chapter'] ?? '') === 'ch2') {
            $themisUser = $u;
            break;
        }
    }
    if ($themisUser !== null) {
        $themisFile = __DIR__ . '/themis_leader_confirm.json';
        $attempts = [];
        $confirmed = [];
        if (file_exists($themisFile)) {
            $tData = json_decode(file_get_contents($themisFile), true);
            if (is_array($tData)) {
                $attempts = $tData['attempts'] ?? [];
                $confirmed = $tData['confirmed'] ?? [];
            }
        }
        $out['themis_attempts_used'] = (int)($attempts[$code] ?? 0);
        $out['themis_attempts_left'] = max(0, 3 - $out['themis_attempts_used']);
        $out['themis_confirmed'] = !empty($confirmed[$code]);
    }
}

echo json_encode($out);
