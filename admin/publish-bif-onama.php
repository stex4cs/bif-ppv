<?php
$newsFile = __DIR__ . '/../data/news.json';
$news = json_decode(file_get_contents($newsFile), true);

foreach ($news as &$article) {
    if ($article['slug'] === 'bif-onama') {
        $article['status'] = 'published';
        echo "✅ Published: {$article['title']}\n";
    }
}

file_put_contents($newsFile, json_encode($news, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "✅ Done!\n";
