<?php
session_start();
require 'config.php'; // Подключение к базе данных
require 'cart_index.php'; // Подключение функционала корзины

// Получение всех категорий
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Фильтрация и поиск
$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$price_min = $_GET['price_min'] ?? '';
$price_max = $_GET['price_max'] ?? '';

// Базовый запрос
$query = "SELECT * FROM products WHERE 1=1";

// Поиск
if ($search) {
    $query .= " AND name LIKE :search";
}

// Фильтр по категории
if ($category_id) {
    $query .= " AND category_id = :category_id";
}

// Фильтр по цене
if ($price_min !== '') {
    $query .= " AND price >= :price_min";
}
if ($price_max !== '') {
    $query .= " AND price <= :price_max";
}

$stmt = $pdo->prepare($query);

// Привязка параметров
if ($search) {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
if ($category_id) {
    $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
}
if ($price_min !== '') {
    $stmt->bindValue(':price_min', $price_min, PDO::PARAM_INT);
}
if ($price_max !== '') {
    $stmt->bindValue(':price_max', $price_max, PDO::PARAM_INT);
}

$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Все товары - Мелодик</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Шапка -->
    <header>
    <div class="container">
        <div class="logo">
            <a href="index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center;">
                <img src="https://png.pngtree.com/png-vector/20190904/ourlarge/pngtree-violin-piano-key-musical-instrument-logo-png-image_1722686.jpg" alt="Логотип Мелодик">
                <div>
                    <h1>Мелодик</h1>
                    <p>Магазин музыкальных товаров</p>
                </div>
            </a>
        </div>

        <!-- Кнопки авторизации и корзины -->
        <div class="auth">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php" class="btn">Личный кабинет</a>
            <?php else: ?>
                <a href="login.php" class="btn">Авторизация</a>
                <a href="register.php" class="btn">Регистрация</a>
            <?php endif; ?>

            <!-- Значок корзины (отображается только для авторизованных пользователей) -->
            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'user'): ?>
                <div class="cart-icon" id="cartIcon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?= $cartItemCount; ?></span>
                </div>
            <?php endif; ?>

            <!-- Иконка админ-панели (отображается только для администраторов) -->
            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
                <div class="admin-icon" id="adminIcon">
                    <a href="admin_panel.php" style="color: inherit; text-decoration: none;">
                        <i class="fas fa-cog"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-header">
        <h3>Корзина</h3>
        <button class="close-btn" id="closeCart">&times;</button>
    </div>
    <div class="cart-content" id="cart-content">
        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'user'): ?>
            <?php
            $cartItems = getCartItems($_SESSION['user_id']);
            $total = 0;
            ?>
            <?php if (empty($cartItems)): ?>
                <p>Ваша корзина пуста.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($cartItems as $item): ?>
                        <?php
                        $itemTotal = $item['price'] * $item['quantity'];
                        $total += $itemTotal;
                        ?>
                        <li>
                            <div>
                                <h4><?= htmlspecialchars($item['name']) ?></h4>
                                <p><?= $item['quantity'] ?> шт. - <?= $itemTotal ?> руб.</p>
                                <div class="quantity-control">
                                    <input type="number" 
                                           class="quantity-input" 
                                           value="<?= $item['quantity'] ?>" 
                                           min="1" 
                                           max="<?= $item['quantity'] ?>" 
                                           data-id="<?= htmlspecialchars($item['id']) ?>">
                                    <button class="remove-from-cart" data-id="<?= htmlspecialchars($item['id']) ?>">Удалить</button>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
        <h4>Итого: <?= $total ?> руб.</h4>
        <button class="btn">Оформить заказ</button>
    </div>
</div>

<!-- Основной контент -->
<main>
    <div class="container">
        <!-- Фильтры -->
        <section class="filters-section">
            <form method="GET" action="products_index.php" class="filters">
                <div class="filter-group">
                    <label for="search">Поиск:</label>
                    <input type="text" name="search" id="search" value="<?= htmlspecialchars($search) ?>" placeholder="Введите название">
                </div>
                <div class="filter-group">
                    <label for="category_id">Категория:</label>
                    <select name="category_id" id="category_id">
                        <option value="">Все категории</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= $category_id == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="price_min">Цена от:</label>
                    <input type="number" name="price_min" id="price_min" value="<?= htmlspecialchars($price_min) ?>">
                </div>
                <div class="filter-group">
                    <label for="price_max">Цена до:</label>
                    <input type="number" name="price_max" id="price_max" value="<?= htmlspecialchars($price_max) ?>">
                </div>
                <button type="submit" class="btn">Применить</button>
                <a href="products_index.php" class="btn reset-btn">Сбросить</a>
            </form>
        </section>

          <!-- Товары -->
          <section class="products-section">
            <h2>Товары</h2>
            <div class="products-grid">
                <?php if (empty($products)): ?>
                    <p>Товары не найдены.</p>
                <?php else: ?>
                   <!-- В разделе отображения товаров -->
