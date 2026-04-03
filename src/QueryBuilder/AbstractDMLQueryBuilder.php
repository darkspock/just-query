<?php

declare(strict_types=1);

namespace JustQuery\QueryBuilder;

use Iterator;
use IteratorAggregate;
use Traversable;
use JustQuery\Connection\ConnectionInterface;
use JustQuery\Constraint\Index;
use JustQuery\Exception\Exception;
use InvalidArgumentException;
use JustQuery\Exception\InvalidConfigException;
use JustQuery\Exception\NotSupportedException;
use JustQuery\Expression\Expression;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\Expression\Function\ArrayMerge;
use JustQuery\Expression\Function\MultiOperandFunction;
use JustQuery\Helper\DbArrayHelper;
use JustQuery\Query\QueryInterface;
use JustQuery\Schema\QuoterInterface;
use JustQuery\Schema\SchemaInterface;

use function array_combine;
use function array_diff;
use function array_fill_keys;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function get_object_vars;
use function gettype;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function iterator_to_array;
use function preg_match;
use function reset;

/**
 * It's used to manipulate data in tables.
 *
 * This manipulation involves inserting data into database tables, retrieving existing data, deleting data from existing
 * tables and modifying existing data.
 *
 * @link https://en.wikipedia.org/wiki/Data_manipulation_language
 *
 * @psalm-import-type ParamsType from ConnectionInterface
 * @psalm-import-type BatchValues from DMLQueryBuilderInterface
 */
abstract class AbstractDMLQueryBuilder implements DMLQueryBuilderInterface
{
    protected bool $typecasting = true;

    public function __construct(
        protected QueryBuilderInterface $queryBuilder,
        protected QuoterInterface $quoter,
        protected SchemaInterface $schema,
    ) {}

    /**
     * @param-out array<int|string, mixed> $params
     */
    public function insertBatch(string $table, iterable $rows, array $columns = [], array &$params = []): string
    {
        if (!is_array($rows)) {
            $rows = $this->prepareTraversable($rows);
        }

        if (empty($rows)) {
            return '';
        }

        $allColumns = $this->extractColumnNames($rows, $columns);
        $excludedColumnNames = $this->getExcludedWriteColumns($table, $allColumns);
        $columns = $this->removeExcludedColumnNames($allColumns, $excludedColumnNames);

        if ($allColumns !== [] && $columns === []) {
            throw new InvalidArgumentException("No writable columns supplied for batch insert into '$table'.");
        }

        $values = $this->prepareBatchInsertValues($table, $rows, $allColumns, $params, $excludedColumnNames);

        $query = 'INSERT INTO ' . $this->quoter->quoteTableName($table);

        if (count($columns) > 0) {
            $quotedColumnNames = array_map($this->quoter->quoteColumnName(...), $columns);

            $query .= ' (' . implode(', ', $quotedColumnNames) . ')';
        }

        return $query . ' VALUES (' . implode('), (', $values) . ')';
    }

    public function delete(string $table, array|string $condition, array &$params): string
    {
        $sql = 'DELETE FROM ' . $this->quoter->quoteTableName($table);
        $where = $this->queryBuilder->buildWhere($condition, $params);

        return $where === '' ? $sql : $sql . ' ' . $where;
    }

    /**
     * @param array<int|string, mixed> $params
     * @param array<int|string, mixed>|QueryInterface $columns
     */
    public function insert(string $table, array|QueryInterface $columns, array &$params = []): string
    {
        return $this->buildInsertSql($table, $columns, $params);
    }

    /** @throws NotSupportedException */
    public function insertReturningPks(string $table, array|QueryInterface $columns, array &$params = []): string
    {
        throw new NotSupportedException(__METHOD__ . '() is not supported by this DBMS.');
    }

    public function isTypecastingEnabled(): bool
    {
        return $this->typecasting;
    }

    /** @throws NotSupportedException */
    public function resetSequence(string $table, int|string|null $value = null): string
    {
        throw new NotSupportedException(__METHOD__ . '() is not supported by this DBMS.');
    }

