<?php
session_start();

// Настройки путей
$dataDir = __DIR__ . '/applause';
$dataFile = $dataDir . '/data.csv';
$resetFile = $dataDir . '/last_reset.txt'; // Файл для отслеживания времени сброса

// Создаем папку, если ее нет
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, "");
}
if (!file_exists($resetFile)) {
    file_put_contents($resetFile, "0");
}

// Функция для чтения данных
function readScores($file) {
    $scores = [];
    if (($handle = fopen($file, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            if (count($data) >= 2) {
                $scores[trim($data[0])] = (int)trim($data[1]);
            }
        }
        fclose($handle);
    }
    return $scores;
}

// Функция для сохранения данных
function saveScores($file, $scores) {
    $fp = fopen($file, 'w');
    foreach ($scores as $name => $score) {
        fputcsv($fp, [$name, $score], ";");
    }
    fclose($fp);
}

// --- ПРОВЕРКА НА ГЛОБАЛЬНЫЙ СБРОС ---
$lastResetTime = (int)file_get_contents($resetFile);
if (isset($_SESSION['login_time']) && $_SESSION['login_time'] < $lastResetTime) {
    session_destroy();
    setcookie('quiz_player', '', time() - 3600, "/");
    header("Location: index.php");
    exit;
}

// --- ОБРАБОТКА ВХОДА ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $name = trim($_POST['name'] ?? '');
    if (mb_strlen($name) >= 2) {
        $_SESSION['player_name'] = $name;
        $_SESSION['login_time'] = time();
        setcookie('quiz_player', $name, time() + (86400 * 30), "/");
        
        $scores = readScores($dataFile);
        if (!isset($scores[$name])) {
            $scores[$name] = 0;
            saveScores($dataFile, $scores);
        }
        
        header("Location: index.php");
        exit;
    } else {
        $error = "Имя должно быть не короче 2 символов.";
    }
}

// Автовход по куке
if (!isset($_SESSION['player_name']) && isset($_COOKIE['quiz_player'])) {
    $_SESSION['player_name'] = $_COOKIE['quiz_player'];
    $_SESSION['login_time'] = time();
}

// --- ОБРАБОТКА ИЗМЕНЕНИЯ БАЛЛОВ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_score') {
    if (isset($_SESSION['player_name'])) {
        $name = $_SESSION['player_name'];
        $amount = (int)$_POST['amount'];
        
        $scores = readScores($dataFile);
        if (isset($scores[$name])) {
            $scores[$name] += $amount;
            saveScores($dataFile, $scores);
        }
        header("Location: index.php");
        exit;
    }
}

// --- ОБРАБОТКА ГЛОБАЛЬНОГО СБРОСА ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_all') {
    file_put_contents($dataFile, "");
    file_put_contents($resetFile, time());
    session_destroy();
    setcookie('quiz_player', '', time() - 3600, "/");
    header("Location: index.php");
    exit;
}

