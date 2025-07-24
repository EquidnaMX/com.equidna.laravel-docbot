<?php

return [
    // Token variables for API segments
    'tokens' => [
        'api' => env('API_TOKEN', ''),
        'clients-api' => env('CLIENT_TOKEN', ''),
        'distributor-api' => env('DIST_TOKEN', ''),
        'web' => env('WEB_TOKEN', ''),
    ],

    'output_dir' => env('DOCBOT_OUTPUT_DIR', base_path('doc')),

    // Segments for route documentation
    'segments' => [
        [
            'key' => 'api',
            'prefix' => 'api/',
            'token' => 'API_TOKEN',
        ],
        [
            'key' => 'clients-api',
            'prefix' => 'clients-api/',
            'token' => 'CLIENT_TOKEN',
        ],
        [
            'key' => 'distributor-api',
            'prefix' => 'distributors-api/',
            'token' => 'DIST_TOKEN',
        ],
        [
            'key' => 'hooks',
            'prefix' => 'hooks/'
        ]
    ],

];
