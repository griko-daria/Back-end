<?php
session_start();
require 'config.php';

// Проверка авторизации пользователя
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT name, email, phone, address FROM users WHERE user_id = :user_id");
$stmt->execute(['user_id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Получаем товары из корзины
$stmt = $pdo->prepare("SELECT p.name, p.price, c.quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = :user_id");
$stmt->execute(['user_id' => $userId]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="checkout-container container">
        <h1>Оформление заказа</h1>
        
        <!-- Данные пользователя -->
        <div class="user-info">
            <h2>Ваши данные</h2>
            <p><strong>Имя:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Номер телефона:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
            <p><strong>Адрес:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
        </div>
        
        <!-- Выбор времени доставки -->
        <div class="delivery-time">
            <h2>Выберите время доставки</h2>
            <input type="datetime-local" id="delivery-time" class="filter-group">
        </div>
        
        <!-- Корзина товаров -->
        <div class="cart-summary">
            <h2>Ваши товары</h2>
            <div id="cart-items" class="cart-items-container">
                <?php
                $totalPrice = 0;
                foreach ($cartItems as $item) {
                    $itemTotal = $item['price'] * $item['quantity'];
                    $totalPrice += $itemTotal;
                    echo '<div class="cart-item">';
                    echo '<p><strong>' . htmlspecialchars($item['name']) . '</strong></p>';
                    echo '<p>' . $item['quantity'] . ' шт. — ' . $itemTotal . ' руб.</p>';
                    echo '</div>';
                }
                ?>
            </div>
            <h3 class="total-amount">Итого: <span id="total-price"><?php echo $totalPrice; ?></span> руб.</h3>
        </div>
        
        <!-- Кнопка оформить -->
        <button id="place-order-btn" class="btn">Заказать</button>
    </div>

    <script>
        document.getElementById('place-order-btn').addEventListener('click', () => {
            const deliveryTime = document.getElementById('delivery-time').value;

            if (!deliveryTime) {
                alert('Пожалуйста, выберите время доставки.');
                return;
            }

            fetch('place_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ deliveryTime })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Ваш заказ был принят!');
                    // Скачиваем файл
                    window.location.href = data.file;
                    window.location.href = 'profile.php';
                } else {
                    alert('Ошибка при оформлении заказа. Попробуйте снова.');
                }
            })
            .catch(error => console.error('Ошибка:', error));
        });
    </script>
</body>
</html>