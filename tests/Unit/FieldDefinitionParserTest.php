<?php

use App\Support\FieldDefinitionParser;

it('parses fields and derives validation rules for update with sometimes', function () {
    $parser = new FieldDefinitionParser();
    $fields = $parser->parse('name:string:required|max:255,price:integer:required|numeric');

    expect($fields)->toHaveCount(2);

    $updateRules = $parser->toValidationRulesForUpdate($fields);

    expect($updateRules)
        ->toContain("'name' => 'sometimes|max:255',")
        ->toContain("'price' => 'sometimes|numeric',");
});

it('throws on invalid fields format', function () {
    $parser = new FieldDefinitionParser();
    $parser->parse('name');
})->throws(InvalidArgumentException::class);