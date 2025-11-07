<?php
/**
 * db-test.php - Simple Database Connection Test
 * KREIRAJ OVAJ FAJL U ROOT-u
 */

echo "<h1>Database Test</h1>";

// Test 1: PHP basic
echo "<h2>1. PHP Test</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Date: " . date('Y-m-d H:i:s') . "<br>";

// Test 2: Load environment
echo "<h2>2. Environment Test</h2>";
$envFile = 'env/.env';
if (file_exists($envFile)) {
    echo "✅ .env file exists<br>";
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value, '"\'');
    }
    
    echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'not set') . "<br>";
    echo "DB_NAME: " . ($_ENV['DB_NAME'] ?? 'not set') . "<br>";
    echo "DB_USER: " . ($_ENV['DB_USER'] ?? 'not set') . "<br>";
    echo "DB_PASS: " . (empty($_ENV['DB_PASS']) ? 'empty' : 'set') . "<br>";
} else {
    echo "❌ .env file not found<br>";
}

// Test 3: MySQL connection
echo "<h2>3. MySQL Connection Test</h2>";

$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'bif_ppv';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

echo "Attempting connection...<br>";
echo "Host: $host<br>";
echo "Database: $dbname<br>";
echo "User: $username<br>";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ <strong>MySQL connection successful!</strong><br>";
    
    // Test query
    $result = $pdo->query("SELECT 1 as test")->fetch();
    echo "✅ Test query successful: " . $result['test'] . "<br>";
    
    // Check if database exists
    $result = $pdo->query("SELECT DATABASE() as db")->fetch();
    echo "✅ Current database: " . $result['db'] . "<br>";
    
    // Show tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll();
    echo "📋 Tables in database: " . count($tables) . "<br>";
    
    if (count($tables) > 0) {
        echo "<ul>";
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            echo "<li>$tableName</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>MySQL connection failed:</strong><br>";
    echo "Error: " . $e->getMessage() . "<br>";
    
    // Try connecting without database name
    try {
        $dsn = "mysql:host=$host;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password);
        
        echo "✅ Can connect to MySQL server<br>";
        echo "💡 Database '$dbname' might not exist. Creating...<br>";
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Database '$dbname' created<br>";
        
    } catch (Exception $e2) {
        echo "❌ Cannot connect to MySQL server: " . $e2->getMessage() . "<br>";
        echo "<br><strong>Possible solutions:</strong><br>";
        echo "1. Start MySQL/MariaDB service<br>";
        echo "2. Check username/password<br>";
        echo "3. Use empty password for XAMPP default<br>";
    }
}

// Test 4: Database class
echo "<h2>4. Database Class Test</h2>";
if (file_exists('database.php')) {
    echo "✅ database.php exists<br>";
    
    try {
        require_once 'database.php';
        $db = new PPV_Database();
        
        $info = $db->getConnectionInfo();
        
        if ($info['using_database']) {
            echo "✅ <strong>PPV_Database using MySQL!</strong><br>";
            echo "Host: " . $info['host'] . "<br>";
            echo "Database: " . $info['database'] . "<br>";
        } else {
            echo "⚠️ PPV_Database using JSON fallback<br>";
            echo "Reason: " . $info['fallback'] . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Database class error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ database.php not found<br>";
}

echo "<h2>5. Next Steps</h2>";
echo "<p>If everything above shows ✅, your MySQL is working!</p>";
echo "<p><a href='test-system.php'>🔄 Run Full System Test</a></p>";
?>