<?php foreach ($products as $product): ?>
    <div class="product">
        <a href="product_detail.php?id=<?= $product['id'] ?>">
            <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            <h3><?= htmlspecialchars($product['name']) ?></h3>
            <p><?= htmlspecialchars($product['price']) ?> руб.</p>
        </a>
        <button class="add-to-cart" data-product-id="<?= $product['id'] ?>">Добавить в корзину</button>
    </div>
<?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

    <footer>
    <div class="container">
        <p>&copy; 2025 Мелодик. Все права защищены.</p>
    </div>
</footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    // Обработка добавления товара в корзину
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function () {
            const productId = this.getAttribute('data-product-id'); // Получаем ID товара

            if (!productId) {
                alert('Ошибка: ID товара не найден.');
                return;
            }

            // Отправляем запрос на сервер
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ product_id: productId }) // Передаем ID товара
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCart(); // Обновляем корзину и счетчик
                } else {
                    alert('Ошибка при добавлении товара в корзину: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Произошла ошибка при добавлении товара.');
            });
        });
    });

    // Функция для обновления корзины
    function updateCart() {
        fetch('get_cart_data.php') // Получаем данные корзины с сервера
            .then(response => response.json())
            .then(data => {
                renderCart(data); // Обновляем содержимое корзины
                updateCartCount(data); // Обновляем счетчик товаров
            })
            .catch(error => {
                console.error('Ошибка при обновлении корзины:', error);
            });
    }

    // Функция для отрисовки корзины
    function renderCart(cartItems) {
        const cartContent = document.getElementById('cart-content');
        let total = 0;
        let html = '';

        if (cartItems.length === 0) {
            html = '<p>Ваша корзина пуста.</p>';
        } else {
            html = '<ul>';
            cartItems.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                html += `
                <li>
                    <div>
                        <h4>${item.name}</h4>
                        <p>${item.quantity} шт. - ${itemTotal} руб.</p>
                        <div class="quantity-control">
                            <input type="number" 
                                   class="quantity-input" 
                                   value="${item.quantity}" 
                                   min="1" 
                                   max="${item.quantity}" 
                                   data-id="${item.id}">
                            <button class="remove-from-cart" data-id="${item.id}">Удалить</button>
                        </div>
                    </div>
                </li>`;
            });
            html += '</ul>';
            html += `
        <div class="cart-footer">
            <h4>Итого: ${total} руб.</h4>
            <a href = "checkout.php"><button class="btn">Оформить заказ</button>
        </div>`;
        }

        cartContent.innerHTML = html; // Обновляем содержимое корзины
        bindRemoveButtons(); // Привязываем обработчики для кнопок "Удалить"
    }

    // Функция для привязки обработчиков к кнопкам "Удалить"
    function bindRemoveButtons() {
        document.querySelectorAll('.remove-from-cart').forEach(button => {
            button.addEventListener('click', function () {
                const productId = this.getAttribute('data-id'); // Получаем ID товара
                const quantityInput = this.closest('.quantity-control').querySelector('.quantity-input');
                const quantity = quantityInput.value; // Получаем количество для удаления

                if (!productId || !quantity) {
                    alert('Ошибка: Данные не найдены.');
                    return;
                }

                // Отправляем запрос на сервер
                fetch('remove_from_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: productId, quantity: quantity }) // Передаем ID товара и количество
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateCart(); // Обновляем корзину и счетчик
                    } else {
                        alert('Ошибка при удалении товара из корзины: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    alert('Произошла ошибка при удалении товара.');
                });
            });
        });
    }

    // Функция для обновления счетчика товаров
    function updateCartCount(cartItems) {
        const cartCountElement = document.querySelector('.cart-count');
        if (cartCountElement) {
            const totalItems = cartItems.reduce((sum, item) => sum + item.quantity, 0);
            cartCountElement.textContent = totalItems;
        }
    }

    // Инициализация корзины при загрузке страницы
    updateCart();
});

    document.addEventListener('DOMContentLoaded', function () {
        const cartIcon = document.getElementById('cartIcon');
        const cartSidebar = document.getElementById('cartSidebar');
        const closeCart = document.getElementById('closeCart');
        if (cartIcon) {
            cartIcon.addEventListener('click', function () {
                cartSidebar.classList.add('open');
            });
        }
        if (closeCart) {
            closeCart.addEventListener('click', function () {
                cartSidebar.classList.remove('open');
            });
        }
        document.addEventListener('click', function (event) {
            if (!cartSidebar.contains(event.target) && !cartIcon.contains(event.target)) {
                cartSidebar.classList.remove('open');
            }
        });
    });
</script>
</body>
</html>
