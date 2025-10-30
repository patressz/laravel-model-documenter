<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Casts\AsEnumArrayObject;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Patressz\LaravelModelDocumenter\Resolvers\ModelCastTypeResolver;
use Workbench\App\Models\User;

describe('ModelCastTypeResolver', function () {
    it('resolves collection cast correctly', function (string $castType) {
        $resolver = new ModelCastTypeResolver();

        expect($resolver->resolve($castType))->toBe('\Illuminate\Support\Collection<int, \Workbench\App\Models\User>');
    })->with([
        '\Illuminate\Database\Eloquent\Casts\AsCollection:,Workbench\App\Models\User',
        '\Illuminate\Database\Eloquent\Casts\AsEnumCollection:Workbench\App\Models\User',
    ]);

    it('resolves enum array object cast correctly', function () {
        $resolver = new ModelCastTypeResolver();

        expect($resolver->resolve(AsEnumArrayObject::of(User::class)))->toBe('\ArrayObject<int, \Workbench\App\Models\User>');
    });

    it('resolves integer cast to `int`', function (string $castType) {
        $resolver = new ModelCastTypeResolver();
        expect($resolver->resolve($castType))->toBe('int');
    })->with([
        'int',
        'integer',
    ]);

    it('resolves decimal cast to `float`', function (string $castType) {
        $resolver = new ModelCastTypeResolver();
        expect($resolver->resolve($castType))->toBe('float');
    })->with([
        'real',
        'float',
        'double',
        'decimal',
    ]);

    it('resolves string cast to `string`', function () {
        $resolver = new ModelCastTypeResolver();
        expect($resolver->resolve('string'))->toBe('string');
    });

    it('resolves boolean cast to `bool`', function (string $castType) {
        $resolver = new ModelCastTypeResolver();
        expect($resolver->resolve($castType))->toBe('bool');
    })->with([
        'bool',
        'boolean',
    ]);

    it('resolves object cast to `object`', function () {
        $resolver = new ModelCastTypeResolver();
        expect($resolver->resolve('object'))->toBe('object');
    });

    it('resolves array cast to `array<array-key, mixed>`', function (string $castType) {
        $resolver = new ModelCastTypeResolver();
        expect($resolver->resolve($castType))->toBe('array<array-key, mixed>');
    })->with([
        'array',
        'json',
        'json:unicode',
    ]);

    it('resolves encrypted collection cast to `\Illuminate\Support\Collection`', function (string $castType) {
        $resolver = new ModelCastTypeResolver();
        expect($resolver->resolve($castType))->toBe('\Illuminate\Support\Collection');
    })->with([
        'encrypted:collection',
        AsEncryptedCollection::class,
    ]);

    it('resolves collection cast to `\Illuminate\Support\Collection`', function (string $castType) {
        $resolver = new ModelCastTypeResolver();
        expect($resolver->resolve($castType))->toBe('\Illuminate\Support\Collection<array-key, mixed>');
    })->with([
        'collection',
    ]);

    it('resolves datetime cast to `\Illuminate\Support\Carbon`', function (string $castType) {
        $resolver = new ModelCastTypeResolver();
        expect($resolver->resolve($castType))->toBe('\Illuminate\Support\Carbon');
    })->with([
        'date',
        'datetime',
        'custom_datetime',
        'timestamp',
    ]);

    it('resolves immutable datetime cast to `\Carbon\CarbonImmutable`', function (string $castType) {
        $resolver = new ModelCastTypeResolver();
        expect($resolver->resolve($castType))->toBe('\Carbon\CarbonImmutable');
    })->with([
        'immutable_date',
        'immutable_datetime',
        'immutable_custom_datetime',
    ]);

    it('resolves hashed/encrypted cast to `string`', function (string $castType) {
        $resolver = new ModelCastTypeResolver();
        expect($resolver->resolve($castType))->toBe('string');
    })->with([
        'hashed',
        'encrypted',
    ]);

    it('resolves encrypted array cast to `array`', function (string $castType) {
        $resolver = new ModelCastTypeResolver();
        expect($resolver->resolve($castType))->toBe('array');
    })->with([
        'encrypted:array',
        'encrypted:json',
    ]);

    it('resolves encrypted object cast to `object`', function (string $castType) {
        $resolver = new ModelCastTypeResolver();
        expect($resolver->resolve($castType))->toBe('object');
    })->with([
        'encrypted:object',
    ]);

    it('resolves AsStringable cast to `\Illuminate\Support\Stringable`', function () {
        $resolver = new ModelCastTypeResolver();
        expect($resolver->resolve(AsStringable::class))->toBe('\Illuminate\Support\Stringable');
    });

    it('resolves AsArrayObject cast to `\ArrayObject`', function (string $castType) {
        $resolver = new ModelCastTypeResolver();
        expect($resolver->resolve($castType))->toBe('\ArrayObject');
    })->with([
        AsArrayObject::class,
        AsEncryptedArrayObject::class,
    ]);

    it('resolves unknown cast type to `mixed`', function () {
        $resolver = new ModelCastTypeResolver();
        expect($resolver->resolve('unknown_cast_type'))->toBe('mixed');
    });
});
