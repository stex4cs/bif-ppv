<?php
// Security Headers - primeni na vrhu stranice
require_once 'includes/security-headers.php';
Security_Headers::apply();
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('bif-theme');
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                var theme = savedTheme || (prefersDark ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
                document.documentElement.style.colorScheme = theme;
            } catch (err) {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.documentElement.style.colorScheme = 'dark';
            }
        })();
    </script>
    <title>BIF PPV - Gledaj Uživo</title>

    <!-- Security: Referrer policy -->
    <meta name="referrer" content="no-referrer">

    <?php
    // CSRF Protection - dodaj meta tag za JavaScript pristup
    require_once 'includes/csrf-protection.php';
    echo CSRF_Protection::getTokenMeta();
    ?>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png">

    <link rel="stylesheet" href="css/loading-screen.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/modern-design.css">


    <script src="js/bot-protection.js"></script>
    <!-- Stripe -->
    <script src="https://js.stripe.com/v3/"></script>

    <script src="js/enhanced-protection.js"></script>

    <!-- HLS.js for streaming -->
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.4.12/dist/hls.min.js"></script>
    <?php
// Load .env
function loadEnvFile($path) {
    if (!is_file($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $t = trim($line);
        if ($t === '' || strpos($t, '#') === 0 || strpos($t, '=') === false) continue;
        list($k, $v) = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v, "\"' \t\r\n"));
    }
}
loadEnvFile('env/.env');
$recaptchaSiteKey = getenv('RECAPTCHA_SITE_KEY');
if (!$recaptchaSiteKey) {
    error_log('CRITICAL: RECAPTCHA_SITE_KEY not configured in .env file');
    die('Configuration error: reCAPTCHA not configured. Please contact administrator.');
}
?>

