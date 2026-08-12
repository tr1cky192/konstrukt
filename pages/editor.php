<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$username = get_logged_in_user();
$role = get_user_role();

$siteId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id'] ?? 'default');
if (empty($siteId)) {
    $siteId = 'default';
}


if ($siteId === 'default' && $role !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

if (!can_edit_site($username, $siteId)) {
    die("<h1>Доступ заборонено</h1><p>Ви не маєте прав для редагування цього сайту.</p>");
}

$sitesDir = __DIR__ . '/../sites';
$filePath = $sitesDir . '/' . $siteId . '.json';


if (!file_exists($filePath)) {
    if ($siteId !== 'default' && file_exists($sitesDir . '/default.json')) {
        copy($sitesDir . '/default.json', $filePath);

        $copiedData = json_decode(file_get_contents($filePath), true);
        if ($copiedData) {
            $copiedData['owner'] = $username;
            $copiedData['blocks'] = []; 
            file_put_contents($filePath, json_encode($copiedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}


$configData = 'null';
if (file_exists($filePath)) {
    $configData = file_get_contents($filePath);
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platon LaunchPad — Конструктор лендінгів</title>
    
    <link rel="stylesheet" href="css/editor.css">

</head>
<body class="editor-body">


    <header class="editor-header">
        <a href="dashboard.php" class="brand" style="text-decoration: none;">
            <img src="assets/logo.svg" alt="Logo" style="height: 32px; width: auto; object-fit: contain;">
        </a>
        

        <div class="viewport-controls">
            <button class="viewport-btn active" data-viewport="desktop" title="Десктоп">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                <span>Десктоп</span>
            </button>
            <button class="viewport-btn" data-viewport="tablet" title="Планшет">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                <span>Планшет</span>
            </button>
            <button class="viewport-btn" data-viewport="mobile" title="Мобільний">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                <span>Мобільний</span>
            </button>
        </div>
        

        <div class="publish-controls">
            <a href="<?php echo htmlspecialchars($siteId); ?>" target="_blank" class="editor-btn btn-editor-secondary" id="btn-preview-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                Переглянути сайт
            </a>
            <button class="editor-btn btn-editor-primary" id="btn-save">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                Зберегти зміни
            </button>
        </div>
    </header>


    <main class="editor-main">
        

        <aside class="editor-sidebar">
            <div class="sidebar-tabs">
                <button class="tab-btn active" data-tab="blocks">Блоки</button>
                <button class="tab-btn" data-tab="settings">Налаштування</button>
            </div>
            
            <div class="sidebar-content">
                <div class="tab-panel active" id="tab-blocks">
                    <div class="panel-section-title">Додати новий блок</div>
                    <div class="add-blocks-grid">
                        <button class="add-block-card" data-type="header">
                            <span class="add-block-icon" style="background: linear-gradient(135deg, #6366f1, #818cf8);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
                            </span>
                            <span class="add-block-label">Шапка</span>
                        </button>
                        <button class="add-block-card" data-type="hero">
                            <span class="add-block-icon" style="background: linear-gradient(135deg, #f59e0b, #f97316);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                            </span>
                            <span class="add-block-label">Банер</span>
                        </button>
                        <button class="add-block-card" data-type="features">
                            <span class="add-block-icon" style="background: linear-gradient(135deg, #10b981, #34d399);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            </span>
                            <span class="add-block-label">Переваги</span>
                        </button>
                        <button class="add-block-card" data-type="showcase">
                            <span class="add-block-icon" style="background: linear-gradient(135deg, #3b82f6, #60a5fa);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 3l-4 4-4-4"/></svg>
                            </span>
                            <span class="add-block-label">Товар</span>
                        </button>
                        <button class="add-block-card" data-type="testimonials">
                            <span class="add-block-icon" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            </span>
                            <span class="add-block-label">Відгуки</span>
                        </button>
                        <button class="add-block-card" data-type="pricing">
                            <span class="add-block-icon" style="background: linear-gradient(135deg, #ef4444, #f87171);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            </span>
                            <span class="add-block-label">Оплата</span>
                        </button>
                        <button class="add-block-card" data-type="footer">
                            <span class="add-block-icon" style="background: linear-gradient(135deg, #64748b, #94a3b8);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
                            </span>
                            <span class="add-block-label">Підвал</span>
                        </button>
                    </div>
                    
                    <div class="panel-section-title" style="margin-top: 2rem;">Активні блоки сайту</div>
                    <div class="active-blocks-list" id="active-blocks-list">

                    </div>
                </div>

                <div class="tab-panel" id="tab-settings">
                    <div class="panel-section-title">Кольорова палітра</div>
                    <div class="form-group inline-group">
                        <label class="form-label">Основний колір</label>
                        <input type="color" class="color-picker-input" id="color-primary" value="#6366f1">
                    </div>
                    <div class="form-group inline-group">
                        <label class="form-label">Вторинний колір</label>
                        <input type="color" class="color-picker-input" id="color-secondary" value="#06b6d4">
                    </div>
                    <div class="form-group inline-group">
                        <label class="form-label">Фон сторінки</label>
                        <input type="color" class="color-picker-input" id="color-bg" value="#ffffff">
                    </div>
                    <div class="form-group inline-group">
                        <label class="form-label">Колір тексту</label>
                        <input type="color" class="color-picker-input" id="color-text" value="#1f2937">
                    </div>
                    <div class="form-group inline-group">
                        <label class="form-label">Фон карток</label>
                        <input type="color" class="color-picker-input" id="color-card-bg" value="#f9fafb">
                    </div>
                    
                    <div class="panel-section-title" style="margin-top: 2rem;">Шрифти</div>
                    <div class="form-group">
                        <label class="form-label">Шрифт заголовків</label>
                        <select class="form-select" id="font-heading">
                            <option value="Outfit">Outfit</option>
                            <option value="Inter">Inter</option>
                            <option value="Montserrat">Montserrat</option>
                            <option value="Playfair Display">Playfair Display</option>
                            <option value="Unbounded">Unbounded</option>
                            <option value="Oswald">Oswald</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Шрифт тексту</label>
                        <select class="form-select" id="font-body">
                            <option value="Inter">Inter</option>
                            <option value="Roboto">Roboto</option>
                            <option value="Open Sans">Open Sans</option>
                            <option value="Lora">Lora</option>
                            <option value="Montserrat">Montserrat</option>
                        </select>
                    </div>
                    
                    <div class="panel-section-title" style="margin-top: 2rem;">Загальні налаштування</div>
                    <div class="form-group">
                        <label class="form-label">Адреса сайту (Slug ID)</label>
                        <input type="text" class="form-input" id="site-slug-id" value="<?php echo htmlspecialchars($siteId); ?>" placeholder="напр. my-product">
                        <small style="font-size: 0.75rem; color: var(--panel-text-muted); display: block; margin-top: 0.25rem;">
                            Впливає на посилання: <b>/<span id="site-slug-display"><?php echo htmlspecialchars($siteId); ?></span></b>
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Заголовок сторінки (Title)</label>
                        <input type="text" class="form-input" id="site-page-title" value="Мій Лендінг" placeholder="напр. Магазин стильного одягу">
                    </div>
                    
                    <div class="panel-section-title" style="margin-top: 2rem;">Налаштування оплати</div>
                    
                    <div class="form-group">
                        <label class="form-label">Merchant Key (Platon)</label>
                        <input type="text" class="form-input" id="payment-merchant-key" value="" placeholder="Введіть ваш публічний ключ Platon">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Merchant Password / Secret</label>
                        <input type="password" class="form-input" id="payment-merchant-secret" value="" placeholder="Введіть ваш секретний пароль Platon">
                    </div>
                </div>

                <div class="tab-panel" id="tab-edit-block">
                    <div id="block-editor-container"></div>
                </div>
                
            </div>
        </aside>
        

        <section class="editor-canvas-container">
            <div class="canvas-wrapper desktop" id="canvas-wrapper">
                <iframe src="view.php?id=<?php echo htmlspecialchars($siteId); ?>&v=1" class="preview-iframe" id="preview-iframe"></iframe>
            </div>
        </section>
        
    </main>


    <div class="toast-container" id="toast-container"></div>

    <script>
        window.INITIAL_SITE_CONFIG = <?php echo $configData; ?>;
        window.BASE_DIR = '<?php echo htmlspecialchars($baseDir ?? ""); ?>';
    </script>
    <script src="js/editor.js"></script>
</body>
</html>
