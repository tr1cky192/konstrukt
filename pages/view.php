<?php
$baseDir = dirname($_SERVER['SCRIPT_NAME']);
$baseDir = ($baseDir === '\\' || $baseDir === '/') ? '' : rtrim($baseDir, '/\\');

$siteId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id'] ?? '');

if (empty($siteId)) {
    $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = explode('/', rtrim($uriPath, '/'));
    $lastSegment = end($segments);
    if ($lastSegment !== 'view.php' && $lastSegment !== '') {
        $siteId = preg_replace('/[^a-zA-Z0-9_-]/', '', $lastSegment);
    }
}

if (empty($siteId)) {
    $siteId = 'default';
}

$sitesDir = __DIR__ . '/../sites';
$filePath = $sitesDir . '/' . $siteId . '.json';

if (!file_exists($filePath)) {
    header("HTTP/1.0 404 Not Found");
    ?>
    <!DOCTYPE html>
    <html lang="uk">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 — Сторінку не знайдено</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Outfit:wght@800&display=swap" rel="stylesheet">
        <style>
            body {
                background-color: #ffffff;
                color: #000000;
                font-family: 'Inter', sans-serif;
                margin: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                text-align: center;
                padding: 1.5rem;
            }
            .container {
                max-width: 450px;
            }
            .logo {
                height: 48px;
                margin-bottom: 2rem;
            }
            .error-code {
                font-family: 'Outfit', sans-serif;
                font-size: 5rem;
                font-weight: 800;
                color: #ff4800;
                margin: 0 0 1rem 0;
                line-height: 1;
            }
            h1 {
                font-family: 'Outfit', sans-serif;
                font-size: 1.5rem;
                font-weight: 800;
                margin: 0 0 1rem 0;
            }
            p {
                color: #555555;
                font-size: 0.95rem;
                margin: 0 0 2rem 0;
                line-height: 1.5;
            }
            .btn {
                display: inline-block;
                background-color: #ff4800;
                color: #ffffff;
                text-decoration: none;
                padding: 0.8rem 2rem;
                border-radius: 10px;
                font-weight: 600;
                font-size: 0.95rem;
                transition: background-color 0.2s;
            }
            .btn:hover {
                background-color: #e03f00;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <img src="<?php echo htmlspecialchars($baseDir); ?>/assets/logo.svg" alt="Logo" class="logo">
            <div class="error-code">404</div>
            <h1>Сторінку не знайдено</h1>
            <p>Сайт <strong><?php echo htmlspecialchars($siteId); ?></strong> ще не створено в системі або він був видалений.</p>
            <a href="dashboard.php" class="btn">Повернутися в кабінет</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$config = json_decode(file_get_contents($filePath), true);
if (!$config) {
    die("<h1>Помилка конфігурації</h1><p>Не вдалося розпарсити файл налаштувань.</p>");
}


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($config['stats'])) {
    $config['stats'] = ['views' => 0, 'sales' => 0];
}

$isIframePreview = isset($_GET['v']);
$statsChanged = false;


if (!$isIframePreview && $siteId !== 'default') {
    $config['stats']['views'] = ($config['stats']['views'] ?? 0) + 1;
    $statsChanged = true;
}


$status = $_GET['status'] ?? '';
if ($status === 'success' && $siteId !== 'default') {
    if (!isset($_SESSION['sales_counted_' . $siteId])) {
        $config['stats']['sales'] = ($config['stats']['sales'] ?? 0) + 1;
        $_SESSION['sales_counted_' . $siteId] = true;
        $statsChanged = true;
    }
}


if ($statsChanged) {
    file_put_contents($filePath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

$settings = $config['settings'] ?? [];
$blocks = $config['blocks'] ?? [];


$preloadHeroImage = '';
foreach ($blocks as $block) {
    if (($block['type'] ?? '') === 'hero' && !empty($block['image'])) {
        $preloadHeroImage = $block['image'];
        break;
    }
}


$primaryColor = $settings['color_primary'] ?? '#6366f1';
$secondaryColor = $settings['color_secondary'] ?? '#06b6d4';
$bgColor = $settings['color_bg'] ?? '#ffffff';
$textColor = $settings['color_text'] ?? '#1f2937';
$cardBg = $settings['color_card_bg'] ?? '#f9fafb';
$fontHeading = $settings['font_heading'] ?? 'Outfit';
$fontBody = $settings['font_body'] ?? 'Inter';

$title = $settings['title'] ?? 'Мій Лендінг';


function renderStars($rating)
{
    $html = '';
    for ($i = 0; $i < 5; $i++) {
        if ($i < $rating) {
            $html .= '<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>';
        } else {
            $html .= '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>';
        }
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="uk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    
    <?php if (!empty($preloadHeroImage)): ?>
        <?php 
        $preloadPath = $preloadHeroImage;
        if (strpos($preloadPath, 'http') !== 0 && strpos($preloadPath, '/') !== 0) {
            $preloadPath = $baseDir . '/' . $preloadPath;
        }
        ?>
        <link rel="preload" fetchpriority="high" as="image" href="<?php echo htmlspecialchars($preloadPath); ?>">
    <?php endif; ?>


    <meta name="description" content="Створіть свій успіх онлайн за допомогою нашого конструктора лендінгів.">
    <meta property="og:title" content="<?php echo htmlspecialchars($title); ?>">
    <meta property="og:description" content="Швидкий старт продажів та послуг без програмування.">


    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($fontHeading); ?>:wght@600;700;800&family=<?php echo urlencode($fontBody); ?>:wght@400;500;600&display=swap"
        rel="stylesheet">


    <link rel="stylesheet" href="<?php echo $baseDir; ?>/css/blocks.css">


    <style>
        :root {
            --primary-color:
                <?php echo $primaryColor; ?>
            ;

            --primary-hover:
                <?php echo $primaryColor; ?>
                dd;
            --secondary-color:
                <?php echo $secondaryColor; ?>
            ;
            --bg-color:
                <?php echo $bgColor; ?>
            ;
            --text-color:
                <?php echo $textColor; ?>
            ;
            --card-bg:
                <?php echo $cardBg; ?>
            ;
            --font-heading: '<?php echo $fontHeading; ?>', sans-serif;
            --font-body: '<?php echo $fontBody; ?>', sans-serif;
        }


        html {
            scroll-behavior: smooth;
        }
    </style>
</head>

<body class="public-body">

    <?php if (isset($_GET['status'])): ?>
        <div
            style="background-color: <?php echo $_GET['status'] === 'success' ? '#10b981' : '#ef4444'; ?>; color: #ffffff; text-align: center; padding: 1.2rem; font-weight: 600; font-family: var(--font-heading); position: sticky; top: 0; z-index: 1000; box-shadow: 0 4px 20px rgba(0,0,0,0.15); display: flex; align-items: center; justify-content: center; gap: 1rem;">
            <span>
                <?php echo $_GET['status'] === 'success'
                    ? 'Дякуємо! Оплату проведено успішно.'
                    : 'Помилка оплати. Будь ласка, перевірте дані картки та спробуйте ще раз.'; ?>
            </span>
            <a href="view.php?id=<?php echo htmlspecialchars($siteId); ?>"
                style="color: #ffffff; text-decoration: none; background-color: rgba(255,255,255,0.2); padding: 0.35rem 1rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 700; transition: background-color 0.2s;">Закрити</a>
        </div>
    <?php endif; ?>

    <?php foreach ($blocks as $block): ?>
        <?php
        $id = htmlspecialchars($block['id'] ?? uniqid('block_'));
        $type = $block['type'] ?? '';
        ?>

        <?php if ($type === 'header'): ?>

            <header id="<?php echo $id; ?>" class="block-header">
                <div class="container header-wrap">
                    <a href="#" class="logo-link">
                        <?php if (!empty($block['logo_img'])): ?>
                            <?php
                            $logoPath = $block['logo_img'];
                            if (strpos($logoPath, 'http') !== 0 && strpos($logoPath, '/') !== 0) {
                                $logoPath = $baseDir . '/' . $logoPath;
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo" class="logo-img">
                        <?php endif; ?>
                        <span><?php echo htmlspecialchars($block['logo_text'] ?? 'ApexCorp'); ?></span>
                    </a>

                    <?php if (!empty($block['nav_links'])): ?>
                        <ul class="header-nav">
                            <?php
                            $navs = explode(',', $block['nav_links']);
                            foreach ($navs as $navItem):
                                $navItem = trim($navItem);

                                $href = '#' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $navItem));

                                if (stripos($navItem, 'головна') !== false || stripos($navItem, 'home') !== false)
                                    $href = '#';
                                if (stripos($navItem, 'переваги') !== false || stripos($navItem, 'features') !== false)
                                    $href = '#features';
                                if (stripos($navItem, 'тарифи') !== false || stripos($navItem, 'pricing') !== false)
                                    $href = '#pricing';
                                if (stripos($navItem, 'відгуки') !== false || stripos($navItem, 'reviews') !== false)
                                    $href = '#testimonials';
                                ?>
                                <li><a href="<?php echo $href; ?>"><?php echo htmlspecialchars($navItem); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (!empty($block['cta_text'])): ?>
                        <a href="#pricing" class="btn btn-primary" style="padding: 0.5rem 1.5rem; font-size: 0.9rem;">
                            <?php echo htmlspecialchars($block['cta_text']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </header>

        <?php elseif ($type === 'hero'): ?>

            <?php 
            $heroBg = $block['image'] ?? '';
            if (!empty($heroBg) && strpos($heroBg, 'http') !== 0 && strpos($heroBg, '/') !== 0) {
                $heroBg = $baseDir . '/' . $heroBg;
            }
            ?>
            <section id="<?php echo $id; ?>" class="block-hero"
                style="<?php echo !empty($heroBg) ? "background-image: url('" . htmlspecialchars($heroBg) . "');" : ""; ?>">
                <div class="container">
                    <div class="hero-wrap">
                        <h1 class="hero-title"><?php echo htmlspecialchars($block['title'] ?? 'Створіть свій успіх'); ?></h1>
                        <p class="hero-subtitle"><?php echo htmlspecialchars($block['subtitle'] ?? ''); ?></p>
                        <div class="hero-btns">
                            <?php if (!empty($block['btn_primary_text'])): ?>
                                <a href="#pricing"
                                    class="btn btn-primary"><?php echo htmlspecialchars($block['btn_primary_text']); ?></a>
                            <?php endif; ?>
                            <?php if (!empty($block['btn_secondary_text'])): ?>
                                <a href="#features" class="btn btn-secondary"
                                    style="color: #ffffff; border-color: rgba(255,255,255,0.4);"><?php echo htmlspecialchars($block['btn_secondary_text']); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

        <?php elseif ($type === 'features'): ?>

            <section id="features" class="section block-features">
                <div class="container">
                    <div class="section-title-wrap">
                        <h2 class="section-title"><?php echo htmlspecialchars($block['title'] ?? 'Наші переваги'); ?></h2>
                        <?php if (!empty($block['subtitle'])): ?>
                            <p class="section-subtitle"><?php echo htmlspecialchars($block['subtitle']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="features-grid">
                        <?php
                        $items = $block['items'] ?? [];
                        foreach ($items as $item):
                            ?>
                            <div class="feature-card">
                                <div class="feature-icon-wrap">
                                    <?php echo htmlspecialchars($item['icon'] ?? '✓'); ?>
                                </div>
                                <h3 class="feature-title"><?php echo htmlspecialchars($item['title'] ?? 'Перевага'); ?></h3>
                                <p class="feature-desc"><?php echo htmlspecialchars($item['desc'] ?? ''); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

        <?php elseif ($type === 'showcase'): ?>

            <section id="showcase" class="section block-showcase">
                <div class="container">
                    <div class="showcase-wrap">
                        <div class="showcase-content">
                            <h3><?php echo htmlspecialchars($block['title'] ?? 'Опис продукту'); ?></h3>
                            <p><?php echo htmlspecialchars($block['desc'] ?? ''); ?></p>

                            <?php if (!empty($block['bullets'])): ?>
                                <ul class="showcase-bullets">
                                    <?php foreach ($block['bullets'] as $bullet): ?>
                                        <li>
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                            <span><?php echo htmlspecialchars($bullet); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <a href="#pricing" class="btn btn-primary">Придбати зараз</a>
                        </div>

                        <?php if (!empty($block['image'])): ?>
                            <div class="showcase-media">
                                <img src="<?php echo htmlspecialchars($block['image']); ?>" alt="Product showcase">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

        <?php elseif ($type === 'pricing'): ?>

            <section id="pricing" class="section block-pricing">
                <div class="container">
                    <div class="section-title-wrap">
                        <h2 class="section-title"><?php echo htmlspecialchars($block['title'] ?? 'Тарифи'); ?></h2>
                        <?php if (!empty($block['subtitle'])): ?>
                            <p class="section-subtitle"><?php echo htmlspecialchars($block['subtitle']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="pricing-wrap">
                        <div class="pricing-card">
                            <div class="pricing-badge">Хіт продажів</div>
                            <div class="pricing-name"><?php echo htmlspecialchars($block['product_name'] ?? 'Стандарт'); ?>
                            </div>

                            <div class="price-display">
                                <span class="price-currency"><?php echo htmlspecialchars($block['currency'] ?? '₴'); ?></span>
                                <span class="price-amount"><?php echo htmlspecialchars($block['price'] ?? '0'); ?></span>
                            </div>

                            <?php if (!empty($block['features'])): ?>
                                <ul class="pricing-features">
                                    <?php foreach ($block['features'] as $feature): ?>
                                        <li>
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                            <span><?php echo htmlspecialchars($feature); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>


                            <form action="<?php echo $baseDir; ?>/pay.php" method="POST" style="margin-top: 2rem;">
                                <input type="hidden" name="site_id" value="<?php echo htmlspecialchars($siteId); ?>">
                                <input type="hidden" name="product_name"
                                    value="<?php echo htmlspecialchars($block['product_name'] ?? 'Order'); ?>">
                                <input type="hidden" name="amount"
                                    value="<?php echo htmlspecialchars($block['price'] ?? '0'); ?>">
                                <input type="hidden" name="currency"
                                    value="<?php echo htmlspecialchars($block['currency'] ?? 'UAH'); ?>">

                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    <?php echo htmlspecialchars($block['btn_text'] ?? 'Оплатити'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>

        <?php elseif ($type === 'testimonials'): ?>

            <section id="testimonials" class="section block-testimonials">
                <div class="container">
                    <div class="section-title-wrap">
                        <h2 class="section-title"><?php echo htmlspecialchars($block['title'] ?? 'Відгуки клієнтів'); ?></h2>
                        <?php if (!empty($block['subtitle'])): ?>
                            <p class="section-subtitle"><?php echo htmlspecialchars($block['subtitle']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="testimonials-grid">
                        <?php
                        $items = $block['items'] ?? [];
                        foreach ($items as $item):
                            ?>
                            <div class="testimonial-card">
                                <div>
                                    <div class="testimonial-stars">
                                        <?php echo renderStars($item['rating'] ?? 5); ?>
                                    </div>
                                    <blockquote class="testimonial-quote">
                                        "<?php echo htmlspecialchars($item['quote'] ?? ''); ?>"
                                    </blockquote>
                                </div>

                                <div class="testimonial-user">
                                    <?php if (!empty($item['avatar'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['avatar']); ?>" alt="Avatar"
                                            class="testimonial-avatar">
                                    <?php endif; ?>
                                    <div>
                                        <div class="testimonial-name"><?php echo htmlspecialchars($item['name'] ?? 'Клієнт'); ?>
                                        </div>
                                        <div class="testimonial-role"><?php echo htmlspecialchars($item['role'] ?? ''); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

        <?php elseif ($type === 'footer'): ?>

            <footer id="<?php echo $id; ?>" class="block-footer">
                <div class="container footer-wrap">
                    <div class="footer-logo">
                        <?php
                        $logoImg = !empty($block['logo_img']) ? $block['logo_img'] : 'assets/logo.svg';
                        ?>
                        <?php if (!empty($logoImg)): ?>
                            <?php
                            $logoPath = $logoImg;
                            if (strpos($logoPath, 'http') !== 0 && strpos($logoPath, '/') !== 0) {
                                $logoPath = $baseDir . '/' . $logoPath;
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo" class="logo-img" style="height: 30px;">
                        <?php else: ?>
                            <div><?php echo htmlspecialchars($block['logo_text'] ?? 'ApexCorp'); ?></div>
                        <?php endif; ?>
                    </div>

                    <ul class="footer-nav">
                        <li><a href="#">Головна</a></li>
                        <li><a href="#features">Переваги</a></li>
                        <li><a href="#pricing">Тарифи</a></li>
                        <li><a href="#testimonials">Відгуки</a></li>
                    </ul>

                    <div class="footer-copy">
                        <?php echo htmlspecialchars($block['copy_text'] ?? '© 2026 ApexCorp. Всі права захищені.'); ?>
                    </div>
                </div>
            </footer>
        <?php endif; ?>
    <?php endforeach; ?>

</body>

</html>