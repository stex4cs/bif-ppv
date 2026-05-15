<?php
/**
 * BIF data path resolver
 *
 * Resolves the absolute directory where dynamic data (fighters.json,
 * news.json, hero_settings.json, etc.) is stored.
 *
 * - If env var BIF_DATA_PATH is set (recommended on Hostinger), use it.
 *   This lets you place data OUTSIDE of public_html so Hostinger Git
 *   Deploy can never touch / overwrite / delete it.
 *
 * - Otherwise fallback to /<project>/data (works locally on XAMPP).
 *
 * Usage:
 *   require_once __DIR__ . '/path/to/includes/data-path.php';
 *   $file = bif_data_path('fighters.json');
 *   $contents = file_get_contents($file);
 */

if (!function_exists('bif_data_dir')) {
    function bif_data_dir(): string {
        static $resolved = null;
        if ($resolved !== null) return $resolved;

        $envPath = getenv('BIF_DATA_PATH');
        if ($envPath !== false && trim($envPath) !== '') {
            $resolved = rtrim(trim($envPath), '/\\');
            if (!is_dir($resolved)) {
                @mkdir($resolved, 0755, true);
            }
            return $resolved;
        }

        // Fallback: <project root>/data
        // This file lives in /<root>/includes/, so project root = parent of __DIR__
        $resolved = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
        return $resolved;
    }
}

if (!function_exists('bif_data_path')) {
    function bif_data_path(string $filename): string {
        return bif_data_dir() . DIRECTORY_SEPARATOR . ltrim($filename, '/\\');
    }
}
