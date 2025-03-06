<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa; /* Светлый фон */
            color: #333;
            font-family: 'Arial', sans-serif;
        }
        .welcome-section {
            height: 93vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: #42a5f5; /* Голубой фон */
            color: white;
            text-align: center;
            padding: 20px;
        }
        .welcome-section h1 {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        .welcome-section .btn {
            margin: 10px;
            border-radius: 25px;
            padding: 10px 20px;
            font-size: 1.2rem;
        }
        .table-section {
            padding: 20px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 1200px;
        }
        .table-section h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #42a5f5;
        }
        .search-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: center;
        }
        .search-filter input, .search-filter select {
            border-radius: 25px;
            padding: 10px 20px;
            border: 1px solid #ddd;
        }
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .table th, .table td {
            padding: 15px;
            text-align: center;
            vertical-align: middle;
        }
        .table th {
            background-color: #42a5f5;
            color: white;
            cursor: pointer;
        }
        .table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .table tr:hover {
            background-color: #e9ecef;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .btn-primary {
            background-color: #42a5f5;
            border: none;
            border-radius: 25px;
            padding: 10px 20px;
        }
        .btn-primary:hover {
            background-color: #1e88e5;
        }
        .btn-danger {
            border-radius: 25px;
            padding: 10px 20px;
        }
        .btn-warning {
            border-radius: 25px;
            padding: 10px 20px;
        }
        .scroll-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: none;
            background-color: #42a5f5;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 24px;
            line-height: 50px;
            text-align: center;
            cursor: pointer;
        }
        .scroll-to-top:hover {
            background-color: #1e88e5;
        }
    </style>

</head>
<body>
     <!-- Приветствие и кнопки -->
  <div class="welcome-section">
        <h1>Привет, Админ, хорошей работы!</h1>
        <div class ='.welcome-section .btn'>
        <a href="users.php" ><button class="btn btn-light"><i class="fas fa-users"></i> Пользователи</button></a>
        <a href="products.php"><button class="btn btn-light"><i class="fas fa-box"></i> Товары</button></a>
        <a href="categories.php"><button class="btn btn-light"><i class="fas fa-list"></i> Категории</button></a>
        <a href="cart.php" ><button class="btn btn-light"><i class=" fas fa-shopping-cart"></i> Корзина</button></a>
        <a href="orders.php" ><button class="btn btn-light"><i class=" fas fa-shopping-bag"></i> Заказы</button></a>
        <a href="index.php" ><button class="btn btn-light"><i class="fas fa-music"></i> Сайт</button></a>
        </div>
    </div>
</body>
</html>

