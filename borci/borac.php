<?php
/**
 * Dinamička stranica za prikaz informacija o borcu
 * Učitava podatke iz fighters.json na osnovu slug-a
 */

// Učitaj slug iz URL-a
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// Učitaj fighters.json
$fightersFile = dirname(__DIR__) . '/data/fighters.json';
if (!file_exists($fightersFile)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 - Fighter not found</h1></body></html>';
    exit;
}

$fightersData = file_get_contents($fightersFile);
$fighters = json_decode($fightersData, true);

if (!$fighters) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>Error</title></head><body><h1>Error loading data</h1></body></html>';
    exit;
}

// Pronađi borca sa ovim slug-om
$fighter = null;
foreach ($fighters as $f) {
    if (isset($f['slug']) && $f['slug'] === $slug) {
        $fighter = $f;
        break;
    }
}

// Ako borac nije pronađen
if (!$fighter) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 - Fighter not found</h1><p>Borac sa slug-om "' . htmlspecialchars($slug) . '" nije pronađen.</p><a href="index.php">Nazad na listu boraca</a></body></html>';
    exit;
}

// Pripremi podatke za prikaz
$name = htmlspecialchars($fighter['name'] ?? 'Unknown');
$nickname = htmlspecialchars($fighter['nickname'] ?? '');
$age = (int)($fighter['age'] ?? 0);
$height = (int)($fighter['height'] ?? 0);
$weight = (int)($fighter['weight'] ?? 0);
$wins = (int)($fighter['wins'] ?? 0);
$losses = (int)($fighter['losses'] ?? 0);
$draws = (int)($fighter['draws'] ?? 0);
$overallWins = (int)($fighter['overall_wins'] ?? 0);
$overallLosses = (int)($fighter['overall_losses'] ?? 0);
$overallDraws = (int)($fighter['overall_draws'] ?? 0);
$overallKo = (int)($fighter['overall_ko'] ?? 0);
$bio = htmlspecialchars($fighter['bio'] ?? '');

// Image URL handling
$imageUrl = $fighter['image_url'] ?? '/assets/images/fighters/default.png';
$imageUrl = str_replace('\\/', '/', $imageUrl);
if (substr($imageUrl, 0, 1) === '/') {
    $imageUrl = '..' . $imageUrl;
}

// Konverzija težine i visine
$weightLbs = round($weight * 2.20462);
$heightFt = floor($height / 30.48);
$heightIn = round(($height % 30.48) / 2.54);

// Odredi kategoriju težine (weight class)
function getWeightClassByWeight($weight) {
    if ($weight <= 49) return ['sr' => 'MUHA', 'en' => 'FLYWEIGHT'];
    if ($weight <= 52) return ['sr' => 'BANTAM', 'en' => 'BANTAMWEIGHT'];
    if ($weight <= 56) return ['sr' => 'PEROLAKA', 'en' => 'FEATHERWEIGHT'];
    if ($weight <= 60) return ['sr' => 'LAKA', 'en' => 'LIGHTWEIGHT'];
    if ($weight <= 64) return ['sr' => 'POLUVELTER', 'en' => 'LIGHT WELTERWEIGHT'];
    if ($weight <= 69) return ['sr' => 'VELTER', 'en' => 'WELTERWEIGHT'];
    if ($weight <= 75) return ['sr' => 'SREDNJA', 'en' => 'MIDDLEWEIGHT'];
    if ($weight <= 81) return ['sr' => 'POLUTEŠKA', 'en' => 'LIGHT HEAVYWEIGHT'];
    if ($weight <= 91) return ['sr' => 'TEŠKA', 'en' => 'HEAVYWEIGHT'];
    return ['sr' => 'SUPERTEŠKA', 'en' => 'SUPER HEAVYWEIGHT'];
}

function getWeightClassLabels($weightClassKey) {
    $weightClasses = [
        'flyweight' => ['sr' => 'MUHA', 'en' => 'FLYWEIGHT'],
        'bantamweight' => ['sr' => 'BANTAM', 'en' => 'BANTAMWEIGHT'],
        'featherweight' => ['sr' => 'PEROLAKA', 'en' => 'FEATHERWEIGHT'],
        'lightweight' => ['sr' => 'LAKA', 'en' => 'LIGHTWEIGHT'],
        'light-welterweight' => ['sr' => 'POLUVELTER', 'en' => 'LIGHT WELTERWEIGHT'],
        'welterweight' => ['sr' => 'VELTER', 'en' => 'WELTERWEIGHT'],
        'middleweight' => ['sr' => 'SREDNJA', 'en' => 'MIDDLEWEIGHT'],
        'light-heavyweight' => ['sr' => 'POLUTEŠKA', 'en' => 'LIGHT HEAVYWEIGHT'],
        'heavyweight' => ['sr' => 'TEŠKA', 'en' => 'HEAVYWEIGHT'],
        'super-heavyweight' => ['sr' => 'SUPERTEŠKA', 'en' => 'SUPER HEAVYWEIGHT']
    ];
    return $weightClasses[$weightClassKey] ?? null;
}

// Proveri da li postoji manuelno podešena kategorija
$customWeightClass = $fighter['weight_class'] ?? '';
if (!empty($customWeightClass)) {
    $weightClass = getWeightClassLabels($customWeightClass);
    if (!$weightClass) {
        // Fallback ako je nepoznata kategorija
        $weightClass = getWeightClassByWeight($weight);
    }
} else {
    // Automatski odredi prema težini
    $weightClass = getWeightClassByWeight($weight);
}

