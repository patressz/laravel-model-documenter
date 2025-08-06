<?php

declare(strict_types=1);

namespace Patressz\LaravelModelDocumenter;

use PhpParser\Comment\Doc;
use PhpParser\Lexer\Emulative;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use RuntimeException;

/**
 * @internal
 */
final class FileUpdater
{
    /**
     * Update DocBlock in PHP file with format preservation.
     */
    public function updateDocBlock(string $filePath, string $docBlockContent): void
    {
        $lexer = new Emulative();
        $parser = (new ParserFactory)->createForHostVersion();

        $code = file_get_contents($filePath);

        if ($code === false) {
            throw new RuntimeException("Could not read file: {$filePath}");
        }

        $originalTokens = $lexer->tokenize($code);
        $originalStmts = $parser->parse($code);

        if ($originalStmts === null) {
            throw new RuntimeException("Could not parse file: {$filePath}");
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new CloningVisitor());
        $stmts = $traverser->traverse($originalStmts);

        $traverser->addVisitor(new class($docBlockContent) extends NodeVisitorAbstract
        {
            public function __construct(
                private readonly string $docBlockContent
            ) {}

            public function enterNode(Node $node): Node
            {
                if ($node instanceof Node\Stmt\Class_) {
                    $docComment = new Doc($this->docBlockContent);
                    $node->setDocComment($docComment);
                }

                return $node;
            }
        });

        $modifiedStmts = $traverser->traverse($stmts);

        $prettyPrinter = new Standard();
        $newCode = $prettyPrinter->printFormatPreserving($modifiedStmts, $originalStmts, $originalTokens);

        file_put_contents($filePath, $newCode);
    }
}
