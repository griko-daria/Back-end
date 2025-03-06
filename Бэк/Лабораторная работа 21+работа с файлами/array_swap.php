<?php
// Создаем массив из 17 целых чисел
$array = range(1, 17);

echo "<div class='result'>";
echo "<p>Изначальный массив: " . implode(", ", $array) . "</p>";

// Меняем местами первый и последний элементы
$firstElement = $array[0];
$lastElement = $array[count($array) - 1];
$array[0] = $lastElement;
$array[count($array) - 1] = $firstElement;

echo "<p>Измененный массив (первый и последний элементы поменяны местами): " . implode(", ", $array) . "</p>";
echo "</div>";
?>