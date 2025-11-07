<?php
/**
 * test-mysql.php
 * MySQL Migration and Test Script
 * KREIRAJ OVAJ FAJL U ROOT-u za testiranje MySQL-a
 */

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: #28a745; }
.error { color: #dc3545; }
.warning { color: #ffc107; }
.info { color: #17a2b8; }
h1, h2 { color: #333; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
.step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #17a2b8; }
.success-box { background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0; }
.error-box { background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0; }
</style>";

echo "<h1>🗄️ BIF PPV MySQL Setup & Migration</h1>";

// Step 1: Check MySQL Connection
echo "<div class='step'>";
echo "<h2>🔌 Step 1: Testing MySQL Connection</h2>";

try {
    require_once 'database.php';
    $db = new PPV_Database();
    
    $connectionInfo = $db->getConnectionInfo();
    echo "<pre>";
    print_r($connectionInfo);
    echo "</pre>";
    
    if ($connectionInfo['using_database']) {
        echo "<div class='success-box'>";
        echo "<strong>✅ SUCCESS:</strong> MySQL connection working!<br>";
        echo "Host: {$connectionInfo['host']}<br>";
        echo "Database: {$connectionInfo['database']}<br>";
        echo "User: {$connectionInfo['user']}";
        echo "</div>";
        
        $mysqlWorking = true;
    } else {
        echo "<div class='error-box'>";
        echo "<strong>❌ FALLBACK:</strong> Using JSON files instead of MySQL<br>";
        echo "Reason: {$connectionInfo['fallback']}";
        echo "</div>";
        
        $mysqlWorking = false;
    }
    
} catch (Exception $e) {
    echo "<div class='error-box'>";
    echo "<strong>❌ ERROR:</strong> " . $e->getMessage();
    echo "</div>";
    $mysqlWorking = false;
}
echo "</div>";

// Step 2: Check Tables
if ($mysqlWorking) {
    echo "<div class='step'>";
    echo "<h2>📋 Step 2: Checking Database Tables</h2>";
    
    try {
        $tables = ['events', 'purchases', 'access_tokens', 'device_sessions', 'security_violations'];
        
        echo "<table style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='border: 1px solid #ddd; padding: 8px;'>Table</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px;'>Status</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px;'>Row Count</th>";
        echo "</tr>";
        
        foreach ($tables as $table) {
            try {
                $result = $db->fetchOne("SHOW TABLES LIKE '$table'");
                $exists = !empty($result);
                
                if ($exists) {
                    $count = $db->fetchOne("SELECT COUNT(*) as count FROM $table");
                    $rowCount = $count['count'];
                    $status = "✅ Exists";
                } else {
                    $rowCount = "N/A";
                    $status = "❌ Missing";
                }
                
                echo "<tr>";
                echo "<td style='border: 1px solid #ddd; padding: 8px;'>$table</td>";
                echo "<td style='border: 1px solid #ddd; padding: 8px;'>$status</td>";
                echo "<td style='border: 1px solid #ddd; padding: 8px;'>$rowCount</td>";
                echo "</tr>";
                
            } catch (Exception $e) {
                echo "<tr>";
                echo "<td style='border: 1px solid #ddd; padding: 8px;'>$table</td>";
                echo "<td style='border: 1px solid #ddd; padding: 8px;'>❌ Error</td>";
                echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $e->getMessage() . "</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<div class='error-box'>";
        echo "<strong>❌ ERROR checking tables:</strong> " . $e->getMessage();
        echo "</div>";
    }
    echo "</div>";
}

// Step 3: Migration from JSON
if ($mysqlWorking) {
    echo "<div class='step'>";
    echo "<h2>🔄 Step 3: Migration from JSON to MySQL</h2>";
    
    if (isset($_GET['migrate']) && $_GET['migrate'] === 'true') {
        try {
            echo "<p>🚀 Starting migration...</p>";
            
            $result = $db->migrateFromJSON();
            
            if ($result['success']) {
                echo "<div class='success-box'>";
                echo "<strong>✅ MIGRATION SUCCESSFUL!</strong><br>";
                echo "Migrated data:<br>";
                echo "• Events: {$result['migrated']['events']}<br>";
                echo "• Purchases: {$result['migrated']['purchases']}<br>";
                echo "• Access Tokens: {$result['migrated']['access_tokens']}";
                echo "</div>";
            } else {
                echo "<div class='error-box'>";
                echo "<strong>❌ MIGRATION FAILED:</strong> {$result['error']}";
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error-box'>";
            echo "<strong>❌ MIGRATION ERROR:</strong> " . $e->getMessage();
            echo "</div>";
        }
    } else {
        echo "<p>Migration will transfer all data from JSON files to MySQL database.</p>";
        echo "<p><strong>⚠️ Note:</strong> This is safe - JSON files will remain as backup.</p>";
        echo "<a href='?migrate=true' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Start Migration</a>";
    }
    echo "</div>";
}

// Step 4: Performance Test
if ($mysqlWorking) {
    echo "<div class='step'>";
    echo "<h2>⚡ Step 4: Performance Test</h2>";
    
    try {
        // Test database queries
        $start = microtime(true);
        
        $events = $db->getEvents();
        $purchases = $db->getPurchases();
        $accessTokens = $db->getAccessTokens();
        
        $end = microtime(true);
        $executionTime = ($end - $start) * 1000; // Convert to milliseconds
        
        echo "<div class='success-box'>";
        echo "<strong>⚡ Performance Results:</strong><br>";
        echo "• Query execution time: " . number_format($executionTime, 2) . " ms<br>";
        echo "• Events loaded: " . count($events) . "<br>";
        echo "• Purchases loaded: " . count($purchases) . "<br>";
        echo "• Access tokens loaded: " . count($accessTokens);
        echo "</div>";
        
        if ($executionTime < 100) {
            echo "<p class='success'>✅ Excellent performance! Database queries are fast.</p>";
        } elseif ($executionTime < 500) {
            echo "<p class='warning'>⚠️ Good performance. Database queries are acceptable.</p>";
        } else {
            echo "<p class='error'>❌ Slow performance. Consider database optimization.</p>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error-box'>";
        echo "<strong>❌ PERFORMANCE TEST ERROR:</strong> " . $e->getMessage();
        echo "</div>";
    }
    echo "</div>";
}

// Step 5: Verify API Integration
echo "<div class='step'>";
echo "<h2>🌐 Step 5: API Integration Test</h2>";

try {
    $apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/ppv.php?action=events';
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        
        if (isset($data['success']) && $data['success']) {
            echo "<div class='success-box'>";
            echo "<strong>✅ API INTEGRATION WORKING!</strong><br>";
            echo "Events endpoint returning " . count($data['events']) . " events";
            echo "</div>";
            
            echo "<h4>📋 Sample Event Data:</h4>";
            echo "<pre>";
            print_r($data['events'][0] ?? 'No events found');
            echo "</pre>";
        } else {
            echo "<div class='error-box'>";
            echo "<strong>❌ API ERROR:</strong> " . ($data['error'] ?? 'Unknown error');
            echo "</div>";
        }
    } else {
        echo "<div class='error-box'>";
        echo "<strong>❌ API UNREACHABLE:</strong> Cannot connect to API endpoint";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error-box'>";
    echo "<strong>❌ API TEST ERROR:</strong> " . $e->getMessage();
    echo "</div>";
}
echo "</div>";

// Step 6: Configuration Summary
echo "<div class='step'>";
echo "<h2>📊 Configuration Summary</h2>";

if ($mysqlWorking) {
    echo "<div class='success-box'>";
    echo "<h3>✅ MySQL Database Active</h3>";
    echo "<p><strong>Benefits you're now getting:</strong></p>";
    echo "<ul>";
    echo "<li>🚀 <strong>Better Performance:</strong> Faster queries and data access</li>";
    echo "<li>🔒 <strong>Data Integrity:</strong> ACID transactions and referential integrity</li>";
    echo "<li>📈 <strong>Scalability:</strong> Can handle thousands of concurrent users</li>";
    echo "<li>🔍 <strong>Advanced Queries:</strong> Complex analytics and reporting</li>";
    echo "<li>🛡️ <strong>Security:</strong> SQL injection protection and user permissions</li>";
    echo "<li>📊 <strong>Real-time Stats:</strong> Instant dashboard updates</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='warning' style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px;'>";
    echo "<h3>⚠️ Using JSON Fallback</h3>";
    echo "<p><strong>To enable MySQL:</strong></p>";
    echo "<ol>";
    echo "<li>Update <code>env/.env</code> with correct database credentials</li>";
    echo "<li>Ensure MySQL server is running</li>";
    echo "<li>Create database: <code>CREATE DATABASE bif_ppv;</code></li>";
    echo "<li>Refresh this page</li>";
    echo "</ol>";
    echo "</div>";
}
echo "</div>";

// Step 7: Next Steps
echo "<div class='step'>";
echo "<h2>🚀 Next Steps</h2>";

if ($mysqlWorking) {
    echo "<div class='info'>";
    echo "<h3>🎉 MySQL Setup Complete!</h3>";
    echo "<p><strong>Your system is now using MySQL database. Here's what to do next:</strong></p>";
    echo "<ol>";
    echo "<li><strong>Test the system:</strong> <a href='watch.html'>🎬 Test Watch Page</a></li>";
    echo "<li><strong>Check admin panel:</strong> <a href='admin/admin.html'>🛠️ Admin Panel</a></li>";
    echo "<li><strong>Monitor performance:</strong> <a href='api/ppv.php?action=realtime_stats'>📊 Real-time Stats</a></li>";
    echo "<li><strong>Security:</strong> Delete this test file before production</li>";
    echo "<li><strong>Backup:</strong> Set up regular database backups</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div class='warning'>";
    echo "<h3>⚙️ MySQL Setup Required</h3>";
    echo "<p><strong>To complete MySQL setup:</strong></p>";
    echo "<ol>";
    echo "<li>Fix database connection issues shown above</li>";
    echo "<li>Run this test again</li>";
    echo "<li>Complete the migration process</li>";
    echo "</ol>";
    echo "</div>";
}
echo "</div>";

// Performance comparison (if both JSON and MySQL data exist)
if ($mysqlWorking) {
    echo "<div class='step'>";
    echo "<h2>📈 Performance Comparison</h2>";
    
    // JSON performance test
    $jsonStart = microtime(true);
    $jsonEvents = [];
    $jsonFile = 'data/ppv_events.json';
    if (file_exists($jsonFile)) {
        $content = file_get_contents($jsonFile);
        $jsonEvents = json_decode($content, true) ?: [];
    }
    $jsonEnd = microtime(true);
    $jsonTime = ($jsonEnd - $jsonStart) * 1000;
    
    // MySQL performance test
    $mysqlStart = microtime(true);
    $mysqlEvents = $db->getEvents();
    $mysqlEnd = microtime(true);
    $mysqlTime = ($mysqlEnd - $mysqlStart) * 1000;
    
    echo "<table style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Method</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Query Time (ms)</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Records</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Performance</th>";
    echo "</tr>";
    
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>JSON Files</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . number_format($jsonTime, 2) . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . count($jsonEvents) . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>📄 Baseline</td>";
    echo "</tr>";
    
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>MySQL Database</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . number_format($mysqlTime, 2) . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . count($mysqlEvents) . "</td>";
    
    if ($mysqlTime < $jsonTime) {
        $improvement = (($jsonTime - $mysqlTime) / $jsonTime) * 100;
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>🚀 " . number_format($improvement, 1) . "% faster</td>";
    } else {
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>⚡ Comparable</td>";
    }
    echo "</tr>";
    echo "</table>";
    
    echo "</div>";
}

echo "<div style='margin-top: 30px; padding: 15px; background: #e9ecef; border-radius: 5px;'>";
echo "<strong>⚠️ Security Note:</strong> Delete this test file (test-mysql.php) before going to production!";
echo "</div>";
?>