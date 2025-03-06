<?php
$fileContent = "ПССИП";
file_put_contents('example.txt', $fileContent);
echo "<p>Содержимое файла: " . file_get_contents('example.txt') . "</p>";
?>