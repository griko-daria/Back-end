<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); // ���������
    exit();
}

require 'config.php';

// �������� ID ������ �� �������
$userId = $_GET['user_id'] ?? 0;

if ($userId <= 0) {
    http_response_code(400); // �������� ������
    echo json_encode(['error' => '�������� ID ������']);
    exit();
}

try {
    // �������� ������ ������ �� ���� ������
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    $users = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$users) {
        http_response_code(404); // ����� �� ������
        echo json_encode(['error' => '����� �� ������']);
        exit();
    }

    // ���������� ������ � ������� JSON
    http_response_code(200);
    echo json_encode($users);
} catch (PDOException $e) {
    http_response_code(500); // ������ �������
    echo json_encode(['error' => '������ ���� ������: ' . $e->getMessage()]);
}