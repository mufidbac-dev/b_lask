<?php

return [
    'architecture' => env('SCAFFOLD_ARCHITECTURE', env('SCAFFOLD_PATTERN', 'service')),
    'pattern' => env('SCAFFOLD_PATTERN', 'service'), // backward compatibility

    'model' => [
        'primary_key'   => env('SCAFFOLD_PRIMARY_KEY', 'uuid'),
        'incrementing'  => env('SCAFFOLD_INCREMENTING', false),
        'key_type'      => env('SCAFFOLD_KEY_TYPE', 'string'),
        'timestamps'    => env('SCAFFOLD_TIMESTAMPS', true),
        'soft_delete'   => env('SCAFFOLD_SOFT_DELETE', true),
    ],

    'database' => [
        'connection' => env('DB_CONNECTION', 'mysql'),
    ],

    'auth' => [
        'driver' => env('SCAFFOLD_AUTH', 'sanctum'),
    ],

    'permission' => [
        'driver' => env('SCAFFOLD_PERMISSION', 'spatie'),
    ],

    'testing' => [
        'framework' => env('SCAFFOLD_TEST', 'phpunit'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Patterns and stub locations
    |--------------------------------------------------------------------------
    |
    | PENTING: semua path di 'files' relatif terhadap ROOT modul/app.
    | - Komponen source-code PHP (controller, service, policy, resource, dst)
    |   WAJIB diawali "app/" — ini mengikuti PSR-4 mapping di
    |   composer.json module ("Modules\\{Module}\\": "app/") dan konvensi
    |   nwidart sendiri (lihat config/modules.php > generator).
    | - 'migration' dan 'test' TIDAK pakai prefix "app/" karena PSR-4 module
    |   tidak memetakan folder database/ dan tests/ ke app/.
    |
    | Ubah/tambah pattern baru cukup lewat config ini — jangan mengubah logic
    | di command (config-driven extensibility, lihat AI Development
    | Constitution §4 Convention over Configuration).
    |
    */
    'patterns' => [
        'service' => [
            'stub_path' => 'service',
            'files' => [
                'controller'      => 'app/Http/Controllers/{Model}Controller.php',
                'service'         => 'app/Services/{Model}Service.php',
                'policy'          => 'app/Policies/{Model}Policy.php',
                'resource'        => 'app/Http/Resources/{Model}Resource.php',
                'export'          => 'app/Exports/{Model}Export.php',
                'request-store'   => 'app/Http/Requests/Store{Model}Request.php',
                'request-update'  => 'app/Http/Requests/Update{Model}Request.php',
                'model'           => 'app/Models/{Model}.php',
                'migration'       => 'database/migrations/create_{model_kebab_plural}_table.php',
                'test'            => 'tests/Feature/{Model}ControllerTest.php',
            ],
        ],

        'repository' => [
            'stub_path' => 'repository',
            'files' => [
                'controller'            => 'app/Http/Controllers/{Model}Controller.php',
                'repository-interface'  => 'app/Repositories/Contracts/{Model}RepositoryInterface.php',
                'repository'            => 'app/Repositories/Eloquent{Model}Repository.php',
                'service'               => 'app/Services/{Model}Service.php',
                'policy'                => 'app/Policies/{Model}Policy.php',
                'resource'              => 'app/Http/Resources/{Model}Resource.php',
                'export'                => 'app/Exports/{Model}Export.php',
                'request-store'         => 'app/Http/Requests/Store{Model}Request.php',
                'request-update'        => 'app/Http/Requests/Update{Model}Request.php',
                'model'                 => 'app/Models/{Model}.php',
                'migration'             => 'database/migrations/create_{model_kebab_plural}_table.php',
                'test'                  => 'tests/Feature/{Model}ControllerTest.php',
            ],
        ],

        'clean' => [
            'stub_path' => 'clean',
            'files' => [
                'controller'      => 'app/Http/Controllers/{Model}Controller.php',
                'action'          => 'app/Actions/{Model}Action.php',
                'policy'          => 'app/Policies/{Model}Policy.php',
                'resource'        => 'app/Http/Resources/{Model}Resource.php',
                'request-store'   => 'app/Http/Requests/Store{Model}Request.php',
                'request-update'  => 'app/Http/Requests/Update{Model}Request.php',
                'model'           => 'app/Models/{Model}.php',
                'migration'       => 'database/migrations/create_{model_kebab_plural}_table.php',
            ],
        ],

        'action' => [
            'stub_path' => 'action',
            'files' => [
                'controller'      => 'app/Http/Controllers/{Model}Controller.php',
                'action'          => 'app/Actions/{Model}Action.php',
                'policy'          => 'app/Policies/{Model}Policy.php',
                'resource'        => 'app/Http/Resources/{Model}Resource.php',
                'request-store'   => 'app/Http/Requests/Store{Model}Request.php',
                'request-update'  => 'app/Http/Requests/Update{Model}Request.php',
                'model'           => 'app/Models/{Model}.php',
                'migration'       => 'database/migrations/create_{model_kebab_plural}_table.php',
            ],
        ],

        'cqrs' => [
            'stub_path' => 'cqrs',
            'files' => [
                'controller'      => 'app/Http/Controllers/{Model}Controller.php',
                'command'         => 'app/Commands/{Model}Command.php',
                'query'           => 'app/Queries/{Model}Query.php',
                'handler'         => 'app/Handlers/{Model}Handler.php',
                'policy'          => 'app/Policies/{Model}Policy.php',
                'resource'        => 'app/Http/Resources/{Model}Resource.php',
                'request-store'   => 'app/Http/Requests/Store{Model}Request.php',
                'request-update'  => 'app/Http/Requests/Update{Model}Request.php',
                'model'           => 'app/Models/{Model}.php',
                'migration'       => 'database/migrations/create_{model_kebab_plural}_table.php',
            ],
        ],

        'ddd' => [
            'stub_path' => 'ddd',
            'files' => [
                'controller'      => 'app/Http/Controllers/{Model}Controller.php',
                'domain'          => 'app/Domain/Entities/{Model}.php',
                'repository'      => 'app/Repositories/{Model}Repository.php',
                'service'         => 'app/Services/{Model}Service.php',
                'policy'          => 'app/Policies/{Model}Policy.php',
                'resource'        => 'app/Http/Resources/{Model}Resource.php',
                'request-store'   => 'app/Http/Requests/Store{Model}Request.php',
                'request-update'  => 'app/Http/Requests/Update{Model}Request.php',
                'model'           => 'app/Models/{Model}.php',
                'migration'       => 'database/migrations/create_{model_kebab_plural}_table.php',
            ],
        ],
    ],
];
