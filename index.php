<?php
// Security Headers - primeni na vrhu stranice
require_once 'includes/security-headers.php';
Security_Headers::apply();

// Load fighters from JSON
function loadActiveFighters() {
    $fightersFile = __DIR__ . '/data/fighters.json';
    if (!file_exists($fightersFile)) {
        return [];
    }

    $fighters = json_decode(file_get_contents($fightersFile), true);
    if (!$fighters) {
        return [];
    }

    // Filter only active fighters
    $activeFighters = array_filter($fighters, function($fighter) {
        return isset($fighter['status']) && $fighter['status'] === 'active';
    });

    // Sort by created_at (newest first)
    usort($activeFighters, function($a, $b) {
        return ($b['created_at'] ?? '') <=> ($a['created_at'] ?? '');
    });

    return $activeFighters;
}

$activeFighters = loadActiveFighters();

// Weight class helpers (same as in borac.php)
function getWeightClassByWeight($weight) {
    if ($weight <= 57) return ['sr' => 'Mušičja', 'en' => 'Flyweight'];
    if ($weight <= 61) return ['sr' => 'Pijevčija', 'en' => 'Bantamweight'];
    if ($weight <= 66) return ['sr' => 'Perjana', 'en' => 'Featherweight'];
    if ($weight <= 70) return ['sr' => 'Laka', 'en' => 'Lightweight'];
    if ($weight <= 77) return ['sr' => 'Polu-srednja', 'en' => 'Welterweight'];
    if ($weight <= 84) return ['sr' => 'Srednja', 'en' => 'Middleweight'];
    if ($weight <= 93) return ['sr' => 'Polu-teška', 'en' => 'Light Heavyweight'];
    if ($weight <= 120) return ['sr' => 'Teška', 'en' => 'Heavyweight'];
    return ['sr' => 'Superteška', 'en' => 'Super Heavyweight'];
}

function getWeightClassLabels($weightClassKey) {
    $weightClasses = [
        'flyweight' => ['sr' => 'Mušičja', 'en' => 'Flyweight'],
        'bantamweight' => ['sr' => 'Pijevčija', 'en' => 'Bantamweight'],
        'featherweight' => ['sr' => 'Perjana', 'en' => 'Featherweight'],
        'lightweight' => ['sr' => 'Laka', 'en' => 'Lightweight'],
        'welterweight' => ['sr' => 'Polu-srednja', 'en' => 'Welterweight'],
        'middleweight' => ['sr' => 'Srednja', 'en' => 'Middleweight'],
        'light-heavyweight' => ['sr' => 'Polu-teška', 'en' => 'Light Heavyweight'],
        'heavyweight' => ['sr' => 'Teška', 'en' => 'Heavyweight'],
        'super-heavyweight' => ['sr' => 'Superteška', 'en' => 'Super Heavyweight']
    ];
    return $weightClasses[$weightClassKey] ?? null;
}

function getFighterWeightClass($fighter) {
    $customWeightClass = $fighter['weight_class'] ?? '';
    if (!empty($customWeightClass)) {
        $weightClass = getWeightClassLabels($customWeightClass);
        if ($weightClass) {
            return $weightClass;
        }
    }
    // Fallback to auto-detect by weight
    return getWeightClassByWeight($fighter['weight'] ?? 0);
}

// Load published news from JSON
function loadPublishedNews($limit = 3) {
    $newsFile = __DIR__ . '/data/news.json';
    if (!file_exists($newsFile)) {
        return [];
    }

    $news = json_decode(file_get_contents($newsFile), true);
    if (!$news) {
        return [];
    }

    // Filter only published news
    $publishedNews = array_filter($news, function($article) {
        return isset($article['status']) && $article['status'] === 'published';
    });

    // Sort by published_at (newest first)
    usort($publishedNews, function($a, $b) {
        return ($b['published_at'] ?? '') <=> ($a['published_at'] ?? '');
    });

    // Limit results
    return array_slice($publishedNews, 0, $limit);
}

$publishedNews = loadPublishedNews(3);

// Load hero settings from JSON
function loadHeroSettings() {
    $settingsFile = __DIR__ . '/data/hero_settings.json';
    if (!file_exists($settingsFile)) {
        return [
            'video_url' => 'https://www.youtube.com/embed/PwjZeFIpxvo?rel=0&controls=1&autoplay=0&modestbranding=1',
            'countdown_date' => '2025-07-21T18:45:00',
            'countdown_title_sr' => 'Do BIF 1',
            'countdown_title_en' => 'Until BIF 1'
        ];
    }

    $settings = json_decode(file_get_contents($settingsFile), true);
    if (!$settings) {
        return [
            'video_url' => 'https://www.youtube.com/embed/PwjZeFIpxvo?rel=0&controls=1&autoplay=0&modestbranding=1',
            'countdown_date' => '2025-07-21T18:45:00',
            'countdown_title_sr' => 'Do BIF 1',
            'countdown_title_en' => 'Until BIF 1'
        ];
    }

    return $settings;
}

$heroSettings = loadHeroSettings();

// Helper function to extract excerpt from content
function extractExcerpt($content, $length = 150) {
    // Strip HTML tags
    $text = strip_tags($content);
    // Remove extra whitespace
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);

    if (mb_strlen($text) > $length) {
        $text = mb_substr($text, 0, $length) . '...';
    }

    return $text;
}

