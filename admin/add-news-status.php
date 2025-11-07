<?php
/**
 * Add status field to news (published/draft)
 */

$newsFile = dirname(__DIR__) . '/data/news.json';

// Load news
$news = json_decode(file_get_contents($newsFile), true);

echo "Adding status field to news...\n\n";

foreach ($news as &$item) {
    // Add status if not exists (default to published)
    if (!isset($item['status'])) {
        $item['status'] = 'published';
        echo "✅ Added status 'published' to: {$item['title']}\n";
    }
}

// Save
file_put_contents($newsFile, json_encode($news, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "\n✅ All news updated with status!\n";
