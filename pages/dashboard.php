<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$username = get_logged_in_user();
$role = get_user_role();

$sitesDir = __DIR__ . '/../sites';

if (!is_dir($sitesDir)) {
    mkdir($sitesDir, 0755, true);
}

$defaultPath = $sitesDir . '/default.json';
if (!file_exists($defaultPath)) {
    $defaultConfig = [
        'site_id' => 'default',
        'settings' => ['title' => 'Мій Лендінг', 'font_heading' => 'Outfit', 'font_body' => 'Inter', 'color_primary' => '#6366f1', 'color_secondary' => '#06b6d4', 'color_bg' => '#ffffff', 'color_text' => '#1f2937', 'color_card_bg' => '#f9fafb'],
        'blocks' => [] 
    ];
    file_put_contents($defaultPath, json_encode($defaultConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        if (!can_user_create_site($username)) {
            $message = 'Ви досягли вашого ліміту на створення сайтів. Зверніться до адміністратора для збільшення кількості.';
            $messageType = 'error';
        } else {
            $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['slug'] ?? '');
            $title = trim($_POST['title'] ?? '');
            
            if (empty($slug)) {
                $message = 'Адреса сайту (Slug) не може бути порожньою.';
                $messageType = 'error';
            } elseif (file_exists($sitesDir . '/' . $slug . '.json')) {
                $message = 'Сайт з такою адресою (Slug) вже існує.';
                $messageType = 'error';
            } else {
                $config = json_decode(file_get_contents($defaultPath), true);
                $config['site_id'] = $slug;
                $config['settings']['title'] = !empty($title) ? $title : 'Лендінг ' . $slug;
                $config['blocks'] = []; 
                $config['owner'] = $username; 
                $config['updated_at'] = date('c');
                
                if (file_put_contents($sitesDir . '/' . $slug . '.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                    header('Location: index.php?id=' . urlencode($slug));
                    exit;
                } else {
                    $message = 'Не вдалося створити новий сайт.';
                    $messageType = 'error';
                }
            }
        }
    }
    

    if ($role === 'admin' && isset($_POST['action']) && $_POST['action'] === 'update_limit') {
        $targetUser = trim($_POST['target_username'] ?? '');
        $newLimit = intval($_POST['max_sites'] ?? 1);
        
        $users = load_users();
        if (isset($users[$targetUser])) {
            $users[$targetUser]['max_sites'] = max(0, $newLimit);
            if (save_users($users)) {
                $message = "Ліміт сайтів для користувача '" . htmlspecialchars($targetUser) . "' успішно змінено на " . $newLimit . ".";
                $messageType = 'success';
            } else {
                $message = 'Не вдалося оновити ліміт.';
                $messageType = 'error';
            }
        } else {
            $message = 'Користувача не знайдено.';
            $messageType = 'error';
        }
    }
}


if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id'] ?? '');
    $filePath = $sitesDir . '/' . $slug . '.json';
    
    if ($slug === 'default') {
        $message = 'Неможливо видалити стандартний шаблон.';
        $messageType = 'error';
    } elseif (file_exists($filePath)) {
        if (!can_edit_site($username, $slug)) {
            $message = 'У вас немає прав для видалення цього сайту.';
            $messageType = 'error';
        } else {
            if (unlink($filePath)) {
                $message = 'Сайт "' . htmlspecialchars($slug) . '" успішно видалено.';
                $messageType = 'success';
            } else {
                $message = 'Не вдалося видалити файл сайту.';
                $messageType = 'error';
            }
        }
    } else {
        $message = 'Сайт не знайдено.';
        $messageType = 'error';
    }
}


$sites = [];
if (is_dir($sitesDir)) {
    $files = scandir($sitesDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $slug = pathinfo($file, PATHINFO_FILENAME);
            if ($slug === 'default') continue; 
            
            $content = json_decode(file_get_contents($sitesDir . '/' . $file), true);
            if ($content) {
                $siteOwner = $content['owner'] ?? 'admin';
                

                if ($role !== 'admin' && $siteOwner !== $username) {
                    continue;
                }
                
                $sites[] = [
                    'slug' => $slug,
                    'title' => $content['settings']['title'] ?? 'Без назви',
                    'blocks_count' => count($content['blocks'] ?? []),
                    'updated_at' => $content['updated_at'] ?? filemtime($sitesDir . '/' . $file),
                    'owner' => $siteOwner,
                    'visits' => intval($content['stats']['views'] ?? 0),
                    'sales' => intval($content['stats']['sales'] ?? 0)
                ];
            }
        }
    }
}

$allUsers = [];
if ($role === 'admin') {
    $usersData = load_users();
    foreach ($usersData as $u => $uInfo) {
        $allUsers[] = [
            'username' => $u,
            'role' => $uInfo['role'] ?? 'user',
            'max_sites' => $uInfo['max_sites'] ?? 1,
            'sites_count' => get_user_sites_count($u)
        ];
    }
}