// Helper function to format date
function formatNewsDate($dateString) {
    $timestamp = strtotime($dateString);
    if (!$timestamp) {
        return $dateString;
    }

    $months_sr = ['Jan', 'Feb', 'Mar', 'Apr', 'Maj', 'Jun', 'Jul', 'Avg', 'Sep', 'Okt', 'Nov', 'Dec'];
    $months_en = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    $day = date('j', $timestamp);
    $month_num = date('n', $timestamp) - 1;
    $year = date('Y', $timestamp);

    return [
        'sr' => $day . '. ' . $months_sr[$month_num] . ' ' . $year,
        'en' => $months_en[$month_num] . ' ' . $day . ', ' . $year,
        'iso' => date('Y-m-d', $timestamp)
    ];
}
?>
<!DOCTYPE html>
<html lang="sr" data-title-sr="BIF - Balkan Influence Fighting" data-title-en="BIF - Balkan Influence Fighting">
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
    <meta name="description" content="BIF - Balkan Influence Fighting, najveći balkanski fight-show sa influenserima">
    <meta name="keywords" content="BIF, MMA, борбе, борци, Балкан, спорт">
    <meta name="author" content="BIF - Balkan Influence Fighting">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="BIF - Balkan Influence Fighting">
    <meta property="og:description" content="Najveći balkanski fight-show sa influenserima">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://bif.events">
    <meta property="og:image" content="/assets/images/news/news-1.png">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="BIF - Balkan Influence Fighting">
    <meta name="twitter:description" content="Najveći balkanski fight-show sa influenserima">
    <meta name="twitter:image" content="/assets/images/news/news-1.png">
    
    <!-- Favicon -->
<link rel="icon" type="image/x-icon" href="favicon/favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png">

<!-- Android Chrome Icons -->
<link rel="icon" type="image/png" sizes="192x192" href="/favicon/android-chrome-192x192.png">
<link rel="icon" type="image/png" sizes="512x512" href="/favicon/android-chrome-512x512.png">

<!-- Web App Manifest (ako imate site.webmanifest) -->
<link rel="manifest" href="/favicon/site.webmanifest">
    
    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Google Fonts -->
     
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Fonts Awesone -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Main CSS -->
    <link rel="stylesheet" href="css/loading-screen.css"> <!-- Loading Screen -->
    <link rel="stylesheet" href="css/main.css"> <!-- CSS Variables -->
    <link rel="stylesheet" href="css/modern-design.css"> <!-- Modern Design System -->

    <!-- Main JavaScript (loaded early with defer) -->
    <script src="js/main.js" defer></script>
    <script src="js/modern-ui.js" defer></script>

    <!-- Theme Color -->
    <meta name="theme-color" content="#c41e3a">
    
    <title>BIF - Balkan Influence Fighting</title>
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SportsOrganization",
        "name": "BIF - Balkan Influence Fighting",
        "description": "Najveći balkanski fight-show sa influenserima",
        "url": "https://bif-fighting.com",
        "logo": "https://bif-fighting.com/assets/images/news/news-1.png",
        "sameAs": [
            "https://www.facebook.com/BIFFighting",
            "https://www.instagram.com/bif_fighting",
            "https://www.youtube.com/c/BIFFighting"
        ],
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+381-11-123-4567",
            "contactType": "customer service",
            "email": "info@bif-fighting.com"
        },
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "Belgrade",
            "addressCountry": "RS"
        }
    }
    </script>

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

</head>
<body>
    <!-- Loading Screen -->
    <div id="loading-screen">
        <div class="loading-particles">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>
        <div class="loading-logo">
            <div class="loading-logo-text">BIF</div>
            <div class="loading-subtitle">Balkan Influence Fighting</div>
        </div>
        <div class="loading-spinner">
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
            <div class="spinner-dot"></div>
        </div>
        <div class="loading-text">Loading Experience...</div>
        <div class="loading-progress">
            <div class="loading-progress-bar"></div>
        </div>
    </div>

    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="sr-only">Skip to main content</a>

    <!-- Header -->
    <header role="banner">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <div class="logo-text">BIF</div>
                    <span class="lang-content active" data-lang="sr">Balkan Influence Fighting</span>
                    <span class="lang-content" data-lang="en">Balkan Influence Fighting</span>
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
                            <a href="#home" aria-label="Početna stranica">
                                <span class="lang-content active" data-lang="sr">Početna</span>
                                <span class="lang-content" data-lang="en">Home</span>
                            </a>
                        </li>
                        <li>
                            <a href="#fighters" aria-label="Naši borci">
                                <span class="lang-content active" data-lang="sr">Borci</span>
                                <span class="lang-content" data-lang="en">Fighters</span>
                            </a>
                        </li>
                        <li>
                            <a href="#news" aria-label="Najnovije vesti">
                                <span class="lang-content active" data-lang="sr">Vesti</span>
                                <span class="lang-content" data-lang="en">News</span>
                            </a>
                        </li>
                        <li>
                            <a href="#events" aria-label="Događaji">
                                <span class="lang-content active" data-lang="sr">Događaji</span>
                                <span class="lang-content" data-lang="en">Events</span>
                            </a>
                        </li>
                        <li>
                            <a href="#contact" aria-label="Kontakt informacije">
                                <span class="lang-content active" data-lang="sr">Kontakt</span>
                                <span class="lang-content" data-lang="en">Contact</span>
                            </a>
                        </li>
                        <li>
                            <a href="watch.php" aria-label="PPV Prenos uživo">
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
                                    <button class="lang-btn" onclick="bifApp.switchLanguage('sr')" data-lang="sr" aria-label="Srpski jezik">SR</button>
                                    <button class="lang-btn active" onclick="bifApp.switchLanguage('en')" data-lang="en" aria-label="English language">EN</button>
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
                        <button class="lang-btn active" onclick="bifApp.switchLanguage('sr')" data-lang="sr" aria-label="Srpski jezik">SR</button>
                        <button class="lang-btn" onclick="bifApp.switchLanguage('en')" data-lang="en" aria-label="English language">EN</button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main id="main-content">
        <!-- Hero Section -->
        <section class="hero section" id="home" role="banner">
            <div class="container">
                <div class="hero-content">
                    <h1>
                        <span class="lang-content active" data-lang="sr">Balkan Influence Fighting</span>
                        <span class="lang-content" data-lang="en">Balkan Influence Fighting</span>
                    </h1>
                    
                    <p>
                        <span class="lang-content active" data-lang="sr">Najveći balkanski fight-show sa influenserima</span>
                        <span class="lang-content" data-lang="en">The biggest Balkan fight show featuring influencers</span>
                    </p>
                    
                    <a href="#fighters" class="btn btn-primary cta-button">
                        <span class="lang-content active" data-lang="sr">Pogledaj Borce</span>
                        <span class="lang-content" data-lang="en">View Fighters</span>
                    </a>
                </div>
            </div>
            <!-- Scroll Indicator -->
            <div class="scroll-indicator">
                <i class="fas fa-chevron-down"></i>
            </div>
        </section>


        
