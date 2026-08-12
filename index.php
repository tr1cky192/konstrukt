<?php
$requestUri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$baseDir = dirname($_SERVER['SCRIPT_NAME']);
if ($baseDir === '/' || $baseDir === '\\') {
    $baseDir = '';
}


if ($baseDir !== '' && strpos($requestUri, $baseDir) === 0) {
    $requestUri = substr($requestUri, strlen($baseDir));
}

$uri = ltrim($requestUri, '/');


if (strpos($uri, '?') !== false) {
    $uri = explode('?', $uri)[0];
}


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

        if (preg_match('/^[a-zA-Z0-9_-]+$/', $uri)) {
            $_GET['id'] = $uri;
            require __DIR__ . '/pages/view.php';
        } else {
            http_response_code(404);
            require __DIR__ . '/pages/view.php';
        }
        break;
}
