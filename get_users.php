<?php
// get_users.php — API-проксі для отримання списку гравців без прямого доступу до users.json
// Серверний кеш: 60 с. Клієнтський: 25 с + ETag для швидкого 304.
header('Content-Type: application/json; charset=utf-8');
$cacheTtl = 60; // серверний кеш, секунд
$browserMaxAge = 25; // клієнт може тримати копію 25 с (швидкий back/refresh)
header('Cache-Control: private, max-age=' . $browserMaxAge);

$file = __DIR__ . '/users.json';
$cacheFile = __DIR__ . '/.get_users_cache.json';

if (!is_file($file)) {
    http_response_code(404);
    echo json_encode([]);
    exit;
}

$mtime = filemtime($file);
$now = time();

if (is_file($cacheFile)) {
    $cache = @json_decode(file_get_contents($cacheFile), true);
    if (
        is_array($cache) &&
        isset($cache['data'], $cache['mtime'], $cache['ts']) &&
        $cache['mtime'] == $mtime &&
        ($now - $cache['ts']) < $cacheTtl
    ) {
        $data = $cache['data'];
        $etag = '"' . md5($mtime . 'u') . '"';
        header('ETag: ' . $etag);
        header('X-Cache: HIT');
        if (!empty($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
            http_response_code(304);
            exit;
        }
        echo json_encode($data);
        exit;
    }
}

$content = file_get_contents($file);
$data = json_decode($content, true);
$data = is_array($data) ? $data : [];
$etag = '"' . md5($mtime . 'u') . '"';
header('ETag: ' . $etag);
header('X-Cache: MISS');
if (!empty($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
    http_response_code(304);
    exit;
}
echo json_encode($data);
flush();
@file_put_contents($cacheFile, json_encode([
    'data' => $data,
    'mtime' => $mtime,
    'ts' => $now
], JSON_UNESCAPED_UNICODE));
exit;
