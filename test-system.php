<?php
/**
 * test-system.php
 * System Test File - KREIRAJ U ROOT-u za testiranje
 */

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: #28a745; }
.error { color: #dc3545; }
.warning { color: #ffc107; }
.info { color: #17a2b8; }
h1, h2 { color: #333; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; }
.test-item { margin: 10px 0; padding: 10px; border-left: 4px solid #ddd; }
.test-pass { border-left-color: #28a745; background: #f8fff8; }
.test-fail { border-left-color: #dc3545; background: #fff8f8; }
.test-warn { border-left-color: #ffc107; background: #fffcf0; }
</style>";

echo "<h1>🥊 BIF PPV System Test</h1>";

$tests = [];
$errors = [];

// Test 1: PHP Version
$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '7.4.0', '>=');
$tests['PHP Version'] = [
    'status' => $phpOk ? 'pass' : 'fail',
    'message' => "PHP $phpVersion " . ($phpOk ? '✅' : '❌ Requires PHP 7.4+')
];

// Test 2: Required Extensions
$extensions = ['json', 'curl', 'pdo', 'pdo_mysql'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $tests["Extension: $ext"] = [
        'status' => $loaded ? 'pass' : 'fail',
        'message' => $loaded ? '✅ Loaded' : '❌ Missing'
    ];
    if (!$loaded) $errors[] = "Missing extension: $ext";
}

// Test 3: Folder Structure
$folders = [
    'api' => 'api',
    'config' => 'config',
    'data' => 'data',
    'js' => 'js',
    'env' => 'env'
];

foreach ($folders as $name => $path) {
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    
    if (!$exists) {
        mkdir($path, 0755, true);
        $exists = is_dir($path);
    }
    
    $tests["Folder: $name"] = [
        'status' => $exists && $writable ? 'pass' : ($exists ? 'warn' : 'fail'),
        'message' => $exists 
            ? ($writable ? '✅ Exists and writable' : '⚠️ Exists but not writable') 
            : '❌ Missing'
    ];
}

// Test 4: Critical Files
$files = [
    'database.php' => 'database.php',
    'security config' => 'config/security.php',
    'main API' => 'api/ppv.php',
    'enhanced protection' => 'js/enhanced-protection.js',
    'watch page' => 'watch.html',
    'env file' => 'env/.env'
];

foreach ($files as $name => $path) {
    $exists = file_exists($path);
    $tests["File: $name"] = [
        'status' => $exists ? 'pass' : 'fail',
        'message' => $exists ? '✅ Exists' : '❌ Missing'
    ];
    if (!$exists) $errors[] = "Missing file: $path";
}

// Test 5: Database Connection
echo "<h2>🗄️ Database Test</h2>";
try {
    if (file_exists('database.php')) {
        require_once 'database.php';
        $db = new PPV_Database();
        $result = $db->testConnection();
        
        $tests['Database Connection'] = [
            'status' => $result['success'] ? 'pass' : 'warn',
            'message' => $result['success'] 
                ? "✅ {$result['type']} connection working" 
                : "⚠️ {$result['type']} connection failed, using fallback"
        ];
        
        echo "<pre>";
        print_r($db->getConnectionInfo());
        echo "</pre>";
    } else {
        $tests['Database Connection'] = [
            'status' => 'fail',
            'message' => '❌ database.php not found'
        ];
    }
} catch (Exception $e) {
    $tests['Database Connection'] = [
        'status' => 'fail',
        'message' => '❌ Error: ' . $e->getMessage()
    ];
}

// Test 6: Security Config
echo "<h2>🔒 Security Config Test</h2>";
try {
    if (file_exists('config/security.php')) {
        $securityConfig = include 'config/security.php';
        $tests['Security Config'] = [
            'status' => is_array($securityConfig) ? 'pass' : 'fail',
            'message' => is_array($securityConfig) ? '✅ Config loaded' : '❌ Invalid config'
        ];
        
        echo "<pre>";
        echo "DRM Settings:\n";
        print_r($securityConfig['drm'] ?? []);
        echo "\nDetection Settings:\n";
        print_r($securityConfig['detection'] ?? []);
        echo "</pre>";
    } else {
        $tests['Security Config'] = [
            'status' => 'fail',
            'message' => '❌ config/security.php not found'
        ];
    }
} catch (Exception $e) {
    $tests['Security Config'] = [
        'status' => 'fail',
        'message' => '❌ Error loading config: ' . $e->getMessage()
    ];
}

// Test 7: Environment Variables
echo "<h2>⚙️ Environment Test</h2>";
$envFile = 'env/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    $hasStripeKeys = strpos($envContent, 'STRIPE_SECRET_KEY') !== false && 
                     strpos($envContent, 'STRIPE_PUBLISHABLE_KEY') !== false;
    
    $tests['Environment File'] = [
        'status' => $hasStripeKeys ? 'pass' : 'warn',
        'message' => $hasStripeKeys ? '✅ Stripe keys configured' : '⚠️ Stripe keys missing'
    ];
    
    // Show environment variables (without sensitive values)
    $lines = explode("\n", $envContent);
    echo "<pre>Environment variables:\n";
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            if (strpos(strtolower($key), 'secret') !== false || 
                strpos(strtolower($key), 'key') !== false) {
                echo trim($key) . "=***HIDDEN***\n";
            } else {
                echo trim($key) . "=" . trim($value, '"\'') . "\n";
            }
        }
    }
    echo "</pre>";
} else {
    $tests['Environment File'] = [
        'status' => 'fail',
        'message' => '❌ env/.env file not found'
    ];
}

// Test 8: API Endpoint Test
echo "<h2>🌐 API Test</h2>";
try {
    $apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/ppv.php?test=1';
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        $tests['API Endpoint'] = [
            'status' => isset($data['success']) && $data['success'] ? 'pass' : 'fail',
            'message' => isset($data['success']) && $data['success'] ? '✅ API responding' : '❌ API error'
        ];
        
        echo "<pre>API Response:\n";
        print_r($data);
        echo "</pre>";
    } else {
        $tests['API Endpoint'] = [
            'status' => 'fail',
            'message' => '❌ Cannot reach API endpoint'
        ];
    }
} catch (Exception $e) {
    $tests['API Endpoint'] = [
        'status' => 'fail',
        'message' => '❌ API test error: ' . $e->getMessage()
    ];
}

// Display Test Results
echo "<h2>📊 Test Results</h2>";
$passCount = 0;
$failCount = 0;
$warnCount = 0;

foreach ($tests as $testName => $result) {
    $class = 'test-' . $result['status'];
    if ($result['status'] === 'pass') $passCount++;
    elseif ($result['status'] === 'fail') $failCount++;
    else $warnCount++;
    
    echo "<div class='test-item $class'>";
    echo "<strong>$testName:</strong> {$result['message']}";
    echo "</div>";
}

// Summary
echo "<h2>📈 Summary</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px;'>";
echo "<strong>✅ Passed:</strong> $passCount<br>";
echo "<strong>⚠️ Warnings:</strong> $warnCount<br>";
echo "<strong>❌ Failed:</strong> $failCount<br>";
echo "</div>";

// Recommendations
echo "<h2>💡 Recommendations</h2>";

if ($failCount > 0) {
    echo "<div class='error'>";
    echo "<h3>❌ Critical Issues to Fix:</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
    echo "</div>";
}

if ($warnCount > 0) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Warnings to Address:</h3>";
    echo "<ul>";
    echo "<li>Configure missing Stripe keys in env/.env</li>";
    echo "<li>Set up database credentials if you want to use MySQL</li>";
    echo "<li>Ensure all folders are writable</li>";
    echo "</ul>";
    echo "</div>";
}

if ($failCount === 0) {
    echo "<div class='success'>";
    echo "<h3>🎉 System Ready!</h3>";
    echo "<p>Your BIF PPV system appears to be properly configured.</p>";
    echo "<ul>";
    echo "<li><a href='watch.html'>🎬 Test Watch Page</a></li>";
    echo "<li><a href='api/ppv.php?action=config'>⚙️ Test API Config</a></li>";
    echo "<li><a href='admin/admin.html'>🛠️ Admin Panel</a></li>";
    echo "</ul>";
    echo "</div>";
}

// Next Steps
echo "<h2>🚀 Next Steps</h2>";
echo "<ol>";
echo "<li><strong>If using MySQL:</strong> Update env/.env with database credentials</li>";
echo "<li><strong>Configure Stripe:</strong> Add your Stripe keys to env/.env</li>";
echo "<li><strong>Test the system:</strong> Try the watch page and admin panel</li>";
echo "<li><strong>Production setup:</strong> Enable SSL and remove this test file</li>";
echo "</ol>";

echo "<div style='margin-top: 30px; padding: 15px; background: #e9ecef; border-radius: 5px;'>";
echo "<strong>⚠️ Security Note:</strong> Delete this test file (test-system.php) before going to production!";
echo "</div>";
?>