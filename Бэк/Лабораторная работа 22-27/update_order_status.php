<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Доступ запрещён.");
}

require 'config.php';

$orderId = $_POST['order_id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$orderId || !$status) {
    die("Недостаточно данных для обновления.");
}

$stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE order_id = :order_id");
$stmt->execute(['status' => $status, 'order_id' => $orderId]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении статуса.']);
}
?>