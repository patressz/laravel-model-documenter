<?php

declare(strict_types=1);

use Patressz\LaravelModelDocumenter\ModelFinder;

it('skips abstract models when finding classes in directory', function () {
    $directory = sys_get_temp_dir().'/model-finder-'.uniqid('', true);

    mkdir($directory, 0777, true);

    file_put_contents($directory.'/AbstractBaseModel.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Models;

abstract class AbstractBaseModel
{
}
PHP);

    file_put_contents($directory.'/User.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Models;

class User
{
}
PHP);

    try {
        $finder = new ModelFinder();
        $models = $finder->findModels($directory);
        $classes = array_map(static fn (array $model): string => $model['class'], $models);

        expect($classes)->toContain('App\\Models\\User');
        expect($classes)->not->toContain('App\\Models\\AbstractBaseModel');
    } finally {
        @unlink($directory.'/AbstractBaseModel.php');
        @unlink($directory.'/User.php');
        @rmdir($directory);
    }
});
