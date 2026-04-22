<?php
/**
 * CSRF Protection Class
 * Generisanje i validacija CSRF tokena za zaštitu od Cross-Site Request Forgery napada
 */

class CSRF_Protection {

    private const TOKEN_NAME = 'bif_csrf_token';
    private const TOKEN_EXPIRY = 3600; // 1 sat

    /**
     * Generiše novi CSRF token i čuva ga u sesiji
     * @return string CSRF token
     */
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Generiši kriptografski siguran token
        $token = bin2hex(random_bytes(32));

        // Čuvaj u sesiji sa timestampom
        $_SESSION[self::TOKEN_NAME] = [
            'token' => $token,
            'time' => time()
        ];

        return $token;
    }

    /**
     * Vraća trenutni CSRF token ili generiše novi ako ne postoji
     * @return string CSRF token
     */
    public static function getToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Ako token ne postoji ili je istekao, generiši novi
        if (!isset($_SESSION[self::TOKEN_NAME]) || self::isTokenExpired()) {
            return self::generateToken();
        }

        return $_SESSION[self::TOKEN_NAME]['token'];
    }

    /**
     * Validira CSRF token iz requesta
     * @param string $token Token za validaciju
     * @return bool True ako je validan, false ako nije
     */
    public static function validateToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Proveri da li token postoji u sesiji
        if (!isset($_SESSION[self::TOKEN_NAME])) {
            error_log('CSRF validation failed: No token in session');
            return false;
        }

        // Proveri da li je token istekao
        if (self::isTokenExpired()) {
            error_log('CSRF validation failed: Token expired');
            self::generateToken(); // Generiši novi za sledeći zahtev
            return false;
        }

        // Uporedi tokene (timing-safe comparison)
        $sessionToken = $_SESSION[self::TOKEN_NAME]['token'];
        if (!hash_equals($sessionToken, $token)) {
            error_log('CSRF validation failed: Token mismatch');
            return false;
        }

        return true;
    }

    /**
     * Proveri da li je token istekao
     * @return bool True ako je istekao
     */
    private static function isTokenExpired() {
        if (!isset($_SESSION[self::TOKEN_NAME]['time'])) {
            return true;
        }

        $tokenAge = time() - $_SESSION[self::TOKEN_NAME]['time'];
        return $tokenAge > self::TOKEN_EXPIRY;
    }

    /**
     * Generiše hidden HTML input field sa CSRF tokenom
     * @return string HTML input tag
     */
    public static function getTokenField() {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Generiše meta tag sa CSRF tokenom (za AJAX requests)
     * @return string HTML meta tag
     */
    public static function getTokenMeta() {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validira CSRF token iz POST ili JSON requesta
     * @return array Result sa status i porukom
     */
    public static function validateRequest() {
        $token = null;

        // Proveri POST data
        if (isset($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        }
        // Proveri JSON payload
        else if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
            $jsonData = json_decode(file_get_contents('php://input'), true);
            if (isset($jsonData['csrf_token'])) {
                $token = $jsonData['csrf_token'];
            }
        }
        // Proveri HTTP header
        else if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        if (!$token) {
            return [
                'valid' => false,
                'error' => 'CSRF token missing'
            ];
        }

        $valid = self::validateToken($token);

        return [
            'valid' => $valid,
            'error' => $valid ? null : 'CSRF token invalid or expired'
        ];
    }

    /**
     * Middleware funkcija - blokira request ako CSRF validacija fails
     */
    public static function requireValidToken() {
        $result = self::validateRequest();

        if (!$result['valid']) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'CSRF validation failed',
                'message' => $result['error']
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Resetuj token (koristi nakon uspešne forme submission)
     */
    public static function resetToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION[self::TOKEN_NAME]);
        return self::generateToken();
    }
}
