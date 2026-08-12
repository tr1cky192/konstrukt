<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Якщо це реально існуючий файл (css, js, картинка), віддаємо його напряму
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// Усі інші запити направляємо в index.php
require_once __DIR__ . '/index.php';