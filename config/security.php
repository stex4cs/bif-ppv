<?php
/**
 * config/security.php
 * Security Configuration for BIF PPV System
 * KREIRAJ OVAJ FAJL U: config/security.php
 */

return [
    // DRM Protection Settings
    'drm' => [
        'token_rotation_interval' => 300, // 5 minuta - koliko često da rotira tokene
        'max_concurrent_devices' => 1,    // Maksimalno uređaja po korisniku
        'device_change_cooldown' => 1800, // 30 minuta - cooldown između promene uređaja
        'heartbeat_timeout' => 120,       // 2 minute - interval heartbeat provere
        'stream_timeout' => 300,          // 5 minuta - timeout za neaktivne stream-ove
    ],
    
    // Threat Detection Settings
    'detection' => [
        'vpn_detection' => false,         // VPN detekcija (disable za sada)
        'geo_blocking' => false,          // Geografsko blokiranje (disable za sada)
        'recording_detection' => true,    // Detekcija screen recording-a
        'devtools_detection' => true,     // Detekcija Developer Tools
        'performance_monitoring' => true, // Performance monitoring za detekciju recording software-a
        'network_monitoring' => true,     // Monitoring network requests
    ],
    
    // Violation Handling
    'violations' => [
        'max_warnings' => 3,             // Maksimalno upozorenja pre suspendovanja
        'suspension_duration' => 24,     // Trajanje suspendovanja u satima
        'auto_ban_threshold' => 5,       // Automatski ban nakon ovaj broj violation-a
        'violation_decay_hours' => 72,   // Violation-i se "trošeˮ nakon ovoliko sati
        'critical_violations' => [       // Violation-i koji instantno terminiraju stream
            'devtools_console_detection',
            'screen_recording_attempt',
            'media_recorder_attempt'
        ],
        'high_severity_violations' => [  // High severity violation-i
            'devtools_size_detection',
            'devtools_debugger_detection',
            'suspicious_network_request',
            'multiple_device_attempt'
        ],
    ],
    
    // Monitoring and Analytics
    'monitoring' => [
        'realtime_updates' => true,      // Real-time dashboard updates
        'analytics_retention' => 90,     // Koliko dana da čuva analitičke podatke
        'alert_email' => 'admin@bif.events', // Email za security alerts
        'log_all_violations' => true,    // Loguj sve violation-e
        'performance_tracking' => true,  // Track performance metrics
        'device_fingerprinting' => true, // Advanced device fingerprinting
    ],
    
    // Geographic Settings (za buduće korišćenje)
    'geographic' => [
        'enabled' => false,
        'allowed_countries' => ['RS', 'HR', 'BA', 'ME', 'MK', 'SI'], // Balkan region
        'blocked_countries' => [],       // Blokirane zemlje
        'vpn_whitelist' => [],          // Dozvoljeni VPN-ovi (ako postoje)
    ],
    
    // Rate Limiting
    'rate_limiting' => [
        'enabled' => true,
        'max_requests_per_minute' => 60,  // Maksimalno API poziva po minuti
        'max_login_attempts' => 5,        // Maksimalno login pokušaja
        'lockout_duration' => 900,        // 15 minuta lockout
    ],
    
    // Email Settings
    'email' => [
        'security_alerts' => true,       // Pošalji email za security alerts
        'violation_threshold' => 3,      // Pošalji email nakon ovaj broj violation-a
        'admin_notifications' => true,   // Admin notifikacije
        'user_notifications' => false,   // User notifikacije (disable za sada)
    ],
    
    // Stream Protection
    'stream' => [
        'watermarking' => true,          // Dynamic watermarking
        'watermark_interval' => 10,      // Promena watermark-a svakih 10 sekundi
        'signed_urls' => true,           // Signed stream URLs
        'url_expiry' => 900,             // Stream URL expiry (15 minuta)
        'quality_restrictions' => false, // Ograniči kvalitet za sumnjive korisnike
        'buffer_monitoring' => true,     // Monitor buffer health
    ],
    
    // Device Tracking
    'device_tracking' => [
        'fingerprinting_enabled' => true,
        'track_canvas' => true,          // Canvas fingerprinting
        'track_webgl' => true,           // WebGL fingerprinting
        'track_audio' => true,           // Audio context fingerprinting
        'track_hardware' => true,        // Hardware info tracking
        'session_persistence' => 300,    // Koliko dugo da pamti device session (5 min)
    ],
    
    // Advanced Security
    'advanced' => [
        'csrf_protection' => true,       // CSRF protection
        'sql_injection_protection' => true, // SQL injection protection
        'xss_protection' => true,        // XSS protection
        'input_validation' => true,      // Strict input validation
        'output_encoding' => true,       // Output encoding
        'secure_headers' => true,        // Security headers
    ],
    
    // Debug and Development
    'debug' => [
        'enabled' => false,              // Debug mode (enable samo za development)
        'log_level' => 'info',           // Logging level: debug, info, warning, error
        'detailed_errors' => false,      // Detailed error messages
        'performance_profiling' => false, // Performance profiling
    ],
    
    // Compliance
    'compliance' => [
        'gdpr_enabled' => true,          // GDPR compliance
        'data_retention_days' => 365,    // Data retention period
        'anonymize_logs' => true,        // Anonymize sensitive data in logs
        'privacy_mode' => false,         // Extra privacy protections
    ],
    
    // API Security
    'api' => [
        'require_https' => true,         // Require HTTPS for production
        'api_rate_limiting' => true,     // API rate limiting
        'token_encryption' => true,      // Encrypt access tokens
        'request_signing' => false,      // Sign API requests (advanced)
        'ip_whitelisting' => false,      // IP whitelisting (za admin)
    ],
    
    // Experimental Features
    'experimental' => [
        'ai_fraud_detection' => false,   // AI-powered fraud detection
        'behavioral_analysis' => false,  // User behavior analysis
        'predictive_blocking' => false,  // Predictive violation blocking
        'advanced_fingerprinting' => false, // More advanced device fingerprinting
    ],
    
    // Emergency Settings
    'emergency' => [
        'kill_switch' => false,          // Emergency kill switch
        'maintenance_mode' => false,     // Maintenance mode
        'block_all_access' => false,     // Block all access (emergency)
        'admin_only_mode' => false,      // Admin only access
    ],
];
?>