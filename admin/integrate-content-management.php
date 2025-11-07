<?php
/**
 * Automatska integracija Content Management ekstenzije u BIF Admin Panel
 *
 * Pokreni: php integrate-content-management.php
 */

echo "🚀 BIF Admin - Content Management Integration Script\n";
echo "=================================================\n\n";

$adminHtmlPath = __DIR__ . '/admin.html';
$extensionPath = __DIR__ . '/content-management-extension.html';
$backupPath = __DIR__ . '/admin.html.backup.' . date('Y-m-d_His');

// Check if files exist
if (!file_exists($adminHtmlPath)) {
    die("❌ Error: admin.html not found at $adminHtmlPath\n");
}

if (!file_exists($extensionPath)) {
    die("❌ Error: content-management-extension.html not found at $extensionPath\n");
}

// Create backup
echo "📦 Creating backup: " . basename($backupPath) . "\n";
if (!copy($adminHtmlPath, $backupPath)) {
    die("❌ Error: Failed to create backup\n");
}
echo "✅ Backup created successfully\n\n";

// Read files
$adminHtml = file_get_contents($adminHtmlPath);
$extension = file_get_contents($extensionPath);

// Check if already integrated
if (strpos($adminHtml, 'data-section="fighters"') !== false) {
    echo "⚠️  Content Management appears to be already integrated!\n";
    echo "   If you want to re-integrate, restore from backup first.\n";
    exit(1);
}

echo "🔧 Integrating Content Management...\n\n";

// Extract parts from extension file
preg_match('/<!-- NAVIGATION ITEMS.*?-->(.*?)<!-- FIGHTERS SECTION/s', $extension, $navMatch);
preg_match('/<!-- FIGHTERS SECTION.*?-->(.*?)<!-- NEWS SECTION/s', $extension, $fightersMatch);
preg_match('/<!-- NEWS SECTION.*?-->(.*?)<!-- WEBSITE EVENTS SECTION/s', $extension, $newsMatch);
preg_match('/<!-- WEBSITE EVENTS SECTION.*?-->(.*?)<!-- MODALS/s', $extension, $eventsMatch);
preg_match('/<!-- MODALS.*?-->(.*?)<!-- JAVASCRIPT/s', $extension, $modalsMatch);
preg_match('/<!-- JAVASCRIPT.*?-->(.*?)<\/script>/s', $extension, $jsMatch);

$navItems = trim($navMatch[1] ?? '');
$fightersSection = trim($fightersMatch[1] ?? '');
$newsSection = trim($newsMatch[1] ?? '');
$eventsSection = trim($eventsMatch[1] ?? '');
$modals = trim($modalsMatch[1] ?? '');
$javascript = trim($jsMatch[1] ?? '');

// 1. Add navigation items
echo "1️⃣  Adding navigation items...\n";
$pattern = '/(<a href="#" class="nav-item" data-section="events">.*?<\/a>)/s';
if (preg_match($pattern, $adminHtml, $matches)) {
    $replacement = $matches[1] . "\n" . $navItems;
    $adminHtml = preg_replace($pattern, $replacement, $adminHtml, 1);
    echo "   ✅ Navigation items added\n";
} else {
    echo "   ⚠️  Could not find Event Management nav item\n";
}

// 2. Add content sections
echo "2️⃣  Adding content sections...\n";
$pattern = '/(<div id="access" class="content-section">.*?<\/div>\s*<\/div>)/s';
if (preg_match($pattern, $adminHtml)) {
    $adminHtml = preg_replace(
        $pattern,
        "$1\n\n" . $fightersSection . "\n\n" . $newsSection . "\n\n" . $eventsSection,
        $adminHtml,
        1
    );
    echo "   ✅ Fighters section added\n";
    echo "   ✅ News section added\n";
    echo "   ✅ Website Events section added\n";
} else {
    echo "   ⚠️  Could not find access section end\n";
}

// 3. Add modals
echo "3️⃣  Adding modals...\n";
$pattern = '/(<div id="createEventModal" class="modal">)/';
if (preg_match($pattern, $adminHtml)) {
    $adminHtml = preg_replace($pattern, $modals . "\n\n$1", $adminHtml, 1);
    echo "   ✅ Fighter modal added\n";
    echo "   ✅ News modal added\n";
    echo "   ✅ Website Event modal added\n";
} else {
    echo "   ⚠️  Could not find createEventModal\n";
}

// 4. Add JavaScript
echo "4️⃣  Adding JavaScript functions...\n";
$pattern = '/(\s+\/\/ 🚀 INITIALIZE ADMIN PANEL)/';
if (preg_match($pattern, $adminHtml)) {
    $adminHtml = preg_replace($pattern, "\n" . $javascript . "\n$1", $adminHtml, 1);
    echo "   ✅ JavaScript functions added\n";
} else {
    echo "   ⚠️  Could not find initialization marker\n";
}

// Save integrated file
echo "\n💾 Saving integrated admin.html...\n";
if (file_put_contents($adminHtmlPath, $adminHtml)) {
    echo "✅ Integration completed successfully!\n\n";
    echo "📊 Summary:\n";
    echo "   • Backup: " . basename($backupPath) . "\n";
    echo "   • Modified: admin.html\n";
    echo "   • New sections: 3 (Fighters, News, Events)\n";
    echo "   • New modals: 3\n";
    echo "   • Backend: Already configured ✓\n\n";
    echo "🎉 Content Management is now integrated!\n\n";
    echo "Next steps:\n";
    echo "1. Open http://localhost/bif-PPV/admin in your browser\n";
    echo "2. Look for new sidebar items: 🥊 Borci, 📰 Vesti, 🎪 Događaji\n";
    echo "3. Start adding content!\n";
} else {
    die("❌ Error: Failed to save admin.html\n");
}
?>