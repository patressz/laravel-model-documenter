<?php

declare(strict_types=1);

namespace Patressz\LaravelModelDocumenter;

use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use ReflectionClass;

/**
 * @internal
 */
final readonly class DocBlockComparator
{
    /**
     * Determine whether the current PHPDoc matches the expected one, treating imported and fully qualified class names as equal.
     *
     * @param  class-string  $modelClass
     */
    public function matches(string $current, string $expected, string $modelClass): bool
    {
        return $this->normalize($current, $modelClass) === $this->normalize($expected, $modelClass);
    }

    /**
     * Expand class names in the @property and @method types of a PHPDoc to fully qualified names.
     *
     * @param  class-string  $modelClass
     */
    public function normalize(string $docBlock, string $modelClass): string
    {
        $imports = $this->imports($modelClass);
        $namespace = (new ReflectionClass($modelClass))->getNamespaceName();

        $lines = array_map(
            fn (string $line): string => $this->normalizeLine(mb_rtrim($line), $imports, $namespace),
            explode("\n", str_replace(["\r\n", "\r"], "\n", mb_trim($docBlock))),
        );

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, string>  $imports
     */
    private function normalizeLine(string $line, array $imports, string $namespace): string
    {
        if (preg_match('/^(\s*\*\s*@property(?:-read|-write)?\s+)(.+?)(\s+\$\w+.*)$/', $line, $matches) === 1) {
            return $matches[1].$this->expandType($matches[2], $imports, $namespace).$matches[3];
        }

        if (preg_match('/^(\s*\*\s*@method\s+(?:static\s+)?)(.+?)(\s+\w+\(.*)$/', $line, $matches) === 1) {
            return $matches[1].$this->expandType($matches[2], $imports, $namespace).$matches[3];
        }

        return $line;
    }

    /**
     * @param  array<string, string>  $imports
     */
    private function expandType(string $type, array $imports, string $namespace): string
    {
        return (string) preg_replace_callback(
            '/(?<![\w\\\\$-])\\\\?[A-Za-z_]\w*(?:\\\\[A-Za-z_]\w*)*(?![\w-])/',
            fn (array $matches): string => $this->expandName($matches[0], $imports, $namespace),
            $type,
        );
    }

    /**
     * @param  array<string, string>  $imports
     */
    private function expandName(string $name, array $imports, string $namespace): string
    {
        if (str_starts_with($name, '\\')) {
            return $name;
        }

        $segments = explode('\\', $name);
        $alias = mb_strtolower($segments[0]);

        if (array_key_exists($alias, $imports)) {
            $segments[0] = $imports[$alias];

            return '\\'.implode('\\', $segments);
        }

        $namespacedName = mb_ltrim($namespace.'\\'.$name, '\\');

        if ($this->typeExists($namespacedName)) {
            return '\\'.$namespacedName;
        }

        return $name;
    }

    private function typeExists(string $name): bool
    {
        return class_exists($name) || interface_exists($name) || enum_exists($name);
    }

    /**
     * Get the class imports of the model file keyed by their lowercase alias.
     *
     * @param  class-string  $modelClass
     * @return array<string, string>
     */
    private function imports(string $modelClass): array
    {
        $fileName = (new ReflectionClass($modelClass))->getFileName();
        $code = is_string($fileName) ? (string) file_get_contents($fileName) : '';

        $nodes = (new ParserFactory)->createForHostVersion()->parse($code) ?? [];
        $imports = [];

        /** @var list<Use_> $uses */
        $uses = (new NodeFinder)->find($nodes, fn (Node $node): bool => $node instanceof Use_ && $node->type === Use_::TYPE_NORMAL);

        foreach ($uses as $use) {
            foreach ($use->uses as $useItem) {
                $imports[mb_strtolower($useItem->getAlias()->toString())] = $useItem->name->toString();
            }
        }

        return $imports;
    }
}