// Meta podaci
$metaTitle = $name . ($nickname ? ' - ' . $nickname : '') . ' - Detalji Borca | BIF';
$metaDescription = 'Detalji o borcu: ' . $name . ($nickname ? ' - ' . $nickname : '') . ' - BIF Borac';
$pageUrl = 'https://bif.events/borci/' . $slug;
?><!DOCTYPE html>
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
    <meta name="description" content="<?php echo $metaDescription; ?>">
    <meta name="keywords" content="BIF, boks, borac, <?php echo $name; ?>, <?php echo $nickname; ?>, statistika, borbe">
    <meta name="author" content="BIF - Balkan Influence Fighting">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo $metaTitle; ?>">
    <meta property="og:description" content="<?php echo $metaDescription; ?>">
    <meta property="og:type" content="profile">
    <meta property="og:url" content="<?php echo $pageUrl; ?>">
    <meta property="og:image" content="<?php echo $imageUrl; ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/loading-screen.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/modern-design.css">
    <link rel="stylesheet" href="../css/fighter-details.css">
    <meta name="theme-color" content="#c41e3a">

    <title><?php echo $metaTitle; ?></title>

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Person",
        "name": "<?php echo $name; ?>",
        <?php if ($nickname): ?>"alternateName": "<?php echo $nickname; ?>",<?php endif; ?>
        "jobTitle": "Boxer",
        "worksFor": {
            "@type": "SportsOrganization",
            "name": "BIF - Balkan Influence Fighting"
        },
        "image": "<?php echo $pageUrl; ?>",
        "url": "<?php echo $pageUrl; ?>",
        "height": "<?php echo $height; ?> cm",
        "weight": "<?php echo $weight; ?> kg"
    }
    </script>
</head>
<body>
    <a href="#main-content" class="sr-only">Skip to main content</a>

    <!-- Header -->
    <?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

    <main id="main-content" class="fighter-details-page">
        <section class="fighter-profile-section section">
            <div class="container">
                <div class="fighter-profile-grid">
                    <div class="fighter-image-container">
                        <img src="<?php echo $imageUrl; ?>" alt="<?php echo $name . ($nickname ? ' - ' . $nickname : ''); ?>" loading="lazy">
                    </div>
                    <div class="fighter-info-container">
                        <div class="fighter-header">
                            <h1>
                                <span class="lang-content active" data-lang="sr"><?php echo strtoupper($name); ?></span>
                                <span class="lang-content" data-lang="en"><?php echo strtoupper($name); ?></span>
                                <?php if ($age > 0): ?><span class="fighter-age">, <?php echo $age; ?></span><?php endif; ?>
                            </h1>
                            <?php if ($nickname): ?>
                            <p class="fighter-nickname">"<?php echo $nickname; ?>"</p>
                            <?php endif; ?>
                            <p class="fighter-category-details">
                                <span class="lang-content active" data-lang="sr"><?php echo $weightClass['sr']; ?></span>
                                <span class="lang-content" data-lang="en"><?php echo $weightClass['en']; ?></span>
                            </p>
                        </div>

                        <div class="fighter-stats-overview">
                            <div class="stat-block">
                                <h3>
                                    <span class="lang-content active" data-lang="sr">BIF SKOR</span>
                                    <span class="lang-content" data-lang="en">BIF SCORE</span>
                                </h3>
                                <p><?php echo $wins; ?>-<?php echo $losses; ?>-<?php echo $draws; ?> <span style="font-size: 0.6em; color: var(--gray-400);">(W-L-D)</span></p>
                            </div>
                            <div class="stat-block">
                                <h3>
                                    <span class="lang-content active" data-lang="sr">UKUPNI SKOR</span>
                                    <span class="lang-content" data-lang="en">OVERALL SCORE</span>
                                </h3>
                                <p><?php echo $overallWins; ?>-<?php echo $overallLosses; ?>-<?php echo $overallDraws; ?> <span style="font-size: 0.6em; color: var(--gray-400);">(W-L-D)</span><?php if ($overallKo > 0): ?> <span style="font-size: 0.7em; color: #c41e3a; font-weight: 700;"><?php echo $overallKo; ?> KO/TKO</span><?php endif; ?></p>
                            </div>
                        </div>

                        <div class="fighter-attributes">
                            <?php if ($height > 0): ?>
                            <div class="attribute-item">
                                <span class="label"><span class="lang-content active" data-lang="sr">Visina</span><span class="lang-content" data-lang="en">Height</span></span>
                                <span class="value"><?php echo $height; ?> CM (<?php echo $heightFt; ?>'<?php echo $heightIn; ?>" FT)</span>
                            </div>
                            <?php endif; ?>
                            <?php if ($weight > 0): ?>
                            <div class="attribute-item">
                                <span class="label"><span class="lang-content active" data-lang="sr">Težina</span><span class="lang-content" data-lang="en">Weight</span></span>
                                <span class="value"><?php echo $weight; ?> KG (<?php echo $weightLbs; ?> LBS)</span>
                            </div>
                            <?php endif; ?>
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
                        </div>

                        <?php if (!empty($bio)): ?>
                        <div class="fighter-bio">
                            <h3>
                                <span class="lang-content active" data-lang="sr">Biografija</span>
                                <span class="lang-content" data-lang="en">Biography</span>
                            </h3>
                            <p><?php echo nl2br($bio); ?></p>
                        </div>
                        <?php endif; ?>
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

    <!-- JavaScript -->
    <script src="../js/loading-screen.js"></script>
    <script src="../js/main.js"></script>
</body>
</html>