    public function update(
        string $table,
        array $columns,
        array|ExpressionInterface|string $condition,
        array|ExpressionInterface|string|null $from = null,
        array &$params = [],
    ): string {
        $updates = $this->prepareUpdateSets($table, $columns, $params);

        if ($updates === []) {
            throw new InvalidArgumentException("No writable columns supplied for update of table '$table'.");
        }

        $sql = 'UPDATE ' . $this->quoter->quoteTableName($table) . ' SET ' . implode(', ', $updates);
        $where = $this->queryBuilder->buildWhere($condition, $params);
        if ($from !== null) {
            $from = DbArrayHelper::normalizeExpressions($from);
            $fromClause = $this->queryBuilder->buildFrom($from, $params);
            $sql .=  $fromClause === '' ? '' : ' ' . $fromClause;
        }

        return $where === '' ? $sql : $sql . ' ' . $where;
    }

    /**
     * @throws NotSupportedException
     * @param-out array<int|string, mixed> $params
     */
    public function upsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        array &$params = [],
    ): string {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    /**
     * @throws NotSupportedException
     * @param-out array<int|string, mixed> $params
     */
    public function upsertReturning(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        ?array $returnColumns = null,
        array &$params = [],
    ): string {
        throw new NotSupportedException(__METHOD__ . '() is not supported by this DBMS.');
    }

    public function withTypecasting(bool $typecasting = true): static
    {
        $new = clone $this;
        $new->typecasting = $typecasting;
        return $new;
    }

    /**
     * @psalm-param array<string, string> $columns
     */
    protected function buildSimpleSelect(array $columns): string
    {
        $quoter = $this->quoter;

        foreach ($columns as $name => &$column) {
            $column .= ' AS ' . $quoter->quoteSimpleColumnName($name);
        }

        return 'SELECT ' . implode(', ', $columns);
    }

    /**
     * Prepare traversable for batch insert.
     *
     * @param Traversable<mixed> $rows The rows to be batch inserted into the table.
     *
     * @return array|Iterator The prepared rows.
     *
     * @psalm-return Iterator|array<iterable<array-key, mixed>>
     */
    final protected function prepareTraversable(Traversable $rows): Iterator|array
    {
        while ($rows instanceof IteratorAggregate) {
            $rows = $rows->getIterator();
        }

        /** @var Iterator $rows */
        if (!$rows->valid()) {
            return [];
        }

        return $rows;
    }

    /**
     * Prepare values for batch insert.
     *
     * @param string $table The table name.
     * @param iterable<mixed> $rows The rows to be batch inserted into the table.
     * @param string[] $columnNames The column names.
     * @param array<int|string, mixed> $params The binding parameters that will be generated by this method.
     * @param array<string, bool> $excludedColumnNames The column names that should be excluded from the insert.
     *
     * @return string[] The values.
     *
     * @psalm-param array<int|string, mixed> $params
     */
    protected function prepareBatchInsertValues(
        string $table,
        iterable $rows,
        array $columnNames,
        array &$params,
        array $excludedColumnNames = [],
    ): array
    {
        $values = [];
        $names = array_values($this->removeExcludedColumnNames($columnNames, $excludedColumnNames));
        $keys = array_fill_keys($names, false);
        $columns = $this->typecasting ? $this->schema->getTableSchema($table)?->getColumns() ?? [] : [];
        $queryBuilder = $this->queryBuilder;

        foreach ($rows as $row) {
            $i = 0;
            $placeholders = $keys;

            /** @var int|string $key */
            /** @phpstan-ignore foreach.nonIterable */
            foreach ($row as $key => $value) {
                $columnName = $columnNames[$key] ?? (isset($keys[$key]) ? $key : $names[$i] ?? $i);

                if (is_string($columnName) && isset($excludedColumnNames[$columnName])) {
                    ++$i;
                    continue;
                }

                if (isset($columns[$columnName])) {
                    $value = $columns[$columnName]->dbTypecast($value);
                }

                $placeholders[$columnName] = $queryBuilder->buildValue($value, $params);

                ++$i;
            }

            $values[] = implode(', ', $placeholders);
        }

        return $values;
    }

    /**
     * Extract column names from columns and rows.
     *
     * @param array[]|Iterator $rows The rows to be batch inserted into the table.
     * @param string[] $columns The column names.
     *
     * @return string[] The column names.
     *
     * @psalm-param Iterator|non-empty-array<iterable<array-key, mixed>> $rows
     */
    protected function extractColumnNames(array|Iterator $rows, array $columns): array
    {
        $columns = $this->getNormalizedColumnNames($columns);

        if (!empty($columns)) {
            return $columns;
        }

        if ($rows instanceof Iterator) {
            $row = $rows->current();
        } else {
            $row = reset($rows);
        }

        $row = match (gettype($row)) {
            'array' => $row,
            'object' => $row instanceof Traversable
                ? iterator_to_array($row)
                : get_object_vars($row),
            default => [],
        };

        if (array_key_exists(0, $row)) {
            return [];
        }

        /** @var string[] $columnNames */
        $columnNames = array_keys($row);

        return array_combine($columnNames, $columnNames);
    }

    /**
     * Prepare select-subQuery and field names for `INSERT INTO ... SELECT` SQL statement.
     *
     * @param QueryInterface $query Object, which represents a select query.
     * @param array<int|string, mixed> $params The parameters to bind to the generated SQL statement. These parameters will be included
     * in the result, with the more parameters generated during the query building process.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string[] Array of column names, values, and params.
     *
     * @psalm-param ParamsType $params
     */
    protected function getQueryColumnNames(QueryInterface $query, array &$params = []): array
    {
        /** @var string[] $select */
        $select = $query->getSelect();

        if (empty($select) || in_array('*', $select, true)) {
            throw new InvalidArgumentException('Expected select query object with enumerated (named) parameters');
        }

        $names = [];

        foreach ($select as $title => $field) {
            if (is_string($title)) {
                $names[] = $title;
            } else {
                /** @phpstan-ignore instanceof.alwaysFalse */
                if ($field instanceof ExpressionInterface) {
                    $field = $this->queryBuilder->buildExpression($field, $params);
                }

                if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_.]+)$/', $field, $matches)) {
                    $names[] = $matches[2];
                } else {
                    $names[] = $field;
                }
            }
        }

        return $this->getNormalizedColumnNames($names);
    }

    /**
     * Prepare column names and placeholders for `INSERT` SQL statement.
     *
     * @param string $table The table to insert new rows into.
     * @param array<int|string, mixed>|QueryInterface $columns The column data (name => value) to insert into the table or instance of
     * {@see Query} to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array<int|string, mixed> $params The binding parameters that will be generated by this method.
     * They should be bound to the DB command later.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     *
     * @return array{0: string[], 1: string[], 2: string} Array of column names, placeholders, and values.
     *
     * @psalm-param ParamsType $params
     * @psalm-return array{0: string[], 1: string[], 2: string, 3: array<int|string, mixed>}
     */
    /** @phpstan-ignore-next-line missingType.iterableValue */
    protected function prepareInsertValues(
        string $table,
        array|QueryInterface $columns,
        array &$params = [],
        bool $excludeAutoIncrement = true,
    ): array
    {
        if (empty($columns)) {
            return [[], [], $this->buildDefaultInsertValues()];
        }

        if ($columns instanceof QueryInterface) {
            $names = $this->getQueryColumnNames($columns, $params); // @phpstan-ignore argument.type
            [$values, $params] = $this->queryBuilder->build($columns, $params);
            return [$names, [], $values];
        }

        $placeholders = [];
        $columns = $this->filterWriteColumns($table, $columns, $excludeAutoIncrement);

        if ($columns === []) {
            return [[], [], $this->buildDefaultInsertValues()];
        }

        $tableColumns = $this->typecasting ? $this->schema->getTableSchema($table)?->getColumns() ?? [] : [];

        foreach ($columns as $name => $value) {
            if (isset($tableColumns[$name])) {
                $value = $tableColumns[$name]->dbTypecast($value);
            }
            $placeholders[] = $this->queryBuilder->buildValue($value, $params);
        }

        return [array_keys($columns), $placeholders, ''];
    }

    /**
     * Prepare column names and placeholders for `UPDATE` SQL statement.
     *
     * @param array<int|string, mixed> $columns
     * @param array<int|string, mixed> $params
     * @psalm-param ParamsType $params
     *
     * @return string[]
     */
    protected function prepareUpdateSets(
        string $table,
        array $columns,
        array &$params,
        bool $forUpsert = false,
        bool $useTableName = false,
    ): array {
        $sets = [];
        $columns = $this->filterWriteColumns($table, $columns);
        $tableColumns = $this->schema->getTableSchema($table)?->getColumns() ?? [];
        $typecastColumns = $this->typecasting ? $tableColumns : [];
        $queryBuilder = $this->queryBuilder;
        $quoter = $this->quoter;

        if ($useTableName) {
            $quotedTableName = $quoter->quoteTableName($table);
            $columnPrefix = "$quotedTableName.";
        } else {
            $columnPrefix = '';
        }

        foreach ($columns as $name => $value) {
            if (isset($typecastColumns[$name])) {
                $value = $typecastColumns[$name]->dbTypecast($value);
            }

            $quotedName = $quoter->quoteSimpleColumnName($name);

            if ($forUpsert && $value instanceof MultiOperandFunction && empty($value->getOperands())) {
                $quotedTableName ??= $quoter->quoteTableName($table);
                $value->add(new Expression("$quotedTableName.$quotedName"))
                    ->add(new Expression("EXCLUDED.$quotedName"));

                if (isset($tableColumns[$name]) && $value instanceof ArrayMerge) {
                    $value->type($tableColumns[$name]);
                }

                $builtValue = $queryBuilder->buildExpression($value, $params); // @phpstan-ignore argument.type
            } else {
                $builtValue = $queryBuilder->buildValue($value, $params); // @phpstan-ignore parameterByRef.type
            }

            $sets[] = "$columnPrefix$quotedName=$builtValue";
        }

        return $sets;
    }

    /**
     * Prepare column names and placeholders for upsert SQL statement.
     *
     * @param array<int|string, mixed>|bool $updateColumns
     * @param string[]|null $updateNames
     * @param array<int|string, mixed> $params
     *
     * @psalm-param array|true $updateColumns
     * @psalm-param ParamsType $params
     *
     * @return string[]
     */
    protected function prepareUpsertSets( // @phpstan-ignore missingType.iterableValue
        string $table,
        array|bool $updateColumns,
        ?array $updateNames,
        array &$params,
    ): array {
        if ($updateColumns === true) {
            $quoter = $this->quoter;
            $sets = [];

            /** @var string[] $updateNames */
            foreach ($updateNames as $name) {
                $quotedName = $quoter->quoteSimpleColumnName($name);
                $sets[] = "$quotedName=EXCLUDED.$quotedName";
            }

            return $sets;
        }

        /** @var array<int|string, mixed> $updateColumns */
        return $this->prepareUpdateSets($table, $updateColumns, $params, true);
    }

    /**
     * Prepare column names and constraints for "upsert" operation.
     *
     * @param array<int|string, mixed>|bool $updateColumns
     * @param Index[] $constraints
     *
     * @psalm-param array<string, mixed>|QueryInterface $insertColumns
     *
     * @return array Array of unique, insert and update column names.
     * @psalm-return array{0: string[], 1: string[], 2: string[]|null}
     */
    protected function prepareUpsertColumns(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        array &$constraints = [],
    ): array {
        if ($insertColumns instanceof QueryInterface) {
            $insertNames = $this->getQueryColumnNames($insertColumns);
        } else {
            $insertNames = $this->getNormalizedColumnNames(array_keys($insertColumns));
        }

        $uniqueNames = $this->getTableUniqueColumnNames($table, $insertNames, $constraints);

        if ($updateColumns === true) {
            return [
                $uniqueNames,
                $insertNames,
                $this->filterWritableColumnNames($table, array_values(array_diff($insertNames, $uniqueNames))),
            ];
        }

        return [$uniqueNames, $insertNames, null];
    }

    protected function buildDefaultInsertValues(): string
    {
        return 'DEFAULT VALUES';
    }

    /**
     * @param array<int|string, mixed> $params
     * @param array<int|string, mixed>|QueryInterface $columns
     */
    protected function buildInsertSql(
        string $table,
        array|QueryInterface $columns,
        array &$params = [],
        bool $excludeAutoIncrement = true,
    ): string {
        $result = $this->prepareInsertValues(
            $table,
            $columns,
            $params,
            $excludeAutoIncrement,
        );
        $names = $result[0];
        $placeholders = $result[1];
        $values = $result[2];

        $quotedNames = array_map($this->quoter->quoteColumnName(...), $names); // @phpstan-ignore argument.type, argument.type

        return 'INSERT INTO ' . $this->quoter->quoteTableName($table)
            . (!empty($quotedNames) ? ' (' . implode(', ', $quotedNames) . ')' : '')
            . (!empty($placeholders) ? ' VALUES (' . implode(', ', $placeholders) . ')' : ' ' . $values); // @phpstan-ignore binaryOp.invalid, argument.type
    }

    /**
     * @param array<int|string, mixed> $columns
     *
     * @return array<string, mixed>
     */
    protected function filterWriteColumns(string $table, array $columns, bool $excludeAutoIncrement = true): array
    {
        $columns = $this->normalizeColumnNames($columns);

        if ($columns === []) {
            return [];
        }

        $excludedColumnNames = $this->getExcludedWriteColumns($table, array_keys($columns), $excludeAutoIncrement);

        foreach (array_keys($excludedColumnNames) as $name) {
            unset($columns[$name]);
        }

        return $columns;
    }

    /**
     * @param string[] $columnNames
     *
     * @return string[]
     */
    protected function filterWritableColumnNames(
        string $table,
        array $columnNames,
        bool $excludeAutoIncrement = true,
    ): array {
        return $this->removeExcludedColumnNames(
            $this->getNormalizedColumnNames($columnNames),
            $this->getExcludedWriteColumns($table, $columnNames, $excludeAutoIncrement),
        );
    }

    /**
     * Normalizes the column names.
     *
     * @param array<int|string, mixed> $columns The column data (name => value).
     *
     * @return array The normalized column names (name => value).
     *
     * @psalm-return array<string, mixed>
     */
    protected function normalizeColumnNames(array $columns): array
    {
        /** @var string[] $columnNames */
        $columnNames = array_keys($columns);
        $normalizedNames = $this->getNormalizedColumnNames($columnNames);

        return array_combine($normalizedNames, $columns);
    }

    /**
     * Get normalized column names
     *
     * @param string[] $columns The column names.
     *
     * @return string[] Normalized column names.
     */
    protected function getNormalizedColumnNames(array $columns): array
    {
        foreach ($columns as &$name) {
            $name = $this->quoter->ensureColumnName($name);
            $name = $this->quoter->unquoteSimpleColumnName($name);
        }

        return $columns;
    }

    /**
     * @param string[] $columnNames
     *
     * @return array<string, bool>
     */
    private function getExcludedWriteColumns(
        string $table,
        array $columnNames,
        bool $excludeAutoIncrement = true,
    ): array {
        if ($columnNames === []) {
            return [];
        }

        $tableColumns = $this->schema->getTableSchema($table)?->getColumns() ?? [];
        $excluded = [];

        foreach ($this->getNormalizedColumnNames($columnNames) as $name) {
            $column = $tableColumns[$name] ?? null;

            if ($column === null) {
                continue;
            }

            if ($column->isComputed() || ($excludeAutoIncrement && $column->isAutoIncrement())) {
                $excluded[$name] = true;
            }
        }

        return $excluded;
    }

    /**
     * @param string[] $columnNames
     * @param array<string, bool> $excludedColumnNames
     *
     * @return string[]
     */
    private function removeExcludedColumnNames(array $columnNames, array $excludedColumnNames): array
    {
        $filtered = [];

        foreach ($columnNames as $name) {
            if (!isset($excludedColumnNames[$name])) {
                $filtered[] = $name;
            }
        }

        return $filtered;
    }

    /**
     * Returns all column names belonging to constraints enforcing uniqueness (`PRIMARY KEY`, `UNIQUE INDEX`, etc.)
     * for the named table removing constraints which didn't cover the specified column list.
     *
     * The column list will be unique by column names.
     *
     * @param string $name The table name, may contain schema name if any. Don't quote the table name.
     * @param string[] $columns Source column list.
     * @param Index[] $indexes This parameter optionally receives a matched index list.
     * The constraints will be unique by their column names.
     *
     * @return string[] The column names.
    */
    private function getTableUniqueColumnNames(string $name, array $columns, array &$indexes = []): array
    {
        $indexes = $this->schema->getTableUniques($name);
        $columnNames = [];

        // Remove all indexes which don't cover the specified column list.
        $indexes = array_values(
            array_filter(
                $indexes,
                static function (Index $index) use ($columns, &$columnNames): bool {
                    $result = empty(array_diff($index->columnNames, $columns));

                    if ($result) {
                        $columnNames[] = $index->columnNames;
                    }

                    return $result;
                },
            ),
        );

        if (empty($columnNames)) {
            return [];
        }

        return array_unique(array_merge(...$columnNames));
    }
}
