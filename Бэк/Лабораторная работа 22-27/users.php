 <?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require 'config.php';

// Получаем данные из GET-запроса
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? [];
if (!is_array($roleFilter)) {
    $roleFilter = [$roleFilter];
}
$columns = $_GET['columns'] ?? ['user_id', 'name', 'email', 'role', 'created_at', 'password', 'address', 'phone'];
$sortColumn = $_GET['sort'] ?? 'user_id';
$sortOrder = $_GET['order'] ?? 'asc';

// Проверяем выбранные колонки
$validColumns = ['user_id', 'name', 'email', 'role', 'created_at', 'password', 'address', 'phone'];
$columns = array_intersect($columns, $validColumns);

// SQL-запрос для получения пользователей
$query = "SELECT * FROM users WHERE (name LIKE :search OR email LIKE :search)";

// Массив параметров
$params = [':search' => '%' . $search . '%'];

// Фильтрация по ролям
if (!empty($roleFilter)) {
    $roleParams = [];
    foreach ($roleFilter as $index => $role) {
        $paramName = ':role_' . $index;
        $roleParams[] = $paramName;
        $params[$paramName] = $role;
    }
    $placeholders = implode(',', $roleParams);
    $query .= " AND role IN ($placeholders)";
}

// Сортировка
if (in_array($sortColumn, $validColumns)) {
    $query .= " ORDER BY $sortColumn " . ($sortOrder === 'desc' ? 'DESC' : 'ASC');
}

