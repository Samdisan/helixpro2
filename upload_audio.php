<?php
// upload_audio.php — завантаження музики в assets/audio (тільки для адміна)
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

if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
    die(json_encode(['ok' => false, 'error' => 'Upload failed or no file']));
}

$fileTmpPath = $_FILES['audio']['tmp_name'];
$originalName = $_FILES['audio']['name'];
$fileNameCmps = explode('.', $originalName);
$fileExtension = strtolower(end($fileNameCmps));

$allowedExtensions = ['mp3', 'ogg', 'wav'];
if (!in_array($fileExtension, $allowedExtensions)) {
    die(json_encode(['ok' => false, 'error' => 'Дозволені формати: mp3, ogg, wav']));
}

$maxSize = 20 * 1024 * 1024; // 20 MB
if ($_FILES['audio']['size'] > $maxSize) {
    die(json_encode(['ok' => false, 'error' => 'Макс. розмір 20 MB']));
}

// Ім'я файлу: з форми "save_as" або оригінальне (санитизоване)
$saveAs = isset($_POST['save_as']) ? trim(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_POST['save_as'])) : '';
if ($saveAs !== '') {
    if (!preg_match('/\.(mp3|ogg|wav)$/i', $saveAs)) {
        $saveAs .= '.' . $fileExtension;
    }
    $newFileName = $saveAs;
} else {
    $newFileName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($originalName));
}

$audioDir = __DIR__ . '/assets/audio';
if (!is_dir($audioDir)) {
    mkdir($audioDir, 0755, true);
}
$destPath = $audioDir . '/' . $newFileName;

if (move_uploaded_file($fileTmpPath, $destPath)) {
    echo json_encode(['ok' => true, 'file' => $newFileName]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Помилка збереження']);
}
