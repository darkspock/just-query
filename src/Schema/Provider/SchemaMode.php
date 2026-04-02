<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Schema\Provider;

enum SchemaMode
{
    /** No schema awareness. No typecasting. Pure query builder. */
    case DISABLED;

    /** Read schema from JSON files only. Zero DB overhead. Deploy-safe. */
    case JSON;

    /** Read schema from DB, cache in PSR-16 (Redis/APCu/file). Original behavior. */
    case CACHE;

    /** JSON first. If table not found in JSON, fall back to DB + cache. */
    case JSON_CACHE;
}
