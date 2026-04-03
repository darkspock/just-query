<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pdo;

final class LogType
{
    public const CONNECTION = 'connection';
    public const QUERY = 'query';
    public const TRANSACTION = 'transaction';
}
