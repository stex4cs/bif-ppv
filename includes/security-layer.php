<?php
/**
 * KREIRAJ: includes/security-layer.php
 * Advanced Bot Protection & Anti-Fraud System
 */

class BIF_SecurityLayer {
    
    private $redis;
    private $db;
    private $config;
    private $logFile;
    
    public function __construct() {
        $this->logFile = dirname(__DIR__) . '/data/security.log';
        $this->config = [
            'max_attempts_per_ip' => 10,      // Maksimalno pokuÅ¡aja po IP u sat vremena
            'max_attempts_per_email' => 3,    // Maksimalno pokuÅ¡aja po email-u u sat vremena
            'ip_whitelist' => [],             // IP adrese koje su uvek dozvoljene
            'country_blacklist' => [],        // Zemlje koje su blokirane
            'honeypot_threshold' => 5,        // Broj pokuÅ¡aja za honeypot
            'velocity_check_window' => 300,   // 5 minuta za velocity check
            'device_fingerprint_required' => true,
            'recaptcha_required' => true,
            'fraud_score_threshold' => 70,    // Maksimalni fraud score
        ];
        
        $this->initializeRedis();
        $this->setupDatabase();
    }
    
    /**
     * GLAVNA METODA - Kompletna validacija pre payment intent-a
     */
    public function validatePaymentRequest($data) {
    $startTime = microtime(true);
    $ip = $this->getClientIP();
    $email = $data['email'] ?? '';
    $deviceFingerprint = $data['device_fingerprint'] ?? '';
    
    $this->log("ðŸ”’ Starting comprehensive security validation", [
        'ip' => $ip,
        'email' => $email,
        'device_fp' => substr($deviceFingerprint, 0, 12) . '...',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'is_lookup' => isset($data['lookup_request'])
    ]);
    
    $result = [
        'allowed' => false,
        'reason' => '',
        'security_score' => 0,
        'checks' => [],
        'requires_3ds' => false,
        'fraud_indicators' => []
    ];
    
    try {
        // --- POÄŒETAK KLJUÄŒNE IZMENE ---
        $isLookupRequest = isset($data['lookup_request']) && $data['lookup_request'] === true;
        // --- KRAJ KLJUÄŒNE IZMENE ---
        
        // 1. RECAPTCHA VALIDATION (uvek obavezna)
        $recaptchaResult = $this->validateRecaptcha($data['recaptcha_token'] ?? '');
        if (!$recaptchaResult['valid']) {
            return $this->blockRequest($result, 'RECAPTCHA_FAILED', $recaptchaResult['error']);
        }
        $result['security_score'] += 20;
        
        // 2. IP RATE LIMITING & GEOLOCATION (uvek obavezna)
        $ipResult = $this->validateIP($ip);
        if (!$ipResult['allowed']) {
            return $this->blockRequest($result, 'IP_BLOCKED', $ipResult['reason']);
        }
        $result['security_score'] += 15;
        
        // 3. EMAIL VALIDATION & RATE LIMITING (uvek obavezna)
        $emailResult = $this->validateEmail($email);
        if (!$emailResult['valid']) {
            return $this->blockRequest($result, 'EMAIL_INVALID', $emailResult['reason']);
        }
        $result['security_score'] += 15;
        
        // --- POÄŒETAK KLJUÄŒNE IZMENE ---
        // 4. DEVICE FINGERPRINTING (samo ako NIJE provera emaila)
        if (!$isLookupRequest) {
            $deviceResult = $this->validateDeviceFingerprint($deviceFingerprint);
            if (!$deviceResult['valid']) {
                return $this->blockRequest($result, 'DEVICE_SUSPICIOUS', $deviceResult['reason']);
            }
            $result['security_score'] += 15;
        } else {
            // Ako je lookup, samo dodeli poene da proÄ‘e dalje
            $result['security_score'] += 15;
        }
        // --- KRAJ KLJUÄŒNE IZMENE ---

        // 5. VELOCITY CHECKS (samo ako NIJE provera emaila)
        if (!$isLookupRequest) {
            $velocityResult = $this->checkVelocity($ip, $email, $deviceFingerprint);
            if (!$velocityResult['passed']) {
                return $this->blockRequest($result, 'VELOCITY_EXCEEDED', $velocityResult['reason']);
            }
            $result['security_score'] += 10;
        } else {
            $result['security_score'] += 10;
        }

        // 6. FRAUD DETECTION (samo ako NIJE provera emaila)
        if (!$isLookupRequest) {
            $fraudResult = $this->detectFraud($data);
            $result['fraud_indicators'] = $fraudResult['indicators'];
            if ($fraudResult['score'] > $this->config['fraud_score_threshold']) {
                return $this->blockRequest($result, 'FRAUD_DETECTED', 'High fraud probability: ' . $fraudResult['score']);
            }
            $result['security_score'] += (100 - $fraudResult['score']) / 5;
        } else {
            $result['security_score'] += 15;
        }
        
        // 7. HONEYPOT & BEHAVIORAL CHECKS (samo ako NIJE provera emaila)
        if (!$isLookupRequest) {
            $behaviorResult = $this->checkBehavior($data);
            if (!$behaviorResult['human']) {
                return $this->blockRequest($result, 'BOT_DETECTED', $behaviorResult['reason']);
            }
            $result['security_score'] += 10;
        } else {
            $result['security_score'] += 10;
        }
        
        // 8. DETERMINE 3DS REQUIREMENT
        $result['requires_3ds'] = $this->requires3DS($result);
        
        // 9. FINAL SCORING
        if ($result['security_score'] >= 70) {
            $result['allowed'] = true;
            $result['reason'] = 'All security checks passed';
            $this->logSuccessfulValidation($data, $result);
        } else {
            return $this->blockRequest($result, 'INSUFFICIENT_SCORE', 'Security score too low: ' . $result['security_score']);
        }
        
    } catch (Exception $e) {
        $this->log("ðŸš¨ Security validation error: " . $e->getMessage());
        return $this->blockRequest($result, 'VALIDATION_ERROR', 'Security system error');
    }
    
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    $this->log("âœ… Security validation completed in {$duration}ms", ['score' => $result['security_score']]);
    
    return $result;
}
    