<!-- reCAPTCHA v3 -->
<script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($recaptchaSiteKey); ?>"></script>

    
    <style>
        /* PostojeÄ‡i CSS + DODAJ ove anti-recording stilove: */
        .ppv-container {
            padding-top: 80px;
            min-height: 100vh;
            background: #f8f9fa;
        }

        [data-theme="dark"] .ppv-container {
            background: #1a1a1a;
        }

        .ppv-hero {
            background: linear-gradient(135deg, #c41e3a 0%, #8b0000 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
            position: relative;
        }

        .event-info {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 30px;
            margin: 30px auto;
            max-width: 600px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .price-display {
            font-size: 3rem;
            font-weight: 800;
            color: #ffd700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .early-bird-badge {
            background: #ffd700;
            color: #000;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.875rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .payment-form {
            max-width: 500px;
            margin: 30px auto 0;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }

        [data-theme="dark"] .payment-form {
            background: #2d2d2d;
            border-color: #404040;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        [data-theme="dark"] .form-group label {
            color: #fff;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            background: #fff;
            color: #333;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        [data-theme="dark"] .form-group input {
            background: #404040;
            border-color: #555;
            color: #fff;
        }

        .form-group input:focus {
            outline: none;
            border-color: #c41e3a;
            box-shadow: 0 0 0 3px rgba(196, 30, 58, 0.1);
        }

        #card-element {
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background: #fff;
            transition: all 0.3s ease;
        }

        [data-theme="dark"] #card-element {
            background: #404040;
            border-color: #555;
        }

        #card-element:focus-within {
            border-color: #c41e3a;
            box-shadow: 0 0 0 3px rgba(196, 30, 58, 0.1);
        }

        #card-errors {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 8px;
            padding: 8px;
            background: rgba(220, 53, 69, 0.1);
            border-radius: 6px;
            display: none;
        }

        #card-errors.show {
            display: block;
        }

        .purchase-btn {
            width: 100%;
            padding: 15px;
            font-size: 1.125rem;
            font-weight: 700;
            background: linear-gradient(45deg, #ffd700, #ffed4a);
            color: #000;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 20px;
        }

        .purchase-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .purchase-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .error-message, .success-animation {
            border-radius: 16px;
            padding: 40px;
            margin: 40px 0;
            text-align: center;
        }

        .error-message {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05));
            border: 2px solid #dc3545;
            color: #dc3545;
        }

        .success-animation {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
            border: 2px solid #28a745;
            animation: successSlide 0.5s ease-out;
        }

        @keyframes successSlide {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* NOVO: Anti-recording protection styles */
        .protected-video {
            position: relative;
            background: #000;
            overflow: hidden;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        .protected-video::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1;
            pointer-events: none;
            background: linear-gradient(45deg, 
                transparent 25%, 
                rgba(255,255,255,0.02) 25%, 
                rgba(255,255,255,0.02) 50%, 
                transparent 50%, 
                transparent 75%, 
                rgba(255,255,255,0.02) 75%);
            background-size: 50px 50px;
            animation: watermark 20s linear infinite;
        }

        @keyframes watermark {
            0% { transform: translateX(-50px) translateY(-50px); }
            100% { transform: translateX(0px) translateY(0px); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .ppv-hero { padding: 40px 0; }
            .payment-form { margin: 20px; padding: 20px; }
            .price-display { font-size: 2.5rem; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header role="banner">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: var(--space-md);">
                        <div class="logo-text">BIF</div>
                        <span class="lang-content active" data-lang="sr">Balkan Influence Fighting</span>
                        <span class="lang-content" data-lang="en">Balkan Influence Fighting</span>
                    </a>
                </div>

                <!-- Mobile hamburger menu button -->
                <button class="mobile-menu-toggle" aria-label="Toggle menu" aria-expanded="false">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>

                <nav role="navigation" aria-label="Main navigation">
                    <ul class="nav-menu">
                        <li>
                            <a href="index.php#home" aria-label="Početna stranica">
                                <span class="lang-content active" data-lang="sr">Početna</span>
                                <span class="lang-content" data-lang="en">Home</span>
                            </a>
                        </li>
                        <li>
                            <a href="index.php#fighters" aria-label="Naši borci">
                                <span class="lang-content active" data-lang="sr">Borci</span>
                                <span class="lang-content" data-lang="en">Fighters</span>
                            </a>
                        </li>
                        <li>
                            <a href="index.php#news" aria-label="Najnovije vesti">
                                <span class="lang-content active" data-lang="sr">Vesti</span>
                                <span class="lang-content" data-lang="en">News</span>
                            </a>
                        </li>
                        <li>
                            <a href="index.php#events" aria-label="Događaji">
                                <span class="lang-content active" data-lang="sr">Događaji</span>
                                <span class="lang-content" data-lang="en">Events</span>
                            </a>
                        </li>
                        <li>
                            <a href="index.php#contact" aria-label="Kontakt informacije">
                                <span class="lang-content active" data-lang="sr">Kontakt</span>
                                <span class="lang-content" data-lang="en">Contact</span>
                            </a>
                        </li>
                        <li>
                            <a href="watch.php" aria-label="PPV Prenos uživo" class="active">
                                <span class="lang-content active" data-lang="sr">PPV</span>
                                <span class="lang-content" data-lang="en">PPV</span>
                            </a>
                        </li>

                        <!-- Mobile only controls -->
                        <li class="mobile-only-controls">
                            <div class="mobile-theme-language">
                                <button class="theme-toggle-mobile" aria-label="Toggle dark mode">
                                    <span class="theme-icon-mobile">🌙</span>
                                    <span class="lang-content active" data-lang="sr">Tema</span>
                                    <span class="lang-content" data-lang="en">Theme</span>
                                </button>

                                <div class="language-switch-mobile">
                                    <button class="lang-btn active" data-lang="sr" aria-label="Srpski jezik">SR</button>
                                    <button class="lang-btn" data-lang="en" aria-label="English language">EN</button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </nav>

                <div class="header-controls">
                    <!-- Theme Toggle Button -->
                    <button class="theme-toggle" aria-label="Toggle dark mode">
                        <span class="theme-icon">🌙</span>
                    </button>

                    <!-- Language Switch -->
                    <div class="language-switch" role="group" aria-label="Language selection">
                        <button class="lang-btn active" data-lang="sr" aria-label="Srpski jezik">SR</button>
                        <button class="lang-btn" data-lang="en" aria-label="English language">EN</button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="ppv-container">
        <!-- Loading Section -->
        <section id="loading-section" class="ppv-hero">
            <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
                <div class="loading">
                    <div class="spinner"></div>
                    <span>Učitava se...</span>
                </div>
            </div>
        </section>

        <!-- Event Payment Section -->
        <section id="payment-section" class="ppv-hero" style="display: none;">
            <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
                <h1 id="event-title">BIF 1: New Rise</h1>
                <p id="event-description">Najveći influenser fight show na Balkanu</p>
                
                <div class="event-info">
                    <div id="event-date"></div>
                    <div style="display: flex; align-items: center; justify-content: center; gap: 15px; flex-wrap: wrap; margin-top: 20px;">
                        <span class="price-display" id="event-price">19.99 RSD</span>
                        <span id="early-bird-badge" class="early-bird-badge" style="display: none;">Rana Ptica!</span>
                    </div>
                </div>

                <div class="payment-form">
                    <h3 style="text-align: center; margin-bottom: 30px; color: #333;">Kupite PPV Pristup</h3>
                    
                    <div id="form-messages"></div>
                    
                    <form id="payment-form">
                        <div class="form-group">
                            <label for="customer-name">Ime i Prezime *</label>
                            <input type="text" id="customer-name" name="name" required autocomplete="name" placeholder="Marko Petrović">
                        </div>
                        
                        <div class="form-group">
                            <label for="customer-email">Email Adresa *</label>
                            <input type="email" id="customer-email" name="email" required autocomplete="email" placeholder="marko@example.com">
                        </div>
                        
                        <div class="form-group">
                            <label for="card-element">Podaci o Kartici *</label>
                            <div id="card-element">
                                <!-- Stripe Elements will create form elements here -->
                            </div>
                            <div id="card-errors" role="alert"></div>
                        </div>
                        
                        <button type="submit" id="purchase-btn" class="purchase-btn">
                            <span id="btn-text">Kupi Sada</span>
                            <div id="btn-loading" class="loading" style="display: none;">
                                <div class="spinner"></div>
                                <span>Obrađuje se...</span>
                            </div>
                        </button>
                    </form>
                    
                    <div style="margin-top: 20px; text-align: center; font-size: 0.875rem; opacity: 0.8;">
                        <p>Sigurno plaćanje preko Stripe</p>
                        <p>Prihvatamo Visa, Mastercard, American Express</p>
                    </div>
                </div>
            </div>

            <!-- Access lookup section -->
            <div class="access-lookup" style="max-width: 500px; margin: 20px auto; padding: 20px; background: rgba(255,255,255,0.1); border-radius: 16px; border: 1px solid rgba(255,255,255,0.2);">
                <h4 style="text-align: center; color: white; margin-bottom: 20px;">Već imate pristup?</h4>
                <p style="text-align: center; color: rgba(255,255,255,0.8); font-size: 0.9rem; margin-bottom: 20px;">
                    Unesite email adresu sa kojom ste kupili pristup
                </p>
                
                <form id="access-lookup-form" style="display: flex; gap: 10px; align-items: end;">
                    <div style="flex: 1;">
                        <input 
                            type="email" 
                            id="lookup-email" 
                            placeholder="vaš@email.com" 
                            required
                            style="width: 100%; padding: 12px; border: 2px solid rgba(255,255,255,0.3); border-radius: 8px; background: rgba(255,255,255,0.1); color: white; box-sizing: border-box;"
                        >
                    </div>
                    <button 
                        type="submit" 
                        id="lookup-btn"
                        style="padding: 12px 20px; background: #ffd700; color: #000; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; white-space: nowrap;"
                    >
                        Pristup
                    </button>
                </form>
                
                <div id="lookup-result" style="margin-top: 15px; padding: 10px; border-radius: 6px; display: none;"></div>
            </div>
        </section>

        <!-- Success Section -->
        <section id="success-section" style="display: none;">
            <div style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
                <div class="success-animation">
                    <h2>Plaćanje Uspešno!</h2>
                    <p>Hvala vam na kupovini! Stream je spreman za gledanje.</p>
                    
                    <div style="margin: 30px 0;">
                        <button 
                            id="open-stream-btn" 
                            onclick="window.bifApp?.openLastStream()" 
                            style="display: inline-block; background: #ffd700; color: #000; padding: 15px 30px; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 700; cursor: pointer; margin-right: 15px;"
                        >
                            Otvori Stream
                        </button>
                        <a href="index.php" style="display: inline-block; background: #c41e3a; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: 600;">Nazad na Početnu</a>
                    </div>
                    
                    <div style="background: rgba(255,215,0,0.1); padding: 20px; border-radius: 8px; margin-top: 20px;">
                        <h4 style="margin: 0 0 10px 0; color: #ffd700;">Važne informacije:</h4>
                        <ul style="text-align: left; color: #333;">
                            <li>Poslali smo vam email sa linkom za pristup</li>
                            <li>Link možete koristiti na bilo kom uređaju</li>
                            <li>Pristup važi 30 dana od kupovine</li>
                            <li>Sačuvajte ovaj email za buduće pristupe</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Enhanced Info/Error Section -->
<section id="info-section" style="display: none;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
        <div class="info-message-box" style="background: white; border-radius: 16px; padding: 40px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
            
            <!-- Logo Ä‡e biti ubaÄen ovde preko JavaScripta -->
            <div id="info-logo-container" style="margin-bottom: 30px;">
                <img src="assets/images/logo/logo.png" alt="BIF Logo" style="max-width: 150px; height: auto;">
            </div>

            <!-- Poruka Ä‡e biti ubaÄena ovde -->
            <div id="info-text-container">
                <!-- Primer: <h3>Naslov poruke</h3><p>Tekst poruke.</p> -->
            </div>

            <div id="info-actions-container" style="margin-top: 30px;">
                <a href="index.php" style="display: inline-block; background: #c41e3a; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600;">Nazad na Početnu</a>
            </div>
        </div>
    </div>
</section>

    </main>

    <script>
class BIF_PPV_Frontend_Enhanced {
    constructor() {
    this.apiUrl = 'api/ppv.php';
    this.cacheBuster = Date.now();
    
    // DOM elements - postavi na null, inicijalizuj ih tek u init()
    this.loadingSection = null;
    this.paymentSection = null;
    this.successSection = null;
    this.infoSection = null;
    this.infoText = null;
    this.errorSection = null;
    this.eventTitle = null;
    this.eventDescription = null;
    this.eventPrice = null;
    this.earlyBirdBadge = null;
    this.errorText = null;
    this.formMessages = null;
    this.paymentForm = null;
    this.purchaseBtn = null;
    this.btnText = null;
    this.btnLoading = null;
    this.customerName = null;
    this.customerEmail = null;
    this.cardElement = null;
    this.cardErrors = null;
    
    // Stripe
    this.stripe = null;
    this.elements = null;
    this.paymentIntentClientSecret = null;
    this.stripeKey = null;
    
    // Enhanced properties
    this.deviceId = null;
    this.heartbeatInterval = null;
    this.isStreamActive = false;
    this.protectionEnabled = false;
    this.securityScore = 0;
    
    console.log('PPV Frontend initialized (safe mode)');
}

initDOMElements() {
    console.log('Initializing DOM elements...');
    
    this.loadingSection = document.getElementById('loading-section');
    this.paymentSection = document.getElementById('payment-section');
    this.successSection = document.getElementById('success-section');
    this.infoSection = document.getElementById('info-section');
    this.infoText = document.getElementById('info-text-container');
    this.errorSection = document.getElementById('error-section');
    this.eventTitle = document.getElementById('event-title');
    this.eventDescription = document.getElementById('event-description');
    this.eventPrice = document.getElementById('event-price');
    this.earlyBirdBadge = document.getElementById('early-bird-badge');
    this.errorText = document.getElementById('error-text');
    this.formMessages = document.getElementById('form-messages');
    this.paymentForm = document.getElementById('payment-form');
    this.purchaseBtn = document.getElementById('purchase-btn');
    this.btnText = document.getElementById('btn-text');
    this.btnLoading = document.getElementById('btn-loading');
    this.customerName = document.getElementById('customer-name');
    this.customerEmail = document.getElementById('customer-email');
    this.cardElement = document.getElementById('card-element');
    this.cardErrors = document.getElementById('card-errors');
    
    console.log('DOM elements initialized');
}

    async init() {
    console.log('🔄 Starting FIXED initialization...');

    // PRVO: Inicijalizuj DOM elemente
    this.initDOMElements();
    
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    let eventId = urlParams.get('event');
    let activeEvent = null;
    
    // PRVO: Generisi device ID odmah
    try {
        this.deviceId = window.enhancedProtection.getDeviceId();
        console.log('✅ Device ID generated:', this.deviceId.substring(0, 10) + '...');
    } catch (error) {
        console.error('❌ Device ID generation failed:', error);
        this.deviceId = 'fallback_' + Date.now();
    }
    
    // DRUGO: Ako postoji TOKEN, odmah ga testiraj
    if (token) {
    console.log('Token found, verifying access...');
    try {
        const accessData = await this.verifyAccess(token);
        console.log('Access verification result:', accessData);
        
        if (accessData.success && accessData.event) {
            console.log('Access granted, showing stream...');
            return this.showStreamAccess(token, accessData.event);
        }
        
        if (accessData.event_finished) {
            console.log('Event finished');
            return this.showEventFinishedMessage("Vaš pristup je za događaj koji je završen.");
        }
        
        console.log('Access denied:', accessData.error);
        
    } catch (error) {
        console.error('Token verification failed:', error);
        
        // NOVO: Prikaži specifičnu grešku
        if (error.message.includes('već aktivan')) {
            // showBlockedMessage je već pozvana, ne radi ništa više
            return;
        } else {
            // Za sve druge greške, prikaži error
            this.showError('Neispravan pristupni token. Molimo kupite novi pristup ili koristite email lookup.');
            return;
        }
    }
}
    
    // TREĆE: Učitaj konfiguraciju (samo ako nema tokena ili token ne radi)
    try {
        console.log('⚙️ Loading config...');
        await this.loadConfig();
        console.log('✅ Config loaded');
    } catch (error) {
        console.error('❌ Config loading failed:', error);
        return this.showError('Greška pri učitavanju konfiguracije: ' + error.message);
    }
    
    // ČETVRTO: Učitaj listu događaja
    try {
        console.log('📅 Loading events...');
        const eventsResponse = await this.makeApiCall(`${this.apiUrl}?action=events`);
        if (eventsResponse.success && eventsResponse.events.length > 0) {
            activeEvent = eventsResponse.events.find(e => e.status === 'live') || 
                         eventsResponse.events.find(e => e.status === 'upcoming');
            eventId = eventId || (activeEvent ? activeEvent.id : null);
            console.log('✅ Events loaded, active event:', activeEvent?.id);
        }
    } catch (error) {
        console.error('❌ Events loading failed:', error);
        return this.showError("Ne mogu da učitam listu događaja.");
    }

    // PETO: Probaj IP auto-login
    if (eventId) {
        try {
            console.log('🌐 Trying IP auto-login for event:', eventId);
            const ipCheckResponse = await this.makeApiCall(this.apiUrl, {
                method: 'POST',
                body: JSON.stringify({ 
                    action: 'check_ip_access', 
                    event_id: eventId 
                })
            });
            
            if (ipCheckResponse.success && ipCheckResponse.access_token) {
                console.log('✅ Auto-login via IP successful. Redirecting...');
                window.location.href = `watch.php?token=${ipCheckResponse.access_token}`;
                return;
            }
            console.log('ℹ️ IP auto-login not available');
        } catch (e) {
            console.log('ℹ️ IP check failed, continuing...');
        }
    }
    
    // ŠESTO: Prikaži odgovarajuću stranicu
    if (activeEvent) {
        console.log('💳 Showing payment section for:', activeEvent.title);
        this.showPaymentSection(activeEvent);
    } else {
        console.log('📭 No active events');
        this.showNoActiveEventsMessage();
    }
}

    async makeApiCall(url, options = {}) {

         try {
        console.log('📡 API Call START:', url, options.method || 'GET');
        
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache',
                ...options.headers
            }
        });
        
        console.log('📡 Response received:', response.status, response.statusText);
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('âŒ Non-JSON response:', text);
                throw new Error(`Server returned ${contentType || 'unknown'} instead of JSON`);
            }
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('âœ… API Response:', data);
            return data;
            
        } catch (error) {
            console.error('âŒ API Error:', error);
            throw error;
        }
    }

    async loadConfig() {
        try {
            console.log('âš™ï¸ Loading config...');
            const url = `${this.apiUrl}?action=config&_=${this.cacheBuster}`;
            const data = await this.makeApiCall(url);
            
            if (!data.success) {
                throw new Error(data.error || 'Configuration not available');
            }
            this.stripeKey = data.stripe_key;
            this.csrfToken = data.csrf_token; // Sačuvaj CSRF token
            console.log('âœ… Config loaded (with CSRF token)');
        } catch (error) {
            console.error('âš™ï¸ Error loading config:', error);
            throw error;
        }
    }

    async loadEvent(eventId) {
        try {
            console.log('ðŸ“… Loading event:', eventId);
            this.showLoading();
            const url = `${this.apiUrl}?action=event&event_id=${eventId}&_=${this.cacheBuster}`;
            const data = await this.makeApiCall(url);
            
            if (!data.success || !data.event) {
                throw new Error('Event not found');
            }
            console.log('âœ… Event loaded:', data.event);
            return data.event;
        } catch (error) {
            console.error('ðŸ“… Error loading event:', error);
            throw error;
        }
    }

    async verifyAccess(token) {
    try {
        console.log('Verifying access token...');
        this.showLoading();
        
        this.deviceId = window.enhancedProtection.getDeviceId();
        console.log('Persistent Device ID:', this.deviceId);
        
        const data = await this.makeApiCall(this.apiUrl, {
            method: 'POST',
            body: JSON.stringify({ 
                action: 'verify_access', 
                token: token,
                device_id: this.deviceId
            })
        });
        
        if (!data.success) {
            // KLJUČNA IZMENA: Prikaži error umesto throw
            if (data.error && data.error.includes('već aktivan')) {
                this.showBlockedMessage(data.error);
                throw new Error(data.error); // Zaustavi dalje izvršavanje
            }
            throw new Error(data.error || 'Invalid access token');
        }
        
        this.deviceId = data.device_id || this.deviceId;
        return data;
    } catch (error) {
        console.error('Error verifying access:', error);
        throw error;
    }
}

showBlockedMessage(message) {
    console.log('Showing blocked message:', message);
    this.hideLoading();
    
    if (this.paymentSection) this.paymentSection.style.display = 'none';
    if (this.successSection) this.successSection.style.display = 'none';
    
    if (this.infoText) {
        this.infoText.innerHTML = `
            <h3 style="color: #ff6b6b;">Pristup Blokiran</h3>
            <p style="font-size: 1.1rem; line-height: 1.6;">${message}</p>
            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-top: 20px; border-left: 4px solid #ffc107;">
                <strong>Šta sada?</strong>
                <ul style="text-align: left; margin: 10px 0;">
                    <li>Zatvorite stream na prvom uređaju</li>
                    <li>Sačekajte 2 minuta</li>
                    <li>Pokušajte ponovo</li>
                </ul>
            </div>
        `;
    }
    
    const actionsContainer = document.getElementById('info-actions-container');
    if (actionsContainer) {
        actionsContainer.innerHTML = `
            <button onclick="location.reload()" style="background: #6c757d; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; margin-right: 15px;">Pokušaj Ponovo</button>
            <a href="index.php" style="display: inline-block; background: #c41e3a; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600;">Nazad na Početnu</a>
        `;
    }
    
    if (this.infoSection) this.infoSection.style.display = 'block';
}

    // Helper metoda - dinamički učitaj Stripe
    loadStripeDynamic() {
        return new Promise((resolve, reject) => {
            console.log('📥 Loading Stripe dynamically...');
            const script = document.createElement('script');
            script.src = 'https://js.stripe.com/v3/';

            script.onload = () => {
                if (typeof Stripe !== 'undefined') {
                    console.log('✅ Stripe loaded dynamically');
                    resolve();
                } else {
                    reject(new Error('Stripe script loaded but Stripe object not available'));
                }
            };

            script.onerror = (err) => {
                console.error('❌ Failed to load Stripe script:', err);
                reject(new Error('Failed to load Stripe library from CDN'));
            };

            document.head.appendChild(script);
        });
    }

    // Helper metoda - učitaj i čekaj Stripe
    loadStripeScript() {
        return new Promise((resolve, reject) => {
            // Proveri da li već postoji
            if (typeof Stripe !== 'undefined') {
                console.log('✅ Stripe already loaded');
                resolve();
                return;
            }

            // Proveri da li već postoji script tag
            const existingScript = document.querySelector('script[src*="stripe.com"]');
            if (existingScript) {
                console.log('⏳ Stripe script tag exists, polling for availability...');

                // Polling pristup jer script možda već propustio 'load' event
                const startTime = Date.now();
                const maxWait = 10000;

                const checkStripe = () => {
                    if (typeof Stripe !== 'undefined') {
                        console.log('✅ Stripe loaded from existing script tag');
                        resolve();
                    } else if (Date.now() - startTime > maxWait) {
                        console.warn('⚠️ Timeout waiting for existing tag, trying dynamic load...');
                        // Fallback: probaj dinamički load
                        this.loadStripeDynamic().then(resolve).catch(reject);
                    } else {
                        setTimeout(checkStripe, 100);
                    }
                };

                checkStripe();
                return;
            }

            // Ako ne postoji, koristi loadStripeDynamic
            this.loadStripeDynamic().then(resolve).catch(reject);
        });
    }

    // Helper metoda - čeka da se Stripe učita
    waitForStripe(timeout = 10000) {
        return new Promise((resolve, reject) => {
            const startTime = Date.now();
            let attempts = 0;
            const checkStripe = () => {
                attempts++;
                if (typeof Stripe !== 'undefined') {
                    console.log(`✅ Stripe available after ${attempts} attempts (${Date.now() - startTime}ms)`);
                    resolve();
                } else if (Date.now() - startTime > timeout) {
                    console.error('❌ Stripe timeout details:', {
                        attempts,
                        timeElapsed: Date.now() - startTime,
                        scriptTag: document.querySelector('script[src*="stripe.com"]'),
                        windowStripe: typeof window.Stripe
                    });
                    reject(new Error('Stripe failed to load within timeout'));
                } else {
                    if (attempts % 10 === 0) {
                        console.log(`⏳ Still waiting for Stripe... (${attempts} attempts, ${Date.now() - startTime}ms)`);
                    }
                    setTimeout(checkStripe, 100);
                }
            };
            checkStripe();
        });
    }

    async setupStripe(event) {
        if (!this.stripeKey) {
            this.showError('Stripe key not loaded.');
            return;
        }

        // Učitaj Stripe ako nije već učitan
        if (typeof Stripe === 'undefined') {
            console.log('⏳ Loading Stripe...');
            try {
                await this.loadStripeScript();
            } catch (error) {
                console.error('❌ Stripe failed to load:', error);
                this.showError('Greška: Stripe nije mogao da se učita. Proverite internet konekciju i osvežite stranicu.');
                return;
            }
        }

        console.log('💳 Setting up Stripe with enhanced security...')
        this.stripe = Stripe(this.stripeKey);
        this.elements = this.stripe.elements();
        const card = this.elements.create('card', { 
            style: { 
                base: { 
                    fontSize: '16px', 
                    color: '#333',
                    '::placeholder': { color: '#aab7c4' },
                },
                invalid: { color: '#fa755a', iconColor: '#fa755a' }
            } 
        });
        card.mount(this.cardElement);

        card.addEventListener('change', (event) => {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
                displayError.classList.add('show');
            } else {
                displayError.textContent = '';
                displayError.classList.remove('show');
            }
        });

        this.paymentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            console.log('ðŸ’° Starting enhanced payment process...');
            
            // SIMPLIFIED SECURITY VALIDATION
if (!this.botProtection) {
    console.warn('âš ï¸ ENHANCED PROTECTION not loaded, proceeding with basic validation.');
    // Ne prekidamo, ali logujemo upozorenje
}

this.btnText.style.display = 'none';
this.btnLoading.style.display = 'flex';
this.purchaseBtn.disabled = true;

try {
    // Create payment method
    const { error: pmError, paymentMethod } = await this.stripe.createPaymentMethod({
        type: 'card',
        card: card,
        billing_details: {
            name: this.customerName.value,
            email: this.customerEmail.value,
        },
    });

    if (pmError) {
        console.error('ðŸ’³ Payment method error:', pmError);
        this.showPaymentError(pmError.message);
        // Vrati dugme u normalu
        this.btnText.style.display = 'inline';
        this.btnLoading.style.display = 'none';
        this.purchaseBtn.disabled = false;
        return;
    }

    console.log('ðŸ’³ Payment method created:', paymentMethod.id);

    // Simplified security data collection
    const securityData = {
        device_fingerprint: this.generateBasicDeviceId(), // Koristimo osnovni ID kao fingerprint
        recaptcha_token: '', // Recaptcha token Ä‡emo dodati sada
    };
    
    // Potrebno je ubaciti reCAPTCHA logiku
try {
    await new Promise(resolve => grecaptcha.ready(resolve));
    const token = await grecaptcha.execute('<?php echo htmlspecialchars($recaptchaSiteKey); ?>', {action: 'purchase'});
    securityData.recaptcha_token = token;
    console.log('âœ… reCAPTCHA token generated after ready');
} catch(e) {
    console.error('âŒ reCAPTCHA execution failed', e);
    // Zaustavljamo proces ako reCAPTCHA pukne, jer je obavezna
    this.showPaymentError('reCAPTCHA validation failed. Please refresh and try again.');
    this.btnText.style.display = 'inline';
    this.btnLoading.style.display = 'none';
    this.purchaseBtn.disabled = false;
    return;
}

    const isDevelopment = window.location.hostname === 'localhost';
    let paymentData = {
        action: 'create_payment',
        event_id: event.id,
        email: this.customerEmail.value,
        name: this.customerName.value,
        payment_method_id: paymentMethod.id,
        csrf_token: this.csrfToken, // CSRF zaštita
        // ENHANCED: Include all security data
        ...securityData
                };

                if (isDevelopment && confirm('Development mode: Simulate successful payment?')) {
                    paymentData.simulate_success = true;
                }

                // Create payment intent with security data
                const data = await this.makeApiCall(this.apiUrl, {
                    method: 'POST',
                    body: JSON.stringify(paymentData)
                });

                if (!data.success) {
                    if (data.security_block) {
                        this.showPaymentError('ðŸ›¡ï¸ Security Check Failed: ' + data.error + '\n\nThis helps protect against fraud and ensures legitimate purchases.');
                    } else {
                        this.showPaymentError(data.error);
                    }
                    return;
                }

                // Log security score for debugging
                if (data.security_score) {
                    console.log('Security Score:', data.security_score);
                    this.securityScore = data.security_score;
                }

                if (data.access_token) {
                    console.log(' DEVELOPMENT: Received access token directly! Opening stream...');
                    this.showStreamAccess(data.access_token, data.event || event);
                    return;
                }

                if (data.already_purchased || data.simulated) {
                    console.log(' Direct access available!');
                    this.showStreamAccess(data.access_token, event);
                    return;
                }
                
                console.log(' Payment intent created:', data.client_secret);

console.log('ðŸ”’ 3D Secure required:', data.requires_3ds);

// AKO NIJE POTREBNA 3D SECURE (naÅ¡ localhost sluÄaj), plaÄ‡anje je VEÄ† USPELO.
// Zato preskaÄemo potvrdu i idemo direktno na sledeÄ‡i korak.
if (data.requires_3ds === false) {
    console.log('Payment already succeeded on backend (automatic confirmation)!');
    
    // Izvuci ID iz client_secret stringa
    const paymentIntentId = data.client_secret.split('_secret_')[0];
    this.waitForAccessToken(paymentIntentId, event);

} else {
    // AKO JE POTREBNA 3D SECURE (deÅ¡avaÄ‡e se na live serveru)
    // Tek onda pokuÅ¡avamo da potvrdimo plaÄ‡anje na frontendu.
    console.log('Payment requires confirmation on the frontend (3D Secure)...');
    
    const confirmData = {
        payment_method: paymentMethod.id
    };

    const { error: confirmError, paymentIntent } = await this.stripe.confirmCardPayment(
        data.client_secret, 
        confirmData
    );

    if (confirmError) {
        console.error('Payment confirmation error:', confirmError);
        this.showPaymentError(confirmError.message);
        return;
    }

    console.log('Payment successful on frontend:', paymentIntent.id);
    this.waitForAccessToken(paymentIntent.id, event);
}

            } catch (error) {
                console.error('Payment error:', error);
                this.showPaymentError('Payment error: ' + error.message);
            } finally {
                this.btnText.style.display = 'inline';
                this.btnLoading.style.display = 'none';
                this.purchaseBtn.disabled = false;
            }
        });
    }



    setupAccessLookup() {
        const accessForm = document.getElementById('access-lookup-form');
        if (!accessForm) return;
        
        accessForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('lookup-email').value.trim();
            const resultDiv = document.getElementById('lookup-result');
            const lookupBtn = document.getElementById('lookup-btn');
            
            if (!email) {
                this.showLookupResult('Please enter email address', 'error');
                return;
            }
            
            // SIMPLIFIED security for email lookup
if (!this.botProtection) {
    console.warn('ENHANCED PROTECTION not loaded, using basic lookup validation.');
}

let eventId = 'bif-1-new-rise'; // default fallback

try {
    const eventsResponse = await this.makeApiCall(`${this.apiUrl}?action=events`);
    if (eventsResponse.success && eventsResponse.events.length > 0) {
        const activeEvents = eventsResponse.events.filter(e => ['live', 'upcoming'].includes(e.status));
        if (activeEvents.length > 0) {
            eventId = activeEvents[0].id;
        }
    }
} catch (error) {
    console.log('Warning: Could not load events for lookup, using default');
}

if (!this.deviceId) {
    this.deviceId = this.generateBasicDeviceId();

            }
            
            lookupBtn.disabled = true;
            lookupBtn.textContent = 'Searching...';
            
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<div style="text-align: center;">Checking access with enhanced security...</div>';
            
            try {
                console.log('ðŸ“± Enhanced lookup with device ID:', this.deviceId);
                console.log('ðŸŽ¬ Looking up for event:', eventId);
                
                // Simplified security data for lookup too
const securityData = {
    device_fingerprint: this.deviceId,
    recaptcha_token: '',
};

// Ubacite reCAPTCHA i ovde
try {
    await new Promise(resolve => grecaptcha.ready(resolve));
    const token = await grecaptcha.execute('<?php echo htmlspecialchars($recaptchaSiteKey); ?>', {action: 'lookup'});
    securityData.recaptcha_token = token;
    console.log('reCAPTCHA token for lookup generated after ready');
} catch(e) {
    console.error('reCAPTCHA execution for lookup failed', e);
    this.showLookupResult('reCAPTCHA validation failed. Please refresh.', 'error');
    lookupBtn.disabled = false;
    lookupBtn.textContent = 'Access';
    return; // Prekini ako ne uspe
}
                
                const response = await this.makeApiCall(this.apiUrl, {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'lookup_access',
                        email: email,
                        event_id: eventId,
                        device_id: this.deviceId,
                        // Include security data
                        device_fingerprint: securityData.device_fingerprint,
                        recaptcha_token: securityData.recaptcha_token
                    })
                });
                
                console.log('Lookup response:', response);
                
                if (response.success && response.access_token) {
                    this.showLookupResult(' Access found! Starting secured stream...', 'success');
                    
                    setTimeout(() => {
    const event = response.event || { 
        title: 'BIF Event', 
        stream_url: response.event?.stream_url || 'fallback_url_here'
    };
    // Proveri da li event ima stream_url
    if (!event.stream_url) {
        console.error('Stream URL missing from event!');
    }
    if (response.device_id) {
        this.deviceId = response.device_id;
    }
    this.showStreamAccess(response.access_token, event);
}, 1500);
                    
                } else {
                    let errorMessage = response.error || 'Access not found for this email';
                    
                    if (response.security_block) {
                        errorMessage = ' Security Check: ' + errorMessage + '\n\nThis helps prevent unauthorized access.';
                    }
                    
                    if (errorMessage.includes('already active')) {
                        errorMessage += '\n\n Tips:\n Close stream on other device\n Wait 5 minutes for session to expire\nâ€¢ Try again';
                    } else if (errorMessage.includes('change device')) {
                        errorMessage += '\n\n This restriction prevents account sharing between different users.';
                    }
                    
                    this.showLookupResult(errorMessage, 'error');
                }
                
            } catch (error) {
                console.error(' Email lookup error:', error);
                this.showLookupResult('Error looking up access: ' + error.message, 'error');
            } finally {
                lookupBtn.disabled = false;
                lookupBtn.textContent = 'Access';
            }
        });
    }

    showStreamAccess(accessToken, event) {
        console.log(' Showing enhanced protected stream...');
        
        this.hideLoading();
        this.hideProcessingPayment();
        
        // Save token with enhanced security
        try {
            localStorage.setItem('bif_access_token', accessToken);
            localStorage.setItem('bif_event_data', JSON.stringify(event));
            localStorage.setItem('bif_device_id', this.deviceId);
        } catch (e) {
            console.log('Note: Could not save to localStorage');
        }
        
        // Initialize enhanced protection - SAD OVO RADI KAKO TREBA!
if (window.enhancedProtection) {
    window.enhancedProtection.initialize(accessToken, this.deviceId);
} else {
    console.error("Enhanced protection script not loaded!");
}
        
        // Create protected stream interface
        const streamContainer = document.createElement('div');
        streamContainer.id = 'stream-container';
        streamContainer.className = 'protected-video';
        streamContainer.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        `;
        
        const streamUrl = event.stream_url || 'https://vimeo.com/1017406920?fl=pl&fe=sh'; // fallback ako nema URL
        
        streamContainer.innerHTML = `
    <div style="background: linear-gradient(135deg, #c41e3a, #8b0000); color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0;"${event.title} - Live</h2>
        <div>
            <span id="device-status" style="background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 3px; font-size: 12px; margin-right: 10px;">
                Enhanced Security Active
            </span>
            <span style="background: rgba(40, 167, 69, 0.8); padding: 5px 10px; border-radius: 3px; font-size: 12px; margin-right: 10px;">
                 Score: ${this.securityScore || 'N/A'}
            </span>
            <button onclick="window.bifApp?.copyWatchUrl()" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-right: 10px;"> Copy URL</button>
            <button onclick="window.bifApp?.closeStream()" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Close</button>
        </div>
    </div>
    <div style="flex: 1; position: relative; background: #000; display: flex; align-items: center; justify-content: center; overflow: hidden;">
        <video 
            id="video-player" 
            style="width: 100%; height: 100%; object-fit: contain;"
            controls
            autoplay
            playsinline
            muted>
        </video>
    </div>
    <div style="background: #333; color: white; padding: 10px; text-align: center; font-size: 12px;">
        Enhanced DRM Protected Content - Recording and sharing disabled | Device: ${this.deviceId.substring(0, 8)} | Security Score: ${this.securityScore || 'N/A'}
    </div>
`;

document.body.appendChild(streamContainer);

// Koristite HLS.js za .m3u8 streamove
const videoElement = document.getElementById('video-player');

if (streamUrl.includes('.m3u8')) {
    // Proverite da li je HLS.js uÄitan
    if (typeof Hls !== 'undefined' && Hls.isSupported()) {
        const hls = new Hls({
            debug: false,
            enableWorker: true,
            lowLatencyMode: true,
            backBufferLength: 90
        });
        
        hls.loadSource(streamUrl);
        hls.attachMedia(videoElement);
        
        hls.on(Hls.Events.MANIFEST_PARSED, function() {
            videoElement.play().catch(e => console.log('Autoplay blocked:', e));
        });
        
        hls.on(Hls.Events.ERROR, function(event, data) {
            console.error('HLS Error:', data);
            if (data.fatal) {
                switch(data.type) {
                    case Hls.ErrorTypes.NETWORK_ERROR:
                        console.error('Network error - trying to recover');
                        hls.startLoad();
                        break;
                    case Hls.ErrorTypes.MEDIA_ERROR:
                        console.error('Media error - trying to recover');
                        hls.recoverMediaError();
                        break;
                    default:
                        console.error('Fatal error - destroying HLS instance');
                        hls.destroy();
                        break;
                }
            }
        });
    } else if (videoElement.canPlayType('application/vnd.apple.mpegurl')) {
        // Safari native HLS support
        videoElement.src = streamUrl;
        videoElement.play().catch(e => console.log('Autoplay blocked:', e));
    } else {
        console.error('HLS is not supported in this browser');
        alert('Your browser does not support HLS streaming. Please use Chrome, Safari or Firefox.');
    }
} else {
    // Za obiÄne video fajlove
    videoElement.src = streamUrl;
    videoElement.play().catch(e => console.log('Autoplay blocked:', e));
}
        
        // Hide other sections (sa null check)
        if (this.paymentSection) this.paymentSection.style.display = 'none';
        if (this.successSection) this.successSection.style.display = 'none';
        if (this.errorSection) this.errorSection.style.display = 'none';
        
        // Start heartbeat
        this.isStreamActive = true;
        this.startEnhancedHeartbeat(accessToken);
        
        // Setup global functions
        window.bifApp = window.bifApp || {};
        
        window.bifApp.copyWatchUrl = () => {
            const url = `${window.location.origin}${window.location.pathname}?token=${accessToken}`;
            navigator.clipboard.writeText(url).then(() => {
                alert(' URL copied!\n\nâš ï¸ IMPORTANT: This link works only on 1 device. Enhanced security prevents recording and sharing.');
            }).catch(() => {
                prompt('Copy this URL (Enhanced Security Protected):', url);
            });
        };
        
        window.bifApp.closeStream = () => {
            this.stopEnhancedHeartbeat();
            if (window.enhancedProtection) {
                window.enhancedProtection.cleanup();
            }
            const container = document.getElementById('stream-container');
            if (container) {
                container.remove();
            }
            this.showPaymentSection(event);
        };
        
        window.bifApp.openLastStream = () => {
            this.showStreamAccess(accessToken, event);
        };
    }

    buildProtectedStreamUrl(baseUrl) {
        const url = new URL(baseUrl);
        
        url.searchParams.set('autoplay', '1');
        url.searchParams.set('title', '0');
        url.searchParams.set('byline', '0');
        url.searchParams.set('portrait', '0');
        url.searchParams.set('controls', '1');
        url.searchParams.set('dnt', '1');
        url.searchParams.set('transparency', '0');
        url.searchParams.set('responsive', '1');
        
        return url.toString();
    }

    startEnhancedHeartbeat(token) {
        console.log('ðŸ’“ Starting enhanced heartbeat...');
        
        this.heartbeatInterval = setInterval(async () => {
            if (!this.isStreamActive) return;
            
            try {
                const securityData = this.botProtection ? 
                    await this.botProtection.collectSecurityData() : {};
                
                const response = await this.makeApiCall(this.apiUrl, {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'enhanced_heartbeat',
                        token: token,
                        device_id: this.deviceId,
                        violations_count: window.enhancedProtection ? window.enhancedProtection.violations.length : 0,
                        performance_metrics: this.getPerformanceMetrics(),
                        protection_status: {
                            enabled: window.enhancedProtection ? true : false,
                            violations: window.enhancedProtection ? window.enhancedProtection.violations.slice(-5) : []
                        },
                        security_data: securityData
                    })
                });
                
                if (!response.success) {
                    console.log('Enhanced heartbeat failed:', response.error);
                    this.handleStreamTermination('Session expired: ' + response.error);
                    return;
                }
                
                console.log('ðŸ’“ Enhanced heartbeat OK', response);
                
                // Update status display
                const statusElement = document.getElementById('device-status');
                if (statusElement && response.active_devices > 1) {
                    statusElement.style.background = 'rgba(255, 193, 7, 0.8)';
                    statusElement.textContent = `âš ï¸ ${response.active_devices} devices`;
                }
                
            } catch (error) {
                console.error(' Enhanced heartbeat error:', error);
            }
        }, 90000); // 1.5 minutes
    }

    stopEnhancedHeartbeat() {
        console.log(' Stopping enhanced heartbeat...');
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
        }
        this.isStreamActive = false;
    }

    getPerformanceMetrics() {
        return {
            memory_used: performance.memory ? performance.memory.usedJSHeapSize : 0,
            memory_limit: performance.memory ? performance.memory.jsHeapSizeLimit : 0,
            timing: performance.timing ? (performance.timing.loadEventEnd - performance.timing.navigationStart) : 0,
            connection: navigator.connection ? {
                effective_type: navigator.connection.effectiveType,
                downlink: navigator.connection.downlink,
                rtt: navigator.connection.rtt
            } : null,
            hardware_concurrency: navigator.hardwareConcurrency || 'unknown',
            device_memory: navigator.deviceMemory || 'unknown'
        };
    }

    handleStreamTermination(reason) {
        console.log(' Stream terminated:', reason);
        
        this.stopEnhancedHeartbeat();
        if (window.enhancedProtection) {
            window.enhancedProtection.cleanup();
        }
        
        const streamContainer = document.getElementById('stream-container');
        if (streamContainer) {
            streamContainer.remove();
        }
        
        this.showError(reason + '\n\nPlease close stream on other device and try again.');
    }

    async waitForAccessToken(paymentIntentId, event) {
        console.log(' Waiting for access token...');
        this.showProcessingPayment();
        
        for (let i = 0; i < 10; i++) {
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            try {
    // PRELAZIMO NA POST METODU DA POÅ ALJEMO PODATKE
    const response = await this.makeApiCall(this.apiUrl, {
        method: 'POST',
        body: JSON.stringify({
            action: 'check_payment',
            payment_intent_id: paymentIntentId,
            // Å ALJEMO PRAVE PODATKE KOJE JE KORISNIK UNEO
            email: this.customerEmail.value,
            name: this.customerName.value
        })
    });

    if (response.success && response.access_token) {
                    console.log(' Access token received!');
                    this.showStreamAccess(response.access_token, event);
                    return;
                }
            } catch (error) {
                console.log(`Attempt ${i + 1}: Token not ready yet...`);
            }
        }
        
        // Fallback lookup
        try {
            const email = this.customerEmail.value;
            console.log('Fallback: Looking up access by email:', email);
            
            const securityData = this.botProtection ? 
                await this.botProtection.collectSecurityData() : {};
                const lookupResponse = await this.makeApiCall(this.apiUrl, {
                method: 'POST',
                body: JSON.stringify({
                    action: 'lookup_access',
                    email: email,
                    event_id: event.id,
                    device_id: this.deviceId,
                    ...securityData
                })
            });
            
            if (lookupResponse.success && lookupResponse.access_token) {
                console.log(' Fallback successful!');
                this.showStreamAccess(lookupResponse.access_token, event);
                return;
            }
        } catch (error) {
            console.log('Fallback also failed:', error);
        }
        
        this.showSuccess();
    }

    showProcessingPayment() {
        console.log(' Showing payment processing...');
        
        this.hideLoading();
        
        const existing = document.getElementById('processing-screen');
        if (existing) existing.remove();
        
        const processingSection = document.createElement('section');
        processingSection.id = 'processing-screen';
        processingSection.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
            z-index: 1000;
        `;
        
        processingSection.innerHTML = `
            <div class="loading">
                <div class="spinner"></div>
                <h3>Processing payment...</h3>
                <p>Please wait while we process your payment and prepare your secure access.</p>
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <small>ð Enhanced security verification in progress...<br>
                    Security Score: ${this.securityScore || 'Calculating...'}</small>
                </div>
            </div>
        `;
        
        document.body.appendChild(processingSection);
    }

    hideProcessingPayment() {
        const processingScreen = document.getElementById('processing-screen');
        if (processingScreen) {
            processingScreen.remove();
        }
    }

    showLookupResult(message, type) {
        const resultDiv = document.getElementById('lookup-result');
        if (!resultDiv) return;
        
        const bgColor = type === 'success' ? 'rgba(40, 167, 69, 0.2)' : 'rgba(220, 53, 69, 0.2)';
        const textColor = type === 'success' ? '#28a745' : '#dc3545';
        
        resultDiv.style.display = 'block';
        resultDiv.style.background = bgColor;
        resultDiv.style.color = textColor;
        resultDiv.style.border = `1px solid ${textColor}`;
        resultDiv.innerHTML = message.replace(/\n/g, '<br>');
        
        if (type !== 'success') {
            setTimeout(() => {
                resultDiv.style.display = 'none';
            }, 10000); // Longer for security messages
        }
    }

    showLoading() {
    console.log('Showing loading...');
    if (this.loadingSection) this.loadingSection.style.display = 'block';
    if (this.paymentSection) this.paymentSection.style.display = 'none';
    if (this.successSection) this.successSection.style.display = 'none';
    if (this.errorSection) this.errorSection.style.display = 'none';
    if (this.infoSection) this.infoSection.style.display = 'none';
}

