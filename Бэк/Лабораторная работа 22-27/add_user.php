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
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'user';
$address = $_POST['address'] ?? '';
$phone = $_POST['phone'] ?? '';


// Валидация данных
if (empty($name) || empty($email) || empty($password)) {
    http_response_code(400); // Неверный запрос
    echo json_encode(['error' => 'Неверные данные']);
    exit();
}

// Хешируем пароль
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Добавляем пользователя в базу данных
try {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, address, phone) VALUES (:name, :email, :password, :role, :address, :phone)");
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => $hashedPassword,
        ':role' => $role,
        ':address' => $address,
        ':phone' => $phone
    ]);

    http_response_code(200); // Успешно
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500); // Ошибка сервера
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}