// Выполняем запрос
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

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
    header('Content-Disposition: attachment; filename="users.csv"');

    // Открываем поток вывода
    $output = fopen('php://output', 'w');

    // Добавляем BOM (Byte Order Mark) для корректного отображения кириллицы в Excel
    fwrite($output, "\xEF\xBB\xBF");

    // Заголовки столбцов
    fputcsv($output, array('ID пользователя', 'Имя пользователя', 'Email', 'Роль', 'Дата регистрации', 'Адрес', 'Телефон'), ';');

    // Данные
    foreach ($users as $item) {
        $row = array(
            $item['user_id'],
            $item['name'],
            $item['email'],
            $item['role'],
            $item['created_at'],
            $item['address'],
            $item['phone']
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
    <title>Пользователи</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="admin-style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <!-- Иконки FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2><i class="fas fa-users"></i> Пользователи</h2>
        <a href="admin_panel.php" class="btn btn-primary mb-3"><i class="fas fa-home"></i> На главную</a>

        <!-- Форма поиска и фильтрации -->
        <div class="search-filter">
            <!-- Поле поиска -->
            <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" class="form-control" placeholder="Поиск по имени или email" id="searchInput">
            </div>
            <div class="search-filter">
            <!-- Фильтрация по ролям -->
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" id="roleDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-filter"></i> Роли
                </button>
                <ul class="dropdown-menu" aria-labelledby="roleDropdown">
                    <li>
                        <label class="dropdown-item">
                            <input type="checkbox" name="role[]" value="admin" <?= in_array('admin', $roleFilter) ? 'checked' : ''; ?>>
                            Админ
                        </label>
                    </li>
                    <li>
                        <label class="dropdown-item">
                            <input type="checkbox" name="role[]" value="user" <?= in_array('user', $roleFilter) ? 'checked' : ''; ?>>
                            Пользователь
                        </label>
                    </li>
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
            <a href="users.php" class="btn btn-secondary"><i class="fas fa-sync"></i> Сброс</a>
        </div>

        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addUserForm">
            <i class="fas fa-plus"></i> Добавить пользователя
        </button>

        <!-- Таблица пользователей -->
        <table class="table" id="usersTable">
            <thead>
                <tr>
                    <?php if (in_array('user_id', $columns)): ?>
                        <th><a href="?sort=user_id&order=<?= $sortColumn === 'user_id' && $sortOrder === 'asc' ? 'desc' : 'asc'; ?>">ID</a></th>
                    <?php endif; ?>
                    <?php if (in_array('name', $columns)): ?>
                        <th><a href="?sort=name&order=<?= $sortColumn === 'name' && $sortOrder === 'asc' ? 'desc' : 'asc'; ?>">Имя</a></th>
                    <?php endif; ?>
                    <?php if (in_array('email', $columns)): ?>
                        <th><a href="?sort=email&order=<?= $sortColumn === 'email' && $sortOrder === 'asc' ? 'desc' : 'asc'; ?>">Email</a></th>
                    <?php endif; ?>
                    <?php if (in_array('role', $columns)): ?>
                        <th><a href="?sort=role&order=<?= $sortColumn === 'role' && $sortOrder === 'asc' ? 'desc' : 'asc'; ?>">Роль</a></th>
                    <?php endif; ?>
                    <?php if (in_array('created_at', $columns)): ?>
                        <th><a href="?sort=created_at&order=<?= $sortColumn === 'created_at' && $sortOrder === 'asc' ? 'desc' : 'asc'; ?>">Дата регистрации</a></th>
                    <?php endif; ?>
                    <th>Адрес</th>
                    <th>Телефон</th>
                    <th>Пароль</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <?php if (in_array('user_id', $columns)): ?>
                            <td><?= htmlspecialchars($user['user_id']); ?></td>
                        <?php endif; ?>
                        <?php if (in_array('name', $columns)): ?>
                            <td><?= highlight($user['name'], $search); ?></td>
                        <?php endif; ?>
                        <?php if (in_array('email', $columns)): ?>
                            <td><?= highlight($user['email'], $search); ?></td>
                        <?php endif; ?>
                        <?php if (in_array('role', $columns)): ?>
                            <td><?= htmlspecialchars($user['role']); ?></td>
                        <?php endif; ?>
                        <?php if (in_array('created_at', $columns)): ?>
                            <td><?= htmlspecialchars($user['created_at']); ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($user['address'] ?? 'Нет данных'); ?></td>
                        <td><?= htmlspecialchars($user['phone'] ?? 'Нет данных'); ?></td>
                        <td>
                            <span class="password-field" data-password="<?= htmlspecialchars($user['password']); ?>">
                                <?= str_repeat('*', 20); ?>
                                <i class="fas fa-eye" style="margin-left: 5px;"></i> <!-- Иконка глаза -->
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                            <button class="btn btn-warning btn-sm edit-user" data-id="<?= $user['user_id']; ?>">
                                <i class="fas fa-edit"></i> Редактировать
                            </button>
                                <button class="btn btn-danger btn-sm delete-user" data-id="<?= $user['user_id']; ?>"><i class="fas fa-trash"></i> Удалить</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Кнопки экспорта и печати -->
        <div class="export-print">
        <a href="users.php?export_csv=1"><button class="btn btn-info" id="exportExcel"><i class="fas fa-file-csv"></i> Экспорт в CSV</a>
            <button class="btn btn-info" onclick="window.print()"><i class="fas fa-print"></i> Печать</button>
        </div>
    </div>

     <!-- Горизонтальный контейнер для добавления и редактирования -->
     <div class="form-container">
            <!-- Секция добавления пользователя -->
            <div class="form-section" id="addCategorySection">
                <h3><i class="fas fa-user-plus"></i> Добавить пользователя</h3>
                <form id="addUserForm">
                    <div class="mb-3">
                        <label for="userName" class="form-label">Имя</label>
                        <input type="text" name="name" id="userName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="userEmail" class="form-label">Email</label>
                        <input type="email" name="email" id="userEmail" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="userPassword" class="form-label">Пароль</label>
                        <input type="password" name="password" id="userPassword" class="form-control" required>
                    </div>
                    <div class="mb-3">
            <label for="userAddress" class="form-label">Адрес</label>
            <input type="text" name="address" id="userAddress" class="form-control">
        </div>
        <div class="mb-3">
            <label for="userPhone" class="form-label">Телефон</label>
            <input type="text" name="phone" id="userPhone" class="form-control">
        </div>
                    <div class="mb-3">
                        <label for="userRole" class="form-label">Роль</label>
                        <select name="role" id="userRole" class="form-select" required>
                            <option value="admin">Админ</option>
                            <option value="user">Пользователь</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-success" id="saveUserButton"><i class="fas fa-save"></i> Сохранить</button>
                </form>
            </div>

            <!-- Секция редактирования пользователя -->
            <div class="form-section" id="editUserSection">
                <h3><i class="fas fa-user-edit"></i> Редактировать пользователя</h3>
                <form id="editUserForm">
                    <input type="hidden" name="id" id="editUserId">
                    <div class="mb-3">
                        <label for="editUserName" class="form-label">Имя</label>
                        <input type="text" name="name" id="editUserName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="editUserEmail" class="form-label">Email</label>
                        <input type="email" name="email" id="editUserEmail" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPassword" class="form-label">Пароль</label>
                        <input type="password" name="password" id="editPassword" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="editUserAddress" class="form-label">Адрес</label>
                        <input type="text" name="address" id="editUserAddress" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="editUserPhone" class="form-label">Телефон</label>
                        <input type="text" name="phone" id="editUserPhone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="editUserRole" class="form-label">Роль</label>
                        <select name="role" id="editUserRole" class="form-select" required>
                            <option value="admin">Админ</option>
                            <option value="user">Пользователь</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" id="updateUserButton"><i class="fas fa-save"></i> Обновить</button>
                </form>
            </div>
        </div>

    <!-- Кнопка "Наверх" -->
    <button class="scroll-to-top" id="scrollToTop"><i class="fas fa-arrow-up"></i></button>

    <!-- Скрипты для работы с пользователями -->
    <script>
    $(document).ready(function() {
        // Добавление пользователя
$('#saveUserButton').click(function() {
    const formData = {
        name: $('#userName').val(),
        email: $('#userEmail').val(),
        password: $('#userPassword').val(),
        address: $('#userAddress').val(),
        phone: $('#userPhone').val(),
        role: $('#userRole').val()
    };

    $.ajax({
        url: 'add_user.php',
        method: 'POST',
        data: formData,
        success: function(response) {
            $('#addUserForm')[0].reset();
            location.reload();
        },
        error: function(xhr, status, error) {
            alert('Ошибка при добавлении пользователя: ' + error);
        }
    });
});
});

// Редактирование пользователя
$(document).ready(function() {
    $('.edit-user').click(function() {
        const userId = $(this).data('id'); // Получаем ID пользователя

        $.ajax({
            url: 'get_users.php',
            method: 'GET',
            data: { user_id: userId }, // Отправляем ID пользователя
            success: function(response) {
                const user = JSON.parse(response); // Парсим JSON-ответ
                if (user.error) {
                    alert(user.error); // Показываем ошибку, если есть
                    return;
                }
            $('#editUserId').val(user.user_id);
            $('#editUserName').val(user.name);
            $('#editUserEmail').val(user.email);
            $('#editPassword').val(user.password);
            $('#editUserAddress').val(user.address);
            $('#editUserPhone').val(user.phone);
            $('#editUserRole').val(user.role);

               // Открываем модальное окно (если используется)
            $('#editUserModal').modal('show');
        
        },
        error: function(xhr, status, error) {
            alert('Ошибка при загрузке данных пользователя: ' + error);
        }
    });
});


// Обновление пользователя
$(document).ready(function() {
    $('#updateUserButton').click(function() {
        const formData = {
            user_id: $('#editUserId').val(), // Исправлено на user_id
            name: $('#editUserName').val(),
            email: $('#editUserEmail').val(),
            password: $('#editPassword').val(),
            address: $('#editUserAddress').val(),
            phone: $('#editUserPhone').val(),
            role: $('#editUserRole').val()
        };

        $.ajax({
            url: 'update_users.php',
            method: 'POST',
            data: formData,
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    location.reload(); // Перезагружаем страницу после успешного обновления
                } else {
                    alert(result.error || 'Ошибка при обновлении пользователя');
                }
            },
            error: function(xhr, status, error) {
                alert('Ошибка при обновлении пользователя: ' + error);
            }
        });
    });
});

        // Удаление пользователя
        $('.delete-user').click(function() {
    const userId = $(this).data('id'); // Используем data-id, как в HTML

    if (confirm('Вы уверены, что хотите удалить этого пользователя?')) {
        $.ajax({
            url: 'delete_user.php',
            method: 'POST',
            data: { user_id: userId }, // Отправляем user_id на сервер
            success: function(response) {
                location.reload(); // Перезагружаем страницу после успешного удаления
            },
            error: function(xhr, status, error) {
                alert('Ошибка при удалении пользователя: ' + error);
            }
        });
    }
});
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
    });

    $('#applyFilters').click(function() {
        const search = $('#searchInput').val();
        const categories = [];
        $('input[name="role[]"]:checked').each(function() {
            categories.push($(this).val());
        });
        const columns = [];
        $('input[name="columns[]"]:checked').each(function() {
            columns.push($(this).val());
        });

        // Формируем URL с параметрами
        let url = 'users.php?search=' + encodeURIComponent(search);
        if (categories.length > 0) {
            url += '&role[]=' + categories.join('&category[]=');
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

    $(document).ready(function() {
    $('.password-field').click(function() {
        const $this = $(this);
        const password = $this.data('password');
        
        // Если пароль уже отображается, скрываем его
        if ($this.text() === password) {
            $this.text('*'.repeat(password.length)); // Скрываем пароль звёздочками
        } else {
            // Показываем урезанный пароль (первые 10 символов)
            const truncatedPassword = password.length > 10 ? password.substring(0, 10) + '...' : password;
            $this.text(truncatedPassword);
        }
    });
});

$(document).ready(function() {
    // Прокрутка к форме добавления категории
    $('button[data-bs-target="#addUserForm"]').click(function() {
        $('html, body').animate({
            scrollTop: $('#addUserForm').offset().top
        }, 1000); // 1000 мс (1 секунда) для плавной прокрутки
    });

    // Прокрутка к форме редактирования категории
    $('.edit-user').click(function() {
        $('html, body').animate({
            scrollTop: $('#editUserSection').offset().top
        }, 1000); // 1000 мс (1 секунда) для плавной прокрутки
    });
});

    </script>
</body>
</html>