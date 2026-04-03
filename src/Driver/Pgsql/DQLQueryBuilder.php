<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql;

use JustQuery\Expression\Value\ArrayValue;
use JustQuery\Expression\Statement\CaseX;
use JustQuery\Expression\Function\ArrayMerge;
use JustQuery\Expression\Value\JsonValue;
use JustQuery\Expression\Value\StructuredValue;
use JustQuery\Driver\Pgsql\Builder\ArrayValueBuilder;
use JustQuery\Driver\Pgsql\Builder\ArrayMergeBuilder;
use JustQuery\Driver\Pgsql\Builder\ArrayOverlapsBuilder;
use JustQuery\Driver\Pgsql\Builder\CaseXBuilder;
use JustQuery\Driver\Pgsql\Builder\JsonContainsBuilder;
use JustQuery\Driver\Pgsql\Builder\JsonLengthBuilder;
use JustQuery\Driver\Pgsql\Builder\JsonOverlapsBuilder;
use JustQuery\Driver\Pgsql\Builder\LikeBuilder;
use JustQuery\Driver\Pgsql\Builder\StructuredValueBuilder;
use JustQuery\Driver\Pgsql\Builder\JsonValueBuilder;
use JustQuery\Driver\Pgsql\Expression\Builder\DateRangeValueBuilder;
use JustQuery\Driver\Pgsql\Expression\Builder\Int4RangeValueBuilder;
use JustQuery\Driver\Pgsql\Expression\Builder\Int8RangeValueBuilder;
use JustQuery\Driver\Pgsql\Expression\Builder\MultiRangeValueBuilder;
use JustQuery\Driver\Pgsql\Expression\Builder\NumRangeValueBuilder;
use JustQuery\Driver\Pgsql\Expression\Builder\TsRangeValueBuilder;
use JustQuery\Driver\Pgsql\Expression\Builder\TsTzRangeValueBuilder;
use JustQuery\Driver\Pgsql\Expression\DateRangeValue;
use JustQuery\Driver\Pgsql\Expression\Int4RangeValue;
use JustQuery\Driver\Pgsql\Expression\Int8RangeValue;
use JustQuery\Driver\Pgsql\Expression\MultiRangeValue;
use JustQuery\Driver\Pgsql\Expression\NumRangeValue;
use JustQuery\Driver\Pgsql\Expression\TsRangeValue;
use JustQuery\Driver\Pgsql\Expression\TsTzRangeValue;
use JustQuery\QueryBuilder\AbstractDQLQueryBuilder;
use JustQuery\QueryBuilder\Condition\Like;
use JustQuery\QueryBuilder\Condition\ArrayOverlaps;
use JustQuery\QueryBuilder\Condition\JsonContains;
use JustQuery\QueryBuilder\Condition\JsonLength;
use JustQuery\QueryBuilder\Condition\JsonOverlaps;
use JustQuery\QueryBuilder\Condition\NotLike;

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
            JsonContains::class => JsonContainsBuilder::class,
            JsonLength::class => JsonLengthBuilder::class,
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
