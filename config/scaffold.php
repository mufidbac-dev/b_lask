<?php

return [
    'pattern' => env('SCAFFOLD_PATTERN', 'service'),
    'primary_key' => env('SCAFFOLD_PRIMARY_KEY', 'uuid'),

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
                'test'                  => 'tests/Feature/{Model}ControllerTest.php',
            ],
        ],
    ],
];