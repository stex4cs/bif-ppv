<?php

// Security Headers za API
require_once dirname(__DIR__) . '/includes/security-headers.php';
Security_Headers::applyAPICSP();

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

/**
 * BIF PPV System - API Endpoint (Enhanced Version)
 * api/ppv.php - Complete clean version with enhanced security
 */
require_once dirname(__DIR__) . '/includes/security-layer.php';
require_once dirname(__DIR__) . '/includes/csrf-protection.php';

// Test endpoint
if (isset($_GET['test'])) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => true,
        'message' => 'PHP works',
        'php_version' => PHP_VERSION,
        'post_data' => file_get_contents('php://input'),
        'method' => $_SERVER['REQUEST_METHOD']
    ]);
    exit;
}

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/data/php_errors.log');

// Check vendor/autoload.php
$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false, 
        'error' => 'Composer autoload not found. Run: composer install',
        'path_checked' => $autoloadPath
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require $autoloadPath;
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false, 
        'error' => 'Autoload error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check Stripe library
if (!class_exists('Stripe\Stripe')) {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false, 
        'error' => 'Stripe library not installed. Run: composer require stripe/stripe-php'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

use Stripe\Stripe;
use Stripe\PaymentIntent;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Environment loader
// --- NOVA I POBOLJÅ ANA .ENV LOADER FUNKCIJA ---
function loadEnv() {
    $envFile = dirname(__DIR__) . '/env/.env';
    
    // Logujemo putanju da budemo 100% sigurni da je taÄna
    error_log("Attempting to load .env file from: " . $envFile);

    if (!is_readable($envFile)) {
        error_log("FATAL: .env file not found or is not readable at the specified path.");
        return; // Izlazimo ako ne moÅ¾emo da proÄitamo fajl
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if ($lines === false) {
        error_log("FATAL: Could not read lines from .env file.");
        return;
    }

    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim(trim($value), "\"'");
            $_ENV[$name] = $value;
            putenv("$name=$value"); // Postavljamo i preko putenv za svaki sluÄaj
        }
    }

    // Finalna provera i logovanje
    $loadedKeys = implode(', ', array_keys($_ENV));
    error_log("SUCCESS: .env file loaded. Keys found: [" . $loadedKeys . "]");
    if (isset($_ENV['APP_ENV'])) {
        error_log("VERIFICATION: APP_ENV is set to '" . $_ENV['APP_ENV'] . "'");
    } else {
        error_log("VERIFICATION WARNING: APP_ENV is NOT set after loading .env!");
    }
}
// --- KRAJ NOVE FUNKCIJE ---

loadEnv();

// Include dependencies
require_once dirname(__DIR__) . '/database.php';

// Load security config
$securityConfigFile = dirname(__DIR__) . '/config/security.php';
$securityConfig = file_exists($securityConfigFile) ? include $securityConfigFile : [
    'drm' => ['max_concurrent_devices' => 1, 'heartbeat_timeout' => 120],
    'detection' => ['recording_detection' => true],
    'violations' => ['max_warnings' => 3]
];

// Validate Stripe keys
$stripeSecret = $_ENV['STRIPE_SECRET_KEY'] ?? '';
$stripePublic = $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '';

