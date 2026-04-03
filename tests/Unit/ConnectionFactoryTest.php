<?php

declare(strict_types=1);

namespace JustQuery\Tests\Unit;

use PDO;
use JustQuery\Driver\Mysql\Connection as MysqlConnection;
use JustQuery\Driver\Pgsql\Connection as PgsqlConnection;
use PHPUnit\Framework\TestCase;

final class ConnectionFactoryTest extends TestCase
{
    public function testMysqlFromDsnBuildsDriverConfiguration(): void
    {
        $db = MysqlConnection::fromDsn(
            'mysql:host=127.0.0.1;dbname=myapp;port=3306',
            'root',
            'secret',
            [PDO::ATTR_TIMEOUT => 3],
        );

        self::assertSame('mysql', $db->getDriverName());
        self::assertSame('mysql:host=127.0.0.1;dbname=myapp;port=3306', $db->getDriver()->getDsn());
        self::assertSame('root', $db->getDriver()->getUsername());
        self::assertSame('secret', $db->getDriver()->getPassword());
        self::assertFalse($db->isActive());
    }

    public function testPgsqlFromDsnBuildsDriverConfiguration(): void
    {
        $db = PgsqlConnection::fromDsn(
            'pgsql:host=127.0.0.1;dbname=myapp;port=5432',
            'postgres',
            'secret',
        );

        self::assertSame('pgsql', $db->getDriverName());
        self::assertSame('pgsql:host=127.0.0.1;dbname=myapp;port=5432', $db->getDriver()->getDsn());
        self::assertSame('postgres', $db->getDriver()->getUsername());
        self::assertSame('secret', $db->getDriver()->getPassword());
        self::assertFalse($db->isActive());
    }

    public function testSharedPdoCanBeInjectedAndReopened(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $db = MysqlConnection::fromPdo($pdo);

        self::assertSame($pdo, $db->getPdo());
        self::assertTrue($db->isActive());

        $db->close();

        self::assertFalse($db->isActive());
        self::assertNull($db->getPdo());

        $db->open();

        self::assertSame($pdo, $db->getActivePdo());

        $otherPdo = new PDO('sqlite::memory:');
        $db->setSharedPdo($otherPdo);

        self::assertSame($otherPdo, $db->getPdo());
    }
}
