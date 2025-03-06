<?php
$lastName = "Грико"; // Замените на вашу фамилию
$firstName = "Дарья"; // Замените на ваше имя
$repeatCount = 5;
$i = 0;

echo "<div class='result'>";
do {
    echo "<p>$lastName $firstName</p>";
    $i++;
} while ($i < $repeatCount + 5);
echo "</div>";
?>