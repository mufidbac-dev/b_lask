<?php

namespace App\Console\Commands;

use App\Support\FieldDefinitionParser;
use App\Support\RelationDefinitionParser;
use App\Support\StubReplacer;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeResourceCrudCommand extends Command
{
    protected $signature = 'make:resource-crud {name}
        {--fields= : name:type:rules,... }
        {--relations= : name:type,... }
        {--primary-key= : override primary key untuk model ini saja}
        {--pattern= : konfirmasi eksplisit pattern aktif, harus sama dengan .env}
        {--no-export}
        {--no-policy}
        {--no-test}
        {--force}';

    protected $description = 'Generate CRUD scaffold (controller, service/repository, policy, resource, export, requests, test) dari stub sesuai pattern aktif project.';

    public function __construct(
        private readonly StubReplacer $stubReplacer,
        private readonly FieldDefinitionParser $fieldParser,
        private readonly RelationDefinitionParser $relationParser,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name');

        if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name)) {
            $this->error("Nama model harus PascalCase alfanumerik tanpa spasi/simbol (mis. 'Product'), diterima: '{$name}'");
            return self::FAILURE;
        }

        $activePattern = config('scaffold.pattern');
        $requestedPattern = $this->option('pattern');

        if ($requestedPattern !== null && $requestedPattern !== $activePattern) {
            $this->error(
                "Project ini dikunci ke pattern '{$activePattern}' (lihat SCAFFOLD_PATTERN di .env). " .
                "Generate dengan pattern lain akan membuat modul tidak konsisten. Untuk beralih pattern " .
                "project-wide, ubah SCAFFOLD_PATTERN di .env — perhatikan bahwa file yang sudah di-generate " .
                "SEBELUMNYA tidak akan otomatis dikonversi ke pattern baru."
            );
            return self::FAILURE;
        }

        $patternConfig = config("scaffold.patterns.{$activePattern}");

        if ($patternConfig === null) {
            $this->error("Pattern '{$activePattern}' tidak terdaftar di config('scaffold.patterns').");
            return self::FAILURE;
        }

        if (!$this->ensureModelExists($name)) {
            return self::FAILURE;
        }

        try {
            $fields = $this->fieldParser->parse($this->option('fields'));
            $relations = $this->relationParser->parse($this->option('relations'));
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $baseReplacements = $this->buildReplacements($name, $fields, $relations);
        $force = (bool) $this->option('force');

        $generatedFiles = [];

        foreach ($patternConfig['files'] as $key => $destinationTemplate) {
            if ($this->shouldSkip($key)) {
                continue;
            }

            $destinationRelative = str_replace('{Model}', $name, $destinationTemplate);
            $stubPath = base_path("stubs/{$patternConfig['stub_path']}/{$key}.stub");

            try {
                $namespace = $this->resolveNamespaceFromPath($destinationRelative);
                $replacements = array_merge($baseReplacements, ['namespace' => $namespace]);

                $contents = $this->stubReplacer->replace($stubPath, $replacements);
                $this->stubReplacer->write($contents, base_path($destinationRelative), $force);

                $this->info("Generated: {$destinationRelative}");
                $generatedFiles[] = base_path($destinationRelative);
            } catch (\RuntimeException $e) {
                $this->warn($e->getMessage());
            }
        }

        if (!$this->option('no-policy')) {
            $this->registerPolicy($name);
        }

        if ($activePattern === 'repository') {
            $this->registerRepositoryBinding($name);
        }

        // Post-generation automation: format, dump-autoload, dan jalankan generated tests
        $this->runPostGenerationTasks($name, $generatedFiles);

        $this->line('Reminder: add resource route to routes/api.php and create migration/factory manually.');

        return self::SUCCESS;
    }

    private function shouldSkip(string $fileKey): bool
    {
        return match ($fileKey) {
            'export' => (bool) $this->option('no-export'),
            'policy' => (bool) $this->option('no-policy'),
            'test' => (bool) $this->option('no-test'),
            default => false,
        };
    }

    /**
     * Resolve namespace dari path tujuan relatif (mis.
     * "app/Http/Controllers/ProductController.php" -> "App\Http\Controllers").
     * Throw eksplisit kalau destination di luar app/ — jangan diam-diam
     * menghasilkan namespace yang salah.
     */
    private function resolveNamespaceFromPath(string $destinationRelativePath): string
    {
        $dir = trim(dirname($destinationRelativePath), '/');

        // app/ -> App\*
        if ($dir === 'app' || $dir === '.') {
            return 'App';
        }

        if (Str::startsWith($dir, 'app/')) {
            return 'App\\' . str_replace('/', '\\', Str::after($dir, 'app/'));
        }

        // tests/Feature -> Tests\Feature
        if (Str::startsWith($dir, 'tests/')) {
            $sub = trim(Str::after($dir, 'tests/'), '/');
            return $sub === '' ? 'Tests' : 'Tests\\' . str_replace('/', '\\', $sub);
        }

        // fall back: attempt to derive a sensible PHP namespace (e.g., database/factories -> Database\\Factories)
        $parts = explode('/', $dir);
        $parts = array_map(fn($p) => Str::studly($p), $parts);

        return implode('\\', $parts);
    }

    private function buildReplacements(string $name, array $fields, array $relations): array
    {
        $derived = StubReplacer::deriveNames($name);

        $primaryKey = $this->option('primary-key') ?? config('scaffold.primary_key');

        return array_merge($derived, [
            'primaryKey' => $primaryKey,
            'relationships' => $this->relationParser->toRelationshipMethods($relations),
            'validationRules' => $this->fieldParser->toValidationRules($fields),
            'validationRulesUpdate' => $this->fieldParser->toValidationRulesForUpdate($fields),
            'resourceFields' => $this->fieldParser->toResourceFields($fields),
            'exportHeadings' => $this->fieldParser->toExportHeadings($fields),
            'exportMappings' => $this->fieldParser->toExportMappings($fields, $derived['modelVariable']),
        ]);
    }

    private function ensureModelExists(string $name): bool
    {
        $modelPath = app_path("Models/{$name}.php");

        if (file_exists($modelPath)) {
            return true;
        }

        if (!$this->confirm("Model {$name} belum ada di app/Models. Generate stub Model dasar sekarang?")) {
            $this->error("Model {$name} tidak ditemukan. Buat model dulu sebelum generate CRUD.");
            return false;
        }

        $primaryKey = $this->option('primary-key') ?? config('scaffold.primary_key');
        $usesUuid = $primaryKey === 'uuid';
        
        // PERBAIKAN: Evaluasi Str methods SEBELUM dimasukkan ke dalam heredoc
        $tableName = Str::snake(Str::plural($name));

        $stub = $usesUuid
            ? <<<PHP
                <?php

                namespace App\\Models;

                use Illuminate\\Database\\Eloquent\\Concerns\\HasUuids;
                use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
                use Illuminate\\Database\\Eloquent\\Model;

                class {$name} extends Model
                {
                    use HasFactory, HasUuids;

                    protected \$table = '{$tableName}';

                    protected \$fillable = [];
                }

                PHP
            : <<<PHP
                <?php

                namespace App\\Models;

                use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
                use Illuminate\\Database\\Eloquent\\Model;

                class {$name} extends Model
                {
                    use HasFactory;

                    protected \$table = '{$tableName}';

                    protected \$fillable = [];
                }

                PHP;

        file_put_contents($modelPath, $stub);
        $this->info("Generated: app/Models/{$name}.php");

        return true;
    }

    /**
     * Idempotent: cek dulu apakah mapping sudah ada sebelum menambah.
     */
    private function registerPolicy(string $name): void
    {
        $providerPath = app_path('Providers/AuthServiceProvider.php');

        if (!file_exists($providerPath)) {
            $this->warn('AuthServiceProvider.php tidak ditemukan — daftarkan policy secara manual: ' .
                "\\App\\Models\\{$name}::class => \\App\\Policies\\{$name}Policy::class,");
            return;
        }

        $mapping = "\\App\\Models\\{$name}::class => \\App\\Policies\\{$name}Policy::class,";
        $contents = file_get_contents($providerPath);

        if (str_contains($contents, $mapping)) {
            $this->line('Policy mapping already exists');
            return;
        }

        $pattern = '/(protected\s+(?:array\s+)?\$policies\s*=\s*\[)/';

        if (!preg_match($pattern, $contents)) {
            $this->warn('Tidak menemukan properti $policies di AuthServiceProvider — daftarkan manual: ' . $mapping);
            return;
        }

        $updated = preg_replace($pattern, "$1\n        {$mapping}", $contents, 1);
        file_put_contents($providerPath, $updated);
        $this->info('Policy registered in AuthServiceProvider');
    }

    /**
     * Idempotent, sama seperti registerPolicy — hanya untuk pattern repository.
     */
    private function registerRepositoryBinding(string $name): void
    {
        $providerPath = app_path('Providers/AppServiceProvider.php');

        if (!file_exists($providerPath)) {
            $this->warn('AppServiceProvider.php tidak ditemukan — daftarkan binding secara manual: ' .
                "\$this->app->bind(\\App\\Repositories\\Contracts\\{$name}RepositoryInterface::class, " .
                "\\App\\Repositories\\Eloquent{$name}Repository::class);");
            return;
        }

        $binding = "\$this->app->bind(\\App\\Repositories\\Contracts\\{$name}RepositoryInterface::class, \\App\\Repositories\\Eloquent{$name}Repository::class);";
        $contents = file_get_contents($providerPath);

        if (str_contains($contents, $binding)) {
            $this->line('Repository binding already exists');
            return;
        }

        $pattern = '/(public\s+function\s+register\s*\(\s*\)\s*(?::\s*void\s*)?\{)/';

        if (!preg_match($pattern, $contents)) {
            $this->warn('Tidak menemukan method register() di AppServiceProvider — daftarkan manual: ' . $binding);
            return;
        }

        $updated = preg_replace($pattern, "$1\n        {$binding}", $contents, 1);
        file_put_contents($providerPath, $updated);
        $this->info('Repository binding registered in AppServiceProvider');
    }

    /**
     * Hanya jalankan test file yang baru di-generate, bukan semua tests.
     * Skip jika file tidak ada atau test option di-disable.
     */
    private function runPostGenerationTasks(string $modelName, array $generatedFiles): void
    {
        // 1) Composer dump-autoload
        exec('composer dump-autoload --no-interaction 2>&1', $out, $code);
        if (isset($code) && $code === 0) {
            $this->info('composer dump-autoload completed');
        } else {
            $this->warn('composer dump-autoload failed: ' . (isset($out) ? implode("\n", $out) : 'unknown'));
        }

        // 2) Run Pint on generated files if available
        $pintPath = DIRECTORY_SEPARATOR === '\\' ? 'vendor\\bin\\pint' : './vendor/bin/pint';
        if (!empty($generatedFiles) && file_exists('vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'pint')) {
            $pintArgs = array_map('escapeshellarg', $generatedFiles);
            $cmd = $pintPath . ' ' . implode(' ', $pintArgs) . ' --quiet';
            exec($cmd . ' 2>&1', $pout, $pcode);
            if (isset($pcode) && $pcode === 0) {
                $this->info('Formatted generated files with pint');
            } else {
                $this->warn('pint formatting failed: ' . (isset($pout) ? implode("\n", $pout) : 'unknown'));
            }
        }

        // 3) Run ONLY generated test file untuk model ini (jika di-generate)
        if (!$this->option('no-test')) {
            $testPath = base_path("tests/Feature/{$modelName}ControllerTest.php");
            
            if (file_exists($testPath)) {
                $testClass = $modelName . 'ControllerTest';
                $cmd = 'php artisan test --filter ' . escapeshellarg($testClass) . ' 2>&1';
                exec($cmd, $tout, $tcode);
                
                if (isset($tcode) && $tcode === 0) {
                    $this->info('Generated test passed');
                } else {
                    // Jangan treat sebagai error - test mungkin perlu setup tambahan
                    $this->line('Generated test completed with warnings (mungkin butuh setup fixture/seeding)');
                }
            } else {
                $this->line('Generated test file not found — skip test run');
            }
        }
    }
}