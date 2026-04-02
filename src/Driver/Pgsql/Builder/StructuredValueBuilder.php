<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql\Builder;

use FastPHP\QueryBuilder\Constant\DataType;
use FastPHP\QueryBuilder\Expression\Value\Builder\AbstractStructuredValueBuilder;
use FastPHP\QueryBuilder\Expression\Value\Param;
use FastPHP\QueryBuilder\Expression\Value\StructuredValue;
use FastPHP\QueryBuilder\Driver\Pgsql\Data\StructuredLazyArray;
use FastPHP\QueryBuilder\Query\QueryInterface;
use FastPHP\QueryBuilder\Schema\Column\AbstractStructuredColumn;
use FastPHP\QueryBuilder\Schema\Data\LazyArrayInterface;

use function implode;

/**
 * Builds expressions for {@see StructuredValue} for PostgreSQL Server.
 */
final class StructuredValueBuilder extends AbstractStructuredValueBuilder
{
    protected function buildStringValue(string $value, StructuredValue $expression, array &$params): string
    {
        $param = new Param($value, DataType::STRING);

        /** @phpstan-ignore argument.type */
        return $this->queryBuilder->bindParam($param, $params) . $this->getTypeHint($expression);
    }

    protected function buildSubquery(QueryInterface $query, StructuredValue $expression, array &$params): string
    {
        /** @phpstan-ignore argument.type */
        [$sql, $params] = $this->queryBuilder->build($query, $params);

        return "($sql)" . $this->getTypeHint($expression);
    }

    /**
     * @param array<string, mixed>|object $value
     */
    protected function buildValue(array|object $value, StructuredValue $expression, array &$params): string
    {
        $value = $this->prepareValues($value, $expression);
        /** @psalm-var string[] $placeholders */
        $placeholders = $this->buildPlaceholders($value, $expression, $params);

        return 'ROW(' . implode(',', $placeholders) . ')' . $this->getTypeHint($expression);
    }

    /**
     * @return array<int|string, mixed>|string
     */
    protected function getLazyArrayValue(LazyArrayInterface $value): array|string
    {
        if ($value instanceof StructuredLazyArray) {
            return $value->getRawValue();
        }

        return $value->getValue();
    }

    /**
     * Builds a placeholder array out of $expression value.
     *
     * @param array<string, mixed> $value The expression value.
     * @param StructuredValue $expression The structured expression.
     * @param array<int|string, mixed> $params The binding parameters.
     * @return array<int, string>
     */
    private function buildPlaceholders(array $value, StructuredValue $expression, array &$params): array
    {
        $type = $expression->type;
        $queryBuilder = $this->queryBuilder;
        $columns = $type instanceof AbstractStructuredColumn && $queryBuilder->isTypecastingEnabled()
            ? $type->getColumns()
            : [];

        $placeholders = [];

        /** @psalm-var int|string $columnName */
        foreach ($value as $columnName => $item) {
            if (isset($columns[$columnName])) {
                $item = $columns[$columnName]->dbTypecast($item);
            }

            $placeholders[] = $queryBuilder->buildValue($item, $params);
        }

        return $placeholders;
    }

    /**
     * Returns the type hint expression based on type.
     */
    private function getTypeHint(StructuredValue $expression): string
    {
        $type = $expression->type;

        if ($type instanceof AbstractStructuredColumn) {
            $type = $type->getDbType();
        }

        if (empty($type)) {
            return '';
        }

        return '::' . $type;
    }
}
