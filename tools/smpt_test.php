<?php
/**
 * SMTP Test Script - Testiranje SMTP konfiguracije
 */

// Učitavanje .env
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

// SMTP konfiguracija
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? '');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');

echo "<h2>SMTP Test</h2>";
echo "<pre>";

// Test 1: Provera konfiguracije
echo "=== SMTP Konfiguracija ===\n";
echo "SMTP_HOST: " . SMTP_HOST . "\n";
echo "SMTP_PORT: " . SMTP_PORT . "\n";
echo "SMTP_USERNAME: " . SMTP_USERNAME . "\n";
echo "SMTP_PASSWORD: " . (SMTP_PASSWORD ? str_repeat('*', strlen(SMTP_PASSWORD)) : 'NIJE POSTAVLJENA') . "\n\n";

// Test 2: DNS provera
echo "=== DNS Provera ===\n";
if (SMTP_HOST) {
    $ip = gethostbyname(SMTP_HOST);
    if ($ip === SMTP_HOST) {
        echo "❌ DNS: Ne mogu da resolviram " . SMTP_HOST . "\n";
        
        // Predlog alternativnih SMTP servera
        echo "\n🔧 Pokušajte sa:\n";
        echo "- smtp.gmail.com (port 587) - za Gmail\n";
        echo "- smtp-mail.outlook.com (port 587) - za Outlook\n";
        echo "- mail.bif.events (port 587) - ako imate hosting\n";
        echo "- smtp.bif.events (port 587) - alternativa\n";
    } else {
        echo "✅ DNS: " . SMTP_HOST . " → " . $ip . "\n";
        
        // Test konekcije
        echo "\n=== Test Konekcije ===\n";
        $socket = @fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);
        if (!$socket) {
            echo "❌ Konekcija: Ne mogu da se povezem na " . SMTP_HOST . ":" . SMTP_PORT . "\n";
            echo "Error: $errstr ($errno)\n";
        } else {
            echo "✅ Konekcija: Uspešno povezan na " . SMTP_HOST . ":" . SMTP_PORT . "\n";
            $response = fgets($socket, 4096);
            echo "Server odgovor: " . trim($response) . "\n";
            fclose($socket);
        }
    }
} else {
    echo "❌ SMTP_HOST nije konfigurisan\n";
}

// Test 3: MX zapisi za bif.events
echo "\n=== MX Zapisi za bif.events ===\n";
if (function_exists('getmxrr')) {
    $mxHosts = [];
    if (getmxrr('bif.events', $mxHosts)) {
        echo "✅ MX zapisi pronađeni:\n";
        foreach ($mxHosts as $mx) {
            echo "  - " . $mx . "\n";
        }
        echo "\n💡 Pokušajte sa prvim MX zapisom kao SMTP_HOST\n";
    } else {
        echo "❌ Nema MX zapisa za bif.events\n";
    }
} else {
    echo "⚠️ getmxrr funkcija nije dostupna\n";
}

echo "</pre>";

// Test slanje email-a
if (isset($_POST['test_email'])) {
    echo "<h3>Test Slanje Email-a</h3>";
    echo "<pre>";
    
    $testEmail = $_POST['email'] ?? 'test@example.com';
    $result = sendTestEmail($testEmail);
    
    if ($result) {
        echo "✅ Email je uspešno poslat na: " . $testEmail . "\n";
    } else {
        echo "❌ Greška pri slanju email-a\n";
    }
    
    echo "</pre>";
}

function sendTestEmail($to) {
    if (!SMTP_HOST || !SMTP_USERNAME || !SMTP_PASSWORD) {
        echo "❌ SMTP nije potpuno konfigurisan\n";
        return false;
    }
    
    $socket = @fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 30);
    if (!$socket) {
        echo "❌ Ne mogu da se povezem: $errstr ($errno)\n";
        return false;
    }
    
    $commands = [
        "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
        "STARTTLS",
        "AUTH LOGIN",
        base64_encode(SMTP_USERNAME),
        base64_encode(SMTP_PASSWORD),
        "MAIL FROM: <" . SMTP_USERNAME . ">",
        "RCPT TO: <{$to}>",
        "DATA"
    ];
    
    // Čitanje pozdravne poruke
    $response = fgets($socket, 4096);
    echo "Server pozdrav: " . trim($response) . "\n";
    
    foreach ($commands as $i => $command) {
        echo "Šaljem: " . ($i >= 3 && $i <= 4 ? '[AUTH DATA]' : $command) . "\n";
        fputs($socket, $command . "\r\n");
        $response = fgets($socket, 4096);
        echo "Odgovor: " . trim($response) . "\n";
        
        if (substr($response, 0, 1) == '5') {
            echo "❌ SMTP greška: " . trim($response) . "\n";
            fclose($socket);
            return false;
        }
    }
    
    // Slanje email sadržaja
    $emailContent = "Subject: SMTP Test Email\r\n";
    $emailContent .= "From: BIF Test <" . SMTP_USERNAME . ">\r\n";
    $emailContent .= "To: {$to}\r\n";
    $emailContent .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $emailContent .= "\r\n";
    $emailContent .= "Ovo je test email sa BIF sajta.\r\n";
    $emailContent .= "Datum: " . date('Y-m-d H:i:s') . "\r\n";
    $emailContent .= ".\r\n";
    
    fputs($socket, $emailContent);
    $response = fgets($socket, 4096);
    echo "Finalni odgovor: " . trim($response) . "\n";
    
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    return substr($response, 0, 1) == '2';
}
?>

<form method="post" style="margin-top: 20px; padding: 20px; border: 1px solid #ccc;">
    <h3>Test Slanje Email-a</h3>
    <label>Email adresa za test:</label><br>
    <input type="email" name="email" value="business@bif.events" style="width: 300px; padding: 5px;"><br><br>
    <button type="submit" name="test_email" style="padding: 10px 20px; background: #007cba; color: white; border: none; cursor: pointer;">Pošalji Test Email</button>
</form>