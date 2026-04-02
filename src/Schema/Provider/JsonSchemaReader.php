<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Schema\Provider;

use FastPHP\QueryBuilder\Schema\Column\BooleanColumn;
use FastPHP\QueryBuilder\Schema\Column\ColumnInterface;
use FastPHP\QueryBuilder\Schema\Column\DoubleColumn;
use FastPHP\QueryBuilder\Schema\Column\IntegerColumn;
use FastPHP\QueryBuilder\Schema\Column\JsonColumn;
use FastPHP\QueryBuilder\Schema\Column\StringColumn;
use FastPHP\QueryBuilder\Schema\TableSchema;
use FastPHP\QueryBuilder\Schema\TableSchemaInterface;

/**
 * Reads table schema from JSON files.
 *
 * JSON format per table:
 * {
 *   "column_name": {
 *     "type": "string|integer|float|boolean|json",
 *     "primaryKey": true,
 *     "autoIncrement": true,
 *     "computed": true,
 *     "readOnly": true,
 *     "notNull": true,
 *     "size": 255,
 *     "scale": 2,
 *     "defaultValue": "value"
 *   }
 * }
 */
final class JsonSchemaReader
{
    /** @var array<string, TableSchemaInterface> */
    private array $tables = [];

    private bool $loaded = false;

    /**
     * @param string $path Path to directory containing JSON schema files, or a single JSON file with all tables.
     */
    public function __construct(
        private readonly string $path,
    ) {}

    public function getTableSchema(string $tableName): ?TableSchemaInterface
    {
        if (!$this->loaded) {
            $this->load();
        }

        return $this->tables[$tableName] ?? null;
    }

    public function hasTable(string $tableName): bool
    {
        if (!$this->loaded) {
            $this->load();
        }

        return isset($this->tables[$tableName]);
    }

    private function load(): void
    {
        $this->loaded = true;

        if (is_file($this->path)) {
            $this->loadSingleFile($this->path);
            return;
        }

        if (!is_dir($this->path)) {
            return;
        }

        $files = glob($this->path . '/*.json');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $this->loadSingleFile($file);
        }
    }

    private function loadSingleFile(string $file): void
    {
        $content = file_get_contents($file);
        if ($content === false) {
            return;
        }

        /** @var array<string, array<string, array<string, mixed>>> $data */
        $data = json_decode($content, true);
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $tableName => $columns) {
            if (!is_array($columns)) {
                continue;
            }
            $this->tables[$tableName] = $this->buildTableSchema($tableName, $columns);
        }
    }

    /**
     * @param array<string, array<string, mixed>> $columns
     */
    private function buildTableSchema(string $tableName, array $columns): TableSchemaInterface
    {
        $table = new TableSchema();
        $table->name($tableName);

        foreach ($columns as $columnName => $definition) {
            $column = $this->buildColumn($definition);
            $table->column($columnName, $column->withName($columnName));
        }

        return $table;
    }

    /**
     * @param array<string, mixed> $def
     */
    private function buildColumn(array $def): ColumnInterface
    {
        $type = (string) ($def['type'] ?? 'string');

        $column = match ($type) {
            'integer' => new IntegerColumn(),
            'float' => (new DoubleColumn()),
            'boolean' => new BooleanColumn(),
            'json' => new JsonColumn(),
            default => new StringColumn(),
        };

        if (isset($def['primaryKey']) && $def['primaryKey']) {
            $column->primaryKey();
        }
        if (isset($def['autoIncrement']) && $def['autoIncrement']) {
            $column->autoIncrement();
        }
        if (isset($def['computed']) && $def['computed']) {
            $column->computed();
        }
        if (isset($def['notNull']) && $def['notNull']) {
            $column->notNull();
        }
        if (isset($def['size'])) {
            $column->size((int) $def['size']);
        }
        if (isset($def['scale'])) {
            $column->scale((int) $def['scale']);
        }
        if (array_key_exists('defaultValue', $def)) {
            $column->defaultValue($def['defaultValue']);
        }

        return $column;
    }
}