    /**
     * RECAPTCHA V3 VALIDATION (PoboljÅ¡ana verzija sa cURL)
     */
    private function validateRecaptcha($token) {
        if (empty($token)) {
            return ['valid' => false, 'error' => 'reCAPTCHA token missing'];
        }
        
        $secretKey = $_ENV['RECAPTCHA_SECRET_KEY'] ?? '';
        if (empty($secretKey)) {
            // Dodajemo logovanje da znamo da kljuÄ nije uÄitan
            $this->log("RECAPTCHA_ERROR: Secret key is missing from .env file.");
            return ['valid' => false, 'error' => 'reCAPTCHA secret key not configured'];
        }
        
        $this->log("Verifying reCAPTCHA token with secret key: " . substr($secretKey, 0, 4) . "...");

        // Podaci koje Å¡aljemo Google-u
        $postData = http_build_query([
            'secret' => $secretKey,
            'response' => $token,
            'remoteip' => $this->getClientIP()
        ]);
        
        // Inicijalizacija cURL-a
        $ch = curl_init();
        
        // PodeÅ¡avanje cURL opcija
curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Vrati odgovor kao string
curl_setopt($ch, CURLOPT_POST, true);           // Koristi POST metod
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); // Dodaj podatke
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

// --- DODATE LINIJE ZA POPRAVKU "VRÄ†ENJA" NA LOCALHOST ---
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Timeout za konekciju (5 sekundi)
curl_setopt($ch, CURLOPT_TIMEOUT, 10);        // Ukupan timeout za izvrÅ¡enje (10 sekundi)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // KljuÄno: preskoÄi SSL verifikaciju
// -----------------------------------------------------------

        // Opciono, ali preporuÄeno za XAMPP - preskoÄi SSL proveru ako pravi problem
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        // IzvrÅ¡i zahtev
        $response = curl_exec($ch);
        
