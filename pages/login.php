<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Будь ласка, введіть ім\'я користувача та пароль.';
    } else {
        $users = load_users();
        if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $users[$username]['role'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Невірне ім\'я користувача або пароль.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вхід — Platon LaunchPad</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg: #ffffff;
            --card-bg: #f8f9fa;
            --border: rgba(0, 0, 0, 0.08);
            --text: #000000;
            --text-muted: #555555;
            --primary: #ff4800;
            --primary-hover: #cc3a00;
            --accent: #ff4800;
            --danger: #ef4444;
        }

        body {
            background-color: #f9fafb;
            color: var(--text);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 1.5rem;
        }

        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .brand-logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.4rem;
        }

        .brand-name {
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text);
        }

        .card {
            background-color: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .card-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 0.5rem;
            text-align: center;
            color: var(--text);
        }

        .card-subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            box-sizing: border-box;
            background-color: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.12);
            border-radius: 10px;
            padding: 0.8rem 1rem;
            color: var(--text);
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 72, 0, 0.15);
        }

        .btn {
            width: 100%;
            background-color: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 0.9rem;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 1rem;
        }

        .btn:hover {
            background-color: var(--primary-hover);
        }

        .alert {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }

        .card-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .card-footer a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        .card-footer a:hover {
            text-decoration: underline;
        }

        .info-box {
            margin-top: 2rem;
            background-color: rgba(99, 102, 241, 0.05);
            border: 1px dashed rgba(99, 102, 241, 0.3);
            border-radius: 12px;
            padding: 1rem;
            font-size: 0.8rem;
        }

        .info-box-title {
            font-weight: 600;
            color: var(--accent);
            margin-bottom: 0.5rem;
        }

        .info-box-item {
            margin-bottom: 0.25rem;
            color: var(--text-muted);
        }

        .info-box-item b {
            color: var(--text);
        }
    </style>
</head>
<body>

    <div class="login-container">
        
        <div class="brand">
            <img src="assets/logo.svg" alt="Logo" style="height: 40px; width: auto; object-fit: contain;">
        </div>

        <div class="card">
            <h1 class="card-title">Вхід до системи</h1>
            <p class="card-subtitle">Увійдіть у свій кабінет конструктора сайтів</p>

            <?php if (!empty($error)): ?>
                <div class="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="username">Ім'я користувача</label>
                    <input type="text" name="username" id="username" class="form-input" placeholder="напр. admin" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Пароль</label>
                    <input type="password" name="password" id="password" class="form-input" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn">Увійти</button>
            </form>

            <div class="card-footer">
                Немає акаунту? <a href="register.php">Зареєструватися</a>
            </div>
        </div>

        <div class="info-box">
            <div class="info-box-title">Тестові акаунти:</div>
            <div class="info-box-item">Адміністратор: <b>admin</b> / Пароль: <b>admin</b> (Бачить усі сайти)</div>
            <div class="info-box-item">Користувач: <b>user</b> / Пароль: <b>user</b> (Максимум 1 сайт)</div>
        </div>

    </div>

</body>
</html>
