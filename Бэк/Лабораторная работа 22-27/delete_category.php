<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); // Запрещено
    exit();
}

require 'config.php';

// Получаем ID пользователя из POST-запроса
$categoryId = $_POST['id'] ?? 0;

if ($categoryId <= 0) {
    http_response_code(400); // Неверный запрос
    echo json_encode(['error' => 'Неверный ID пользователя']);
    exit();
}

try {
    // Удаляем пользователя из базы данных
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
    $stmt->execute([':id' => $categoryId]);

    // Возвращаем успешный ответ
    http_response_code(200);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500); // Ошибка сервера
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}