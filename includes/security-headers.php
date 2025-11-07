<?php
/**
 * Security Headers Configuration
 * Postavlja sve sigurnosne HTTP headere za zaštitu od XSS, clickjacking, i drugih napada
 */

class Security_Headers {

    /**
     * Primeni sve sigurnosne headere
     * Pozovi ovu funkciju na vrhu svakog PHP fajla koji služi HTML
     */
    public static function apply() {
        // Spreči da se headeri šalju više puta
        if (headers_sent()) {
            return;
        }

        // Content Security Policy (CSP)
        self::setCSP();

        // X-Frame-Options - zaštita od clickjacking
        header("X-Frame-Options: DENY");

        // X-Content-Type-Options - spreči MIME type sniffing
        header("X-Content-Type-Options: nosniff");

        // X-XSS-Protection - legacy zaštita (za stare browsere)
        header("X-XSS-Protection: 1; mode=block");

        // Referrer Policy - kontroliši koliko informacija se šalje u referer headeru
        header("Referrer-Policy: strict-origin-when-cross-origin");

        // Permissions Policy (ranije Feature Policy)
        self::setPermissionsPolicy();

        // Strict-Transport-Security (HSTS) - samo za HTTPS
        if (self::isHTTPS()) {
            // 1 godina = 31536000 sekundi
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        }

        // Cross-Origin Policies
        // COEP blokira Stripe - zakomentirano za payment processor compatibility
        // header("Cross-Origin-Embedder-Policy: require-corp");
        header("Cross-Origin-Opener-Policy: same-origin");
        header("Cross-Origin-Resource-Policy: same-origin");
    }

    /**
     * Content Security Policy - najvažnija zaštita
     */
    private static function setCSP() {
        $cspDirectives = [
            // Default fallback
            "default-src 'self'",

            // Scripts - dozvoli samo sa specifičnih izvora
            "script-src 'self' https://js.stripe.com https://www.google.com https://www.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline' 'unsafe-eval'",

            // Styles
            "style-src 'self' https://fonts.googleapis.com https://cdnjs.cloudflare.com 'unsafe-inline'",

            // Images
            "img-src 'self' data: https: blob:",

            // Fonts
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:",

            // Connect (AJAX, WebSocket, fetch)
            "connect-src 'self' https://api.stripe.com https://www.google.com https://www.gstatic.com",

            // Media (video, audio)
            "media-src 'self' blob: https://*.live-video.net https://*.cloudfront.net https://*.vimeocdn.com https://player.vimeo.com",

            // Frames (dodato google.com za reCAPTCHA, youtube.com za video embeds)
            "frame-src 'self' https://js.stripe.com https://hooks.stripe.com https://player.vimeo.com https://www.google.com https://www.gstatic.com https://recaptcha.google.com https://www.recaptcha.net https://www.youtube.com https://youtube.com",

            // Objects (Flash, Java applets - blokiraj sve)
            "object-src 'none'",

            // Base tag restriction
            "base-uri 'self'",

            // Form submission
            "form-action 'self'",

            // Frame ancestors (ko može embedovati tvoj sajt)
            "frame-ancestors 'none'",

            // Upgrade insecure requests (HTTP -> HTTPS) - samo na HTTPS
            self::isHTTPS() ? "upgrade-insecure-requests" : "",

            // Block mixed content - samo na HTTPS
            self::isHTTPS() ? "block-all-mixed-content" : ""
        ];

        // Ukloni prazne direktive
        $cspDirectives = array_filter($cspDirectives);

        $csp = implode('; ', $cspDirectives);
        header("Content-Security-Policy: " . $csp);

        // Report-Only mod za testiranje (opciono)
        // header("Content-Security-Policy-Report-Only: " . $csp);
    }

    /**
     * Permissions Policy - kontrola browser features
     */
    private static function setPermissionsPolicy() {
        $policies = [
            "geolocation=()",           // Blokiraj geolokaciju
            "microphone=()",            // Blokiraj mikrofon
            "camera=()",                // Blokiraj kameru
            "payment=(self)",           // Dozvoli payment samo iz istog origin-a
            "usb=()",                   // Blokiraj USB pristup
            "magnetometer=()",          // Blokiraj senzore
            "accelerometer=()",
            "gyroscope=()",
            "fullscreen=(self)",        // Fullscreen samo za tvoj domen
            "picture-in-picture=(self)"
        ];

        header("Permissions-Policy: " . implode(', ', $policies));
    }

    /**
     * Proveri da li je HTTPS konekcija
     */
    private static function isHTTPS() {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            $_SERVER['SERVER_PORT'] == 443 ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        );
    }

    /**
     * CSP za Admin Panel (blažije pravilo jer treba više funkcionalnosti)
     */
    public static function applyAdminCSP() {
        if (headers_sent()) {
            return;
        }

        $cspDirectives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-src 'self' https://www.youtube.com https://youtube.com",
            "object-src 'none'",
            "base-uri 'self'",
            "frame-ancestors 'none'"
        ];

        $csp = implode('; ', $cspDirectives);
        header("Content-Security-Policy: " . $csp);

        // Ostali security headeri
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }

    /**
     * CSP za API endpoints (najstrožiji)
     */
    public static function applyAPICSP() {
        if (headers_sent()) {
            return;
        }

        // Za JSON API-je ne treba toliko CSP
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");

        // CORS headers (prilagodi prema potrebama)
        // header("Access-Control-Allow-Origin: https://tvoj-domen.com");
        // header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
        // header("Access-Control-Allow-Headers: Content-Type, X-CSRF-Token");
        // header("Access-Control-Max-Age: 86400"); // 24h
    }

    /**
     * Dodatne security headers za file download
     */
    public static function applyDownloadHeaders($filename) {
        header("Content-Disposition: attachment; filename=\"" . basename($filename) . "\"");
        header("X-Content-Type-Options: nosniff");
        header("Content-Security-Policy: default-src 'none'; sandbox");
    }
}