if (empty($stripeSecret) || empty($stripePublic)) {
    if (isset($_GET['debug'])) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'error' => 'Stripe keys missing',
            'debug' => [
                'env_file_exists' => file_exists(dirname(__DIR__) . '/env/.env'),
                'stripe_secret_length' => strlen($stripeSecret),
                'stripe_public_length' => strlen($stripePublic),
                'env_vars' => array_keys($_ENV)
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false, 
        'error' => 'Stripe keys not configured. Check env/.env file.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

\Stripe\Stripe::setApiKey($stripeSecret);

// Constants
define('PPV_DATA_DIR', dirname(__DIR__) . '/data');
define('PPV_LOG_FILE', PPV_DATA_DIR . '/ppv_purchases.json');
define('PPV_EVENTS_FILE', PPV_DATA_DIR . '/ppv_events.json');
define('PPV_ACCESS_FILE', PPV_DATA_DIR . '/ppv_access.json');
define('STRIPE_SECRET_KEY', $stripeSecret);
define('STRIPE_PUBLISHABLE_KEY', $stripePublic);
define('STRIPE_WEBHOOK_SECRET', $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '');

// CORS headers
$allowedOrigins = [
    'https://bif.events',
    'https://www.bif.events', 
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'null'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
    strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false) {
    header('Access-Control-Allow-Origin: *');
} else if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: *');
}

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Stripe-Signature');
header('Access-Control-Allow-Credentials: true');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Debug logging
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    error_log("PPV API Debug - Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("PPV API Debug - Query: " . $_SERVER['QUERY_STRING']);
    error_log("PPV API Debug - Input: " . file_get_contents('php://input'));
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

class BIF_PPV_System
{
    private BIF_SecurityLayer $securityLayer;
    private bool $stripe3dsEnabled = true;

    // izbegni dynamic properties
    private string $logFile;
    private PPV_Database $database;
    private array $securityConfig;

    public function __construct()
    {
        
        global $securityConfig;

        $this->securityLayer  = new BIF_SecurityLayer();
        $this->logFile        = PPV_DATA_DIR . '/ppv_debug.log';
        $this->database       = new PPV_Database();
        $this->securityConfig = $securityConfig;

        $this->createDataDirectory();
        $this->initializeEvents();
        $this->log('PPV System initialized with enhanced security');
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    private function createDataDirectory() {
        if (!file_exists(PPV_DATA_DIR)) {
            mkdir(PPV_DATA_DIR, 0755, true);
        }
        
        $htaccessPath = PPV_DATA_DIR . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Require all denied\n");
        }
        
        // Initialize files
        if (!file_exists(PPV_LOG_FILE)) {
            file_put_contents(PPV_LOG_FILE, json_encode([]));
        }
        if (!file_exists(PPV_EVENTS_FILE)) {
            file_put_contents(PPV_EVENTS_FILE, json_encode([]));
        }
        if (!file_exists(PPV_ACCESS_FILE)) {
            file_put_contents(PPV_ACCESS_FILE, json_encode([]));
        }
    }
    
    private function initializeEvents() {
    $events = $this->loadEvents();
    
    // JEDNOSTAVNO: Ne kreiraj nikakve default dogaÄ‘aje
    // Svi dogaÄ‘aji se kreiraju kroz admin panel
    
    $this->log('Events initialized - all events managed via admin panel');
    
    // Ako nema dogaÄ‘aja, samo ispiÅ¡i poruku u log
    if (empty($events)) {
        $this->log('No events found - create events via admin panel at /admin');
    } else {
        $this->log('Found ' . count($events) . ' existing events');
    }
}
    
    private function loadEvents() {
        if (!file_exists(PPV_EVENTS_FILE)) return [];
        $content = file_get_contents(PPV_EVENTS_FILE);
        $events = json_decode($content, true);
        return is_array($events) ? $events : [];
    }
    
    private function saveEvents($events) {
        return file_put_contents(PPV_EVENTS_FILE, json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    private function loadPurchases() {
        if (!file_exists(PPV_LOG_FILE)) return [];
        $content = file_get_contents(PPV_LOG_FILE);
        $purchases = json_decode($content, true);
        return is_array($purchases) ? $purchases : [];
    }
    
    private function savePurchases($purchases) {
        return file_put_contents(PPV_LOG_FILE, json_encode($purchases, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    private function loadAccess() {
        if (!file_exists(PPV_ACCESS_FILE)) return [];
        $content = file_get_contents(PPV_ACCESS_FILE);
        $access = json_decode($content, true);
        return is_array($access) ? $access : [];
    }
    
    private function saveAccess($access) {
        return file_put_contents(PPV_ACCESS_FILE, json_encode($access, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    public function getConfig() {
        if (empty(STRIPE_PUBLISHABLE_KEY)) {
            return [
                'success' => false,
                'error' => 'Stripe konfiguracija nije dostupna'
            ];
        }

        return [
            'success' => true,
            'stripe_key' => STRIPE_PUBLISHABLE_KEY,
            'currency' => 'rsd',
            'site_url' => $_ENV['SITE_URL'] ?? 'https://bif.events',
            'csrf_token' => CSRF_Protection::getToken() // Dodaj CSRF token
        ];
    }
    
    public function getEvents() {
        $events = $this->loadEvents();
        
        foreach ($events as &$event) {
            $currentPrice = $event['price'];
            $isEarlyBird = false;
            
            if (isset($event['early_bird_until']) && time() < strtotime($event['early_bird_until'])) {
                $currentPrice = $event['early_bird_price'];
                $isEarlyBird = true;
            }
            
            $event['current_price'] = $currentPrice;
            $event['is_early_bird'] = $isEarlyBird;
            
            
        }
        
        return $events;
    }
    
    public function getEvent($eventId) {
        $events = $this->loadEvents();
        foreach ($events as $event) {
            if ($event['id'] === $eventId) {
                $currentPrice = $event['price'];
                $isEarlyBird = false;
                
                if (isset($event['early_bird_until']) && time() < strtotime($event['early_bird_until'])) {
                    $currentPrice = $event['early_bird_price'];
                    $isEarlyBird = true;
                }
                
                $event['current_price'] = $currentPrice;
                $event['is_early_bird'] = $isEarlyBird;
                
               
                
                return $event;
            }
        }
        return null;
    }
    
    
    public function createPaymentIntentSecure($eventId, $email, $name, $paymentMethodId, $securityData = []) {
        error_log("=== ENHANCED createPaymentIntent START ===");
        error_log("Security data received: " . json_encode(array_keys($securityData)));
        
        try {
            // 1. COMPREHENSIVE SECURITY VALIDATION
            $validationResult = $this->securityLayer->validatePaymentRequest([
                'email' => $email,
                'name' => $name,
                'event_id' => $eventId,
                'device_fingerprint' => $securityData['device_fingerprint'] ?? '',
                'recaptcha_token' => $securityData['recaptcha_token'] ?? '',
                // ... (ostali security podaci ostaju isti) ...
            ]);
            
            if (!$validationResult['allowed']) {
                error_log("SECURITY BLOCK: " . $validationResult['reason']);
                return [
                    'success' => false,
                    'error' => 'Security validation failed: ' . $validationResult['reason'],
                    'security_block' => true,
                    'block_code' => $validationResult['block_code'] ?? 'UNKNOWN'
                ];
            }
            
            error_log("Security validation PASSED - Score: " . $validationResult['security_score']);
            
            // 2. BASIC EVENT AND EMAIL VALIDATION
            $event = $this->getEventForPayment($eventId);
            if (!$event) {
                error_log("ERROR: Event not found: $eventId");
                return ['success' => false, 'error' => 'Event not found'];
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                error_log("ERROR: Invalid email: $email");
                return ['success' => false, 'error' => 'Invalid email address'];
            }
            
            // 3. CHECK EXISTING ACCESS
            $existingAccess = $this->checkExistingAccess($eventId, $email);
            if ($existingAccess) {
                error_log("User already has access, returning existing token");
                return [
                    'success' => true,
                    'already_purchased' => true,
                    'access_token' => $existingAccess['token'],
                    'message' => 'You already have access to this event'
                ];
            }
            
            $amount = $event['current_price'];
            $minimumAmount = 5000; // 50 RSD minimum
            if ($amount < $minimumAmount) {
                $amount = $minimumAmount;
            }
            
            // 4. DEVELOPMENT SIMULATION
            $isDevelopment = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false);
            if ($isDevelopment && isset($_POST['simulate_success'])) {
                error_log("DEVELOPMENT: Simulating successful payment");
                return $this->simulatePaymentSuccess($eventId, $email, $name, $amount, $event);
            }
            
            // 5. CREATE ENHANCED STRIPE PAYMENT INTENT DATA
            $paymentIntentData = [
                'amount' => (int)$amount,
                'currency' => $event['currency'],
                'receipt_email' => $email,
                'payment_method' => $paymentMethodId,
                'metadata' => [
                    'event_id' => $eventId,
                    'customer_email' => $email,
                    'customer_name' => $name,
                    'device_fingerprint' => $securityData['device_fingerprint'] ?? '',
                    'security_score' => $validationResult['security_score'],
                    'product_type' => 'ppv',
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]
            ];
            
            // 6. CONFIGURE 3D SECURE
            $isDevelopment = ($_ENV['APP_ENV'] ?? 'production') === 'development';
            $requires_3ds_response = false;
            if ($isDevelopment) {
                $paymentIntentData['payment_method_types'] = ['card'];
                $paymentIntentData['confirmation_method'] = 'automatic';
                $paymentIntentData['confirm'] = true;
                $requires_3ds_response = false;
                error_log("3D Secure is AUTOMATIC (Development Mode).");
            } else {
                if (($validationResult['requires_3ds'] ?? false) || ($this->stripe3dsEnabled ?? true)) {
                    $paymentIntentData['payment_method_types'] = ['card'];
                    $paymentIntentData['confirmation_method'] = 'manual';
                    $paymentIntentData['confirm'] = false;
                    $requires_3ds_response = true;
                    error_log("3D Secure is MANUAL (Live Mode - High Risk).");
                } else {
                    $paymentIntentData['payment_method_types'] = ['card'];
                    $paymentIntentData['confirmation_method'] = 'automatic';
                    $paymentIntentData['confirm'] = true;
                    $requires_3ds_response = false;
                    error_log("3D Secure is AUTOMATIC (Live Mode - Low Risk).");
                }
            }
            
            // 7. CREATE STRIPE PAYMENT INTENT
            $paymentIntent = \Stripe\PaymentIntent::create($paymentIntentData);
            
            if (!$paymentIntent || !isset($paymentIntent->client_secret)) {
                error_log("ERROR: Failed to create payment intent");
                return ['success' => false, 'error' => 'Failed to create payment intent'];
            }

            // --- ISPRAVNA LOGIKA ZA LOCALHOST IDE OVDE ---
            // Proveravamo status ODMAH NAKON Å¡to je $paymentIntent kreiran
            if ($paymentIntent->status === 'succeeded') {
                $this->log("âœ… DEVELOPMENT: Payment succeeded instantly on backend.");
                
                // Odmah procesiraj kupovinu, bez Äekanja webhook-a!
                $purchase = $this->processSuccessfulPayment($paymentIntent);
                $accessToken = $this->grantAccess($eventId, $email, $purchase['id']);
                
                $this->log("âœ… DEVELOPMENT: Access token generated instantly: " . substr($accessToken, 0, 10));
                
                // Odmah vrati access token!
                return [
                    'success' => true,
                    'access_token' => $accessToken,
                    'event' => $this->getEvent($eventId),
                    'already_purchased' => true,
                    'development_mode' => true
                ];
            }
            // --- KRAJ ISPRAVNE LOGIKE ---
            
            // 8. RECORD ATTEMPT FOR SECURITY MONITORING
            $this->securityLayer->recordAttempt(
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $email,
                $securityData['device_fingerprint'] ?? '',
                false
            );
            
            error_log("=== ENHANCED createPaymentIntent SUCCESS (3DS Required) ===");
            
            // Ovaj return se sada koristi samo za LIVE server (kada je potrebna 3D Secure potvrda)
            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'amount' => $amount,
                'currency' => $event['currency'],
                'event_title' => $event['title'],
                'requires_3ds' => $requires_3ds_response,
                'security_score' => $validationResult['security_score'],
                'payment_intent_id' => $paymentIntent->id,
                'development_mode' => $isDevelopment
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Stripe API Error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Payment service error: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Enhanced Payment Error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Payment error: ' . $e->getMessage()];
        }
    }

    /**
     * ENHANCED PAYMENT STATUS CHECK
     */
    public function checkPaymentStatus($paymentIntentId, $securityData = [], $email = '', $name = '') {
        $this->log("Enhanced payment status check for: $paymentIntentId");
        
        try {
            // Validate security data for status check too
            if (!empty($securityData)) {
                $quickValidation = $this->securityLayer->validatePaymentRequest($securityData);
                if (!$quickValidation['allowed']) {
                    return [
                        'success' => false,
                        'error' => 'Security validation failed for status check',
                        'security_block' => true
                    ];
                }
            }
            
            // Get payment from database/file
            $purchases = $this->loadPurchases();
            $purchase = null;
            
            foreach ($purchases as $p) {
                if (($p['payment_intent_id'] ?? '') === $paymentIntentId && 
                    ($p['status'] ?? '') === 'completed') {
                    $purchase = $p;
                    break;
                }
            }
            
            if (!$purchase) {
                // Development simulation
                $isDevelopment = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false);
                
                if ($isDevelopment) {
                    $this->log("DEVELOPMENT: Simulating payment completion for $paymentIntentId");
                    return $this->simulatePaymentCompletion($paymentIntentId, $email, $name);
                
                }
                
                return ['success' => false, 'error' => 'Payment not found or not completed'];
            }
            
            // Find existing access token
            $access = $this->loadAccess();
            $accessToken = null;
            
            foreach ($access as $record) {
                if (($record['purchase_id'] ?? '') === $purchase['id']) {
                    $accessToken = $record['token'];
                    break;
                }
            }
            
            if (!$accessToken) {
                $accessToken = $this->grantAccess(
                    $purchase['event_id'], 
                    $purchase['customer_email'], 
                    $purchase['id']
                );
            }
            
            // Record successful attempt
            if (!empty($securityData)) {
                $this->securityLayer->recordAttempt(
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $purchase['customer_email'],
                    $securityData['device_fingerprint'] ?? '',
                    true // Successful
                );
            }
            
            $this->log("Enhanced payment status check successful");
            
            return [
                'success' => true,
                'access_token' => $accessToken,
                'purchase' => $purchase
            ];
            
        } catch (Exception $e) {
            $this->log("ERROR in enhanced checkPaymentStatus: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error checking payment status'];
        }
    }

    private function checkExistingAccess($eventId, $email) {
        $access = $this->loadAccess();
        foreach ($access as $record) {
            if ($record['event_id'] === $eventId && 
                $record['customer_email'] === $email &&
                strtotime($record['expires_at']) > time()) {
                return $record;
            }
        }
        return false;
    }
    
    private function getEventForPayment($eventId) {
        $events = $this->loadEvents();
        foreach ($events as $event) {
            if ($event['id'] === $eventId) {
                $currentPrice = $event['price'];
                if (isset($event['early_bird_until']) && time() < strtotime($event['early_bird_until'])) {
                    $currentPrice = $event['early_bird_price'];
                }
                $event['current_price'] = $currentPrice;
                return $event;
            }
        }
        return null;
    }
    
    public function handleStripeWebhook($payload, $signature) {
        $this->log("Webhook received with signature: " . substr($signature, 0, 20) . "...");
        
        if (empty(STRIPE_WEBHOOK_SECRET)) {
            $this->log("ERROR: Webhook secret not configured");
            return ['success' => false, 'error' => 'Webhook secret nije konfigurisan'];
        }
        
        try {
            if (!$this->verifyStripeSignature($payload, $signature, STRIPE_WEBHOOK_SECRET)) {
                $this->log("ERROR: Invalid webhook signature");
                return ['success' => false, 'error' => 'Neispravna signatura'];
            }
            
            $event = json_decode($payload, true);
            $this->log("Processing webhook event: " . $event['type']);
            
            if ($event['type'] === 'payment_intent.succeeded') {
                $paymentIntent = $event['data']['object'];
                $this->processSuccessfulPayment($paymentIntent);
            }
            
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->log("ERROR: Webhook processing error: " . $e->getMessage());
            return ['success' => false, 'error' => 'GreÅ¡ka pri procesiranju webhook-a'];
        }
    }

    /**
     * ENHANCED ACCESS LOOKUP BY EMAIL
     */
    public function lookupAccessByEmailSecure($email, $eventId, $securityData = []) {
    $this->log("Enhanced access lookup for email: $email, event: $eventId");
    
    $validationResult = $this->securityLayer->validatePaymentRequest([
        'email' => $email, 'event_id' => $eventId,
        'recaptcha_token' => $securityData['recaptcha_token'] ?? '',
        'device_fingerprint' => $securityData['device_fingerprint'] ?? '',
        'lookup_request' => true
    ]);
    
    if (!$validationResult['allowed']) {
        return ['success' => false, 'error' => $validationResult['reason']];
    }
    
    $access = $this->loadAccess();
    $currentDeviceId = $securityData['device_id'] ?? null;

    if (empty($currentDeviceId)) {
         return ['success' => false, 'error' => 'Device ID nedostaje iz zahteva.'];
    }

    foreach ($access as &$record) {
        if ($record['event_id'] === $eventId && $record['customer_email'] === $email) {
            
            // Proveri da li je pristup istekao
            if (strtotime($record['expires_at']) < time()) {
                return ['success' => false, 'error' => 'Pristup za ovaj email je istekao.'];
            }

            // KLJUČNO: Očisti neaktivne uređaje PRE provere
            $this->cleanupInactiveDevices($record);
            
            // NOVA LOGIKA: Striktna device validacija čak i za email lookup
            $deviceCheck = $this->validateDevice($record, $currentDeviceId);
            if (!$deviceCheck['allowed']) {
                $this->saveAccess($access); // Sačuvaj violation count
                return ['success' => false, 'error' => $deviceCheck['reason']];
            }

            // Ako je sve u redu, ažuriraj pristup i vrati podatke
            $this->updateDeviceAccess($record, $currentDeviceId);
            $record['last_accessed'] = date('Y-m-d H:i:s');
            $this->saveAccess($access);

            $event = $this->getEventWithStream($eventId);
            return [
                'success' => true,
                'access_token' => $record['token'],
                'event' => $event,
                'device_id' => $currentDeviceId
            ];
        }
    }
    return ['success' => false, 'error' => 'Pristup nije pronađen za uneti email.'];
}

public function lookupAccessByIP($eventId, $ipAddress) {
    $this->log("Looking up access by IP: $ipAddress for event: $eventId");
    
    $purchases = $this->loadPurchases();
    $access = $this->loadAccess();
    
    foreach ($purchases as $purchase) {
        if (($purchase['ip_address'] ?? '') === $ipAddress && 
            ($purchase['event_id'] ?? '') === $eventId &&
            ($purchase['status'] ?? '') === 'completed') {
            
            foreach ($access as &$record) {
                if (($record['purchase_id'] ?? '') === $purchase['id']) {
                    if (strtotime($record['expires_at']) > time()) {
                        
                        $this->cleanupInactiveDevices($record);
                        $this->saveAccess($access);
                        
                        // KLJUÄŒNA PROVERA: Dozvoli IP login SAMO AKO NEMA aktivnih sesija
                        if (empty($record['active_devices'])) {
                            $event = $this->getEventWithStream($eventId);
                            $this->log("IP access allowed for $ipAddress. No active sessions.");
                            return [
                                'success' => true,
                                'access_token' => $record['token'],
                                'event' => $event
                            ];
                        } else {
                            $this->log("IP access DENIED for $ipAddress. Active session exists.");
                            return ['success' => false, 'error' => 'Active session exists.'];
                        }
                    }
                }
            }
        }
    }
    
    return ['success' => false, 'error' => 'Nema kupovine sa ove IP adrese'];
}
    
    /**
     * SIMULATE PAYMENT SUCCESS FOR DEVELOPMENT
     */
    private function simulatePaymentSuccess($eventId, $email, $name, $amount, $event) {
        $mockPaymentIntent = [
            'id' => 'pi_dev_' . uniqid(),
            'amount' => $amount,
            'currency' => $event['currency'],
            'metadata' => [
                'event_id' => $eventId,
                'customer_email' => $email,
                'customer_name' => $name,
                'product_type' => 'ppv'
            ]
        ];
        
        $purchase = $this->processSuccessfulPayment($mockPaymentIntent);
        $accessToken = $this->grantAccess($eventId, $email, $purchase['id']);
        
        return [
            'success' => true,
            'simulated' => true,
            'access_token' => $accessToken,
            'amount' => $amount,
            'currency' => $event['currency'],
            'event_title' => $event['title'],
            'message' => 'DEVELOPMENT: Payment simulated successfully'
        ];
    }

     /**
     * SIMULATE PAYMENT COMPLETION FOR DEVELOPMENT
     */
    private function simulatePaymentCompletion($paymentIntentId, $email = '', $name = '') {
        // ... (deo za proveru postojeÄ‡ih kupovina ostaje isti) ...
        
        // Ako ne postoji, napravi novu samo za development
        $mockPurchase = [
            'id' => uniqid('ppv_dev_', true),
            'event_id' => 'bif-2-test', // Prilagodi ako je potrebno
            // KORISTIMO PROSLEÄENE PODATKE, A 'dev@test.com' JE SAMO REZERVA
            'customer_email' => !empty($email) ? $email : 'dev@test.com',
            'customer_name' => !empty($name) ? $name : 'Development User',
            'amount' => 149900,
            'currency' => 'rsd',
            'payment_intent_id' => $paymentIntentId,
            'status' => 'completed',
            'purchased_at' => date('Y-m-d H:i:s'),
            'access_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'localhost'
        ];
        
        $purchases[] = $mockPurchase;
        $this->savePurchases($purchases);
        
        $accessToken = $this->grantAccess($mockPurchase['event_id'], $mockPurchase['customer_email'], $mockPurchase['id']);
        
        return [
            'success' => true,
            'access_token' => $accessToken,
            'purchase' => $mockPurchase,
            'development_mode' => true
        ];
    }
    
    /**
     * GET SECURITY STATISTICS
     */
    public function getSecurityStats() {
        // This would be called from admin panel
        return [
            'success' => true,
            'stats' => [
                'total_blocks' => $this->getSecurityBlockCount(),
                'block_reasons' => $this->getBlockReasons(),
                'fraud_attempts' => $this->getFraudAttempts(),
                'bot_detections' => $this->getBotDetections(),
                'ip_blocks' => $this->getIPBlocks(),
                'recaptcha_failures' => $this->getRecaptchaFailures()
            ]
        ];
    }
    
    // Helper methods for security stats
    private function getSecurityBlockCount() {
        // Read from security logs
        return rand(0, 50); // Mock
    }
    
    private function getBlockReasons() {
        return [
            'RECAPTCHA_FAILED' => rand(5, 20),
            'IP_BLOCKED' => rand(2, 10),
            'FRAUD_DETECTED' => rand(1, 5),
            'BOT_DETECTED' => rand(3, 15),
            'VELOCITY_EXCEEDED' => rand(1, 8)
        ];
    }
    
    private function getFraudAttempts() {
        return rand(1, 10);
    }
    
    private function getBotDetections() {
        return rand(5, 25);
    }
    
    private function getIPBlocks() {
        return rand(2, 15);
    }
    
    private function getRecaptchaFailures() {
        return rand(10, 30);
    }


    private function updateAccessTracking($token) {
        $access = $this->loadAccess();
        
        foreach ($access as &$record) {
            if ($record['token'] === $token) {
                $record['last_accessed'] = date('Y-m-d H:i:s');
                $record['access_count'] = ($record['access_count'] ?? 0) + 1;
                break;
            }
        }
        
        $this->saveAccess($access);
    }
    
    private function processSuccessfulPayment($paymentIntent) {
        $metadata = $paymentIntent['metadata'];
        $eventId = $metadata['event_id'];
        $email = $metadata['customer_email'];
        $name = $metadata['customer_name'];
        
        $purchases = $this->loadPurchases();
        $purchase = [
            'id' => uniqid('ppv_', true),
            'event_id' => $eventId,
            'customer_email' => $email,
            'customer_name' => $name,
            'amount' => $paymentIntent['amount'],
            'currency' => $paymentIntent['currency'],
            'payment_intent_id' => $paymentIntent['id'],
            'status' => 'completed',
            'purchased_at' => date('Y-m-d H:i:s'),
            'access_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $purchases[] = $purchase;
        $this->savePurchases($purchases);
        
        $accessToken = $this->grantAccess($eventId, $email, $purchase['id']);
        
        $this->log("Payment processed for: $email, Access token created: " . substr($accessToken, 0, 10) . "...");
        // PoÅ¡alji email sa pristupnim linkom
        $this->log("Payment processed for: $email from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $this->sendPurchaseEmail($email, $accessToken, $eventId);
        return $purchase;
    }

     private function sendPurchaseEmail($email, $accessToken, $eventId) {
    $mail = new PHPMailer(true);

    try {
        // Pronađi naziv eventa
        $events = $this->loadEvents();
        $eventTitle = 'BIF Event';
        foreach ($events as $event) {
            if ($event['id'] === $eventId) {
                $eventTitle = $event['title'];
                break;
            }
        }

        $siteUrl = $_ENV['SITE_URL'] ?? 'https://bif.events';
        $watchUrl = rtrim($siteUrl, '/') . "/watch.php?token=" . urlencode($accessToken);

        // --- SMTP PODEŠAVANJA ---
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USERNAME'];
        $mail->Password   = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = $_ENV['SMTP_ENCRYPTION'];
        $mail->Port       = $_ENV['SMTP_PORT'];
        
        // ✅ KRITIČNO: UTF-8 ENCODING
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64'; // Dodaj ovo!

        // --- PODACI O POŠILJAOCU I PRIMAOCU ---
        $mail->setFrom('business@bif.events', 'BIF Events');
        $mail->addAddress($email);
        $mail->addReplyTo('support@bif.events', 'BIF Podrška');

        // --- SADRŽAJ EMAILA ---
        $mail->isHTML(true);
        $mail->Subject = 'BIF PPV - Vaš pristup za ' . $eventTitle;
        
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <h2>Poštovani,</h2>
            <p>Hvala vam na kupovini PPV pristupa za događaj: <strong>{$eventTitle}</strong>!</p>
            <p>Vaš jedinstveni link za gledanje je spreman. Kliknite na dugme ispod da biste pristupili streamu:</p>
            <p style='text-align: center; margin: 30px 0;'>
                <a href='{$watchUrl}' style='background-color: #c41e3a; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>GLEDAJ UŽIVO</a>
            </p>
            <p><strong>Sačuvajte ovaj email!</strong> Ovaj link možete koristiti na bilo kom uređaju (jedan po jedan).</p>
            <p>Ako link iznad ne radi, kopirajte i nalepite sledeću adresu u vaš browser:<br>
            <a href='{$watchUrl}'>{$watchUrl}</a></p>
            <hr>
            <p style='font-size: 0.9em; color: #777;'>
                Ako promenite uređaj, potrebno je da zatvorite stream na starom i sačekate 2 minuta pre otvaranja na novom.
            </p>
            <p>Uživajte u programu!<br><strong>BIF Team</strong></p>
        </div>";

        $mail->AltBody = "Poštovani,\n\nHvala vam na kupovini PPV pristupa!\n\nVaš link za gledanje je: {$watchUrl}\n\nSačuvajte ovaj email! Ovaj link možete koristiti na bilo kom uređaju (jedan po jedan).\n\nUživajte u programu!\nBIF Team";

        $mail->send();
        $this->log('Confirmation email sent successfully to ' . $email . ' using PHPMailer.');
    } catch (Exception $e) {
        $this->log("PHPMailer Error: Failed to send email to {$email}. Mailer Error: {$mail->ErrorInfo}");
    }
}
    
    private function grantAccess($eventId, $email, $purchaseId) {
        $access = $this->loadAccess();
        
        $accessToken = $this->generateAccessToken();
        $accessRecord = [
            'token' => $accessToken,
            'event_id' => $eventId,
            'customer_email' => $email,
            'purchase_id' => $purchaseId,
            'granted_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'last_accessed' => null,
            'access_count' => 0,
            
            // Anti-sharing protection
            'max_concurrent_devices' => 1,
            'active_devices' => [],
            'device_whitelist' => [],
            'last_device_check' => null,
            'sharing_violations' => 0,
            'device_change_cooldown' => 3600,
            
            // Tracking
            'granted_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        $access[] = $accessRecord;
        $this->saveAccess($access);
        
        $this->log("Access granted with device restrictions - Token: " . substr($accessToken, 0, 10) . "...");
        
        return $accessToken;
    }
    
     public function verifyAccess($token, $deviceId = null) {
    $this->log("Verifying access for token: " . substr($token, 0, 10) . " with Device ID: " . substr($deviceId, 0, 10));

    if (empty($token) || empty($deviceId)) {
        return ['success' => false, 'error' => 'Token i Device ID su obavezni'];
    }

    $access = $this->loadAccess();
    $currentTime = time();
    $recordFound = false;

    foreach ($access as &$record) {
        if ($record['token'] === $token) {
            $recordFound = true;
            // 1. Provera da li je pristup istekao
            if (strtotime($record['expires_at']) < $currentTime) {
                return ['success' => false, 'error' => 'Pristup je istekao'];
            }

            // 2. Provera da li je event zavrÅ¡en
            $event = $this->getEventWithStream($record['event_id']);
            if (!$event || $event['status'] === 'finished') {
                return ['success' => false, 'error' => 'Ovaj event je zavrÅ¡en.', 'event_finished' => true];
            }

            // 3. Provera ureÄ‘aja (ova funkcija u sebi poziva cleanup)
            $deviceCheck = $this->validateDevice($record, $deviceId);
            if (!$deviceCheck['allowed']) {
                $this->saveAccess($access); // SaÄuvaj promenu (npr. violation count)
                return ['success' => false, 'error' => $deviceCheck['reason']];
            }
            
            // 4. AÅ¾uriraj podatke o pristupu
            $record['last_accessed'] = date('Y-m-d H:i:s');
            $record['access_count'] = ($record['access_count'] ?? 0) + 1;
            
            // 5. SaÄuvaj SVE promene koje su se desile
            $this->saveAccess($access);
            
            // 6. Nastavi dalje...
            $this->trackView($record['event_id'], $record['customer_email']);

            return [
                'success' => true,
                'event' => $event,
                'access_expires' => $record['expires_at'],
                'customer_email' => $record['customer_email'],
                'device_id' => $deviceId
            ];
        }
    }
    
    // Ako petlja proÄ‘e a token nije naÄ‘en
    if (!$recordFound) {
        return ['success' => false, 'error' => 'Neispravan pristupni token'];
    }
}
// Nova funkcija trackView ide POSLE verifyAccess, ne unutar nje
private function trackView($eventId, $email) {
    $analyticsFile = PPV_DATA_DIR . '/analytics.json';
    $analytics = file_exists($analyticsFile) ? json_decode(file_get_contents($analyticsFile), true) : [];
    
    $analytics[] = [
        'event_id' => $eventId,
        'email' => $email,
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // ÄŒuvaj samo poslednjih 1000 zapisa
    if (count($analytics) > 1000) {
        $analytics = array_slice($analytics, -1000);
    }
    
    file_put_contents($analyticsFile, json_encode($analytics, JSON_PRETTY_PRINT));
}
    
    private function getEventWithStream($eventId) {
    $events = $this->loadEvents();
    foreach ($events as $event) {
        if ($event['id'] === $eventId) {
            // NE BRIÅ ITE stream_url ovde!
            return $event; // Include stream_url for verified access
        }
    }
    return null;
}

    private function validateDevice(&$record, $currentDeviceId) {
    $currentTime = time();
    $maxDevices = $record['max_concurrent_devices'] ?? 1;

    // Prvo, očisti stare, neaktivne sesije
    $this->cleanupInactiveDevices($record);

    // Proveri da li je trenutni uređaj već poznat i aktivan
    if ($this->isDeviceKnown($record, $currentDeviceId)) {
        // Uređaj je poznat. Ovo je verovatno samo osvežavanje stranice.
        $this->updateDeviceAccess($record, $currentDeviceId);
        $this->log("KNOWN DEVICE REFRESH: Email {$record['customer_email']}, Device: " . substr($currentDeviceId, -8));
        return ['allowed' => true, 'reason' => 'Known device refresh'];
    }

    // Uređaj nije poznat. Proveri da li ima slobodnih mesta.
    $activeDevicesCount = count($record['active_devices'] ?? []);

    if ($activeDevicesCount >= $maxDevices) {
        // Nema slobodnih mesta. Ovo je pokušaj deljenja.
        $record['sharing_violations'] = ($record['sharing_violations'] ?? 0) + 1;
        $this->log("SHARING ATTEMPT BLOCKED: Email {$record['customer_email']} trying to access from new device. Active devices: " . $activeDevicesCount);
        
        // Detaljnija poruka
        $firstDeviceInfo = reset($record['active_devices']);
        $timeAgo = $currentTime - $firstDeviceInfo['last_seen'];
        $minutesAgo = round($timeAgo / 60, 1);
        
        return [
            'allowed' => false,
            'reason' => "Pristup je već aktivan na drugom uređaju (aktivan pre " . $minutesAgo . " minuta). Zatvorite stream na prvom uređaju i sačekajte 2 minuta, pa pokušajte ponovo."
        ];
    }

    // Ima slobodnih mesta. Ovo je novi, legitiman uređaj.
    $this->updateDeviceAccess($record, $currentDeviceId);
    $this->log("NEW DEVICE ALLOWED: Email {$record['customer_email']}, Device: " . substr($currentDeviceId, -8));
    return ['allowed' => true, 'reason' => 'New device allowed'];
}

    public function getSharingViolations() {
        $access = $this->loadAccess();
        $violations = [];
        
        foreach ($access as $record) {
            if (($record['sharing_violations'] ?? 0) > 0) {
                $violations[] = [
                    'email' => $record['customer_email'],
                    'event_id' => $record['event_id'],
                    'violations' => $record['sharing_violations'],
                    'active_devices' => count($record['active_devices'] ?? []),
                    'last_violation' => $record['last_device_check'] ?? null,
                    'device_details' => $record['active_devices'] ?? []
                ];
            }
        }
        
        // Sort by violation count
        usort($violations, function($a, $b) {
            return $b['violations'] - $a['violations'];
        });
        
        return $violations;
    }

    // Update device access tracking
    private function updateDeviceAccess(&$record, $deviceId) {
        $currentTime = time();
        
        if (!isset($record['active_devices'])) {
            $record['active_devices'] = [];
        }
        
        $record['active_devices'][$deviceId] = [
            'last_seen' => $currentTime,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'first_seen' => $record['active_devices'][$deviceId]['first_seen'] ?? $currentTime
        ];
        
        $record['last_device_check'] = $currentTime;
    }

    // Cleanup inactive devices
    private function cleanupInactiveDevices(&$record) {
    if (!isset($record['active_devices'])) return;
    
    $currentTime = time();
    $timeout = 150; // Povećaj na 2.5 minuta umesto 2
    
    $cleaned = false;
    foreach ($record['active_devices'] as $deviceId => $info) {
        if (($currentTime - $info['last_seen']) > $timeout) {
            unset($record['active_devices'][$deviceId]);
            $this->log("Device session expired: " . substr($deviceId, -8) . " (inactive " . round(($currentTime - $info['last_seen'])/60, 1) . " min)");
            $cleaned = true;
        }
    }
}

    // Check if device is known
    private function isDeviceKnown($record, $deviceId) {
        return isset($record['active_devices'][$deviceId]);
    }

    // Generate unique device ID
    private function generateDeviceId() {
        $factors = [
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'unknown',
        ];
        
        return hash('sha256', implode('|', $factors));
    }

    // Heartbeat system
    public function updateHeartbeat($token, $deviceId) {
        $access = $this->loadAccess();
        
        foreach ($access as &$record) {
            if ($record['token'] === $token) {
                $currentDeviceId = $this->generateDeviceId();
                
                if ($deviceId !== $currentDeviceId) {
                    return ['success' => false, 'error' => 'Device ID mismatch'];
                }
                
                $this->updateDeviceAccess($record, $deviceId);
                $this->saveAccess($access);
                $this->cleanupInactiveDevices($record);
                
                return [
                    'success' => true,
                    'active_devices' => count($record['active_devices']),
                    'max_devices' => $record['max_concurrent_devices'] ?? 1
                ];
            }
        }
        
        return ['success' => false, 'error' => 'Token not found'];
    }

    // Enhanced heartbeat method
    public function enhancedHeartbeat($token, $deviceId, $additionalData = []) {
        try {
            $this->log("Enhanced heartbeat - Token: " . substr($token, 0, 10) . "..., Device: " . substr($deviceId, 0, 10) . "...");
            
            // Validate token
            $accessToken = $this->validateAccessToken($token);
            if (!$accessToken) {
                return ['success' => false, 'error' => 'Invalid token'];
            }
            
            // Check device limits
            $deviceValidation = $this->validateDeviceAccess($token, $deviceId);
            if (!$deviceValidation['allowed']) {
                $this->logSecurityViolation($token, $deviceId, 'device_limit_violation', $deviceValidation);
                return ['success' => false, 'error' => $deviceValidation['reason']];
            }
            
            // Update device session
            $this->updateDeviceSession($token, $deviceId, $additionalData);
            
            // Get active device count
            $activeDevices = $this->getActiveDeviceCount($token);
            
            // Check for violations
            $violationsCount = $additionalData['violations_count'] ?? 0;
            if ($violationsCount > 3) {
                $this->logSecurityViolation($token, $deviceId, 'multiple_client_violations', [
                    'violation_count' => $violationsCount,
                    'violations' => $additionalData['protection_status']['violations'] ?? []
                ]);
            }
            
            $this->log("Enhanced heartbeat successful - Active devices: $activeDevices");
            
            return [
                'success' => true,
                'active_devices' => $activeDevices,
                'max_devices' => $this->securityConfig['drm']['max_concurrent_devices'] ?? 1,
                'next_heartbeat' => time() + ($this->securityConfig['drm']['heartbeat_timeout'] ?? 120),
                'server_time' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            $this->log("Enhanced heartbeat error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Heartbeat failed'];
        }
    }

    // Report violation method
    public function reportViolation($token, $deviceId, $violation) {
        try {
            $this->logSecurityViolation($token, $deviceId, $violation['type'], [
                'client_violation' => true,
                'details' => $violation['details'] ?? [],
                'timestamp' => $violation['timestamp'] ?? time(),
                'total_violations' => $violation['total_violations'] ?? 1
            ]);
            
            // Determine response based on violation type
            $response = $this->handleViolationResponse($violation['type'], $violation['total_violations'] ?? 1);
            
            return [
                'success' => true,
                'action' => $response['action'],
                'message' => $response['message']
            ];
            
        } catch (Exception $e) {
            $this->log("Report violation error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Could not report violation'];
        }
    }

    // Get realtime stats method
    public function getRealtimeStats() {
        try {
            $stats = [
                'metrics' => [
                    'active_viewers' => $this->getActiveViewersCount(),
                    'total_revenue' => $this->getTodayRevenue(),
                    'violations_count' => $this->getTodayViolationsCount(),
                    'conversion_rate' => $this->getConversionRate(),
                    'viewers_change' => $this->getViewersChange(),
                    'revenue_change' => $this->getRevenueChange(),
                    'violations_last_hour' => $this->getViolationsLastHour()
                ],
                'charts' => [
                    'viewers' => ['current' => $this->getActiveViewersCount()],
                    'devices' => $this->getDeviceDistribution()
                ],
                'alerts' => $this->getActiveAlerts(),
                'streams' => $this->getActiveStreams(),
                'violations' => $this->getRecentViolations(10)
            ];
            
            return $stats;
            
        } catch (Exception $e) {
            $this->log("Get realtime stats error: " . $e->getMessage());
            return [
                'metrics' => ['active_viewers' => 0, 'total_revenue' => 0, 'violations_count' => 0, 'conversion_rate' => 0],
                'charts' => ['viewers' => ['current' => 0], 'devices' => []],
                'alerts' => [],
                'streams' => [],
                'violations' => []
            ];
        }
    }

    // Helper methods for enhanced functionality
    private function validateAccessToken($token) {
        // Try database first
        if ($this->database->isUsingDatabase()) {
            return $this->database->fetchOne(
                "SELECT * FROM access_tokens WHERE token = ? AND is_active = 1 AND expires_at > NOW()",
                [$token]
            );
        }
        
        // Fallback to JSON
        $access = $this->loadAccess();
        foreach ($access as $record) {
            if ($record['token'] === $token && 
                strtotime($record['expires_at']) > time()) {
                return $record;
            }
        }
        return false;
    }

    private function validateDeviceAccess($token, $deviceId) {
        $activeSessions = $this->getActiveDeviceSessions($token);
        $maxDevices = $this->securityConfig['drm']['max_concurrent_devices'] ?? 1;
        
        // Check if this device is already active
        foreach ($activeSessions as $session) {
            if ($session['device_id'] === $deviceId) {
                return ['allowed' => true, 'reason' => 'Known device'];
            }
        }
        
        // Check device limit
        if (count($activeSessions) >= $maxDevices) {
            return [
                'allowed' => false,
                'reason' => 'Pristup veÄ‡ aktivan na drugom ureÄ‘aju. Molimo zatvorite postojeÄ‡u sesiju.'
            ];
        }
        
        return ['allowed' => true, 'reason' => 'New device allowed'];
    }

    private function getActiveDeviceSessions($token) {
        // Simplified - returns mock data for now
        return []; // Placeholder
    }

    private function updateDeviceSession($token, $deviceId, $data) {
        // Simplified - in production version should update database
        $this->log("Device session updated: $deviceId");
    }

    private function getActiveDeviceCount($token) {
        // Simplified - returns 1 for now
        return 1; // Placeholder
    }

    private function logSecurityViolation($token, $deviceId, $type, $details) {
        $violation = [
            'token' => substr($token, 0, 10) . '...',
            'device_id' => substr($deviceId, 0, 10) . '...',
            'type' => $type,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->log("SECURITY VIOLATION: " . json_encode($violation));
    }

    private function handleViolationResponse($type, $totalViolations) {
        $criticalTypes = ['devtools_console_detection', 'screen_recording_attempt', 'media_recorder_attempt'];
        
        if (in_array($type, $criticalTypes)) {
            return [
                'action' => 'terminate',
                'message' => 'Stream terminated due to critical security violation'
            ];
        }
        
        if ($totalViolations >= 5) {
            return [
                'action' => 'suspend',
                'message' => 'Too many violations detected'
            ];
        }
        
        return [
            'action' => 'warning',
            'message' => 'Security warning logged'
        ];
    }

    // Mock methods for stats (replace with real data):
    private function getActiveViewersCount() { return rand(1, 50); }
    private function getTodayRevenue() { return rand(5000, 50000) * 100; } // in cents
    private function getTodayViolationsCount() { return rand(0, 5); }
    private function getConversionRate() { return rand(10, 25); }
    private function getViewersChange() { return rand(-5, 15); }
    private function getRevenueChange() { return rand(0, 20); }
    private function getViolationsLastHour() { return rand(0, 2); }
    private function getDeviceDistribution() {
        return [
            'desktop' => rand(10, 30),
            'mobile' => rand(5, 20),
            'tablet' => rand(1, 5)
        ];
    }
    private function getActiveAlerts() { return []; }
    private function getActiveStreams() {
        return [[
            'title' => 'BIF 1: New Rise',
            'status' => 'live',
            'start_time' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'viewers' => $this->getActiveViewersCount(),
            'revenue' => number_format($this->getTodayRevenue() / 100, 0) . ' RSD',
            'quality' => 'HD',
            'buffer_health' => rand(85, 99)
        ]];
    }
    private function getRecentViolations($limit) { return []; }
    
    // Utility methods
    private function verifyStripeSignature($payload, $signature, $secret) {
        $elements = explode(',', $signature);
        $timestamp = null;
        $signatures = [];
        
        foreach ($elements as $element) {
            if (strpos($element, 't=') === 0) {
                $timestamp = substr($element, 2);
            } elseif (strpos($element, 'v1=') === 0) {
                $signatures[] = substr($element, 3);
            }
        }
        
        if (!$timestamp || empty($signatures)) {
            return false;
        }
        
        // Check timestamp (within 5 minutes)
        if (abs(time() - $timestamp) > 300) {
            return false;
        }
        
        $payloadForSig = $timestamp . '.' . $payload;
        $expectedSig = hash_hmac('sha256', $payloadForSig, $secret);
        
        foreach ($signatures as $sig) {
            if (hash_equals($expectedSig, $sig)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function generateAccessToken() {
        return bin2hex(random_bytes(32));
    }
    
    public function sendJsonResponse($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

// Request handling
try {
    $ppv = new BIF_PPV_System(); // Use enhanced version

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle Stripe webhook (unchanged)
        if (!empty($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
            $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'];
            $payload = file_get_contents('php://input');
            $result = $ppv->handleStripeWebhook($payload, $signature);
            $ppv->sendJsonResponse($result);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $ppv->sendJsonResponse(['success' => false, 'error' => 'Invalid JSON data'], 400);
        }
        
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'create_payment':
                // CSRF PROTECTION - Validacija tokena
                $csrfResult = CSRF_Protection::validateRequest();
                if (!$csrfResult['valid']) {
                    $ppv->sendJsonResponse([
                        'success' => false,
                        'error' => 'CSRF validation failed',
                        'message' => $csrfResult['error']
                    ], 403);
                }

                $eventId = trim($input['event_id'] ?? '');
                $email = trim($input['email'] ?? '');
                $name = trim($input['name'] ?? '');
                $paymentMethodId = trim($input['payment_method_id'] ?? ''); // <<-- DODAJ OVU LINIJU

                // ENHANCED: Extract all security data
                $securityData = [
                    'device_fingerprint' => $input['device_fingerprint'] ?? '',
                    'recaptcha_token' => $input['recaptcha_token'] ?? '',
                    'form_time' => $input['form_time'] ?? 0,
                    'mouse_movements' => $input['mouse_movements'] ?? 0,
                    'keystrokes' => $input['keystrokes'] ?? 0,
                    'interaction_events' => $input['interaction_events'] ?? 0,
                    'js_challenge_response' => $input['js_challenge_response'] ?? '',
                    'automation_indicators' => $input['automation_indicators'] ?? [],
                    'webdriver' => $input['webdriver'] ?? false,
                    'website' => $input['website'] ?? '', // Honeypot
                    'session_duration' => $input['session_duration'] ?? 0,
                    'screen_width' => $input['screen_width'] ?? 0,
                    'screen_height' => $input['screen_height'] ?? 0,
                    'timezone_offset' => $input['timezone_offset'] ?? 0,
                    'full_fingerprint' => $input['full_fingerprint'] ?? [],
                    'behavior_data' => $input['behavior_data'] ?? [],
                    'memory_info' => $input['memory_info'] ?? null,
                    'connection_info' => $input['connection_info'] ?? null
                ];
                
                if (empty($eventId) || empty($email) || empty($name)) {
                    $ppv->sendJsonResponse(['success' => false, 'error' => 'Required fields missing'], 400);
                }
                
                // Use enhanced secure payment creation
                $result = $ppv->createPaymentIntentSecure($eventId, $email, $name, $paymentMethodId, $securityData);
                $ppv->sendJsonResponse($result);
                break;

            case 'verify_access':
                $token = trim($input['token'] ?? '');
                $deviceId = trim($input['device_id'] ?? '');
                
                if (empty($token)) {
                    $ppv->sendJsonResponse(['success' => false, 'error' => 'Token is required'], 400);
                }
                
                $result = $ppv->verifyAccess($token, $deviceId);
                $ppv->sendJsonResponse($result);
                break;

            case 'lookup_access':
                $email = trim($input['email'] ?? '');
                $eventId = trim($input['event_id'] ?? '');
                
                // ENHANCED: Extract security data for lookup too
                $securityData = [
                    'device_fingerprint' => $input['device_fingerprint'] ?? '',
                    'recaptcha_token' => $input['recaptcha_token'] ?? '',
                    'device_id' => $input['device_id'] ?? ''
                ];
                
                if (empty($email) || empty($eventId)) {
                    $ppv->sendJsonResponse(['success' => false, 'error' => 'Email and Event ID are required'], 400);
                }
                
                // Use enhanced secure lookup
                $result = $ppv->lookupAccessByEmailSecure($email, $eventId, $securityData);
                $ppv->sendJsonResponse($result);
                break;

                case 'check_ip_access':
    $eventId = trim($input['event_id'] ?? '');
    if (empty($eventId)) {
        $ppv->sendJsonResponse(['success' => false, 'error' => 'Event ID required'], 400);
    }
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $result = $ppv->lookupAccessByIP($eventId, $ipAddress);
    $ppv->sendJsonResponse($result);
    break;

            case 'check_payment':
                $paymentIntentId = trim($input['payment_intent_id'] ?? '');
                
                if (empty($paymentIntentId)) {
                    $ppv->sendJsonResponse(['success' => false, 'error' => 'Payment Intent ID is required'], 400);
                }
                
                // ÄŒITAMO NOVE PODATKE
                $email = trim($input['email'] ?? '');
                $name = trim($input['name'] ?? '');

                $securityData = [
                    'device_fingerprint' => $input['device_fingerprint'] ?? '',
                    'recaptcha_token' => $input['recaptcha_token'] ?? ''
                ];
                
                // PROSLEÄUJEMO IH FUNKCIJI
                $result = $ppv->checkPaymentStatus($paymentIntentId, $securityData, $email, $name);
                $ppv->sendJsonResponse($result);
                break;

            case 'get_security_stats':
                // Admin only - add authentication check here
                $result = $ppv->getSecurityStats();
                $ppv->sendJsonResponse($result);
                break;

            // Keep all other existing cases unchanged...
            case 'heartbeat':
                $token = trim($input['token'] ?? '');
                $deviceId = trim($input['device_id'] ?? '');
                
                if (empty($token) || empty($deviceId)) {
                    $ppv->sendJsonResponse(['success' => false, 'error' => 'Token and Device ID are required'], 400);
                }
                
                $result = $ppv->updateHeartbeat($token, $deviceId);
                $ppv->sendJsonResponse($result);
                break;
                
            case 'enhanced_heartbeat':
                $token = trim($input['token'] ?? '');
                $deviceId = trim($input['device_id'] ?? '');
                $additionalData = $input ?? [];
                
                if (empty($token) || empty($deviceId)) {
                    $ppv->sendJsonResponse(['success' => false, 'error' => 'Token and Device ID are required'], 400);
                }
                
                $result = $ppv->enhancedHeartbeat($token, $deviceId, $additionalData);
                $ppv->sendJsonResponse($result);
                break;

                case 'debug_access':
    $email = trim($input['email'] ?? '');
    if (empty($email)) {
        $ppv->sendJsonResponse(['success' => false, 'error' => 'Email required'], 400);
    }
    
    // Učitaj access podatke direktno iz fajla umesto kroz privatnu metodu
    $accessFile = PPV_DATA_DIR . '/ppv_access.json';
    if (!file_exists($accessFile)) {
        $ppv->sendJsonResponse(['success' => false, 'error' => 'No access records found'], 404);
    }
    
    $accessContent = file_get_contents($accessFile);
    $access = json_decode($accessContent, true);
    if (!is_array($access)) {
        $access = [];
    }
    
    $result = [];
    
    foreach ($access as $record) {
        if ($record['customer_email'] === $email) {
            // Ispravi closure - treba da koristi $record sa use()
            $deviceDetails = array_map(function($deviceId, $device) {
                return [
                    'id' => substr($deviceId, -8),
                    'last_seen' => date('H:i:s', $device['last_seen']),
                    'seconds_ago' => time() - $device['last_seen']
                ];
            }, array_keys($record['active_devices'] ?? []), array_values($record['active_devices'] ?? []));
            
            $result[] = [
                'event_id' => $record['event_id'],
                'active_devices' => count($record['active_devices'] ?? []),
                'device_details' => $deviceDetails,
                'expires_at' => $record['expires_at'],
                'sharing_violations' => $record['sharing_violations'] ?? 0
            ];
        }
    }
    
    $ppv->sendJsonResponse(['success' => true, 'debug_data' => $result]);
    break;

            case 'report_violation':
                $token = trim($input['token'] ?? '');
                $deviceId = trim($input['device_id'] ?? '');
                $violation = $input['violation'] ?? [];
                
                if (empty($token) || empty($deviceId) || empty($violation)) {
                    $ppv->sendJsonResponse(['success' => false, 'error' => 'Missing required data'], 400);
                }
                
                $result = $ppv->reportViolation($token, $deviceId, $violation);
                $ppv->sendJsonResponse($result);
                break;

            case 'realtime_stats':
                $stats = $ppv->getRealtimeStats();
                $ppv->sendJsonResponse(['success' => true] + $stats);
                break;
                
            default:
                $ppv->sendJsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
        }
        
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'config':
                $config = $ppv->getConfig();
                $ppv->sendJsonResponse($config);
                break;
                
            case 'events':
                $events = $ppv->getEvents();
                $ppv->sendJsonResponse(['success' => true, 'events' => $events]);
                break;
                
            case 'event':
                $eventId = trim($_GET['event_id'] ?? '');
                if (empty($eventId)) {
                    $ppv->sendJsonResponse(['success' => false, 'error' => 'Event ID is required'], 400);
                }
                
                $event = $ppv->getEvent($eventId);
                if ($event) {
                    $ppv->sendJsonResponse(['success' => true, 'event' => $event]);
                } else {
                    $ppv->sendJsonResponse(['success' => false, 'error' => 'Event not found'], 404);
                }
                break;
                
            case 'check_payment':
                $paymentIntentId = trim($_GET['payment_intent_id'] ?? '');
                if (empty($paymentIntentId)) {
                    $ppv->sendJsonResponse(['success' => false, 'error' => 'Payment Intent ID is required'], 400);
                }
                
                $result = $ppv->checkPaymentStatus($paymentIntentId);
                $ppv->sendJsonResponse($result);
                break;

                
                
            default:
                $ppv->sendJsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
        }
    } else {
        $ppv->sendJsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }

} catch (Exception $e) {
    error_log("Enhanced PPV System Error: " . $e->getMessage());
    $ppv = new BIF_PPV_System();
    $ppv->sendJsonResponse(['success' => false, 'error' => 'Server error: ' . $e->getMessage()], 500);
} catch (Throwable $e) {
    error_log("FATAL ENHANCED ERROR: " . $e->getMessage());
    error_log("File: " . $e->getFile() . ", Line: " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(500);
    }
    
    echo json_encode([
        'success' => false, 
        'error' => 'Enhanced security system error: ' . $e->getMessage(),
        'debug' => isset($_GET['debug']) ? [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ] : null
    ], JSON_UNESCAPED_UNICODE);
    exit;
     
     }
?>