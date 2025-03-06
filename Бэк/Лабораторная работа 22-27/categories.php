<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require 'config.php';

$search = $_GET['search'] ?? '';
$sortColumn = $_GET['sort'] ?? 'name';
$sortOrder = $_GET['order'] ?? 'asc';

// Проверяем валидные колонки для сортировки
$validColumns = ['id ,name'];
if (!in_array($sortColumn, $validColumns)) {
    $sortColumn = 'id';
}
if ($sortOrder !== 'desc') {
    $sortOrder = 'asc';
}

// SQL-запрос для получения категорий
$query = "SELECT * FROM categories WHERE name LIKE :search ORDER BY $sortColumn $sortOrder";
$params = [':search' => '%' . $search . '%'];

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$categories = $stmt->fetchAll();

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
    header('Content-Disposition: attachment; filename="categories.csv"');

    // Открываем поток вывода
    $output = fopen('php://output', 'w');

    // Добавляем BOM (Byte Order Mark) для корректного отображения кириллицы в Excel
    fwrite($output, "\xEF\xBB\xBF");

    // Заголовки столбцов
    fputcsv($output, array('ID категории', 'Название категории'), ';');

    // Данные
    foreach ($categories as $item) {
        $row = array(
            $item['id'],
            $item['name']
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
    <title>Категории</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="admin-style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Иконки FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-tags"></i> Категории</h2>
        <a href="admin_panel.php" class="btn btn-primary mb-3"><i class="fas fa-home"></i> На главную</a>

        <!-- Форма поиска -->
        <div class="search-filter">
            <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" class="form-control" placeholder="Поиск по названию" id="searchInput">
            <button type="button" class="btn btn-primary" id="applyFilters"><i class="fas fa-search"></i> Найти</button>
            
        </div>

        <!-- Кнопка добавления категории -->
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fas fa-plus"></i> Добавить категорию
        </button>

        <!-- Таблица категорий -->
        <table class="table" id="categoriesTable">
            <thead>
                <tr>
                    <th><a href="?sort=id&order=<?= $sortColumn === 'id' && $sortOrder === 'asc' ? 'desc' : 'asc'; ?>">ID</a></th>
                    <th><a href="?sort=name&order=<?= $sortColumn === 'name' && $sortOrder === 'asc' ? 'desc' : 'asc'; ?>">Название</a></th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= htmlspecialchars($category['id']); ?></td>
                        <td><?= highlight($category['name'], $search); ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm edit-category" data-id="<?= $category['id']; ?>">
                                <i class="fas fa-edit"></i> Редактировать
                            </button>
                            <button class="btn btn-danger btn-sm delete-category" data-id="<?= $category['id']; ?>"><i class="fas fa-trash"></i> Удалить</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
   

     <!-- Кнопки экспорта и печати -->
     <div class="export-print">
     <a href="categories.php?export_csv=1"><button class="btn btn-info" id="exportExcel"><i class="fas fa-file-csv"></i> Экспорт в CSV</a>
            <button class="btn btn-info" onclick="window.print()"><i class="fas fa-print"></i> Печать</button>
        </div>
    </div>

 <!-- Горизонтальный контейнер для добавления и редактирования -->
<div class="form-container">
    <!-- Секция добавления товара -->
    <div class="form-section " id="addCategorySection">
        <h3><i class="fas fa-plus"></i> Добавить категорию</h3>
        <form id="addCategoryForm">
            <div class="mb-3">
                <label for="categoriesName" class="form-label">Название</label>
                <input type="text" name="name" id="categoriesName" class="form-control" required>
            </div>
            <button type="button" class="btn btn-success" id="saveCategoryButton"><i class="fas fa-save"></i> Сохранить</button>
        </form>
    </div>

    <!-- Секция редактирования товара -->
    <div class="form-section" id="editCategorySection">
        <h3><i class="fas fa-edit"></i> Редактировать категорию</h3>
        <form id="editCategoryForm">
            <input type="hidden" name="id" id="editCategoryId">
            <div class="mb-3">
                <label for="editcategoriesName" class="form-label">Название</label>
                <input type="text" name="name" id="editcategoriesName" class="form-control" required>
            </div>
            <button type="button" class="btn btn-primary" id="updateCategoryButton"><i class="fas fa-save"></i> Обновить</button>
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
        $('#saveCategoryButton').click(function() {
            const formData = {
                name: $('#categoriesName').val(),
            };
            $.ajax({
                url: 'add_categories.php',
                method: 'POST',
                data: formData,
                success: function(response) {
                    $('#addCategoryModal').modal('hide');
                    $('#addCategoryForm')[0].reset();
                    location.reload();
                },
                error: function(xhr, status, error) {
                    alert('Ошибка при добавлении категории: ' + error);
                }
            });
        });

        // Редактирование товара
        $('.edit-category').click(function() {
            const categoryId = $(this).data('id');

            $.ajax({
                url: 'get_category.php',
                method: 'GET',
                data: { id: categoryId },
                success: function(response) {
                    const categories = JSON.parse(response);
                    $('#editCategoryId').val(categories.id);
                    $('#editcategoriesName').val(categories.name);
                    $('#editCategoryModal').modal('show');
                },
                error: function(xhr, status, error) {
                    alert('Ошибка при загрузке данных категории: ' + error);
                }
            });
        });

        // Обновление товара
        $('#updateCategoryButton').click(function() {
            const formData = {
                id: $('#editCategoryId').val(),
                name: $('#editcategoriesName').val(),
            };

            $.ajax({
                url: 'update_category.php',
                method: 'POST',
                data: formData,
                success: function(response) {
                    $('#editCategoryModal').modal('hide');
                    location.reload();
                },
                error: function(xhr, status, error) {
                    alert('Ошибка при обновлении Категории: ' + error);
                }
            });
        });

        // Удаление товара
        $('.delete-category').click(function() {
            const categoryId = $(this).data('id');

            if (confirm('Вы уверены, что хотите удалить этот товар?')) {
                $.ajax({
                    url: 'delete_category.php',
                    method: 'POST',
                    data: { id: categoryId },
                    success: function(response) {
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        alert('Ошибка при удалении товара: ' + error);
                    }
                });
            }
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
        let url = 'categories.php?search=' + encodeURIComponent(search);
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
    });

    $(document).ready(function() {
    // Прокрутка к форме добавления категории
    $('button[data-bs-target="#addCategoryModal"]').click(function() {
        $('html, body').animate({
            scrollTop: $('#addCategorySection').offset().top
        }, 1000); // 1000 мс (1 секунда) для плавной прокрутки
    });

    // Прокрутка к форме редактирования категории
    $('.edit-category').click(function() {
        $('html, body').animate({
            scrollTop: $('#editCategorySection').offset().top
        }, 1000); // 1000 мс (1 секунда) для плавной прокрутки
    });
});
    </script>
</body>
</html>
