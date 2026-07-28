<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\StubReplacer;
use Illuminate\Support\Str;

class MakeResourceCrud extends Command
{
    protected $signature = 'make:resource-crud {name}
        {--fields=}
        {--relations=}
        {--primary-key=}
        {--pattern=}
        {--no-export}
        {--no-policy}
        {--no-test}
        {--force}';

    protected $description = 'Generate a set of CRUD files from stubs according to scaffold pattern';

    public function handle(StubReplacer $replacer): int
    {
        $name = $this->argument('name');
        $flagPattern = $this->option('pattern');
        $active = config('scaffold.pattern');

        if ($flagPattern) {
            if ($flagPattern !== $active) {
                $this->error("Project ini dikunci ke pattern '{$active}' (lihat SCAFFOLD_PATTERN di .env). Generate dengan pattern lain akan membuat modul tidak konsisten. Untuk beralih pattern project-wide, ubah SCAFFOLD_PATTERN di .env — perhatikan bahwa file yang sudah di-generate SEBELUMNYA tidak akan otomatis dikonversi ke pattern baru.");
                return self::FAILURE;
            }
        }

        $patternConfig = config("scaffold.patterns.{$active}");
        if (!$patternConfig) {
            $this->error('Scaffold pattern config not found.');
            return self::FAILURE;
        }

        $files = $patternConfig['files'] ?? [];
        $skip = [];
        if ($this->option('no-export')) $skip[] = 'export';
        if ($this->option('no-policy')) $skip[] = 'policy';
        if ($this->option('no-test')) $skip[] = 'test';

        // derive replacements
        $derived = StubReplacer::deriveNames($name);
        $derived['primaryKey'] = $this->option('primary-key') ?: config('scaffold.primary_key');

        // simple parsing of fields to produce validationRules placeholders (very basic)
        $fields = $this->option('fields') ?: '';
        $validationRules = [];
        if (!empty($fields)) {
            $parts = explode(',', $fields);
            foreach ($parts as $p) {
                $bits = explode(':', $p);
                $fname = $bits[0];
                $rules = $bits[2] ?? ($bits[1] ?? '');
                $validationRules[] = "'{$fname}' => '{$rules}'";
            }
        }

        $derived['validationRules'] = implode(",\n            ", $validationRules) ?: "'name' => 'required'";
        $derived['validationRulesUpdate'] = str_replace("required", "sometimes", $derived['validationRules']);
        $derived['exportHeadings'] = "'id', 'name'";
        $derived['exportMappings'] = "\$this->id, \$this->name";

        $stubBase = base_path('stubs/' . $patternConfig['stub_path']);

        foreach ($files as $key => $destTemplate) {
            if (in_array($key, $skip, true)) {
                continue;
            }

            $stubPath = $stubBase . '/' . $key . '.stub';
            $destination = str_replace('{Model}', $name, $destTemplate);
            try {
                $contents = $replacer->replace($stubPath, $derived);
                $replacer->write($contents, base_path($destination), $this->option('force'));
                $this->info("Generated: {$destination}");
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        // Post-generate: register policy (idempotent)
        if (!in_array('policy', $skip, true)) {
            $this->registerPolicy($name);
        }

        // Post-generate: if repository pattern, register binding
        if ($active === 'repository') {
            $this->registerRepositoryBinding($name);
        }

        $this->line('Reminder: add resource route to routes/api.php and create migration/factory manually.');

        return self::SUCCESS;
    }

    protected function registerPolicy(string $modelName): void
    {
        $providerPath = app_path('Providers/AuthServiceProvider.php');
        $mapping = "{$modelName}::class => {$modelName}Policy::class";

        if (!file_exists($providerPath)) {
            // create a basic AuthServiceProvider
            $content = "<?php\n\nnamespace App\\Providers;\n\nuse Illuminate\\Support\\ServiceProvider;\n\nclass AuthServiceProvider extends ServiceProvider\n{\n    protected \$policies = [\n        // Model => Policy mapping\n    ];\n\n    public function boot(): void\n    {\n        \n    }\n}\n";
            file_put_contents($providerPath, $content);
        }

        $content = file_get_contents($providerPath);
        if (strpos($content, $mapping) === false) {
            // insert mapping into $policies array
            $content = preg_replace('/protected \$policies = \[\n(.*?)\];/s', "protected \\\$policies = [\n    // Model => Policy mapping\n    {$mapping},\n];", $content, 1);
            file_put_contents($providerPath, $content);
            $this->info("Registered policy mapping in AuthServiceProvider: {$mapping}");
        } else {
            $this->info('Policy mapping already exists');
        }
    }

    protected function registerRepositoryBinding(string $modelName): void
    {
        $providerPath = app_path('Providers/AppServiceProvider.php');
        $bindLine = "\$this->app->bind(\App\\Repositories\\Contracts\\{$modelName}RepositoryInterface::class, \App\\Repositories\\Eloquent{$modelName}Repository::class);";

        $content = file_get_contents($providerPath);
        if (strpos($content, $bindLine) === false) {
            // insert into register() method
            $content = preg_replace('/public function register\(\): void\n\s*\{\n\s*\}\n/s', "public function register(): void\n    {\n        {$bindLine}\n    }\n", $content, 1);
            file_put_contents($providerPath, $content);
            $this->info("Registered repository binding in AppServiceProvider");
        } else {
            $this->info('Repository binding already exists');
        }
    }
}
