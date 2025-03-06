<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); // ���������
    exit();
}

require 'config.php';

// �������� ID ������ �� �������
$categoryID = $_GET['id'] ?? 0;

if ($categoryID <= 0) {
    http_response_code(400); // �������� ������
    echo json_encode(['error' => '�������� ID ������']);
    exit();
}

try {
    // �������� ������ ������ �� ���� ������
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
    $stmt->execute([':id' => $categoryID]);
    $categories = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$categories) {
        http_response_code(404); // ����� �� ������
        echo json_encode(['error' => '����� �� ������']);
        exit();
    }

    // ���������� ������ � ������� JSON
    http_response_code(200);
    echo json_encode($categories);
} catch (PDOException $e) {
    http_response_code(500); // ������ �������
    echo json_encode(['error' => '������ ���� ������: ' . $e->getMessage()]);
}