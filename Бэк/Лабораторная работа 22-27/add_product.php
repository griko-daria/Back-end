<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); // Запрещено
    exit();
}

require 'config.php';

// Получаем данные из POST-запроса
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$price = $_POST['price'] ?? 0;
$categoryId = $_POST['category_id'] ?? 0;
$image = $_POST['image_url'] ?? '';

// Проверка данных
if (empty($name) || empty($description) || $price <= 0 || $categoryId <= 0) {
    http_response_code(400); // Неверный запрос
    echo json_encode(['error' => 'Неверные данные']);
    exit();
}

try {
    // Добавляем запись в базу данных
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category_id, image_url) VALUES (:name, :description, :price, :category_id, :image_url)");
    $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':price' => $price,
        ':category_id' => $categoryId,
        ':image_url' => $image
    ]);

    // Возвращаем успешный ответ
    http_response_code(200);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500); // Ошибка сервера
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}