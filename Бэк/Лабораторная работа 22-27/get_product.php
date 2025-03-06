<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); // Запрещено
    exit();
}

require 'config.php';

// Получаем ID товара из запроса
$productId = $_GET['id'] ?? 0;

if ($productId <= 0) {
    http_response_code(400); // Неверный запрос
    echo json_encode(['error' => 'Неверный ID товара']);
    exit();
}

try {
    // Получаем данные товара из базы данных
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404); // Не найдено
        echo json_encode(['error' => 'Товар не найден']);
        exit();
    }

    // Возвращаем данные в формате JSON
    http_response_code(200);
    echo json_encode($product);
} catch (PDOException $e) {
    http_response_code(500); // Ошибка сервера
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}