<?php

declare(strict_types=1);

use Patressz\LaravelModelDocumenter\DocBlockGenerator;
use Patressz\LaravelModelDocumenter\Resolvers\DatabaseColumnTypeResolver;
use Patressz\LaravelModelDocumenter\Resolvers\ModelCastTypeResolver;
use Workbench\App\Models\User;

beforeEach(function () {
    $this->generator = new DocBlockGenerator(
        new DatabaseColumnTypeResolver,
        new ModelCastTypeResolver
    );
});

describe('DocBlockGenerator', function () {
    it('generates property tag for simple column', function () {
        $columns = [
            [
                'name' => 'id',
                'type_name' => 'bigint',
                'type' => 'bigint',
                'collation' => null,
                'nullable' => false,
                'default' => null,
                'auto_increment' => true,
                'comment' => null,
                'generation' => null,
            ],
        ];

        $docBlock = $this->generator->generate($columns, User::class);

        $expectedDocBlock = <<<'EOD'
/**
 * @property int $id
 */
EOD;

        expect($docBlock)->toBe($expectedDocBlock);
    });

    it('generates nullable property tag', function () {

        $columns = [
            [
                'name' => 'description',
                'type_name' => 'longtext',
                'type' => 'longtext',
                'collation' => null,
                'nullable' => true,
                'default' => null,
                'auto_increment' => false,
                'comment' => null,
                'generation' => null,
            ],
        ];

        $docBlock = $this->generator->generate($columns, User::class);

        $expectedDocBlock = <<<'EOD'
/**
 * @property ?string $description
 */
EOD;

        expect($docBlock)->toBe($expectedDocBlock);
    });

    it('generates property tag with column comment', function () {
        $columns = [
            [
                'name' => 'status',
                'type_name' => 'varchar',
                'type' => 'varchar',
                'collation' => null,
                'nullable' => false,
                'default' => 'active',
                'auto_increment' => false,
                'comment' => 'User status field',
                'generation' => null,
            ],
        ];

        $docBlock = $this->generator->generate($columns, User::class);

        $expectedDocBlock = <<<'EOD'
/**
 * @property string $status User status field
 */
EOD;

        expect($docBlock)->toBe($expectedDocBlock);
    });

    it('handles created_at and updated_at timestamps correctly', function () {
        $columns = [
            [
                'name' => 'created_at',
                'type_name' => 'timestamp',
                'type' => 'timestamp',
                'collation' => null,
                'nullable' => true,
                'default' => null,
                'auto_increment' => false,
                'comment' => null,
                'generation' => null,
            ],
            [
                'name' => 'updated_at',
                'type_name' => 'timestamp',
                'type' => 'timestamp',
                'collation' => null,
                'nullable' => true,
                'default' => null,
                'auto_increment' => false,
                'comment' => null,
                'generation' => null,
            ],
        ];

        $docBlock = $this->generator->generate($columns, User::class);

        $expectedDocBlock = <<<'EOD'
/**
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 */
EOD;

        expect($docBlock)->toBe($expectedDocBlock);
    });

    it('handles casted properties correctly', function () {
        $columns = [
            [
                'name' => 'birth_date',
                'type_name' => 'date',
                'type' => 'date',
                'collation' => null,
                'nullable' => true,
                'default' => null,
                'auto_increment' => false,
                'comment' => null,
                'generation' => null,
            ],
            [
                'name' => 'deleted_at',
                'type_name' => 'timestamp',
                'type' => 'timestamp',
                'collation' => null,
                'nullable' => true,
                'default' => null,
                'auto_increment' => false,
                'comment' => null,
                'generation' => null,
            ],
        ];

        $docBlock = $this->generator->generate($columns, User::class);

        $expectedDocBlock = <<<'EOD'
/**
 * @property ?\Illuminate\Support\Carbon $birth_date
 * @property ?\Carbon\CarbonImmutable $deleted_at
 */
EOD;

        expect($docBlock)->toBe($expectedDocBlock);
    });

    it('handles different numeric types correctly', function () {
        $columns = [
            [
                'name' => 'age',
                'type_name' => 'int',
                'type' => 'int',
                'collation' => null,
                'nullable' => true,
                'default' => null,
                'auto_increment' => false,
                'comment' => null,
                'generation' => null,
            ],
            [
                'name' => 'salary',
                'type_name' => 'decimal',
                'type' => 'decimal',
                'collation' => null,
                'nullable' => true,
                'default' => null,
                'auto_increment' => false,
                'comment' => null,
                'generation' => null,
            ],
            [
                'name' => 'rating',
                'type_name' => 'float',
                'type' => 'float',
                'collation' => null,
                'nullable' => false,
                'default' => 0.00,
                'auto_increment' => false,
                'comment' => null,
                'generation' => null,
            ],
        ];

        $docBlock = $this->generator->generate($columns, User::class);

        $expectedDocBlock = <<<'EOD'
/**
 * @property ?int $age
 * @property ?float $salary
 * @property float $rating
 */
EOD;

        expect($docBlock)->toBe($expectedDocBlock);
    });

    it('handles text column types correctly', function () {
        $columns = [
            [
                'name' => 'name',
                'type_name' => 'varchar',
                'type' => 'varchar',
                'collation' => null,
                'nullable' => false,
                'default' => null,
                'auto_increment' => false,
                'comment' => null,
                'generation' => null,
            ],
            [
                'name' => 'bio',
                'type_name' => 'text',
                'type' => 'text',
                'collation' => null,
                'nullable' => true,
                'default' => null,
                'auto_increment' => false,
                'comment' => null,
                'generation' => null,
            ],
            [
                'name' => 'summary',
                'type_name' => 'mediumtext',
                'type' => 'mediumtext',
                'collation' => null,
                'nullable' => true,
                'default' => null,
                'auto_increment' => false,
                'comment' => null,
                'generation' => null,
            ],
        ];

        $docBlock = $this->generator->generate($columns, User::class);

        $expectedDocBlock = <<<'EOD'
/**
 * @property string $name
 * @property ?string $bio
 * @property ?string $summary
 */
EOD;

        expect($docBlock)->toBe($expectedDocBlock);
    });

    it('handles boolean columns correctly', function () {
        $columns = [
            [
                'name' => 'is_active',
                'type_name' => 'tinyint',
                'type' => 'tinyint',
                'collation' => null,
                'nullable' => false,
                'default' => 1,
                'auto_increment' => false,
                'comment' => null,
                'generation' => null,
            ],
            [
                'name' => 'is_verified',
                'type_name' => 'boolean',
                'type' => 'boolean',
                'collation' => null,
                'nullable' => true,
                'default' => null,
                'auto_increment' => false,
                'comment' => null,
                'generation' => null,
            ],
        ];

        $docBlock = $this->generator->generate($columns, User::class);

        $expectedDocBlock = <<<'EOD'
/**
 * @property int $is_active
 * @property ?bool $is_verified
 */
EOD;

        expect($docBlock)->toBe($expectedDocBlock);
    });

    it('handles json columns correctly', function () {
        $columns = [
            [
                'name' => 'metadata',
                'type_name' => 'json',
                'type' => 'json',
                'collation' => null,
                'nullable' => true,
                'default' => null,
                'auto_increment' => false,
                'comment' => null,
                'generation' => null,
            ],
            [
                'name' => 'settings',
                'type_name' => 'json',
                'type' => 'json',
                'collation' => null,
                'nullable' => false,
                'default' => '{}',
                'auto_increment' => false,
                'comment' => 'User settings',
                'generation' => null,
            ],
        ];

        $docBlock = $this->generator->generate($columns, User::class);

        $expectedDocBlock = <<<'EOD'
/**
 * @property ?string $metadata
 * @property string $settings User settings
 */
EOD;

        expect($docBlock)->toBe($expectedDocBlock);
    });

    it('handles mixed column types in single generation', function () {
        $columns = [
            [
                'name' => 'id',
                'type_name' => 'bigint',
                'type' => 'bigint',
                'collation' => null,
                'nullable' => false,
                'default' => null,
                'auto_increment' => true,
                'comment' => null,
                'generation' => null,
            ],
            [
                'name' => 'name',
                'type_name' => 'varchar',
                'type' => 'varchar',
                'collation' => null,
                'nullable' => false,
                'default' => null,
                'auto_increment' => false,
                'comment' => null,
                'generation' => null,
            ],
            [
                'name' => 'age',
                'type_name' => 'int',
                'type' => 'int',
                'collation' => null,
                'nullable' => true,
                'default' => null,
                'auto_increment' => false,
                'comment' => 'User age in years',
                'generation' => null,
            ],
            [
                'name' => 'created_at',
                'type_name' => 'timestamp',
                'type' => 'timestamp',
                'collation' => null,
                'nullable' => true,
                'default' => null,
                'auto_increment' => false,
                'comment' => null,
                'generation' => null,
            ],
        ];

        $docBlock = $this->generator->generate($columns, User::class);

        $expectedDocBlock = <<<'EOD'
/**
 * @property int $id
 * @property string $name
 * @property ?int $age User age in years
 * @property ?\Illuminate\Support\Carbon $created_at
 */
EOD;

        expect($docBlock)->toBe($expectedDocBlock);
    });
});
