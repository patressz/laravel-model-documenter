<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Workbench\App\Enums\NotificationChannel;

/**
 * @property ?CarbonImmutable $published_at
 * @property ?SupportCollection<int, NotificationChannel> $notification_channels
 * @property-read Collection<int, Post> $posts
 *
 * @method static Builder<static>|ImportedTypesModel published()
 */
final class ImportedTypesModel extends Model {}
