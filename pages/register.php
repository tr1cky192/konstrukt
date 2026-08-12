<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $usernameClean = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);

    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'Будь ласка, заповніть усі поля.';
    } elseif ($username !== $usernameClean) {
        $error = 'Ім\'я користувача може містити тільки латинські літери, цифри, дефіси та підкреслення.';
    } elseif (strlen($username) < 3) {
        $error = 'Ім\'я користувача має містити щонайменше 3 символи.';
    } elseif (strlen($password) < 4) {
        $error = 'Пароль має містити щонайменше 4 символи.';
    } elseif ($password !== $confirm_password) {
        $error = 'Паролі не співпадають.';
    } else {
        $users = load_users();
        if (isset($users[$username])) {
            $error = 'Користувач з таким ім\'ям вже існує.';
        } else {
            $users[$username] = [
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'user',
                'max_sites' => 1
            ];
            if (save_users($users)) {
                $success = 'Реєстрація успішна! Тепер ви можете увійти.';
            } else {
                $error = 'Сталася помилка при збереженні користувача.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реєстрація — Platon LaunchPad</title>
    
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
            --success: #10b981;
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

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-left-color: var(--success);
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
    </style>
</head>
<body>

    <div class="login-container">
        
        <div class="brand">
            <img src="assets/logo.svg" alt="Logo" style="height: 40px; width: auto; object-fit: contain;">
        </div>

        <div class="card">
            <h1 class="card-title">Реєстрація</h1>
            <p class="card-subtitle">Створіть акаунт для конструювання сайтів</p>

            <?php if (!empty($error)): ?>
                <div class="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="username">Ім'я користувача</label>
                    <input type="text" name="username" id="username" class="form-input" placeholder="Тільки літери, цифри, -, _" required autofocus value="<?php echo htmlspecialchars($username ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Пароль</label>
                    <input type="password" name="password" id="password" class="form-input" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Підтвердьте пароль</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-input" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn">Зареєструватися</button>
            </form>

            <div class="card-footer">
                Вже маєте акаунт? <a href="login.php">Увійти</a>
            </div>
        </div>

    </div>

</body>
</html>
