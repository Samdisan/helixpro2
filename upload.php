<?php
// upload.php — завантаження аватарки; перевірка коду по users.json, тільки image/jpeg, image/png
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['ok' => false, 'error' => 'Method not allowed']));
}

$code = trim((string)($_POST['access_code'] ?? ''));
if ($code === '') {
    die(json_encode(['ok' => false, 'error' => 'Missing access_code']));
}

// Перевірка коду по users.json — тільки існуючий гравець може завантажити файл під своїм кодом
$usersFile = __DIR__ . '/users.json';
if (!file_exists($usersFile)) {
    die(json_encode(['ok' => false, 'error' => 'Data not found']));
}
$users = json_decode(file_get_contents($usersFile), true);
if (!is_array($users)) {
    die(json_encode(['ok' => false, 'error' => 'Invalid data']));
}
$userFound = false;
foreach ($users as $u) {
    if (($u['access_code'] ?? '') === $code) {
        $userFound = true;
        break;
    }
}
if (!$userFound) {
    die(json_encode(['ok' => false, 'error' => 'Invalid access_code']));
}

// Перевірка, чи є файл
if (!isset($_FILES['avatar'])) {
    die(json_encode(['ok' => false, 'error' => 'No file sent. Check server limits (post_max_size, upload_max_filesize).']));
}
$err = $_FILES['avatar']['error'];
if ($err !== UPLOAD_ERR_OK) {
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'Файл занадто великий (ліміт сервера).',
        UPLOAD_ERR_FORM_SIZE => 'Файл занадто великий.',
        UPLOAD_ERR_PARTIAL => 'Файл завантажено лише частково.',
        UPLOAD_ERR_NO_FILE => 'Файл не вибрано.',
        UPLOAD_ERR_NO_TMP_DIR => 'Помилка сервера: немає тимчасової папки.',
        UPLOAD_ERR_CANT_WRITE => 'Помилка сервера: не вдалося записати файл.',
        UPLOAD_ERR_EXTENSION => 'Сервер заблокував завантаження.',
    ];
    die(json_encode(['ok' => false, 'error' => $messages[$err] ?? 'Upload failed (code ' . $err . ')']));
}

$fileTmpPath = $_FILES['avatar']['tmp_name'];
$fileNameCmps = explode(".", $_FILES['avatar']['name']);
$fileExtension = strtolower(end($fileNameCmps));

// Тільки image/jpeg, image/png — перевірка і по розширенню, і по MIME
$allowedExtensions = ['jpg', 'jpeg', 'png'];
$allowedMimes = ['image/jpeg', 'image/png'];
if (!in_array($fileExtension, $allowedExtensions)) {
    die(json_encode(['ok' => false, 'error' => 'Invalid file type. Allowed: JPEG, PNG only.']));
}
$mime = '';
if (function_exists('finfo_open') && $fileTmpPath && is_readable($fileTmpPath)) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $mime = finfo_file($finfo, $fileTmpPath) ?: '';
        finfo_close($finfo);
    }
}
if (!in_array($mime, $allowedMimes)) {
    die(json_encode(['ok' => false, 'error' => 'Invalid file type. Allowed: JPEG, PNG only.']));
}

// Нормалізуємо розширення для імені: jpg/jpeg -> jpg
$saveExt = ($fileExtension === 'jpeg') ? 'jpg' : $fileExtension;

// Нове ім'я файлу = КОД_ДОСТУПУ.jpg (або png)
$newFileName = $code . '.' . $saveExt;
$uploadFileDir = __DIR__ . '/uploads/';

if (!is_dir($uploadFileDir)) {
    if (!@mkdir($uploadFileDir, 0755, true)) {
        die(json_encode(['ok' => false, 'error' => 'Папка uploads не існує і не може бути створена.']));
    }
}
if (!is_writable($uploadFileDir)) {
    die(json_encode(['ok' => false, 'error' => 'Папка uploads недоступна для запису.']));
}

$dest_path = $uploadFileDir . $newFileName;

// Видалити старий аватар з іншим розширенням, щоб у кабінеті показувався новий
$otherExt = ($saveExt === 'jpg') ? 'png' : 'jpg';
$otherPath = $uploadFileDir . $code . '.' . $otherExt;
if (file_exists($otherPath) && is_file($otherPath)) {
    @unlink($otherPath);
}

if (move_uploaded_file($fileTmpPath, $dest_path)) {
    echo json_encode(['ok' => true, 'file' => $newFileName]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Не вдалося зберегти файл. Перевірте права на папку uploads.']);
}
?>