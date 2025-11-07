<?php
// Jednostavan test da vidimo da li env učitava kako treba

// Učitaj .env
$envPath = __DIR__ . '/env/.env';
if (!file_exists($envPath)) {
    die("❌ env/.env file not found!\n");
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
    [$key, $value] = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value, '"\'');
    putenv(trim($key) . '=' . trim($value, '"\''));
}

echo "🔍 Checking AWS Configuration:\n\n";

$key = $_ENV['AWS_ACCESS_KEY_ID'] ?? getenv('AWS_ACCESS_KEY_ID');
$secret = $_ENV['AWS_SECRET_ACCESS_KEY'] ?? getenv('AWS_SECRET_ACCESS_KEY');
$region = $_ENV['AWS_REGION'] ?? getenv('AWS_REGION');

echo "AWS_ACCESS_KEY_ID: " . ($key ? substr($key, 0, 10) . '...' : '❌ NOT SET') . "\n";
echo "AWS_SECRET_ACCESS_KEY: " . ($secret ? '✅ SET (hidden)' : '❌ NOT SET') . "\n";
echo "AWS_REGION: " . ($region ?: '❌ NOT SET') . "\n\n";

if (!$key || !$secret) {
    echo "⚠️ AWS credentials are missing!\n\n";
    echo "Add these to your env/.env file:\n";
    echo "AWS_ACCESS_KEY_ID=your-access-key-here\n";
    echo "AWS_SECRET_ACCESS_KEY=your-secret-key-here\n";
    echo "AWS_REGION=eu-west-1\n";
    exit;
}

// Test basic AWS connection
require __DIR__ . '/vendor/autoload.php';

use Aws\Sts\StsClient;

try {
    $sts = new StsClient([
        'region' => $region ?: 'eu-west-1',
        'version' => '2011-06-15',
        'credentials' => [
            'key' => $key,
            'secret' => $secret
        ]
    ]);
    
    $result = $sts->getCallerIdentity();
    
    echo "✅ AWS Connection Successful!\n\n";
    echo "Account ID: " . $result['Account'] . "\n";
    echo "User ARN: " . $result['Arn'] . "\n";
    echo "User ID: " . $result['UserId'] . "\n";
    
} catch (Exception $e) {
    echo "❌ AWS Connection Failed!\n\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    if (strpos($e->getMessage(), 'InvalidClientTokenId') !== false) {
        echo "🔴 Your AWS Access Key ID is invalid!\n";
        echo "➡️ Create new credentials at: https://console.aws.amazon.com/iam/home#/security_credentials\n";
    } elseif (strpos($e->getMessage(), 'SignatureDoesNotMatch') !== false) {
        echo "🔴 Your AWS Secret Access Key is invalid!\n";
        echo "➡️ Check for extra spaces or wrong copy/paste\n";
    }
}
?>