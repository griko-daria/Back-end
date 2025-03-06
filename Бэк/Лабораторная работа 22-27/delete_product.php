<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); // Запрещено
    exit();
}

require 'config.php';

// Получаем ID товара из POST-запроса
$productId = $_POST['id'] ?? 0;

if ($productId <= 0) {
    http_response_code(400); // Неверный запрос
    echo json_encode(['error' => 'Неверный ID товара']);
    exit();
}

try {
    // Удаляем товар из базы данных
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
    $stmt->execute([':id' => $productId]);

    // Возвращаем успешный ответ
    http_response_code(200);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500); // Ошибка сервера
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}