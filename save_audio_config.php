<?php
// save_audio_config.php — збереження прив'язки музики до глав/фракцій (тільки для адміна)
require_once __DIR__ . '/admin_modules/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged'])) {
    http_response_code(403);
    die(json_encode(['ok' => false, 'error' => 'Forbidden']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['ok' => false, 'error' => 'Method not allowed']));
}

$config = getJson(AUDIO_CONFIG_FILE);
if (empty($config)) {
    $config = [
        'chapters' => ['ch1' => 'ambience.mp3', 'ch2' => 'ambience.mp3'],
        'factions' => ['OLYMPOS' => 'ambience_olympos.mp3', 'ORIGIN' => 'ambience_origin.mp3', 'THEMIS' => 'ambience_themis.mp3', 'MOIRAI' => ''],
        'default_volume' => 0.3
    ];
}

$chapters = $_POST['chapters'] ?? null;
$factions = $_POST['factions'] ?? null;
$defaultVolume = isset($_POST['default_volume']) ? (float)$_POST['default_volume'] : null;
$bunkerSound = isset($_POST['3']) ? trim(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_POST['3'])) : null;
$applicationSound = isset($_POST['4']) ? trim(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_POST['4'])) : null;
$backgroundTracks = [];
for ($i = 1; $i <= 4; $i++) {
    $key = 'background_track_' . $i;
    if (!empty($_POST[$key])) {
        $f = trim(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_POST[$key]));
        if ($f !== '') $backgroundTracks[] = $f;
    }
}

if (is_array($chapters)) {
    $config['chapters'] = [
        'ch1' => isset($chapters['ch1']) ? trim(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $chapters['ch1'])) : ($config['chapters']['ch1'] ?? 'ambience.mp3'),
        'ch2' => isset($chapters['ch2']) ? trim(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $chapters['ch2'])) : ($config['chapters']['ch2'] ?? 'ambience.mp3')
    ];
}
if (is_array($factions)) {
    $config['factions'] = [
        'OLYMPOS' => isset($factions['OLYMPOS']) ? trim(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $factions['OLYMPOS'])) : ($config['factions']['OLYMPOS'] ?? 'ambience_olympos.mp3'),
        'ORIGIN'  => isset($factions['ORIGIN'])  ? trim(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $factions['ORIGIN']))  : ($config['factions']['ORIGIN'] ?? 'ambience_origin.mp3'),
        'THEMIS'  => isset($factions['THEMIS'])  ? trim(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $factions['THEMIS']))  : ($config['factions']['THEMIS'] ?? 'ambience_themis.mp3'),
        'MOIRAI'  => isset($factions['MOIRAI'])  ? trim(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $factions['MOIRAI']))  : ($config['factions']['MOIRAI'] ?? '')
    ];
}
if ($defaultVolume !== null) {
    $config['default_volume'] = max(0, min(1, $defaultVolume));
}
if ($bunkerSound !== null) {
    $config['3'] = $bunkerSound === '' ? '' : $bunkerSound;
}
if ($applicationSound !== null) {
    $config['4'] = $applicationSound === '' ? '' : $applicationSound;
}
$config['background_tracks'] = $backgroundTracks;

saveJson(AUDIO_CONFIG_FILE, $config);
echo json_encode(['ok' => true]);
