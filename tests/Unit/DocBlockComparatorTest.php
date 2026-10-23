<?php

declare(strict_types=1);

use Patressz\LaravelModelDocumenter\DocBlockComparator;
use Workbench\App\Models\ImportedTypesModel;

describe('DocBlockComparator', function (): void {
    it('treats imported, aliased and same-namespace class names as their fully qualified names', function (): void {
        $current = <<<'EOD'
        /**
         * @property ?CarbonImmutable $published_at
         * @property ?SupportCollection<int, NotificationChannel> $notification_channels
         * @property-read Collection<int, Post> $posts
         *
         * @method static Builder<static>|ImportedTypesModel published()
         */
        EOD;

        $expected = <<<'EOD'
        /**
         * @property ?\Carbon\CarbonImmutable $published_at
         * @property ?\Illuminate\Support\Collection<int, \Workbench\App\Enums\NotificationChannel> $notification_channels
         * @property-read \Illuminate\Database\Eloquent\Collection<int, \Workbench\App\Models\Post> $posts
         *
         * @method static \Illuminate\Database\Eloquent\Builder<static>|\Workbench\App\Models\ImportedTypesModel published()
         */
        EOD;

        expect((new DocBlockComparator)->matches($current, $expected, ImportedTypesModel::class))->toBeTrue();
    });

    it('reports a mismatch when a type differs', function (): void {
        $current = "/**\n * @property ?CarbonImmutable \$published_at\n */";
        $expected = "/**\n * @property ?\\Illuminate\\Support\\Carbon \$published_at\n */";

        expect((new DocBlockComparator)->matches($current, $expected, ImportedTypesModel::class))->toBeFalse();
    });

    it('keeps built-in types, unknown names and descriptions unchanged', function (): void {
        $docBlock = "/**\r\n * @property array<array-key, mixed> \$settings Settings for Collection\r\n * @property ?UnknownType \$legacy\r\n * A free text line\r\n */";

        expect((new DocBlockComparator)->normalize($docBlock, ImportedTypesModel::class))->toBe(
            "/**\n * @property array<array-key, mixed> \$settings Settings for Collection\n * @property ?UnknownType \$legacy\n * A free text line\n */"
        );
    });
});
