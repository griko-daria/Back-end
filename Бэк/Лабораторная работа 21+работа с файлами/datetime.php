<?php
function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

echo "<div class='result'>";
echo "<p>Текущая дата и время: " . getCurrentDateTime() . "</p>";
echo "</div>";
?>