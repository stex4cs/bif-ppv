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

// Meta podaci - SEO optimized
$recordShort = $wins . '-' . $losses . ($draws > 0 ? '-' . $draws : '');
$metaTitle = $name . ($nickname ? ' "' . $nickname . '"' : '') . ' — BIF Borac | ' . $weightClass['sr'] . ' | Rekord ' . $recordShort;
$metaDescription = 'Zvanični profil BIF borca ' . $name . ($nickname ? ' "' . $nickname . '"' : '') . '. Težinska kategorija: ' . $weightClass['sr'] . '. BIF rekord: ' . $wins . ' pobeda, ' . $losses . ' poraza' . ($draws > 0 ? ', ' . $draws . ' nerešeno' : '') . '. Statistike, istorija borbi i video snimci.';
$metaDescription = mb_substr($metaDescription, 0, 160);
$pageUrl = 'https://bif.events/borci/' . $slug;
// Absolute image URL for social sharing
$absImageUrl = $fighter['image_url'] ?? '/assets/images/fighters/default.png';
$absImageUrl = str_replace('\\/', '/', $absImageUrl);
if (substr($absImageUrl, 0, 4) !== 'http') {
    $absImageUrl = 'https://bif.events' . (substr($absImageUrl, 0, 1) === '/' ? '' : '/') . $absImageUrl;
}
?><!DOCTYPE html>
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
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="author" content="BIF - Balkan Influence Fighting">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="<?php echo $pageUrl; ?>">

    <!-- Open Graph Meta Tags -->
    <meta property="og:site_name" content="BIF - Balkan Influence Fighting">
    <meta property="og:locale" content="sr_RS">
    <meta property="og:locale:alternate" content="en_US">
    <meta property="og:title" content="<?php echo htmlspecialchars($metaTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta property="og:type" content="profile">
    <meta property="og:url" content="<?php echo $pageUrl; ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($absImageUrl); ?>">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($name . ($nickname ? ' - ' . $nickname : '')); ?>">
    <meta property="profile:first_name" content="<?php echo htmlspecialchars(explode(' ', $name)[0]); ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($metaTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($absImageUrl); ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php
        $_cssBase = dirname(__DIR__) . '/css/';
        $_cssVer = [
            'loading-screen' => file_exists($_cssBase.'loading-screen.css') ? filemtime($_cssBase.'loading-screen.css') : time(),
            'main' => file_exists($_cssBase.'main.css') ? filemtime($_cssBase.'main.css') : time(),
            'modern-design' => file_exists($_cssBase.'modern-design.css') ? filemtime($_cssBase.'modern-design.css') : time(),
            'fighter-details' => file_exists($_cssBase.'fighter-details.css') ? filemtime($_cssBase.'fighter-details.css') : time(),
        ];
    ?>
    <link rel="stylesheet" href="../css/loading-screen.css?v=<?php echo $_cssVer['loading-screen']; ?>">
    <link rel="stylesheet" href="../css/main.css?v=<?php echo $_cssVer['main']; ?>">
    <link rel="stylesheet" href="../css/modern-design.css?v=<?php echo $_cssVer['modern-design']; ?>">
    <link rel="stylesheet" href="../css/fighter-details.css?v=<?php echo $_cssVer['fighter-details']; ?>">
    <meta name="theme-color" content="#c41e3a">

    <title><?php echo $metaTitle; ?></title>

    <script type="application/ld+json">
    <?php
    $personSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => $name,
        'url' => $pageUrl,
        'image' => $absImageUrl,
        'jobTitle' => 'Professional Boxer',
        'worksFor' => [
            '@type' => 'SportsOrganization',
            'name' => 'BIF - Balkan Influence Fighting',
            'url' => 'https://bif.events',
        ],
    ];
    if ($nickname) $personSchema['alternateName'] = $nickname;
    if ($height > 0) $personSchema['height'] = ['@type' => 'QuantitativeValue', 'value' => $height, 'unitCode' => 'CMT'];
    if ($weight > 0) $personSchema['weight'] = ['@type' => 'QuantitativeValue', 'value' => $weight, 'unitCode' => 'KGM'];
    if (!empty($fighter['bio'])) $personSchema['description'] = mb_substr(strip_tags($fighter['bio']), 0, 300);
    echo json_encode($personSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    ?>
    </script>

    <!-- Breadcrumb Schema -->
    <script type="application/ld+json">
    <?php
    $breadcrumbs = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Početna', 'item' => 'https://bif.events/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Borci', 'item' => 'https://bif.events/#fighters'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $name, 'item' => $pageUrl],
        ],
    ];
    echo json_encode($breadcrumbs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    ?>
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
                            <span class="fighter-eyebrow">
                                <span class="lang-content active" data-lang="sr">BIF BORAC</span>
                                <span class="lang-content" data-lang="en">BIF FIGHTER</span>
                            </span>
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

        <?php
        $fights = $fighter['fights'] ?? [];
        // Sort by date descending (newest first)
        usort($fights, function($a, $b) {
            return strtotime($b['date'] ?? '1970-01-01') <=> strtotime($a['date'] ?? '1970-01-01');
        });

        $methodLabels = [
            'ko'  => ['sr' => 'KO',      'en' => 'KO'],
            'tko' => ['sr' => 'TKO',     'en' => 'TKO'],
            'ud'  => ['sr' => 'Odluka',  'en' => 'Unanimous Decision'],
            'sd'  => ['sr' => 'Podelj.', 'en' => 'Split Decision'],
            'md'  => ['sr' => 'Većin.',  'en' => 'Majority Decision'],
            'sub' => ['sr' => 'Submis.', 'en' => 'Submission'],
            'dq'  => ['sr' => 'Diskvalif.', 'en' => 'Disqualification'],
        ];
        $resultLabels = [
            'win'  => ['sr' => 'POBEDA', 'en' => 'WIN',  'cls' => 'win'],
            'loss' => ['sr' => 'PORAZ',  'en' => 'LOSS', 'cls' => 'loss'],
            'draw' => ['sr' => 'NEREŠENO', 'en' => 'DRAW', 'cls' => 'draw'],
            'nc'   => ['sr' => 'BEZ OD.',  'en' => 'NC',   'cls' => 'nc'],
        ];

        function ytEmbed($url) {
            if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|v/))([A-Za-z0-9_-]{6,})~', $url, $m)) {
                return 'https://www.youtube.com/embed/' . $m[1];
            }
            return '';
        }
        ?>
        <section class="fight-history-section section">
            <div class="container">
                <h2 class="section-title">
                    <span class="lang-content active" data-lang="sr">Istorija Borbi</span>
                    <span class="lang-content" data-lang="en">Fight History</span>
                </h2>

                <?php if (empty($fights)): ?>
                    <div class="fight-history-empty">
                        <p class="fight-history-empty__title">
                            <span class="lang-content active" data-lang="sr">Nema zabeleženih borbi</span>
                            <span class="lang-content" data-lang="en">No fights recorded</span>
                        </p>
                        <p class="fight-history-empty__note">
                            <span class="lang-content active" data-lang="sr">Borbe će biti dodate kada se održe</span>
                            <span class="lang-content" data-lang="en">Fights will be added after events</span>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="fight-history-list">
                        <?php foreach ($fights as $f):
                            $res = $resultLabels[$f['result'] ?? 'win'] ?? $resultLabels['win'];
                            $method = $methodLabels[$f['method'] ?? ''] ?? null;
                            $embed = ytEmbed($f['youtube_url'] ?? '');
                            $poster = $f['poster_url'] ?? '';
                            if ($poster && substr($poster, 0, 1) === '/') $poster = '..' . $poster;
                            $dateTime = strtotime($f['date'] ?? '');
                            $dateFormatted = $dateTime ? date('d.m.Y', $dateTime) : '';
                        ?>
                        <article class="fight-card fight-card--<?php echo $res['cls']; ?><?php echo !empty($f['is_bif']) ? ' fight-card--bif' : ''; ?>">
                            <div class="fight-card__result-box">
                                <span class="fight-card__result-label">
                                    <span class="lang-content active" data-lang="sr"><?php echo $res['sr']; ?></span>
                                    <span class="lang-content" data-lang="en"><?php echo $res['en']; ?></span>
                                </span>
                                <?php if ($method): ?>
                                <span class="fight-card__result-method">
                                    <span class="lang-content active" data-lang="sr"><?php echo $method['sr']; ?></span>
                                    <span class="lang-content" data-lang="en"><?php echo $method['en']; ?></span>
                                </span>
                                <?php endif; ?>
                            </div>

                            <div class="fight-card__info">
                                <h3 class="fight-card__title">
                                    vs <?php echo htmlspecialchars($f['opponent'] ?? ''); ?>
                                    <?php if (!empty($f['is_bif'])): ?>
                                    <span class="fight-card__bif-badge">BIF</span>
                                    <?php endif; ?>
                                </h3>
                                <p class="fight-card__event"><?php echo htmlspecialchars($f['event'] ?? ''); ?></p>
                                <div class="fight-card__meta">
                                    <?php if ($dateFormatted): ?>
                                        <span class="fight-meta-item">📅 <?php echo $dateFormatted; ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($f['round'])): ?>
                                        <span class="fight-meta-item">
                                            <span class="lang-content active" data-lang="sr">⏱ <?php echo (int)$f['round']; ?>. runda<?php echo !empty($f['time']) ? ' ('.htmlspecialchars($f['time']).')' : ''; ?></span>
                                            <span class="lang-content" data-lang="en">⏱ Round <?php echo (int)$f['round']; ?><?php echo !empty($f['time']) ? ' ('.htmlspecialchars($f['time']).')' : ''; ?></span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!empty($f['youtube_url'])): ?>
                            <div class="fight-card__action">
                                <a href="<?php echo htmlspecialchars($f['youtube_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary fight-watch-btn">
                                    <span class="lang-content active" data-lang="sr">▶ Gledaj Borbu</span>
                                    <span class="lang-content" data-lang="en">▶ Watch Fight</span>
                                </a>
                            </div>
                            <?php endif; ?>
                        </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="../js/loading-screen.js"></script>
    <script src="../js/main.js"></script>
</body>
</html>
