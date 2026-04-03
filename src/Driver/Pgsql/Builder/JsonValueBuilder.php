<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Builder;

use JustQuery\Expression\Value\ArrayValue;
use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\Expression\Value\JsonValue;
use JustQuery\Expression\Value\Builder\JsonValueBuilder as BaseJsonValueBuilder;
use JustQuery\QueryBuilder\QueryBuilderInterface;

/**
 * Builds expressions for {@see `JustQuery\Expression\Value\JsonValue`} for PostgreSQL Server.
 *
 * @implements ExpressionBuilderInterface<JsonValue>
 */
final class JsonValueBuilder implements ExpressionBuilderInterface
{
    private BaseJsonValueBuilder $baseValueBuilder;

    public function __construct(QueryBuilderInterface $queryBuilder)
    {
        $this->baseValueBuilder = new BaseJsonValueBuilder($queryBuilder);
    }

    /**
     * The Method builds the raw SQL from the $expression that won't be additionally escaped or quoted.
     *
     * @param JsonValue $expression The expression to build.
     * @param array<int|string, mixed> $params The binding parameters.
     *
     * @return string The raw SQL that won't be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $statement = $this->baseValueBuilder->build($expression, $params);

        if ($expression->value instanceof ArrayValue) {
            $statement = 'array_to_json(' . $statement . ')';
        }

        return $statement . $this->getTypeHint($expression);
    }

    /**
     * @return string The typecast expression based on {@see JsonValue::type}.
     */
    private function getTypeHint(JsonValue $expression): string
    {
        $type = $expression->type;

        if ($type === null) {
            return '';
        }

        return '::' . $type;
    }
}
