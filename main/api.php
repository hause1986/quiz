<?php
require_once __DIR__ . '/config.cfg';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/handlers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается.'], 405);
}

$raw = file_get_contents('php://input');
$payload = [];
if ($raw !== '' && $raw !== false) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    } else {
        parse_str($raw, $payload);
    }
}
if (empty($payload)) {
    $payload = $_POST;
}
if (empty($payload)) {
    $payload = $_GET;
}

$action = isset($payload['action']) ? (string)$payload['action'] : 'list';

try {
    switch ($action) {
        case 'list':
            handleList();
            break;
        case 'add':
            handleAdd($payload['name'] ?? null);
            break;
        case 'rename':
            handleRename($payload['id'] ?? null, $payload['name'] ?? null);
            break;
        case 'delete':
            handleDelete($payload['id'] ?? null);
            break;
        case 'setScore':
            handleSetScore($payload['id'] ?? null, $payload['score'] ?? null);
            break;
        case 'applyDelta':
            handleApplyDelta($payload['id'] ?? null, $payload['delta'] ?? null, $payload['mode'] ?? null);
            break;
        case 'reset':
            handleReset();
            break;
        default:
            jsonResponse(['ok' => false, 'error' => 'Неизвестное действие.'], 400);
    }
} catch (Throwable $ex) {
    jsonResponse(['ok' => false, 'error' => 'Внутренняя ошибка сервера.'], 500);
}