<!-- ===== TIMER SECTION ===== -->
<section class="timer-section">
  <div class="container timer-container">

    <!-- Video preview umesto linka -->
    <div class="video-preview">
        <iframe
    src="<?php echo htmlspecialchars($heroSettings['video_url']); ?>"
    title="YouTube video preview"
    frameborder="0"
    loading="lazy"
    allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
    allowfullscreen>
  </iframe>
    </div>

    <!-- Countdown -->
    <div id="countdown" class="countdown">
      <div class="time-box">
        <span id="days">0</span>
        <small>Days</small>
      </div>
      <div class="time-box">
        <span id="hours">0</span>
        <small>Hours</small>
      </div>
      <div class="time-box">
        <span id="minutes">0</span>
        <small>Minutes</small>
      </div>
      <div class="time-box">
        <span id="seconds">0</span>
        <small>Seconds</small>
      </div>
    </div>
  </div>
</section>

<script>
  (function() {
    const target = new Date('<?php echo $heroSettings['countdown_date']; ?>').getTime();
    const daysEl = document.getElementById('days');
    const hoursEl = document.getElementById('hours');
    const minsEl = document.getElementById('minutes');
    const secsEl = document.getElementById('seconds');

    function updateCountdown() {
      const now = Date.now();
      const diff = target - now;
      if (diff < 0) return clearInterval(timerInterval);

      daysEl.textContent = Math.floor(diff / (1000 * 60 * 60 * 24));
      hoursEl.textContent = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      minsEl.textContent = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
      secsEl.textContent = Math.floor((diff % (1000 * 60)) / 1000);
    }

    const timerInterval = setInterval(updateCountdown, 1000);
    updateCountdown();
  })();
</script>

     

       <!-- Fighters Section -->
