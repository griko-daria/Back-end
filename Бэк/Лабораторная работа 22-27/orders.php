<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require 'config.php';

$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? [];
if (!is_array($statusFilter)) {
    $statusFilter = [$statusFilter];
}
$columns = $_GET['columns'] ?? ['order_id', 'user_id', 'product_id', 'quantity', 'order_date', 'status'];
$sortColumn = $_GET['sort'] ?? 'order_date';
$sortOrder = $_GET['order'] ?? 'desc';

// Проверяем выбранные колонки
$validColumns = ['order_id', 'user_id', 'product_id', 'quantity', 'order_date', 'status'];
$columns = array_intersect($columns, $validColumns);

// SQL-запрос для получения заказов
$query = "SELECT o.*, u.name, p.name AS product_name 
          FROM orders o 
          JOIN users u ON o.user_id = u.user_id 
          JOIN products p ON o.product_id = p.id 
          WHERE (u.name LIKE :search OR p.name LIKE :search)";

// Массив параметров
$params = [':search' => '%' . $search . '%'];

// Фильтрация по статусу
if (!empty($statusFilter)) {
    $statusParams = [];
    foreach ($statusFilter as $index => $status) {
        $paramName = ':status_' . $index;
        $statusParams[] = $paramName;
        $params[$paramName] = $status;
    }
    $placeholders = implode(',', $statusParams);
    $query .= " AND o.status IN ($placeholders)";
}

// Сортировка
if (in_array($sortColumn, $validColumns)) {
    $query .= " ORDER BY $sortColumn " . ($sortOrder === 'desc' ? 'DESC' : 'ASC');
}

// Выполняем запрос
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Функция для выделения искомого текста
function highlight($text, $search) {
    if (!$search) {
        return htmlspecialchars($text);
    }
    return preg_replace('/(' . preg_quote($search, '/') . ')/iu', '<mark>$1</mark>', htmlspecialchars($text));
}

