<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\QueryBuilder\Condition;

/**
 * Condition that represents `NOT EXISTS` operator that checks if a sub-query returns no rows.
 */
final class NotExists extends AbstractExists {}
