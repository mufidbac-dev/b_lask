<?php

namespace App\Support;

class RelationDefinitionParser
{
    private const SUPPORTED_TYPES = [
        'belongsTo', 'hasOne', 'hasMany', 'belongsToMany',
    ];

    /**
     * @return array<int, array{name: string, type: string}>
     */
    public function parse(?string $relationsOption): array
    {
        if (blank($relationsOption)) {
            return [];
        }

        $relations = [];

        foreach (explode(',', $relationsOption) as $definition) {
            [$name, $type] = array_pad(explode(':', trim($definition), 2), 2, null);

            if (!$name || !$type) {
                throw new \InvalidArgumentException(
                    "Format --relations tidak valid: '{$definition}'. Gunakan name:type"
                );
            }

            if (!in_array($type, self::SUPPORTED_TYPES, true)) {
                throw new \InvalidArgumentException(
                    "Tipe relasi '{$type}' tidak didukung. Pilihan: " . implode(', ', self::SUPPORTED_TYPES)
                );
            }

            $relations[] = ['name' => trim($name), 'type' => $type];
        }

        return $relations;
    }

    public function toRelationshipMethods(array $relations): string
    {
        $methods = array_map(function (array $r) {
            $methodName = $r['name'];
            $relatedModel = \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($r['name']));

            return <<<PHP
                // TODO: pastikan related model App\\Models\\{$relatedModel} sudah benar
                public function {$methodName}()
                {
                    return \$this->{$r['type']}(\\App\\Models\\{$relatedModel}::class);
                }
            PHP;
        }, $relations);

        return implode("\n\n", $methods);
    }
}