$userLimit = get_user_limit($username);
$userCreatedCount = get_user_sites_count($username);
$canCreate = can_user_create_site($username);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель керування — Platon LaunchPad</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/editor.css">
    <style>
        body.dashboard-body {
            background-color: #ffffff;
            color: #000000;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        
        .logo-wrap {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #6366f1 0%, #06b6d4 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.5rem;
        }
        
        .logo-title {
            display: none;
        }

        .user-nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-info {
            font-size: 0.9rem;
            color: #94a3b8;
        }

        .user-info strong {
            color: #000000;
        }

        .user-info .badge {
            background-color: rgba(255, 72, 0, 0.15);
            color: var(--accent);
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-left: 0.25rem;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background-color: #f8f9fa;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 16px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }
        
        .stat-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2.25rem;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            color: #000000;
        }
        
        .stat-desc {
            font-size: 0.75rem;
            color: #10b981;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        /* Sites Grid */
        .sites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }
        
        .site-card {
            background-color: #f8f9fa;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .site-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            box-shadow: 0 20px 40px rgba(255, 72, 0, 0.15);
        }
        
        .site-card-body {
            padding: 2rem;
            flex: 1;
        }
        
        .site-card-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: #000000;
        }
        
        .site-card-slug {
            font-size: 0.9rem;
            color: var(--accent);
            font-weight: 600;
            text-decoration: none;
        }

        .site-card-slug:hover {
            text-decoration: underline;
        }

        .site-info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            margin-bottom: 0.85rem;
        }

        .info-label {
            font-size: 0.7rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .site-owner-tag {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f1f5f9;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
        }

        .site-owner-tag strong {
            color: #000000;
        }

        .badge {
            background-color: var(--accent);
            color: #ffffff;
            padding: 0.2rem 0.4rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
        }
        
        .site-card-meta {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            color: #64748b;
            border-top: 1px dashed rgba(0, 0, 0, 0.08);
            padding-top: 1rem;
        }
        
        .site-card-meta div {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .site-card-footer {
            background-color: #f8f9fa;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
            padding: 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .site-card-actions {
            display: flex;
            gap: 0.5rem;
            width: 100%;
            justify-content: space-between;
        }
        
        .btn-card {
            flex: 1;
            padding: 0.5rem 0.25rem;
            font-size: 0.8rem;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-card-edit {
            background-color: var(--accent);
            color: #ffffff;
        }
        
        .btn-card-edit:hover {
            background-color: var(--accent-hover);
        }
        
        .btn-card-view {
            background-color: #ffffff;
            color: #000000;
            border: 1px solid rgba(0, 0, 0, 0.15);
        }
        
        .btn-card-view:hover {
            background-color: #f1f5f9;
        }
        
        .btn-card-delete {
            background: transparent;
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .btn-card-delete:hover {
            background-color: rgba(239, 68, 68, 0.08);
        }
        
        /* Modal Popup */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background-color: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 24px;
            padding: 2.5rem;
            width: 100%;
            max-width: 500px;
            position: relative;
            animation: modalFade 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        @keyframes modalFade {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        .modal-close {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            font-weight: 500;
            border-left: 4px solid transparent;
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border-left-color: #10b981;
        }
        
        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border-left-color: #ef4444;
        }

        /* User Management Table for Admin */
        .user-management-section {
            background-color: #f8f9fa;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 20px;
            padding: 2rem;
            margin-top: 2rem;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }

        .user-table th {
            text-align: left;
            padding: 1rem;
            color: #94a3b8;
            font-weight: 600;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        .user-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            vertical-align: middle;
        }

        .user-table tr:last-child td {
            border-bottom: none;
        }

        .limit-form {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .limit-input {
            width: 60px;
            background-color: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 6px;
            padding: 0.35rem 0.5rem;
            color: #000000;
            text-align: center;
            font-family: inherit;
        }

        .limit-btn {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
 
        .limit-btn:hover {
            background-color: var(--accent-hover);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background-color: #f8f9fa;
            border: 1px dashed rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            margin-bottom: 4rem;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .empty-state-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            font-weight: 600;
            color: #000000;
            margin-bottom: 0.5rem;
        }

        .empty-state-desc {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .btn-editor-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-editor-danger:hover {
            background-color: rgba(239, 68, 68, 0.2);
        }
    </style>
</head>
<body class="dashboard-body">

    <div class="dashboard-container">
        
        <header class="dashboard-header">
            <div class="logo-wrap">
                <img src="assets/logo.svg" alt="Logo" style="height: 32px; width: auto; object-fit: contain;">
            </div>

            <div class="user-nav">
                <div class="user-info">
                    Вітаємо, <strong><?php echo htmlspecialchars($username); ?></strong>
                    <span class="badge"><?php echo $role === 'admin' ? 'Адмін' : 'Користувач'; ?></span>
                </div>
                
                <?php if ($canCreate): ?>
                    <button class="editor-btn btn-editor-primary" id="btn-open-modal">
                        <span>+ Створити лендінг</span>
                    </button>
                <?php else: ?>
                    <button class="editor-btn btn-editor-primary" disabled style="opacity: 0.5; cursor: not-allowed;" title="Ви досягли ліміту сайтів">
                        <span>+ Створити лендінг</span>
                    </button>
                <?php endif; ?>

                <a href="logout.php" class="editor-btn btn-editor-secondary btn-editor-danger" style="text-decoration: none;">
                    <span>Вийти</span>
                </a>
            </div>
        </header>


        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>


        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-label">
                    <?php echo $role === 'admin' ? 'Всього сайтів' : 'Ваші сайти'; ?>
                </span>
                <span class="stat-value"><?php echo count($sites); ?></span>
                <span class="stat-desc">Лендінги активні</span>
            </div>
            
            <div class="stat-card">
                <span class="stat-label">Ліміт на сайти</span>
                <span class="stat-value">
                    <?php echo $role === 'admin' ? '∞' : $userCreatedCount . ' / ' . $userLimit; ?>
                </span>
                <span class="stat-desc" style="<?php echo !$canCreate && $role !== 'admin' ? 'color:#ef4444;' : 'color:#10b981;'; ?>">
                    <?php 
                    if ($role === 'admin') {
                        echo 'Безлімітний доступ адміністратора';
                    } elseif ($canCreate) {
                        echo 'Можна створити ще ' . ($userLimit - $userCreatedCount) . ' сайт(ів)';
                    } else {
                        echo 'Увага: ліміт сайтів вичерпано';
                    }
                    ?>
                </span>
            </div>

            <div class="stat-card">
                <span class="stat-label">Перегляди сайтів</span>
                <span class="stat-value">
                    <?php 
                    $totalVisits = array_sum(array_column($sites, 'visits'));
                    echo number_format($totalVisits); 
                    ?>
                </span>
                <span class="stat-desc">Реальна статистика відвідувань</span>
            </div>

            <div class="stat-card">
                <span class="stat-label">Всього замовлень</span>
                <span class="stat-value">
                    <?php 
                    $totalSales = array_sum(array_column($sites, 'sales'));
                    echo number_format($totalSales); 
                    ?>
                </span>
                <span class="stat-desc">Успішні оплати Platon</span>
            </div>
        </div>

        <div class="panel-section-title" style="margin-bottom: 2rem; font-size: 1.5rem;">
            <?php echo $role === 'admin' ? 'Усі сайти користувачів' : 'Ваші Landing Pages'; ?>
        </div>

        <?php if (count($sites) === 0): ?>
            <div class="empty-state">
                <img src="assets/logo.svg" alt="Platon LaunchPad Logo" style="height: 48px; width: auto; object-fit: contain; margin-bottom: 1.5rem; opacity: 0.3;">
                <div class="empty-state-title">У вас немає створених сайтів</div>
                <p class="empty-state-desc">
                    <?php if ($canCreate): ?>
                        Створіть свій перший професійний лендінг прямо зараз!
                    <?php else: ?>
                        Ви досягли ліміту створення сайтів (0/0). Зверніться до адміна для збільшення кількості.
                    <?php endif; ?>
                </p>
                <?php if ($canCreate): ?>
                    <button class="editor-btn btn-editor-primary" id="btn-open-empty-state" style="margin: 0 auto;">
                        <span>+ Створити перший лендінг</span>
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="sites-grid">
                <?php foreach ($sites as $site): ?>
                    <div class="site-card">
                        <div class="site-card-body">
                            <div class="site-info-item">
                                <span class="info-label">Назва сайту</span>
                                <div class="site-card-title"><?php echo htmlspecialchars($site['title']); ?></div>
                            </div>
                            
                            <div class="site-info-item">
                                <span class="info-label">Адреса сайту</span>
                                <div>
                                    <a href="<?php echo htmlspecialchars($site['slug']); ?>" target="_blank" class="site-card-slug">
                                        /<?php echo htmlspecialchars($site['slug']); ?>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="site-owner-tag">
                                <span>Власник: <strong><?php echo htmlspecialchars($site['owner']); ?></strong></span>
                                <span class="badge"><?php echo $site['blocks_count']; ?> бл.</span>
                            </div>
                            
                            <div class="site-card-meta">
                                <div>
                                    <span>Перегляди: <b><?php echo $site['visits']; ?></b></span>
                                </div>
                                <div>
                                    <span>Замовлення: <b><?php echo $site['sales']; ?></b></span>
                                </div>
                            </div>

                            <div style="font-size: 0.75rem; color: #94a3b8; display: flex; align-items: center; gap: 0.5rem;">
                                <span>Змінено: <?php echo date('d.m.Y H:i', is_numeric($site['updated_at']) ? $site['updated_at'] : strtotime($site['updated_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="site-card-footer">
                            <div class="site-card-actions">
                                <a href="index.php?id=<?php echo htmlspecialchars($site['slug']); ?>" class="btn-card btn-card-edit">
                                    Редагувати
                                </a>
                                <a href="<?php echo htmlspecialchars($site['slug']); ?>" target="_blank" class="btn-card btn-card-view">
                                    Перегляд
                                </a>
                                <?php if ($site['slug'] !== 'default'): ?>
                                    <a href="dashboard.php?action=delete&id=<?php echo htmlspecialchars($site['slug']); ?>" class="btn-card btn-card-delete" onclick="return confirm('Ви впевнені, що хочете видалити цей сайт?')">
                                        Видалити
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
            <div class="user-management-section">
                <div class="panel-section-title" style="font-size: 1.3rem; margin-bottom: 0;">Керування лімітами користувачів</div>
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Ім'я користувача</th>
                            <th>Роль</th>
                            <th>Створено сайтів</th>
                            <th>Поточний ліміт</th>
                            <th>Дія</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allUsers as $uData): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($uData['username']); ?></strong></td>
                                <td>
                                    <span class="badge" style="background-color: <?php echo $uData['role'] === 'admin' ? 'rgba(234,179,8,0.15)' : 'rgba(99,102,241,0.15)'; ?>; color: <?php echo $uData['role'] === 'admin' ? '#f59e0b' : '#818cf8'; ?>;">
                                        <?php echo $uData['role'] === 'admin' ? 'Адміністратор' : 'Користувач'; ?>
                                    </span>
                                </td>
                                <td><?php echo $uData['sites_count']; ?></td>
                                <td>
                                    <?php echo $uData['role'] === 'admin' ? 'Безліміт (9999)' : $uData['max_sites']; ?>
                                </td>
                                <td>
                                    <?php if ($uData['role'] !== 'admin'): ?>
                                        <form action="dashboard.php" method="POST" class="limit-form">
                                            <input type="hidden" name="action" value="update_limit">
                                            <input type="hidden" name="target_username" value="<?php echo htmlspecialchars($uData['username']); ?>">
                                            <input type="number" name="max_sites" class="limit-input" value="<?php echo $uData['max_sites']; ?>" min="0" required>
                                            <button type="submit" class="limit-btn">Оновити</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #64748b; font-size: 0.85rem;">Зміна ліміту недоступна</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

    <?php if ($canCreate): ?>
        <div class="modal" id="create-modal">
            <div class="modal-content">
                <button class="modal-close" id="btn-close-modal">✖</button>
                <div class="panel-section-title" style="font-size: 1.3rem; margin-bottom: 1.5rem;">Створити новий лендінг</div>
                
                <form action="dashboard.php" method="POST">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-group">
                        <label class="form-label">Назва сайту (Title)</label>
                        <input type="text" name="title" class="form-input" placeholder="напр. Магазин стильних аксесуарів" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Унікальна адреса (Slug ID)</label>
                        <input type="text" name="slug" id="modal-slug" class="form-input" placeholder="напр. accessories-store" required>
                        <small style="font-size: 0.75rem; color: #94a3b8; display: block; margin-top: 0.25rem;">
                            Тільки англійські літери, цифри, дефіси. Посилання буде: view.php?id=<b id="modal-slug-preview">accessories-store</b>
                        </small>
                    </div>
                    
                    <button type="submit" class="editor-btn btn-editor-primary" style="width: 100%; margin-top: 1.5rem; justify-content: center; padding: 0.8rem;">
                        Створити чистий сайт
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        const modal = document.getElementById('create-modal');
        const openBtn = document.getElementById('btn-open-modal');
        const openBtnEmpty = document.getElementById('btn-open-empty-state');
        const closeBtn = document.getElementById('btn-close-modal');
        const slugInput = document.getElementById('modal-slug');
        const slugPreview = document.getElementById('modal-slug-preview');

        const openModal = () => {
            if (modal) modal.classList.add('active');
        };

        const closeModal = () => {
            if (modal) modal.classList.remove('active');
        };

        if (openBtn) openBtn.addEventListener('click', openModal);
        if (openBtnEmpty) openBtnEmpty.addEventListener('click', openModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);

        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });
        }

        if (slugInput) {
            slugInput.addEventListener('input', (e) => {
                let slug = e.target.value.toLowerCase().replace(/[^a-z0-9_-]/g, '');
                slugInput.value = slug;
                slugPreview.textContent = slug || 'accessories-store';
            });
        }
    </script>
</body>
</html>
