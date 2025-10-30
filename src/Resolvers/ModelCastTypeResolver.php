<?php

declare(strict_types=1);

namespace Patressz\LaravelModelDocumenter\Resolvers;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

/**
 * @internal
 */
final class ModelCastTypeResolver
{
    /**
     * Resolve the type of a casted property.
     */
    public function resolve(string $castType): string
    {
        $regexes = [
            [
                'class_name' => '\Illuminate\Support\Collection',
                'regex' => 'Illuminate\Database\Eloquent\Casts\AsCollection',
            ],
            [
                'class_name' => '\Illuminate\Support\Collection',
                'regex' => 'Illuminate\Database\Eloquent\Casts\AsEnumCollection',
            ],
            [
                'class_name' => '\ArrayObject',
                'regex' => 'Illuminate\Database\Eloquent\Casts\AsEnumArrayObject',
            ],
        ];

        foreach ($regexes as $item) {
            $quotedRegex = preg_quote($item['regex'], '/');

            if (preg_match("/(?<={$quotedRegex}:).+/i", $castType, $matches)) {
                if (count($matches) === 0) {
                    continue;
                }

                $castType = Str::of($matches[0])
                    ->whenStartsWith(',', fn (Stringable $value) => $value->trim(','))
                    ->whenEndsWith(',', fn (Stringable $value) => $value->trim(','))
                    ->explode('\\')
                    ->map(fn (string $part) => ucfirst($part))
                    ->implode('\\');

                return sprintf('%s<int, \%s>', $item['class_name'], $castType);
            }
        }

        return match ($castType) {
            'int', 'integer' => 'int',
            'real', 'float', 'double', 'decimal' => 'float',
            'string' => 'string',
            'bool', 'boolean' => 'bool',
            'object' => 'object',
            'array', 'json', 'json:unicode' => 'array<array-key, mixed>',
            'collection' => '\Illuminate\Support\Collection<array-key, mixed>',
            'date', 'datetime', 'custom_datetime', 'timestamp' => '\Illuminate\Support\Carbon',
            'immutable_date', 'immutable_datetime', 'immutable_custom_datetime' => '\Carbon\CarbonImmutable',
            'hashed', 'encrypted' => 'string',
            'encrypted:array', 'encrypted:json' => 'array',
            'encrypted:object' => 'object',
            'encrypted:collection' => '\Illuminate\Support\Collection',
            AsStringable::class => '\Illuminate\Support\Stringable',
            AsArrayObject::class => '\ArrayObject',
            AsCollection::class => '\Illuminate\Support\Collection',
            AsEncryptedArrayObject::class => '\ArrayObject',
            AsEncryptedCollection::class => '\Illuminate\Support\Collection',
            default => $this->handleCustomType($castType),
        };
    }

    /**
     * Handle custom cast types (classes, enums, etc.)
     */
    private function handleCustomType(string $castType): string
    {
        if ($this->isClassType($castType)) {
            return $this->normalizeClassName($castType);
        }

        return 'mixed';
    }

    /**
     * Check if the cast type is a class, enum, or interface.
     */
    private function isClassType(string $castType): bool
    {
        return class_exists($castType) || enum_exists($castType) || interface_exists($castType);
    }

    /**
     * Normalize class names to ensure they are fully qualified.
     */
    private function normalizeClassName(string $className): string
    {
        if (str_starts_with($className, '\\')) {
            return $className;
        }

        if (! str_contains($className, '\\')) {
            return $className;
        }

        return '\\'.$className;
    }
}
