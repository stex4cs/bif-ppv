<?php
/**
 * Konvertuje postojeći loš format vesti u novi čist format
 * Izvlači SR i EN sadržaj iz HTML-a i pravi odvojene properties
 */

$newsFile = dirname(__DIR__) . '/data/news.json';
$backupFile = dirname(__DIR__) . '/data/news_backup_' . date('Y-m-d_H-i-s') . '.json';

// Backup existing file
if (file_exists($newsFile)) {
    copy($newsFile, $backupFile);
    echo "✅ Backup created: $backupFile\n\n";
}

// Load existing news
$newsData = file_get_contents($newsFile);
$allNews = json_decode($newsData, true);

if (!$allNews) {
    die("❌ Error loading news.json\n");
}

echo "Found " . count($allNews) . " news articles\n\n";

// Function to extract clean text from lang-content tags
function extractLangContent($html, $lang) {
    // Match <div class="lang-content active" data-lang="sr"> or <span class="lang-content" data-lang="en">
    $pattern = '/<(?:div|span)[^>]*class="lang-content[^"]*"[^>]*data-lang="' . preg_quote($lang, '/') . '"[^>]*>(.*?)<\/(?:div|span)>/s';

    if (preg_match($pattern, $html, $matches)) {
        $content = $matches[1];
        // Remove nested lang-content tags
        $content = preg_replace('/<(?:div|span)[^>]*class="lang-content[^"]*"[^>]*>.*?<\/(?:div|span)>/s', '', $content);
        // Clean up extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);
        return $content;
    }

    return '';
}

// Function to extract first title from content
function extractTitle($content, $existingTitle) {
    // Try to extract from <h1 class="article-title">
    if (preg_match('/<h1[^>]*class="article-title"[^>]*>(.*?)<\/h1>/s', $content, $matches)) {
        $titleContent = $matches[1];
        return trim(strip_tags($titleContent));
    }
    return $existingTitle;
}

// Convert each news article
$convertedNews = [];

foreach ($allNews as $news) {
    echo "Processing: " . ($news['title'] ?? 'No title') . "\n";

    $content = $news['content'] ?? '';

    // Extract SR and EN content separately
    $contentSr = extractLangContent($content, 'sr');
    $contentEn = extractLangContent($content, 'en');

    // Extract titles
    $titleSr = $news['title'] ?? '';
    $titleEn = $titleSr; // Will try to extract from content

    // Try to get EN title from content
    if (preg_match('/<span[^>]*class="lang-content"[^>]*data-lang="en"[^>]*>([^<]+)<\/span>/s', $content, $match)) {
        $titleEn = trim($match[1]);
    }

    // Clean up content - remove excessive HTML tags but keep structure
    $contentSr = preg_replace('/<(?:div|span)[^>]*class="[^"]*"[^>]*>/', '', $contentSr);
    $contentSr = str_replace(['</div>', '</span>'], '', $contentSr);
    $contentSr = trim($contentSr);

    $contentEn = preg_replace('/<(?:div|span)[^>]*class="[^"]*"[^>]*>/', '', $contentEn);
    $contentEn = str_replace(['</div>', '</span>'], '', $contentEn);
    $contentEn = trim($contentEn);

    // If we couldn't extract clean content, keep the original
    if (empty($contentSr) && empty($contentEn)) {
        $contentSr = $content;
        $contentEn = $content;
    }

    $convertedNews[] = [
        'id' => $news['id'] ?? 'news_' . uniqid(),
        'title_sr' => $titleSr,
        'title_en' => $titleEn,
        'slug' => $news['slug'] ?? '',
        'excerpt_sr' => $news['excerpt'] ?? '',
        'excerpt_en' => $news['excerpt'] ?? '', // Same for now
        'content_sr' => $contentSr,
        'content_en' => $contentEn,
        'image_url' => $news['image_url'] ?? 'assets/images/news/news-1.png',
        'category' => $news['category'] ?? 'news',
        'status' => $news['status'] ?? 'published',
        'published_at' => $news['published_at'] ?? $news['created_at'] ?? date('Y-m-d H:i:s'),
        'created_at' => $news['created_at'] ?? date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    echo "  ✅ Converted\n";
}

// Save converted news
$jsonOutput = json_encode($convertedNews, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
file_put_contents($newsFile, $jsonOutput);

echo "\n✅ Successfully converted " . count($convertedNews) . " news articles\n";
echo "Original file backed up to: $backupFile\n";
echo "New format saved to: $newsFile\n";
