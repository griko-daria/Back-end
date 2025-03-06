<?php
// Функция для расчета по формуле
function calculateFormula($a, $b, $c) {
    // Проверка на деление на ноль
    if ($c - 1 == 0) {
        throw new Exception("Ошибка: Деление на ноль (c - 1 = 0).");
    }

    // Проверка на корень из отрицательного числа
    if ($a + $b < 0) {
        throw new Exception("Ошибка: Корень из отрицательного числа (a + b < 0).");
    }

    // Вычисление результата
    $result = sqrt($a + $b) / ($c - 1);
    return $result;
}

echo "<div class='result'>";

// Вывод формулы в HTML
echo "<p>Формула для расчета: f(a, b, c) = √(a + b) / (c - 1)</p>";

// Пример использования функции
try {
    // Входные данные
    $a = 4;
    $b = 5;
    $c = 2;

    // Вызов функции
    $result = calculateFormula($a, $b, $c);

    // Вывод результата
    echo "<p>Результат расчета по формуле: $result</p>";
} catch (Exception $e) {
    // Обработка исключений
    echo "<p class='error'>" . $e->getMessage() . "</p>";
}

echo "</div>";
?>