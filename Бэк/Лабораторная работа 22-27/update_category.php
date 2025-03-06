<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit();
}

require 'config.php';
$categoryId = $_POST['id'] ?? 0;
$name = $_POST['name'] ?? '';

// ��������� ������
if ($categoryId <= 0 || empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => '�������� ������']);
    exit();
}

try {
    // ��������� ������ ������ � ���� ������
    $stmt = $pdo->prepare("UPDATE categories SET name = :name WHERE id = :id");
    $stmt->execute([
        ':name' => $name,
        ':id' => $categoryId
    ]);

    // ���������� �������� �����
    http_response_code(200);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500); // ������ �������
    echo json_encode(['error' => '������ ���� ������: ' . $e->getMessage()]);
}