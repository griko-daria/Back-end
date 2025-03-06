<?php
session_start();
require 'config.php';

// Получаем количество товаров в корзине для текущего пользователя
function getCartItemCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

// Получаем содержимое корзины для текущего пользователя
function getCartItems($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT p.name, p.price, c.quantity, p.id 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Если пользователь авторизован, получаем количество товаров в корзине
$cartItemCount = 0;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'user') {
    $cartItemCount = getCartItemCount($_SESSION['user_id']);
}
?>










