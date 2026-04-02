<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pdo;

use FastPHP\QueryBuilder\Exception\NotSupportedException;
use FastPHP\QueryBuilder\Schema\AbstractSchema;

use function md5;
use function serialize;

/**
 * Represents a schema for a PDO (PHP Data Object) connection.
 */
abstract class AbstractPdoSchema extends AbstractSchema
{
    /**
     * Generates the cache key for the current connection.
     *
     * @throws NotSupportedException If the connection is not a PDO connection.
     *
     * @return array<int, string> The cache key.
     */
    protected function generateCacheKey(): array
    {
        if ($this->db instanceof PdoConnectionInterface) {
            $cacheKey = [$this->db->getDriver()->getDsn(), $this->db->getDriver()->getUsername()];
        } else {
            throw new NotSupportedException('Only PDO connections are supported.');
        }

        return $cacheKey;
    }

    /**
     * @return array<int, string>
     */
    protected function getCacheKey(string $name): array // @phpstan-ignore method.childReturnType
    {
        return [static::class, ...$this->generateCacheKey(), $name];
    }

    protected function getCacheTag(): string
    {
        return md5(serialize([static::class, ...$this->generateCacheKey()]));
    }

    protected function getResultColumnCacheKey(array $metadata): string
    {
        return md5(serialize([static::class . '::getResultColumn', ...$this->generateCacheKey(), ...$metadata]));
    }
}
