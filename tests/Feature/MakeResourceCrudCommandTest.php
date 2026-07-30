<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test State
|--------------------------------------------------------------------------
|
| These tests execute make:resource-crud, which modifies the application
| filesystem. We therefore backup all affected files before each test
| and restore them afterwards.
|
*/

beforeEach(function () {
    $this->scaffoldBackup = [];

    $this->scaffoldFiles = [
        // Model
        app_path('Models/ScaffoldTestModel.php'),

        // Controller
        app_path('Http/Controllers/ScaffoldTestModelController.php'),

        // Service
        app_path('Services/ScaffoldTestModelService.php'),

        // Policy
        app_path('Policies/ScaffoldTestModelPolicy.php'),

        // Resource
        app_path('Http/Resources/ScaffoldTestModelResource.php'),

        // Export
        app_path('Exports/ScaffoldTestModelExport.php'),

        // Requests
        app_path('Http/Requests/StoreScaffoldTestModelRequest.php'),
        app_path('Http/Requests/UpdateScaffoldTestModelRequest.php'),

        // Repository
        app_path('Repositories/EloquentScaffoldTestModelRepository.php'),

        // Repository Contract
        app_path(
            'Repositories/Contracts/ScaffoldTestModelRepositoryInterface.php'
        ),

        // Generated Feature Test
        base_path(
            'tests/Feature/ScaffoldTestModelControllerTest.php'
        ),

        // Providers modified by the generator
        app_path('Providers/AppServiceProvider.php'),
        app_path('Providers/AuthServiceProvider.php'),
    ];

    foreach ($this->scaffoldFiles as $path) {
        $this->scaffoldBackup[$path] = [
            'exists' => file_exists($path),
            'content' => file_exists($path)
                ? file_get_contents($path)
                : null,
        ];
    }

    cleanupScaffoldGeneratedFiles();
});

afterEach(function () {
    restoreScaffoldFiles(
        $this->scaffoldBackup ?? []
    );

    cleanupScaffoldGeneratedDirectories();
});

/*
|--------------------------------------------------------------------------
| Cleanup Helpers
|--------------------------------------------------------------------------
*/

function cleanupScaffoldGeneratedFiles(): void
{
    $files = [
        // Model
        app_path('Models/ScaffoldTestModel.php'),

        // Controller
        app_path('Http/Controllers/ScaffoldTestModelController.php'),

        // Service
        app_path('Services/ScaffoldTestModelService.php'),

        // Policy
        app_path('Policies/ScaffoldTestModelPolicy.php'),

        // Resource
        app_path('Http/Resources/ScaffoldTestModelResource.php'),

        // Export
        app_path('Exports/ScaffoldTestModelExport.php'),

        // Requests
        app_path('Http/Requests/StoreScaffoldTestModelRequest.php'),
        app_path('Http/Requests/UpdateScaffoldTestModelRequest.php'),

        // Repository
        app_path('Repositories/EloquentScaffoldTestModelRepository.php'),

        // Repository Contract
        app_path(
            'Repositories/Contracts/ScaffoldTestModelRepositoryInterface.php'
        ),

        // Generated Feature Test
        base_path(
            'tests/Feature/ScaffoldTestModelControllerTest.php'
        ),
    ];

    foreach ($files as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

function cleanupScaffoldGeneratedDirectories(): void
{
    $directories = [
        app_path('Http/Controllers/__tmp'),
    ];

    foreach ($directories as $directory) {
        deleteScaffoldDirectory($directory);
    }
}

function deleteScaffoldDirectory(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $items = scandir($directory);

    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $directory . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            deleteScaffoldDirectory($path);
        } else {
            @unlink($path);
        }
    }

    @rmdir($directory);
}

/*
|--------------------------------------------------------------------------
| Restore Helpers
|--------------------------------------------------------------------------
*/

