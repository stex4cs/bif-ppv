<?php
/**
 * BIF Contact Form Handler - POBOLJŠANA VERZIJA
 * contact.php - Handles contact form submissions
 */

// Error handling na početku
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Load environment variables
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

try {
    loadEnv();
} catch (Exception $e) {
    error_log("Error loading .env: " . $e->getMessage());
}

// Configuration sa fallback vrednostima
define('CONTACT_LOG_FILE', __DIR__ . '/data/contact_log.json');
define('RATE_LIMIT_FILE', __DIR__ . '/data/contact_rate_limits.json');
define('FROM_EMAIL', $_ENV['FROM_EMAIL'] ?? 'business@bif.events');
define('FROM_NAME', $_ENV['FROM_NAME'] ?? 'BIF - Balkan Influence Fighting');
define('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL'] ?? 'business@bif.events');
define('SITE_URL', $_ENV['SITE_URL'] ?? 'https://bif.events');

// CORS headers
header('Access-Control-Allow-Origin: https://bif.events');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Debug function
function debugLog($message) {
    error_log("[BIF Contact Debug] " . $message);
}

class ContactFormHandler {
    
    public function __construct() {
        try {
            $this->createDataDirectory();
        } catch (Exception $e) {
            debugLog("Constructor error: " . $e->getMessage());
        }
    }
    
    private function createDataDirectory() {
        $dataDir = __DIR__ . '/data';
        if (!file_exists($dataDir)) {
            if (!mkdir($dataDir, 0755, true)) {
                throw new Exception("Cannot create data directory");
            }
        }
        
        $htaccessPath = $dataDir . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Require all denied\n");
        }
        
