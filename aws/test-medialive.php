<?php
require __DIR__ . '/../vendor/autoload.php';

use Aws\Sts\StsClient;
use Aws\MediaLive\MediaLiveClient;

/** Simple .env loader (root/.env ili root/env/.env) */
function loadEnv() {
    $candidates = [dirname(__DIR__).'/.env', dirname(__DIR__).'/env/.env'];
    foreach ($candidates as $p) {
        if (is_file($p)) {
            foreach (file($p, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $t = trim($line);
                if ($t === '' || $t[0] === '#' || strpos($t, '=') === false) continue;
                [$k,$v] = explode('=', $line, 2);
                $k = trim($k);
                $v = trim($v, "\"' \t\r\n");
                // Nemoj session token ako ga slučajno ima
                if ($k === 'AWS_SESSION_TOKEN') continue;
                putenv("$k=$v"); $_ENV[$k] = $v; $_SERVER[$k] = $v;
            }
            return $p;
        }
    }
    return null;
}
loadEnv();

header('Content-Type: application/json');

$region = $_ENV['AWS_REGION'] ?? 'eu-north-1';
$key    = $_ENV['AWS_ACCESS_KEY_ID'] ?? '';
$secret = $_ENV['AWS_SECRET_ACCESS_KEY'] ?? '';

try {
    // 1) Proveri kredencijale preko STS
    $sts = new StsClient([
        'region'  => $region,
        'version' => '2011-06-15',
        'credentials' => ['key' => $key, 'secret' => $secret],
    ]);
    $me = $sts->getCallerIdentity();

    // 2) MediaLive listInputs
    $ml = new MediaLiveClient([
        'region'  => $region,
        'version' => '2017-10-14',
        'credentials' => ['key' => $key, 'secret' => $secret],
    ]);
    $inputs = $ml->listInputs(['MaxResults' => 1]);

    echo json_encode([
        'ok' => true,
        'caller' => [
            'Account' => $me->get('Account'),
            'Arn'     => $me->get('Arn'),
            'UserId'  => $me->get('UserId'),
        ],
        'medialive' => [
            'inputs_count' => count($inputs->get('Inputs') ?? []),
        ],
    ], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT);
}
