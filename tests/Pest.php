<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extends(
    TestCase::class,
    RefreshDatabase::class
)->in(__DIR__);
