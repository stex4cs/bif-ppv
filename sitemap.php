<?php
/**
 * Dinamički sitemap.xml za BIF
 * Automatski uključuje sve aktivne borce i objavljene vesti iz data/*.json
 */

header('Content-Type: application/xml; charset=UTF-8');

$base = 'https://bif.events';

// Static URLs
$urls = [
    ['loc' => $base . '/',         'priority' => '1.0', 'changefreq' => 'daily'],
    ['loc' => $base . '/watch',    'priority' => '0.9', 'changefreq' => 'weekly'],
    ['loc' => $base . '/contact',  'priority' => '0.6', 'changefreq' => 'yearly'],
    ['loc' => $base . '/newsletter','priority' => '0.5', 'changefreq' => 'yearly'],
];

// Fighters
$fightersFile = __DIR__ . '/data/fighters.json';
if (file_exists($fightersFile)) {
    $fighters = json_decode(file_get_contents($fightersFile), true) ?: [];
    foreach ($fighters as $f) {
        if (($f['status'] ?? '') !== 'active' || empty($f['slug'])) continue;
        $lastmod = !empty($f['updated_at']) ? date('Y-m-d', strtotime($f['updated_at'])) : date('Y-m-d');
        $urls[] = [
            'loc' => $base . '/borci/' . $f['slug'],
            'lastmod' => $lastmod,
            'priority' => '0.8',
            'changefreq' => 'monthly',
        ];
    }
}

// News
$newsFile = __DIR__ . '/data/news.json';
if (file_exists($newsFile)) {
    $news = json_decode(file_get_contents($newsFile), true) ?: [];
    foreach ($news as $n) {
        if (($n['status'] ?? '') !== 'published' || empty($n['slug'])) continue;
        $lastmod = !empty($n['updated_at'])
            ? date('Y-m-d', strtotime($n['updated_at']))
            : (!empty($n['published_at']) ? date('Y-m-d', strtotime($n['published_at'])) : date('Y-m-d'));
        $urls[] = [
            'loc' => $base . '/vesti/' . $n['slug'],
            'lastmod' => $lastmod,
            'priority' => '0.7',
            'changefreq' => 'weekly',
        ];
    }
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $u): ?>
    <url>
        <loc><?php echo htmlspecialchars($u['loc']); ?></loc>
        <?php if (!empty($u['lastmod'])): ?><lastmod><?php echo $u['lastmod']; ?></lastmod><?php endif; ?>
        <changefreq><?php echo $u['changefreq']; ?></changefreq>
        <priority><?php echo $u['priority']; ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
