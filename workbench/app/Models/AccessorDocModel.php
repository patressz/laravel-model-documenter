<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

final class AccessorDocModel extends Model
{
    /**
     * @return Attribute<array<int, string>, null>
     */
    protected function phoneArray(): Attribute
    {
        return Attribute::make(
            get: fn (): array => [],
        );
    }
}
