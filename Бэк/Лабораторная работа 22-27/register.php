<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

// Подключение к базе данных
require 'config.php';

$error = '';

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Проверка, что пароли совпадают
    if ($password !== $confirm_password) {
        $error = "Пароли не совпадают!";
    } else {
        // Хэшируем пароль
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Вставляем данные в базу
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hashed_password]);
            header("Location: login.php"); // Перенаправляем на страницу входа
            exit();
        } catch (PDOException $e) {
            $error = "Ошибка при регистрации: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #124059, #84a7de);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Arial', sans-serif;
        }
        .register-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .register-container h2 {
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
    </style>
</head>
<body>
    <div class="register-container">
        <h2><i class="fas fa-user-plus"></i> Регистрация</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <input type="text" class="form-control" name="name" placeholder="Имя" required>
            </div>
            <div class="mb-3">
                <input type="email" class="form-control" name="email" placeholder="Email" required>
            </div>
            <div class="mb-3">
                <input type="password" class="form-control" name="password" placeholder="Пароль" required>
            </div>
            <div class="mb-3">
                <input type="password" class="form-control" name="confirm_password" placeholder="Подтвердите пароль" required>
            </div>
            <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
        </form>
        <p class="mt-3">Уже есть аккаунт? <a href="login.php">Войдите</a></p>
    </div>
</body>
</html>