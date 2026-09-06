<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;
use ReflectionClass;
use ReflectionMethod;

class GenerateRestApiCommand extends Command
{
    protected $signature = 'make:rest-api
        {model        : The model class name}
        {module?      : The module name (auto-detected if omitted)}
        {--all        : Generate all components}
        {--bootstrap  : Create basic module skeleton (model + migration)}
        {--analyze    : Analyze model and generate missing API artifacts (default)}
        {--sync       : Sync requests/resources when model changes}
        {--root       : Force generation into the root app/ tree, skip module detection}
        {--force      : Overwrite existing files}';

    protected $description = 'Generate REST API components based on scaffold configuration';

    protected string $modelName;

    /** null = root app (no module), non-null = target module name */
    protected ?string $moduleName = null;

    protected ?string $modelClass = null;

    protected ?ReflectionClass $reflection = null;

    protected array $fillable = [];

    protected array $relationships = [];

    protected array $casts = [];

    protected string $tableName = '';

    protected bool $hasUuid = false;

    protected array $config;

    protected string $stubPath = 'shema-api';

    public function handle(): int
    {
        $rawModel = $this->argument('model');
        $this->modelName = Str::studly(Str::afterLast($rawModel, '.'));

        if (! preg_match('/^[A-Z][A-Za-z0-9]*$/', $this->modelName)) {
            $this->error("Nama model tidak valid: '{$rawModel}'");

            return self::FAILURE;
        }

        if ($rawModel !== $this->modelName) {
            $this->warn("Nama model disanitasi: '{$rawModel}' → '{$this->modelName}'");
        }

        if (! $this->resolveModule()) {
            return self::FAILURE;
        }

        $pattern = config('scaffold.architecture', config('scaffold.pattern', 'service'));
        $this->config = config("scaffold.patterns.{$pattern}");

        if (empty($this->config)) {
            $this->error("Scaffold pattern '{$pattern}' tidak ditemukan di config/scaffold.php.");

            return self::FAILURE;
        }

        $this->stubPath = $this->config['stub_path'] ?? $this->stubPath;

        $target = $this->moduleName ?? 'root app';
        $this->info("🚀 Generating REST API components for {$this->modelName} in {$target} using pattern {$pattern}…");

        if ($this->option('bootstrap')) {
            if (! $this->prepareBootstrap()) {
                return self::FAILURE;
            }
            $this->info("\n✅ Bootstrap completed.");
            $this->displayNextSteps();

            return self::SUCCESS;
        }

        if (! $this->analyzeModel()) {
            return self::FAILURE;
        }

        if ($this->option('sync')) {
            foreach ($this->getComponentsToGenerate('sync') as $component => $pathTemplate) {
                $this->generate($component, $pathTemplate);
            }
            $this->info("\n✅ Sync completed.");
            $this->displayNextSteps();

            return self::SUCCESS;
        }

        foreach ($this->getComponentsToGenerate('analyze') as $component => $pathTemplate) {
            $this->generate($component, $pathTemplate);
        }

        $this->info("\n✅ REST API generation completed successfully!");
        $this->displayNextSteps();

        return self::SUCCESS;
    }

    /**
     * Resolve target module (or root app) and validate it explicitly.
     * Fails fast instead of silently falling back to a different target.
     */
    protected function resolveModule(): bool
    {
        $requested = $this->argument('module');

        if ($this->option('root')) {
            if ($requested) {
                $this->error('--root tidak bisa dipakai bersamaan dengan argumen module.');

                return false;
            }
            $this->moduleName = null;

            return true;
        }

        if ($requested) {
            if (! $this->isModuleEnabled($requested)) {
                $this->error("Module '{$requested}' tidak ditemukan atau belum enabled (cek modules_statuses.json).");

                return false;
            }
            $this->moduleName = $requested;

            return true;
        }

        $detected = $this->detectModule();

        // detectModule() returns '' as a sentinel for "root app detected",
        // null for "nothing found at all" (ambiguous, must be explicit).
        if ($detected === null) {
            $this->error(
                "Tidak bisa auto-detect module/lokasi untuk model '{$this->modelName}'. "
                .'Sertakan argumen module secara eksplisit, atau pakai --root untuk generate ke app/.'
            );

            return false;
        }

        $this->moduleName = $detected === '' ? null : $detected;

        return true;
    }

    /**
     * Auto-detect where the model lives.
     *
     * @return string|null Module name, '' for root app, null if not found anywhere.
     */
    protected function detectModule(): ?string
    {
        foreach ($this->getEnabledModules() as $name) {
            if (File::exists(module_path($name, "app/Models/{$this->modelName}.php"))) {
                return $name;
            }
        }

        if (File::exists(app_path("Models/{$this->modelName}.php"))) {
            return '';
        }

        return null;
    }