        if (!file_exists(CONTACT_LOG_FILE)) {
            file_put_contents(CONTACT_LOG_FILE, json_encode([]));
        }
        if (!file_exists(RATE_LIMIT_FILE)) {
            file_put_contents(RATE_LIMIT_FILE, json_encode([]));
        }
    }
    
    public function handleSubmission($data) {
        try {
            debugLog("Starting submission handling");
            
            // Validate data
            if (!$this->validateData($data)) {
                debugLog("Validation failed");
                return $this->sendJsonResponse(false, $this->getErrorMessage('validation', $data['language'] ?? 'sr'));
            }
            
            debugLog("Data validated successfully");
            
            // Rate limiting
            if (!$this->checkRateLimit()) {
                debugLog("Rate limit exceeded");
                return $this->sendJsonResponse(false, $this->getErrorMessage('rate_limit', $data['language'] ?? 'sr'));
            }
            
            debugLog("Rate limit check passed");
            
            // Sanitize data
            $cleanData = $this->sanitizeData($data);
            debugLog("Data sanitized");
            
            // Log submission
            $this->logSubmission($cleanData);
            debugLog("Submission logged");
            
            // Send emails
            $emailResult = $this->sendEmails($cleanData);
            debugLog("Email result: " . json_encode($emailResult));
            
            if ($emailResult['success']) {
                return $this->sendJsonResponse(true, $this->getSuccessMessage($data['language'] ?? 'sr'));
            } else {
                debugLog("Email sending failed: " . ($emailResult['error'] ?? 'Unknown error'));
                return $this->sendJsonResponse(false, $this->getErrorMessage('email_failed', $data['language'] ?? 'sr'));
            }
            
        } catch (Exception $e) {
            debugLog("Exception in handleSubmission: " . $e->getMessage());
            debugLog("Stack trace: " . $e->getTraceAsString());
            return $this->sendJsonResponse(false, "Došlo je do neočekivane greške. Molimo pokušajte ponovo.");
        }
    }
    
    private function validateData($data) {
        try {
            if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
                debugLog("Name validation failed");
                return false;
            }
            
            if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                debugLog("Email validation failed");
                return false;
            }
            
            if (empty($data['subject'])) {
                debugLog("Subject validation failed");
                return false;
            }
            
            if (empty($data['message']) || strlen(trim($data['message'])) < 10) {
                debugLog("Message validation failed");
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            debugLog("Exception in validateData: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendEmails($data) {
        try {
            debugLog("Starting email sending process");
            
            $adminSubject = "Nova poruka sa BIF sajta - " . $this->getSubjectText($data['subject'], $data['language']);
            $adminMessage = $this->getAdminEmailTemplate($data);
            
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
                'Reply-To: ' . $data['email'],
                'X-Mailer: PHP/' . phpversion()
            ];
            
            debugLog("Prepared email headers and content");
            debugLog("Sending to: " . ADMIN_EMAIL);
            debugLog("Subject: " . $adminSubject);
            
            // Admin email
            $adminSent = mail(ADMIN_EMAIL, $adminSubject, $adminMessage, implode("\r\n", $headers));
            debugLog("Admin email result: " . ($adminSent ? 'SUCCESS' : 'FAILED'));
            
            if (!$adminSent) {
                $lastError = error_get_last();
                debugLog("Last PHP error: " . json_encode($lastError));
                return ['success' => false, 'error' => 'Admin email failed'];
            }
            
            // User confirmation (samo ako admin email uspe)
            $userSent = true;
            if ($adminSent) {
                $userSubject = $data['language'] === 'sr' ? 
                    'Hvala vam na poruci - BIF' : 
                    'Thank you for your message - BIF';
                $userMessage = $this->getUserConfirmationTemplate($data);
                
                debugLog("Sending confirmation email to user");
                $userSent = mail($data['email'], $userSubject, $userMessage, implode("\r\n", $headers));
                debugLog("User email result: " . ($userSent ? 'SUCCESS' : 'FAILED'));
            }
            
            return ['success' => true, 'method' => 'php_mail', 'admin_sent' => $adminSent, 'user_sent' => $userSent];
            
        } catch (Exception $e) {
            debugLog("Exception in sendEmails: " . $e->getMessage());
            return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
        }
    }
    
    private function sanitizeData($data) {
        try {
            return [
                'name' => htmlspecialchars(strip_tags(trim($data['name'])), ENT_QUOTES, 'UTF-8'),
                'email' => filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL),
                'subject' => htmlspecialchars(strip_tags(trim($data['subject'])), ENT_QUOTES, 'UTF-8'),
                'phone' => htmlspecialchars(strip_tags(trim($data['phone'] ?? '')), ENT_QUOTES, 'UTF-8'),
                'message' => htmlspecialchars(strip_tags(trim($data['message'])), ENT_QUOTES, 'UTF-8'),
                'language' => in_array($data['language'] ?? 'sr', ['sr', 'en']) ? $data['language'] : 'sr',
                'ip_address' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            debugLog("Exception in sanitizeData: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function checkRateLimit() {
        try {
            $rateLimits = [];
            if (file_exists(RATE_LIMIT_FILE)) {
                $content = file_get_contents(RATE_LIMIT_FILE);
                $rateLimits = json_decode($content, true) ?: [];
            }
            
            $ip = $this->getClientIP();
            $currentTime = time();
            $maxRequests = 5; // Povećano sa 3 na 5
            $timeWindow = 3600;
            
            $rateLimits = array_filter($rateLimits, function($entry) use ($currentTime, $timeWindow) {
                return ($currentTime - $entry['time']) < $timeWindow;
            });
            
            $ipRequests = array_filter($rateLimits, function($entry) use ($ip) {
                return $entry['ip'] === $ip;
            });
            
            if (count($ipRequests) >= $maxRequests) {
                debugLog("Rate limit exceeded for IP: " . $ip);
                return false;
            }
            
            $rateLimits[] = ['ip' => $ip, 'time' => $currentTime, 'type' => 'contact'];
            
            file_put_contents(RATE_LIMIT_FILE, json_encode($rateLimits));
            return true;
        } catch (Exception $e) {
            debugLog("Exception in checkRateLimit: " . $e->getMessage());
            return true; // Allow request if rate limiting fails
        }
    }
    
    private function logSubmission($data) {
        try {
            $logs = [];
            if (file_exists(CONTACT_LOG_FILE)) {
                $content = file_get_contents(CONTACT_LOG_FILE);
                $logs = json_decode($content, true) ?: [];
            }
            
            $logs[] = $data;
            
            if (count($logs) > 100) {
                $logs = array_slice($logs, -100);
            }
            
            file_put_contents(CONTACT_LOG_FILE, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {
            debugLog("Exception in logSubmission: " . $e->getMessage());
            // Ne bacamo grešku jer logovanje nije kritično
        }
    }
    
    private function getAdminEmailTemplate($data) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #c41e3a, #8b0000); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; margin: -30px -30px 20px -30px; }
                .field { margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #c41e3a; }
                .label { font-weight: bold; color: #c41e3a; margin-bottom: 5px; }
                .value { color: #333; }
                .message-box { background: #fff; border: 2px solid #e9ecef; border-radius: 8px; padding: 20px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🥊 Nova poruka sa BIF sajta</h1>
                    <p>Datum: " . date('d.m.Y H:i') . "</p>
                </div>
                
                <div class='field'>
                    <div class='label'>Ime:</div>
                    <div class='value'>" . htmlspecialchars($data['name']) . "</div>
                </div>
                
                <div class='field'>
                    <div class='label'>Email:</div>
                    <div class='value'><a href='mailto:" . htmlspecialchars($data['email']) . "'>" . htmlspecialchars($data['email']) . "</a></div>
                </div>
                
                <div class='field'>
                    <div class='label'>Tema:</div>
                    <div class='value'>" . $this->getSubjectText($data['subject'], $data['language']) . "</div>
                </div>
                
                " . (!empty($data['phone']) ? "
                <div class='field'>
                    <div class='label'>Telefon:</div>
                    <div class='value'><a href='tel:" . htmlspecialchars($data['phone']) . "'>" . htmlspecialchars($data['phone']) . "</a></div>
                </div>
                " : "") . "
                
                <div class='field'>
                    <div class='label'>Jezik:</div>
                    <div class='value'>" . ($data['language'] === 'sr' ? 'Srpski' : 'English') . "</div>
                </div>
                
                <div class='message-box'>
                    <div class='label'>Poruka:</div>
                    <div class='value'>" . nl2br(htmlspecialchars($data['message'])) . "</div>
                </div>
                
                <div class='field'>
                    <div class='label'>IP adresa:</div>
                    <div class='value'>" . htmlspecialchars($data['ip_address']) . "</div>
                </div>
                
                <div class='footer'>
                    <p><strong>BIF - Balkan Influence Fighting</strong><br>
                    Automatski generisana poruka</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getUserConfirmationTemplate($data) {
        if ($data['language'] === 'sr') {
            return "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
                    .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #c41e3a, #8b0000); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; margin: -30px -30px 20px -30px; }
                    .content { padding: 20px 0; }
                    .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef; color: #666; font-size: 14px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🥊 BIF - Balkan Influence Fighting</h1>
                        <p>Hvala vam na poruci!</p>
                    </div>
                     <div class='content'>
                        <h2>Poštovani/a " . htmlspecialchars($data['name']) . ",</h2>
                        <p>Hvala vam što ste nas kontaktirali! Vaša poruka je uspešno primljena.</p>
                        <p><strong>Tema vaše poruke:</strong> " . $this->getSubjectText($data['subject'], 'sr') . "</p>
                        <p>Odgovoriću vam u najkraćem mogućem roku, obično u roku od 24 sata.</p>
                        <p>Ako imate hitno pitanje, možete nas kontaktirati direktno:</p>
                        <ul>
                            <li>📧 Email: <a href='mailto:" . FROM_EMAIL . "'>" . FROM_EMAIL . "</a></li>
                            <li>📞 Telefon: <a href='tel:+381601484066'>+381 601484066</a></li>
                        </ul>
                    </div>
                    <div class='footer'>
                        <p><strong>BIF - Balkan Influence Fighting</strong><br>Belgrade, Serbia</p>
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
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
                    .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #c41e3a, #8b0000); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; margin: -30px -30px 20px -30px; }
                    .content { padding: 20px 0; }
                    .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef; color: #666; font-size: 14px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🥊 BIF - Balkan Influence Fighting</h1>
                        <p>Thank you for your message!</p>
                    </div>
                    <div class='content'>
                        <h2>Dear " . htmlspecialchars($data['name']) . ",</h2>
                        <p>Thank you for contacting us! Your message has been successfully received.</p>
                        <p><strong>Subject of your message:</strong> " . $this->getSubjectText($data['subject'], 'en') . "</p>
                        <p>We will respond to you as soon as possible, usually within 24 hours.</p>
                        <p>If you have an urgent question, you can contact us directly:</p>
                        <ul>
                            <li>📧 Email: <a href='mailto:" . FROM_EMAIL . "'>" . FROM_EMAIL . "</a></li>
                            <li>📞 Phone: <a href='tel:+381601484066'>+381 601484066</a></li>
                        </ul>
                    </div>
                    <div class='footer'>
                        <p><strong>BIF - Balkan Influence Fighting</strong><br>Belgrade, Serbia</p>
                    </div>
                </div>
            </body>
            </html>";
        }
    }
    
    private function getSubjectText($subject, $language) {
        $subjects = [
            'sponsorship' => ['sr' => 'Sponzorstvo', 'en' => 'Sponsorship'],
            'fighter' => ['sr' => 'Pridruživanje kao borac', 'en' => 'Joining as a fighter'],
            'media' => ['sr' => 'Mediji i PR', 'en' => 'Media & PR'],
            'events' => ['sr' => 'Događaji', 'en' => 'Events'],
            'other' => ['sr' => 'Ostalo', 'en' => 'Other']
        ];
        
        return $subjects[$subject][$language] ?? $subject;
    }
    
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }
    
    private function getSuccessMessage($language) {
        return $language === 'sr' ? 
            'Hvala vam! Vaša poruka je uspešno poslata. Odgovoriću vam u najkraćem roku.' :
            'Thank you! Your message has been sent successfully. We will respond shortly.';
    }
    
    private function getErrorMessage($type, $language) {
        $messages = [
            'validation' => [
                'sr' => 'Molimo proverite unete podatke.',
                'en' => 'Please check the entered data.'
            ],
            'rate_limit' => [
                'sr' => 'Previše zahteva. Pokušajte ponovo za sat vremena.',
                'en' => 'Too many requests. Please try again in an hour.'
            ],
            'email_failed' => [
                'sr' => 'Greška pri slanju email-a. Pokušajte ponovo.',
                'en' => 'Error sending email. Please try again.'
            ]
        ];
        
        return $messages[$type][$language] ?? 'Neočekivana greška.';
    }
    
    private function sendJsonResponse($success, $message) {
        http_response_code($success ? 200 : 400);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'timestamp' => time()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Handle request
try {
    debugLog("=== NEW REQUEST ===");
    debugLog("Method: " . $_SERVER['REQUEST_METHOD']);
    debugLog("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawInput = file_get_contents('php://input');
        debugLog("Raw input length: " . strlen($rawInput));
        debugLog("Raw input: " . substr($rawInput, 0, 500) . (strlen($rawInput) > 500 ? '...' : ''));
        
        $input = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            debugLog("JSON decode error: " . json_last_error_msg());
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON input'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if ($input) {
            debugLog("Input decoded successfully");
            $contactHandler = new ContactFormHandler();
            $contactHandler->handleSubmission($input);
        } else {
            debugLog("Empty input after JSON decode");
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Empty input'], JSON_UNESCAPED_UNICODE);
        }
    } else {
        debugLog("Invalid method: " . $_SERVER['REQUEST_METHOD']);
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    debugLog("Global exception: " . $e->getMessage());
    debugLog("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error'], JSON_UNESCAPED_UNICODE);
}
?>