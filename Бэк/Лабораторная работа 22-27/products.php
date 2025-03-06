<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require 'config.php';



$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? [];
if (!is_array($categoryFilter)) {
    $categoryFilter = [$categoryFilter];
}
$columns = $_GET['columns'] ?? ['name', 'description', 'price', 'category', 'created_at', 'image_url', 'sold'];
$sortColumn = $_GET['sort'] ?? 'name';
$sortOrder = $_GET['order'] ?? 'asc';

// Проверяем выбранные колонки
$validColumns = ['name', 'description', 'price', 'category', 'created_at', 'image_url', 'sold'];
$columns = array_intersect($columns, $validColumns);

// SQL-запрос для получения товаров
$query = "SELECT p.*, c.name AS category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE (p.name LIKE :search OR p.description LIKE :search)";

// Массив параметров
$params = [':search' => '%' . $search . '%'];

// Фильтрация по категориям
if (!empty($categoryFilter)) {
    // Создаём именованные параметры для каждой категории
    $categoryParams = [];
    foreach ($categoryFilter as $index => $categoryId) {
        $paramName = ':category_' . $index;
        $categoryParams[] = $paramName;
        $params[$paramName] = $categoryId;
    }
    $placeholders = implode(',', $categoryParams);
    $query .= " AND p.category_id IN ($placeholders)";
}

// Сортировка
if (in_array($sortColumn, $validColumns)) {
    $query .= " ORDER BY $sortColumn " . ($sortOrder === 'desc' ? 'DESC' : 'ASC');
}

