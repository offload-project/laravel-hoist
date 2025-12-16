<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Directories
    |--------------------------------------------------------------------------
    |
    | Define the directories where your feature classes are located, mapped
    | to their corresponding namespaces. The package will automatically
    | discover all feature classes from these directories.
    |
    | Example:
    |   app_path('Authorization/Features') => 'App\\Authorization\\Features',
    |   app_path('Billing/Features') => 'App\\Billing\\Features',
    |
    */
    'feature_directories' => [
        app_path('Features') => 'App\\Features',
    ],
];
