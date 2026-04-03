<?php

declare(strict_types=1);

namespace JustQuery\Tests\Integration\Pgsql;

final class ConnectionTest extends PgsqlTestCase
{
    public function testConnects(): void
    {
        $db = $this->getDb();
        $db->open();

        self::assertTrue($db->isActive());
    }

    public function testRawQuery(): void
    {
        $result = $this->getDb()->createCommand('SELECT 1 as val')->queryScalar();

        self::assertSame(1, (int) $result);
    }

    public function testServerVersion(): void
    {
        $version = $this->getDb()->getServerInfo()->getVersion();

        self::assertNotEmpty($version);
        self::assertMatchesRegularExpression('/^\d+\.\d+/', $version);
    }
}
