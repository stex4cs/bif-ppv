<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('bif-theme');
                var theme = savedTheme || 'dark';
                document.documentElement.setAttribute('data-theme', theme);
                document.documentElement.style.colorScheme = theme;
            } catch (err) {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.documentElement.style.colorScheme = 'dark';
            }
        })();
    </script>
    <meta name="description" content="Detalji o borcu: Vuk Branković - Ridji - BIF Borac">
    <meta name="keywords" content="BIF, boks, borac, Vuk Branković, Ridji, statistika, borbe">
    <meta name="author" content="BIF - Balkan Influence Fighting">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="Vuk Branković - Ridji - BIF Borac">
    <meta property="og:description" content="Pogledajte profil i istoriju borbi za Vuka Brankovića - Ridji.">
    <meta property="og:type" content="profile">
    <meta property="og:url" content="https://bif.events/fighter-details.html?fighter=vuk-brankovic">
    <meta property="og:image" content="/assets/images/fighters/ridji.png">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="/css/loading-screen.css">
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="/css/modern-design.css">
    <link rel="stylesheet" href="/css/fighter-details.css">
    <meta name="theme-color" content="#c41e3a">

    <title>Vuk Branković - Ridji - Detalji Borca | BIF</title>

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Person",
        "name": "Vuk Branković",
        "alternateName": "Ridji",
        "jobTitle": "Boxer",
        "worksFor": {
            "@type": "SportsOrganization",
            "name": "BIF - Balkan Influence Fighting"
        },
        "image": "https://bif-fighting.com/assets/images/fighters/ridji.png",
        "url": "https://bif-fighting.com/fighter-details.html?fighter=vuk-brankovic",
        "height": "160 cm",
        "weight": "60 kg"
    }
    </script>
