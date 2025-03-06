<?php
session_start();
require 'config.php';

if (isset($_GET['query'])) {
    $query = '%' . $_GET['query'] . '%';

    // Поиск товаров по названию с ограничением на 5 результатов
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE :query LIMIT 5");
    $stmt->execute(['query' => $query]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($products) > 0) {
        foreach ($products as $product) {
            echo '
            <div class="product-card">
                <img src="' . htmlspecialchars($product['image_url']) . '" alt="' . htmlspecialchars($product['name']) . '">
                <h4>' . htmlspecialchars($product['name']) . '</h4>
            </div>';
        }
    } else {
        echo '<p>Ничего не найдено.</p>';
    }
}
?>