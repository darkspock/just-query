<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pgsql;

use FastPHP\QueryBuilder\Expression\Value\ArrayValue;
use FastPHP\QueryBuilder\Expression\Statement\CaseX;
use FastPHP\QueryBuilder\Expression\Function\ArrayMerge;
use FastPHP\QueryBuilder\Expression\Value\JsonValue;
use FastPHP\QueryBuilder\Expression\Value\StructuredValue;
use FastPHP\QueryBuilder\Driver\Pgsql\Builder\ArrayValueBuilder;
use FastPHP\QueryBuilder\Driver\Pgsql\Builder\ArrayMergeBuilder;
use FastPHP\QueryBuilder\Driver\Pgsql\Builder\ArrayOverlapsBuilder;
use FastPHP\QueryBuilder\Driver\Pgsql\Builder\CaseXBuilder;
use FastPHP\QueryBuilder\Driver\Pgsql\Builder\JsonOverlapsBuilder;
use FastPHP\QueryBuilder\Driver\Pgsql\Builder\LikeBuilder;
use FastPHP\QueryBuilder\Driver\Pgsql\Builder\StructuredValueBuilder;
use FastPHP\QueryBuilder\Driver\Pgsql\Builder\JsonValueBuilder;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\Builder\DateRangeValueBuilder;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\Builder\Int4RangeValueBuilder;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\Builder\Int8RangeValueBuilder;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\Builder\MultiRangeValueBuilder;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\Builder\NumRangeValueBuilder;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\Builder\TsRangeValueBuilder;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\Builder\TsTzRangeValueBuilder;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\DateRangeValue;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\Int4RangeValue;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\Int8RangeValue;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\MultiRangeValue;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\NumRangeValue;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\TsRangeValue;
use FastPHP\QueryBuilder\Driver\Pgsql\Expression\TsTzRangeValue;
use FastPHP\QueryBuilder\QueryBuilder\AbstractDQLQueryBuilder;
use FastPHP\QueryBuilder\QueryBuilder\Condition\Like;
use FastPHP\QueryBuilder\QueryBuilder\Condition\ArrayOverlaps;
use FastPHP\QueryBuilder\QueryBuilder\Condition\JsonOverlaps;
use FastPHP\QueryBuilder\QueryBuilder\Condition\NotLike;

/**
 * Implements a DQL (Data Query Language) SQL statements for PostgreSQL Server.
 */
final class DQLQueryBuilder extends AbstractDQLQueryBuilder
{
    protected function defaultExpressionBuilders(): array // @phpstan-ignore missingType.generics
    {
        return [
            ...parent::defaultExpressionBuilders(),
            ArrayValue::class => ArrayValueBuilder::class,
            ArrayOverlaps::class => ArrayOverlapsBuilder::class,
            JsonValue::class => JsonValueBuilder::class,
            JsonOverlaps::class => JsonOverlapsBuilder::class,
            StructuredValue::class => StructuredValueBuilder::class,
            Like::class => LikeBuilder::class,
            NotLike::class => LikeBuilder::class,
            CaseX::class => CaseXBuilder::class,
            ArrayMerge::class => ArrayMergeBuilder::class,
            DateRangeValue::class => DateRangeValueBuilder::class,
            Int4RangeValue::class => Int4RangeValueBuilder::class,
            Int8RangeValue::class => Int8RangeValueBuilder::class,
            NumRangeValue::class => NumRangeValueBuilder::class,
            TsRangeValue::class => TsRangeValueBuilder::class,
            TsTzRangeValue::class => TsTzRangeValueBuilder::class,
            MultiRangeValue::class => MultiRangeValueBuilder::class,
        ];
    }
}
