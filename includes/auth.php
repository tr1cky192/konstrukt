<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usersFile = __DIR__ . '/users.php';


if (!file_exists($usersFile)) {
    $defaultUsers = [
        'admin' => [
            'username' => 'admin',
            'password' => password_hash('admin', PASSWORD_DEFAULT),
            'role' => 'admin',
            'max_sites' => 9999
        ],
        'user' => [
            'username' => 'user',
            'password' => password_hash('user', PASSWORD_DEFAULT),
            'role' => 'user',
            'max_sites' => 1
        ]
    ];
    file_put_contents($usersFile, "<?php\nreturn " . var_export($defaultUsers, true) . ";\n");
}

function load_users() {
    global $usersFile;
    return include $usersFile;
}

function save_users($users) {
    global $usersFile;
    return file_put_contents($usersFile, "<?php\nreturn " . var_export($users, true) . ";\n");
}

function is_logged_in() {
    return isset($_SESSION['username']);
}

function get_logged_in_user() {
    return $_SESSION['username'] ?? null;
}

function get_user_role() {
    return $_SESSION['role'] ?? 'user';
}

function get_user_limit($username) {
    $users = load_users();
    return $users[$username]['max_sites'] ?? 1;
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (get_user_role() !== 'admin') {
        die("<h1>Доступ заборонено</h1><p>Ця сторінка доступна тільки для адміністраторів.</p>");
    }
}


function get_user_sites_count($username) {
    $sitesDir = __DIR__ . '/../sites';
    if (!is_dir($sitesDir)) return 0;
    
    $count = 0;
    $files = scandir($sitesDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $slug = pathinfo($file, PATHINFO_FILENAME);
            if ($slug === 'default') continue; 
            
            $content = json_decode(file_get_contents($sitesDir . '/' . $file), true);
            if ($content && isset($content['owner']) && $content['owner'] === $username) {
                $count++;
            }
        }
    }
    return $count;
}

function can_user_create_site($username) {
    $users = load_users();
    $role = $users[$username]['role'] ?? 'user';
    if ($role === 'admin') return true;
    
    $limit = get_user_limit($username);
    $current = get_user_sites_count($username);
    return $current < $limit;
}

function is_site_owner($username, $site_id) {
    if ($site_id === 'default') return false;
    
    $filePath = __DIR__ . '/../sites/' . $site_id . '.json';
    if (!file_exists($filePath)) return false;
    
    $content = json_decode(file_get_contents($filePath), true);
    return ($content && isset($content['owner']) && $content['owner'] === $username);
}

function can_edit_site($username, $site_id) {
    $users = load_users();
    $role = $users[$username]['role'] ?? 'user';
    if ($role === 'admin') return true;
    
    return is_site_owner($username, $site_id);
}
