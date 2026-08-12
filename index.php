<?php
// Отримуємо чистовий URL без GET-параметрів
$requestUri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Нормалізуємо базову директорію
$baseDir = dirname($_SERVER['SCRIPT_NAME']);
if ($baseDir === '/' || $baseDir === '\\') {
    $baseDir = '';
}

if ($baseDir !== '' && strpos($requestUri, $baseDir) === 0) {
    $requestUri = substr($requestUri, strlen($baseDir));
}

// Очищаємо початкові та кінцеві слеші (наприклад, '/dashboard/' перетворюється на 'dashboard')
$uri = trim($requestUri, '/');

// Роутинг
switch ($uri) {
    case '':
    case 'index.php':
    case 'editor':
    case 'editor.php':
        require __DIR__ . '/pages/editor.php';
        break;

    case 'dashboard':
    case 'dashboard.php':
        require __DIR__ . '/pages/dashboard.php';
        break;

    case 'login':
    case 'login.php':
        require __DIR__ . '/pages/login.php';
        break;

    case 'register':
    case 'register.php':
        require __DIR__ . '/pages/register.php';
        break;

    case 'logout':
    case 'logout.php':
        require __DIR__ . '/pages/logout.php';
        break;

    case 'pay':
    case 'pay.php':
        require __DIR__ . '/pages/pay.php';
        break;

    case 'view.php':
        require __DIR__ . '/pages/view.php';
        break;

    default:
        // Перевіряємо чи це ID для перегляду
        if (preg_match('/^[a-zA-Z0-9_-]+$/', $uri)) {
            $_GET['id'] = $uri;
            require __DIR__ . '/pages/view.php';
        } else {
            http_response_code(404);
            if (file_exists(__DIR__ . '/pages/view.php')) {
                require __DIR__ . '/pages/view.php';
            } else {
                echo "404 - Page Not Found";
            }
        }
        break;
}
