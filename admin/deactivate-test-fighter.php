<?php
$fightersFile = __DIR__ . '/../data/fighters.json';
$fighters = json_decode(file_get_contents($fightersFile), true);

foreach ($fighters as &$fighter) {
    if ($fighter['slug'] === 'test-borac') {
        $fighter['status'] = 'inactive';
        $fighter['updated_at'] = date('Y-m-d H:i:s');
        echo "✅ Deactivated: {$fighter['name']}\n";
    }
}

file_put_contents($fightersFile, json_encode($fighters, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "✅ Test Borac deactivated!\n";
