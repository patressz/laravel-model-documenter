<?php

declare(strict_types=1);

namespace Patressz\LaravelModelDocumenter;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Throwable;

/**
 * @internal
 */
final readonly class ModelDocumenter
{
    /**
     * Create a new ModelDocumenter instance.
     */
    public function __construct(
        private ModelFinder $modelFinder,
        private DocBlockGenerator $docBlockGenerator,
        private FileUpdater $fileUpdater,
    ) {}

    /**
     * Generate documentation for all models in the specified directory.
     *
     * @return Collection<int, array{success: bool, class: string, error?: string}>
     */
    public function generateForDirectory(string $directory): Collection
    {
        $results = collect();

        $models = $this->modelFinder->findModels($directory);

        foreach ($models as $modelData) {
            $result = $this->generateForModel($modelData['class'], $modelData['file']);
            $results->push($result);
        }

        return $results;
    }

    /**
     * Generate documentation for a single model class.
     *
     * @return array{success: bool, class: string, error?: string}
     */
    public function generateForModel(string $className, string $filePath): array
    {
        if (! class_exists($className)) {
            return [
                'success' => false,
                'class' => $className,
                'error' => 'Class not found.',
            ];
        }

        try {
            $reflectionClass = new ReflectionClass($className);

            if (! $reflectionClass->isInstantiable()) {
                return [
                    'success' => false,
                    'class' => $className,
                    'error' => 'Class is not instantiable.',
                ];
            }

            $modelInstance = $reflectionClass->newInstance();

            if (! $modelInstance instanceof Model) {
                return [
                    'success' => false,
                    'class' => $className,
                    'error' => 'Class is not an Eloquent model.',
                ];
            }

            $connectionName = $modelInstance->getConnectionName();

            if (is_string($connectionName)) {
                /**
                 * @var array<int, array{
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
                $columns = Schema::connection($connectionName)->getColumns($modelInstance->getTable());
            } else {
                /**
                 * @var array<int, array{
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
                $columns = Schema::getColumns($modelInstance->getTable());
            }

            $docBlock = $this->docBlockGenerator->generate($columns, $className);

            $this->fileUpdater->updateDocBlock($filePath, $docBlock);

            return [
                'success' => true,
                'class' => $className,
            ];

        } catch (Throwable $e) {
            return [
                'success' => false,
                'class' => $className,
                'error' => $e->getMessage(),
            ];
        }
    }
}