</head>
<body>
    <a href="#main-content" class="sr-only">Skip to main content</a>

    <?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

    <main id="main-content" class="fighter-details-page">
        <section class="fighter-profile-section section">
            <div class="container">
                <div class="fighter-profile-grid">
                    <div class="fighter-image-container">
                        <img src="/assets/images/fighters/ridji.png" alt="Vuk Branković - Ridji" loading="lazy">
                        <div class="champion-banner">
                            <span class="lang-content active" data-lang="sr">ŠAMPION</span>
                            <span class="lang-content" data-lang="en">CHAMPION</span>
                        </div>
                    </div>
                    <div class="fighter-info-container">
                        <div class="fighter-header">
                            <h1>
                                <span class="lang-content active" data-lang="sr">VUK BRANKOVIĆ</span>
                                <span class="lang-content" data-lang="en">VUK BRANKOVIĆ</span>
                                <span class="fighter-age">, 18</span>
                            </h1>
                            <p class="fighter-nickname">"Ridji"</p>
                            <p class="fighter-category-details">
                                <span class="lang-content active" data-lang="sr">PERO KATEGORIJA</span>
                                <span class="lang-content" data-lang="en">FEATHERWEIGHT</span>
                            </p>
                        </div>

                        <div class="fighter-stats-overview">
                            <div class="stat-block">
                                <h3>
                                    <span class="lang-content active" data-lang="sr">BIF SKOR</span>
                                    <span class="lang-content" data-lang="en">BIF SCORE</span>
                                </h3>
                                <p>0-0-0 <span style="font-size: 0.6em; color: var(--gray-400);">(W-L-D)</span></p>
                            </div>
                            <div class="stat-block">
                                <h3>
                                    <span class="lang-content active" data-lang="sr">UKUPNI SKOR</span>
                                    <span class="lang-content" data-lang="en">OVERALL SCORE</span>
                                </h3>
                                <p>0-0-0 <span style="font-size: 0.6em; color: var(--gray-400);">(W-L-D)</span></p>
                            </div>
                        </div>

                        <div class="fighter-attributes">
                            <div class="attribute-item">
                                <span class="label"><span class="lang-content active" data-lang="sr">Visina</span><span class="lang-content" data-lang="en">Height</span></span>
                                <span class="value">160 CM (5'3 FT)</span>
                            </div>
                            <div class="attribute-item">
                                <span class="label"><span class="lang-content active" data-lang="sr">Težina</span><span class="lang-content" data-lang="en">Weight</span></span>
                                <span class="value">60 KG (132 LBS)</span>
                            </div>
                            <div class="attribute-item">
                                <span class="label"><span class="lang-content active" data-lang="sr">Raspon ruku</span><span class="lang-content" data-lang="en">Reach</span></span>
                                <span class="value">N/A</span>
                            </div>
                            <div class="attribute-item">
                                <span class="label"><span class="lang-content active" data-lang="sr">Disciplina</span><span class="lang-content" data-lang="en">Discipline</span></span>
                                <span class="value">Boks</span>
                            </div>
                            <div class="attribute-item">
                                <span class="label"><span class="lang-content active" data-lang="sr">Država</span><span class="lang-content" data-lang="en">Country</span></span>
                                <span class="value">
                                    <span class="lang-content active" data-lang="sr">Srbija</span>
                                    <span class="lang-content" data-lang="en">Serbia</span>
                                </span>
                            </div>
                            <div class="attribute-item">
                                <span class="label"><span class="lang-content active" data-lang="sr">Grad</span><span class="lang-content" data-lang="en">City</span></span>
                                <span class="value">Beograd</span>
                            </div>
                            <div class="attribute-item">
                                <span class="label"><span class="lang-content active" data-lang="sr">Datum rođenja</span><span class="lang-content" data-lang="en">DOB</span></span>
                                <span class="value">xx.xx.2006.</span>
                            </div>
                        </div>

                        <div class="fighter-status">
                             <p class="champion-status">
                                <span class="icon">🏆</span>
                                <span class="lang-content active" data-lang="sr">BIF Šampion Pero Kategorije</span>
                                <span class="lang-content" data-lang="en">BIF Featherweight Champion</span>
                            </p>
                        </div>

                        <div class="fighter-social-media">
                           <a href="https://www.tiktok.com/@ridji_2006?_t=ZN-8xUoGX8Yx7e&_r=1" class="social-link" aria-label="TikTok" target="_blank" rel="noopener">
                            <i class="fab fa-tiktok"></i></a>
                            <a href="https://www.instagram.com/ridji_06?igsh=cTVrZzMyaXNkYW16" class="social-link" aria-label="Instagram" target="_blank" rel="noopener">
                            <i class="fab fa-instagram"></i> </a>
                        </div>

                        <div class="watch-last-fight">
                            <a href="#" class="btn btn-primary">
                                <span class="lang-content active" data-lang="sr">Pogledaj poslednju borbu</span>
                                <span class="lang-content" data-lang="en">Watch Last Fight</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="fight-history-section section">
            <div class="container">
                <h2 class="section-title">
                    <span class="lang-content active" data-lang="sr">Istorija Borbi</span>
                    <span class="lang-content" data-lang="en">Fight History</span>
                </h2>
                <div class="fight-history-table-container">
                    <div style="text-align: center; padding: 40px; color: var(--light-text-secondary);">
                        <p style="font-size: 18px; margin-bottom: 10px;">
                            <span class="lang-content active" data-lang="sr">Nema zabeleženih borbi</span>
                            <span class="lang-content" data-lang="en">No fights recorded</span>
                        </p>
                        <p style="font-size: 14px;">
                            <span class="lang-content active" data-lang="sr">Borbe će biti dodane kada se održe</span>
                            <span class="lang-content" data-lang="en">Fights will be added when they occur</span>
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>

    <script src="/js/main.js"></script>
    <script>
        if (!document.documentElement.classList.contains('js-enabled')) {
             document.documentElement.classList.add('js-enabled');
        }
    </script>
</body>
</html>
