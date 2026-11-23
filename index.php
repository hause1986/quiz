<?php
require_once __DIR__ . '/main/config.cfg';
require_once __DIR__ . '/main/functions.php';
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Квиз — подсчёт голосов</title>
    <link rel="icon" href="./img/favicon.ico">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <header class="site-header">
        <h1 class="site-title">Квиз — подсчёт голосов</h1>
        <div class="site-actions">
            <button type="button" class="btn btn-primary" id="btn-add-participant">Добавить участника</button>
            <a class="btn btn-link" href="./total/index.php">Табло</a>
        </div>
    </header>

    <main class="site-main">
        <section class="participants" aria-live="polite">
            <div class="participants-empty is-hidden" id="participants-empty">
                Пока нет участников. Нажмите «Добавить участника».
            </div>
            <ul class="participants-list" id="participants-list"></ul>
        </section>

        <section class="reset-zone">
            <button type="button" class="btn-reset" id="btn-reset-all">Сбросить всё</button>
        </section>
    </main>

    <!-- Модалка: имя участника (добавление/редактирование) -->
    <div class="modal is-hidden" id="modal-name" role="dialog" aria-modal="true" aria-labelledby="modal-name-title">
        <div class="modal-backdrop" data-modal-close="name"></div>
        <div class="modal-window">
            <h2 class="modal-title" id="modal-name-title">Имя участника</h2>
            <label class="field">
                <span class="field-label">Имя</span>
                <input type="text" class="field-input" id="input-name" maxlength="50" autocomplete="off">
            </label>
            <p class="field-error is-hidden" id="error-name"></p>
            <div class="modal-actions">
                <button type="button" class="btn" data-modal-close="name">Отмена</button>
                <button type="button" class="btn btn-primary" id="btn-name-save">Сохранить</button>
            </div>
        </div>
    </div>

    <!-- Модалка: количество очков -->
    <div class="modal is-hidden" id="modal-score" role="dialog" aria-modal="true" aria-labelledby="modal-score-title">
        <div class="modal-backdrop" data-modal-close="score"></div>
        <div class="modal-window">
            <h2 class="modal-title" id="modal-score-title">Количество очков</h2>
            <label class="field">
                <span class="field-label">Целое число (может быть отрицательным)</span>
                <input type="text" inputmode="numeric" class="field-input" id="input-score" autocomplete="off">
            </label>
            <p class="field-error is-hidden" id="error-score"></p>
            <div class="modal-actions">
                <button type="button" class="btn" data-modal-close="score">Отмена</button>
                <button type="button" class="btn btn-primary" id="btn-score-save">Сохранить</button>
            </div>
        </div>
    </div>

    <!-- Модалка: подтверждение действия (удаление / сброс) -->
    <div class="modal is-hidden" id="modal-confirm" role="dialog" aria-modal="true" aria-labelledby="modal-confirm-title">
        <div class="modal-backdrop" data-modal-close="confirm"></div>
        <div class="modal-window">
            <h2 class="modal-title" id="modal-confirm-title">Подтверждение</h2>
            <p class="modal-text" id="confirm-text"></p>
            <div class="modal-actions">
                <button type="button" class="btn" data-modal-close="confirm">Отмена</button>
                <button type="button" class="btn btn-danger" id="btn-confirm-ok">Подтвердить</button>
            </div>
        </div>
    </div>

    <div class="toast is-hidden" id="toast" role="status" aria-live="polite"></div>

    <script src="./js/script.js"></script>
</body>
</html>