    /**
     * List enabled module names. Uses nwidart's Module facade (source of
     * truth = modules_statuses.json) instead of a hardcoded list, so this
     * stays correct as modules are added/removed without touching code.
     */
    protected function getEnabledModules(): array
    {
        try {
            $enabled = Module::allEnabled();

            return collect($enabled)
                ->map(function ($module, $key) {
                    // Module::allEnabled() bisa mengembalikan:
                    // - array/collection keyed by nama module => Module instance
                    // - array/collection numerik berisi Module instance saja
                    if (is_object($module) && method_exists($module, 'getName')) {
                        return $module->getName();
                    }

                    return is_string($key) ? $key : (string) $module;
                })
                ->filter()
                ->values()
                ->all();
        } catch (\Throwable) {
            // Fallback murni baca file jika Module facade gagal total
            $statusesFile = base_path('modules_statuses.json');
            if (! File::exists($statusesFile)) {
                return [];
            }
            $statuses = json_decode(File::get($statusesFile), true) ?? [];

            return array_keys(array_filter($statuses));
        }
    }

    protected function isModuleEnabled(string $name): bool
    {
        return in_array($name, $this->getEnabledModules(), true);
    }

    protected function analyzeModel(): bool
    {
        try {
            $this->modelClass = $this->moduleName === null
                ? "App\\Models\\{$this->modelName}"
                : "Modules\\{$this->moduleName}\\Models\\{$this->modelName}";

            if (! class_exists($this->modelClass)) {
                $this->error("Model class {$this->modelClass} not found.");

                return false;
            }

            $this->reflection = new ReflectionClass($this->modelClass);
            $model = new $this->modelClass;

            $this->fillable = $model->getFillable();
            $this->casts = $model->getCasts();
            $this->tableName = $model->getTable();

            $traits = class_uses_recursive($model);
            $this->hasUuid = in_array('Illuminate\\Database\\Eloquent\\Concerns\\HasUuids', $traits);

            $this->extractRelationships();

            return true;
        } catch (\Exception $e) {
            $this->error('Failed to analyze model: '.$e->getMessage());

            return false;
        }
    }

