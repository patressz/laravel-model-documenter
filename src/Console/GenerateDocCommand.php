<?php

declare(strict_types=1);

namespace Patressz\LaravelModelDocumenter\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Patressz\LaravelModelDocumenter\DocBlockGenerator;
use Patressz\LaravelModelDocumenter\ModelDocumenter;
use Patressz\LaravelModelDocumenter\ModelFinder;
use ReflectionClass;

final class GenerateDocCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'model-doc:generate 
                            {--path= : Custom path to models directory}
                            {--model= : Specific model class to generate documentation for}
                            {--test : Compare existing PHPDoc with expected documentation}
                            {--ci : Run in CI mode (silent test with exit codes)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate PHPDoc blocks for Eloquent models';

    public function __construct(
        private readonly DocBlockGenerator $docBlockGenerator,
        private readonly ModelFinder $modelFinder,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(ModelDocumenter $documenter): void
    {
        $modelClass = $this->option('model');
        $testMode = $this->option('test');
        $ciMode = $this->option('ci');

        // CI mode implies test mode
        if ($ciMode) {
            $testMode = true;
        }

        if ($modelClass) {
            $this->handleSingleModel($documenter, $modelClass, $testMode, $ciMode);

            return;
        }

        $this->handleDirectory($documenter, $testMode, $ciMode);
    }

    /**
     * Handle documentation generation for a single model.
     */
    private function handleSingleModel(ModelDocumenter $documenter, string $modelClass, bool $testMode = false, bool $ciMode = false): void
    {
        if (! class_exists($modelClass)) {
            $this->error("Model class not found: {$modelClass}");

            return;
        }

        $reflection = new ReflectionClass($modelClass);
        $filePath = $reflection->getFileName();

        if (! $filePath) {
            $this->error("Could not determine file path for model: {$modelClass}");

            return;
        }

        if ($testMode) {
            $this->testModel($modelClass, $ciMode);
        } else {
            $this->info("Generating documentation for model: {$modelClass}");

            $result = $documenter->generateForModel($modelClass, $filePath);

            if ($result['success']) {
                $this->info("✓ Successfully processed {$result['class']}.");
            } else {
                $this->error("✗ Failed to process {$result['class']}: {$result['error']}");
            }
        }
    }

    /**
     * Handle documentation generation for a directory of models.
     */
    private function handleDirectory(ModelDocumenter $documenter, bool $testMode = false, bool $ciMode = false): void
    {
        $path = $this->option('path') ?: app_path('Models');

        if (! is_dir($path)) {
            $this->error("Directory not found: {$path}");

            return;
        }

        if ($testMode) {
            if (! $ciMode) {
                $this->info("Testing documentation for models in: {$path}");
            }
            $this->testDirectory($path, $ciMode);
        } else {
            $this->info("Generating documentation for models in: {$path}");

            $results = $documenter->generateForDirectory($path);

            $successful = $results->where('success', true);
            $failed = $results->where('success', false);

            $this->info("Successfully processed {$successful->count()} models");

            if ($failed->isNotEmpty()) {
                $this->warn("Failed to process {$failed->count()} models:");

                foreach ($failed as $result) {
                    $this->error("  - {$result['class']}: {$result['error']}");
                }
            }

            foreach ($successful as $result) {
                $this->line("  ✓ {$result['class']}.");
            }
        }
    }

    /**
     * Test documentation for a single model.
     */
    private function testModel(string $modelClass, bool $ciMode = false): void
    {
        if (! $ciMode) {
            $this->info("Testing documentation for model: {$modelClass}");
        }

        $currentDocBlock = $this->extractCurrentDocBlock($modelClass);
        $expectedDocBlock = $this->getExpectedDocBlock($modelClass);

        if ($currentDocBlock === null) {
            if (! $ciMode) {
                $this->warn('No existing PHPDoc block found');
            }
            $currentDocBlock = '';
        }

        $isUpToDate = $this->isDocumentationUpToDate($currentDocBlock, $expectedDocBlock);

        if ($ciMode) {
            if (! $isUpToDate) {
                $this->error("PHPDoc outdated: {$modelClass}");
                exit(1);
            }
        } else {
            $this->displayComparison($currentDocBlock, $expectedDocBlock, $modelClass);
        }
    }

    /**
     * Test documentation for all models in directory.
     */
    private function testDirectory(string $path, bool $ciMode = false): void
    {
        $models = $this->modelFinder->findModels($path);
        $outdatedModels = [];

        foreach ($models as $modelData) {
            $modelClass = $modelData['class'];

            $currentDocBlock = $this->extractCurrentDocBlock($modelClass);
            $expectedDocBlock = $this->getExpectedDocBlock($modelClass);

            if ($currentDocBlock === null) {
                $currentDocBlock = '';
            }

            $isUpToDate = $this->isDocumentationUpToDate($currentDocBlock, $expectedDocBlock);

            if ($ciMode) {
                if (! $isUpToDate) {
                    $outdatedModels[] = $modelClass;
                }
            } else {
                $this->testModel($modelClass, false);
                $this->newLine();
            }
        }

        if ($ciMode && ! empty($outdatedModels)) {
            $this->error('PHPDoc outdated in '.count($outdatedModels).' model(s):');
            foreach ($outdatedModels as $model) {
                $this->line("  - {$model}");
            }
            exit(1);
        }

        if ($ciMode && empty($outdatedModels)) {
            $this->info('All PHPDoc blocks are up to date ✓');
        }
    }

    /**
     * Extract current PHPDoc block from model class using Reflection.
     */
    private function extractCurrentDocBlock(string $modelClass): ?string
    {
        $reflection = new ReflectionClass($modelClass);
        $docComment = $reflection->getDocComment();

        if ($docComment !== false) {
            return $docComment;
        }

        return null;
    }

    /**
     * Check if documentation is up to date.
     */
    private function isDocumentationUpToDate(string $current, string $expected): bool
    {
        $currentLines = explode("\n", mb_trim($current));
        $expectedLines = explode("\n", mb_trim($expected));

        return $currentLines === $expectedLines;
    }

    /**
     * Get expected PHPDoc block from documenter.
     */
    private function getExpectedDocBlock(string $modelClass): string
    {
        $model = app()->make($modelClass);
        $columns = Schema::getColumns($model->getTable());

        return $this->docBlockGenerator->generate($columns, $modelClass);
    }

    /**
     * Display comparison between current and expected documentation.
     */
    private function displayComparison(string $current, string $expected, string $modelClass): void
    {
        $currentLines = explode("\n", mb_trim($current));
        $expectedLines = explode("\n", mb_trim($expected));

        $this->line("<comment>Comparison for {$modelClass}:</comment>");
        $this->line('<comment>'.str_repeat('=', 80).'</comment>');

        // Calculate diff using longest common subsequence algorithm
        $diff = $this->calculateDiff($currentLines, $expectedLines);

        $allMatch = true;

        foreach ($diff as $operation) {
            switch ($operation['type']) {
                case 'equal':
                    $this->line("<fg=white;>  ✓ {$operation['line']}</fg=white;>");
                    break;
                case 'delete':
                    $allMatch = false;
                    $this->line("<fg=red;>  - {$operation['line']}</fg=red;>");
                    break;
                case 'insert':
                    $allMatch = false;
                    $this->line("<fg=green;>  + {$operation['line']}</fg=green;>");
                    break;
            }
        }

        if ($allMatch) {
            $this->line('<fg=green>🎉 PHPDoc is up to date!</fg=green>');
        } else {
            $this->line('<fg=red>❌ PHPDoc needs to be updated</fg=red>');
        }
    }

    /**
     * Calculate diff between two arrays of lines using LCS algorithm.
     */
    private function calculateDiff(array $currentLines, array $expectedLines): array
    {
        $lcs = $this->longestCommonSubsequence($currentLines, $expectedLines);

        $diff = [];
        $i = 0; // current lines pointer
        $j = 0; // expected lines pointer
        $k = 0; // lcs pointer

        while ($i < count($currentLines) || $j < count($expectedLines)) {
            if ($k < count($lcs) &&
                isset($currentLines[$i]) &&
                isset($expectedLines[$j]) &&
                $currentLines[$i] === $expectedLines[$j] &&
                $currentLines[$i] === $lcs[$k]) {

                // Lines match - they are part of LCS
                $diff[] = ['type' => 'equal', 'line' => $currentLines[$i]];
                $i++;
                $j++;
                $k++;
            } elseif ($k < count($lcs) &&
                     isset($expectedLines[$j]) &&
                     $expectedLines[$j] === $lcs[$k]) {

                // Current line should be deleted
                if (isset($currentLines[$i])) {
                    $diff[] = ['type' => 'delete', 'line' => $currentLines[$i]];
                    $i++;
                } else {
                    // Insert expected line
                    $diff[] = ['type' => 'insert', 'line' => $expectedLines[$j]];
                    $j++;
                }
            } elseif ($k < count($lcs) &&
                     isset($currentLines[$i]) &&
                     $currentLines[$i] === $lcs[$k]) {

                // Expected line should be inserted
                $diff[] = ['type' => 'insert', 'line' => $expectedLines[$j]];
                $j++;
            } else {
                // Neither line is in LCS
                if (isset($currentLines[$i])) {
                    $diff[] = ['type' => 'delete', 'line' => $currentLines[$i]];
                    $i++;
                }
                if (isset($expectedLines[$j])) {
                    $diff[] = ['type' => 'insert', 'line' => $expectedLines[$j]];
                    $j++;
                }
            }
        }

        return $diff;
    }

    /**
     * Find longest common subsequence between two arrays.
     */
    private function longestCommonSubsequence(array $a, array $b): array
    {
        $m = count($a);
        $n = count($b);

        // Create LCS table
        $lcs = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));

        // Build LCS table
        for ($i = 1; $i <= $m; $i++) {
            for ($j = 1; $j <= $n; $j++) {
                if ($a[$i - 1] === $b[$j - 1]) {
                    $lcs[$i][$j] = $lcs[$i - 1][$j - 1] + 1;
                } else {
                    $lcs[$i][$j] = max($lcs[$i - 1][$j], $lcs[$i][$j - 1]);
                }
            }
        }

        // Reconstruct LCS
        $result = [];
        $i = $m;
        $j = $n;

        while ($i > 0 && $j > 0) {
            if ($a[$i - 1] === $b[$j - 1]) {
                array_unshift($result, $a[$i - 1]);
                $i--;
                $j--;
            } elseif ($lcs[$i - 1][$j] > $lcs[$i][$j - 1]) {
                $i--;
            } else {
                $j--;
            }
        }

        return $result;
    }
}
