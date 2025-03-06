<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); // Запрещено
    exit();
}

require 'config.php';

// Получаем данные из POST-запроса
$productId = $_POST['id'] ?? 0;
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$price = $_POST['price'] ?? 0;
$categoryId = $_POST['category_id'] ?? 0;
$image = $_POST['image_url'] ?? '';

// Проверка данных
if ($productId <= 0 || empty($name) || empty($description) || $price <= 0 || $categoryId <= 0) {
    http_response_code(400); // Неверный запрос
    echo json_encode(['error' => 'Неверные данные']);
    exit();
}

try {
    // Обновляем запись в базе данных
    $stmt = $pdo->prepare("UPDATE products SET name = :name, description = :description, price = :price, category_id = :category_id, image_url = :image_url WHERE id = :id");
    $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':price' => $price,
        ':category_id' => $categoryId,
        ':id' => $productId,
        ':image_url' => $image
    ]);

    // Возвращаем успешный ответ
    http_response_code(200);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500); // Ошибка сервера
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}