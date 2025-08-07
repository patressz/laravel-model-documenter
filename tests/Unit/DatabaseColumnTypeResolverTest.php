<?php

declare(strict_types=1);

use Patressz\LaravelModelDocumenter\Resolvers\DatabaseColumnTypeResolver;

describe('DatabaseColumnTypeResolver', function () {
    it('resolves string types to `string`', function (string $type) {
        $resolver = new DatabaseColumnTypeResolver();
        expect($resolver->resolve($type))->toBe('string');
    })->with([
        'varchar',
        'char',
        'character',
        'nchar',
        'nvarchar',
        'text',
        'longtext',
        'mediumtext',
        'tinytext',
        'clob',
        'enum',
        'set',
        'blob',
        'tinyblob',
        'mediumblob',
        'longblob',
        'binary',
        'varbinary',
        'uuid',
        'xml',
    ]);

    it('resolves integer types to `int`', function (string $type) {
        $resolver = new DatabaseColumnTypeResolver();
        expect($resolver->resolve($type))->toBe('int');
    })->with([
        'int',
        'integer',
        'bigint',
        'tinyint',
        'smallint',
        'mediumint',
        'int1',
        'int2',
        'int3',
        'int4',
        'int8',
        'middleint',
        'serial',
        'serial2',
        'serial4',
        'serial8',
        'smallserial',
        'bigserial',
        'unsigned big int',
    ]);

    it('resolves decimal types to `float`', function (string $type) {
        $resolver = new DatabaseColumnTypeResolver();
        expect($resolver->resolve($type))->toBe('float');
    })->with([
        'decimal',
        'float',
        'double',
        'double precision',
        'real',
        'dec',
        'numeric',
        'fixed',
        'number',
        'float4',
        'float8',
        'money',
    ]);

    it('resolves boolean types to `bool`', function (string $type) {
        $resolver = new DatabaseColumnTypeResolver();
        expect($resolver->resolve($type))->toBe('bool');
    })->with([
        'bool',
        'boolean',
    ]);

    it('resolves date/time types to `string`', function (string $type) {
        $resolver = new DatabaseColumnTypeResolver();
        expect($resolver->resolve($type))->toBe('string');
    })->with([
        'date',
        'time',
        'timetz',
        'datetime',
        'timestamp',
        'timestamp without time zone',
        'timestamp with time zone',
        'year',
        'sql_tsi_year',
        'interval',
    ]);

    it('resolves JSON types to `string`', function (string $type) {
        $resolver = new DatabaseColumnTypeResolver();
        expect($resolver->resolve($type))->toBe('string');
    })->with([
        'json',
        'jsonb',
    ]);

    it('resolves bit types to `string`', function (string $type) {
        $resolver = new DatabaseColumnTypeResolver();
        expect($resolver->resolve($type))->toBe('string');
    })->with([
        'bit',
        'bit varying',
        'varbit',
    ]);

    it('resolves network types to `string`', function (string $type) {
        $resolver = new DatabaseColumnTypeResolver();
        expect($resolver->resolve($type))->toBe('string');
    })->with([
        'cidr',
        'inet',
        'inet4',
        'inet6',
        'macaddr',
        'macaddr8',
    ]);

    it('resolves geometric types to `string`', function (string $type) {
        $resolver = new DatabaseColumnTypeResolver();
        expect($resolver->resolve($type))->toBe('string');
    })->with([
        'point',
        'line',
        'lseg',
        'box',
        'path',
        'polygon',
        'circle',
    ]);

    it('resolves PostgreSQL specific types to `string`', function (string $type) {
        $resolver = new DatabaseColumnTypeResolver();
        expect($resolver->resolve($type))->toBe('string');
    })->with([
        'tsvector',
        'tsquery',
        'pg_lsn',
        'pg_snapshot',
        'txid_snapshot',
        'bytea',
        'raw',
        'row',
    ]);

    it('resolves types with parameters correctly', function (string $type, string $expected) {
        $resolver = new DatabaseColumnTypeResolver();
        expect($resolver->resolve($type))->toBe($expected);
    })->with([
        ['varchar(255)', 'string'],
        ['decimal(10,2)', 'float'],
        ['int(11)', 'int'],
        ['char(50)', 'string'],
        ['float(7,4)', 'float'],
    ]);

    it('resolves unknown type to `string`', function () {
        $resolver = new DatabaseColumnTypeResolver();
        expect($resolver->resolve('unknown_type'))->toBe('string');
    });

    it('handles case insensitive type names', function (string $type, string $expected) {
        $resolver = new DatabaseColumnTypeResolver();
        expect($resolver->resolve($type))->toBe($expected);
    })->with([
        ['VARCHAR', 'string'],
        ['INT', 'int'],
        ['BOOLEAN', 'bool'],
        ['DECIMAL', 'float'],
        ['JSON', 'string'],
    ]);
});
