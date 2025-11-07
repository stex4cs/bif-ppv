<?php
require __DIR__ . '/vendor/autoload.php';

// Load .env
$envPath = __DIR__ . '/env/.env';
$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
    [$key, $value] = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value, '"\'');
    putenv(trim($key) . '=' . trim($value, '"\''));
}

use Aws\MediaLive\MediaLiveClient;
use Aws\Exception\AwsException;

$key = $_ENV['AWS_ACCESS_KEY_ID'];
$secret = $_ENV['AWS_SECRET_ACCESS_KEY'];

echo "🔍 Detailed MediaLive Test:\n\n";

// Test Ireland region (most likely to have MediaLive)
$region = 'eu-west-1';
echo "Testing region: $region (Ireland)\n\n";

try {
    $mediaLive = new MediaLiveClient([
        'region' => $region,
        'version' => '2017-10-14',
        'credentials' => ['key' => $key, 'secret' => $secret],
        'http' => ['verify' => false], // Disable SSL verification for testing
        'debug' => true // Enable debug output
    ]);
    
    echo "1️⃣ Attempting to list channels...\n";
    $result = $mediaLive->listChannels(['MaxResults' => 1]);
    
    echo "✅ SUCCESS! MediaLive is accessible!\n";
    echo "Channels found: " . count($result['Channels']) . "\n";
    
} catch (AwsException $e) {
    echo "❌ AWS Exception occurred:\n";
    echo "Error Code: " . $e->getAwsErrorCode() . "\n";
    echo "Error Message: " . $e->getAwsErrorMessage() . "\n";
    echo "Error Type: " . $e->getAwsErrorType() . "\n\n";
    
    // Analyze specific errors
    $errorCode = $e->getAwsErrorCode();
    
    if ($errorCode === 'UnauthorizedOperation') {
        echo "🔴 PROBLEM: Your IAM user doesn't have MediaLive permissions!\n\n";
        echo "SOLUTION:\n";
        echo "1. Go to: https://console.aws.amazon.com/iam/\n";
        echo "2. Click on Users → bif-ppv-service\n";
        echo "3. Click 'Add permissions' → 'Attach policies directly'\n";
        echo "4. Search and add these policies:\n";
        echo "   ✓ AWSElementalMediaLiveFullAccess\n";
        echo "   ✓ AWSElementalMediaPackageFullAccess\n";
        echo "   ✓ AmazonS3FullAccess\n";
        echo "   ✓ CloudFrontFullAccess\n";
        
    } elseif ($errorCode === 'OptInRequired') {
        echo "🔴 PROBLEM: MediaLive service needs to be activated!\n\n";
        echo "SOLUTION:\n";
        echo "1. Go to: https://console.aws.amazon.com/medialive/\n";
        echo "2. Switch to region: Ireland (eu-west-1)\n";
        echo "3. Click 'Get Started' or accept terms\n";
        echo "4. Wait 5-10 minutes for activation\n";
        
    } elseif ($errorCode === 'SubscriptionRequiredException') {
        echo "🔴 PROBLEM: MediaLive requires subscription!\n\n";
        echo "SOLUTION:\n";
        echo "1. Go to AWS Marketplace\n";
        echo "2. Subscribe to MediaLive service\n";
        echo "3. Accept terms and conditions\n";
        
    } else {
        echo "🔴 Unknown error. Full details:\n";
        echo $e->getMessage() . "\n";
    }
}

echo "\n-------------------\n";
echo "📺 Alternative: Testing AWS IVS (Interactive Video Service):\n\n";

try {
    // IVS is simpler alternative to MediaLive
    $ivs = new \Aws\IVS\IVSClient([
        'region' => 'eu-west-1',
        'version' => 'latest',
        'credentials' => ['key' => $key, 'secret' => $secret]
    ]);
    
    $channels = $ivs->listChannels(['maxResults' => 1]);
    echo "✅ IVS is available! (Simpler alternative to MediaLive)\n";
    echo "   This might be better for your use case!\n";
    
} catch (Exception $e) {
    echo "⚠️ IVS also needs setup\n";
}
?>