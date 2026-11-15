<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    */
    'ssr' => [
        'enabled' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | The paths and extensions options define where to look for page
    | components and which file extensions to consider.
    |
    */
    'pages' => [
        'ensure_pages_exist' => false,

        'paths' => [
            resource_path('js/Pages'),
            resource_path('js/pages'),
        ],

        'extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    */
    'testing' => [
        'ensure_pages_exist' => true,
    ],

    'expose_shared_prop_keys' => true,

];
