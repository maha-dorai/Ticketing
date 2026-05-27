<?php
/**
 * Affiche les 30 dernières lignes du log Laravel
 * Usage : php tail_log.php
 */
$log = __DIR__ . '/storage/logs/laravel.log';
if (!file_exists($log)) { echo "Log introuvable.\n"; exit; }

$lines = array_slice(explode("\n", file_get_contents($log)), -60);
foreach ($lines as $line) {
    if (trim($line) === '') continue;
    // Highlight erreurs
    if (stripos($line, 'error') !== false || stripos($line, 'exception') !== false || stripos($line, 'AIService') !== false) {
        echo ">>> " . $line . "\n";
    } else {
        echo "    " . $line . "\n";
    }
}
