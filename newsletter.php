<?php
/**
 * BIF Newsletter System - RADNA VERZIJA sa ispravnim SMTP
 * newsletter.php - Koristi JSON fajlove i radni SMTP
 */

// Učitavanje .env fajla
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

// Konfiguracija
$debugMode = $_ENV['DEBUG_MODE'] ?? 'false';
ini_set('display_errors', $debugMode === 'true' ? 1 : 0);
error_reporting(E_ALL);

// Konstante iz .env
define('SUBSCRIBERS_FILE', __DIR__ . '/data/subscribers.json');
define('RATE_LIMIT_FILE', __DIR__ . '/data/newsletter_rate_limits.json');
define('FROM_EMAIL', $_ENV['FROM_EMAIL'] ?? 'business@bif.events');
define('FROM_NAME', $_ENV['FROM_NAME'] ?? 'BIF - Balkan Influence Fighting');
define('SITE_URL', $_ENV['SITE_URL'] ?? 'https://bif.events');

// SMTP konfiguracija
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? '');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION'] ?? 'tls');

// RADNA SMTP klasa (ista kao u contact.php)
class WorkingNewsletterSMTP {
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption;
    private $debugMode;
    
    public function __construct($host, $port, $username, $password, $encryption = 'tls', $debugMode = false) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->encryption = $encryption;
        $this->debugMode = $debugMode;
    }
    
    public function sendEmail($to, $subject, $message, $fromEmail = null, $fromName = null) {
        $fromEmail = $fromEmail ?: $this->username;
        $fromName = $fromName ?: 'BIF Newsletter';
        
        try {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
            
            $socket = @stream_socket_client(
                "tcp://{$this->host}:{$this->port}",
                $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context
            );
            
            if (!$socket) {
                if ($this->debugMode) {
                    error_log("Newsletter SMTP Connection failed: $errstr ($errno)");
                }
                return false;
            }
            
            // SMTP komunikacija - ISTA kao u radnom contact.php
            fgets($socket, 4096); // greeting
            
            fwrite($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'bif.events') . "\r\n");
            fgets($socket, 4096);
            
            // STARTTLS za TLS enkriptaciju
            if ($this->encryption === 'tls') {
                fwrite($socket, "STARTTLS\r\n");
                $response = fgets($socket, 4096);
                
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    fclose($socket);
                    return false;
                }
                
                // EHLO ponovo nakon TLS
                fwrite($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'bif.events') . "\r\n");
                fgets($socket, 4096);
            }
            
            // Autentifikacija
            fwrite($socket, "AUTH LOGIN\r\n");
            fgets($socket, 4096);
            
            fwrite($socket, base64_encode($this->username) . "\r\n");
            fgets($socket, 4096);
            
            fwrite($socket, base64_encode($this->password) . "\r\n");
            $auth_response = fgets($socket, 4096);
            
            if (substr($auth_response, 0, 3) !== '235') {
                fclose($socket);
                return false;
            }
            
            // Slanje email-a
            fwrite($socket, "MAIL FROM: <{$fromEmail}>\r\n");
            fgets($socket, 4096);
            
            fwrite($socket, "RCPT TO: <{$to}>\r\n");
            fgets($socket, 4096);
            
            fwrite($socket, "DATA\r\n");
            fgets($socket, 4096);
            
            $emailContent = "Subject: {$subject}\r\n";
            $emailContent .= "From: {$fromName} <{$fromEmail}>\r\n";
            $emailContent .= "To: {$to}\r\n";
            $emailContent .= "Date: " . date('r') . "\r\n";
            $emailContent .= "Message-ID: <" . md5(uniqid()) . "@{$this->host}>\r\n";
            $emailContent .= "MIME-Version: 1.0\r\n";
            $emailContent .= "Content-Type: text/html; charset=UTF-8\r\n";
            $emailContent .= "Content-Transfer-Encoding: 8bit\r\n";
            $emailContent .= "\r\n";
            $emailContent .= $message . "\r\n.\r\n";
            
            fwrite($socket, $emailContent);
            $response = fgets($socket, 4096);
            
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            
            return substr($response, 0, 3) === '250';
            
        } catch (Exception $e) {
            if ($this->debugMode) {
                error_log("Newsletter SMTP Error: " . $e->getMessage());
            }
            return false;
        }
    }
}

