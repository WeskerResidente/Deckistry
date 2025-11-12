<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    'navigation' => [
        'path' => './assets/js/navigation.js',
        'entrypoint' => true,
    ],
    'settings' => [
        'path' => './assets/js/settings.js',
        'entrypoint' => true,
    ],
    'register' => [
        'path' => './assets/js/register.js',
        'entrypoint' => true,
    ],
    'password-reset' => [
        'path' => './assets/js/password-reset.js',
        'entrypoint' => true,
    ],
    'search' => [
        'path' => './assets/js/search.js',
        'entrypoint' => true,
    ],
    'profile' => [
        'path' => './assets/js/profile.js',
        'entrypoint' => true,
    ],
    'avatar-upload' => [
        'path' => './assets/js/avatar-upload.js',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
];
