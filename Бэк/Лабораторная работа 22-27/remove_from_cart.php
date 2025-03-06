<?php
session_start();

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit();
}

require 'config.php';

// Получаем данные из запроса
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id']) || !isset($data['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Неверные данные']);
    exit();
}

$productId = $data['id'];
$quantityToRemove = (int)$data['quantity'];
$userId = $_SESSION['user_id'];

try {
    // Получаем текущее количество товара в корзине
    $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE product_id = :product_id AND user_id = :user_id");
    $stmt->execute([
        ':product_id' => $productId,
        ':user_id' => $userId
    ]);
    $currentItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentItem) {
        echo json_encode(['success' => false, 'message' => 'Товар не найден в корзине']);
        exit();
    }

    $currentQuantity = (int)$currentItem['quantity'];

    // Если количество для удаления больше или равно текущему количеству, удаляем запись полностью
    if ($quantityToRemove >= $currentQuantity) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE product_id = :product_id AND user_id = :user_id");
        $stmt->execute([
            ':product_id' => $productId,
            ':user_id' => $userId
        ]);
    } else {
        // Иначе уменьшаем количество
        $newQuantity = $currentQuantity - $quantityToRemove;
        $stmt = $pdo->prepare("UPDATE cart SET quantity = :quantity WHERE product_id = :product_id AND user_id = :user_id");
        $stmt->execute([
            ':quantity' => $newQuantity,
            ':product_id' => $productId,
            ':user_id' => $userId
        ]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}