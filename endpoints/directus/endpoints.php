<?php

namespace Directus\Custom\Endpoints\Directus;

require __DIR__ . '/controllers/App.php';
require __DIR__ . '/controllers/Mail.php';
require __DIR__ . '/controllers/Metadata.php';

return [
    '/app' => [
        'method' => 'GET',
        'handler' => App::class
    ],
    '/mail' => [
        'method' => 'POST',
        'handler' => Mail::class
    ],
    '/metadata' => [
        'method' => 'POST',
        'handler' => Metadata::class
    ]
];
