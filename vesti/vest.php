<?php
/**
 * Dinamička stranica za prikaz vesti
 * Učitava podatke iz news.json na osnovu slug-a
 */

// Učitaj slug iz URL-a
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header('Location: ../index.php#news');
    exit;
}

// Učitaj news.json
$newsFile = dirname(__DIR__) . '/data/news.json';
if (!file_exists($newsFile)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 - News not found</h1></body></html>';
    exit;
}

$newsData = file_get_contents($newsFile);
$allNews = json_decode($newsData, true);

if (!$allNews) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>Error</title></head><body><h1>Error loading data</h1></body></html>';
    exit;
}

// Pronađi vest sa ovim slug-om
$news = null;
foreach ($allNews as $n) {
    if (isset($n['slug']) && $n['slug'] === $slug) {
        $news = $n;
        break;
    }
}

// Ako vest nije pronađena
if (!$news) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 - News article not found</h1><p>Vest sa slug-om "' . htmlspecialchars($slug) . '" nije pronađena.</p><a href="../index.php#news">Nazad na vesti</a></body></html>';
    exit;
}

// Prip remi podatke za prikaz
$titleSr = $news['title_sr'] ?? $news['title'] ?? 'Bez naslova';
$titleEn = $news['title_en'] ?? $news['title'] ?? 'No title';
$excerptSr = $news['excerpt_sr'] ?? $news['excerpt'] ?? '';
$excerptEn = $news['excerpt_en'] ?? $news['excerpt'] ?? '';
$contentSr = $news['content_sr'] ?? $news['content'] ?? '';
$contentEn = $news['content_en'] ?? $news['content'] ?? '';
$imageUrl = $news['image_url'] ?? '/assets/images/news/default.png';
$category = $news['category'] ?? 'news';
$publishedAt = $news['published_at'] ?? $news['created_at'] ?? '';

// Obradi image URL
$imageUrl = str_replace('\\/', '/', $imageUrl);
if (substr($imageUrl, 0, 1) === '/') {
    $imageUrl = '..' . $imageUrl;
} elseif (substr($imageUrl, 0, 6) !== 'http://' && substr($imageUrl, 0, 7) !== 'https://') {
    $imageUrl = '../' . $imageUrl;
}
// Cache busting - dodaj timestamp fajla da browser uvek ucita novu sliku
$absPath = dirname(__DIR__) . '/' . ltrim($news['image_url'] ?? '', '/');
$imageUrl .= '?v=' . (file_exists($absPath) ? filemtime($absPath) : time());

// Formatiraj datum
$dateTime = strtotime($publishedAt);
$dateSr = $dateTime ? date('j. M Y', $dateTime) : '';
$dateEn = $dateTime ? date('F j, Y', $dateTime) : '';

// Month names translation
$months = [
    'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
    'May' => 'Maj', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Avg',
    'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Dec'
];
foreach ($months as $en => $sr) {
    $dateSr = str_replace($en, $sr, $dateSr);
}

// Category labels
$categoryLabels = [
    'news' => ['sr' => 'VESTI', 'en' => 'NEWS'],
    'events' => ['sr' => 'DOGAĐAJI', 'en' => 'EVENTS'],
    'fighters' => ['sr' => 'BORCI', 'en' => 'FIGHTERS'],
    'about' => ['sr' => 'O NAMA', 'en' => 'ABOUT US']
];
$categoryLabel = $categoryLabels[$category] ?? ['sr' => 'VESTI', 'en' => 'NEWS'];

// Meta podaci
$metaTitle = $titleSr . ' | BIF - Balkan Influence Fighting';
$metaDescription = $excerptSr ?: mb_substr(strip_tags($contentSr), 0, 160);
$metaDescription = mb_substr($metaDescription, 0, 160);
$pageUrl = 'https://bif.events/vesti/' . $slug;

// Absolute image URL for SEO/social (schema.org requires absolute URLs)
$absImageUrl = $news['image_url'] ?? '/assets/images/news/default.png';
$absImageUrl = str_replace('\\/', '/', $absImageUrl);
if (substr($absImageUrl, 0, 4) !== 'http') {
    $absImageUrl = 'https://bif.events' . (substr($absImageUrl, 0, 1) === '/' ? '' : '/') . $absImageUrl;
}
$modifiedAt = $news['updated_at'] ?? $publishedAt;

