<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Доступ запрещён.']));
}

require 'config.php';

$orderId = $_POST['order_id'] ?? null;

if (!$orderId) {
    die(json_encode(['success' => false, 'message' => 'ID заказа не указан.']));
}

// Обновление статуса заказа на "cancelled"
$stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = :order_id AND user_id = :user_id");
$stmt->execute(['order_id' => $orderId, 'user_id' => $_SESSION['user_id']]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Заказ не найден или уже отменён.']);
}
?>