        // Provera da li je doÅ¡lo do greÅ¡ke u samom cURL-u (npr. nema interneta)
        if (curl_errno($ch)) {
            $curlError = curl_error($ch);
            curl_close($ch);
            $this->log("RECAPTCHA_CURL_ERROR: " . $curlError);
            return ['valid' => false, 'error' => 'Could not connect to Google service: ' . $curlError];
        }
        
        // Zatvori cURL konekciju
        curl_close($ch);
        
        // Dekodiraj JSON odgovor od Google-a
        $data = json_decode($response, true);
        
        $this->log("Google reCAPTCHA API Response: ", $data);

        if (!$data || !isset($data['success'])) {
            return ['valid' => false, 'error' => 'Invalid response from Google service'];
        }
        
        if (!$data['success']) {
            return [
                'valid' => false, 
                'error' => 'reCAPTCHA verification failed',
                'codes' => $data['error-codes'] ?? []
            ];
        }
        
        $score = $data['score'] ?? 0;
        
        if ($score < 0.5) {
            return [
                'valid' => false,
                'error' => 'reCAPTCHA score too low: ' . $score,
                'score' => $score
            ];
        }
        
        return [
            'valid' => true,
            'score' => $score,
            'action' => $data['action'] ?? '',
            'timestamp' => $data['challenge_ts'] ?? ''
        ];
    }
    
    /**
     * IP VALIDATION & RATE LIMITING
     */
    private function validateIP($ip) {
        // 1. Check whitelist
        if (in_array($ip, $this->config['ip_whitelist'])) {
            return ['allowed' => true, 'reason' => 'whitelisted'];
        }
        
        // 2. Check if blocked
        $blockKey = "ip_blocked:$ip";
        if ($this->redis && $this->redis->get($blockKey)) {
            return ['allowed' => false, 'reason' => 'IP temporarily blocked'];
        }
        
        // 3. Rate limiting
        $hourKey = "ip_attempts:" . $ip . ":" . date('YmdH');
        $attempts = $this->redis ? $this->redis->get($hourKey) : 0;
        
        if ($attempts >= $this->config['max_attempts_per_ip']) {
            // Block IP za 1 sat
            if ($this->redis) {
                $this->redis->setex($blockKey, 3600, time());
            }
            return ['allowed' => false, 'reason' => 'Too many attempts from IP'];
        }
        
        // 4. Geolocation check
        $geoData = $this->getIPGeolocation($ip);
        if ($geoData && in_array($geoData['country'], $this->config['country_blacklist'])) {
            return ['allowed' => false, 'reason' => 'Country blocked: ' . $geoData['country']];
        }
        
        // 5. VPN/Proxy detection
        if ($geoData && ($geoData['is_vpn'] || $geoData['is_proxy'])) {
            return ['allowed' => false, 'reason' => 'VPN/Proxy detected'];
        }
        
        return [
            'allowed' => true,
            'attempts' => intval($attempts),
            'geo' => $geoData,
            'reputation' => $this->getIPReputation($ip)
        ];
    }
    
    /**
     * EMAIL VALIDATION & RATE LIMITING
     */
    private function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'reason' => 'Invalid email format'];
        }
        
        // Rate limiting po email-u
        $hourKey = "email_attempts:" . hash('sha256', $email) . ":" . date('YmdH');
        $attempts = $this->redis ? $this->redis->get($hourKey) : 0;
        
        if ($attempts >= $this->config['max_attempts_per_email']) {
            return ['valid' => false, 'reason' => 'Too many attempts for this email'];
        }
        
        // Disposable email check
        if ($this->isDisposableEmail($email)) {
            return ['valid' => false, 'reason' => 'Disposable email not allowed'];
        }
        
        // Domain reputation
        $domain = substr(strrchr($email, "@"), 1);
        $domainScore = $this->getDomainReputation($domain);
        
        if ($domainScore < 30) {
            return ['valid' => false, 'reason' => 'Low reputation email domain'];
        }
        
        return [
            'valid' => true,
            'domain' => $domain,
            'domain_score' => $domainScore,
            'attempts' => intval($attempts)
        ];
    }
    
    /**
     * DEVICE FINGERPRINT VALIDATION
     */
    private function validateDeviceFingerprint($fingerprint) {
        if (empty($fingerprint)) {
            return ['valid' => false, 'reason' => 'Device fingerprint missing'];
        }
        
        // Check ako je fingerprint previse kratak (moguci fake)
        if (strlen($fingerprint) < 10) {
            return ['valid' => false, 'reason' => 'Invalid device fingerprint'];
        }
        
        // Check frequency ovog fingerprint-a
        $fpKey = "device_fp:" . hash('sha256', $fingerprint);
        $usage = $this->redis ? $this->redis->get($fpKey) : 0;
        
        // Ako isti fingerprint koristi vise od 5 korisnika, sumnjivo je
        if ($usage > 5) {
            return ['valid' => false, 'reason' => 'Device fingerprint used too frequently'];
        }
        
        return [
            'valid' => true,
            'usage_count' => intval($usage),
            'hash' => hash('sha256', $fingerprint)
        ];
    }
    
    /**
     * VELOCITY CHECKS
     */
    private function checkVelocity($ip, $email, $deviceFingerprint) {
        $window = $this->config['velocity_check_window'];
        $currentTime = time();
        
        // Multiple attempts from same IP in short time
        $ipKey = "velocity_ip:$ip";
        $ipAttempts = $this->getRecentAttempts($ipKey, $window);
        if (count($ipAttempts) > 3) {
            return ['passed' => false, 'reason' => 'Too many attempts from IP in short time'];
        }
        
        // Multiple different IPs for same email
        $emailKey = "velocity_email:" . hash('sha256', $email);
        $emailAttempts = $this->getRecentAttempts($emailKey, $window);
        if (count($emailAttempts) > 2) {
            return ['passed' => false, 'reason' => 'Email used from multiple IPs'];
        }
        
        // Device fingerprint velocity
        $deviceKey = "velocity_device:" . hash('sha256', $deviceFingerprint);
        $deviceAttempts = $this->getRecentAttempts($deviceKey, $window);
        if (count($deviceAttempts) > 2) {
            return ['passed' => false, 'reason' => 'Device used too frequently'];
        }
        
        return [
            'passed' => true,
            'ip_attempts' => count($ipAttempts),
            'email_attempts' => count($emailAttempts),
            'device_attempts' => count($deviceAttempts)
        ];
    }
    
    /**
     * FRAUD DETECTION
     */
    private function detectFraud($data) {
        $fraudScore = 0;
        $indicators = [];
        
        $ip = $this->getClientIP();
        $email = $data['email'] ?? '';
        $deviceFingerprint = $data['device_fingerprint'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // 1. Bot-like User Agent
        if ($this->isBotUserAgent($userAgent)) {
            $fraudScore += 30;
            $indicators[] = 'bot_user_agent';
        }
        
        // 2. Headless browser detection
        if ($this->isHeadlessBrowser($data)) {
            $fraudScore += 40;
            $indicators[] = 'headless_browser';
        }
        
        // 3. Timing analysis
        $timingAnomaly = $this->checkTimingAnomaly($data);
        if ($timingAnomaly) {
            $fraudScore += 25;
            $indicators[] = 'timing_anomaly';
        }
        
        // 4. Repeated patterns
        if ($this->hasRepeatedPatterns($email, $deviceFingerprint)) {
            $fraudScore += 35;
            $indicators[] = 'repeated_patterns';
        }
        
        // 5. Suspicious geolocation
        $geoData = $this->getIPGeolocation($ip);
        if ($geoData && $this->isSuspiciousLocation($geoData)) {
            $fraudScore += 20;
            $indicators[] = 'suspicious_location';
        }
        
        // 6. Email/name mismatch
        if ($this->hasEmailNameMismatch($data)) {
            $fraudScore += 15;
            $indicators[] = 'email_name_mismatch';
        }
        
        return [
            'score' => min($fraudScore, 100),
            'indicators' => $indicators,
            'risk_level' => $fraudScore > 70 ? 'high' : ($fraudScore > 40 ? 'medium' : 'low')
        ];
    }
    
    /**
     * BEHAVIORAL ANALYSIS
     */
    private function checkBehavior($data) {
        $suspiciousFactors = 0;
        $reasons = [];
        
        // 1. Check honeypot field
        if (!empty($data['website'])) { // Honeypot field
            $suspiciousFactors += 10;
            $reasons[] = 'honeypot_filled';
        }
        
        // 2. Too fast form submission
        $submitTime = $data['form_time'] ?? 0;
        if ($submitTime > 0 && $submitTime < 10) { // Manje od 10 sekundi
            $suspiciousFactors += 5;
            $reasons[] = 'too_fast_submission';
        }
        
        // 3. Missing JavaScript challenges
        if (empty($data['js_challenge_response'])) {
            $suspiciousFactors += 3;
            $reasons[] = 'missing_js_challenge';
        }
        
        // 4. Mouse/keyboard interaction missing
        if (empty($data['interaction_events'])) {
            $suspiciousFactors += 4;
            $reasons[] = 'no_user_interaction';
        }
        
        return [
            'human' => $suspiciousFactors < 8,
            'suspicion_score' => $suspiciousFactors,
            'reasons' => $reasons
        ];
    }
    
    /**
     * DETERMINE IF 3DS IS REQUIRED
     */
    private function requires3DS($validationResult) {
        $score = $validationResult['security_score'];
        $fraudScore = $validationResult['checks']['fraud']['score'] ?? 0;
        
        // Uvek zahtevaj 3DS ako:
        // - Security score je nizak
        // - Fraud score je visok
        // - Ima fraud indikatore
        
        if ($score < 80 || $fraudScore > 30 || !empty($validationResult['fraud_indicators'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * INCREMENT ATTEMPT COUNTERS
     */
    public function recordAttempt($ip, $email, $deviceFingerprint, $success = false) {
        if (!$this->redis) return;
        
        $timestamp = time();
        
        // IP attempts
        $hourKey = "ip_attempts:" . $ip . ":" . date('YmdH');
        $this->redis->incr($hourKey);
        $this->redis->expire($hourKey, 3600);
        
        // Email attempts
        $emailKey = "email_attempts:" . hash('sha256', $email) . ":" . date('YmdH');
        $this->redis->incr($emailKey);
        $this->redis->expire($emailKey, 3600);
        
        // Device fingerprint usage
        $fpKey = "device_fp:" . hash('sha256', $deviceFingerprint);
        $this->redis->incr($fpKey);
        $this->redis->expire($fpKey, 86400); // 24 hours
        
        // Velocity tracking
        $this->recordVelocityAttempt($ip, $email, $deviceFingerprint);
        
        // Log attempt
        $this->log($success ? "âœ… Valid attempt recorded" : "âš ï¸ Failed attempt recorded", [
            'ip' => $ip,
            'email' => hash('sha256', $email),
            'success' => $success
        ]);
    }
    
    private function recordVelocityAttempt($ip, $email, $deviceFingerprint) {
        if (!$this->redis) return;
        
        $timestamp = time();
        
        // Store recent attempts for velocity checking
        $ipKey = "velocity_ip:$ip";
        $this->redis->lpush($ipKey, $timestamp);
        $this->redis->expire($ipKey, 600); // 10 minutes
        
        $emailKey = "velocity_email:" . hash('sha256', $email);
        $this->redis->lpush($emailKey, $timestamp);
        $this->redis->expire($emailKey, 600);
        
        $deviceKey = "velocity_device:" . hash('sha256', $deviceFingerprint);
        $this->redis->lpush($deviceKey, $timestamp);
        $this->redis->expire($deviceKey, 600);
    }
    
    // HELPER METHODS
    
    private function getClientIP() {
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    private function getIPGeolocation($ip) {
        // Mock implementation - u produkciji koristi MaxMind GeoIP2 ili sliÄno
        static $cache = [];
        if (isset($cache[$ip])) return $cache[$ip];
        
        try {
            // Ovde bi bio poziv ka GeoIP servisu
            $data = [
                'country' => 'RS',
                'is_vpn' => false,
                'is_proxy' => false,
                'risk_score' => 10
            ];
            $cache[$ip] = $data;
            return $data;
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function getIPReputation($ip) {
        // Mock implementation - u produkciji koristi reputation servis
        return rand(50, 100);
    }
    
    private function isDisposableEmail($email) {
        $disposableDomains = [
            '10minutemail.com', 'tempmail.org', 'guerrillamail.com',
            'mailinator.com', 'temp-mail.org'
        ];
        
        $domain = substr(strrchr($email, "@"), 1);
        return in_array(strtolower($domain), $disposableDomains);
    }
    
    private function getDomainReputation($domain) {
        // Mock implementation
        $trustedDomains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com'];
        if (in_array(strtolower($domain), $trustedDomains)) return 90;
        return rand(30, 70);
    }
    
    private function isBotUserAgent($userAgent) {
        $botPatterns = ['bot', 'crawler', 'spider', 'scraper', 'headless', 'phantom'];
        $userAgentLower = strtolower($userAgent);
        foreach ($botPatterns as $pattern) {
            if (strpos($userAgentLower, $pattern) !== false) return true;
        }
        return false;
    }
    
    private function isHeadlessBrowser($data) {
        // Check for headless browser indicators in data
        return isset($data['webdriver']) || 
               (isset($data['screen_width']) && $data['screen_width'] == 0);
    }
    
    private function checkTimingAnomaly($data) {
        $submitTime = $data['form_time'] ?? 0;
        // PreviÅ¡e brzo (bot) ili previÅ¡e sporo (moÅ¾da sumnjivo)
        return $submitTime > 0 && ($submitTime < 5 || $submitTime > 3600);
    }
    
    private function hasRepeatedPatterns($email, $deviceFingerprint) {
        // Check da li postoji pattern u email/device kombinacijama
        return false; // Mock
    }
    
    private function isSuspiciousLocation($geoData) {
        return $geoData['risk_score'] > 70;
    }
    
    private function hasEmailNameMismatch($data) {
        // Check da li se ime u email-u razlikuje od name field-a
        return false; // Mock
    }
    
    private function getRecentAttempts($key, $window) {
        if (!$this->redis) return [];
        
        $attempts = $this->redis->lrange($key, 0, -1);
        $currentTime = time();
        $recent = [];
        
        foreach ($attempts as $timestamp) {
            if (($currentTime - intval($timestamp)) <= $window) {
                $recent[] = $timestamp;
            }
        }
        
        return $recent;
    }
    
    private function blockRequest($result, $code, $reason) {
        $result['allowed'] = false;
        $result['block_code'] = $code;
        $result['reason'] = $reason;
        
        $this->log("ðŸš« Request blocked: $code - $reason", $result);
        return $result;
    }
    
    private function logSuccessfulValidation($data, $result) {
        $this->log("âœ… Security validation passed", [
            'score' => $result['security_score'],
            'ip' => $this->getClientIP(),
            'requires_3ds' => $result['requires_3ds']
        ]);
    }
    
    private function initializeRedis() {
        try {
            if (class_exists('Redis')) {
                $this->redis = new Redis();
                $this->redis->connect('127.0.0.1', 6379);
            }
        } catch (Exception $e) {
            $this->log("âš ï¸ Redis not available: " . $e->getMessage());
            $this->redis = null;
        }
    }
    
    private function setupDatabase() {
        // Database setup za perzistentno Äuvanje security podataka
    }
    
    private function log($message, $data = []) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = [
            'timestamp' => $timestamp,
            'message' => $message,
            'data' => $data,
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        $logLine = "[{$timestamp}] {$message} " . json_encode($data) . PHP_EOL;
        @file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}