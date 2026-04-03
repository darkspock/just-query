<?php

declare(strict_types=1);

namespace JustQuery\QueryBuilder\Condition\Builder;

use Stringable;
use Traversable;
use JustQuery\Expression\Value\Param;
use JustQuery\Constant\DataType;
use JustQuery\Exception\NotSupportedException;
use JustQuery\Expression\ExpressionBuilderInterface;
use JustQuery\Expression\ExpressionInterface;
use JustQuery\QueryBuilder\Condition\Like;
use JustQuery\QueryBuilder\Condition\LikeConjunction;
use JustQuery\QueryBuilder\Condition\LikeMode;
use JustQuery\QueryBuilder\Condition\NotLike;
use JustQuery\QueryBuilder\QueryBuilderInterface;

use function implode;
use function is_string;
use function strtr;

/**
 * Build an object of {@see Like} or {@see NotLike} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<Like|NotLike>
 */
class LikeBuilder implements ExpressionBuilderInterface
{
    /**
     * @var string SQL fragment to append to the end of `LIKE` conditions.
     */
    protected const ESCAPE_SQL = '';

    /**
     * @var array<string, string> Map of chars to their replacements in `LIKE` conditions. By default, it's configured to escape
     * `%`, `_` and `\` with `\`.
     */
    protected array $escapingReplacements = [
        '%' => '\%',
        '_' => '\_',
        '\\' => '\\\\',
    ];

    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    /**
     * Build SQL for {@see Like} or {@see NotLike}.
     *
     * @param Like|NotLike $expression
     * @param array<int|string, mixed> $params
     *
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $values = $expression->value;

        [$not, $operator] = $this->getOperatorData($expression);

        if ($values === null) {
            return $this->buildForEmptyValue($not);
        }

        if (is_iterable($values)) {
            if ($values instanceof Traversable) {
                $values = iterator_to_array($values);
            }
            if (empty($values)) {
                return $this->buildForEmptyValue($not);
            }
        } else {
            $values = [$values];
        }

        $column = $this->prepareColumn($expression, $params);

        $parts = [];
        foreach ($values as $value) {
            /** @var ExpressionInterface|int|string|Stringable $value */
            $placeholderName = $this->preparePlaceholderName($value, $expression, $params);
            $parts[] = "$column $operator $placeholderName" . static::ESCAPE_SQL;
        }

        $conjunction = match ($expression->conjunction) {
            LikeConjunction::And => ' AND ',
            LikeConjunction::Or => ' OR ',
        };

        return implode($conjunction, $parts);
    }

    /**
     * Prepare column to use in SQL.
     *
     * @param array<int|string, mixed> $params
     *
     * @throws NotSupportedException
     */
    protected function prepareColumn(Like|NotLike $condition, array &$params): string
    {
        $column = $condition->column;

        if ($column instanceof ExpressionInterface) {
            /** @phpstan-ignore argument.type */
            return $this->queryBuilder->buildExpression($column, $params);
        }

        return $this->queryBuilder->getQuoter()->quoteColumnName($column);
    }

    /**
     * Prepare value to use in SQL.
     *
     * @param array<int|string, mixed> $params
     *
     * @throws NotSupportedException
     */
    protected function preparePlaceholderName(
        string|Stringable|int|ExpressionInterface $value,
        Like|NotLike $condition,
        array &$params,
    ): string {
        if ($value instanceof ExpressionInterface) {
            /** @phpstan-ignore argument.type */
            return $this->queryBuilder->buildExpression($value, $params);
        }

        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if (is_string($value) && $condition->escape) {
            $value = strtr($value, $this->escapingReplacements);
        }

        $value = match ($condition->mode) {
            LikeMode::Contains => '%' . $value . '%',
            LikeMode::StartsWith => $value . '%',
            LikeMode::EndsWith => '%' . $value,
            LikeMode::Custom => (string) $value,
        };

        /** @phpstan-ignore argument.type */
        return $this->queryBuilder->bindParam(new Param($value, DataType::STRING), $params);
    }

    /**
     * Get operator and `not` flag for the given condition.
     *
     * @psalm-return array{0: bool, 1: string}
     */
    protected function getOperatorData(Like|NotLike $condition): array
    {
        return match ($condition::class) {
            Like::class => [false, 'LIKE'],
            NotLike::class => [true, 'NOT LIKE'],
        };
    }

    private function buildForEmptyValue(bool $not): string
    {
        return $not ? '' : '0=1';
    }
}
