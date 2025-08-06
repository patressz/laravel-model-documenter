<?php

declare(strict_types=1);

namespace Patressz\LaravelModelDocumenter;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;
use Throwable;

/**
 * @internal
 */
final class ModelFinder
{
    /**
     * Find all model classes in the specified directory.
     *
     * @return array<int, array{class: string, file: string, filename: string}>
     */
    public function findModels(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $models = [];

        $files = Finder::create()
            ->files()
            ->in($directory)
            ->name('*.php')
            ->getIterator();

        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file->getRealPath());

            if ($className !== null && $className !== '' && $className !== '0') {
                $models[] = [
                    'class' => $className,
                    'file' => $file->getRealPath(),
                    'filename' => $file->getFilename(),
                ];
            }
        }

        return $models;
    }

    /**
     * Extract fully qualified class name from a PHP file.
     */
    private function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        $parser = (new ParserFactory)->createForHostVersion();

        if ($content === false) {
            return null;
        }

        try {
            $ast = $parser->parse($content);
        } catch (Throwable) {
            return null;
        }

        if ($ast === null) {
            return null;
        }

        $visitor = new class extends NodeVisitorAbstract
        {
            public ?string $namespace = null;

            public ?string $className = null;

            public function enterNode(Node $node): int|Node|array|null
            {
                if ($node instanceof Node\Stmt\Namespace_) {
                    $this->namespace = $node->name?->toString();
                }

                if ($node instanceof Node\Stmt\Class_) {
                    $this->className = $node->name?->toString();
                }

                return null;
            }
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        if ($visitor->className === null || $visitor->className === '' || $visitor->className === '0') {
            return null;
        }

        return $visitor->namespace !== null && $visitor->namespace !== '' && $visitor->namespace !== '0'
            ? $visitor->namespace.'\\'.$visitor->className
            : $visitor->className;
    }
}
