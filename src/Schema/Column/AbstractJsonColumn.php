<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Schema\Column;

use FastPHP\QueryBuilder\Constant\ColumnType;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\Expression\Value\JsonValue;

/**
 * Represents an abstract JSON column.
 *
 * @see JsonColumn for a JSON column with eager parsing values retrieved from the database.
 * @see JsonLazyColumn for a JSON column with lazy parsing values retrieved from the database.
 */
abstract class AbstractJsonColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::JSON;

    public function dbTypecast(mixed $value): ?ExpressionInterface
    {
        if ($value === null || $value instanceof ExpressionInterface) {
            return $value;
        }

        return new JsonValue($value, $this->getDbType());
    }
}
