

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лабораторная работа</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1, h2 {
            color: #333;
        }
        p {
            margin: 10px 0;
        }
        .error {
            color: red;
        }
        .result {
            background-color: #f9f9f9;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Лабораторная работа</h1>

    <?php
    // Подключаем файл с заданием 2
    echo "<h2>Задание 2: Текущая дата и время</h2>";
    include 'datetime.php';

    // Подключаем файл с заданием 3
    echo "<h2>Задание 3: Повторение имени и фамилии</h2>";
    include 'repeat_name.php';

    // Подключаем файл с заданием 4
    echo "<h2>Задание 4: Работа с массивами</h2>";
    include 'array_swap.php';

    // Подключаем файл с заданием 5
    echo "<h2>Задание 5: Работа со строками</h2>";
    include 'string_ops.php';

    // Подключаем файл с заданием 6
    echo "<h2>Задание 6: Расчет по формуле</h2>";
    include 'formula_calculation.php';

    echo "<h2>Пример работы с файлами</h2>";
include 'file_ops.php';
    ?>
</body>
</html>