// Обработка экспорта в CSV
if (isset($_GET['export_csv'])) {
    // Устанавливаем заголовки для скачивания CSV-файла
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="orders.csv"');

    // Открываем поток вывода
    $output = fopen('php://output', 'w');

    // Добавляем BOM (Byte Order Mark) для корректного отображения кириллицы в Excel
    fwrite($output, "\xEF\xBB\xBF");

    // Заголовки столбцов
    fputcsv($output, array('ID заказа', 'Пользователь', 'Товар', 'Количество', 'Дата заказа', 'Статус'), ';');

    // Данные
    foreach ($orders as $item) {
        $row = array(
            $item['order_id'],
            $item['user_id'],
            $item['product_name'],
            $item['quantity'],
            $item['order_date'],
            $item['status']
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
    <title>Заказы</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="admin-style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-shopping-bag"></i> Заказы</h2>
        <a href="admin_panel.php" class="btn btn-primary mb-3"><i class="fas fa-home"></i> На главную</a>

        <!-- Форма поиска и фильтрации -->
        <div class="search-filter">
            <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" class="form-control" placeholder="Поиск по пользователю или товару" id="searchInput">
            </div>
            <div class="search-filter">
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" id="statusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-filter"></i> Статус
                </button>
                <ul class="dropdown-menu" aria-labelledby="statusDropdown">
                    <li>
                        <label class="dropdown-item">
                            <input type="checkbox" name="status[]" value="pending" <?= in_array('pending', $statusFilter) ? 'checked' : ''; ?>>
                            Ожидание
                        </label>
                    </li>
                    <li>
                        <label class="dropdown-item">
                            <input type="checkbox" name="status[]" value="completed" <?= in_array('completed', $statusFilter) ? 'checked' : ''; ?>>
                            Завершён
                        </label>
                    </li>
                    <li>
                        <label class="dropdown-item">
                            <input type="checkbox" name="status[]" value="cancelled" <?= in_array('cancelled', $statusFilter) ? 'checked' : ''; ?>>
                            Отменён
                        </label>
                    </li>
                </ul>
            </div>

            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" id="columnsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-columns"></i> Колонки
                </button>
                <ul class="dropdown-menu" aria-labelledby="columnsDropdown">
                    <?php foreach ($validColumns as $column): ?>
                        <li>
                            <label class="dropdown-item">
                                <input type="checkbox" name="columns[]" value="<?= $column; ?>" <?= in_array($column, $columns) ? 'checked' : ''; ?>>
                                <?= ucfirst($column); ?>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <button type="button" class="btn btn-primary" id="applyFilters"><i class="fas fa-check"></i> Применить</button>
            <a href="orders.php" class="btn btn-secondary"><i class="fas fa-sync"></i> Сброс</a>
        </div>

        <!-- Таблица заказов -->
        <table class="table" id="ordersTable">
            <thead>
                <tr>
                    <?php if (in_array('order_id', $columns)): ?>
                        <th><a href="?sort=order_id&order=<?= $sortColumn === 'order_id' && $sortOrder === 'asc' ? 'desc' : 'asc'; ?>">ID заказа</a></th>
                    <?php endif; ?>
                    <?php if (in_array('user_id', $columns)): ?>
                        <th><a href="?sort=user_id&order=<?= $sortColumn === 'user_id' && $sortOrder === 'asc' ? 'desc' : 'asc'; ?>">Пользователь</a></th>
                    <?php endif; ?>
                    <?php if (in_array('product_id', $columns)): ?>
                        <th><a href="?sort=product_id&order=<?= $sortColumn === 'product_id' && $sortOrder === 'asc' ? 'desc' : 'asc'; ?>">Товар</a></th>
                    <?php endif; ?>
                    <?php if (in_array('quantity', $columns)): ?>
                        <th><a href="?sort=quantity&order=<?= $sortColumn === 'quantity' && $sortOrder === 'asc' ? 'desc' : 'asc'; ?>">Количество</a></th>
                    <?php endif; ?>
                    <?php if (in_array('order_date', $columns)): ?>
                        <th><a href="?sort=order_date&order=<?= $sortColumn === 'order_date' && $sortOrder === 'asc' ? 'desc' : 'asc'; ?>">Дата заказа</a></th>
                    <?php endif; ?>
                    <?php if (in_array('status', $columns)): ?>
                        <th><a href="?sort=status&order=<?= $sortColumn === 'status' && $sortOrder === 'asc' ? 'desc' : 'asc'; ?>">Статус</a></th>
                    <?php endif; ?>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <?php if (in_array('order_id', $columns)): ?>
                            <td><?= highlight($order['order_id'], $search); ?></td>
                        <?php endif; ?>
                        <?php if (in_array('user_id', $columns)): ?>
                            <td><?= highlight($order['name'], $search); ?></td>
                        <?php endif; ?>
                        <?php if (in_array('product_id', $columns)): ?>
                            <td><?= highlight($order['product_name'], $search); ?></td>
                        <?php endif; ?>
                        <?php if (in_array('quantity', $columns)): ?>
                            <td><?= htmlspecialchars($order['quantity']); ?></td>
                        <?php endif; ?>
                        <?php if (in_array('order_date', $columns)): ?>
                            <td><?= htmlspecialchars($order['order_date']); ?></td>
                        <?php endif; ?>
                        <?php if (in_array('status', $columns)): ?>
                            <td><?= htmlspecialchars($order['status']); ?></td>
                        <?php endif; ?>
                        <td>
                            <div class="action-buttons">
                            <button class="btn btn-warning btn-sm edit-order" data-id="<?= $order['order_id']; ?>">
                                <i class="fas fa-edit"></i> Редактировать
                            </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Кнопки экспорта и печати -->
        <div class="export-print">
        <a href="orders.php?export_csv=1"><button class="btn btn-info" id="exportExcel"><i class="fas fa-file-csv"></i> Экспорт в CSV</a>
            <button class="btn btn-info" onclick="window.print()"><i class="fas fa-print"></i> Печать</button>
        </div>
    </div>

    <!-- Модальное окно для редактирования статуса заказа -->
    <div class="form-container">
        <div class="form-section" id="editOrderSection">
            <h3><i class="fas fa-edit"></i> Редактировать статус заказа</h3>
                <form id="editOrderForm">
                <input type="hidden" name="order_id" id="editOrderId">
                    <div class="mb-3">
                        <label for="editOrderStatus" class="form-label">Роль</label>
                        <select name="status" id="editOrderStatus" class="form-select" required>
                                <option value="pending">Ожидание</option>
                                <option value="completed">Завершён</option>
                                <option value="cancelled">Отменён</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" id="saveOrderStatus"><i class="fas fa-save"></i> Обновить</button>
                </form>
        </div>
    </div>



    <!-- Скрипты для работы с заказами -->
    <script>
        $(document).ready(function() {
            // Применение фильтров
            $('#applyFilters').click(function() {
                const search = $('#searchInput').val();
                const statuses = [];
                $('input[name="status[]"]:checked').each(function() {
                    statuses.push($(this).val());
                });
                const columns = [];
                $('input[name="columns[]"]:checked').each(function() {
                    columns.push($(this).val());
                });

                let url = 'orders.php?search=' + encodeURIComponent(search);
                if (statuses.length > 0) {
                    url += '&status[]=' + statuses.join('&status[]=');
                }
                if (columns.length > 0) {
                    url += '&columns[]=' + columns.join('&columns[]=');
                }

                window.location.href = url;
            });

            // Обработка поиска при нажатии Enter
            $('#searchInput').keypress(function(e) {
                if (e.which === 13) {
                    $('#applyFilters').click();
                }
            });

            // Открытие модального окна для редактирования статуса
            $('.edit-order').click(function() {
                const orderId = $(this).data('id');

                // Загрузка данных заказа
                $.ajax({
                    url: 'get_order.php',
                    method: 'GET',
                    data: { id: orderId },
                    success: function(response) {
                        const order = JSON.parse(response);
                        $('#editOrderId').val(order.order_id);
                        $('#editOrderStatus').val(order.status);
                        $('#editOrderModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        alert('Ошибка при загрузке данных заказа: ' + error);
                    }
                });
            });

            // Сохранение статуса заказа
            $('#saveOrderStatus').click(function() {
                const formData = {
                    order_id: $('#editOrderId').val(),
                    status: $('#editOrderStatus').val()
                };

                $.ajax({
                    url: 'update_order_status.php',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        alert('Статус заказа успешно обновлен');
                        location.reload(); // Перезагружаем страницу для обновления данных
                    },
                    error: function(xhr, status, error) {
                        alert('Ошибка при обновлении статуса заказа: ' + error);
                    }
                });
            });
        });

        $(document).ready(function() {
            // Прокрутка к форме редактирования категории
    $('.edit-order').click(function() {
        $('html, body').animate({
            scrollTop: $('#editOrderSection').offset().top
        }, 1000); // 1000 мс (1 секунда) для плавной прокрутки
    });
});
    </script>
</body>
</html>