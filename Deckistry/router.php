<?php

// Router for PHP built-in server

// Change to public directory
chdir(__DIR__ . '/public');

$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . '/public' . $url;

// Check if the file exists and is not a directory
if (file_exists($file) && !is_dir($file)) {
    // Serve the file directly
    return false;
}

// Otherwise, load Symfony's front controller
require __DIR__ . '/public/index.php';
