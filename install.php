<?php
/**
 * install.php - Setup script za BIF PPV
 * KREIRAJ OVAJ FAJL: install.php
 */

echo "<h1>🥊 BIF PPV Setup</h1>";

// Check requirements
echo "<h2>📋 Checking Requirements...</h2>";

$requirements = [
    'PHP >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'JSON extension' => extension_loaded('json'),
    'cURL extension' => extension_loaded('curl'),
    'PDO extension' => extension_loaded('pdo'),
    'MySQL PDO driver' => extension_loaded('pdo_mysql'),
];

foreach ($requirements as $req => $met) {
    $status = $met ? '✅' : '❌';
    echo "<p>$status $req</p>";
}

// Check folders
echo "<h2>📁 Checking Folders...</h2>";

$folders = [
    'data' => 'data',
    'config' => 'config', 
    'js' => 'js',
    'api' => 'api',
    'admin' => 'admin'
];

foreach ($folders as $name => $path) {
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    
    if (!$exists) {
        mkdir($path, 0755, true);
        $exists = true;
    }
    
    $status = $exists && $writable ? '✅' : '❌';
    echo "<p>$status $name folder ($path)</p>";
}

// Check files
echo "<h2>📄 Checking Files...</h2>";

$files = [
    '.env file' => 'env/.env',
    'Database helper' => 'database.php',
    'Security config' => 'config/security.php',
    'Enhanced protection' => 'js/enhanced-protection.js',
    'Main API' => 'api/ppv.php',
    'Watch page' => 'watch.html'
];

foreach ($files as $name => $path) {
    $exists = file_exists($path);
    $status = $exists ? '✅' : '❌';
    echo "<p>$status $name ($path)</p>";
    
    if (!$exists) {
        echo "<p style='color: red; margin-left: 20px;'>⚠️ Missing required file!</p>";
    }
}

// Test database connection
echo "<h2>🗄️ Testing Database Connection...</h2>";

try {
    $envFile = 'env/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') === false) continue;
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value, '"\'');
        }
    }
    
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'bif_ppv';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    echo "<p>✅ Database connection successful!</p>";
    
    // Test table existence
    $tables = ['events', 'purchases', 'access_tokens'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        $status = $exists ? '✅' : '❌';
        echo "<p>$status Table: $table</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p>💡 Will fallback to JSON files</p>";
}

echo "<h2>🚀 Next Steps:</h2>";
echo "<ol>";
echo "<li>Update your .env file with correct database credentials</li>";
echo "<li>Run setup_database.sql in your MySQL server (if using database)</li>";
echo "<li>Test the watch.html page</li>";
echo "<li>Check admin panel functionality</li>";
echo "<li>Set up SSL certificate for production</li>";
echo "</ol>";

echo "<h2>🔗 Quick Links:</h2>";
echo "<p><a href='watch.html'>🎬 Watch Page</a></p>";
echo "<p><a href='admin/admin.html'>⚙️ Admin Panel</a></p>";
echo "<p><a href='api/ppv.php?action=config'>🔧 API Config Test</a></p>";

?>