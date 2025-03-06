<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $productId = $data['product_id'];
    $userId = $_SESSION['user_id'];

    // Проверяем, есть ли товар в корзине
    $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id");
    $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
    $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cartItem) {
        // Если товар уже есть в корзине, увеличиваем количество
        $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = :user_id AND product_id = :product_id");
        $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
    } else {
        // Если товара нет, добавляем его в корзину
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, 1)");
        $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
    }

    echo json_encode(['success' => true]);
    exit;
}
?>
