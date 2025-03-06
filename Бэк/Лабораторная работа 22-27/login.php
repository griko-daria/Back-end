<?php
session_start();

// Подключение к базе данных с указанием кодировки
$host = 'localhost';
$dbname = 'shop_db';
$username = 'root';
$password = 'admin';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// Обработка формы авторизации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Поиск пользователя в базе данных
    $stmt = $pdo->prepare("SELECT user_id, name, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Устанавливаем данные пользователя в сессию
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        // Перенаправляем в зависимости от роли
        if ($user['role'] === 'admin') {
            header("Location: admin_panel.php");
            exit();
        } else {
            header("Location: index.php");
            exit();
        }
    } else {
        // Пароль неверный
        $error = "Неверный email или пароль!";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #124059, #84a7de);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Arial', sans-serif;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .login-container h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .form-control {
            border-radius: 25px;
            padding: 10px 20px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }
        .form-control:focus {
            border-color:rgb(87, 127, 154);
            box-shadow: 0 0 5prgb(35, 110, 160)(106, 17, 203, 0.5);
        }
        .btn-primary {
            background: #2980b9;
            border: none;
            border-radius: 25px;
            padding: 10px 20px;
            width: 100%;
            font-size: 16px;
            transition: background 0.3s ease;
        }
        .btn-primary:hover {
            background:rgb(87, 127, 154);
        }
        .alert {
            border-radius: 25px;
            margin-bottom: 20px;
        }
        .icon {
            margin-right: 10px;
        }
    </style>
</head>
<body>
<div class="login-container">
    <h2><i class="fas fa-lock icon"></i>Авторизация</h2>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="mb-3">
            <input type="email" class="form-control" id="email" name="email" placeholder="Введите ваш email" required>
        </div>
        <div class="mb-3">
            <input type="password" class="form-control" id="password" name="password" placeholder="Введите ваш пароль" required>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-sign-in-alt icon"></i>Войти
        </button>
        <div class="mt-3">
            <p>Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a></p>
        </div>
    </form>
</div>
</body>
</html>