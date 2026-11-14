<?php
// Настройки путей
$dataFile = __DIR__ . '/applause/data.csv';

// Функция для чтения данных
function readScores($file) {
    $scores = [];
    if (file_exists($file) && ($handle = fopen($file, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            if (count($data) >= 2) {
                $scores[trim($data[0])] = (int)trim($data[1]);
            }
        }
        fclose($handle);
    }
    return $scores;
}

$scores = readScores($dataFile);

// Сортируем от большего к меньшему
arsort($scores);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Таблица лидеров</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, Arial, sans-serif; }
        body { background: #14161a; color: #e6e6e6; display: flex; justify-content: center; padding: 40px 20px; }
        
        .container { width: 100%; max-width: 600px; }
        h1 { color: #4a90e2; margin-bottom: 30px; text-align: center; font-size: 28px; }
        
        table { width: 100%; border-collapse: collapse; background: #1c2129; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #262d37; }
        th { background: #232a33; color: #9fb0c3; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #232a33; }
        
        .rank { color: #6f7d8c; font-weight: bold; width: 50px; }
        .name { font-weight: 500; font-size: 16px; }
        .score { text-align: right; font-weight: bold; color: #7ff2a8; font-size: 18px; }
        
        .empty { text-align: center; color: #6f7d8c; padding: 40px; font-style: italic; }
        
        .back-link { display: block; text-align: center; margin-top: 20px; color: #6f7d8c; text-decoration: none; font-size: 14px; }
        .back-link:hover { color: #9fb0c3; }
        
        /* Красивое выделение топ-3 */
        tr:nth-child(1) .name::before { content: "🥇 "; }
        tr:nth-child(2) .name::before { content: "🥈 "; }
        tr:nth-child(3) .name::before { content: "🥉 "; }
    </style>
</head>
<body>

<div class="container">
    <h1>Таблица лидеров</h1>
    
    <?php if (empty($scores)): ?>
        <div class="empty">Пока нет ни одного игрока. Будьте первым!</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Игрок</th>
                    <th style="text-align: right;">Очки</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1; foreach ($scores as $name => $score): ?>
                    <tr>
                        <td class="rank"><?= $rank++ ?></td>
                        <td class="name"><?= htmlspecialchars($name) ?></td>
                        <td class="score"><?= $score ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <a href="index.php" class="back-link">← Вернуться в личный кабинет</a>
</div>

</body>
</html>