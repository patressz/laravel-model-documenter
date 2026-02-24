<?php

declare(strict_types=1);

namespace Patressz\LaravelModelDocumenter;

use Closure;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Patressz\LaravelModelDocumenter\Resolvers\DatabaseColumnTypeResolver;
use Patressz\LaravelModelDocumenter\Resolvers\ModelCastTypeResolver;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Printer\Printer;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * @internal
 */
final readonly class DocBlockGenerator
{
    /**
     * Supported relation types.
     *
     * @var array<string, class-string>
     */
    private const array RELATION_TYPES = [
        'hasMany' => HasMany::class,
        'hasManyThrough' => HasManyThrough::class,
        'hasOneThrough' => HasOneThrough::class,
        'belongsToMany' => BelongsToMany::class,
        'hasOne' => HasOne::class,
        'belongsTo' => BelongsTo::class,
        'morphOne' => MorphOne::class,
        'morphTo' => MorphTo::class,
        'morphMany' => MorphMany::class,
        'morphToMany' => MorphToMany::class,
        'morphedByMany' => MorphToMany::class,
    ];

    /**
     * Create a new DocBlockGenerator instance.
     */
    public function __construct(
        private DatabaseColumnTypeResolver $typeResolver,
        private ModelCastTypeResolver $castResolver,
    ) {}

    /**
     * Generate DocBlock content for model properties.
     *
     * @param array<int, array{
     *     name: string,
     *     type_name: string,
     *     type: string,
     *     collation: ?string,
     *     nullable: bool,
     *     default: ?string,
     *     auto_increment: bool,
     *     comment: ?string,
     *     generation: ?string
     * }> $columns
     */
    public function generate(array $columns, string $className): string
    {
        $properties = [];

        /** @var Model $model */
        $model = app()->make($className);

        foreach ($columns as $column) {
            $type = $this->resolveColumnType($column, $model);
            $typeNode = new IdentifierTypeNode($type);

            if ($column['nullable']) {
                $typeNode = new NullableTypeNode($typeNode);
            }

            $propertyTag = new PropertyTagValueNode(
                $typeNode,
                sprintf('$%s', $column['name']),
                $column['comment'] ?? '',
            );

            $properties[] = new PhpDocTagNode('@property', $propertyTag);
        }

        foreach ($this->getRelations($model) as $relation) {
            $column = null;

            if ($relation['foreign_key'] && collect($columns)->contains('name', $relation['foreign_key'])) {
                $column = collect($columns)->firstWhere('name', $relation['foreign_key']);
            }

            if (($column && $column['nullable']) || ($relation['relation_class'] === HasOne::class || $relation['relation_class'] === MorphOne::class)) {
                $typeNode = new NullableTypeNode(new IdentifierTypeNode($relation['return']));
            } else {
                $typeNode = new IdentifierTypeNode($relation['return']);
            }

            $propertyTag = new PropertyTagValueNode(
                $typeNode,
                sprintf('$%s', $relation['name']),
                '',
            );

            $properties[] = new PhpDocTagNode('@property-read', $propertyTag);
        }

        if (in_array(Notifiable::class, class_uses($model, true))) {
            $typeNode = new IdentifierTypeNode('\Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification>');
            $propertyTag = new PropertyTagValueNode(
                $typeNode,
                '$notifications',
                '',
            );

            $properties[] = new PhpDocTagNode('@property-read', $propertyTag);
        }

        foreach ($this->getAccessors($model) as $accessor) {
            $typeNode = new IdentifierTypeNode($accessor['type']);
            $propertyTag = new PropertyTagValueNode(
                $typeNode,
                sprintf('$%s', $accessor['name']),
                '',
            );

            $name = match (sprintf('%d%d', (int) $accessor['readable'], (int) $accessor['writable'])) {
                '11' => '@property',
                '10' => '@property-read',
                '01' => '@property-write',
                default => '@property',
            };

            $properties[] = new PhpDocTagNode($name, $propertyTag);
        }

        if (count($this->getLocalScopes($model)) > 0) {
            $properties[] = new PhpDocTextNode(''); // Empty line for readability.
        }

        foreach ($this->getLocalScopes($model) as $scope) {
            $propertyTag = new PropertyTagValueNode(
                new IdentifierTypeNode('static'),
                sprintf('\Illuminate\Database\Eloquent\Builder<static>|%s %s()', $scope['model'], $scope['name']),
                '',
            );

            $properties[] = new PhpDocTagNode('@method', $propertyTag);
        }

        $phpDocNode = new PhpDocNode($properties);
        $printer = new Printer();

        return $printer->print($phpDocNode);
    }

    /**
     * Resolve column type (cast or database type).
     *
    /**
     * @param array{
     *     name: string,
     *     type_name: string,
     *     type: string,
     *     collation: null|string,
     *     nullable: bool,
     *     default: null|string,
     *     auto_increment: bool,
     *     comment: null|string,
     *     generation: null|string
     * } $column
     */
    private function resolveColumnType(array $column, Model $model): string
    {
        $casts = $model->getCasts();

        if (array_key_exists($column['name'], $casts)) {
            $reflectionClass = new ReflectionClass($model);
            $method = $reflectionClass->getMethod('getCastType');
            $method->setAccessible(true);

            /** @var string $castType */
            $castType = $method->invoke($model, $column['name']);

            return $this->castResolver->resolve($castType);
        }

        if (in_array($column['name'], ['created_at', 'updated_at'])) {
            return $this->castResolver->resolve('datetime');
        }

        return $this->typeResolver->resolve($column['type_name']);
    }

    /**
     * Get all relation methods from the model.
     *
     * @return array<int, array{relation_class: string, name: string, type: string, related: string, foreign_key: string, return: string}>
     */
    private function getRelations(Model $model): array
    {
        $relations = [];

        $reflectionClass = new ReflectionClass($model);

        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            $returnType = $reflectionMethod->getReturnType();

            if (! $returnType instanceof ReflectionNamedType) {
                continue;
            }

            if (in_array($returnType->getName(), self::RELATION_TYPES)) {
                $relationInfo = $this->analyzeRelationship($model, $reflectionMethod);

                if ($relationInfo) {
                    $relations[] = $relationInfo;
                }
            }
        }

        return $relations;
    }

    /**
     * Analyze relationship method to determine its type.
     *
     * @return array{relation_class: string, name: string, type: string, related: string, foreign_key: string, return: string}|null
     */
    private function analyzeRelationship(Model $model, ReflectionMethod $method): ?array
    {
        $relation = $method->invoke($model);

        if (! is_object($relation) || ! in_array($relation::class, self::RELATION_TYPES)) {
            return null;
        }

        $relationReflection = new ReflectionClass($relation);

        // Get the related model class
        $relatedProperty = $relationReflection->getProperty('related');
        $relatedProperty->setAccessible(true);
        $relatedModel = $relatedProperty->getValue($relation);

        $foreignKey = null;

        if ($relationReflection->hasProperty('foreignKey')) {
            $foreignProperty = $relationReflection->getProperty('foreignKey');
            $foreignProperty->setAccessible(true);
            $foreignKeyValue = $foreignProperty->getValue($relation);
            $foreignKey = is_string($foreignKeyValue) ? $foreignKeyValue : null;
        }

        if (! is_object($relatedModel) || ! class_exists($relatedModel::class)) {
            return null;
        }

        $relatedClassName = '\\'.$relatedModel::class;
        $returnType = $this->determineRelationshipReturnType($relation::class, $relatedClassName);

        return [
            'relation_class' => $relation::class,
            'name' => $method->getName(),
            'type' => $relatedClassName,
            'related' => $relatedClassName,
            'foreign_key' => $foreignKey ?? '',
            'return' => $returnType,
        ];
    }

    /**
     * Determine the correct return type for a relationship.
     */
    private function determineRelationshipReturnType(string $relationClass, string $relatedClass): string
    {
        return match ($relationClass) {
            HasMany::class,
            HasManyThrough::class,
            MorphMany::class,
            BelongsToMany::class,
            MorphToMany::class => sprintf('\Illuminate\Database\Eloquent\Collection<%s, %s>', 'int', $relatedClass),

            HasOne::class,
            HasOneThrough::class,
            BelongsTo::class,
            MorphOne::class => $relatedClass,

            MorphTo::class => '?\Illuminate\Database\Eloquent\Model',

            default => 'mixed',
        };
    }

    /**
     * Get model accessors with their types (read/write/read-write).
     *
     * @return array<int, array{name: string, type: string, readable: bool, writable: bool}>
     */
    public function getAccessors(Model $model): array
    {
        $accessors = [];
        $reflectionClass = new ReflectionClass($model);

        $method = $reflectionClass->getMethod('getMutatorMethods');
        $method->setAccessible(true);
        $oldAccessors = $method->invoke($model, $model);

        if (is_iterable($oldAccessors)) {
            foreach ($oldAccessors as $accessor) {
                if (is_string($accessor) && $accessor !== 'UseFactory') {
                    $accessors[] = [
                        'name' => lcfirst($accessor),
                        'type' => 'mixed',
                        'readable' => true,
                        'writable' => false, // old accessors are read-only.
                    ];
                }
            }
        }

        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            $returnType = $reflectionMethod->getReturnType();

            if ($returnType instanceof ReflectionNamedType && $returnType->getName() === Attribute::class) {
                $attributeInfo = $this->analyzeAttributeMethod($model, $reflectionMethod);

                if ($attributeInfo !== null) {
                    $accessors[] = $attributeInfo;
                }
            }
        }

        return $accessors;
    }

    /**
     * Analyze Attribute method to determine if it has get/set and return type.
     *
     * @return array{name: string, type: string, readable: bool, writable: bool}|null
     */
    private function analyzeAttributeMethod(Model $model, ReflectionMethod $method): ?array
    {
        $attribute = $method->invoke($model);

        if (! $attribute instanceof Attribute) {
            return null;
        }

        $attributeReflection = new ReflectionClass($attribute);

        $getProperty = $attributeReflection->getProperty('get');
        $getProperty->setAccessible(true);
        $getCallback = $getProperty->getValue($attribute);

        $setProperty = $attributeReflection->getProperty('set');
        $setProperty->setAccessible(true);
        $setCallback = $setProperty->getValue($attribute);

        $type = $this->determineAttributeType($getCallback);

        return [
            'name' => $method->getName(),
            'type' => $type,
            'readable' => $getCallback !== null,
            'writable' => $setCallback !== null,
        ];

    }

    /**
     * Determine type from get callback reflection.
     */
    private function determineAttributeType(mixed $getCallback): string
    {
        if ($getCallback === null) {
            return 'mixed';
        }

        if (! is_callable($getCallback)) {
            return 'mixed';
        }

        try {
            $callbackReflection = new ReflectionFunction(Closure::fromCallable($getCallback));
            $returnType = $callbackReflection->getReturnType();

            if ($returnType instanceof ReflectionNamedType) {
                $typeName = $returnType->getName();

                if (class_exists($typeName)) {
                    $typeName = '\\'.$typeName;
                }

                if ($returnType->allowsNull()) {
                    return sprintf('?%s', $typeName);
                }

                return $typeName;
            }
        } catch (ReflectionException) {
            return 'mixed';
        }

        return 'mixed';
    }

    /**
     * Get local scopes defined in the model.
     *
     * @return array<int, array{name: string, model: string}>
     */
    private function getLocalScopes(Model $model): array
    {
        $localScopes = [];
        $reflectionClass = new ReflectionClass($model);

        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            if (str_starts_with($reflectionMethod->getName(), 'scope')) {
                if ($reflectionClass->hasMethod($reflectionMethod->getName())) {
                    $localScopes[] = [
                        'name' => Str::of($reflectionMethod->getName())->after('scope')->lcfirst()->toString(),
                        'model' => class_basename($reflectionClass->getName()),
                    ];
                }
            }

            foreach ($reflectionMethod->getAttributes() as $attribute) {
                if ($attribute->getName() === 'Illuminate\Database\Eloquent\Attributes\Scope') {
                    $methodName = $reflectionMethod->getName();

                    if (! in_array($methodName, array_column($localScopes, 'name'))) {
                        $localScopes[] = [
                            'name' => $methodName,
                            'model' => class_basename($reflectionClass->getName()),
                        ];
                    }
                }
            }
        }

        return $localScopes;
    }
}
