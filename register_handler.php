<?php
// register_handler.php
header('Content-Type: application/json');

// Отримуємо JSON від JS
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['ok' => false, 'error' => 'No data']);
    exit;
}

$file = 'applications.json';
$apps = [];

if (file_exists($file)) {
    $apps = json_decode(file_get_contents($file), true) ?? [];
}

// Додаємо нову заявку
$newApp = [
    'id' => uniqid(),
    'time' => date('Y-m-d H:i:s'),
    'name' => htmlspecialchars($data['name'] ?? ''),
    'tg' => htmlspecialchars($data['tg'] ?? ''),
    'faction_pref' => isset($data['faction_pref']) ? htmlspecialchars($data['faction_pref']) : '',
    'role_pref' => isset($data['role_pref']) ? htmlspecialchars($data['role_pref']) : '',
    'anon' => $data['anon'] ?? false,
    'psy' => $data['psy'] ?? [],
    'lore' => $data['lore'] ?? [],
    'status' => 'NEW'
];

array_unshift($apps, $newApp); // Додаємо на початок

if (file_put_contents($file, json_encode($apps, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Write error']);
}
?>