// Выполняем запрос
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Получаем список категорий
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

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
    header('Content-Disposition: attachment; filename="product.csv"');

    // Открываем поток вывода
    $output = fopen('php://output', 'w');

    // Добавляем BOM (Byte Order Mark) для корректного отображения кириллицы в Excel
    fwrite($output, "\xEF\xBB\xBF");

    // Заголовки столбцов
    fputcsv($output, array('ID товара', 'Название товара', 'Описание товара', 'Цена', 'Категория', 'Дата добавления', 'Проданных товаров', 'URL изображения'), ';');

    // Данные
    foreach ($products as $item) {
        $row = array(
            $item['id'],
            $item['name'],
            $item['description'],
            $item['price'],
            $item['category_id'],
            $item['created_at'],
            $item['sold'],
            $item['image_url'],
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
    <title>Товары</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="admin-style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <!-- Иконки FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Подключение Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Подключение Bootstrap JS (включая Popper.js) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-box-open"></i> Товары</h2>
        <a href="admin_panel.php" class="btn btn-primary mb-3"><i class="fas fa-home"></i> На главную</a>

        <!-- Форма поиска и фильтрации -->
        <div class="search-filter">
    <!-- Поле поиска -->
    <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" class="form-control" placeholder="Поиск по названию или описанию" id="searchInput">
</div>
<div class="search-filter">
    <!-- Фильтрация по категориям -->
    <div class="dropdown">
        <button class="btn btn-primary dropdown-toggle" type="button" id="categoryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-filter"></i> Категории
        </button>
        <ul class="dropdown-menu" aria-labelledby="categoryDropdown">
            <?php foreach ($categories as $category): ?>
                <li>
                    <label class="dropdown-item">
                        <input type="checkbox" name="category[]" value="<?= $category['id']; ?>" <?= in_array($category['id'], $categoryFilter) ? 'checked' : ''; ?>>
                        <?= htmlspecialchars($category['name']); ?>
                    </label>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Выбор колонок -->
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

    <!-- Кнопки -->
    <button type="button" class="btn btn-primary" id="applyFilters"><i class="fas fa-check"></i> Применить</button>
    <a href="products.php" class="btn btn-secondary"><i class="fas fa-sync"></i> Сброс</a>
</div>

        <!-- Кнопка добавления товара -->
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addProductSection">
            <i class="fas fa-plus"></i> Добавить товар
        </button>

        <!-- Таблица товаров -->
        <table class="table" id="productsTable">
        <thead>
    <tr>
        <?php if (in_array('name', $columns)): ?>
            <th><a href="?sort=name&order=<?= $sortColumn === 'name' && $sortOrder === 'asc' ? 'desc' : 'asc'; ?>">Название</a></th>
        <?php endif; ?>
        <?php if (in_array('description', $columns)): ?>
            <th>Описание</th>
        <?php endif; ?>
        <?php if (in_array('price', $columns)): ?>
            <th><a href="?sort=price&order=<?= $sortColumn === 'price' && $sortOrder === 'asc' ? 'desc' : 'asc'; ?>">Цена</a></th>
        <?php endif; ?>
        <?php if (in_array('category', $columns)): ?>
            <th>Категория</th>
        <?php endif; ?>
        <?php if (in_array('created_at', $columns)): ?>
            <th><a href="?sort=created_at&order=<?= $sortColumn === 'created_at' && $sortOrder === 'asc' ? 'desc' : 'asc'; ?>">Дата добавления</a></th>
        <?php endif; ?>
        <th>Проданные</th> <!-- Новая колонка -->
        <th>Изображение</th>
        <th>Действия</th>
    </tr>
</thead>
<tbody>
    <?php foreach ($products as $product): ?>
        <tr>
            <?php if (in_array('name', $columns)): ?>
                <td><?= highlight($product['name'], $search); ?></td>
            <?php endif; ?>
            <?php if (in_array('description', $columns)): ?>
                <td><?= highlight($product['description'], $search); ?></td>
            <?php endif; ?>
            <?php if (in_array('price', $columns)): ?>
                <td><?= htmlspecialchars($product['price']); ?> руб.</td>
            <?php endif; ?>
            <?php if (in_array('category', $columns)): ?>
                <td><?= htmlspecialchars($product['category_name']); ?></td>
            <?php endif; ?>
            <?php if (in_array('created_at', $columns)): ?>
                <td><?= htmlspecialchars($product['created_at']); ?></td>
            <?php endif; ?>
            <td><?= htmlspecialchars($product['sold']); ?></td> <!-- Количество проданных -->
            <td>
                <?php if (!empty($product['image_url'])): ?>
                    <img src="<?= htmlspecialchars($product['image_url']); ?>" alt="Изображение товара" style="max-width: 100px; max-height: 100px;">
                <?php else: ?>
                    Нет изображения
                <?php endif; ?>
            </td>
            <td>
                <div class="action-buttons">
                            <button class="btn btn-warning btn-sm edit-product" data-id="<?= $product['id']; ?>">
                                <i class="fas fa-edit"></i> Редактировать
                            </button>
                    <button class="btn btn-danger btn-sm delete-product" data-id="<?= $product['id']; ?>"><i class="fas fa-trash"></i> Удалить</button>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>
        </table>

        <!-- Кнопки экспорта и печати -->
        <div class="export-print">
        <a href="products.php?export_csv=1"><button class="btn btn-info" id="exportExcel"><i class="fas fa-file-csv"></i> Экспорт в CSV</a>
            <button class="btn btn-info" onclick="window.print()"><i class="fas fa-print"></i> Печать</button>
        </div>
    </div>

 <!-- Горизонтальный контейнер для добавления и редактирования -->
<div class="form-container">
    <!-- Секция добавления товара -->
    <div class="form-section" id="addProductSection">
        <h3><i class="fas fa-plus"></i> Добавить товар</h3>
        <form id="addProductForm">
            <div class="mb-3">
                <label for="productName" class="form-label">Название</label>
                <input type="text" name="name" id="productName" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="productDescription" class="form-label">Описание</label>
                <textarea name="description" id="productDescription" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label for="productPrice" class="form-label">Цена</label>
                <input type="number" name="price" id="productPrice" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="productCategory" class="form-label">Категория</label>
                <select name="category_id" id="productCategory" class="form-select" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id']; ?>"><?= htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
    <label for="productImageUrl" class="form-label">URL изображения</label>
    <input type="text" name="image_url" id="productImageUrl" class="form-control">
</div>
            <button type="button" class="btn btn-success" id="saveProductButton"><i class="fas fa-save"></i> Сохранить</button>
        </form>
    </div>

    <!-- Секция редактирования товара -->
    <div class="form-section" id="editProductSection">
        <h3><i class="fas fa-edit"></i> Редактировать товар</h3>
        <form id="editProductForm">
            <input type="hidden" name="id" id="editProductId">
            <div class="mb-3">
                <label for="editProductName" class="form-label">Название</label>
                <input type="text" name="name" id="editProductName" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="editProductDescription" class="form-label">Описание</label>
                <textarea name="description" id="editProductDescription" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label for="editProductPrice" class="form-label">Цена</label>
                <input type="number" name="price" id="editProductPrice" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="editProductCategory" class="form-label">Категория</label>
                <select name="category_id" id="editProductCategory" class="form-select" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id']; ?>"><?= htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
    <label for="editProductImageUrl" class="form-label">URL изображения</label>
    <input type="text" name="image_url" id="editProductImageUrl" class="form-control">
</div>
            <button type="button" class="btn btn-primary" id="updateProductButton"><i class="fas fa-save"></i> Обновить</button>
        </form>
    </div>
</div>
    <!-- Кнопка "Наверх" -->
    <button class="scroll-to-top" id="scrollToTop"><i class="fas fa-arrow-up"></i></button>


    <!-- Скрипты для работы с товарами -->
    <script>

      // Показываем кнопку "Наверх" при прокрутке
        window.onscroll = function() {
            if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
                document.getElementById("scrollToTop").style.display = "block";
            } else {
                document.getElementById("scrollToTop").style.display = "none";
            }
        };

        // Прокрутка вверх при нажатии на кнопку
        document.getElementById("scrollToTop").onclick = function() {
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
        };

        $(document).ready(function() {
    // Добавление товара
    $('#saveProductButton').click(function() {
        const formData = {
            name: $('#productName').val(),
            description: $('#productDescription').val(),
            price: $('#productPrice').val(),
            category_id: $('#productCategory').val(),
            image_url: $('#productImageUrl').val()
        };

        $.ajax({
            url: 'add_product.php',
            method: 'POST',
            data: formData,
            success: function(response) {
                alert('Товар успешно добавлен');
                location.reload();
            },
            error: function(xhr, status, error) {
                alert('Ошибка при добавлении товара: ' + error);
            }
        });
    });

    // Редактирование товара
    $('.edit-product').click(function() {
        const productId = $(this).data('id');

        $.ajax({
            url: 'get_product.php',
            method: 'GET',
            data: { id: productId },
            success: function(response) {
                const product = JSON.parse(response);
                $('#editProductId').val(product.id);
                $('#editProductName').val(product.name);
                $('#editProductDescription').val(product.description);
                $('#editProductPrice').val(product.price);
                $('#editProductCategory').val(product.category_id);
                $('#editProductImageUrl').val(product.image_url);
                $('#editProductModal').modal('show');
            },
            error: function(xhr, status, error) {
                alert('Ошибка при загрузке данных товара: ' + error);
            }
        });
    });

    // Обновление товара
    $('#updateProductButton').click(function() {
        const formData = {
            id: $('#editProductId').val(),
            name: $('#editProductName').val(),
            description: $('#editProductDescription').val(),
            price: $('#editProductPrice').val(),
            category_id: $('#editProductCategory').val(),
            image_url: $('#editProductImageUrl').val()
        };

        $.ajax({
            url: 'update_product.php',
            method: 'POST',
            data: formData,
            success: function(response) {
                alert('Товар успешно обновлен');
                location.reload();
            },
            error: function(xhr, status, error) {
                alert('Ошибка при обновлении товара: ' + error);
            }
        });
    });

    // Удаление товара
    $('.delete-product').click(function() {
        const productId = $(this).data('id');

        if (confirm('Вы уверены, что хотите удалить этот товар?')) {
            $.ajax({
                url: 'delete_product.php',
                method: 'POST',
                data: { id: productId },
                success: function(response) {
                    alert('Товар успешно удален');
                    location.reload();
                },
                error: function(xhr, status, error) {
                    alert('Ошибка при удалении товара: ' + error);
                }
            });
        }
    });
});
        
        $(document).ready(function() {
    // Применение фильтров
    $('#applyFilters').click(function() {
        const search = $('#searchInput').val();
        const categories = [];
        $('input[name="category[]"]:checked').each(function() {
            categories.push($(this).val());
        });
        const columns = [];
        $('input[name="columns[]"]:checked').each(function() {
            columns.push($(this).val());
        });

        // Формируем URL с параметрами
        let url = 'products.php?search=' + encodeURIComponent(search);
        if (categories.length > 0) {
            url += '&category[]=' + categories.join('&category[]=');
        }
        if (columns.length > 0) {
            url += '&columns[]=' + columns.join('&columns[]=');
        }   

        // Переходим по новому URL
        window.location.href = url;
    });

    // Обработка поиска при нажатии Enter
    $('#searchInput').keypress(function(e) {
        if (e.which === 13) { // Код клавиши Enter
            $('#applyFilters').click();
        }
    });
});

$(document).ready(function() {
    // Прокрутка к форме добавления категории
    $('button[data-bs-target="#addProductSection"]').click(function() {
        $('html, body').animate({
            scrollTop: $('#addProductSection').offset().top
        }, 1000); // 1000 мс (1 секунда) для плавной прокрутки
    });

    // Прокрутка к форме редактирования категории
    $('.edit-product').click(function() {
        $('html, body').animate({
            scrollTop: $('#editProductSection').offset().top
        }, 1000); // 1000 мс (1 секунда) для плавной прокрутки
    });
});

    </script>
</body>
</html>