    protected function extractRelationships(): void
    {
        $methods = $this->reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $relationshipTypes = [
            'hasMany', 'hasOne', 'belongsTo', 'belongsToMany', 'morphMany',
            'morphOne', 'morphTo', 'morphToMany', 'hasManyThrough', 'hasOneThrough',
        ];

        foreach ($methods as $method) {
            if ($method->class !== $this->modelClass || $method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            try {
                $source = file_get_contents($method->getFileName());
                $lines = explode("\n", $source);
                $methodSource = implode("\n", array_slice(
                    $lines,
                    $method->getStartLine() - 1,
                    $method->getEndLine() - $method->getStartLine() + 1
                ));

                foreach ($relationshipTypes as $type) {
                    if (preg_match('/\\$this->'.$type.'\\s*\\(/', $methodSource)) {
                        $this->relationships[$method->getName()] = ['type' => $type, 'method' => $method->getName()];
                        break;
                    }
                }
            } catch (\Exception) {
                continue;
            }
        }
    }

    protected function getComponentsToGenerate(string $mode = 'analyze'): array
    {
        $files = $this->config['files'] ?? [];

        if ($mode === 'sync') {
            return [
                'request-store' => $files['request-store'] ?? null,
                'request-update' => $files['request-update'] ?? null,
                'resource' => $files['resource'] ?? null,
            ];
        }

        // Model and migration files are domain-owned and must never be
        // overwritten by API regeneration. Bootstrap is the only mode that
        // creates those files.
        unset($files['model'], $files['migration']);

        return $files;
    }

    protected function generate(string $stubName, ?string $pathTemplate): void
    {
        if ($pathTemplate === null) {
            $this->warn("  ⚠ Tidak ada path template untuk '{$stubName}' di pattern ini, dilewati.");

            return;
        }

        $name = str_replace('{Model}', $this->modelName, $stubName);
        $path = str_replace('{Model}', $this->modelName, $pathTemplate);
        $path = str_replace('{model_kebab}', Str::kebab($this->modelName), $path);
        $path = str_replace('{model_kebab_plural}', Str::plural(Str::kebab($this->modelName)), $path);

        $this->info("\n📝 Generating {$name}…");

        $fullPath = $this->getModulePath($path);

        if (File::exists($fullPath) && ! $this->option('force')) {
            $this->warn("  ⚠ Already exists: {$name} (use --force to overwrite)");

            return;
        }

        $stubPath = base_path("stubs/{$this->stubPath}/{$stubName}.stub");
        if (! File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");

            return;
        }

        $content = $this->replaceStubVariables(File::get($stubPath));

        File::ensureDirectoryExists(dirname($fullPath));
        File::put($fullPath, $content);
        $this->info("  ✓ Created: {$name}");
    }

    protected function generateFileFromStub(string $stubName, string $relativePath): void
    {
        $fullPath = $this->getModulePath($relativePath);

        if (File::exists($fullPath) && ! $this->option('force')) {
            $this->warn("  ⚠ Already exists: {$relativePath} (use --force to overwrite)");

            return;
        }

        $stubPath = base_path("stubs/{$this->stubPath}/{$stubName}.stub");
        if (! File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");

            return;
        }

        $content = $this->replaceStubVariables(File::get($stubPath));
        File::ensureDirectoryExists(dirname($fullPath));
        File::put($fullPath, $content);
        $this->info("  ✓ Created: {$relativePath}");
    }

    /**
     * Resolve a config-relative path to an absolute filesystem path,
     * respecting nwidart's configured module root via module_path()
     * instead of assuming Modules/{name} is hardcoded.
     */
    protected function getModulePath(string $relative): string
    {
        return $this->moduleName === null
            ? base_path($relative)
            : module_path($this->moduleName, $relative);
    }

    protected function replaceStubVariables(string $stub): string
    {
        $moduleNamespaceRoot = config('modules.namespace', 'Modules');

        $namespace = $this->moduleName === null
            ? 'App'
            : "{$moduleNamespaceRoot}\\{$this->moduleName}";

        $modelNamespace = $this->moduleName === null
            ? "App\\Models\\{$this->modelName}"
            : "{$moduleNamespaceRoot}\\{$this->moduleName}\\Models\\{$this->modelName}";
        $testNamespace = $this->moduleName === null
            ? 'Tests'
            : "{$moduleNamespaceRoot}\\{$this->moduleName}\\Tests";

        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ moduleNamespace }}' => $namespace,
            '{{ modelNamespace }}' => $modelNamespace,
            '{{ testNamespace }}' => $testNamespace,
            '{{ model }}' => $this->modelName,
            '{{ modelName }}' => $this->modelName,
            '{{ modelVariable }}' => Str::camel($this->modelName),
            '{{ modelVariablePlural }}' => Str::camel(Str::plural($this->modelName)),
            '{{ modelNamePlural }}' => Str::plural($this->modelName),
            '{{ modelKebab }}' => Str::kebab($this->modelName),
            '{{ modelKebabPlural }}' => Str::plural(Str::kebab($this->modelName)),
            '{{ tableName }}' => $this->tableName,
            '{{ primaryKey }}' => $this->hasUuid ? 'uuid' : 'id',
            '{{ fillableFields }}' => $this->generateFillableFields(),
            '{{ validationRules }}' => $this->generateValidationRules('Store'),
            '{{ validationRulesUpdate }}' => $this->generateValidationRules('Update'),
            '{{ resourceFields }}' => $this->generateResourceFields(),
            '{{ relationships }}' => $this->generateRelationshipsLoading(),
            '{{ permissionPrefix }}' => Str::kebab($this->modelName),
            '{{ exportHeadings }}' => $this->generateExportHeadings(),
            '{{ exportMappings }}' => $this->generateExportMappings(),
            '{{ migrationFields }}' => $this->generateMigrationFields(),
            '{{ repositoryInterfaceNamespace }}' => $namespace.'\\Repositories\\Contracts',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    protected function generateMigrationFields(): string
    {
        if (empty($this->fillable)) {
            return "// TODO: tambahkan kolom, contoh: \$table->string('name');";
        }

        $skip = ['id', 'uuid', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by'];
        $lines = [];

        foreach ($this->fillable as $field) {
            if (in_array($field, $skip)) {
                continue;
            }
            $cast = $this->casts[$field] ?? null;
            $type = match (true) {
                Str::endsWith($field, '_uuid') => 'uuid',
                in_array($cast, ['int', 'integer']) => 'integer',
                in_array($cast, ['float', 'double', 'decimal']) => 'decimal',
                in_array($cast, ['bool', 'boolean']) => 'boolean',
                in_array($cast, ['date', 'datetime']) => 'timestamp',
                default => 'string',
            };
            $lines[] = "\$table->{$type}('{$field}')->nullable();";
        }

        return implode("\n            ", $lines);
    }

    protected function generateFillableFields(): string
    {
        $skip = ['id', 'uuid', 'created_at', 'updated_at', 'deleted_at'];
        $fields = array_filter($this->fillable, fn ($f) => ! in_array($f, $skip));

        return implode(', ', array_map(fn ($f) => "'{$f}'", $fields));
    }

    protected function generateValidationRules(string $type): string
    {
        $skip = ['id', 'uuid', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by'];
        $rules = [];

        foreach ($this->fillable as $field) {
            if (in_array($field, $skip)) {
                continue;
            }
            $fieldRules = [$type === 'Store' ? 'required' : 'sometimes'];
            if ($cast = $this->casts[$field] ?? null) {
                $fieldRules[] = match (true) {
                    in_array($cast, ['bool', 'boolean']) => 'boolean',
                    in_array($cast, ['int', 'integer']) => 'integer',
                    in_array($cast, ['float', 'double', 'decimal']) => 'numeric',
                    in_array($cast, ['date', 'datetime']) => 'date',
                    default => null,
                };
            }
            if (Str::contains($field, ['name', 'title', 'description', 'code'])) {
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:255';
            }
            if (Str::contains($field, 'email')) {
                $fieldRules[] = 'email';
            }
            if (Str::endsWith($field, '_uuid')) {
                $fieldRules[] = 'uuid';
                $fieldRules[] = 'exists:'.Str::plural(Str::beforeLast($field, '_uuid')).',uuid';
            }
            $rules[$field] = implode('|', array_filter($fieldRules));
        }

        $lines = array_map(fn ($f, $r) => "            '{$f}' => '{$r}',", array_keys($rules), $rules);

        return "[\n".implode("\n", $lines)."\n        ]";
    }

    protected function generateResourceFields(): string
    {
        $lines = array_map(fn ($f) => "'{$f}' => \$this->{$f}", $this->fillable);
        $lines[] = "'created_at' => \$this->created_at?->toISOString()";
        $lines[] = "'updated_at' => \$this->updated_at?->toISOString()";
        foreach ($this->relationships as $rel) {
            $lines[] = "'{$rel['method']}' => \$this->whenLoaded('{$rel['method']}')";
        }

        return implode(",\n            ", $lines);
    }

    protected function generateRelationshipsLoading(): string
    {
        if (empty($this->relationships)) {
            return '';
        }

        return implode("\n\n    ", array_map(function (array $relationship): string {
            $method = $relationship['method'];
            $type = $relationship['type'];

            return "public function {$method}()\n    {\n        return \$this->{$type}();\n    }";
        }, array_values($this->relationships)));
    }

    protected function generateExportHeadings(): string
    {
        $skip = ['id', 'uuid', 'created_at', 'updated_at', 'deleted_at'];

        return implode("\n            ", array_map(
            fn ($f) => "'".Str::headline($f)."',",
            array_filter($this->fillable, fn ($f) => ! in_array($f, $skip))
        ));
    }

    protected function generateExportMappings(): string
    {
        $skip = ['id', 'uuid', 'created_at', 'updated_at', 'deleted_at'];

        return implode("\n            ", array_map(
            fn ($f) => "\$row->{$f},",
            array_filter($this->fillable, fn ($f) => ! in_array($f, $skip))
        ));
    }

    protected function displayNextSteps(): void
    {
        $this->newLine();
        $this->info('📋 Next steps: Update Service $searchable, register Policy, add routes, seed permissions.');
        if ($this->moduleName !== null) {
            $this->line("   → Daftarkan route di Modules/{$this->moduleName}/routes/api.php (belum otomatis).");
        } else {
            $this->line('   → Daftarkan route di routes/api.php (belum otomatis).');
        }
    }

    protected function defaultTableName(): string
    {
        return Str::snake(Str::plural($this->modelName));
    }

    protected function prepareBootstrap(): bool
    {
        try {
            $this->tableName = $this->defaultTableName();

            $modelPath = $this->config['files']['model'] ?? 'app/Models/{Model}.php';
            $this->generate('model', $modelPath);

            $kebabPlural = Str::plural(Str::kebab($this->modelName));
            $timestamp = date('Y_m_d_His');
            $migrationFile = "database/migrations/{$timestamp}_create_{$kebabPlural}_table.php";
            $this->generateFileFromStub('migration', $migrationFile);

            return true;
        } catch (\Exception $e) {
            $this->error('Bootstrap failed: '.$e->getMessage());

            return false;
        }
    }
}
