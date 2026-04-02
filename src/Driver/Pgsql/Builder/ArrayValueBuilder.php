<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Builder;

use FastPHP\QueryBuilder\Constant\ColumnType;
use FastPHP\QueryBuilder\Constant\DataType;
use FastPHP\QueryBuilder\Constant\GettypeResult;
use FastPHP\QueryBuilder\Expression\Value\ArrayValue;
use FastPHP\QueryBuilder\Expression\Value\Builder\AbstractArrayValueBuilder;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\Expression\Value\Param;
use FastPHP\QueryBuilder\Driver\Pgsql\Data\LazyArray;
use FastPHP\QueryBuilder\Query\QueryInterface;
use FastPHP\QueryBuilder\Schema\Column\AbstractArrayColumn;
use FastPHP\QueryBuilder\Schema\Column\ColumnInterface;
use FastPHP\QueryBuilder\Schema\Data\LazyArrayInterface;

use function array_map;
use function gettype;
use function implode;
use function is_array;
use function iterator_to_array;
use function str_repeat;

/**
 * Builds expressions for {@see ArrayValue} for PostgreSQL Server.
 */
final class ArrayValueBuilder extends AbstractArrayValueBuilder
{
    protected function buildStringValue(string $value, ArrayValue $expression, array &$params): string
    {
        $param = new Param($value, DataType::STRING);

        $column = $this->getColumn($expression);
        $dbType = $this->getColumnDbType($column);

        $typeHint = $this->getTypeHint($dbType, $column?->getDimension() ?? 1);

        /** @phpstan-ignore argument.type */
        return $this->queryBuilder->bindParam($param, $params) . $typeHint;
    }

    protected function buildSubquery(QueryInterface $query, ArrayValue $expression, array &$params): string
    {
        $column = $this->getColumn($expression);
        $dbType = $this->getColumnDbType($column);

        return $this->buildNestedSubquery($query, $dbType, $column?->getDimension() ?? 1, $params);
    }

    /**
     * @param iterable<int|string, mixed> $value
     */
    protected function buildValue(iterable $value, ArrayValue $expression, array &$params): string
    {
        $column = $this->getColumn($expression);
        $dbType = $this->getColumnDbType($column);

        return $this->buildNestedValue($value, $dbType, $column?->getColumn(), $column?->getDimension() ?? 1, $params);
    }

    /**
     * @return array<int|string, mixed>|string
     */
    protected function getLazyArrayValue(LazyArrayInterface $value): array|string
    {
        if ($value instanceof LazyArray) {
            return $value->getRawValue();
        }

        return $value->getValue();
    }

    /**
     * @param string[] $placeholders
     */
    private function buildNestedArray(array $placeholders, ?string $dbType, int $dimension): string
    {
        $typeHint = $this->getTypeHint($dbType, $dimension);

        return 'ARRAY[' . implode(',', $placeholders) . ']' . $typeHint;
    }

    /**
     * @param array<int|string, mixed> $params
     */
    private function buildNestedSubquery(QueryInterface $query, ?string $dbType, int $dimension, array &$params): string
    {
        /** @phpstan-ignore argument.type */
        [$sql, $params] = $this->queryBuilder->build($query, $params);

        return "ARRAY($sql)" . $this->getTypeHint($dbType, $dimension);
    }

    /**
     * @param iterable<int|string, mixed> $value
     * @param array<int|string, mixed> $params
     */
    private function buildNestedValue(iterable $value, ?string &$dbType, ?ColumnInterface $column, int $dimension, array &$params): string
    {
        $placeholders = [];
        $queryBuilder = $this->queryBuilder;
        $isTypecastingEnabled = $column !== null && $queryBuilder->isTypecastingEnabled();

        if ($dimension > 1) {
            /** @var iterable<int|string, mixed>|null $item */
            foreach ($value as $item) {
                if ($item === null) {
                    $placeholders[] = 'NULL';
                } elseif ($item instanceof ExpressionInterface) {
                    $placeholders[] = $item instanceof QueryInterface
                        ? $this->buildNestedSubquery($item, $dbType, $dimension - 1, $params)
                        /** @phpstan-ignore argument.type */ : $queryBuilder->buildExpression($item, $params);
                } else {
                    $placeholders[] = $this->buildNestedValue($item, $dbType, $column, $dimension - 1, $params);
                }
            }
        } else {
            if ($isTypecastingEnabled) {
                $value = $this->dbTypecast($value, $column);
            }

            foreach ($value as $item) {
                $placeholders[] = $queryBuilder->buildValue($item, $params);

                $dbType ??= match (gettype($item)) {
                    GettypeResult::ARRAY => 'jsonb',
                    GettypeResult::BOOLEAN => 'bool',
                    GettypeResult::INTEGER => 'int',
                    GettypeResult::RESOURCE => 'bytea',
                    GettypeResult::STRING => 'text',
                    GettypeResult::DOUBLE => '',
                    default => null,
                };
            }
        }

        return $this->buildNestedArray($placeholders, $dbType, $dimension);
    }

    private function getColumn(ArrayValue $expression): ?AbstractArrayColumn
    {
        $type = $expression->type;

        if ($type === null || $type instanceof AbstractArrayColumn) {
            return $type;
        }

        $info = [];

        if ($type instanceof ColumnInterface) {
            $info['column'] = $type;
        } elseif ($type !== ColumnType::ARRAY) {
            $column = $this
                ->queryBuilder
                ->getColumnFactory()
                ->fromDefinition($type);

            if ($column instanceof AbstractArrayColumn) {
                return $column;
            }

            $info['column'] = $column;
        }

        /** @var AbstractArrayColumn */
        return $this
            ->queryBuilder
            ->getColumnFactory()
            ->fromType(ColumnType::ARRAY, $info);
    }

    private function getColumnDbType(?AbstractArrayColumn $column): ?string
    {
        if ($column === null) {
            return null;
        }

        return rtrim($this->queryBuilder->getColumnDefinitionBuilder()->buildType($column), '[]');
    }

    /**
     * Return the type hint expression based on type and dimension.
     */
    private function getTypeHint(?string $dbType, int $dimension): string
    {
        if (empty($dbType)) {
            return '';
        }

        return '::' . $dbType . str_repeat('[]', $dimension);
    }

    /**
     * Converts array values for use in a db query.
     *
     * @param iterable<int|string, mixed> $value The array or iterable object.
     * @param ColumnInterface $column The column instance to typecast values.
     *
     * @return iterable<int|string, mixed> Converted values.
     */
    private function dbTypecast(iterable $value, ColumnInterface $column): iterable
    {
        if (!is_array($value)) {
            $value = iterator_to_array($value, false);
        }

        return array_map($column->dbTypecast(...), $value);
    }
}
