<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="BIF - Pregled Borbe: Matej Batinic vs Gadzhimurad Khebdeev">
    <meta name="keywords" content="BIF, MMA, pregled borbe, statistika, Matej Batinic, Gadzhimurad Khebdeev">
    <meta name="author" content="BIF - Balkan Influence Fighting">

    <meta property="og:title" content="BIF Pregled Borbe - Batinic vs Khebdeev">
    <meta property="og:description" content="Detaljna statistika i pregled borbe između Mateja Batinića i Gadzhimurada Khebdeeva.">
    <meta property="og:type" content="article">
    <meta property="og:url" content="https://bif.events/fight-preview.html"> <!-- Update if dynamic -->
    <meta property="og:image" content="/assets/images/bif-fight-preview-og.jpg"> <!-- Create a specific OG image -->

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="BIF Pregled Borbe - Batinic vs Khebdeev">
    <meta name="twitter:description" content="Detaljna statistika i pregled borbe.">
    <meta name="twitter:image" content="/assets/images/bif-fight-preview-og.jpg">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">

    <!-- Android Chrome Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="/favicon/android-chrome-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/favicon/android-chrome-512x512.png">

    <!-- Web App Manifest (ako imate site.webmanifest) -->
    <link rel="manifest" href="/favicon/site.webmanifest">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/fight-preview.css">
    <meta name="theme-color" content="#c41e3a">

    <title>Pregled Borbe | BIF</title>
