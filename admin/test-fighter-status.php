<?php
$fighters = json_decode(file_get_contents(__DIR__ . '/../data/fighters.json'), true);
foreach ($fighters as $fighter) {
    echo $fighter['name'] . ' (' . $fighter['slug'] . '): ' . $fighter['status'] . "\n";
}
