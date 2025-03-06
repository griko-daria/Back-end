<?php
require 'config.php';

// Параметры сортировки
$orderBy = 'cart.user_id'; // Сортировка по умолчанию
$orderDir = 'ASC'; // Направление сортировки по умолчанию

if (isset($_GET['sort_by'])) {
    $validSortFields = ['user_id', 'product_id'];
    $sortField = $_GET['sort_by'];
    if (in_array($sortField, $validSortFields)) {
        $orderBy = 'cart.' . $sortField;
    }
}

if (isset($_GET['order']) && in_array($_GET['order'], ['ASC', 'DESC'])) {
    $orderDir = $_GET['order'];
}

// Логика фильтрации по ID пользователя
$filterUserId = '';
if (isset($_GET['user_id']) && $_GET['user_id'] !== '') {
    $filterUserId = $_GET['user_id'];
    $sql = "SELECT cart.user_id, users.name AS user_name, products.name AS product_name, products.price AS product_price, cart.product_id, cart.quantity, cart.added_at 
            FROM cart 
            JOIN users ON cart.user_id = users.user_id 
            JOIN products ON cart.product_id = products.id 
            WHERE cart.user_id = :user_id 
            ORDER BY $orderBy $orderDir";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $filterUserId]);
} else {
    // Запрос для получения всех данных из таблицы cart
    $sql = "SELECT cart.user_id, users.name AS user_name, products.name AS product_name, products.price AS product_price, cart.product_id, cart.quantity, cart.added_at 
            FROM cart 
            JOIN users ON cart.user_id = users.user_id 
            JOIN products ON cart.product_id = products.id 
            ORDER BY $orderBy $orderDir";
    $stmt = $pdo->query($sql);
}

$cartItems = $stmt->fetchAll();

// Обработка экспорта в CSV
if (isset($_GET['export_csv'])) {
    // Устанавливаем заголовки для скачивания CSV-файла
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cart.csv"');

    // Открываем поток вывода
    $output = fopen('php://output', 'w');

    // Добавляем BOM (Byte Order Mark) для корректного отображения кириллицы в Excel
    fwrite($output, "\xEF\xBB\xBF");

    // Заголовки столбцов
    fputcsv($output, array('ID пользователя', 'Имя пользователя', 'Товар', 'Цена', 'ID товара', 'Количество', 'Дата добавления'), ';');

    // Данные
    foreach ($cartItems as $item) {
        $row = array(
            $item['user_id'],
            $item['user_name'],
            $item['product_name'],
            $item['product_price'],
            $item['product_id'],
            $item['quantity'],
            $item['added_at']
        );

        // Записываем строку в CSV
        fputcsv($output, $row, ';');
    }

    // Закрываем поток вывода
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="admin-style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-shopping-cart"></i> Корзина</h2>
        <a href="admin_panel.php" class="btn btn-primary mb-3"><i class="fas fa-home"></i> На главную</a>

        <!-- Форма фильтрации -->
        <div class="search-filter">
            <form method="GET" class="d-flex align-items-center mb-3">
                <input 
                    type="number" 
                    name="user_id" 
                    class="form-control me-2" 
                    placeholder="Введите ID пользователя" 
                    value="<?= htmlspecialchars($filterUserId); ?>"
                >
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Найти</button>
                <a href="cart.php" class="btn btn-secondary"><i class="fas fa-sync"></i> Сбросить</a>
            </form>
        </div>

        <!-- Таблица корзины -->
        <table class="table">
            <thead class="table-light">
                <tr>
                    <th>ID пользователя</th>
                    <th>Имя пользователя</th>
                    <th>Товар</th>
                    <th>Цена</th>
                    <th>ID товара</th>
                    <th>Количество</th>
                    <th>Дата добавления</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($cartItems) > 0): ?>
                    <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['user_id']); ?></td>
                        <td><?= htmlspecialchars($item['user_name']); ?></td>
                        <td><?= htmlspecialchars($item['product_name']); ?></td>
                        <td><?= htmlspecialchars(number_format($item['product_price'], 2)); ?> руб</td>
                        <td><?= htmlspecialchars($item['product_id']); ?></td>
                        <td><?= htmlspecialchars($item['quantity']); ?></td>
                        <td><?= htmlspecialchars($item['added_at']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">Данные не найдены</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Кнопки экспорта и печати -->
        <div class="export-print">
            <a href="cart.php?export_csv=1"><button class="btn btn-info" id="exportExcel"><i class="fas fa-file-csv"></i> Экспорт в CSV</a>
            <button class="btn btn-info" onclick="window.print()"><i class="fas fa-print"></i> Печать</button>
        </div>
    </div>

    <!-- Скрипты -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