<!-- Fighters Section -->
<section class="fighters-section section" id="fighters">
    <div class="container">
        <h2 class="section-title">
            <span class="lang-content active" data-lang="sr">BIF Borci</span>
            <span class="lang-content" data-lang="en">BIF Fighters</span>
        </h2>
        
        <div class="fighters-carousel" role="region" aria-label="Fighters carousel">
            <button class="carousel-arrow prev" onclick="bifApp.previousSlide()" aria-label="Previous fighters">
                <span aria-hidden="true">‹</span>
            </button>
            <button class="carousel-arrow next" onclick="bifApp.nextSlide()" aria-label="Next fighters">
                <span aria-hidden="true">›</span>
            </button>
            
            <div class="fighters-container" id="fightersContainer">
                <?php foreach ($activeFighters as $fighter): ?>
                <a href="borci/<?php echo htmlspecialchars($fighter['slug']); ?>" class="fighter-card" aria-label="Detalji o borcu <?php echo htmlspecialchars($fighter['name']); ?>">
                    <div class="fighter-image">
                        <?php if (($fighter['wins'] ?? 0) > 0): ?>
                        <div class="champion-badge">
                            <span class="lang-content active" data-lang="sr">ŠAMPION</span>
                            <span class="lang-content" data-lang="en">CHAMPION</span>
                        </div>
                        <?php endif; ?>
                        <?php
                        $imgUrl = $fighter['image_url'] ?? '/assets/images/fighters/default.png';
                        // Remove escape slashes and ensure proper path
                        $imgUrl = str_replace('\\/', '/', $imgUrl);
                        // Add dot if starts with /
                        if (substr($imgUrl, 0, 1) === '/') {
                            $imgUrl = '.' . $imgUrl;
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($imgUrl); ?>"
                             alt="<?php echo htmlspecialchars($fighter['name'] . ' - ' . $fighter['nickname']); ?>"
                             loading="lazy"
                             width="400"
                             height="360">
                    </div>
                    <div class="fighter-info">
                        <h3 class="fighter-name">"<?php echo strtoupper(htmlspecialchars($fighter['nickname'] ?? $fighter['name'])); ?>"</h3>
                        <p class="fighter-nickname"><?php echo htmlspecialchars($fighter['name']); ?></p>
                        <p class="fighter-category">
                            <?php $weightClass = getFighterWeightClass($fighter); ?>
                            <span class="lang-content active" data-lang="sr">
                                <?php echo htmlspecialchars($weightClass['sr']); ?>
                            </span>
                            <span class="lang-content" data-lang="en">
                                <?php echo htmlspecialchars($weightClass['en']); ?>
                            </span>
                        </p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="carousel-nav" role="group" aria-label="Carousel navigation">
            <?php for ($i = 0; $i < count($activeFighters); $i++): ?>
            <button class="nav-dot <?php echo $i === 0 ? 'active' : ''; ?>"
                    onclick="bifApp.showSlide(<?php echo $i; ?>)"
                    aria-label="Go to slide <?php echo $i + 1; ?>">
            </button>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- ===== BIF STORE SECTION ===== -->
<section class="store-section section" id="store">
    <div class="container">
        <h2 class="section-title">
            <span class="lang-content active" data-lang="sr">BIF Store</span>
            <span class="lang-content" data-lang="en">BIF Store</span>
        </h2>
        
        <div class="store-intro">
            <p class="store-description">
                <span class="lang-content active" data-lang="sr">
                    Podržite svoje omiljene borce sa zvaničnim BIF dresovima! Visokokvalitetni materijali i dizajn koji odražava snagu i strast balkanskih boraca.
                </span>
                <span class="lang-content" data-lang="en">
                    Support your favorite fighters with official BIF jerseys! High-quality materials and design that reflects the strength and passion of Balkan fighters.
                </span>
            </p>
        </div>

        <div class="jerseys-grid">
            <!-- Crni Dres -->
            <div class="jersey-card">
                <div class="jersey-image">
                    <img src="assets/images/jerseys/crni.png" 
                         alt="BIF Crni Dres" 
                         loading="lazy"
                         width="400" 
                         height="400">
                    <div class="jersey-overlay">
                        <button class="view-details-btn" onclick="bifApp.openJerseyModal('black')">
                            <span class="lang-content active" data-lang="sr">Pogledaj Detalje</span>
                            <span class="lang-content" data-lang="en">View Details</span>
                        </button>
                    </div>
                </div>
                <div class="jersey-info">
                    <h3 class="jersey-name">
                        <span class="lang-content active" data-lang="sr">BIF Crni Dres</span>
                        <span class="lang-content" data-lang="en">BIF Black Jersey</span>
                    </h3>
                    <p class="jersey-description">
                        <span class="lang-content active" data-lang="sr">Klasičan crni dizajn sa BIF logom</span>
                        <span class="lang-content" data-lang="en">Classic black design with BIF logo</span>
                    </p>
                    <div class="jersey-price">
                        <span class="price">3.500 RSD</span>
                        <span class="shipping-note">
                            <span class="lang-content active" data-lang="sr">+ poštarina</span>
                            <span class="lang-content" data-lang="en">+ shipping</span>
                        </span>
                    </div>
                    <button class="btn btn-primary order-btn" onclick="bifApp.openOrderForm('black')">
                        <span class="lang-content active" data-lang="sr">Poruči Sada</span>
                        <span class="lang-content" data-lang="en">Order Now</span>
                    </button>
                </div>
            </div>

            <!-- Beli Dres -->
            <div class="jersey-card">
                <div class="jersey-image">
                    <img src="assets/images/jerseys/beli.png" 
                         alt="BIF Beli Dres" 
                         loading="lazy"
                         width="400" 
                         height="400">
                    <div class="jersey-overlay">
                        <button class="view-details-btn" onclick="bifApp.openJerseyModal('white')">
                            <span class="lang-content active" data-lang="sr">Pogledaj Detalje</span>
                            <span class="lang-content" data-lang="en">View Details</span>
                        </button>
                    </div>
                </div>
                <div class="jersey-info">
                    <h3 class="jersey-name">
                        <span class="lang-content active" data-lang="sr">BIF Beli Dres</span>
                        <span class="lang-content" data-lang="en">BIF White Jersey</span>
                    </h3>
                    <p class="jersey-description">
                        <span class="lang-content active" data-lang="sr">Elegantan beli dizajn sa BIF logom</span>
                        <span class="lang-content" data-lang="en">Elegant white design with BIF logo</span>
                    </p>
                    <div class="jersey-price">
                        <span class="price">3.500 RSD</span>
                        <span class="shipping-note">
                            <span class="lang-content active" data-lang="sr">+ poštarina</span>
                            <span class="lang-content" data-lang="en">+ shipping</span>
                        </span>
                    </div>
                    <button class="btn btn-primary order-btn" onclick="bifApp.openOrderForm('white')">
                        <span class="lang-content active" data-lang="sr">Poruči Sada</span>
                        <span class="lang-content" data-lang="en">Order Now</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Fight Store Partnership -->
        <div class="partner-promo">
            <div class="partner-promo-content">
                <div class="partner-promo-text">
                    <h3>
                        <span class="lang-content active" data-lang="sr">U Partnerstvu sa Fight Store Global</span>
                        <span class="lang-content" data-lang="en">In Partnership with Fight Store Global</span>
                    </h3>
                    <p>
                        <span class="lang-content active" data-lang="sr">
                            Naši dresovi se proizvode u saradnji sa Fight Store Global - vodećim brendom za sportsku opremu i borčiku odećу. 
                            Garantujemo vrhunski kvalitet materijala i izrade.
                        </span>
                        <span class="lang-content" data-lang="en">
                            Our jerseys are produced in partnership with Fight Store Global - the leading brand for sports equipment and fighting gear. 
                            We guarantee top quality materials and craftsmanship.
                        </span>
                    </p>
                </div>
                <div class="partner-promo-action">
                    <a href="https://fightstoreglobal.com" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">
                        <span class="lang-content active" data-lang="sr">Posetite Fight Store</span>
                        <span class="lang-content" data-lang="en">Visit Fight Store</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M7 17L17 7M17 7H7M17 7V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Order Modal -->
<div id="orderModal" class="order-modal" style="display: none;">
    <div class="modal-overlay" onclick="bifApp.closeOrderModal()"></div>
    <div class="modal-content">
        <button class="modal-close" onclick="bifApp.closeOrderModal()">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        
        <div class="modal-header">
            <h3 id="modalTitle">
                <span class="lang-content active" data-lang="sr">Porudžbina Dresa</span>
                <span class="lang-content" data-lang="en">Jersey Order</span>
            </h3>
        </div>
        
        <form class="order-form" onsubmit="bifApp.handleOrderSubmit(event)">
            <input type="hidden" id="selectedJersey" name="jersey" value="">
            
            <div class="selected-jersey-display">
                <img id="selectedJerseyImage" src="" alt="" width="100" height="100">
                <div class="selected-jersey-info">
                    <h4 id="selectedJerseyName"></h4>
                    <p class="selected-jersey-price">3.500 RSD + poštarina</p>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="order-name">
                        <span class="lang-content active" data-lang="sr">Ime i prezime *</span>
                        <span class="lang-content" data-lang="en">Full Name *</span>
                    </label>
                    <input type="text" id="order-name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="order-email">Email *</label>
                    <input type="email" id="order-email" name="email" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="order-phone">
                        <span class="lang-content active" data-lang="sr">Telefon *</span>
                        <span class="lang-content" data-lang="en">Phone *</span>
                    </label>
                    <input type="tel" id="order-phone" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label for="order-size">
                        <span class="lang-content active" data-lang="sr">Veličina *</span>
                        <span class="lang-content" data-lang="en">Size *</span>
                    </label>
                    <select id="order-size" name="size" required>
                        <option value="" disabled selected>
                            <span class="lang-content active" data-lang="sr">Izaberite veličinu</span>
                            <span class="lang-content" data-lang="en">Select size</span>
                        </option>
                        <option value="S">S</option>
                        <option value="M">M</option>
                        <option value="L">L</option>
                        <option value="XL">XL</option>
                        <option value="XXL">XXL</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="order-address">
                    <span class="lang-content active" data-lang="sr">Adresa za dostavu *</span>
                    <span class="lang-content" data-lang="en">Delivery Address *</span>
                </label>
                <textarea id="order-address" name="address" required rows="3" 
                          placeholder="Ulica, broj, grad, poštanski broj"></textarea>
            </div>
            
            <div class="form-group">
                <label for="order-notes">
                    <span class="lang-content active" data-lang="sr">Napomene (opcionalno)</span>
                    <span class="lang-content" data-lang="en">Notes (optional)</span>
                </label>
                <textarea id="order-notes" name="notes" rows="2" 
                          placeholder="Dodatne napomene za porudžbinu..."></textarea>
            </div>
            
            <div class="order-summary">
                <div class="summary-line">
                    <span>
                        <span class="lang-content active" data-lang="sr">Cena dresa:</span>
                        <span class="lang-content" data-lang="en">Jersey price:</span>
                    </span>
                    <span>3.500 RSD</span>
                </div>
                <div class="summary-line">
                    <span>
                        <span class="lang-content active" data-lang="sr">Poštarina:</span>
                        <span class="lang-content" data-lang="en">Shipping:</span>
                    </span>
                    <span>
                        <span class="lang-content active" data-lang="sr">Po dogovoru</span>
                        <span class="lang-content" data-lang="en">To be arranged</span>
                    </span>
                </div>
                <div class="summary-total">
                    <span>
                        <span class="lang-content active" data-lang="sr">Ukupno:</span>
                        <span class="lang-content" data-lang="en">Total:</span>
                    </span>
                    <span>3.500 RSD + poštarina</span>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary order-submit-btn">
                <span class="lang-content active" data-lang="sr">Pošaljite Porudžbinu</span>
                <span class="lang-content" data-lang="en">Submit Order</span>
            </button>
            
            <p class="order-note">
                <span class="lang-content active" data-lang="sr">
                    * Kontaktiraćemo vas u roku od 24 sata radi potvrde porudžbine i dogovora o načinu plaćanja i dostave.
                </span>
                <span class="lang-content" data-lang="en">
                    * We will contact you within 24 hours to confirm the order and arrange payment and delivery.
                </span>
            </p>
        </form>
    </div>
</div>

<section class="sponsor-section section" id="sponsorship">
    <div class="container">
        <div class="sponsor-content">
            <div class="sponsor-text">
                <h2 class="section-title">
                    <span class="lang-content active" data-lang="sr">Postanite Deo BIF Porodice</span>
                    <span class="lang-content" data-lang="en">Become Part of BIF Family</span>
                </h2>
                
                <div class="sponsor-description">
                    <p class="lead-text">
                        <span class="lang-content active" data-lang="sr">Želite da budete sponzor najbrže rastuće MMA organizacije na Balkanu?</span>
                        <span class="lang-content" data-lang="en">Want to sponsor the fastest growing MMA organization in the Balkans?</span>
                    </p>
                    
                    <p>
                        <span class="lang-content active" data-lang="sr">
                            Balkan Influence Fighting predstavlja jedinstvenu priliku za brendove koji žele da budu deo spektakularnih događaja koji okupljaju hiljade gledalaca uživo i milione putem streaminga. Naši događaji se emituju širom regiona i privlače najširu publiku.
                        </span>
                        <span class="lang-content" data-lang="en">
                            Balkan Influence Fighting represents a unique opportunity for brands that want to be part of spectacular events that gather thousands of live viewers and millions through streaming. Our events are broadcast throughout the region and attract the widest audience.
                        </span>
                    </p>
                </div>

                <div class="sponsor-stats">
                    <div class="stat-item">
                        <div class="stat-number">500K+</div>
                        <div class="stat-label">
                            <span class="lang-content active" data-lang="sr">Gledalaca po događaju</span>
                            <span class="lang-content" data-lang="en">Viewers per event</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">15+</div>
                        <div class="stat-label">
                            <span class="lang-content active" data-lang="sr">Uspešnih događaja</span>
                            <span class="lang-content" data-lang="en">Successful events</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">8</div>
                        <div class="stat-label">
                            <span class="lang-content active" data-lang="sr">Zemalja regiona</span>
                            <span class="lang-content" data-lang="en">Regional countries</span>
                        </div>
                    </div>
                </div>

                <div class="sponsor-packages">
                    <h3>
                        <span class="lang-content active" data-lang="sr">Paketi Sponzorstva</span>
                        <span class="lang-content" data-lang="en">Sponsorship Packages</span>
                    </h3>
                    <div class="packages-grid">
                        <div class="package-item">
                            <div class="package-icon">🥇</div>
                            <div class="package-title">
                                <span class="lang-content active" data-lang="sr">Glavni Partner</span>
                                <span class="lang-content" data-lang="en">Main Partner</span>
                            </div>
                            <div class="package-description">
                                <span class="lang-content active" data-lang="sr">Logo na kavezu, dresovima boraca i svim marketinškim materijalima</span>
                                <span class="lang-content" data-lang="en">Logo on cage, fighter gear and all marketing materials</span>
                            </div>
                        </div>
                        <div class="package-item">
                            <div class="package-icon">🥈</div>
                            <div class="package-title">
                                <span class="lang-content active" data-lang="sr">Premium Partner</span>
                                <span class="lang-content" data-lang="en">Premium Partner</span>
                            </div>
                            <div class="package-description">
                                <span class="lang-content active" data-lang="sr">Promocija tokom prenosa i VIP mesta za događaje</span>
                                <span class="lang-content" data-lang="en">Promotion during broadcast and VIP seats for events</span>
                            </div>
                        </div>
                        <div class="package-item">
                            <div class="package-icon">🥉</div>
                            <div class="package-title">
                                <span class="lang-content active" data-lang="sr">Osnovni Partner</span>
                                <span class="lang-content" data-lang="en">Basic Partner</span>
                            </div>
                            <div class="package-description">
                                <span class="lang-content active" data-lang="sr">Logo na digitalnim platformama i društvenim mrežama</span>
                                <span class="lang-content" data-lang="en">Logo on digital platforms and social networks</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sponsor-cta">
                    <a href="#contact" class="btn btn-primary sponsor-btn">
                        <span class="lang-content active" data-lang="sr">Kontaktitajte Nas</span>
                        <span class="lang-content" data-lang="en">Contact Us</span>
                    </a>
                    <p class="cta-note">
                        <span class="lang-content active" data-lang="sr">Budite deo naše priče uspeha!</span>
                        <span class="lang-content" data-lang="en">Be part of our success story!</span>
                    </p>
                </div>
            </div>

            <div class="sponsor-visual">
                <!-- Dodao sam link oko celog kontejnera i uklonio overlay -->
                <a href="https://www.oktagonbet.com/mob/sr/registracija" target="_blank" rel="noopener noreferrer" class="sponsor-image-container">
                    <img src="assets/images/oktagon.jpg" alt="Oktagon Bet - Glavni sponzor BIF" loading="lazy">

                     <!-- ovo je overlay koji se prikazuje na hover -->
  <div class="sponsor-overlay">
    <span class="lang-content active" data-lang="sr">
      Uzmi 500 FREE BETA i 200 FREE SPINOVA
    </span>
    <span class="lang-content" data-lang="en">
      Get 500 FREE BETS and 200 FREE SPINS
    </span>
  </div>
                </a>
            </div>
        </div>
    </div>
</section>

        <!-- News Section -->
        <section class="news-section section" id="news">
    <div class="container">
        <h2 class="section-title">
            <span class="lang-content active" data-lang="sr">Najnovije Vesti</span>
            <span class="lang-content" data-lang="en">Latest News</span>
        </h2>
        
        <div class="news-grid">
            <?php foreach ($publishedNews as $article):
                $dateFormatted = formatNewsDate($article['published_at']);
                $newsUrl = 'vesti/' . htmlspecialchars($article['slug']) . '.html';
                $imgUrl = $article['image_url'] ?? 'assets/images/news/news-1.png';
                // Fix escape slashes in image URL
                $imgUrl = str_replace('\\/', '/', $imgUrl);

                // Get bilingual titles (fallback to old format for compatibility)
                $titleSr = $article['title_sr'] ?? $article['title'] ?? 'Bez naslova';
                $titleEn = $article['title_en'] ?? $article['title'] ?? 'No title';

                // Get bilingual excerpts
                $excerptSr = !empty($article['excerpt_sr'])
                    ? $article['excerpt_sr']
                    : (!empty($article['excerpt']) ? $article['excerpt'] : extractExcerpt($article['content_sr'] ?? $article['content'] ?? ''));
                $excerptEn = !empty($article['excerpt_en'])
                    ? $article['excerpt_en']
                    : (!empty($article['excerpt']) ? $article['excerpt'] : extractExcerpt($article['content_en'] ?? $article['content'] ?? ''));
            ?>
            <article class="news-card">
                <div class="news-image">
                    <img src="<?php echo htmlspecialchars($imgUrl); ?>"
                         alt="<?php echo htmlspecialchars($titleSr); ?>"
                         loading="lazy"
                         width="400"
                         height="220">
                </div>
                <div class="news-content">
                    <time class="news-date" datetime="<?php echo $dateFormatted['iso']; ?>">
                        <span class="lang-content active" data-lang="sr"><?php echo $dateFormatted['sr']; ?></span>
                        <span class="lang-content" data-lang="en"><?php echo $dateFormatted['en']; ?></span>
                    </time>
                    <h3 class="news-title">
                        <a href="<?php echo $newsUrl; ?>">
                            <span class="lang-content active" data-lang="sr"><?php echo htmlspecialchars($titleSr); ?></span>
                            <span class="lang-content" data-lang="en"><?php echo htmlspecialchars($titleEn); ?></span>
                        </a>
                    </h3>
                    <p class="news-excerpt">
                        <span class="lang-content active" data-lang="sr"><?php echo htmlspecialchars($excerptSr); ?></span>
                        <span class="lang-content" data-lang="en"><?php echo htmlspecialchars($excerptEn); ?></span>
                    </p>
                    <a href="<?php echo $newsUrl; ?>" class="read-more">
                        <span class="lang-content active" data-lang="sr">Pročitaj više</span>
                        <span class="lang-content" data-lang="en">Read More</span>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

        <!-- Events Section -->
        <section class="events-section section" id="events">
            <div class="container">
                <h2 class="section-title">
                    <span class="lang-content active" data-lang="sr">Uskoro</span>
                    <span class="lang-content" data-lang="en">Soon</span>
                </h2>
                
                <div class="events-grid">
                    <div class="event-card">
                        <div class="event-date">
                            <span class="event-day">21</span>
                            <span class="event-month">
                                <span class="lang-content active" data-lang="sr">JUL</span>
                                <span class="lang-content" data-lang="en">July</span>
                            </span>
                        </div>
                        <div class="event-info">
                            <h3 class="event-title">BIF 1: New Rise</h3>
                            <p class="event-location">
                                <span class="lang-content active" data-lang="sr">Beograd , Republika Srbija</span>
                                <span class="lang-content" data-lang="en">Belgrade , Republic Serbia</span>
                            </p>
                            <p class="event-time">20:00</p>
                        </div>
                        <div class="event-action">
                            <a href="https://www.youtube.com/@bif.events" class="btn btn-primary">
                                <span class="lang-content active" data-lang="sr">Gledaj ovde</span>
                                <span class="lang-content" data-lang="en">Watch here</span>
                            </a>
                        </div>
                    </div>

                    <div class="event-card">
                        <div class="event-date">
                            <span class="event-day">10</span>
                            <span class="event-month">
                                <span class="lang-content active" data-lang="sr">Oktobar</span>
                                <span class="lang-content" data-lang="en">October</span>
                            </span>
                        </div>
                        <div class="event-info">
                            <h3 class="event-title">BIF 2: After Summer Showdown</h3>
                            <p class="event-location">
                                <span class="lang-content active" data-lang="sr">USKORO</span>
                                <span class="lang-content" data-lang="en">SOON</span>
                            </p>
                            <p class="event-time">19:30</p>
                        </div>
                        <div class="event-action">
                            <a href="https://www.youtube.com/@bif.events" class="btn btn-secondary">
                                <span class="lang-content active" data-lang="sr">Uskoro</span>
                                <span class="lang-content" data-lang="en">Coming Soon</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>



<!-- Contact Section -->
        <section class="contact-section section" id="contact">
            <div class="container">
                <h2 class="section-title">
                    <span class="lang-content active" data-lang="sr">Kontaktirajte Nas</span>
                    <span class="lang-content" data-lang="en">Contact Us</span>
                </h2>
                
                <div class="contact-content">
                    <div class="contact-info">
                        <div class="contact-intro">
                            <h3>
                                <span class="lang-content active" data-lang="sr">Spremni za saradnju?</span>
                                <span class="lang-content" data-lang="en">Ready for collaboration?</span>
                            </h3>
                            <p>
                                <span class="lang-content active" data-lang="sr">
                                    Bilo da ste borac koji želi da se pridruži BIF-u, sponzor koji traži partnerstvo, 
                                    ili jednostavno imate pitanja o našim događajima - kontaktirajte nas!
                                </span>
                                <span class="lang-content" data-lang="en">
                                    Whether you're a fighter looking to join BIF, a sponsor seeking partnership, 
                                    or simply have questions about our events - contact us!
                                </span>
                            </p>
                        </div>
                        
                        <div class="contact-details">
                            <div class="contact-item">
                                <div class="contact-icon">📧</div>
                                <div class="contact-text">
                                    <h4>Email</h4>
                                    <p><a href="mailto:business@bif.events">business@bif.events</a></p>
                                </div>
                            </div>
                            
                            <div class="contact-item">
                                <div class="contact-icon">📞</div>
                                <div class="contact-text">
                                    <h4>
                                        <span class="lang-content active" data-lang="sr">Telefon</span>
                                        <span class="lang-content" data-lang="en">Phone</span>
                                    </h4>
                                    <p><a href="tel:+381601484066">+381 601484066</a></p>
                                </div>
                            </div>
                            
                            <div class="contact-item">
                                <div class="contact-icon">📍</div>
                                <div class="contact-text">
                                    <h4>
                                        <span class="lang-content active" data-lang="sr">Lokacija</span>
                                        <span class="lang-content" data-lang="en">Location</span>
                                    </h4>
                                    <p>
                                        <span class="lang-content active" data-lang="sr">Beograd, Srbija</span>
                                        <span class="lang-content" data-lang="en">Belgrade, Serbia</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="contact-form-container">
                        <form class="contact-form" onsubmit="bifApp.handleContactSubmit(event)">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="contact-name">
                                        <span class="lang-content active" data-lang="sr">Ime i prezime *</span>
                                        <span class="lang-content" data-lang="en">Full Name *</span>
                                    </label>
                                    <input type="text" 
                                           id="contact-name" 
                                           name="name" 
                                           required 
                                           placeholder="Marko Petrović">
                                </div>
                                
                                <div class="form-group">
                                    <label for="contact-email">Email *</label>
                                    <input type="email" 
                                           id="contact-email" 
                                           name="email" 
                                           required 
                                           placeholder="marko@example.com">
                                </div>
                            </div>
                            <div class="form-group">
    <label for="contact-subject">
        <span class="lang-content active" data-lang="sr">Tema *</span>
        <span class="lang-content" data-lang="en">Subject *</span>
    </label>
    <select id="contact-subject" name="subject" required>
        <option value="" disabled selected data-sr="Izaberite temu" data-en="Select a subject">
            
        </option>
        <option value="sponsorship" data-sr="Sponzorstvo" data-en="Sponsorship">
            Sponzorstvo
        </option>
        <option value="fighter" data-sr="Pridruživanje kao borac" data-en="Joining as a fighter">
            Pridruživanje kao borac
        </option>
        <option value="media" data-sr="Mediji i PR" data-en="Media & PR">
            Mediji i PR
        </option>
        <option value="events" data-sr="Događaji" data-en="Events">
            Događaji
        </option>
        <option value="other" data-sr="Ostalo" data-en="Other">
            Ostalo
        </option>
    </select>
</div>
                            
                            <div class="form-group">
                                <label for="contact-phone">
                                    <span class="lang-content active" data-lang="sr">Telefon</span>
                                    <span class="lang-content" data-lang="en">Phone</span>
                                </label>
                                <input type="tel" 
                                       id="contact-phone" 
                                       name="phone" 
                                       placeholder="+381 60 123 4567">
                            </div>
                            
                            <div class="form-group">
                                <label for="contact-message">
                                    <span class="lang-content active" data-lang="sr">Poruka *</span>
                                    <span class="lang-content" data-lang="en">Message *</span>
                                </label>
                                <textarea id="contact-message" 
                                          name="message" 
                                          required 
                                          rows="5"
                                          placeholder="Opišite detaljno vašu ideju ili zahtev..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary contact-submit-btn">
                                <span class="lang-content active" data-lang="sr">Pošaljite Poruku</span>
                                <span class="lang-content" data-lang="en">Send Message</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
        <!-- Partners Section -->
        <section class="partners-section section" id="partners">
    <div class="container">
        <h2 class="section-title">
            <span class="lang-content active" data-lang="sr">Naši Partneri</span>
            <span class="lang-content" data-lang="en">Our Partners</span>
        </h2>
        
        <div class="partners-grid">
            <a href="https://www.popzify.com" target="_blank" rel="noopener noreferrer" class="partner-logo">
                <img src="assets/images/partners/popzify.png" alt="Popzify - Partner" loading="lazy">
            </a>
            <a href="https://www.oktagonbet.com/mob/sr/registracija" target="_blank" rel="noopener noreferrer" class="partner-logo">
                <img src="assets/images/partners/oktagon.jpg" alt="Oktagon Bet - Partner" loading="lazy">
            </a>
            <a href="https://www.correctscore.pro" target="_blank" rel="noopener noreferrer" class="partner-logo">
                <img src="assets/images/partners/cslogo.png" alt="Correct Score Pro - Partner" loading="lazy">
            </a>
        </div>
    </div>
</section>
        <!-- Newsletter Section -->
        <section class="newsletter-section section">
            <div class="container">
                <div class="newsletter-content">
                    <h2>
                        <span class="lang-content active" data-lang="sr">Ostanite Obavešteni</span>
                        <span class="lang-content" data-lang="en">Stay Updated</span>
                    </h2>
                    <p>
                        <span class="lang-content active" data-lang="sr">Prijavite se za naš newsletter i budite prvi koji će saznati o novim borbama i događajima</span>
                        <span class="lang-content" data-lang="en">Subscribe to our newsletter and be first to know about new fights and events</span>
                    </p>
                    <form class="newsletter-form" onsubmit="bifApp.handleNewsletterSubmit(event)">
                        <input type="email" 
                               placeholder="Your email address" 
                               required
                               aria-label="Email address">
                        <button type="submit" class="btn btn-primary">
                            <span class="lang-content active" data-lang="sr">Pretplati se</span>
                            <span class="lang-content" data-lang="en">Subscribe</span>
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <!-- Footer -->
<footer id="contact" role="contentinfo">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>
                    <span class="lang-content active" data-lang="sr">O Nama</span>
                    <span class="lang-content" data-lang="en">About Us</span>
                </h3>
                <p>
                    <span class="lang-content active" data-lang="sr">Balkan Influence Fighting je najjača MMA organizacija na Balkanu, posvećena promociji vrhunskih borilačkih veština i sportskog duha.</span>
                    <span class="lang-content" data-lang="en">Balkan Influence Fighting is the strongest MMA organization in the Balkans, dedicated to promoting top-tier martial arts and sportsmanship.</span>
                </p>
            </div>
            
            <div class="footer-section">
                <h3>
                    <span class="lang-content active" data-lang="sr">Kontakt</span>
                    <span class="lang-content" data-lang="en">Contact</span>
                </h3>
                <p>📧 <a href="mailto:business@bif.events">business@bif.events</a></p>
                <p>📞 <a href="tel:+381 601484066">+381 601484066</a></p>
                <p>
                    <span class="lang-content active" data-lang="sr">📍 Beograd, Srbija</span>
                    <span class="lang-content" data-lang="en">📍 Belgrade, Serbia</span>
                </p>
            </div>
            
            <div class="footer-section">
                <h3>
                    <span class="lang-content active" data-lang="sr">Prati Nas</span>
                    <span class="lang-content" data-lang="en">Follow Us</span>
                </h3>
                <div class="social-links">
                    <a href="https://x.com/BIFFighting" class="social-link" aria-label="X (Twitter)" target="_blank" rel="noopener">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </a>
                    <a href="https://www.instagram.com/bif.events?igsh=MW9pMHlrM2Znc28wOA==" class="social-link" aria-label="Instagram" target="_blank" rel="noopener">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </a>
                    <a href="https://www.youtube.com/@bif.events/" class="social-link" aria-label="YouTube" target="_blank" rel="noopener">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                    </a>
                    <a href="https://www.tiktok.com/@bif.events" class="social-link" aria-label="TikTok" target="_blank" rel="noopener">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                        </svg>
                    </a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>
                    <span class="lang-content active" data-lang="sr">Brzi Linkovi</span>
                    <span class="lang-content" data-lang="en">Quick Links</span>
                </h3>
                <a href="#fighters">
                    <span class="lang-content active" data-lang="sr">Borci</span>
                    <span class="lang-content" data-lang="en">Fighters</span>
                </a>
                <a href="#news">
                    <span class="lang-content active" data-lang="sr">Vesti</span>
                    <span class="lang-content" data-lang="en">News</span>
                </a>
                <a href="#events">
                    <span class="lang-content active" data-lang="sr">Događaji</span>
                    <span class="lang-content" data-lang="en">Events</span>
                </a>
                <a href="/privacy">
                    <span class="lang-content active" data-lang="sr">Privatnost</span>
                    <span class="lang-content" data-lang="en">Privacy</span>
                </a>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>
                <span class="lang-content active" data-lang="sr">© 2025 Balkan Influence Fighting. Sva prava zadržana.</span>
                <span class="lang-content" data-lang="en">© 2025 Balkan Influence Fighting. All rights reserved.</span>
            </p>
            <p class="powered-by">
                Powered by <a href="https://popzify.com" target="_blank" rel="noopener">Popzify</a>
            </p>
        </div>
    </div>
</footer>

     <!-- Main JavaScript already loaded in head with defer -->

    <!-- Google Analytics (Optional) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'GA_MEASUREMENT_ID');
    </script>
    
    <!-- Inline Critical JavaScript -->
    <script>
        // Critical loading optimization
        document.documentElement.classList.add('js-enabled');
        
        // Performance monitoring
        if ('performance' in window) {
            window.addEventListener('load', () => {
                setTimeout(() => {
                    const perfData = performance.getEntriesByType('navigation')[0];
                    if (perfData) { // Check if perfData exists
                        console.log('Page Load Time:', perfData.loadEventEnd - perfData.loadEventStart, 'ms');
                    }
                }, 0);
            });
        }
        
        // Newsletter form handler - This is now primarily handled by main.js
        // The inline script here could act as a fallback or be removed if main.js loads reliably first.
        // For simplicity and to avoid conflicts, better to rely on the main.js handler.
        // function handleNewsletterSubmit(event) { ... } // Consider removing if bifApp.handleNewsletterSubmit is robust
    </script>
</body>
</html>


