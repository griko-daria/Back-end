<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Пользователь не авторизован']);
    exit();
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['deliveryTime'])) {
    echo json_encode(['success' => false, 'message' => 'Неверные данные']);
    exit();
}

$deliveryTime = $data['deliveryTime'];

try {
    // Начинаем транзакцию
    $pdo->beginTransaction();

    // Получаем товары из корзины пользователя
    $stmt = $pdo->prepare("SELECT product_id, quantity FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        echo json_encode(['success' => false, 'message' => 'Корзина пуста']);
        exit();
    }

    // Добавляем заказ в таблицу orders
    foreach ($cartItems as $item) {
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, product_id, quantity, order_date, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $userId,
            $item['product_id'],
            $item['quantity'],
            $deliveryTime
        ]);
    }

    // Очищаем корзину пользователя
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Завершаем транзакцию
    $pdo->commit();

    // Отправляем письмо на почту пользователя
    sendOrderConfirmationEmail($userId);

    echo json_encode(['success' => true, 'message' => 'Заказ успешно оформлен']);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}

// Функция для отправки письма
    function sendOrderConfirmationEmail($userId) {
        global $pdo;
    
        // Получаем данные пользователя
        $stmt = $pdo->prepare("SELECT email, name FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$user) {
            return;
        }
    
        $to = $user['email'];
        $subject = 'Ваш заказ успешно оформлен';
        $message = "
            <html>
            <head>
                <title>Ваш заказ успешно оформлен</title>
            </head>
            <body>
                <h1>Уважаемый(ая) {$user['name']},</h1>
                <p>Ваш заказ успешно оформлен. Мы свяжемся с вами для уточнения деталей.</p>
                <p>Спасибо за покупку!</p>
            </body>
            </html>
        ";
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= 'From: no-reply@example.com';
    
        // Отправляем письмо
        mail($to, $subject, $message, $headers);
    }

?>