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

// Meta podaci - SEO optimized, fully dynamic per-fighter
$hasBifFights = ($wins + $losses + $draws) > 0;
$hasOverallFights = ($overallWins + $overallLosses + $overallDraws) > 0;
$bifRecord = $wins . '-' . $losses . ($draws > 0 ? '-' . $draws : '');
$overallRecord = $overallWins . '-' . $overallLosses . ($overallDraws > 0 ? '-' . $overallDraws : '');
$nicknameStr = $nickname ? ' "' . $nickname . '"' : '';

// TITLE — dynamic by fighter state
if ($hasBifFights) {
    $metaTitle = $name . $nicknameStr . ' — BIF Borac | ' . $weightClass['sr'] . ' | BIF Rekord ' . $bifRecord;
} elseif ($hasOverallFights) {
    $metaTitle = $name . $nicknameStr . ' — BIF Borac | ' . $weightClass['sr'] . ' | Karijera ' . $overallRecord;
} else {
    $metaTitle = $name . $nicknameStr . ' — BIF Borac | ' . $weightClass['sr'];
}

// DESCRIPTION — prefer admin-set bio, fall back to smart auto-generated copy
$customBio = trim(strip_tags($fighter['bio'] ?? ''));
if ($customBio !== '') {
    // Use the fighter's bio if admin filled it in
    $metaDescription = mb_substr($customBio, 0, 157) . (mb_strlen($customBio) > 157 ? '…' : '');
} else {
    $parts = [];
    $parts[] = 'Profil BIF borca ' . $name . $nicknameStr;
    $parts[] = 'kategorija ' . $weightClass['sr'];
    if ($hasBifFights) {
        $parts[] = 'BIF rekord ' . $bifRecord;
    }
    if ($hasOverallFights) {
        $kosTxt = $overallKo > 0 ? ' (' . $overallKo . ' KO)' : '';
        $parts[] = 'Karijera ' . $overallRecord . $kosTxt;
    }
    if (!$hasBifFights && !$hasOverallFights) {
        $parts[] = 'novi član BIF rostera';
    }
    if ($height > 0 && $weight > 0) {
        $parts[] = $height . ' cm / ' . $weight . ' kg';
    }
    $metaDescription = implode(' · ', $parts) . '.';
    $metaDescription = mb_substr($metaDescription, 0, 160);
}
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

    <?php include dirname(__DIR__) . '/includes/google-analytics.php'; ?>
    <script src="/js/ticket-tracker.js" defer></script>

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
    // Schema.org sameAs — verified social profiles for Knowledge Graph
    $sameAs = array_filter([
        $fighter['instagram_url'] ?? '',
        $fighter['tiktok_url'] ?? '',
        $fighter['youtube_url'] ?? '',
        $fighter['twitter_url'] ?? '',
        $fighter['facebook_url'] ?? '',
        $fighter['website_url'] ?? '',
    ]);
    if (!empty($sameAs)) $personSchema['sameAs'] = array_values($sameAs);
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
                                <?php if ($age > 0): ?><span class="fighter-age"><?php echo $age; ?></span><?php endif; ?>
                            </h1>
                            <?php if ($nickname): ?>
                            <p class="fighter-nickname">"<?php echo $nickname; ?>"</p>
                            <?php endif; ?>
                            <p class="fighter-category-details">
                                <span class="lang-content active" data-lang="sr"><?php echo $weightClass['sr']; ?></span>
                                <span class="lang-content" data-lang="en"><?php echo $weightClass['en']; ?></span>
                            </p>

                            <?php
                            $socials = [
                                'instagram' => $fighter['instagram_url'] ?? '',
                                'tiktok'    => $fighter['tiktok_url']    ?? '',
                                'youtube'   => $fighter['youtube_url']   ?? '',
                                'twitter'   => $fighter['twitter_url']   ?? '',
                                'facebook'  => $fighter['facebook_url']  ?? '',
                                'website'   => $fighter['website_url']   ?? '',
                            ];
                            $hasSocials = false;
                            foreach ($socials as $u) { if (trim($u)) { $hasSocials = true; break; } }
                            ?>
                            <?php if ($hasSocials): ?>
                            <div class="fighter-socials">
                                <?php if (!empty(trim($socials['instagram']))): ?>
                                <a href="<?php echo htmlspecialchars($socials['instagram']); ?>" target="_blank" rel="noopener me" class="fighter-social fighter-social--instagram" aria-label="Instagram">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                </a>
                                <?php endif; ?>
                                <?php if (!empty(trim($socials['tiktok']))): ?>
                                <a href="<?php echo htmlspecialchars($socials['tiktok']); ?>" target="_blank" rel="noopener me" class="fighter-social fighter-social--tiktok" aria-label="TikTok">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                                </a>
                                <?php endif; ?>
                                <?php if (!empty(trim($socials['youtube']))): ?>
                                <a href="<?php echo htmlspecialchars($socials['youtube']); ?>" target="_blank" rel="noopener me" class="fighter-social fighter-social--youtube" aria-label="YouTube">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                </a>
                                <?php endif; ?>
                                <?php if (!empty(trim($socials['twitter']))): ?>
                                <a href="<?php echo htmlspecialchars($socials['twitter']); ?>" target="_blank" rel="noopener me" class="fighter-social fighter-social--twitter" aria-label="X (Twitter)">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                </a>
                                <?php endif; ?>
                                <?php if (!empty(trim($socials['facebook']))): ?>
                                <a href="<?php echo htmlspecialchars($socials['facebook']); ?>" target="_blank" rel="noopener me" class="fighter-social fighter-social--facebook" aria-label="Facebook">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </a>
                                <?php endif; ?>
                                <?php if (!empty(trim($socials['website']))): ?>
                                <a href="<?php echo htmlspecialchars($socials['website']); ?>" target="_blank" rel="noopener me" class="fighter-social fighter-social--website" aria-label="Sajt">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
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
        $allFights = $fighter['fights'] ?? [];
        $todayTs = strtotime(date('Y-m-d'));

        // Split into upcoming (date >= today AND no result method) and past
        $upcomingFights = [];
        $fights = [];
        foreach ($allFights as $f) {
            $fts = strtotime($f['date'] ?? '');
            $isFuture = $fts && $fts >= $todayTs;
            $hasResult = !empty($f['method']) || !empty($f['round']);
            if ($isFuture && !$hasResult) {
                $upcomingFights[] = $f;
            } else {
                $fights[] = $f;
            }
        }
        // Past fights: newest first
        usort($fights, function($a, $b) {
            return strtotime($b['date'] ?? '1970-01-01') <=> strtotime($a['date'] ?? '1970-01-01');
        });
        // Upcoming fights: nearest first
        usort($upcomingFights, function($a, $b) {
            return strtotime($a['date'] ?? '9999-12-31') <=> strtotime($b['date'] ?? '9999-12-31');
        });

        // Load events to enrich upcoming cards (poster, ticket URL)
        $eventsFile = dirname(__DIR__) . '/data/website_events.json';
        $allEvents = file_exists($eventsFile) ? (json_decode(file_get_contents($eventsFile), true) ?: []) : [];
        // Build lookup by lower-cased title (sr or en)
        $eventLookup = [];
        foreach ($allEvents as $ev) {
            foreach ([$ev['title_sr'] ?? '', $ev['title_en'] ?? '', $ev['title'] ?? ''] as $t) {
                $t = trim($t);
                if ($t !== '') $eventLookup[mb_strtolower($t)] = $ev;
            }
        }

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

        // Lookup opponent fighter by name (case-insensitive) to get nickname
        $opponentLookup = [];
        foreach ($fighters as $fr) {
            if (!empty($fr['name'])) {
                $opponentLookup[mb_strtolower(trim($fr['name']))] = $fr;
            }
        }
        ?>

        <?php if (!empty($upcomingFights)): ?>
        <section class="upcoming-fights-section section">
            <div class="container">
                <div class="section-heading">
                    <span class="section-eyebrow">
                        <span class="lang-content active" data-lang="sr">DOLAZI USKORO</span>
                        <span class="lang-content" data-lang="en">UP NEXT</span>
                    </span>
                    <h2 class="section-title">
                        <span class="lang-content active" data-lang="sr">Predstojeći Mečevi</span>
                        <span class="lang-content" data-lang="en">Upcoming Matches</span>
                    </h2>
                </div>

                <div class="upcoming-fights-list">
                    <?php foreach ($upcomingFights as $f):
                        $fts = strtotime($f['date'] ?? '');
                        $daysLeft = $fts ? max(0, ceil(($fts - $todayTs) / 86400)) : 0;
                        $dateFormatted = $fts ? date('d.m.Y', $fts) : '';
                        $eventKey = mb_strtolower(trim($f['event'] ?? ''));
                        $matchedEvent = $eventLookup[$eventKey] ?? null;
                        $poster = $matchedEvent['image_url'] ?? ($f['poster_url'] ?? '');
                        if ($poster && substr($poster, 0, 1) === '/') $poster = '..' . $poster;
                        $ticketUrl = $matchedEvent['ticket_url'] ?? '';
                        // Smart fallback: if event has no ticket_url but mentions BIF 2, use the live ticket URL
                        if (empty($ticketUrl)) {
                            $eventName = $f['event'] ?? '';
                            if (stripos($eventName, 'BIF 2') !== false) {
                                $ticketUrl = 'https://ticketing.sajam.rs/catalog/dogadjaj/bif_2_46';
                            }
                        }
                        $eventTimeStr = $matchedEvent['time'] ?? '';
                        $eventLocSr = $matchedEvent['location_sr'] ?? $matchedEvent['location'] ?? '';
                        $eventLocEn = $matchedEvent['location_en'] ?? $matchedEvent['location'] ?? '';
                    ?>
                    <article class="upcoming-fight-card">
                        <?php if ($poster): ?>
                        <div class="upcoming-fight-card__poster" style="background-image:url('<?php echo htmlspecialchars($poster); ?>');"></div>
                        <?php endif; ?>
                        <div class="upcoming-fight-card__overlay"></div>

                        <div class="upcoming-fight-card__body">
                            <div class="upcoming-fight-card__date-block">
                                <div class="upcoming-fight-card__countdown">
                                    <span class="countdown-num"><?php echo $daysLeft; ?></span>
                                    <span class="countdown-label">
                                        <span class="lang-content active" data-lang="sr"><?php echo $daysLeft === 1 ? 'DAN' : 'DANA'; ?></span>
                                        <span class="lang-content" data-lang="en"><?php echo $daysLeft === 1 ? 'DAY' : 'DAYS'; ?></span>
                                    </span>
                                </div>
                                <div class="upcoming-fight-card__date"><?php echo htmlspecialchars($dateFormatted); ?><?php echo $eventTimeStr ? ' · ' . htmlspecialchars($eventTimeStr) : ''; ?></div>
                            </div>

                            <?php
                                $oppList = (!empty($f['opponents']) && is_array($f['opponents']))
                                    ? $f['opponents']
                                    : (!empty($f['opponent']) ? [$f['opponent']] : []);
                                $isHandicap = !empty($f['is_handicap']) || count($oppList) > 1;
                                $matchType = $f['match_type'] ?? (count($oppList) === 3 ? '3v1' : (count($oppList) === 2 ? '2v1' : 'standard'));
                            ?>
                            <div class="upcoming-fight-card__center">
                                <?php if ($isHandicap): ?>
                                <span class="upcoming-fight-card__handicap-badge">⚔ HENDIKEP <?php echo strtoupper($matchType); ?></span>
                                <?php endif; ?>
                                <span class="upcoming-fight-card__vs">VS</span>
                                <h3 class="upcoming-fight-card__opponent">
                                    <?php
                                    $parts = [];
                                    foreach ($oppList as $oppName) {
                                        $oppName = trim($oppName);
                                        $oppData = $opponentLookup[mb_strtolower($oppName)] ?? null;
                                        if (!empty($oppData['slug'])) {
                                            $parts[] = '<a href="' . htmlspecialchars($oppData['slug']) . '" class="upcoming-fight-card__opponent-link">' . htmlspecialchars($oppName) . '</a>';
                                        } else {
                                            $parts[] = htmlspecialchars($oppName);
                                        }
                                    }
                                    echo implode('<span class="upcoming-fight-card__plus"> + </span>', $parts);
                                    ?>
                                </h3>
                                <?php if (!$isHandicap):
                                    $solo = trim($oppList[0] ?? '');
                                    $soloData = $opponentLookup[mb_strtolower($solo)] ?? null;
                                    $soloNickname = $soloData['nickname'] ?? '';
                                ?>
                                    <?php if ($soloNickname): ?>
                                    <p class="upcoming-fight-card__opponent-nickname">"<?php echo htmlspecialchars(strtoupper($soloNickname)); ?>"</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <p class="upcoming-fight-card__event">🥊 <?php echo htmlspecialchars($f['event'] ?? ''); ?></p>
                                <?php if ($eventLocSr || $eventLocEn): ?>
                                <p class="upcoming-fight-card__location">
                                    <span class="lang-content active" data-lang="sr">📍 <?php echo htmlspecialchars($eventLocSr); ?></span>
                                    <span class="lang-content" data-lang="en">📍 <?php echo htmlspecialchars($eventLocEn); ?></span>
                                </p>
                                <?php endif; ?>
                            </div>

                            <div class="upcoming-fight-card__action">
                                <a href="<?php echo htmlspecialchars($ticketUrl ?: '#'); ?>"<?php echo $ticketUrl ? ' target="_blank" rel="noopener"' : ''; ?> class="btn btn-primary upcoming-tickets-btn" data-ticket-source="upcoming_match_<?php echo htmlspecialchars($slug); ?>">
                                    <span class="lang-content active" data-lang="sr">🎟 Kupi Ulaznice</span>
                                    <span class="lang-content" data-lang="en">🎟 Buy Tickets</span>
                                </a>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

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

                            <?php
                                $histOppList = (!empty($f['opponents']) && is_array($f['opponents']))
                                    ? $f['opponents']
                                    : (!empty($f['opponent']) ? [$f['opponent']] : []);
                                $histIsHandicap = !empty($f['is_handicap']) || count($histOppList) > 1;
                                $histMatchType = $f['match_type'] ?? (count($histOppList) === 3 ? '3v1' : (count($histOppList) === 2 ? '2v1' : 'standard'));
                                $histParts = [];
                                $histSoloNickname = '';
                                foreach ($histOppList as $oN) {
                                    $oN = trim($oN);
                                    $oD = $opponentLookup[mb_strtolower($oN)] ?? null;
                                    $oS = $oD['slug'] ?? '';
                                    if ($oS) {
                                        $histParts[] = '<a href="' . htmlspecialchars($oS) . '" style="color:inherit;text-decoration:none;border-bottom:1px solid currentColor;">' . htmlspecialchars($oN) . '</a>';
                                    } else {
                                        $histParts[] = htmlspecialchars($oN);
                                    }
                                    if (!$histIsHandicap && !empty($oD['nickname'])) $histSoloNickname = $oD['nickname'];
                                }
                            ?>
                            <div class="fight-card__info">
                                <h3 class="fight-card__title">
                                    vs <?php echo implode(' <span style="color:var(--primary-red);">+</span> ', $histParts); ?>
                                    <?php if ($histSoloNickname && !$histIsHandicap): ?>
                                        <span style="color:#ffd700;font-style:italic;font-size:0.85em;font-weight:500;letter-spacing:1px;margin-left:0.5rem;">"<?php echo htmlspecialchars(strtoupper($histSoloNickname)); ?>"</span>
                                    <?php endif; ?>
                                    <?php if ($histIsHandicap): ?>
                                        <span class="fight-card__handicap-badge">⚔ HENDIKEP <?php echo strtoupper($histMatchType); ?></span>
                                    <?php endif; ?>
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
