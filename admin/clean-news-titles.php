<?php
/**
 * Clean HTML tags from news titles
 */

$newsFile = dirname(__DIR__) . '/data/news.json';

// Load news
$news = json_decode(file_get_contents($newsFile), true);

echo "Cleaning news titles...\n\n";

foreach ($news as &$item) {
    $oldTitle = $item['title'];

    // Extract Serbian text from lang-content span
    if (preg_match('/<span[^>]*data-lang="sr"[^>]*>([^<]+)<\/span>/i', $oldTitle, $matches)) {
        $item['title'] = trim($matches[1]);
    } else {
        // Fallback: strip all tags
        $item['title'] = trim(strip_tags($oldTitle));
    }

    // Clean up extra whitespace
    $item['title'] = preg_replace('/\s+/', ' ', $item['title']);

    echo "✅ Cleaned: {$item['slug']}\n";
    echo "   Old: " . substr($oldTitle, 0, 80) . "...\n";
    echo "   New: {$item['title']}\n\n";
}

// Save
file_put_contents($newsFile, json_encode($news, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ All titles cleaned!\n";
