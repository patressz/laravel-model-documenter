<?php

declare(strict_types=1);

use Patressz\LaravelModelDocumenter\FileUpdater;

beforeEach(function () {
    $this->fileUpdater = new FileUpdater();

    // Create a temporary test model file
    $this->testFilePath = __DIR__.'/TestModelForFileUpdater.php';
    $this->originalContent = '<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Eloquent\Model;

class TestModelForFileUpdater extends Model
{
protected $table = \'test_models\';

protected $fillable = [
    \'name\',
    \'email\',
];
}';

    file_put_contents($this->testFilePath, $this->originalContent);
});

afterEach(function () {
    // Clean up test file
    if (file_exists($this->testFilePath)) {
        unlink($this->testFilePath);
    }
});
describe('FileUpdater', function () {
    it('updates docblock for a model class without existing docblock', function () {
        $docBlockContent = '/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 */';

        $this->fileUpdater->updateDocBlock($this->testFilePath, $docBlockContent);

        $updatedContent = file_get_contents($this->testFilePath);

        expect($updatedContent)->toContain('@property int $id');
        expect($updatedContent)->toContain('@property string $name');
        expect($updatedContent)->toContain('@property string $email');
        expect($updatedContent)->toContain('class TestModelForFileUpdater extends Model');
        expect($updatedContent)->toContain('protected $fillable = [');
    });

    it('replaces existing docblock with new one', function () {
        // First, create a file with existing docblock
        $contentWithExistingDocBlock = '<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Eloquent\Model;

/**
 * Old docblock
 * @property string $old_property
 */
class TestModelForFileUpdater extends Model
{
    protected $table = \'test_models\';
}';

        file_put_contents($this->testFilePath, $contentWithExistingDocBlock);

        $newDocBlockContent = '/**
 * @property int $id
 * @property string $name
 * @property ?\Illuminate\Support\Carbon $created_at
 */';

        $this->fileUpdater->updateDocBlock($this->testFilePath, $newDocBlockContent);

        $updatedContent = file_get_contents($this->testFilePath);

        expect($updatedContent)->toContain('@property int $id');
        expect($updatedContent)->toContain('@property string $name');
        expect($updatedContent)->toContain('@property ?\Illuminate\Support\Carbon $created_at');
        expect($updatedContent)->not()->toContain('Old docblock');
        expect($updatedContent)->not()->toContain('@property string $old_property');
    });

    it('preserves file formatting and structure', function () {
        $docBlockContent = '/**
 * @property int $id
 * @property string $name
 */';

        $this->fileUpdater->updateDocBlock($this->testFilePath, $docBlockContent);

        $updatedContent = file_get_contents($this->testFilePath);

        // Check that original structure is preserved
        expect($updatedContent)->toContain('declare(strict_types=1);');
        expect($updatedContent)->toContain('namespace Tests;');
        expect($updatedContent)->toContain('use Illuminate\Database\Eloquent\Model;');
        expect($updatedContent)->toContain('protected $table = \'test_models\';');
        expect($updatedContent)->toContain('protected $fillable = [');
        expect($updatedContent)->toContain('\'name\',');
        expect($updatedContent)->toContain('\'email\',');
    });

    it('handles complex docblock with multiple property types', function () {
        $complexDocBlock = '/**
 * @property int $id
 * @property string $name
 * @property ?string $email
 * @property bool $is_active
 * @property array<array-key, mixed> $settings
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Post> $posts
 * 
 * @method static Builder<static>|User active()
 */';

        $this->fileUpdater->updateDocBlock($this->testFilePath, $complexDocBlock);

        $updatedContent = file_get_contents($this->testFilePath);

        expect($updatedContent)->toContain('@property int $id');
        expect($updatedContent)->toContain('@property string $name');
        expect($updatedContent)->toContain('@property ?string $email');
        expect($updatedContent)->toContain('@property bool $is_active');
        expect($updatedContent)->toContain('@property array<array-key, mixed> $settings');
        expect($updatedContent)->toContain('@property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Post> $posts');
        expect($updatedContent)->toContain('@method static Builder<static>|User active()');
    });

    it('handles file reading errors gracefully', function () {
        // Create a file and then make it unreadable (if possible on the system)
        $tempFile = __DIR__.'/UnreadableFile.php';
        file_put_contents($tempFile, '<?php class Test {}');

        try {
            // Try to make file unreadable - this might not work on all systems
            chmod($tempFile, 0000);

            expect(fn () => $this->fileUpdater->updateDocBlock($tempFile, '/** test */'))
                ->toThrow();
        } finally {
            // Restore permissions and clean up
            chmod($tempFile, 0644);
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    })->skip('Permission tests can be unreliable across different systems');

    it('throws exception for unparseable file', function () {
        $invalidPhpFile = __DIR__.'/InvalidPhp.php';
        file_put_contents($invalidPhpFile, '<?php this is not valid php syntax {');

        try {
            expect(fn () => $this->fileUpdater->updateDocBlock($invalidPhpFile, '/** test */'))
                ->toThrow(RuntimeException::class);
        } finally {
            if (file_exists($invalidPhpFile)) {
                unlink($invalidPhpFile);
            }
        }
    });

    it('works with real workbench model', function () {
        $postModelPath = realpath(__DIR__.'/../../workbench/app/Models/Post.php');

        if (! $postModelPath) {
            $this->markTestSkipped('Post model not found in workbench');
        }

        // Create backup
        $backupContent = file_get_contents($postModelPath);

        try {
            $docBlockContent = '/**
 * @property int $id
 * @property string $title
 * @property string $content
 * @property int $user_id
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property-read \Workbench\App\Models\User $user
 */';

            $this->fileUpdater->updateDocBlock($postModelPath, $docBlockContent);

            $updatedContent = file_get_contents($postModelPath);

            expect($updatedContent)->toContain('@property int $id');
            expect($updatedContent)->toContain('@property string $title');
            expect($updatedContent)->toContain('@property int $user_id');
            expect($updatedContent)->toContain('@property-read \Workbench\App\Models\User $user');
            expect($updatedContent)->toContain('public function user(): BelongsTo');

        } finally {
            // Restore original content
            file_put_contents($postModelPath, $backupContent);
        }
    });
});
