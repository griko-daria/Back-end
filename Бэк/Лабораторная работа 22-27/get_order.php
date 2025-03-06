<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Доступ запрещён.");
}

require 'config.php';

$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    die("ID заказа не указан.");
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = :order_id");
$stmt->execute(['order_id' => $orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Заказ не найден.");
}

echo json_encode($order);
?>