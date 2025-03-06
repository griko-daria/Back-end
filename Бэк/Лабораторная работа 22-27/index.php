<?php
session_start(); // Запуск сессии
require 'config.php'; // Подключение к базе данных
require 'cart_index.php'; // Подключение функционала корзины

// Загрузка категорий из базы данных
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Загрузка случайных товаров
$stmt = $pdo->query("SELECT * FROM products ORDER BY RAND() LIMIT 4");
$randomProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Загрузка самых популярных товаров (на основе количества проданных)
$stmt = $pdo->query("SELECT * 
                    FROM products 
                    ORDER BY sold DESC LIMIT 4");
$popularProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Загрузка новинок (последние добавленные товары)
$stmt = $pdo->query("SELECT * 
                    FROM products 
                    ORDER BY created_at DESC LIMIT 4");
$newProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мелодик - Магазин музыкальных товаров</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <link rel="stylesheet" href="styles.css">
</head>

<style>
    .reviews-section {
    background: #f5f5f5;
    padding: 50px 0;
    overflow: hidden; /* Чтобы карусель не выходила за пределы */
}

.swiper-container {
    width: 100%;
    padding: 50px 0;
}

.swiper-slide {
    width: 60%; /* Ширина слайда */
    opacity: 0.5; /* Полупрозрачные боковые слайды */
    transition: opacity 0.3s ease, transform 0.3s ease;
    transform: scale(0.8); /* Боковые слайды меньше */
}

.swiper-slide-active {
    opacity: 1; /* Центральный слайд полностью видим */
    transform: scale(1); /* Центральный слайд больше */
    z-index: 1; /* Центральный слайд на переднем плане */
}

.review {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.review p {
    font-style: italic;
    color: #555;
}

.review span {
    display: block;
    margin-top: 10px;
    font-weight: bold;
    color: #2c3e50;
}

/* Стили для пагинации (точек) */
.swiper-pagination {
    position: relative;
    margin-top: 20px;
}

.swiper-pagination-bullet {
    width: 12px;
    height: 12px;
    background-color: #3498db;
    opacity: 0.5;
    transition: opacity 0.3s ease;
}

.swiper-pagination-bullet-active {
    opacity: 1;
    background-color: #2980b9;
}
</style>
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

            <!-- Выпадающий список категорий -->
            <div class="categories-dropdown">
                <a href = "products_index.php"><button class="dropbtn">Все категории <i class="fas fa-chevron-down"></i></button>
                <div class="dropdown-content">
                    <?php foreach ($categories as $category): ?>
                        <a href="products_index.php?category_id=<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Поиск -->
            <div class="search">
                <form action="products_index.php" method="GET">
                    <input type="text" name="search" id="searchInput" placeholder="Поиск товаров...">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <!-- Кнопки авторизации и корзины -->
            <div class="auth">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="btn">Личный кабинет</a>
                <?php else: ?>
                    <a href="login.php" class="btn">Авторизация</a>
                    <a href="register.php" class="btn">Регистрация</a>
                <?php endif; ?>

                <!-- Значок корзины -->
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'user'): ?>
                    <div class="cart-icon" id="cartIcon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count"><?= $cartItemCount; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Иконка админ-панели -->
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
<!-- Основной контент -->
<main>
    <!-- Герой-секция -->
    <section class="hero">
        <div class="container">
            <h2>Добро пожаловать в Мелодик!</h2>
            <p>Мы предлагаем лучшие музыкальные инструменты и аксессуары для вашего творчества.</p>
            <a href="products_index.php" class="btn">Посмотреть все товары</a>
        </div>
    </section>

        <!-- О нас -->
        <section class="about-us">
        <div class="container">
            <h3>О нас</h3>
            <p>Мелодик — это магазин для настоящих ценителей музыки. Мы предлагаем широкий ассортимент инструментов и аксессуаров, а также профессиональные консультации.</p>
        </div>
    </section>

    <!-- Самые популярные товары -->
    <section class="random-products">
        <div class="container">
            <h3>Самые популярные</h3>
            <div class="products-grid">
                <?php foreach ($popularProducts as $product): ?>
                    <a href="product_detail.php?id=<?= $product['id'] ?>" class="product">
                        <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <h4><?= htmlspecialchars($product['name']) ?></h4>
                        <p><?= htmlspecialchars($product['price']) ?> руб.</p>
                        <p class="sold">Продано: <?= htmlspecialchars($product['sold']) ?> шт.</p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Новинки -->
<section class="random-products">
    <div class="container">
        <h3>Новинки</h3>
        <div class="products-grid">
            <?php foreach ($newProducts as $product): ?>
                <a href="product_detail.php?id=<?= $product['id'] ?>" class="product">
                    <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <h4><?= htmlspecialchars($product['name']) ?></h4>
                    <p><?= htmlspecialchars($product['price']) ?> руб.</p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

    <!-- Случайные товары -->
    <section class="random-products">
        <div class="container">
            <h3>Рекомендуем</h3>
            <div class="products-grid">
                <?php foreach ($randomProducts as $product): ?>
                    <a href="product_detail.php?id=<?= $product['id'] ?>" class="product">
                        <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <h4><?= htmlspecialchars($product['name']) ?></h4>
                        <p><?= htmlspecialchars($product['price']) ?> руб.</p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <div class="info-container">
    <!-- Время работы -->
    <section class="working-hours">
        <div class="container">
            <h3>Время работы</h3>
            <p>Понедельник - Пятница: 10:00 - 20:00</p>
            <p>Суббота: 11:00 - 18:00</p>
            <p>Воскресенье: Выходной</p>
        </div>
    </section>

    <!-- Контакты -->
    <section class="contacts">
        <div class="container">
            <h3>Контакты</h3>
            <p>Адрес: ул. Музыкальная, 123</p>
            <p>Телефон: +375 (29) 285-31-00</p>
            <p>Email: info@melodic.ru</p>
        </div>
    </section>
</div>

<!-- Карусель с отзывами -->
<section class="reviews-section">
    <div class="container">
        <h3>Отзывы наших клиентов</h3>
        <!-- Swiper -->
        <div class="swiper-container">
            <div class="swiper-wrapper">
                <!-- Отзыв 1 -->
                <div class="swiper-slide">
                    <div class="review">
                        <p>"Отличный магазин! Быстрая доставка и качественные товары."</p>
                        <span>- Иван Петров</span>
                    </div>
                </div>
                <!-- Отзыв 2 -->
                <div class="swiper-slide">
                    <div class="review">
                        <p>"Очень доволен покупкой гитары. Цены приятно удивили!"</p>
                        <span>- Анна Сидорова</span>
                    </div>
                </div>
                <!-- Отзыв 3 -->
                <div class="swiper-slide">
                    <div class="review">
                        <p>"Лучший выбор музыкальных инструментов в городе."</p>
                        <span>- Сергей Иванов</span>
                    </div>
                </div>
                <!-- Отзыв 4 -->
                <div class="swiper-slide">
                    <div class="review">
                        <p>"Отличный сервис и широкий ассортимент."</p>
                        <span>- Мария Кузнецова</span>
                    </div>
                </div>
                <!-- Отзыв 5 -->
                <div class="swiper-slide">
                    <div class="review">
                        <p>"Быстро нашли то, что нужно. Спасибо!"</p>
                        <span>- Дмитрий Смирнов</span>
                    </div>
                </div>
            </div>
            <!-- Добавляем пагинацию (точки) -->
            <div class="swiper-pagination"></div>
        </div>
    </div>
</section>

    <!-- Подвал -->
    <footer>
        <div class="container">
            <p>&copy; 2025 Мелодик. Все права защищены.</p>
        </div>
    </footer>

    <!-- Скрипты -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <script>
 document.addEventListener('DOMContentLoaded', function () {
        const swiper = new Swiper('.swiper-container', {
            loop: true, // Бесконечная карусель
            centeredSlides: true, // Центрирование слайдов
            slidesPerView: 1, // Показывать 1 слайд по умолчанию
            spaceBetween: 30, // Расстояние между слайдами
            autoplay: {
                delay: 5000, // Автопереключение каждые 5 секунд
                disableOnInteraction: false, // Не останавливать автопереключение при взаимодействии
            },
            pagination: {
                el: '.swiper-pagination', // Пагинация (точки)
                clickable: true,
            },
            breakpoints: {
                // Настройки для разных размеров экрана
                768: {
                    slidesPerView: 3, // Показывать 3 слайда на экранах шире 768px
                    spaceBetween: 20,
                },
            },
        });
    });

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