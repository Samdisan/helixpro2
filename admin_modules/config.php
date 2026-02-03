<?php
// admin_modules/config.php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- КОНСТАНТИ ФАЙЛІВ ---
define('LORE_FILE', 'helix_data.json');
define('USERS_FILE', 'users.json');
define('GAMESTATE_FILE', 'gamestate.json');
define('QUESTS_FILE', 'quests.json');
define('ANALYSIS_REQUESTS_FILE', dirname(__DIR__) . '/analysis_requests.json');
define('AUDIO_CONFIG_FILE', dirname(__DIR__) . '/audio_config.json');
define('ADMIN_PASS', 'HELIX2025');

// --- ФУНКЦІЇ ---
function getJson($file) {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    return json_decode($content, true) ?? [];
}

function saveJson($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/** Санитизація рядка для збереження: trim, strip_tags, обмеження довжини. */
function sanitizeString($s, $maxLen = 10000) {
    $s = trim(strip_tags((string)($s ?? '')));
    return mb_strlen($s) > $maxLen ? mb_substr($s, 0, $maxLen) : $s;
}

/** Вивід у HTML безпечний (екранування). */
function h($s) {
    return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8');
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.html");
    exit;
}
?>
