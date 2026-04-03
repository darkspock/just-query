<?php

declare(strict_types=1);

namespace JustQuery\Expression\Value\Builder;

use JustQuery\Expression\Value\Param;
use JustQuery\Constant\DataType;
use JustQuery\Expression\Value\StructuredValue;
use JustQuery\Query\QueryInterface;
use JustQuery\Schema\Data\JsonLazyArray;
use JustQuery\Schema\Data\LazyArray;
use JustQuery\Schema\Data\LazyArrayInterface;
use JustQuery\Schema\Data\StructuredLazyArray;

use function array_values;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Default expression builder for {@see StructuredValue}. Builds an expression as a JSON.
 */
final class StructuredValueBuilder extends AbstractStructuredValueBuilder
{
    /**
     * @param array<int|string, mixed> $params
     */
    protected function buildStringValue(string $value, StructuredValue $expression, array &$params): string
    {
        $param = new Param($value, DataType::STRING);

        return $this->queryBuilder->bindParam($param, $params); // @phpstan-ignore argument.type
    }

    /**
     * @param array<int|string, mixed> $params
     */
    protected function buildSubquery(QueryInterface $query, StructuredValue $expression, array &$params): string
    {
        [$sql, $params] = $this->queryBuilder->build($query, $params); // @phpstan-ignore argument.type

        return "($sql)";
    }

    /**
     * @param array<string, mixed>|object $value
     * @param array<int|string, mixed> $params
     */
    protected function buildValue(array|object $value, StructuredValue $expression, array &$params): string
    {
        $value = $this->prepareValues($value, $expression);
        $param = new Param(json_encode(array_values($value), JSON_THROW_ON_ERROR), DataType::STRING);

        return $this->queryBuilder->bindParam($param, $params); // @phpstan-ignore argument.type
    }

    /**
     * @return array<string, mixed>|string
     */
    protected function getLazyArrayValue(LazyArrayInterface $value): array|string
    {
        return match ($value::class) { // @phpstan-ignore return.type
            LazyArray::class, JsonLazyArray::class, StructuredLazyArray::class => $value->getRawValue(),
            default => $value->getValue(),
        };
    }
}
