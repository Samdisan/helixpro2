<?php
// save_bunker_test.php — збереження результатів тесту «Протокол бункера» у профіль гравця (медкарта)
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$code = trim($input['access_code'] ?? $_POST['access_code'] ?? '');
$answers = $input['answers'] ?? $_POST['answers'] ?? null;
if (is_string($answers)) $answers = json_decode($answers, true);
if (!is_array($answers)) $answers = [];
$pronounForm = isset($answers['_pronoun']) && in_array($answers['_pronoun'], ['vin', 'vona', 'vony'], true) ? $answers['_pronoun'] : null;
if ($pronounForm !== null) {
    $answersCopy = $answers;
    unset($answersCopy['_pronoun']);
    $answers = $answersCopy;
}

if (!$code) {
    echo json_encode(['ok' => false, 'error' => 'Missing access_code']);
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

// Визначення типу профілю за відповідями: Лідер, Системник, Спостерігач, Слухняний, Бунтар
$resultType = 'Слухняний';
if (!empty($answers)) {
    $points = ['A' => 4, 'B' => 3, 'C' => 2, 'D' => 1];
    $counts = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
    $total = 0;
    foreach ($answers as $letter) {
        $letter = strtoupper(trim((string)$letter));
        if (isset($points[$letter])) {
            $counts[$letter]++;
            $total += $points[$letter];
        }
    }
    $maxCount = max($counts);
    if ($maxCount >= 3) {
        if ($counts['A'] === $maxCount && $counts['A'] >= $counts['B'] && $counts['A'] >= $counts['C'] && $counts['A'] >= $counts['D']) {
            $resultType = 'Лідер';
        } elseif ($counts['B'] === $maxCount && $counts['B'] >= $counts['A'] && $counts['B'] >= $counts['C'] && $counts['B'] >= $counts['D']) {
            $resultType = 'Системник';
        } elseif ($counts['C'] === $maxCount && $counts['C'] >= $counts['A'] && $counts['C'] >= $counts['B'] && $counts['C'] >= $counts['D']) {
            $resultType = 'Спостерігач';
        } elseif ($counts['D'] === $maxCount && $counts['D'] >= $counts['A'] && $counts['D'] >= $counts['B'] && $counts['D'] >= $counts['C']) {
            $resultType = 'Слухняний';
        } else {
            $resultType = $total >= 24 ? 'Бунтар' : 'Рівноважений';
        }
    } else {
        $resultType = $total >= 26 ? 'Бунтар' : ($total <= 18 ? 'Слухняний' : 'Рівноважений');
    }
}

$found = false;
foreach ($users as $i => $u) {
    if (($u['access_code'] ?? '') === $code || ($u['id'] ?? '') === $code) {
        $users[$i]['bunker_test'] = [
            'answers' => $answers,
            'saved_at' => time(),
            'pronoun_form' => $pronounForm,
            'result_type' => $resultType
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
echo json_encode(['ok' => true, 'result_type' => $resultType]);
exit;
