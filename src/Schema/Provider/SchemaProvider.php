<?php

declare(strict_types=1);

namespace JustQuery\Schema\Provider;

use JustQuery\Schema\SchemaInterface;
use JustQuery\Schema\TableSchemaInterface;

/**
 * Configurable schema provider with multiple modes:
 *
 * - DISABLED:   No schema. No typecasting. Pure query builder.
 * - JSON:       JSON files only. Zero DB overhead. Deploy-safe.
 * - CACHE:      DB + PSR-16 cache (original behavior).
 * - JSON_CACHE: JSON first, DB + cache fallback for tables not in JSON.
 */
final class SchemaProvider
{
    private ?JsonSchemaReader $jsonReader = null;

    public function __construct(
        private readonly SchemaMode $mode,
        private readonly ?SchemaInterface $dbSchema = null,
        private readonly ?string $jsonPath = null,
    ) {
        if ($this->mode === SchemaMode::JSON || $this->mode === SchemaMode::JSON_CACHE) {
            if ($this->jsonPath === null) {
                throw new \InvalidArgumentException(
                    'jsonPath is required for SchemaMode::JSON and SchemaMode::JSON_CACHE'
                );
            }
            $this->jsonReader = new JsonSchemaReader($this->jsonPath);
        }

        if ($this->mode === SchemaMode::CACHE || $this->mode === SchemaMode::JSON_CACHE) {
            if ($this->dbSchema === null) {
                throw new \InvalidArgumentException(
                    'dbSchema is required for SchemaMode::CACHE and SchemaMode::JSON_CACHE'
                );
            }
        }
    }

    public function getTableSchema(string $name, bool $refresh = false): ?TableSchemaInterface
    {
        return match ($this->mode) {
            SchemaMode::DISABLED => null,
            SchemaMode::JSON => $this->jsonReader?->getTableSchema($name),
            SchemaMode::CACHE => $this->dbSchema?->getTableSchema($name, $refresh),
            SchemaMode::JSON_CACHE => $this->resolveJsonCache($name, $refresh),
        };
    }

    public function isEnabled(): bool
    {
        return $this->mode !== SchemaMode::DISABLED;
    }

    public function getMode(): SchemaMode
    {
        return $this->mode;
    }

    /**
     * Returns column metadata for write filtering (computed, autoIncrement).
     *
     * @return array{computed: string[], autoIncrement: string[]}
     */
    public function getWriteExclusions(string $tableName): array
    {
        $schema = $this->getTableSchema($tableName);
        if ($schema === null) {
            return ['computed' => [], 'autoIncrement' => []];
        }

        $computed = [];
        $autoIncrement = [];

        foreach ($schema->getColumns() as $name => $column) {
            if ($column->isComputed()) {
                $computed[] = $name;
            }
            if ($column->isAutoIncrement()) {
                $autoIncrement[] = $name;
            }
        }

        return [
            'computed' => $computed,
            'autoIncrement' => $autoIncrement,
        ];
    }

    private function resolveJsonCache(string $name, bool $refresh): ?TableSchemaInterface
    {
        $jsonSchema = $this->jsonReader?->getTableSchema($name);
        if ($jsonSchema !== null) {
            return $jsonSchema;
        }

        return $this->dbSchema?->getTableSchema($name, $refresh);
    }
}
