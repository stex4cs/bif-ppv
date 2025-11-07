<?php
/**
 * Update news metadata - add image URLs and excerpts
 */

$newsFile = __DIR__ . '/../data/news.json';

// Load news
$news = json_decode(file_get_contents($newsFile), true);

echo "Updating news metadata...\n\n";

// Default excerpts for each article
$excerpts = [
    'bif-onama' => 'Zaboravite na stare borbe. Zaboravite na dosadne prenose i generičke likove. BIF — Balkan Influence Fighting dolazi da razbije šablone i postavi nove standarde zabave na Balkanu.',
    'bif0' => 'Jul 2025. Upamti taj mesec. Jer tada počinje sve. BIF 0 je naš prvi korak, ali i najava svega što sledi. Predstavljanje našeg pokreta, tima i prvih boraca.',
    'bif1' => 'A sada... ono zbog čega sve ovo radimo. BIF 1 je veliko platno na koje stavljamo najveće influensere Balkana u najluđem, najskupljem i najviralnijem događaju u istoriji regionalnog interneta.',
    'bif15' => 'Balkan Influence Fighting ponosno najavljuje BIF 15, događaj koji će obeležiti ovu godinu. Spektakularni turnir u Štark Areni sa borbom za titulu prvaka.'
];

foreach ($news as &$article) {
    $slug = $article['slug'];

    // Add image URL if missing
    if (empty($article['image_url'])) {
        $article['image_url'] = 'assets/images/news/news-1.png';
        echo "✅ Added image URL to: {$article['title']}\n";
    }

    // Add excerpt if missing
    if (empty($article['excerpt'])) {
        if (isset($excerpts[$slug])) {
            $article['excerpt'] = $excerpts[$slug];
        } else {
            // Extract from content
            $text = strip_tags($article['content']);
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            $article['excerpt'] = mb_substr($text, 0, 200) . '...';
        }
        echo "✅ Added excerpt to: {$article['title']}\n";
    }
}

// Save
file_put_contents($newsFile, json_encode($news, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "\n✅ All news metadata updated!\n";
