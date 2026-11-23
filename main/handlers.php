<?php
require_once __DIR__ . '/functions.php';

function sanitizeName($name): ?string
{
    if (!is_string($name)) {
        return null;
    }
    $name = trim($name);
    if ($name === '') {
        return null;
    }
    if (function_exists('mb_substr')) {
        $name = mb_substr($name, 0, MAX_NAME_LEN, 'UTF-8');
    } else {
        $name = substr($name, 0, MAX_NAME_LEN);
    }
    return $name;
}

function handleList(): void
{
    $data = loadData();
    $sorted = sortByScoreThenName($data['participants']);
    jsonResponse(['ok' => true, 'data' => ['participants' => $sorted]]);
}

function handleAdd($name): void
{
    $clean = sanitizeName($name);
    if ($clean === null) {
        jsonResponse(['ok' => false, 'error' => 'Имя не может быть пустым.'], 400);
    }
    $data = loadData();
    $participant = ['id' => generateId(), 'name' => $clean, 'score' => 0];
    $data['participants'][] = $participant;
    saveData($data);
    jsonResponse(['ok' => true, 'data' => ['participant' => $participant]]);
}

function handleRename($id, $name): void
{
    $id = is_string($id) ? $id : '';
    $clean = sanitizeName($name);
    if ($id === '' || $clean === null) {
        jsonResponse(['ok' => false, 'error' => 'Некорректные данные.'], 400);
    }
    $data = loadData();
    $idx = findIndexById($data, $id);
    if ($idx === null) {
        jsonResponse(['ok' => false, 'error' => 'Участник не найден.'], 404);
    }
    $data['participants'][$idx]['name'] = $clean;
    saveData($data);
    jsonResponse(['ok' => true, 'data' => ['participant' => $data['participants'][$idx]]]);
}

function handleDelete($id): void
{
    $id = is_string($id) ? $id : '';
    if ($id === '') {
        jsonResponse(['ok' => false, 'error' => 'Некорректный id.'], 400);
    }
    $data = loadData();
    $idx = findIndexById($data, $id);
    if ($idx === null) {
        jsonResponse(['ok' => false, 'error' => 'Участник не найден.'], 404);
    }
    array_splice($data['participants'], $idx, 1);
    saveData($data);
    jsonResponse(['ok' => true, 'data' => ['id' => $id]]);
}

function handleSetScore($id, $score): void
{
    $id = is_string($id) ? $id : '';
    $n = validateInt($score, -MAX_SCORE_ABS, MAX_SCORE_ABS);
    if ($id === '' || $n === null) {
        jsonResponse(['ok' => false, 'error' => 'Очки должны быть целым числом в допустимом диапазоне.'], 400);
    }
    $data = loadData();
    $idx = findIndexById($data, $id);
    if ($idx === null) {
        jsonResponse(['ok' => false, 'error' => 'Участник не найден.'], 404);
    }
    $data['participants'][$idx]['score'] = $n;
    saveData($data);
    jsonResponse(['ok' => true, 'data' => ['participant' => $data['participants'][$idx]]]);
}

function handleApplyDelta($id, $delta, $mode): void
{
    $id = is_string($id) ? $id : '';
    $mode = is_string($mode) ? $mode : '';
    $d = validateInt($delta, MIN_DELTA, MAX_DELTA);
    if ($id === '' || $d === null || !in_array($mode, ['add', 'sub'], true)) {
        jsonResponse(['ok' => false, 'error' => 'Некорректные данные для начисления/списания.'], 400);
    }
    $data = loadData();
    $idx = findIndexById($data, $id);
    if ($idx === null) {
        jsonResponse(['ok' => false, 'error' => 'Участник не найден.'], 404);
    }
    $current = (int)$data['participants'][$idx]['score'];
    $next = $mode === 'add' ? $current + $d : $current - $d;
    if ($next > MAX_SCORE_ABS) {
        $next = MAX_SCORE_ABS;
    }
    if ($next < -MAX_SCORE_ABS) {
        $next = -MAX_SCORE_ABS;
    }
    $data['participants'][$idx]['score'] = $next;
    saveData($data);
    jsonResponse(['ok' => true, 'data' => ['participant' => $data['participants'][$idx]]]);
}

function handleReset(): void
{
    saveData(['participants' => []]);
    jsonResponse(['ok' => true, 'data' => ['participants' => []]]);
}