// Process content - basic HTML support
function processContent($content) {
    // If content looks like markdown, convert it
    // For now, just ensure proper paragraph breaks
    $content = nl2br($content);
    return $content;
}

$contentSr = processContent($contentSr);
$contentEn = processContent($contentEn);

// Get related news (other published news, max 2)
$relatedNews = [];
foreach ($allNews as $n) {
    if ($n['slug'] !== $slug && isset($n['status']) && $n['status'] === 'published') {
        $relatedNews[] = $n;
        if (count($relatedNews) >= 2) break;
    }
}
?><!DOCTYPE html>
<html lang="sr">
<head>
    <script>
        (function() {
            var savedTheme = localStorage.getItem('bif-theme');
            var theme = savedTheme || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.style.colorScheme = theme;
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="author" content="BIF - Balkan Influence Fighting">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="<?php echo $pageUrl; ?>">

    <!-- Open Graph Meta Tags -->
    <meta property="og:site_name" content="BIF - Balkan Influence Fighting">
    <meta property="og:locale" content="sr_RS">
    <meta property="og:locale:alternate" content="en_US">
    <meta property="og:title" content="<?php echo htmlspecialchars($titleSr); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo $pageUrl; ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($absImageUrl); ?>">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($titleSr); ?>">
    <meta property="article:published_time" content="<?php echo htmlspecialchars($publishedAt); ?>">
    <meta property="article:modified_time" content="<?php echo htmlspecialchars($modifiedAt); ?>">
    <meta property="article:section" content="<?php echo htmlspecialchars($categoryLabel['sr']); ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($titleSr); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($absImageUrl); ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/news.css">
    <link rel="stylesheet" href="../css/modern-design.css">

    <meta name="theme-color" content="#c41e3a">

    <title><?php echo htmlspecialchars($metaTitle); ?></title>

    <script type="application/ld+json">
    <?php
    $articleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $pageUrl],
        'headline' => $titleSr,
        'description' => $metaDescription,
        'image' => [$absImageUrl],
        'datePublished' => $publishedAt,
        'dateModified' => $modifiedAt,
        'inLanguage' => 'sr-RS',
        'url' => $pageUrl,
        'articleSection' => $categoryLabel['sr'],
        'author' => [
            '@type' => 'Organization',
            'name' => 'BIF - Balkan Influence Fighting',
            'url' => 'https://bif.events',
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'BIF - Balkan Influence Fighting',
            'url' => 'https://bif.events',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => 'https://bif.events/assets/images/logo/biflogo.png',
            ],
        ],
    ];
    echo json_encode($articleSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    ?>
    </script>

    <!-- Breadcrumb Schema -->
    <script type="application/ld+json">
    <?php
    $newsBreadcrumbs = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Početna', 'item' => 'https://bif.events/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Vesti', 'item' => 'https://bif.events/#news'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $titleSr, 'item' => $pageUrl],
        ],
    ];
    echo json_encode($newsBreadcrumbs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    ?>
    </script>
</head>
<body class="news-page-body">
    <a href="#main-content" class="sr-only">Skip to main content</a>

    <!-- Header -->
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
                            <span class="lang-content active" data-lang="sr"><?php echo htmlspecialchars($titleSr); ?></span>
                            <span class="lang-content" data-lang="en"><?php echo htmlspecialchars($titleEn); ?></span>
                        </li>
                    </ol>
                </nav>
            </div>
        </section>

        <!-- Main Article -->
        <article class="news-article-section section">
            <div class="container">
                <div class="news-article">
                    <!-- Article Header -->
                    <header class="article-header">
                        <div class="article-meta">
                            <time class="article-date" datetime="<?php echo htmlspecialchars($publishedAt); ?>">
                                <span class="lang-content active" data-lang="sr"><?php echo $dateSr; ?></span>
                                <span class="lang-content" data-lang="en"><?php echo $dateEn; ?></span>
                            </time>
                            <span class="article-category">
                                <span class="lang-content active" data-lang="sr"><?php echo $categoryLabel['sr']; ?></span>
                                <span class="lang-content" data-lang="en"><?php echo $categoryLabel['en']; ?></span>
                            </span>
                        </div>

                        <h1 class="article-title">
                            <span class="lang-content active" data-lang="sr"><?php echo htmlspecialchars($titleSr); ?></span>
                            <span class="lang-content" data-lang="en"><?php echo htmlspecialchars($titleEn); ?></span>
                        </h1>

                        <?php if ($excerptSr || $excerptEn): ?>
                        <p class="article-excerpt">
                            <span class="lang-content active" data-lang="sr"><?php echo htmlspecialchars($excerptSr); ?></span>
                            <span class="lang-content" data-lang="en"><?php echo htmlspecialchars($excerptEn); ?></span>
                        </p>
                        <?php endif; ?>
                    </header>

                    <!-- Featured Image -->
                    <div class="article-image">
                        <img src="<?php echo htmlspecialchars($imageUrl); ?>"
                             alt="<?php echo htmlspecialchars($titleSr); ?>"
                             loading="lazy">
                    </div>

                    <!-- Article Content -->
                    <div class="article-content">
                        <div class="lang-content active" data-lang="sr">
                            <?php echo $contentSr; ?>
                        </div>

                        <div class="lang-content" data-lang="en">
                            <?php echo $contentEn; ?>
                        </div>
                    </div>

                    <!-- Article Footer -->
                    <footer class="article-footer">
                        <div class="article-share">
                            <span class="share-label">
                                <span class="lang-content active" data-lang="sr">Podeli:</span>
                                <span class="lang-content" data-lang="en">Share:</span>
                            </span>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($pageUrl); ?>" target="_blank" rel="noopener" class="share-link facebook" aria-label="Share on Facebook">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($pageUrl); ?>&text=<?php echo urlencode($titleSr); ?>" target="_blank" rel="noopener" class="share-link twitter" aria-label="Share on X">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                </svg>
                            </a>
                        </div>
                    </footer>
                </div>

                <!-- Related News -->
                <?php if (count($relatedNews) > 0): ?>
                <section class="related-news">
                    <h2 class="related-title">
                        <span class="lang-content active" data-lang="sr">Povezane Vesti</span>
                        <span class="lang-content" data-lang="en">Related News</span>
                    </h2>

                    <div class="related-grid">
                        <?php foreach ($relatedNews as $related):
                            $relImgUrl = str_replace('\\/', '/', $related['image_url'] ?? '/assets/images/news/default.png');
                            if (substr($relImgUrl, 0, 1) !== '/' && substr($relImgUrl, 0, 4) !== 'http') {
                                $relImgUrl = '../' . $relImgUrl;
                            } elseif (substr($relImgUrl, 0, 1) === '/') {
                                $relImgUrl = '..' . $relImgUrl;
                            }
                            $relTitleSr = $related['title_sr'] ?? $related['title'] ?? 'Bez naslova';
                            $relTitleEn = $related['title_en'] ?? $related['title'] ?? 'No title';
                            $relDate = strtotime($related['published_at'] ?? $related['created_at'] ?? '');
                            $relDateStr = $relDate ? date('j. M Y', $relDate) : '';
                        ?>
                        <article class="related-card">
                            <div class="related-image">
                                <img src="<?php echo htmlspecialchars($relImgUrl); ?>" alt="<?php echo htmlspecialchars($relTitleSr); ?>" loading="lazy">
                            </div>
                            <div class="related-content">
                                <time class="related-date"><?php echo $relDateStr; ?></time>
                                <h3><a href="<?php echo htmlspecialchars($related['slug']); ?>">
                                    <span class="lang-content active" data-lang="sr"><?php echo htmlspecialchars($relTitleSr); ?></span>
                                    <span class="lang-content" data-lang="en"><?php echo htmlspecialchars($relTitleEn); ?></span>
                                </a></h3>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
            </div>
        </article>
    </main>

    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="../js/main.js"></script>
</body>
</html>
