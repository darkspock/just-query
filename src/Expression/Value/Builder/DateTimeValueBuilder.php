<?php

declare(strict_types=1);

namespace JustQuery\Expression\Value\Builder;

use JustQuery\Constant\ColumnType;
use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\Value\DateTimeValue;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\QueryBuilder\QueryBuilderInterface;
use JustQuery\Schema\Column\ColumnFactoryInterface;

/**
 * Builder for {@see DateTimeValue} expressions.
 *
 * @implements ExpressionBuilderInterface<DateTimeValue>
 *
 * @psalm-import-type ColumnInfo from ColumnFactoryInterface
 */
final class DateTimeValueBuilder implements ExpressionBuilderInterface
{
    private ColumnFactoryInterface $columnFactory;

    /**
     * @param QueryBuilderInterface $queryBuilder The query builder instance.
     */
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {
        $this->columnFactory = $this->queryBuilder->getColumnFactory();
    }

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $value = $this->columnFactory
            ->fromType($this->prepareType($expression), $this->prepareInfo($expression))
            ->dbTypecast($expression->value);
        return $this->queryBuilder->buildValue($value, $params);
    }

    /**
     * @psalm-return ColumnType::*
     */
    private function prepareType(DateTimeValue $expression): string
    {
        return match ($expression->type) {
            ColumnType::TIMESTAMP,
            ColumnType::TIME,
            ColumnType::TIMETZ,
            ColumnType::DATETIME,
            ColumnType::DATETIMETZ => $expression->type,
            default => ColumnType::TIMESTAMP,
        };
    }

    /**
     * @psalm-return ColumnInfo
     * @return array<string, mixed>
     */
    private function prepareInfo(DateTimeValue $expression): array
    {
        $info = $expression->info + ['type' => $expression->type];

        return match ($expression->type) {
            ColumnType::TIMESTAMP,
            ColumnType::TIME,
            ColumnType::TIMETZ,
            ColumnType::DATETIME,
            ColumnType::DATETIMETZ => $info + ['size' => 0],
            default => $info,
        };
    }
}
