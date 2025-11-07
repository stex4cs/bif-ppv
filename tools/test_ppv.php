<?php
/**
 * test_api.php - Za testiranje API povezanosti
 * Staviti u root direktorij za debug
 */

header('Content-Type: text/html; charset=UTF-8');

echo "<h1>🔧 BIF API Test</h1>";

// Test putanja
$testUrls = [
    '/api/ppv.php?action=config',
    './api/ppv.php?action=config',
    'api/ppv.php?action=config',
    '/ppv.php?action=config'
];

echo "<h2>🌐 URL Testovi:</h2>";

foreach ($testUrls as $url) {
    echo "<div style='padding: 10px; margin: 5px; border: 1px solid #ccc;'>";
    echo "<strong>Testing: $url</strong><br>";
    
    // Test sa file_exists
    $filePath = ltrim($url, '/');
    $filePath = explode('?', $filePath)[0]; // ukloni query string
    
    if (file_exists($filePath)) {
        echo "✅ File exists: $filePath<br>";
        
        // Test sa cURL
        $fullUrl = "http://" . $_SERVER['HTTP_HOST'] . $url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true); // samo headers
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo "❌ cURL Error: $error<br>";
        } else {
            if ($httpCode == 200) {
                echo "✅ HTTP Response: $httpCode (OK)<br>";
            } else {
                echo "⚠️ HTTP Response: $httpCode<br>";
            }
        }
        
    } else {
        echo "❌ File not found: $filePath<br>";
    }
    
    echo "</div>";
}

// Test direktorijuma
echo "<h2>📁 Direktorij Test:</h2>";
$dirs = ['api', 'admin', 'data', 'env'];

foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "✅ Directory exists: /$dir/<br>";
        
        // Lista fajlova
        $files = scandir($dir);
        $files = array_filter($files, function($f) { return $f !== '.' && $f !== '..'; });
        
        if (!empty($files)) {
            echo "&nbsp;&nbsp;&nbsp;Files: " . implode(', ', $files) . "<br>";
        }
    } else {
        echo "❌ Directory missing: /$dir/<br>";
    }
}

// Test .env
echo "<h2>⚙️ Environment Test:</h2>";
if (file_exists('env/.env')) {
    echo "✅ env/.env file exists<br>";
    
    $envContent = file_get_contents('env/.env');
    $lines = explode("\n", $envContent);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            
            if (in_array($key, ['STRIPE_PUBLISHABLE_KEY', 'STRIPE_SECRET_KEY'])) {
                $maskedValue = substr($value, 0, 8) . '***' . substr($value, -4);
                echo "&nbsp;&nbsp;&nbsp;$key = $maskedValue<br>";
            } else {
                echo "&nbsp;&nbsp;&nbsp;$key = " . (strlen($value) > 0 ? '✅' : '❌') . "<br>";
            }
        }
    }
} else {
    echo "❌ env/.env file missing<br>";
}

// Test permissions
echo "<h2>🔐 Permissions Test:</h2>";
$checkPaths = ['api/', 'api/ppv.php', 'admin/', 'data/'];

foreach ($checkPaths as $path) {
    if (file_exists($path)) {
        $perms = fileperms($path);
        $readable = is_readable($path) ? '✅' : '❌';
        $writable = is_writable($path) ? '✅' : '❌';
        echo "$path - Read: $readable Write: $writable Perms: " . substr(sprintf('%o', $perms), -4) . "<br>";
    }
}

echo "<h2>🔗 JavaScript Test:</h2>";
?>

<script>
async function testAPIConnectivity() {
    const results = document.getElementById('js-results');
    const urls = ['/api/ppv.php?action=config', './api/ppv.php?action=config', 'api/ppv.php?action=config'];
    
    results.innerHTML = '<h3>JavaScript fetch testovi:</h3>';
    
    for (let url of urls) {
        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            const statusText = response.ok ? '✅' : '❌';
            results.innerHTML += `<div>${statusText} ${url}: ${response.status} ${response.statusText}</div>`;
            
            if (response.ok) {
                const data = await response.json();
                results.innerHTML += `<div>&nbsp;&nbsp;&nbsp;Response: ${JSON.stringify(data, null, 2)}</div>`;
            }
            
        } catch (error) {
            results.innerHTML += `<div>❌ ${url}: ${error.message}</div>`;
        }
    }
}

document.addEventListener('DOMContentLoaded', testAPIConnectivity);
</script>

<div id="js-results"></div>

<h2>📝 Next Steps:</h2>
<ol>
    <li>Ako vidite ❌ za api/ppv.php - kreirajte api/ folder i kopirajte ppv.php tamo</li>
    <li>Ako vidite ❌ za HTTP Response - proverite .htaccess konfiguraciju</li>
    <li>Ako vidite ❌ za JavaScript fetch - proverite CORS headers</li>
    <li>Ako sve pokazuje ✅ - problem je verovatno u Stripe konfiguraciji</li>
</ol>

<p><strong>Server Info:</strong><br>
PHP Version: <?= phpversion() ?><br>
Server Software: <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?><br>
Document Root: <?= $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown' ?><br>
Current Dir: <?= __DIR__ ?><br>
</p>