function restoreScaffoldFiles(array $backups): void
{
    foreach ($backups as $path => $backup) {
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir(
                $directory,
                0777,
                true
            );
        }

        if ($backup['exists']) {
            file_put_contents(
                $path,
                $backup['content']
            );

            continue;
        }

        if (is_file($path)) {
            @unlink($path);
        }
    }
}

/*
|--------------------------------------------------------------------------
| Tests
|--------------------------------------------------------------------------
*/

it('generates files with namespace matching PSR-4 for the destination folder', function () {
    $this->artisan('make:resource-crud', [
        'name' => 'ScaffoldTestModel',
        '--fields' => 'name:string:required',
        '--force' => true,
    ])
        ->expectsConfirmation(
            'Model ScaffoldTestModel belum ada di app/Models. Generate stub Model dasar sekarang?',
            'yes'
        )
        ->assertExitCode(0);

    $controllerPath = app_path(
        'Http/Controllers/ScaffoldTestModelController.php'
    );

    $servicePath = app_path(
        'Services/ScaffoldTestModelService.php'
    );

    expect(file_exists($controllerPath))
        ->toBeTrue()
        ->and(file_exists($servicePath))
        ->toBeTrue();

    $controller = file_get_contents($controllerPath);
    $service = file_get_contents($servicePath);

    expect($controller)
        ->toContain('namespace App\Http\Controllers;')
        ->not->toContain('{{');

    expect($service)
        ->toContain('namespace App\Services;')
        ->not->toContain('{{');
});

it('does not overwrite existing files without --force', function () {
    $this->artisan('make:resource-crud', [
        'name' => 'ScaffoldTestModel',
        '--fields' => 'name:string:required',
        '--force' => true,
    ])
        ->expectsConfirmation(
            'Model ScaffoldTestModel belum ada di app/Models. Generate stub Model dasar sekarang?',
            'yes'
        )
        ->assertExitCode(0);

    $controllerPath = app_path(
        'Http/Controllers/ScaffoldTestModelController.php'
    );

    expect(file_exists($controllerPath))
        ->toBeTrue();

    $before = file_get_contents($controllerPath);

    $this->artisan('make:resource-crud', [
        'name' => 'ScaffoldTestModel',
        '--fields' => 'name:string:required',
    ])
        ->assertExitCode(0);

    $after = file_get_contents($controllerPath);

    expect($after)
        ->toBe($before);
});

it('rejects invalid model names', function () {
    $this->artisan('make:resource-crud', [
        'name' => '../evil',
    ])
        ->assertExitCode(1);

    $this->artisan('make:resource-crud', [
        'name' => 'test-model',
    ])
        ->assertExitCode(1);
});

it('rejects mismatched pattern flag', function () {
    if (config('scaffold.pattern') === 'repository') {
        $this->markTestSkipped(
            'Current scaffold pattern is repository.'
        );
    }

    $this->artisan('make:resource-crud', [
        'name' => 'ScaffoldTestModel',
        '--pattern' => 'repository',
    ])
        ->assertExitCode(1);
});

it('registers policy idempotently', function () {
    $authServiceProviderPath = app_path(
        'Providers/AuthServiceProvider.php'
    );

    expect(file_exists($authServiceProviderPath))
        ->toBeTrue();

    $this->artisan('make:resource-crud', [
        'name' => 'ScaffoldTestModel',
        '--fields' => 'name:string:required',
        '--force' => true,
    ])
        ->expectsConfirmation(
            'Model ScaffoldTestModel belum ada di app/Models. Generate stub Model dasar sekarang?',
            'yes'
        )
        ->assertExitCode(0);

    $first = file_get_contents(
        $authServiceProviderPath
    );

    $this->artisan('make:resource-crud', [
        'name' => 'ScaffoldTestModel',
        '--fields' => 'name:string:required',
        '--force' => true,
    ])
        ->assertExitCode(0);

    $second = file_get_contents(
        $authServiceProviderPath
    );

    expect($second)
        ->toBe($first);
});