hideLoading() {
    console.log('Hiding loading...');
    if (this.loadingSection) this.loadingSection.style.display = 'none';
}

showError(message) {
    console.error('Showing error:', message);
    this.hideLoading();
    if (this.paymentSection) this.paymentSection.style.display = 'none';
    if (this.successSection) this.successSection.style.display = 'none';

    if (this.infoText) {
        this.infoText.innerHTML = `
            <h3 style="color: #dc3545;">Greška</h3>
            <p>${message}</p>
        `;
    }
    
    // Pokaži i dugme za ponovni pokušaj
    const actionsContainer = document.getElementById('info-actions-container');
    if (actionsContainer) {
        actionsContainer.innerHTML = `
            <button onclick="location.reload()" style="background: #6c757d; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; margin-right: 15px;">Pokušaj Ponovo</button>
            <a href="index.php" style="display: inline-block; background: #c41e3a; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600;">Nazad na Početnu</a>
        `;
    }
    
    if (this.infoSection) this.infoSection.style.display = 'block';
}

    showNoActiveEventsMessage() {
        this.hideLoading();
        this.paymentSection.style.display = 'none';
        this.successSection.style.display = 'none';
        
        this.infoText.innerHTML = `
            <h3 style="color: #333;">Nema Aktivnih Događaja</h3>
            <p>Trenutno nema predstojecih ili aktivnih događaja za gledanje.</p>
            <p>Pratite nas na društvenim mrežama za sve najave!</p>
        `;
        
        const actionsContainer = document.getElementById('info-actions-container');
        actionsContainer.innerHTML = `<a href="index.php" style="display: inline-block; background: #c41e3a; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600;">Nazad na Početnu</a>`;

        this.infoSection.style.display = 'block';
    }

    showEventFinishedMessage() {
    this.hideLoading();
    this.paymentSection.style.display = 'none';
    this.successSection.style.display = 'none';
    this.errorSection.style.display = 'none';
    
    // Kreiraj special sekciju za zavrÅ¡en event
    const finishedSection = document.createElement('section');
    finishedSection.className = 'ppv-hero';
    finishedSection.innerHTML = `
        <div style="max-width: 600px; margin: 0 auto; padding: 40px 20px; text-align: center;">
            <h2 style="color: white; margin-bottom: 20px;">Event je Završen</h2>
            <div style="background: rgba(255,255,255,0.1); padding: 30px; border-radius: 16px;">
                <p style="color: white; font-size: 1.2rem; margin-bottom: 20px;">
                    Hvala vam što ste gledali naš PPV event!
                </p>
                <p style="color: rgba(255,255,255,0.8);">
                    Pratite nas za najave novih događaja.
                </p>
                <div style="margin-top: 30px;">
                    <a href="index.php" style="display: inline-block; background: #ffd700; color: #000; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: 600;">
                        Nazad na Početnu
                    </a>
                </div>
            </div>
        </div>
    `;
    
    document.querySelector('.ppv-container').appendChild(finishedSection);
}

    async showPaymentSection(event) {
        console.log(' Showing enhanced payment section for:', event.title);
        this.hideLoading();
        this.hideProcessingPayment();
        this.eventTitle.textContent = event.title;
        this.eventDescription.textContent = event.description;
        this.eventPrice.textContent = (event.current_price / 100).toFixed(2) + ' RSD';
        if (event.is_early_bird) {
            this.earlyBirdBadge.style.display = 'inline-block';
        }
        this.paymentSection.style.display = 'block';
        this.infoSection.style.display = 'none'; // Koristimo 'infoSection
        this.successSection.style.display = 'none';
        await this.setupStripe(event);
        this.setupAccessLookup();
    }

    showSuccess() {
        console.log(' Showing success...');
        this.hideLoading();
        this.hideProcessingPayment();
        this.successSection.style.display = 'block';
        this.paymentSection.style.display = 'none';
        this.infoSection.style.display = 'none'; // Koristimo 'infoSection'
    }

    setupCleanup() {
        window.addEventListener('beforeunload', () => {
            this.stopEnhancedHeartbeat();
            if (window.enhancedProtection) {
                window.enhancedProtection.cleanup();
            }
        });
        
        window.addEventListener('unload', () => {
            this.stopEnhancedHeartbeat();
            if (window.enhancedProtection) {
                window.enhancedProtection.cleanup();
            }
        });
    }

    // Enhanced device ID generation fallback
    generateBasicDeviceId() {
        const factors = [
            navigator.userAgent,
            navigator.language,
            screen.width + 'x' + screen.height,
            new Date().getTimezoneOffset(),
            navigator.platform,
            navigator.cookieEnabled
        ];
        
        let hash = 0;
        const str = factors.join('|');
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        
        return 'basic_' + Math.abs(hash).toString(16);
    }

    clearSavedTokens() {
        try {
            localStorage.removeItem('bif_access_token');
            localStorage.removeItem('bif_event_data');
            localStorage.removeItem('bif_device_id');
        } catch (e) {
            console.log('Note: Could not clear localStorage');
        }
    }

    showPaymentError(message) {
        console.error(' Payment Error:', message);
        this.formMessages.innerHTML = `
            <div style="color: #dc3545; padding: 15px; border-radius: 8px; background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3); margin: 15px 0;">
                <strong> Payment Error:</strong><br>
                ${message.replace(/\n/g, '<br>')}
                ${this.securityScore > 0 ? '<br><br><small> Security Score: ' + this.securityScore + '/100</small>' : ''}
            </div>
        `;
        this.formMessages.style.display = 'block';
        setTimeout(() => {
            this.formMessages.style.display = 'none';
        }, 12000); // Longer timeout for security messages
    }
}

