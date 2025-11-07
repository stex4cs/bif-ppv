<?php
/**
 * Debug PPV System Issues
 * debug_ppv.php - Detaljno debugovanje PPV sistema
 */

// Load environment
function loadEnv() {
    $envFile = __DIR__ . '/.env';
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

loadEnv();

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>🔍 PPV Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        .key-display { font-family: monospace; background: #f8f9fa; padding: 5px; border-radius: 3px; }
        .test-btn { background: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .test-btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 BIF PPV Debug Panel</h1>
        
        <!-- Environment Variables Check -->
        <div class="section">
            <h2>1. Environment Variables</h2>
            <?php
            $stripeKeys = [
                'STRIPE_PUBLISHABLE_KEY' => $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? 'NOT SET',
                'STRIPE_SECRET_KEY' => $_ENV['STRIPE_SECRET_KEY'] ?? 'NOT SET',
                'STRIPE_WEBHOOK_SECRET' => $_ENV['STRIPE_WEBHOOK_SECRET'] ?? 'NOT SET'
            ];
            
            foreach ($stripeKeys as $key => $value) {
                $class = $value === 'NOT SET' ? 'error' : 'success';
                $displayValue = $value === 'NOT SET' ? $value : substr($value, 0, 20) . '...';
                echo "<p class='$class'><strong>$key:</strong> <span class='key-display'>$displayValue</span></p>";
            }
            ?>
        </div>
        
        <!-- Stripe API Test -->
        <div class="section">
            <h2>2. Stripe API Test</h2>
            <?php
            if (!empty($_ENV['STRIPE_SECRET_KEY'])) {
                echo "<p><strong>Testing Stripe API sa ključem:</strong> " . substr($_ENV['STRIPE_SECRET_KEY'], 0, 12) . "...</p>";
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/balance');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $_ENV['STRIPE_SECRET_KEY'],
                    'User-Agent: BIF-PPV-Debug/1.0'
                ]);
                curl_setopt($ch, CURLOPT_VERBOSE, true);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                echo "<h3>Stripe API Response:</h3>";
                echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
                
                if ($curlError) {
                    echo "<div class='error'><strong>cURL Error:</strong> $curlError</div>";
                } else {
                    if ($httpCode === 200) {
                        echo "<div class='success'>✅ Stripe API konekcija uspešna!</div>";
                        $data = json_decode($response, true);
                        echo "<pre>" . print_r($data, true) . "</pre>";
                    } else {
                        echo "<div class='error'>❌ Stripe API greška (HTTP $httpCode)</div>";
                        echo "<pre>Response: " . htmlspecialchars($response) . "</pre>";
                        
                        // Pokušaj sa account endpoint-om
                        echo "<h4>Pokušavam sa /v1/account endpoint:</h4>";
                        $ch2 = curl_init();
                        curl_setopt($ch2, CURLOPT_URL, 'https://api.stripe.com/v1/account');
                        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
                        curl_setopt($ch2, CURLOPT_HTTPHEADER, [
                            'Authorization: Bearer ' . $_ENV['STRIPE_SECRET_KEY']
                        ]);
                        
                        $response2 = curl_exec($ch2);
                        $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                        curl_close($ch2);
                        
                        echo "<p><strong>Account HTTP Code:</strong> $httpCode2</p>";
                        if ($httpCode2 === 200) {
                            echo "<div class='success'>✅ Account endpoint radi!</div>";
                        } else {
                            echo "<div class='error'>❌ Account endpoint takođe ne radi</div>";
                            echo "<pre>Account Response: " . htmlspecialchars($response2) . "</pre>";
                        }
                    }
                }
            } else {
                echo "<div class='error'>❌ STRIPE_SECRET_KEY nije postavljen</div>";
            }
            ?>
        </div>
        
        <!-- PPV.php Direct Test -->
        <div class="section">
            <h2>3. PPV.php Direct Test</h2>
            <?php
            // Test ppv.php direktno
            echo "<button class='test-btn' onclick='testPPVConfig()'>Test Config Endpoint</button>";
            echo "<button class='test-btn' onclick='testPPVEvents()'>Test Events Endpoint</button>";
            echo "<div id='ppv-test-results'></div>";
            ?>
        </div>
        
        <!-- File Permissions -->
        <div class="section">
            <h2>4. File Permissions</h2>
            <?php
            $files = [
                'ppv.php' => __DIR__ . '/ppv.php',
                'watch.html' => __DIR__ . '/watch.html',
                '.env' => __DIR__ . '/.env',
                'data/' => __DIR__ . '/data'
            ];
            
            foreach ($files as $name => $path) {
                if (file_exists($path)) {
                    $perms = substr(sprintf('%o', fileperms($path)), -4);
                    $readable = is_readable($path) ? '✅' : '❌';
                    $writable = is_writable($path) ? '✅' : '❌';
                    echo "<p><strong>$name:</strong> Permissions: $perms | Readable: $readable | Writable: $writable</p>";
                } else {
                    echo "<p class='error'><strong>$name:</strong> ❌ Ne postoji</p>";
                }
            }
            ?>
        </div>
        
        <!-- PHP Configuration -->
        <div class="section">
            <h2>5. PHP Configuration</h2>
            <pre><?php
            echo "PHP Version: " . phpversion() . "\n";
            echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'ON' : 'OFF') . "\n";
            echo "curl enabled: " . (extension_loaded('curl') ? 'YES' : 'NO') . "\n";
            echo "openssl enabled: " . (extension_loaded('openssl') ? 'YES' : 'NO') . "\n";
            echo "json enabled: " . (extension_loaded('json') ? 'YES' : 'NO') . "\n";
            echo "mbstring enabled: " . (extension_loaded('mbstring') ? 'YES' : 'NO') . "\n";
            echo "display_errors: " . ini_get('display_errors') . "\n";
            echo "log_errors: " . ini_get('log_errors') . "\n";
            echo "error_log: " . ini_get('error_log') . "\n";
            ?></pre>
        </div>
        
        <!-- Manual PPV Test -->
        <div class="section">
            <h2>6. Manual PPV Class Test</h2>
            <button class='test-btn' onclick='testPPVClass()'>Test PPV Class Directly</button>
            <div id='class-test-results'></div>
            
            <?php
            // Pokušaj učitavanje PPV klase direktno
            if (file_exists(__DIR__ . '/ppv.php')) {
                echo "<h4>Pokušavam učitavanje PPV klase...</h4>";
                try {
                    // Simulate POST request environment
                    $_SERVER['REQUEST_METHOD'] = 'GET';
                    $_GET['action'] = 'config';
                    
                    ob_start();
                    include __DIR__ . '/ppv.php';
                    $output = ob_get_clean();
                    
                    echo "<div class='success'>✅ PPV.php se učitava bez grešaka</div>";
                    echo "<h5>Output:</h5>";
                    echo "<pre>" . htmlspecialchars($output) . "</pre>";
                    
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Greška pri učitavanju PPV.php: " . $e->getMessage() . "</div>";
                } catch (Error $e) {
                    echo "<div class='error'>❌ PHP Error pri učitavanju PPV.php: " . $e->getMessage() . "</div>";
                }
            }
            ?>
        </div>
        
        <!-- Error Logs -->
        <div class="section">
            <h2>7. Error Logs</h2>
            <?php
            $logFiles = [
                'PHP Error Log' => ini_get('error_log'),
                'PPV Debug Log' => __DIR__ . '/data/ppv_debug.log',
                'Apache Error Log' => '/var/log/apache2/error.log'
            ];
            
            foreach ($logFiles as $name => $path) {
                if ($path && file_exists($path) && is_readable($path)) {
                    echo "<h4>$name:</h4>";
                    $lines = array_slice(file($path), -10); // Last 10 lines
                    echo "<pre>" . htmlspecialchars(implode('', $lines)) . "</pre>";
                } else {
                    echo "<p><strong>$name:</strong> Ne mogu pristupiti ($path)</p>";
                }
            }
            ?>
        </div>
    </div>

    <script>
        async function testPPVConfig() {
            const results = document.getElementById('ppv-test-results');
            results.innerHTML = '<p>Testing config endpoint...</p>';
            
            try {
                const response = await fetch('/ppv.php?action=config', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                const responseText = await response.text();
                results.innerHTML = `
                    <h4>Config Response:</h4>
                    <p><strong>Status:</strong> ${response.status}</p>
                    <p><strong>Content-Type:</strong> ${response.headers.get('content-type')}</p>
                    <pre>${responseText}</pre>
                `;
                
                // Try to parse as JSON
                try {
                    const data = JSON.parse(responseText);
                    results.innerHTML += `<h5>Parsed JSON:</h5><pre>${JSON.stringify(data, null, 2)}</pre>`;
                } catch (e) {
                    results.innerHTML += `<div class="error">Failed to parse as JSON: ${e.message}</div>`;
                }
                
            } catch (error) {
                results.innerHTML = `<div class="error">Fetch Error: ${error.message}</div>`;
            }
        }
        
        async function testPPVEvents() {
            const results = document.getElementById('ppv-test-results');
            results.innerHTML = '<p>Testing events endpoint...</p>';
            
            try {
                const response = await fetch('/ppv.php?action=events');
                const responseText = await response.text();
                
                results.innerHTML = `
                    <h4>Events Response:</h4>
                    <p><strong>Status:</strong> ${response.status}</p>
                    <pre>${responseText}</pre>
                `;
                
            } catch (error) {
                results.innerHTML = `<div class="error">Fetch Error: ${error.message}</div>`;
            }
        }
        
        function testPPVClass() {
            const results = document.getElementById('class-test-results');
            results.innerHTML = '<p>Check the PHP output above for class test results...</p>';
        }
    </script>
</body>
</html>