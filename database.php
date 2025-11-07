<?php
/**
 * database.php
 * Enhanced Database Helper for BIF PPV System
 * Supports both MySQL database and JSON file fallback
 */

class PPV_Database {
    private $pdo;
    private $useDatabase;
    private $logFile;
    
    public function __construct() {
        $this->logFile = dirname(__DIR__) . '/data/database_debug.log';
        $this->createLogDirectory();
        
        // Try to initialize database connection, fallback to JSON
        $this->useDatabase = $this->initializeDatabase();
        $this->log("Database initialized - Using: " . ($this->useDatabase ? 'MySQL' : 'JSON files'));
    }
    
    private function createLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] DB: {$message}" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    private function initializeDatabase() {
        try {
            // Load environment variables
            $this->loadEnvIfNeeded();
            
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $dbname = $_ENV['DB_NAME'] ?? 'bif_ppv';
            $username = $_ENV['DB_USER'] ?? 'root';
            $password = $_ENV['DB_PASS'] ?? '';
            
            $this->log("Attempting database connection - Host: $host, DB: $dbname, User: $username");
            
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            
            // Test connection
            $this->pdo->query("SELECT 1");
            
            // Check if tables exist, create if needed
            $this->createTablesIfNeeded();
            
            $this->log("✅ Database connection successful");
            return true;
            
        } catch (Exception $e) {
            $this->log("❌ Database connection failed: " . $e->getMessage());
            $this->log("📄 Falling back to JSON files");
            return false;
        }
    }
    
    private function loadEnvIfNeeded() {
        // Load .env if not already loaded
        if (empty($_ENV['DB_HOST'])) {
            $envFile = dirname(__FILE__) . '/env/.env';
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) continue;
                    if (strpos($line, '=') === false) continue;
                    list($name, $value) = explode('=', $line, 2);
                    $_ENV[trim($name)] = trim($value, '"\'');
                }
            }
        }
    }
    
    private function createTablesIfNeeded() {
        $tables = [
            'events' => "
                CREATE TABLE IF NOT EXISTS events (
                    id VARCHAR(50) PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    date DATETIME NOT NULL,
                    price INT NOT NULL COMMENT 'Price in cents',
                    currency VARCHAR(3) DEFAULT 'rsd',
                    early_bird_price INT,
                    early_bird_until DATETIME,
                    stream_url TEXT,
                    poster_image VARCHAR(500),
                    status ENUM('upcoming', 'live', 'finished') DEFAULT 'upcoming',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    INDEX idx_status (status),
                    INDEX idx_date (date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'purchases' => "
                CREATE TABLE IF NOT EXISTS purchases (
                    id VARCHAR(50) PRIMARY KEY,
                    event_id VARCHAR(50) NOT NULL,
                    customer_name VARCHAR(255) NOT NULL,
                    customer_email VARCHAR(255) NOT NULL,
                    amount INT NOT NULL COMMENT 'Amount in cents',
                    currency VARCHAR(3) DEFAULT 'rsd',
                    payment_intent_id VARCHAR(255),
                    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    access_expires_at DATETIME,
                    refunded_at TIMESTAMP NULL,
                    
                    INDEX idx_customer_email (customer_email),
                    INDEX idx_status (status),
                    INDEX idx_event_id (event_id),
                    INDEX idx_payment_intent (payment_intent_id),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'access_tokens' => "
                CREATE TABLE IF NOT EXISTS access_tokens (
                    token VARCHAR(64) PRIMARY KEY,
                    purchase_id VARCHAR(50) NOT NULL,
                    event_id VARCHAR(50) NOT NULL,
                    customer_email VARCHAR(255) NOT NULL,
                    device_fingerprint VARCHAR(64),
                    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    expires_at DATETIME NOT NULL,
                    last_accessed TIMESTAMP NULL,
                    access_count INT DEFAULT 0,
                    max_concurrent_devices INT DEFAULT 1,
                    is_active BOOLEAN DEFAULT TRUE,
                    
                    INDEX idx_customer_email (customer_email),
                    INDEX idx_event_id (event_id),
                    INDEX idx_expires_at (expires_at),
                    INDEX idx_is_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'device_sessions' => "
                CREATE TABLE IF NOT EXISTS device_sessions (
                    id BIGINT AUTO_INCREMENT PRIMARY KEY,
                    token VARCHAR(64) NOT NULL,
                    device_id VARCHAR(64) NOT NULL,
                    device_fingerprint TEXT,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_heartbeat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    ended_at TIMESTAMP NULL,
                    violation_count INT DEFAULT 0,
                    
                    INDEX idx_token (token),
                    INDEX idx_device_id (device_id),
                    INDEX idx_last_heartbeat (last_heartbeat),
                    UNIQUE KEY unique_active_device (token, device_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'security_violations' => "
                CREATE TABLE IF NOT EXISTS security_violations (
                    id BIGINT AUTO_INCREMENT PRIMARY KEY,
                    token VARCHAR(64),
                    device_id VARCHAR(64),
                    violation_type VARCHAR(100) NOT NULL,
                    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
                    details JSON,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    
                    INDEX idx_token (token),
                    INDEX idx_violation_type (violation_type),
                    INDEX idx_severity (severity),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];
        
        foreach ($tables as $tableName => $sql) {
            try {
                $this->pdo->exec($sql);
                $this->log("✅ Table '$tableName' ready");
            } catch (Exception $e) {
                $this->log("❌ Error creating table '$tableName': " . $e->getMessage());
                throw $e;
            }
        }
        
        // Insert default event if none exist
        $this->insertDefaultEventIfNeeded();
    }
    
    private function insertDefaultEventIfNeeded() {
        try {
            $count = $this->pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
            
            if ($count == 0) {
                $this->log("📄 Inserting default event...");
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO events (id, title, description, date, price, currency, early_bird_price, early_bird_until, stream_url, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    'bif-1-new-rise',
                    'BIF 1: New Rise',
                    'Najveći influenser fight show na Balkanu',
                    '2025-07-21 20:00:00',
                    199900, // 1999.00 RSD in cents
                    'rsd',
                    149900, // 1499.00 RSD in cents
                    '2025-07-15 23:59:59',
                    'https://vimeo.com/1017406920?fl=pl&fe=sh',
                    'upcoming'
                ]);
                
                $this->log("✅ Default event inserted");
            }
        } catch (Exception $e) {
            $this->log("❌ Error inserting default event: " . $e->getMessage());
        }
    }
    
    // Universal methods - use database or JSON
    public function getPurchases() {
        if ($this->useDatabase) {
            return $this->getDatabasePurchases();
        } else {
            return $this->getJSONPurchases();
        }
    }
    
    public function savePurchase($purchase) {
        if ($this->useDatabase) {
            return $this->saveDatabasePurchase($purchase);
        } else {
            return $this->saveJSONPurchase($purchase);
        }
    }
    
    public function getAccessTokens() {
        if ($this->useDatabase) {
            return $this->getDatabaseAccessTokens();
        } else {
            return $this->getJSONAccessTokens();
        }
    }
    
    public function saveAccessToken($token) {
        if ($this->useDatabase) {
            return $this->saveDatabaseAccessToken($token);
        } else {
            return $this->saveJSONAccessToken($token);
        }
    }
    
    public function getEvents() {
        if ($this->useDatabase) {
            return $this->getDatabaseEvents();
        } else {
            return $this->getJSONEvents();
        }
    }
    
    public function saveEvent($event) {
        if ($this->useDatabase) {
            return $this->saveDatabaseEvent($event);
        } else {
            return $this->saveJSONEvent($event);
        }
    }
    
    // DATABASE METHODS
    private function getDatabasePurchases() {
        $stmt = $this->pdo->prepare("SELECT * FROM purchases ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    private function saveDatabasePurchase($purchase) {
        $stmt = $this->pdo->prepare("
            INSERT INTO purchases (id, event_id, customer_name, customer_email, amount, currency, 
                                 payment_intent_id, status, ip_address, user_agent, created_at, access_expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                access_expires_at = VALUES(access_expires_at)
        ");
        
        return $stmt->execute([
            $purchase['id'],
            $purchase['event_id'],
            $purchase['customer_name'],
            $purchase['customer_email'],
            $purchase['amount'],
            $purchase['currency'] ?? 'rsd',
            $purchase['payment_intent_id'] ?? null,
            $purchase['status'] ?? 'completed',
            $purchase['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $purchase['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
    
    private function getDatabaseAccessTokens() {
        $stmt = $this->pdo->prepare("SELECT * FROM access_tokens WHERE is_active = 1 ORDER BY granted_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    private function saveDatabaseAccessToken($token) {
        $stmt = $this->pdo->prepare("
            INSERT INTO access_tokens (token, purchase_id, event_id, customer_email, device_fingerprint, expires_at, granted_at, max_concurrent_devices)
            VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), ?)
            ON DUPLICATE KEY UPDATE
                last_accessed = NOW(),
                access_count = access_count + 1
        ");
        
        return $stmt->execute([
            $token['token'],
            $token['purchase_id'],
            $token['event_id'],
            $token['customer_email'],
            $token['device_fingerprint'] ?? null,
            $token['max_concurrent_devices'] ?? 1
        ]);
    }
    
    private function getDatabaseEvents() {
        $stmt = $this->pdo->prepare("SELECT * FROM events ORDER BY date ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    private function saveDatabaseEvent($event) {
        $stmt = $this->pdo->prepare("
            INSERT INTO events (id, title, description, date, price, currency, early_bird_price, early_bird_until, stream_url, poster_image, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                description = VALUES(description),
                date = VALUES(date),
                price = VALUES(price),
                currency = VALUES(currency),
                early_bird_price = VALUES(early_bird_price),
                early_bird_until = VALUES(early_bird_until),
                stream_url = VALUES(stream_url),
                poster_image = VALUES(poster_image),
                status = VALUES(status),
                updated_at = NOW()
        ");
        
        return $stmt->execute([
            $event['id'],
            $event['title'],
            $event['description'],
            $event['date'],
            $event['price'],
            $event['currency'] ?? 'rsd',
            $event['early_bird_price'] ?? null,
            $event['early_bird_until'] ?? null,
            $event['stream_url'] ?? null,
            $event['poster_image'] ?? null,
            $event['status'] ?? 'upcoming'
        ]);
    }
    
    // JSON FALLBACK METHODS
    private function getJSONPurchases() {
        $file = dirname(__FILE__) . '/data/ppv_purchases.json';
        if (!file_exists($file)) return [];
        $content = file_get_contents($file);
        return json_decode($content, true) ?: [];
    }
    
    private function saveJSONPurchase($purchase) {
        $file = dirname(__FILE__) . '/data/ppv_purchases.json';
        $purchases = $this->getJSONPurchases();
        
        // Check if purchase already exists
        $found = false;
        foreach ($purchases as &$existing) {
            if ($existing['id'] === $purchase['id']) {
                $existing = array_merge($existing, $purchase);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $purchases[] = $purchase;
        }
        
        return file_put_contents($file, json_encode($purchases, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    private function getJSONAccessTokens() {
        $file = dirname(__FILE__) . '/data/ppv_access.json';
        if (!file_exists($file)) return [];
        $content = file_get_contents($file);
        return json_decode($content, true) ?: [];
    }
    
    private function saveJSONAccessToken($token) {
        $file = dirname(__FILE__) . '/data/ppv_access.json';
        $tokens = $this->getJSONAccessTokens();
        
        // Check if token already exists
        $found = false;
        foreach ($tokens as &$existing) {
            if ($existing['token'] === $token['token']) {
                $existing = array_merge($existing, $token);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $tokens[] = $token;
        }
        
        return file_put_contents($file, json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    private function getJSONEvents() {
        $file = dirname(__FILE__) . '/data/ppv_events.json';
        if (!file_exists($file)) {
            // Create default event
            $defaultEvents = [
                [
                    'id' => 'bif-1-new-rise',
                    'title' => 'BIF 1: New Rise',
                    'description' => 'Najveći influenser fight show na Balkanu',
                    'date' => '2025-07-21 20:00:00',
                    'price' => 199900,
                    'currency' => 'rsd',
                    'early_bird_price' => 149900,
                    'early_bird_until' => '2025-07-15 23:59:59',
                    'stream_url' => 'https://vimeo.com/1017406920?fl=pl&fe=sh',
                    'poster_image' => '/assets/images/events/bif-1-poster.png',
                    'status' => 'upcoming'
                ]
            ];
            file_put_contents($file, json_encode($defaultEvents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        
        $content = file_get_contents($file);
        return json_decode($content, true) ?: [];
    }
    
    private function saveJSONEvent($event) {
        $file = dirname(__FILE__) . '/data/ppv_events.json';
        $events = $this->getJSONEvents();
        
        // Check if event already exists
        $found = false;
        foreach ($events as &$existing) {
            if ($existing['id'] === $event['id']) {
                $existing = array_merge($existing, $event);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $events[] = $event;
        }
        
        return file_put_contents($file, json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    // Helper methods for both database and JSON
    public function query($sql, $params = []) {
        if (!$this->useDatabase) {
            throw new Exception("Database not available for custom queries. Using JSON fallback.");
        }
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (Exception $e) {
            $this->log("❌ Query error: " . $e->getMessage() . " | SQL: $sql");
            throw $e;
        }
    }
    
    public function fetchOne($sql, $params = []) {
        if (!$this->useDatabase) {
            throw new Exception("Database not available for custom queries. Using JSON fallback.");
        }
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        if (!$this->useDatabase) {
            throw new Exception("Database not available for custom queries. Using JSON fallback.");
        }
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function lastInsertId() {
        if (!$this->useDatabase) {
            throw new Exception("Database not available for lastInsertId. Using JSON fallback.");
        }
        
        return $this->pdo->lastInsertId();
    }
    
    public function beginTransaction() {
        if ($this->useDatabase) {
            return $this->pdo->beginTransaction();
        }
        return true; // JSON doesn't need transactions
    }
    
    public function commit() {
        if ($this->useDatabase) {
            return $this->pdo->commit();
        }
        return true;
    }
    
    public function rollBack() {
        if ($this->useDatabase) {
            return $this->pdo->rollBack();
        }
        return true;
    }
    
    // Utility methods
    public function isUsingDatabase() {
        return $this->useDatabase;
    }
    
    public function getConnectionInfo() {
        return [
            'using_database' => $this->useDatabase,
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'database' => $_ENV['DB_NAME'] ?? 'bif_ppv',
            'user' => $_ENV['DB_USER'] ?? 'root',
            'fallback' => !$this->useDatabase ? 'JSON files' : null
        ];
    }
    
    public function testConnection() {
        if ($this->useDatabase) {
            try {
                $result = $this->pdo->query("SELECT 1 as test")->fetch();
                return [
                    'success' => true,
                    'type' => 'database',
                    'test_result' => $result['test'] ?? null
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'type' => 'database',
                    'error' => $e->getMessage()
                ];
            }
        } else {
            $testFile = dirname(__FILE__) . '/data/test.json';
            $testData = ['test' => true, 'timestamp' => time()];
            
            if (file_put_contents($testFile, json_encode($testData))) {
                unlink($testFile);
                return [
                    'success' => true,
                    'type' => 'json',
                    'message' => 'JSON file operations working'
                ];
            } else {
                return [
                    'success' => false,
                    'type' => 'json',
                    'error' => 'Cannot write to data directory'
                ];
            }
        }
    }
    
    // Cleanup method
    public function cleanup() {
        if ($this->useDatabase) {
            try {
                // Clean old device sessions
                $this->pdo->exec("DELETE FROM device_sessions WHERE ended_at IS NOT NULL AND ended_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
                
                // Clean old security violations
                $this->pdo->exec("DELETE FROM security_violations WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
                
                $this->log("✅ Database cleanup completed");
            } catch (Exception $e) {
                $this->log("❌ Database cleanup failed: " . $e->getMessage());
            }
        }
    }
    
    // Enhanced security methods
    public function logSecurityViolation($token, $deviceId, $type, $details = []) {
        if ($this->useDatabase) {
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO security_violations (token, device_id, violation_type, details, ip_address, user_agent, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $token,
                    $deviceId,
                    $type,
                    json_encode($details),
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
                
                $this->log("🚨 Security violation logged: $type");
            } catch (Exception $e) {
                $this->log("❌ Failed to log security violation: " . $e->getMessage());
            }
        } else {
            // JSON fallback for security violations
            $file = dirname(__FILE__) . '/data/security_violations.json';
            $violations = [];
            
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $violations = json_decode($content, true) ?: [];
            }
            
            $violations[] = [
                'token' => $token,
                'device_id' => $deviceId,
                'violation_type' => $type,
                'details' => $details,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            file_put_contents($file, json_encode($violations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->log("🚨 Security violation logged to JSON: $type");
        }
    }
    
    public function getSecurityViolations($limit = 50) {
        if ($this->useDatabase) {
            $stmt = $this->pdo->prepare("SELECT * FROM security_violations ORDER BY created_at DESC LIMIT ?");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } else {
            $file = dirname(__FILE__) . '/data/security_violations.json';
            if (!file_exists($file)) return [];
            
            $content = file_get_contents($file);
            $violations = json_decode($content, true) ?: [];
            
            // Sort by created_at descending and limit
            usort($violations, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            return array_slice($violations, 0, $limit);
        }
    }
    
    // Migration helper (for moving from JSON to database)
    public function migrateFromJSON() {
        if (!$this->useDatabase) {
            throw new Exception("Database connection required for migration");
        }
        
        $this->log("🔄 Starting migration from JSON to database...");
        
        try {
            $this->beginTransaction();
            
            // Migrate events
            $jsonEvents = $this->getJSONEvents();
            foreach ($jsonEvents as $event) {
                $this->saveDatabaseEvent($event);
            }
            $this->log("✅ Migrated " . count($jsonEvents) . " events");
            
            // Migrate purchases
            $jsonPurchases = $this->getJSONPurchases();
            foreach ($jsonPurchases as $purchase) {
                $this->saveDatabasePurchase($purchase);
            }
            $this->log("✅ Migrated " . count($jsonPurchases) . " purchases");
            
            // Migrate access tokens
            $jsonTokens = $this->getJSONAccessTokens();
            foreach ($jsonTokens as $token) {
                $this->saveDatabaseAccessToken($token);
            }
            $this->log("✅ Migrated " . count($jsonTokens) . " access tokens");
            
            $this->commit();
            $this->log("🎉 Migration completed successfully!");
            
            return [
                'success' => true,
                'migrated' => [
                    'events' => count($jsonEvents),
                    'purchases' => count($jsonPurchases),
                    'access_tokens' => count($jsonTokens)
                ]
            ];
            
        } catch (Exception $e) {
            $this->rollBack();
            $this->log("❌ Migration failed: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>