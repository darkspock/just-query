<?php

declare(strict_types=1);

namespace JustQuery\Schema\Column;

use JustQuery\Constant\ColumnType;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\Expression\Value\JsonValue;

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
