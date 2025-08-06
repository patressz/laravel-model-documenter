<?php

declare(strict_types=1);

namespace Patressz\LaravelModelDocumenter\Resolvers;

/**
 * @internal
 */
final class DatabaseColumnTypeResolver
{
    /**
     * Resolve the database column type to a PHP type.
     *
     * @param  string  $type  The database column type
     * @return string The corresponding native PHP type
     */
    public function resolve(string $type): string
    {
        $original = mb_strtolower($type);

        $normalized = preg_replace('/\(.*/', '', $original);
        $normalized = mb_trim((string) $normalized);

        return match ($normalized) {
            'bool', 'boolean' => 'bool',

            'int', 'integer', 'tinyint', 'smallint', 'mediumint', 'bigint',
            'int1', 'int2', 'int3', 'int4', 'int8', 'middleint',
            'serial', 'serial2', 'serial4', 'serial8',
            'smallserial', 'bigserial',
            'unsigned big int' => 'int',

            'dec', 'decimal', 'numeric', 'fixed', 'number',
            'float', 'float4', 'float8', 'double', 'double precision', 'real', 'money' => 'float',

            'bit', 'bit varying', 'varbit' => 'string',

            'date', 'time', 'timetz', 'datetime', 'timestamp',
            'timestamp without time zone', 'timestamp with time zone',
            'year', 'sql_tsi_year' => 'string',
            'interval' => 'string',

            'json', 'jsonb' => 'string',

            'char', 'character', 'nchar', 'native character', 'nvarchar',
            'varchar', 'character varying', 'varying character', 'varchar2', 'varcharacter',
            'char byte', 'char varying', 'national char', 'national character',
            'national varchar', 'national character varying', 'national char varying',
            'nchar varying', 'nchar varchar', 'nchar varcharacter', 'nchar',
            'text', 'clob', 'tinytext', 'mediumtext', 'longtext',
            'enum', 'set',

            'blob', 'tinyblob', 'mediumblob', 'longblob', 'bytea',
            'binary', 'varbinary', 'long varbinary', 'raw', 'row',

            'uuid', 'cidr', 'inet', 'inet4', 'inet6', 'macaddr', 'macaddr8',

            'point', 'line', 'lseg', 'box', 'path', 'polygon', 'circle',
            'tsvector', 'tsquery',

            'xml', 'pg_lsn', 'pg_snapshot', 'txid_snapshot' => 'string',

            default => 'string',
        };
    }
}