</head>
<body class="fight-preview-page-body">
    <a href="#main-content" class="sr-only">Skip to main content</a>

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main id="main-content" class="fight-preview-page">
        <section class="fight-preview-header-section">
            <div class="container">
                <div class="fight-title-banner">
                    <span class="lang-content active" data-lang="sr">BORBA ZA TITULU</span>
                    <span class="lang-content" data-lang="en">TITLE FIGHT</span>
                </div>
                <div class="fight-matchup">
                    <div class="fighter-info left">
                        <span class="fight-result-indicator win">
                            <span class="lang-content active" data-lang="sr">POBEDA</span>
                            <span class="lang-content" data-lang="en">WIN</span>
                        </span>
                        <h2>MATEJ BATINIC</h2>
                        <p class="fighter-country">
                            <!-- <img src="assets/images/flags/hr.svg" alt="Croatian Flag"> -->
                            <span class="lang-content active" data-lang="sr">HRVATSKA</span>
                            <span class="lang-content" data-lang="en">CROATIA</span>
                        </p>
                    </div>
                    <div class="versus">VS</div>
                    <div class="fighter-info right">
                        <h2>GADZHIMURAD KHEBDEEV</h2>
                        <p class="fighter-country">
                            <!-- <img src="assets/images/flags/ru.svg" alt="Russian Flag"> -->
                            <span class="lang-content active" data-lang="sr">RUSKA FEDERACIJA</span>
                            <span class="lang-content" data-lang="en">RUSSIAN FEDERATION</span>
                        </p>
                    </div>
                </div>
                <div class="fight-details-bar">
                    <div class="detail-item">
                        <span class="label"><span class="lang-content active" data-lang="sr">RUNDA</span><span class="lang-content" data-lang="en">ROUND</span></span>
                        <span class="value">5</span>
                    </div>
                    <div class="detail-item">
                        <span class="label"><span class="lang-content active" data-lang="sr">VREME</span><span class="lang-content" data-lang="en">TIME</span></span>
                        <span class="value">01:16</span>
                    </div>
                    <div class="detail-item method">
                        <span class="label"><span class="lang-content active" data-lang="sr">METODA</span><span class="lang-content" data-lang="en">METHOD</span></span>
                        <span class="value">SUB - REAR NAKED CHOKE</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="fight-stats-section section">
            <div class="container">
                <nav class="round-tabs-nav" aria-label="Statistika po rundama">
                    <button class="round-tab active" data-round="full">
                        <span class="lang-content active" data-lang="sr">Cela Borba</span>
                        <span class="lang-content" data-lang="en">Full Fight</span>
                    </button>
                    <button class="round-tab" data-round="1">
                        <span class="lang-content active" data-lang="sr">Runda 1</span>
                        <span class="lang-content" data-lang="en">Round 1</span>
                    </button>
                    <button class="round-tab" data-round="2">
                        <span class="lang-content active" data-lang="sr">Runda 2</span>
                        <span class="lang-content" data-lang="en">Round 2</span>
                    </button>
                    <button class="round-tab" data-round="3">
                        <span class="lang-content active" data-lang="sr">Runda 3</span>
                        <span class="lang-content" data-lang="en">Round 3</span>
                    </button>
                    <button class="round-tab" data-round="4">
                        <span class="lang-content active" data-lang="sr">Runda 4</span>
                        <span class="lang-content" data-lang="en">Round 4</span>
                    </button>
                    <button class="round-tab" data-round="5">
                        <span class="lang-content active" data-lang="sr">Runda 5</span>
                        <span class="lang-content" data-lang="en">Round 5</span>
                    </button>
                </nav>

                <div class="stats-comparison-area">
                    <div class="fighter-silhouette left">
                        <img src="assets/images/big-siluete-left.png" alt="Borac 1 Silueta">
                        <div class="champion-banner-preview">
                             <span class="lang-content active" data-lang="sr">Šampion</span>
                             <span class="lang-content" data-lang="en">Champion</span>
                        </div>
                    </div>

                    <div class="stats-bars-container">
                        <!-- Strikes -->
                        <div class="stat-row">
                            <div class="stat-value fighter1">135 <span class="total">OF 177 [76%]</span></div>
                            <div class="progress-bar-container">
                                <div class="progress-bar fighter1" style="width: 76%;"></div>
                            </div>
                            <div class="stat-label">
                                <span class="lang-content active" data-lang="sr">Udarci</span>
                                <span class="lang-content" data-lang="en">Strikes</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar fighter2" style="width: 87%;"></div>
                            </div>
                            <div class="stat-value fighter2">118 <span class="total">OF 136 [87%]</span></div>
                        </div>
                        <!-- Punches -->
                        <div class="stat-row">
                            <div class="stat-value fighter1">82 <span class="total">OF 110 [75%]</span></div>
                            <div class="progress-bar-container">
                                <div class="progress-bar fighter1" style="width: 75%;"></div>
                            </div>
                            <div class="stat-label">
                                <span class="lang-content active" data-lang="sr">Ručni udarci</span>
                                <span class="lang-content" data-lang="en">Punches</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar fighter2" style="width: 86%;"></div>
                            </div>
                            <div class="stat-value fighter2">81 <span class="total">OF 94 [86%]</span></div>
                        </div>
                        <!-- Elbows -->
                        <div class="stat-row">
                            <div class="stat-value fighter1">6 <span class="total">OF 6 [100%]</span></div>
                            <div class="progress-bar-container">
                                <div class="progress-bar fighter1" style="width: 100%;"></div>
                            </div>
                            <div class="stat-label">
                                <span class="lang-content active" data-lang="sr">Laktovi</span>
                                <span class="lang-content" data-lang="en">Elbows</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar fighter2" style="width: 88%;"></div>
                            </div>
                            <div class="stat-value fighter2">7 <span class="total">OF 8 [88%]</span></div>
                        </div>
                        <!-- Kicks -->
                        <div class="stat-row">
                            <div class="stat-value fighter1">35 <span class="total">OF 49 [71%]</span></div>
                            <div class="progress-bar-container">
                                <div class="progress-bar fighter1" style="width: 71%;"></div>
                            </div>
                            <div class="stat-label">
                                <span class="lang-content active" data-lang="sr">Nožni udarci</span>
                                <span class="lang-content" data-lang="en">Kicks</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar fighter2" style="width: 83%;"></div>
                            </div>
                            <div class="stat-value fighter2">15 <span class="total">OF 18 [83%]</span></div>
                        </div>
                        <!-- Knees -->
                        <div class="stat-row">
                            <div class="stat-value fighter1">12 <span class="total">OF 12 [100%]</span></div>
                            <div class="progress-bar-container">
                                <div class="progress-bar fighter1" style="width: 100%;"></div>
                            </div>
                            <div class="stat-label">
                                <span class="lang-content active" data-lang="sr">Kolena</span>
                                <span class="lang-content" data-lang="en">Knees</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar fighter2" style="width: 94%;"></div>
                            </div>
                            <div class="stat-value fighter2">15 <span class="total">OF 16 [94%]</span></div>
                        </div>
                        <!-- Takedowns -->
                        <div class="stat-row">
                            <div class="stat-value fighter1">2 <span class="total">OF 2 [100%]</span></div>
                            <div class="progress-bar-container">
                                <div class="progress-bar fighter1" style="width: 100%;"></div>
                            </div>
                            <div class="stat-label">
                                <span class="lang-content active" data-lang="sr">Obaranja</span>
                                <span class="lang-content" data-lang="en">Takedowns</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar fighter2" style="width: 50%;"></div>
                            </div>
                            <div class="stat-value fighter2">3 <span class="total">OF 6 [50%]</span></div>
                        </div>
                         <!-- Knockdowns -->
                        <div class="stat-row knockdowns">
                            <div class="stat-value fighter1">0</div>
                            <div class="stat-label">
                                <span class="lang-content active" data-lang="sr">Nokdauni</span>
                                <span class="lang-content" data-lang="en">Knockdowns</span>
                            </div>
                            <div class="stat-value fighter2">0</div>
                        </div>
                    </div>

                    <div class="fighter-silhouette right">
                        <img src="assets/images/big-siluete-right.png" alt="Borac 2 Silueta">
                        <!-- Add champion banner if applicable -->
                    </div>
                </div>
            </div>
        </section>

        <!-- BIF Fighters Section (Copied from index.php, with different ID for JS if needed) -->
        <section class="fighters-section section" id="fightersOnPreviewPage">
            <div class="container">
                <h2 class="section-title">
                    <span class="lang-content active" data-lang="sr">BIF Borci</span>
                    <span class="lang-content" data-lang="en">BIF Fighters</span>
                </h2>

                <div class="fighters-carousel" role="region" aria-label="Fighters carousel">
                    <button class="carousel-arrow prev" onclick="bifApp.previousSlide('fightersPreviewContainer')" aria-label="Previous fighters"> <!-- Potentially pass ID -->
                        <span aria-hidden="true">&#8249;</span>
                    </button>
                    <button class="carousel-arrow next" onclick="bifApp.nextSlide('fightersPreviewContainer')" aria-label="Next fighters"> <!-- Potentially pass ID -->
                        <span aria-hidden="true">&#8250;</span>
                    </button>

                    <div class="fighters-container" id="fightersPreviewContainer">
                        <!-- Fighter Card 1 -->
                        <a href="fighter-details.html?fighter=marko-bojkovic" class="fighter-card" aria-label="Detalji o borcu Marko Bojkovi&#263;">
                            <div class="fighter-image">
                                <img src="assets/images/fighters/400x360-bojkovic.png" alt="Marko Bojkovi&#263;" loading="lazy" width="400" height="360">
                            </div>
                            <div class="fighter-info">
                                <h3 class="fighter-name">MARKO BOJKOVI&#262;</h3>
                                <p class="fighter-nickname">"The Skull Crusher"</p>
                            </div>
                        </a>
                        <!-- Fighter Card 2 -->
                        <a href="fighter-details.html?fighter=francisco-barrio" class="fighter-card" aria-label="Detalji o borcu Francisco Barrio">
                            <div class="fighter-image">
                                <img src="assets/images/fighters/400x360-bojkovic.png" alt="Francisco Barrio" loading="lazy" width="400" height="360">
                            </div>
                            <div class="fighter-info">
                                <h3 class="fighter-name">FRANCISCO BARRIO</h3>
                                <p class="fighter-nickname">"Croata"</p>
                            </div>
                        </a>
                        <!-- Add more fighter cards as needed, using 400x360-bojkovic.png -->
                        <a href="fighter-details.html?fighter=jordan-barton" class="fighter-card" aria-label="Detalji o borcu Jordan Barton">
                            <div class="fighter-image">
                                <img src="assets/images/fighters/400x360-bojkovic.png" alt="Jordan Barton" loading="lazy" width="400" height="360">
                            </div>
                            <div class="fighter-info">
                                <h3 class="fighter-name">JORDAN BARTON</h3>
                                <p class="fighter-nickname">"The Destroyer"</p>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="carousel-nav" role="group" aria-label="Carousel navigation for preview page">
                    <!-- Dots will be generated by JS or need manual addition if JS isn't adapted -->
                </div>
            </div>
        </section>
    </main>

       <!-- Footer -->
    <!-- Footer -->
<?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="main.js"></script>
    <script>
        // Basic tab functionality for round selection
        document.querySelectorAll('.round-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.round-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                // Add logic here to update stats based on selected round (data-round attribute)
                console.log('Selected round:', this.dataset.round);
            });
        });

        // Ensure bifApp is initialized and functions (language, theme) work on this page
        if (typeof bifApp === 'undefined' && typeof BIFApp !== 'undefined') {
            window.bifApp = new BIFApp();
        } else if (window.bifApp && window.bifApp.init) {
            // If bifApp is already there, ensure language and theme are applied
             bifApp.switchLanguage(bifApp.currentLang || 'sr');
             bifApp.setTheme(localStorage.getItem('bif-theme') || 'light');
        }

        // Initialize carousel for the BIF Borci section on this page if JS needs specific call
        // This might require adapting your main.js BIFApp.initializeCarousel to accept an element or ID.
        // For now, the HTML structure is there. Functionality depends on main.js.
        // Example: if (bifApp && bifApp.initializeCarouselForElement) {
        //    bifApp.initializeCarouselForElement(document.getElementById('fightersOnPreviewPage'));
        // }
    </script>
</body>
</html>
