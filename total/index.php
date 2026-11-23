<?php
require_once __DIR__ . '/../main/config.cfg';
require_once __DIR__ . '/../main/functions.php';
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Табло — Квиз</title>
    <link rel="icon" href="../img/favicon.ico">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="site-header">
        <h1 class="site-title">Табло</h1>
        <div class="site-actions">
            <a class="btn btn-link" href="../index.php">Управление</a>
        </div>
    </header>

    <main class="site-main">
        <section class="participants" aria-live="polite">
            <div class="participants-empty is-hidden" id="total-empty">
                Пока нет участников.
            </div>
            <ul class="participants-list participants-list--total" id="total-list"></ul>
        </section>
    </main>

    <div class="toast is-hidden" id="toast" role="status" aria-live="polite"></div>

    <script src="../js/total.js"></script>
</body>
</html>