<?php

namespace App\Support;

use Illuminate\Support\Str;

class FieldDefinitionParser
{
    /**
     * @return array<int, array{name: string, type: string, rules: string[]}>
     */
    public function parse(?string $fieldsOption): array
    {
        if (blank($fieldsOption)) {
            return [];
        }

        $fields = [];

        foreach (explode(',', $fieldsOption) as $definition) {
            $parts = explode(':', trim($definition), 3);

            if (count($parts) < 2) {
                throw new \InvalidArgumentException(
                    "Format --fields tidak valid: '{$definition}'. Gunakan name:type:rules"
                );
            }

            [$name, $type] = $parts;
            $rules = isset($parts[2]) && $parts[2] !== ''
                ? explode('|', $parts[2])
                : [];

            $fields[] = [
                'name' => trim($name),
                'type' => trim($type),
                'rules' => array_map('trim', $rules),
            ];
        }

        return $fields;
    }

    public function toValidationRules(array $fields): string
    {
        $lines = array_map(
            fn (array $f) => "            '{$f['name']}' => '" . implode('|', $f['rules']) . "',",
            $fields
        );

        return implode("\n", $lines);
    }

    public function toValidationRulesForUpdate(array $fields): string
    {
        $lines = array_map(function (array $f) {
            $rules = $f['rules'];

            $rules = in_array('required', $rules, true)
                ? array_map(fn ($r) => $r === 'required' ? 'sometimes' : $r, $rules)
                : array_merge(['sometimes'], $rules);

            return "            '{$f['name']}' => '" . implode('|', $rules) . "',";
        }, $fields);

        return implode("\n", $lines);
    }

    public function toResourceFields(array $fields): string
    {
        $lines = array_map(
            fn (array $f) => "            '{$f['name']}' => \$this->{$f['name']},",
            $fields
        );

        return implode("\n", $lines);
    }

    public function toExportHeadings(array $fields): string
    {
        $lines = array_map(
            fn (array $f) => "            '" . Str::headline($f['name']) . "',",
            $fields
        );

        return implode("\n", $lines);
    }

    public function toExportMappings(array $fields, string $modelVariable): string
    {
        $lines = array_map(
            fn (array $f) => "            \${$modelVariable}->{$f['name']},",
            $fields
        );

        return implode("\n", $lines);
    }
}