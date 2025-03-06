<?php
$string = "Я люблю Беларусь. Я учусь в политехническом колледже.";
echo "<div class='result'>";
echo "<p>Начальная строка: $string</p>";

// Длина строки
$length = mb_strlen($string);
echo "<p>Длина строки: $length</p>";

// 5-й символ строки
$s1 = mb_substr($string, 4, 1);
echo "<p>5-й символ строки: $s1</p>";

// ASCII-код 5-го символа
$asciiCode = ord($s1);
echo "<p>ASCII-код 5-го символа: $asciiCode</p>";

// Замена всех букв "о" на "к"
$modifiedString = str_replace('о', 'к', $string);
echo "<p>Строка с заменой 'о' на 'к': $modifiedString</p>";

echo "</div>";
?>