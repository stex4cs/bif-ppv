<?php
$json = file_get_contents("php://input");
$data = json_decode($json, true);
echo "Received action: " . ($data["action"] ?? "NONE") . "\n";
echo "Full data: " . print_r($data, true);
