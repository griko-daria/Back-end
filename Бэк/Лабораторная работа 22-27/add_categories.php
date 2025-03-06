<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); // Запрещено
    echo json_encode(['error' => 'Доступ запрещен']);
    exit();
}

require 'config.php';

// Получаем данные из POST-запроса
$name = $_POST['name'] ?? '';


// Валидация данных
if (empty($name)) {
    http_response_code(400); // Неверный запрос
    echo json_encode(['error' => 'Неверные данные']);
    exit();
}

// Добавляем пользователя в базу данных
try {
    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
    $stmt->execute([
        ':name' => $name,
    ]);

    http_response_code(200); // Успешно
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500); // Ошибка сервера
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}