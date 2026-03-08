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
    <meta name="description" content="BIF 15: Najavljeni novi mečevi - Spektakularni događaj BIF 15">
    <meta name="keywords" content="BIF, MMA, vest, BIF 15, mečevi, borbe">
    <meta name="author" content="BIF - Balkan Influence Fighting">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="BIF 15: Najavljeni novi mečevi">
    <meta property="og:description" content="Spektakularni događaj BIF 15 objavljen je sa neverovatnim mečevima">
    <meta property="og:type" content="article">
    <meta property="og:url" content="https://bif.events/bif15.html">
    <meta property="og:image" content="/assets/images/news/news-1.png">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="BIF 15: Najavljeni novi mečevi">
    <meta name="twitter:description" content="Spektakularni događaj BIF 15 objavljen je sa neverovatnim mečevima">
    <meta name="twitter:image" content="/assets/images/news/news-1.jpg">

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

    <link rel="stylesheet" href="/css/loading-screen.css">
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="/css/modern-design.css">
    <link rel="stylesheet" href="/css/news.css">



    <meta name="theme-color" content="#c41e3a">

    <title>BIF 15: Najavljeni novi mečevi | BIF News</title>
</head>
<body class="news-page-body">
    <a href="#main-content" class="sr-only">Skip to main content</a>

    <?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

    <main id="main-content" class="news-page">
        <!-- Breadcrumb Navigation -->
        <section class="breadcrumb-section">
            <div class="container">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol>
                        <li><a href="../index.php">
                            <span class="lang-content active" data-lang="sr">Početna</span>
                            <span class="lang-content" data-lang="en">Home</span>
                        </a></li>
                        <li><a href="../index.php#news">
                            <span class="lang-content active" data-lang="sr">Vesti</span>
                            <span class="lang-content" data-lang="en">News</span>
                        </a></li>
                        <li aria-current="page">
                            <span class="lang-content active" data-lang="sr">BIF 15: Najavljeni novi mečevi</span>
                            <span class="lang-content" data-lang="en">BIF 15: New Fights Announced</span>
                        </li>
                    </ol>
                </nav>
            </div>
        </section>

        <!-- News Article -->
        <article class="news-article-section section">
            <div class="container">
                <div class="news-article">
                    <!-- Article Header -->
                    <header class="article-header">
                        <div class="article-meta">
                            <time class="article-date" datetime="2025-05-25">
                                <span class="lang-content active" data-lang="sr">25. Maj 2025</span>
                                <span class="lang-content" data-lang="en">May 25, 2025</span>
                            </time>
                            <span class="article-category">
                                <span class="lang-content active" data-lang="sr">DOGAĐAJI</span>
                                <span class="lang-content" data-lang="en">EVENTS</span>
                            </span>
                        </div>

                        <h1 class="article-title">
                            <span class="lang-content active" data-lang="sr">BIF 15: Najavljeni novi mečevi</span>
                            <span class="lang-content" data-lang="en">BIF 15: New Fights Announced</span>
                        </h1>

                        <p class="article-excerpt">
                            <span class="lang-content active" data-lang="sr">Spektakularni događaj BIF 15 objavljen je sa neverovatnim mečevima koji će se održati sledeći mesec u Štark Areni</span>
                            <span class="lang-content" data-lang="en">Spectacular BIF 15 event announced with incredible fights scheduled for next month at Štark Arena</span>
                        </p>
                    </header>

                    <!-- Featured Image -->
                    <div class="article-image">
                        <img src="/assets/images/news/news-1.jpg"
                             alt="BIF 15 Event Announcement"
                             loading="lazy">
                        <div class="image-caption">
                            <span class="lang-content active" data-lang="sr">BIF 15 - Najspektakularniji događaj godine</span>
                            <span class="lang-content" data-lang="en">BIF 15 - The most spectacular event of the year</span>
                        </div>
                    </div>

                    <!-- Article Content -->
                    <div class="article-content">
                        <div class="lang-content active" data-lang="sr">
                            <p>Balkan Influence Fighting ponosno najavljuje BIF 15, događaj koji će obeležiti ovu godinu u svetu mixed martial arts-a na Balkanu. Ovaj spektakularni turnir će se održati <strong>15. juna 2025. godine u Štark Areni</strong>, gde će se najbolji regionalni borci suočiti u borbi za titule i slavu.</p>

                            <h2>Glavne borbe večeri</h2>
                            <p>Program večeri će biti otvoren sa nekoliko uzbudljivih mečeva u različitim kategorijama, a vrhunac će predstavljati borba za titulu prvaka u lakoj kategoriji između aktuelnog šampiona <em>Marka Bojkovića</em> i pretendenta <em>Francisca Barria</em>.</p>

                            <blockquote>
                                <p>"Ovo će biti najveći događaj u istoriji BIF-a. Pripremili smo nezaboravnu noć za sve ljubitelje MMA sporta."</p>
                                <cite>- Direktor BIF organizacije</cite>
                            </blockquote>

                            <h2>Karte u prodaji</h2>
                            <p>Karte za BIF 15 su već u prodaji po sledećim cenama:</p>
                            <ul>
                                <li><strong>VIP sektor:</strong> 15.000 RSD</li>
                                <li><strong>Premium:</strong> 8.000 RSD</li>
                                <li><strong>Standardne:</strong> 4.500 RSD</li>
                                <li><strong>Studentske:</strong> 2.500 RSD</li>
                            </ul>

                            <p>Očekuje se da će arena biti kompletno rasprodana, pa preporučujemo kupovinu karata u najkraćem roku.</p>

                            <h2>Prenos uživo</h2>
                            <p>Za sve koji neće moći da prisustvuju uživo, BIF 15 će biti prenošen uživo na našem YouTube kanalu, kao i na partnerskim televizijskim stanicama širom regiona.</p>
                        </div>

                        <div class="lang-content" data-lang="en">
                            <p>Balkan Influence Fighting proudly announces BIF 15, an event that will mark this year in the world of mixed martial arts in the Balkans. This spectacular tournament will be held on <strong>June 15, 2025 at Štark Arena</strong>, where the best regional fighters will face each other in the fight for titles and glory.</p>

                            <h2>Main Fights of the Evening</h2>
                            <p>The evening's program will open with several exciting matches in different categories, and the climax will be the fight for the lightweight champion title between the current champion <em>Marko Bojković</em> and challenger <em>Francisco Barrio</em>.</p>

                            <blockquote>
                                <p>"This will be the biggest event in BIF's history. We have prepared an unforgettable night for all MMA fans."</p>
                                <cite>- BIF Organization Director</cite>
                            </blockquote>

                            <h2>Tickets on Sale</h2>
                            <p>Tickets for BIF 15 are already on sale at the following prices:</p>
                            <ul>
                                <li><strong>VIP sector:</strong> 15,000 RSD</li>
                                <li><strong>Premium:</strong> 8,000 RSD</li>
                                <li><strong>Standard:</strong> 4,500 RSD</li>
                                <li><strong>Student:</strong> 2,500 RSD</li>
                            </ul>

                            <p>The arena is expected to be completely sold out, so we recommend purchasing tickets as soon as possible.</p>

                            <h2>Live Broadcast</h2>
                            <p>For all those who will not be able to attend live, BIF 15 will be broadcast live on our YouTube channel, as well as on partner television stations throughout the region.</p>
                        </div>
                    </div>

                    <!-- Article Footer -->
                    <footer class="article-footer">
                        <div class="article-tags">
                            <span class="tag-label">
                                <span class="lang-content active" data-lang="sr">Tagovi:</span>
                                <span class="lang-content" data-lang="en">Tags:</span>
                            </span>
                            <span class="tag">BIF 15</span>
                            <span class="tag">
                                <span class="lang-content active" data-lang="sr">Mečevi</span>
                                <span class="lang-content" data-lang="en">Fights</span>
                            </span>
                            <span class="tag">
                                <span class="lang-content active" data-lang="sr">Štark Arena</span>
                                <span class="lang-content" data-lang="en">Štark Arena</span>
                            </span>
                            <span class="tag">MMA</span>
                        </div>

                        <div class="article-share">
                            <span class="share-label">
                                <span class="lang-content active" data-lang="sr">Podeli:</span>
                                <span class="lang-content" data-lang="en">Share:</span>
                            </span>
                            <a href="#" class="share-link facebook" aria-label="Share on Facebook">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </a>
                            <a href="#" class="share-link twitter" aria-label="Share on X">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                </svg>
                            </a>
                            <a href="#" class="share-link linkedin" aria-label="Share on LinkedIn">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                            </a>
                        </div>
                    </footer>
                </div>

                <!-- Related News -->
                <section class="related-news">
                    <h2 class="related-title">
                        <span class="lang-content active" data-lang="sr">Povezane Vesti</span>
                        <span class="lang-content" data-lang="en">Related News</span>
                    </h2>

                    <div class="related-grid">
                        <article class="related-card">
                            <div class="related-image">
                                <img src="/assets/images/news/news-2.jpeg" alt="Related News 1" loading="lazy">
                            </div>
                            <div class="related-content">
                                <time class="related-date">22. Maj 2025</time>
                                <h3><a href="#">
                                    <span class="lang-content active" data-lang="sr">Novi šampion u lakoj kategoriji</span>
                                    <span class="lang-content" data-lang="en">New Lightweight Champion</span>
                                </a></h3>
                            </div>
                        </article>

                        <article class="related-card">
                            <div class="related-image">
                                <img src="/assets/images/news/news-3.jpg" alt="Related News 2" loading="lazy">
                            </div>
                            <div class="related-content">
                                <time class="related-date">20. Maj 2025</time>
                                <h3><a href="#">
                                    <span class="lang-content active" data-lang="sr">Karte u prodaji za BIF 16</span>
                                    <span class="lang-content" data-lang="en">Tickets on Sale for BIF 16</span>
                                </a></h3>
                            </div>
                        </article>
                    </div>
                </section>
            </div>
        </article>
    </main>

    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>

    <script src="/js/main.js"></script>
    <script>
        // Critical loading optimization (if not already in main.js or body)
        if (!document.documentElement.classList.contains('js-enabled')) {
             document.documentElement.classList.add('js-enabled');
        }
        // Initialize BIFApp if it's not already globally available or specific init for this page
        // This assumes main.js creates a global bifApp instance.
        // If not, you might need: const localBifApp = new BIFApp();
        // but ensure it doesn't conflict with the one from index.php if navigating.
        // For simplicity, relying on the global instance from main.js.
    </script>
</body>
</html>