// Enhanced initialization
document.addEventListener('DOMContentLoaded', () => {
    console.log(' Starting Enhanced BIF PPV Frontend with Multi-Layer Security...');
    
    // Wait for bot protection to fully load
    const initEnhancedApp = () => {
        const app = new BIF_PPV_Frontend_Enhanced();
        app.setupCleanup();
        app.init().catch(error => {
            console.error(' Fatal initialization error:', error);
            
            document.body.innerHTML = `
                <div style="padding: 20px; background: #ffebee; color: #c62828; border-radius: 5px; margin: 20px; font-family: Arial, sans-serif;">
                    <h3>âŒ Error loading Enhanced Security PPV System</h3>
                    <p><strong>Message:</strong> ${error.message}</p>
                    
                    <div style="margin-top: 20px;">
                        <button onclick="location.reload()" style="padding: 10px 20px; background: #c41e3a; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">
                            Try Again
                        </button>
                        <a href="index.php" style="display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px;">
                            Back to Home
                        </a>
                    </div>
                </div>
            `;
        });
    };

    // Wait for bot protection or initialize after timeout
    if (window.bifBotProtection) {
        initEnhancedApp();
    } else {
        let attempts = 0;
        const waitForBotProtection = setInterval(() => {
            attempts++;
            if (window.bifBotProtection || attempts > 20) { // Max 10 seconds wait
                clearInterval(waitForBotProtection);
                if (!window.bifBotProtection) {
                    console.warn('Bot protection not loaded within timeout - proceeding with reduced security');
                }
                initEnhancedApp();
            }
        }, 500);
    }
});

// Export for global use
window.BIF_PPV_Frontend_Enhanced = BIF_PPV_Frontend_Enhanced;

</script>

<script src="main.js"></script>
<script>
    if (!document.documentElement.classList.contains('js-enabled')) {
        document.documentElement.classList.add('js-enabled');
    }
</script>
</body>
</html>