// Получаем текущие данные
$playerName = $_SESSION['player_name'] ?? null;
$scores = readScores($dataFile);
$currentScore = $playerName && isset($scores[$playerName]) ? $scores[$playerName] : 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Квиз - Личный кабинет</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, Arial, sans-serif; }
        body { background: #14161a; color: #e6e6e6; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        
        .container { background: #1c2129; border: 1px solid #262d37; border-radius: 12px; padding: 30px; width: 100%; max-width: 500px; text-align: center; }
        
        h1 { color: #4a90e2; margin-bottom: 20px; font-size: 24px; }
        
        input[type="text"] { width: 100%; padding: 12px; margin-bottom: 15px; background: #0d0f12; border: 1px solid #3a4552; color: #fff; border-radius: 8px; font-size: 16px; outline: none; }
        input[type="text"]:focus { border-color: #4a90e2; }
        
        button { background: #2b4a72; color: #fff; border: 1px solid #3a6fb0; padding: 10px 15px; font-size: 16px; border-radius: 8px; cursor: pointer; transition: 0.2s; }
        button:hover { background: #355b8a; }
        
        .btn-main { width: 100%; margin-top: 10px; }
        .error { color: #ff9b9b; margin-bottom: 15px; font-size: 14px; }
        
        .score-display { font-size: 48px; font-weight: bold; color: #7ff2a8; margin: 20px 0; }
        .name-display { font-size: 20px; color: #9fb0c3; margin-bottom: 20px; }
        
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
        .grid button { background: #232a33; border: 1px solid #333c48; padding: 12px 0; font-size: 14px; }
        .grid button:hover { background: #2f3945; }
        .grid button.active { background: #2e8b57; border-color: #7ff2a8; }
        
        .custom-input { width: 100%; padding: 12px; margin-bottom: 15px; background: #0d0f12; border: 1px solid #3a4552; color: #fff; border-radius: 8px; font-size: 16px; }
        
        .actions { display: flex; gap: 10px; }
        .actions button { flex: 1; font-size: 24px; font-weight: bold; padding: 15px; }
        .btn-plus { background: #2e8b57 !important; border-color: #7ff2a8 !important; }
        .btn-minus { background: #8b2e2e !important; border-color: #ff9b9b !important; }
        
        /* Стили для нижних ссылок */
        .bottom-links { margin-top: 30px; display: flex; flex-direction: column; gap: 10px; }
        
        .tools-link { display: block; color: #6f7d8c; text-decoration: none; font-size: 14px; }
        .tools-link:hover { color: #9fb0c3; }

        /* Незаметная кнопка сброса */
        .btn-reset { background: none; border: none; color: #4a5666; font-size: 12px; cursor: pointer; padding: 0; text-decoration: underline; transition: 0.2s; }
        .btn-reset:hover { color: #ff9b9b; }
    </style>
</head>
<body>

<div class="container">
    <?php if (!$playerName): ?>
        <!-- ЭКРАН ВХОДА -->
        <h1>Вход в квиз</h1>
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <input type="text" name="name" placeholder="Введите ваше имя" required minlength="2" autocomplete="off">
            <button type="submit" class="btn-main">Войти</button>
        </form>
    <?php else: ?>
        <!-- ЭКРАН ИГРОКА -->
        <h1>Ваш профиль</h1>
        <div class="name-display">Игрок: <b><?= htmlspecialchars($playerName) ?></b></div>
        <div class="score-display"><?= $currentScore ?></div>
        
        <form method="POST" id="score-form">
            <input type="hidden" name="action" value="change_score">
            <input type="hidden" name="amount" id="input-amount" value="100">
            
            <div class="grid" id="preset-grid">
                <button type="button" data-val="100" class="active">100</button>
                <button type="button" data-val="200">200</button>
                <button type="button" data-val="300">300</button>
                <button type="button" data-val="400">400</button>
                <button type="button" data-val="500">500</button>
                <button type="button" data-val="800">800</button>
                <button type="button" data-val="900">900</button>
                <button type="button" data-val="1000">1000</button>
            </div>
            
            <input type="number" class="custom-input" id="custom-val" placeholder="Своя сумма" min="1">
            
            <div class="actions">
                <button type="submit" name="sign" value="minus" class="btn-minus" onclick="document.getElementById('input-amount').value = Math.abs(document.getElementById('input-amount').value) * -1;">-</button>
                <button type="submit" name="sign" value="plus" class="btn-plus" onclick="document.getElementById('input-amount').value = Math.abs(document.getElementById('input-amount').value);">+</button>
            </div>
        </form>
    <?php endif; ?>

    <!-- Нижний блок со ссылками -->
    <div class="bottom-links">
        <a href="tools.php" class="tools-link">🏆 Таблица лидеров (Tools)</a>
        
        <?php if ($playerName): ?>
            <form method="POST" onsubmit="return confirm('ВНИМАНИЕ! Вы уверены, что хотите полностью перезапустить игру? Все очки всех игроков обнулятся, и всем придется заново вводить свои имена.');">
                <input type="hidden" name="action" value="reset_all">
                <button type="submit" class="btn-reset">Сбросить игру для всех</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
    const presetBtns = document.querySelectorAll('#preset-grid button');
    const inputAmount = document.getElementById('input-amount');
    const customVal = document.getElementById('custom-val');

    if (presetBtns.length > 0) {
        presetBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                presetBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                inputAmount.value = btn.dataset.val;
                customVal.value = '';
            });
        });

        customVal.addEventListener('input', () => {
            presetBtns.forEach(b => b.classList.remove('active'));
            if (customVal.value !== '') {
                inputAmount.value = customVal.value;
            }
        });
    }
</script>
</body>
</html>