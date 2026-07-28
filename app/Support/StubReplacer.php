<?php

namespace App\Support;

use Illuminate\Support\Str;

class StubReplacer
{
    public function replace(string $stubPath, array $replacements): string
    {
        if (!file_exists($stubPath)) {
            throw new \RuntimeException("Stub file not found: {$stubPath}");
        }

        $contents = file_get_contents($stubPath);

        foreach ($replacements as $key => $value) {
            $contents = str_replace(
                [
                    "{{ {$key} }}",
                    "{{{$key}}}",
                ],
                $value,
                $contents
            );
        }

        // Validate unresolved placeholders
        preg_match_all('/{{\s*([^}]+)\s*}}/', $contents, $matches);

        if (!empty($matches[1])) {
            $left = array_map(
                fn($item) => trim($item),
                array_unique($matches[1])
            );

            throw new \RuntimeException(
                'Unresolved placeholders in stub: ' . implode(', ', $left)
            );
        }

        return $contents;
    }

    public function write(string $contents, string $destinationPath, bool $force = false): void
    {
        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new \RuntimeException(
                    "Failed to create directories: {$dir}"
                );
            }
        }

        if (file_exists($destinationPath) && !$force) {
            throw new \RuntimeException(
                "File already exists: {$destinationPath}"
            );
        }

        file_put_contents($destinationPath, $contents);
    }


    public static function deriveNames(string $modelName): array
    {
        $modelVariable = Str::camel($modelName);
        $modelVariablePlural = Str::plural($modelVariable);
        $modelKebabPlural = Str::kebab(Str::plural($modelName));

        return [
            'modelName' => $modelName,
            'modelVariable' => $modelVariable,
            'modelVariablePlural' => $modelVariablePlural,
            'modelKebabPlural' => $modelKebabPlural,
            'tableName' => Str::snake(Str::plural($modelName)),

            'modelNamespace' => 'App\\Models',
            'namespace' => 'App',
            'permissionPrefix' => $modelKebabPlural,
        ];
    }
}
