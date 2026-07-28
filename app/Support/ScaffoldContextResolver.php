<?php

namespace App\Support;

use Illuminate\Support\Str;


class ScaffoldContextResolver
{
    public function resolve(string $model): array {
        $variable = Str::camel($model);
        return [
            'modelName' => $model,
            'modelVariable' => $variable,
            'modelVariablePlural' => Str::plural($variable),
            'modelKebabPlural'=> Str::kebab(Str::plural($model)),
            'tableName' => Str::snake(Str::plural($model)),
            'permissionPrefix'=> Str::kebab(Str::plural($model)),
            'modelNamespace'=> $this->resolveModelNamespace(),
            'namespace'=> app()->getNamespace(),
        ];
    }



    private function resolveModelNamespace(): string
    {

        return app()->getNamespace()
            .'Models';
    }

}