class FileNewsletterManager {
    
    public function __construct() {
        $this->createDataDirectory();
    }
    
    private function createDataDirectory() {
        $dataDir = __DIR__ . '/data';
        if (!file_exists($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        // Kreiranje .htaccess za zaštitu data foldera
        $htaccessPath = $dataDir . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Require all denied\n");
        }
        
        // Kreiranje osnovnih fajlova ako ne postoje
        if (!file_exists(SUBSCRIBERS_FILE)) {
            file_put_contents(SUBSCRIBERS_FILE, json_encode([]));
        }
        if (!file_exists(RATE_LIMIT_FILE)) {
            file_put_contents(RATE_LIMIT_FILE, json_encode([]));
        }
    }
    
    private function loadSubscribers() {
        if (!file_exists(SUBSCRIBERS_FILE)) {
            return [];
        }
        $content = file_get_contents(SUBSCRIBERS_FILE);
        return json_decode($content, true) ?: [];
    }
    
    private function saveSubscribers($subscribers) {
        return file_put_contents(SUBSCRIBERS_FILE, json_encode($subscribers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    public function subscribe($email, $language = 'sr', $name = '') {
        // Validacija email-a
        if (!$this->isValidEmail($email)) {
            return $this->sendJsonResponse(false, 'Neispravna email adresa');
        }
        
        // Rate limiting
        if (!$this->checkRateLimit()) {
            return $this->sendJsonResponse(false, 'Previše zahteva. Pokušajte ponovo za nekoliko minuta.');
        }
        
        $subscribers = $this->loadSubscribers();
        
        // Proveri da li već postoji
        foreach ($subscribers as $index => $subscriber) {
            if ($subscriber['email'] === $email) {
                if ($subscriber['status'] === 'active' && $subscriber['verified']) {
                    return $this->sendJsonResponse(false, 'Već ste pretplaćeni na newsletter');
                } elseif ($subscriber['status'] === 'unsubscribed') {
                    return $this->reactivateSubscription($email, $language, $index);
                } elseif (!$subscriber['verified']) {
                    return $this->resendVerification($subscriber);
                }
            }
        }
        
        // Dodaj novog pretplatnika
        $token = $this->generateToken();
        $newSubscriber = [
            'email' => $email,
            'name' => $name,
            'language' => $language,
            'status' => 'active',
            'verification_token' => $token,
            'verified' => false,
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $subscribers[] = $newSubscriber;
        $this->saveSubscribers($subscribers);
        
        // Pošalji verification email
        if ($this->sendVerificationEmail($email, $token, $language)) {
            return $this->sendJsonResponse(true, 'Proverite svoj email za potvrdu pretplate');
        } else {
            return $this->sendJsonResponse(false, 'Greška pri slanju email-a');
        }
    }
    
    private function sendEmail($to, $subject, $message) {
        // KORISTIMO RADNU SMTP KLASU!
        if (SMTP_HOST && SMTP_USERNAME && SMTP_PASSWORD) {
            $mailer = new WorkingNewsletterSMTP(
                SMTP_HOST,
                SMTP_PORT,
                SMTP_USERNAME,
                SMTP_PASSWORD,
                SMTP_ENCRYPTION,
                $_ENV['DEBUG_MODE'] === 'true'
            );
            
            $result = $mailer->sendEmail($to, $subject, $message, FROM_EMAIL, FROM_NAME);
            
            if ($result) {
                return true;
            } else {
                error_log("Newsletter SMTP failed, trying PHP mail() fallback");
            }
        }
        
        // Fallback na mail() funkciju
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
            'Reply-To: ' . FROM_EMAIL,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }
    
    private function getEmailTemplate($language, $verifyUrl, $unsubscribeUrl) {
        if ($language === 'sr') {
            return "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8f9fa; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #c41e3a, #8b0000); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { padding: 30px; background: #ffffff; }
                    .button { display: inline-block; background: #ffd700; color: #000; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                    .footer { padding: 20px; font-size: 12px; color: #666; text-align: center; background: #e9ecef; border-radius: 0 0 10px 10px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🥊 BIF - Balkan Influence Fighting</h1>
                        <p>Dobrodošli u BIF Newsletter!</p>
                    </div>
                    <div class='content'>
                        <h2>Potvrdite svoju pretplatu</h2>
                        <p>Hvala vam što se pretplaćujete na BIF Newsletter!</p>
                        <p>Da biste potvrdili svoju pretplatu i počeli da primate najnovije vesti o našim borcima, događajima i spektakularnim mečevima, kliknite na dugme ispod:</p>
                        
                        <div style='text-align: center;'>
                            <a href='{$verifyUrl}' class='button'>POTVRDI PRETPLATU</a>
                        </div>
                        
                        <p><strong>Šta možete očekivati:</strong></p>
                        <ul>
                            <li>🥊 Ekskluzivne vesti o našim borcima</li>
                            <li>🎯 Najave novih mečeva i događaja</li>
                            <li>🏆 Rezultati borbi i pobednici</li>
                            <li>💥 Specijalne ponude za karte</li>
                        </ul>
                        
                        <p><em>Ako niste vi zahtevali ovu pretplatu, možete ignorisati ovaj email.</em></p>
                    </div>
                    <div class='footer'>
                        <p><strong>BIF - Balkan Influence Fighting</strong><br>Belgrade, Serbia</p>
                        <p><a href='{$unsubscribeUrl}'>Odjavite se</a> | <a href='mailto:" . FROM_EMAIL . "'>Kontakt</a></p>
                    </div>
                </div>
            </body>
            </html>";
        } else {
            return "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8f9fa; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #c41e3a, #8b0000); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { padding: 30px; background: #ffffff; }
                    .button { display: inline-block; background: #ffd700; color: #000; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                    .footer { padding: 20px; font-size: 12px; color: #666; text-align: center; background: #e9ecef; border-radius: 0 0 10px 10px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🥊 BIF - Balkan Influence Fighting</h1>
                        <p>Welcome to BIF Newsletter!</p>
                    </div>
                    <div class='content'>
                        <h2>Confirm Your Subscription</h2>
                        <p>Thank you for subscribing to BIF Newsletter!</p>
                        <p>To confirm your subscription and start receiving the latest news about our fighters, events, and spectacular matches, click the button below:</p>
                        
                        <div style='text-align: center;'>
                            <a href='{$verifyUrl}' class='button'>CONFIRM SUBSCRIPTION</a>
                        </div>
                        
                        <p><strong>What you can expect:</strong></p>
                        <ul>
                            <li>🥊 Exclusive news about our fighters</li>
                            <li>🎯 Announcements of new matches and events</li>
                            <li>🏆 Fight results and winners</li>
                            <li>💥 Special ticket offers</li>
                        </ul>
                        
                        <p><em>If you didn't request this subscription, you can ignore this email.</em></p>
                    </div>
                    <div class='footer'>
                        <p><strong>BIF - Balkan Influence Fighting</strong><br>Belgrade, Serbia</p>
                        <p><a href='{$unsubscribeUrl}'>Unsubscribe</a> | <a href='mailto:" . FROM_EMAIL . "'>Contact</a></p>
                    </div>
                </div>
            </body>
            </html>";
        }
    }
    
    private function checkRateLimit() {
        $rateLimits = [];
        if (file_exists(RATE_LIMIT_FILE)) {
            $content = file_get_contents(RATE_LIMIT_FILE);
            $rateLimits = json_decode($content, true) ?: [];
        }
        
        $ip = $this->getClientIP();
        $currentTime = time();
        $maxRequests = (int)($_ENV['RATE_LIMIT_REQUESTS'] ?? 5);
        $timeWindow = (int)($_ENV['RATE_LIMIT_MINUTES'] ?? 10) * 60;
        
        // Očisti stare unose
        $rateLimits = array_filter($rateLimits, function($entry) use ($currentTime, $timeWindow) {
            return ($currentTime - $entry['time']) < $timeWindow;
        });
        
        // Proveri koliko zahteva ima ovaj IP
        $ipRequests = array_filter($rateLimits, function($entry) use ($ip) {
            return $entry['ip'] === $ip;
        });
        
        if (count($ipRequests) >= $maxRequests) {
            return false;
        }
        
        // Dodaj novi zahtev
        $rateLimits[] = ['ip' => $ip, 'time' => $currentTime, 'type' => 'newsletter'];
        
        file_put_contents(RATE_LIMIT_FILE, json_encode($rateLimits));
        return true;
    }
    
    private function sendVerificationEmail($email, $token, $language) {
        $subject = $language === 'sr' ? 'Potvrdite pretplatu - BIF Newsletter' : 'Confirm Subscription - BIF Newsletter';
        
        $verifyUrl = SITE_URL . "/newsletter.php?action=verify&token=" . $token;
        $unsubscribeUrl = SITE_URL . "/newsletter.php?action=unsubscribe&email=" . urlencode($email) . "&token=" . $token;
        
        $message = $this->getEmailTemplate($language, $verifyUrl, $unsubscribeUrl);
        
        return $this->sendEmail($email, $subject, $message);
    }
    
    public function verify($token) {
        $subscribers = $this->loadSubscribers();
        
        foreach ($subscribers as $index => $subscriber) {
            if ($subscriber['verification_token'] === $token && !$subscriber['verified']) {
                $subscribers[$index]['verified'] = true;
                $subscribers[$index]['verification_token'] = null;
                $subscribers[$index]['updated_at'] = date('Y-m-d H:i:s');
                
                $this->saveSubscribers($subscribers);
                return true;
            }
        }
        return false;
    }
    
    public function unsubscribe($email, $token = '') {
        $subscribers = $this->loadSubscribers();
        
        foreach ($subscribers as $index => $subscriber) {
            if ($subscriber['email'] === $email) {
                if ($token === '' || $subscriber['verification_token'] === $token) {
                    $subscribers[$index]['status'] = 'unsubscribed';
                    $subscribers[$index]['updated_at'] = date('Y-m-d H:i:s');
                    $this->saveSubscribers($subscribers);
                    return true;
                }
            }
        }
        return false;
    }
    
    private function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    private function generateToken() {
        return bin2hex(random_bytes(16));
    }
    
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    private function reactivateSubscription($email, $language, $index) {
        $subscribers = $this->loadSubscribers();
        $token = $this->generateToken();
        
        $subscribers[$index]['status'] = 'active';
        $subscribers[$index]['verification_token'] = $token;
        $subscribers[$index]['verified'] = false;
        $subscribers[$index]['language'] = $language;
        $subscribers[$index]['updated_at'] = date('Y-m-d H:i:s');
        
        $this->saveSubscribers($subscribers);
        
        if ($this->sendVerificationEmail($email, $token, $language)) {
            return $this->sendJsonResponse(true, 'Pretplata je reaktivirana. Proverite email za potvrdu.');
        } else {
            return $this->sendJsonResponse(false, 'Greška pri slanju email-a');
        }
    }
    
    private function resendVerification($subscriber) {
        if ($this->sendVerificationEmail($subscriber['email'], $subscriber['verification_token'], $subscriber['language'])) {
            return $this->sendJsonResponse(true, 'Verification email je ponovo poslat');
        } else {
            return $this->sendJsonResponse(false, 'Greška pri slanju email-a');
        }
    }
    
    private function sendJsonResponse($success, $message) {
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }
    
    public function getSubscribers($status = 'active', $verified = true) {
        $subscribers = $this->loadSubscribers();
        return array_filter($subscribers, function($subscriber) use ($status, $verified) {
            return $subscriber['status'] === $status && $subscriber['verified'] === $verified;
        });
    }
    
    public function getStats() {
        $allSubscribers = $this->loadSubscribers();
        $active = count($this->getSubscribers('active', true));
        $pending = count(array_filter($allSubscribers, function($sub) {
            return $sub['status'] === 'active' && !$sub['verified'];
        }));
        $unsubscribed = count(array_filter($allSubscribers, function($sub) {
            return $sub['status'] === 'unsubscribed';
        }));
        
        return [
            'active_subscribers' => $active,
            'pending_verification' => $pending,
            'unsubscribed' => $unsubscribed,
            'total' => count($allSubscribers)
        ];
    }
    
    // NOVA METODA - za slanje newsletter-a postojećim pretplatnicima
    public function sendNewsletter($subject, $content, $language = 'sr') {
        $subscribers = $this->getSubscribers('active', true);
        $filteredSubscribers = array_filter($subscribers, function($subscriber) use ($language) {
            return $subscriber['language'] === $language;
        });
        
        $sent = 0;
        $failed = 0;
        
        foreach ($filteredSubscribers as $subscriber) {
            $unsubscribeUrl = SITE_URL . "/newsletter.php?action=unsubscribe&email=" . urlencode($subscriber['email']);
            
            $newsletterContent = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8f9fa; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #c41e3a, #8b0000); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { padding: 30px; background: #ffffff; }
                    .footer { padding: 20px; font-size: 12px; color: #666; text-align: center; background: #e9ecef; border-radius: 0 0 10px 10px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🥊 BIF Newsletter</h1>
                    </div>
                    <div class='content'>
                        {$content}
                    </div>
                    <div class='footer'>
                        <p><strong>BIF - Balkan Influence Fighting</strong><br>Belgrade, Serbia</p>
                        <p><a href='{$unsubscribeUrl}'>Odjavite se</a> | <a href='mailto:" . FROM_EMAIL . "'>Kontakt</a></p>
                    </div>
                </div>
            </body>
            </html>";
            
            if ($this->sendEmail($subscriber['email'], $subject, $newsletterContent)) {
                $sent++;
            } else {
                $failed++;
            }
            
            // Mala pauza između email-ova da se ne preoptereti server
            usleep(100000); // 0.1 sekunda
        }
        
        return ['sent' => $sent, 'failed' => $failed, 'total' => count($filteredSubscribers)];
    }
}

// Handling zahteva
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
    $newsletter = new FileNewsletterManager();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Obična pretplata
        if (isset($input['email'])) {
            $email = $input['email'] ?? '';
            $language = $input['language'] ?? 'sr';
            $name = $input['name'] ?? '';
            
            $newsletter->subscribe($email, $language, $name);
        }
        
        // Slanje newsletter-a (samo za admina)
        elseif (isset($input['send_newsletter'])) {
            // Ovde možete dodati autentifikaciju za admina
            $subject = $input['subject'] ?? '';
            $content = $input['content'] ?? '';
            $language = $input['language'] ?? 'sr';
            
            if (!empty($subject) && !empty($content)) {
                $result = $newsletter->sendNewsletter($subject, $content, $language);
                echo json_encode([
                    'success' => true, 
                    'message' => "Newsletter poslat: {$result['sent']} uspešno, {$result['failed']} neuspešno od ukupno {$result['total']} pretplatnika"
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Subject i content su obavezni']);
            }
            exit;
        }
        
    } elseif (isset($_GET['action'])) {
        $action = $_GET['action'];
        
        switch ($action) {
            case 'verify':
                $token = $_GET['token'] ?? '';
                if ($token && $newsletter->verify($token)) {
                    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Email potvrđen</title><style>body{font-family:Arial,sans-serif;text-align:center;padding:50px;background:#f8f9fa}.container{max-width:500px;margin:0 auto;background:white;padding:40px;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,0.1)}h1{color:#c41e3a}a{color:#c41e3a}</style></head><body><div class='container'><h1>✅ Email je uspešno potvrđen!</h1><p>Vaša pretplata na BIF Newsletter je aktivirana.</p><p>Sada ćete primati najnovije vesti o našim borcima i događajima.</p><p><a href='" . SITE_URL . "'>Vratite se na sajt</a></p></div></body></html>";
                } else {
                    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Greška</title><style>body{font-family:Arial,sans-serif;text-align:center;padding:50px;background:#f8f9fa}.container{max-width:500px;margin:0 auto;background:white;padding:40px;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,0.1)}h1{color:#dc3545}</style></head><body><div class='container'><h1>❌ Greška</h1><p>Neispravna potvrda ili je email već potvrđen.</p></div></body></html>";
                }
                break;
                
            case 'unsubscribe':
                $email = $_GET['email'] ?? '';
                $token = $_GET['token'] ?? '';
                if ($email && $newsletter->unsubscribe($email, $token)) {
                    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Odjavljeni ste</title><style>body{font-family:Arial,sans-serif;text-align:center;padding:50px;background:#f8f9fa}.container{max-width:500px;margin:0 auto;background:white;padding:40px;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,0.1)}h1{color:#28a745}</style></head><body><div class='container'><h1>✅ Uspešno odjavljeni</h1><p>Nećete više primati email-ove sa našeg newsletter-a.</p><p>Hvala vam što ste bili deo BIF zajednice!</p></div></body></html>";
                } else {
                    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Greška</title><style>body{font-family:Arial,sans-serif;text-align:center;padding:50px;background:#f8f9fa}.container{max-width:500px;margin:0 auto;background:white;padding:40px;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,0.1)}h1{color:#dc3545}</style></head><body><div class='container'><h1>❌ Greška</h1><p>Email adresa nije pronađena ili je već odjavljena.</p></div></body></html>";
                }
                break;
                
            case 'stats':
                // Jednostavne statistike - samo za debug
                if ($_ENV['DEBUG_MODE'] === 'true') {
                    $stats = $newsletter->getStats();
                    echo json_encode($stats);
                } else {
                    http_response_code(403);
                    echo json_encode(['error' => 'Access denied']);
                }
                break;
                
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Action not found']);
        }
    }
} else {
    // GET request bez akcije - prikaži jednostavnu formu za pretplatu (opcionalno)
    if (!isset($_GET['action'])) {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>BIF Newsletter</title>";
        echo "<style>body{font-family:Arial,sans-serif;margin:40px;background:#f8f9fa}.container{max-width:500px;margin:0 auto;background:white;padding:40px;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,0.1)}h1{color:#c41e3a;text-align:center}form{margin-top:30px}label{display:block;margin:10px 0 5px;font-weight:bold}input,select{width:100%;padding:12px;margin-bottom:15px;border:1px solid #ddd;border-radius:5px;box-sizing:border-box}button{background:#c41e3a;color:white;padding:15px 30px;border:none;border-radius:5px;cursor:pointer;width:100%;font-size:16px}button:hover{background:#a01729}.success{color:#28a745;text-align:center;margin-top:20px}.error{color:#dc3545;text-align:center;margin-top:20px}</style>";
        echo "</head><body><div class='container'>";
        echo "<h1>🥊 BIF Newsletter</h1>";
        echo "<p>Prijavite se za naš newsletter i budite u toku sa najnovijim vestima o borcima, mečevima i događajima!</p>";
        echo "<form id='newsletterForm'>";
        echo "<label>Email adresa:</label>";
        echo "<input type='email' id='email' required>";
        echo "<label>Ime (opcionalno):</label>";
        echo "<input type='text' id='name'>";
        echo "<label>Jezik:</label>";
        echo "<select id='language'>";
        echo "<option value='sr'>Srpski</option>";
        echo "<option value='en'>English</option>";
        echo "</select>";
        echo "<button type='submit'>Pretplati se</button>";
        echo "</form>";
        echo "<div id='result'></div>";
        echo "<script>";
        echo "document.getElementById('newsletterForm').addEventListener('submit', function(e) {";
        echo "e.preventDefault();";
        echo "var email = document.getElementById('email').value;";
        echo "var name = document.getElementById('name').value;";
        echo "var language = document.getElementById('language').value;";
        echo "fetch('/newsletter.php', {";
        echo "method: 'POST',";
        echo "headers: {'Content-Type': 'application/json'},";
        echo "body: JSON.stringify({email: email, name: name, language: language})";
        echo "}).then(response => response.json()).then(data => {";
        echo "var result = document.getElementById('result');";
        echo "if (data.success) {";
        echo "result.innerHTML = '<div class=\"success\">' + data.message + '</div>';";
        echo "document.getElementById('newsletterForm').reset();";
        echo "} else {";
        echo "result.innerHTML = '<div class=\"error\">' + data.message + '</div>';";
        echo "}";
        echo "}).catch(error => {";
        echo "document.getElementById('result').innerHTML = '<div class=\"error\">Greška pri slanju zahteva</div>';";
        echo "});";
        echo "});";
        echo "</script>";
        echo "</div></body></html>";
    }
}
?>