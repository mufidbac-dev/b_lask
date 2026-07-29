<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::deleteDirectory(app_path('Http/Controllers/__tmp'));
    @unlink(app_path('Models/ScaffoldTestModel.php'));
    @unlink(app_path('Http/Controllers/ScaffoldTestModelController.php'));
    @unlink(app_path('Services/ScaffoldTestModelService.php'));
    @unlink(app_path('Policies/ScaffoldTestModelPolicy.php'));
    @unlink(app_path('Http/Resources/ScaffoldTestModelResource.php'));
    @unlink(app_path('Exports/ScaffoldTestModelExport.php'));
    @unlink(app_path('Http/Requests/StoreScaffoldTestModelRequest.php'));
    @unlink(app_path('Http/Requests/UpdateScaffoldTestModelRequest.php'));
    @unlink(base_path('tests/Feature/ScaffoldTestModelControllerTest.php'));
});

it('generates files with namespace matching PSR-4 for the destination folder', function () {
    $this->artisan('make:resource-crud', [
        'name' => 'ScaffoldTestModel',
        '--fields' => 'name:string:required',
        '--force' => true,
    ])->assertExitCode(0);

    $controller = file_get_contents(app_path('Http/Controllers/ScaffoldTestModelController.php'));
    expect($controller)->toContain('namespace App\Http\Controllers;');

    $service = file_get_contents(app_path('Services/ScaffoldTestModelService.php'));
    expect($service)->toContain('namespace App\Services;');

    expect($controller)->not->toContain('{{')
        ->and($service)->not->toContain('{{');
});

it('does not overwrite existing files without --force', function () {
    $this->artisan('make:resource-crud', [
        'name' => 'ScaffoldTestModel',
        '--fields' => 'name:string:required',
        '--force' => true,
    ])->assertExitCode(0);

    $before = file_get_contents(app_path('Http/Controllers/ScaffoldTestModelController.php'));

    $this->artisan('make:resource-crud', [
        'name' => 'ScaffoldTestModel',
        '--fields' => 'name:string:required',
    ])->assertExitCode(0);

    $after = file_get_contents(app_path('Http/Controllers/ScaffoldTestModelController.php'));

    expect($after)->toBe($before);
});

it('rejects invalid model names', function () {
    $this->artisan('make:resource-crud', ['name' => '../evil'])
        ->assertExitCode(1);

    $this->artisan('make:resource-crud', ['name' => 'test-model'])
        ->assertExitCode(1);
});

it('rejects mismatched --pattern flag', function () {
    $this->artisan('make:resource-crud', [
        'name' => 'ScaffoldTestModel',
        '--pattern' => 'repository',
    ])->assertExitCode(config('scaffold.pattern') === 'repository' ? 0 : 1);
});

it('registers policy idempotently', function () {
    $this->artisan('make:resource-crud', [
        'name' => 'ScaffoldTestModel',
        '--fields' => 'name:string:required',
        '--force' => true,
    ])->assertExitCode(0);

    $first = file_get_contents(app_path('Providers/AuthServiceProvider.php'));

    $this->artisan('make:resource-crud', [
        'name' => 'ScaffoldTestModel',
        '--fields' => 'name:string:required',
        '--force' => true,
    ])->assertExitCode(0);

    $second = file_get_contents(app_path('Providers/AuthServiceProvider.php'));

    expect($second)->toBe($first);
});