<?php
require_once __DIR__ . '/config.cfg';

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ensureDataDir(): void
{
    if (!is_dir(DATA_DIR)) {
        @mkdir(DATA_DIR, 0775, true);
    }
}

function loadData(): array
{
    ensureDataDir();
    if (!is_file(DATA_FILE)) {
        $initial = ['participants' => []];
        saveData($initial);
        return $initial;
    }
    $raw = @file_get_contents(DATA_FILE);
    if ($raw === false || trim($raw) === '') {
        return ['participants' => []];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || !isset($decoded['participants']) || !is_array($decoded['participants'])) {
        return ['participants' => []];
    }
    $decoded['participants'] = array_values(array_filter($decoded['participants'], function ($p) {
        return is_array($p) && isset($p['id'], $p['name'], $p['score']);
    }));
    return $decoded;
}

function saveData(array $data): void
{
    ensureDataDir();
    $tmp = DATA_FILE . '.tmp.' . bin2hex(random_bytes(4));
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $fp = @fopen($tmp, 'wb');
    if ($fp === false) {
        throw new RuntimeException('Не удалось открыть временный файл для записи.');
    }
    if (flock($fp, LOCK_EX)) {
        fwrite($fp, $json === false ? '{"participants":[]}' : $json);
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    if (!@rename($tmp, DATA_FILE)) {
        @unlink($tmp);
        throw new RuntimeException('Не удалось сохранить данные.');
    }
}

function findIndexById(array $data, string $id): ?int
{
    foreach ($data['participants'] as $i => $p) {
        if (isset($p['id']) && $p['id'] === $id) {
            return $i;
        }
    }
    return null;
}

function findParticipant(array $data, string $id): ?array
{
    $i = findIndexById($data, $id);
    return $i === null ? null : $data['participants'][$i];
}

function sortByScoreThenName(array $participants): array
{
    usort($participants, function ($a, $b) {
        $sa = (int)$a['score'];
        $sb = (int)$b['score'];
        if ($sa !== $sb) {
            return $sb <=> $sa;
        }
        return strcasecmp((string)$a['name'], (string)$b['name']);
    });
    return $participants;
}

function generateId(): string
{
    return 'u_' . bin2hex(random_bytes(8));
}

function validateInt($v, int $min, int $max): ?int
{
    if (is_int($v)) {
        $n = $v;
    } elseif (is_string($v) && preg_match('/^-?\d+$/', trim($v))) {
        $n = (int)$v;
    } else {
        return null;
    }
    if ($n < $min || $n > $max) {
        return null;
    }
    return $n;
}