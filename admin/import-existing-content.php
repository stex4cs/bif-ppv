<?php
/**
 * Import Existing Content from Website
 * Automatically extracts fighters, news, and events from existing HTML pages
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Paths
$baseDir = dirname(__DIR__);
$dataDir = $baseDir . '/data';
$borciFolder = $baseDir . '/borci';
$newsFolder = $baseDir . '/vesti';
$eventsFolder = $baseDir . '/events'; // Adjust if different

// Data files
$fightersFile = $dataDir . '/fighters.json';
$newsFile = $dataDir . '/news.json';
$eventsFile = $dataDir . '/website_events.json';

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function loadJSON($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

function saveJSON($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function generateSlug($text) {
    // Convert to lowercase
    $slug = mb_strtolower($text, 'UTF-8');

    // Serbian/Cyrillic to Latin transliteration
    $transliteration = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'ђ' => 'dj', 'е' => 'e', 'ж' => 'z', 'з' => 'z', 'и' => 'i',
        'ј' => 'j', 'к' => 'k', 'л' => 'l', 'љ' => 'lj', 'м' => 'm',
        'н' => 'n', 'њ' => 'nj', 'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'ћ' => 'c', 'у' => 'u', 'ф' => 'f',
        'х' => 'h', 'ц' => 'c', 'ч' => 'c', 'џ' => 'dz', 'ш' => 's',
    ];

    // Replace special characters
    $specialChars = [
        'š' => 's', 'đ' => 'dj', 'č' => 'c', 'ć' => 'c', 'ž' => 'z',
        'Š' => 's', 'Đ' => 'dj', 'Č' => 'c', 'Ć' => 'c', 'Ž' => 'z',
    ];

    $slug = strtr($slug, array_merge($transliteration, $specialChars));

    // Remove non-alphanumeric characters (except hyphens and spaces)
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);

    // Replace spaces with hyphens
    $slug = preg_replace('/[\s]+/', '-', $slug);

    // Remove multiple consecutive hyphens
    $slug = preg_replace('/-+/', '-', $slug);

    // Trim hyphens from edges
    $slug = trim($slug, '-');

    return $slug;
}

function extractFromHTML($html, $pattern) {
    if (preg_match($pattern, $html, $matches)) {
        return trim($matches[1]);
    }
    return null;
}

function extractNumber($html, $pattern) {
    if (preg_match($pattern, $html, $matches)) {
        return (int)$matches[1];
    }
    return 0;
}

// ============================================================================
// IMPORT FIGHTERS
// ============================================================================

function importFighters($borciFolder, $fightersFile) {
    echo "🥊 IMPORTING FIGHTERS...\n";
    echo "======================\n\n";

    $existingFighters = loadJSON($fightersFile);
    $existingSlugs = array_column($existingFighters, 'slug');

    $imported = 0;
    $skipped = 0;

    // Scan folder for fighter HTML files
    if (!is_dir($borciFolder)) {
        echo "❌ Folder not found: $borciFolder\n";
        return;
    }

    $files = glob($borciFolder . '/*.html');

    foreach ($files as $file) {
        $filename = basename($file, '.html');
        $slug = $filename; // e.g., "marko-jack"

        // Skip if already exists
        if (in_array($slug, $existingSlugs)) {
            echo "⏭️  Skipped: $slug (already exists)\n";
            $skipped++;
            continue;
        }

        $html = file_get_contents($file);

        // First try to extract from JSON-LD structured data
        $jsonLd = null;
        if (preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches)) {
            $jsonLd = json_decode($matches[1], true);
        }

        // Extract name - multiple patterns
        $name = null;
        if ($jsonLd && isset($jsonLd['name'])) {
            $name = $jsonLd['name'];
        }
        if (!$name) {
            // Pattern: <span class="lang-content active" data-lang="sr">MARKO MILOVANOVIĆ</span>
            $name = extractFromHTML($html, '/<span[^>]*class="[^"]*lang-content[^"]*active[^"]*"[^>]*data-lang="sr"[^>]*>([^<]+)<\/span>[^<]*<span[^>]*class="fighter-age"/s');
        }
        if (!$name) {
            $name = extractFromHTML($html, '/<h1[^>]*>(.*?)<span[^>]*class="fighter-age"/s');
        }

        // Extract nickname
        $nickname = extractFromHTML($html, '/<p[^>]*class="fighter-nickname"[^>]*>"([^"]+)"<\/p>/');
        if (!$nickname && $jsonLd && isset($jsonLd['alternateName'])) {
            $nickname = $jsonLd['alternateName'];
        }

        // Extract bio (if available)
        $bio = extractFromHTML($html, '/<p[^>]*class="fighter-bio"[^>]*>(.*?)<\/p>/s');

        // Extract stats from JSON-LD or HTML
        $weight = null;
        $height = null;
        $age = null;

        if ($jsonLd) {
            if (isset($jsonLd['weight'])) {
                $weight = extractNumber($jsonLd['weight'], '/(\d+)/');
            }
            if (isset($jsonLd['height'])) {
                $height = extractNumber($jsonLd['height'], '/(\d+)/');
            }
        }

        // Extract from HTML: <span class="value">100 KG (220 LBS)</span>
        if (!$weight) {
            $weight = extractNumber($html, '/<span[^>]*class="label"[^>]*>.*?[Tt]ežina.*?<\/span>.*?<span[^>]*class="value"[^>]*>(\d+)\s*KG/s');
        }
        if (!$height) {
            $height = extractNumber($html, '/<span[^>]*class="label"[^>]*>.*?[Vv]isina.*?<\/span>.*?<span[^>]*class="value"[^>]*>(\d+)\s*CM/s');
        }

        // Extract age from: <span class="fighter-age">, 34</span>
        $age = extractNumber($html, '/<span[^>]*class="fighter-age"[^>]*>,\s*(\d+)<\/span>/');

        // Extract record: <p>0-0-0 <span style="font-size: 0.6em; color: var(--gray-400);">(W-L-D)</span></p>
        $record = extractFromHTML($html, '/<p>(\d+-\d+-\d+)\s*<span[^>]*>\(W-L-D\)<\/span><\/p>/');
        if (!$record) {
            // Alternative: BIF SKOR section
            $record = extractFromHTML($html, '/BIF SKOR<\/span>.*?<\/h3>.*?<p>(\d+-\d+-\d+)/s');
        }

        $wins = 0;
        $losses = 0;
        $draws = 0;

        if ($record && preg_match('/(\d+)-(\d+)-(\d+)/', $record, $matches)) {
            $wins = (int)$matches[1];
            $losses = (int)$matches[2];
            $draws = (int)$matches[3];
        }

        // Extract image URL from meta tag or JSON-LD
        $imageUrl = extractFromHTML($html, '/<meta property="og:image" content="([^"]+)"/');
        if (!$imageUrl && $jsonLd && isset($jsonLd['image'])) {
            $imageUrl = $jsonLd['image'];
        }
        if (!$imageUrl) {
            $imageUrl = extractFromHTML($html, '/<img[^>]*class="fighter-profile-image"[^>]*src="([^"]+)"/');
        }

        // Clean up name
        if ($name) {
            $name = trim(strip_tags($name));
            $name = preg_replace('/\s+/', ' ', $name); // Remove extra whitespace
        }

        // If no data extracted, skip
        if (!$name) {
            echo "⚠️  Could not extract name from: $filename\n";
            continue;
        }

        // Create fighter object
        $fighter = [
            'name' => $name ?: 'Unknown Fighter',
            'nickname' => $nickname ?: '',
            'slug' => $slug,
            'weight' => $weight ?: null,
            'height' => $height ?: null,
            'age' => $age ?: null,
            'wins' => $wins,
            'losses' => $losses,
            'draws' => $draws,
            'bio' => $bio ?: '',
            'image_url' => $imageUrl ?: '/assets/images/fighters/default.png',
            'status' => 'active',
            'id' => uniqid('fighter_', true),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $existingFighters[] = $fighter;
        $existingSlugs[] = $slug;
        $imported++;

        echo "✅ Imported: {$fighter['name']} ({$slug}) - Record: $wins-$losses-$draws\n";
    }

    // Save updated fighters
    if ($imported > 0) {
        saveJSON($fightersFile, $existingFighters);
        echo "\n✅ Successfully imported $imported fighters!\n";
    } else {
        echo "\n⏭️  No new fighters to import ($skipped already exist)\n";
    }

    echo "\n";
}

// ============================================================================
// IMPORT NEWS
// ============================================================================

function importNews($newsFolder, $newsFile) {
    echo "📰 IMPORTING NEWS...\n";
    echo "==================\n\n";

    if (!is_dir($newsFolder)) {
        echo "⚠️  News folder not found: $newsFolder\n";
        echo "   Please create news HTML files or adjust the path.\n\n";
        return;
    }

    $existingNews = loadJSON($newsFile);
    $existingSlugs = array_column($existingNews, 'slug');

    $imported = 0;
    $skipped = 0;

    $files = glob($newsFolder . '/*.html');

    foreach ($files as $file) {
        $filename = basename($file, '.html');
        $slug = $filename;

        if (in_array($slug, $existingSlugs)) {
            echo "⏭️  Skipped: $slug (already exists)\n";
            $skipped++;
            continue;
        }

        $html = file_get_contents($file);

        // Extract news data
        $title = extractFromHTML($html, '/<h1[^>]*class="news-title"[^>]*>(.*?)<\/h1>/s');
        if (!$title) {
            $title = extractFromHTML($html, '/<h1[^>]*>(.*?)<\/h1>/s');
        }

        $content = extractFromHTML($html, '/<div[^>]*class="news-content"[^>]*>(.*?)<\/div>/s');
        if (!$content) {
            $content = extractFromHTML($html, '/<article[^>]*>(.*?)<\/article>/s');
        }

        $publishedDate = extractFromHTML($html, '/<time[^>]*datetime="([^"]+)"/');
        if (!$publishedDate) {
            $publishedDate = date('Y-m-d H:i:s');
        }

        if (!$title) {
            echo "⚠️  Could not extract title from: $filename\n";
            continue;
        }

        $newsItem = [
            'title' => $title,
            'slug' => $slug,
            'content' => strip_tags($content ?: ''),
            'id' => uniqid('news_', true),
            'published_at' => $publishedDate,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $existingNews[] = $newsItem;
        $existingSlugs[] = $slug;
        $imported++;

        echo "✅ Imported: {$newsItem['title']}\n";
    }

    if ($imported > 0) {
        saveJSON($newsFile, $existingNews);
        echo "\n✅ Successfully imported $imported news articles!\n";
    } else {
        echo "\n⏭️  No new news to import ($skipped already exist)\n";
    }

    echo "\n";
}

// ============================================================================
// IMPORT EVENTS
// ============================================================================

function importEvents($eventsFolder, $eventsFile) {
    echo "🎪 IMPORTING EVENTS...\n";
    echo "====================\n\n";

    if (!is_dir($eventsFolder)) {
        echo "⚠️  Events folder not found: $eventsFolder\n";
        echo "   Please create event HTML files or adjust the path.\n\n";
        return;
    }

    $existingEvents = loadJSON($eventsFile);
    $existingSlugs = array_column($existingEvents, 'slug');

    $imported = 0;
    $skipped = 0;

    $files = glob($eventsFolder . '/*.html');

    foreach ($files as $file) {
        $filename = basename($file, '.html');
        $slug = $filename;

        if (in_array($slug, $existingSlugs)) {
            echo "⏭️  Skipped: $slug (already exists)\n";
            $skipped++;
            continue;
        }

        $html = file_get_contents($file);

        // Extract event data
        $title = extractFromHTML($html, '/<h1[^>]*class="event-title"[^>]*>(.*?)<\/h1>/s');
        if (!$title) {
            $title = extractFromHTML($html, '/<h1[^>]*>(.*?)<\/h1>/s');
        }

        $description = extractFromHTML($html, '/<p[^>]*class="event-description"[^>]*>(.*?)<\/p>/s');
        $location = extractFromHTML($html, '/<span[^>]*class="event-location"[^>]*>(.*?)<\/span>/s');
        $date = extractFromHTML($html, '/<time[^>]*datetime="([^"]+)"/');

        $imageUrl = extractFromHTML($html, '/<img[^>]*class="event-image"[^>]*src="([^"]+)"/');

        if (!$title) {
            echo "⚠️  Could not extract title from: $filename\n";
            continue;
        }

        $event = [
            'title' => $title,
            'slug' => $slug,
            'description' => $description ?: '',
            'date' => $date ?: date('Y-m-d H:i:s', strtotime('+1 month')),
            'location' => $location ?: 'TBA',
            'image_url' => $imageUrl ?: '/assets/images/events/default.jpg',
            'status' => 'upcoming',
            'id' => uniqid('wevent_', true),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $existingEvents[] = $event;
        $existingSlugs[] = $slug;
        $imported++;

        echo "✅ Imported: {$event['title']}\n";
    }

    if ($imported > 0) {
        saveJSON($eventsFile, $existingEvents);
        echo "\n✅ Successfully imported $imported events!\n";
    } else {
        echo "\n⏭️  No new events to import ($skipped already exist)\n";
    }

    echo "\n";
}

// ============================================================================
// MAIN EXECUTION
// ============================================================================

echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo "   BIF PPV - IMPORT EXISTING CONTENT\n";
echo "═══════════════════════════════════════════════════════\n";
echo "\n";

// Import fighters
importFighters($borciFolder, $fightersFile);

// Import news
importNews($newsFolder, $newsFile);

// Import events
importEvents($eventsFolder, $eventsFile);

echo "═══════════════════════════════════════════════════════\n";
echo "   IMPORT COMPLETE!\n";
echo "═══════════════════════════════════════════════════════\n";
echo "\n";
echo "Next steps:\n";
echo "1. Check the data files in: $dataDir\n";
echo "2. Open admin panel and verify imported content\n";
echo "3. Adjust any missing or incorrect data\n";
echo "\n";
