<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); // Запрещено
    exit();
}

require 'config.php';

// Получаем данные из POST-запроса
$userId = $_POST['user_id'] ?? 0;
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';
$address = $_POST['address'] ?? '';
$phone = $_POST['phone'] ?? '';

// Валидация данных
if ($userId <= 0 || empty($name) || empty($email) || empty($role) || empty($address) || empty($phone)) {
    http_response_code(400); // Некорректный запрос
    echo json_encode(['error' => 'Некорректные данные']);
    exit();
}

try {
    // Хэшируем пароль, если он был предоставлен
    $hashedPassword = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

    // Подготавливаем SQL-запрос
    $sql = "UPDATE users SET name = :name, email = :email, role = :role, address = :address, phone = :phone";
    if ($hashedPassword) {
        $sql .= ", password = :password";
    }
    $sql .= " WHERE user_id = :user_id";

    $stmt = $pdo->prepare($sql);
    $params = [
        ':name' => $name,
        ':email' => $email,
        ':role' => $role,
        ':address' => $address,
        ':phone' => $phone,
        ':user_id' => $userId
    ];
    if ($hashedPassword) {
        $params[':password'] = $hashedPassword;
    }

    // Выполняем запрос
    $stmt->execute($params);

    // Возвращаем успешный ответ
    http_response_code(200);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500); // Ошибка сервера
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>