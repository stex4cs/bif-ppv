<?php
/**
 * Logs a ticket button click for the admin dashboard.
 * Stores in data/ticket_clicks.json, keeps last 5000 entries.
 */
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) $body = [];

$logDir = dirname(__DIR__) . '/data';
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

$logFile = $logDir . '/ticket_clicks.json';
$clicks = [];
if (file_exists($logFile)) {
    $raw = file_get_contents($logFile);
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) $clicks = $decoded;
}

// Anonymize IP — store only crc32 hash for unique-visitor approximation
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ipHash = $ip !== '' ? hash('crc32b', $ip) : '';

$click = [
    'ts'      => date('Y-m-d H:i:s'),
    'source'  => mb_substr(trim($body['source'] ?? 'unknown'), 0, 64),
    'page'    => mb_substr(trim($body['page'] ?? ''), 0, 200),
    'ref'     => mb_substr(trim($body['ref'] ?? ''), 0, 200),
    'ua'      => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
    'ip_hash' => $ipHash,
];

$clicks[] = $click;
// Cap log size
if (count($clicks) > 5000) {
    $clicks = array_slice($clicks, -5000);
}

@file_put_contents($logFile, json_encode($clicks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(['success' => true]);
