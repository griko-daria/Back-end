<?php
session_start(); // Запуск сессии
require 'config.php'; // Подключение к базе данных

// Проверка подключения к базе данных
if (!isset($pdo)) {
    die("Ошибка: Подключение к базе данных не установлено.");
}

// Проверка авторизации пользователя
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Перенаправление на страницу авторизации, если пользователь не авторизован
    exit();
}

// Получение данных пользователя из базы данных
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // Обновление данных в базе данных
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?");
    $stmt->execute([$name, $email, $phone, $address, $user_id]);

    // Обновление данных в сессии
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;

    // Перенаправление на страницу профиля с сообщением об успешном обновлении
    header('Location: profile.php?success=1');
    exit();
}

// Выход из аккаунта
if (isset($_GET['logout'])) {
    session_destroy(); // Уничтожение сессии
    header('Location: index.php'); // Перенаправление на главную страницу
    exit();
}

// Получение заказов пользователя
$stmt = $pdo->prepare("
    SELECT o.order_id, o.order_date, o.status, p.name AS product_name, p.image_url 
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.user_id = ?
    ORDER BY o.order_date DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - Мелодик</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Дополнительные стили для страницы профиля */
        .profile-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .profile-container h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .profile-form .form-group {
            margin-bottom: 15px;
        }

        .profile-form label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
        }

        .profile-form input {
            width: 97%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 25px; /* Закруглённые углы */
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .profile-form input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5); /* Тень при фокусе */
        }

        .profile-form button {
            width: 100%;
            padding: 10px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 25px; /* Закруглённые углы */
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .profile-form button:hover {
            background: #2980b9;
        }

        .logout-btn {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #e74c3c;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .logout-btn:hover {
            color: #c0392b;
        }

        .success-message {
            text-align: center;
            color: #27ae60;
            margin-bottom: 20px;
            animation: slideDown 0.5s ease-in-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Стиль для текста "Нет данных" */
        .no-data {
            color: #888;
            font-style: italic;
        }
    </style>
</head>
<body>
   <!-- Шапка -->
<header>
    <div class="container">
        <div class="logo">
            <a href="index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center;">
                <img src="https://png.pngtree.com/png-vector/20190904/ourlarge/pngtree-violin-piano-key-musical-instrument-logo-png-image_1722686.jpg" alt="Логотип Мелодик">
                <div>
                    <h1>Мелодик</h1>
                    <p>Магазин музыкальных товаров</p>
                </div>
            </a>
        </div>
        <div class="auth">
            <a href="profile.php" class="btn">Личный кабинет</a>
            <a href="?logout" class="btn">Выйти</a>
        </div>
    </div>
</header>
    <!-- Основной контент -->
    <main>
        <div class="profile-container">
            <h2>Личный кабинет</h2>
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">Данные успешно обновлены!</div>
            <?php endif; ?>
            <form class="profile-form" method="POST">
                <div class="form-group">
                    <label for="name">Имя:</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Почта:</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Номер телефона:</label>
                    <input type="text" id="phone" name="phone" value="<?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Нет данных'; ?>">
                </div>
                <div class="form-group">
                    <label for="address">Адрес:</label>
                    <input type="text" id="address" name="address" value="<?= !empty($user['address']) ? htmlspecialchars($user['address']) : 'Нет данных'; ?>">
                </div>
                <button type="submit">Сохранить изменения</button>
            </form>
            <a href="?logout" class="logout-btn">Выйти из аккаунта</a>
        </div>

        <!-- Контейнер для заказов -->
        <div class="orders-container">
    <h2>Ваши заказы</h2>
    <?php if (empty($orders)): ?>
        <p class="no-data">У вас пока нет заказов.</p>
    <?php else: ?>
        <div class="orders-grid">
            <?php foreach ($orders as $order): ?>
                <div class="order-card <?= $order['status'] === 'cancelled' ? 'cancelled' : '' ?>">
                    <div class="order-info">
                        <p><strong>Номер заказа:</strong> <?= htmlspecialchars($order['order_id']) ?></p>
                        <p><strong>Дата заказа:</strong> <?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></p>
                        <p><strong>Статус:</strong> <span class="status-<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></p>
                    </div>
                    <div class="order-product">
                        <img src="<?= htmlspecialchars($order['image_url']) ?>" alt="<?= htmlspecialchars($order['product_name']) ?>">
                        <p><?= htmlspecialchars($order['product_name']) ?></p>
                    </div>
                    <!-- Кнопка отмены заказа -->
                    <?php if ($order['status'] !== 'cancelled'): ?>
                        <button class="cancel-order-btn" data-order-id="<?= $order['order_id'] ?>">
                            <i class="fas fa-times"></i> <!-- Иконка крестика -->
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
    </main>

    <!-- Подвал -->
    <footer>
        <div class="container">
            <p>&copy; 2025 Мелодик. Все права защищены.</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Обработка отмены заказа
            $('.cancel-order-btn').click(function() {
                const orderId = $(this).data('order-id');

                if (!orderId) {
                    alert('Ошибка: ID заказа не найден.');
                    return;
                }

                if (confirm('Вы уверены, что хотите отменить этот заказ?')) {
                    $.ajax({
                        url: 'cancel_order.php',
                        method: 'POST',
                        data: { order_id: orderId },
                        success: function(response) {
                            const data = JSON.parse(response);
                            if (data.success) {
                                alert('Заказ успешно отменён.');
                                location.reload(); // Перезагружаем страницу для обновления данных
                            } else {
                                alert('Ошибка при отмене заказа: ' + data.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            alert('Произошла ошибка при отмене заказа: ' + error);
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>