<?php
/**
 * Fix CSS paths in news HTML files
 */

$vestiFolder = dirname(__DIR__) . '/vesti';
$files = glob($vestiFolder . '/*.html');

echo "Fixing CSS paths in news files...\n\n";

foreach ($files as $file) {
    $filename = basename($file);
    $html = file_get_contents($file);

    // Replace CSS paths
    $html = str_replace('href="css/', 'href="../css/', $html);

    // Replace asset paths
    $html = str_replace('src="assets/', 'src="../assets/', $html);

    // Replace favicon paths
    $html = str_replace('href="/favicon/', 'href="../favicon/', $html);

    // Fix index.php links
    $html = str_replace('href="index.php', 'href="../index.php', $html);

    file_put_contents($file, $html);

    echo "✅ Fixed: $filename\n";
}

echo "\n✅ All CSS paths fixed!\n";
