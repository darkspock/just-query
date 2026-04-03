<?php

declare(strict_types=1);

namespace JustQuery\Tests\Unit;

use JustQuery\Schema\Provider\SchemaMode;
use JustQuery\Schema\Provider\SchemaProvider;
use PHPUnit\Framework\TestCase;

final class SchemaProviderTest extends TestCase
{
    private string $tmpFile;

    protected function setUp(): void
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'schema_') . '.json';
        file_put_contents($this->tmpFile, json_encode([
            'users' => [
                'id' => ['type' => 'integer', 'primaryKey' => true, 'autoIncrement' => true],
                'name' => ['type' => 'string'],
                'total' => ['type' => 'float', 'computed' => true],
            ],
        ]));
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    public function testDisabledModeReturnsNull(): void
    {
        $provider = new SchemaProvider(SchemaMode::DISABLED);

        self::assertNull($provider->getTableSchema('users'));
        self::assertFalse($provider->isEnabled());
    }

    public function testJsonModeReturnsSchema(): void
    {
        $provider = new SchemaProvider(SchemaMode::JSON, jsonPath: $this->tmpFile);

        $schema = $provider->getTableSchema('users');
        self::assertNotNull($schema);
        self::assertSame('users', $schema->getName());
        self::assertTrue($provider->isEnabled());
    }

    public function testJsonModeReturnsNullForUnknownTable(): void
    {
        $provider = new SchemaProvider(SchemaMode::JSON, jsonPath: $this->tmpFile);

        self::assertNull($provider->getTableSchema('nonexistent'));
    }

    public function testGetWriteExclusions(): void
    {
        $provider = new SchemaProvider(SchemaMode::JSON, jsonPath: $this->tmpFile);
        $exclusions = $provider->getWriteExclusions('users');

        self::assertSame(['computed', 'autoIncrement'], array_keys($exclusions));
        self::assertSame(['total'], $exclusions['computed']);
        self::assertSame(['id'], $exclusions['autoIncrement']);
    }

    public function testGetWriteExclusionsForUnknownTable(): void
    {
        $provider = new SchemaProvider(SchemaMode::JSON, jsonPath: $this->tmpFile);
        $exclusions = $provider->getWriteExclusions('nonexistent');

        self::assertSame([], $exclusions['computed']);
        self::assertSame([], $exclusions['autoIncrement']);
    }

    public function testDisabledModeWriteExclusions(): void
    {
        $provider = new SchemaProvider(SchemaMode::DISABLED);
        $exclusions = $provider->getWriteExclusions('users');

        self::assertSame([], $exclusions['computed']);
    }

    public function testGetMode(): void
    {
        $provider = new SchemaProvider(SchemaMode::JSON, jsonPath: $this->tmpFile);
        self::assertSame(SchemaMode::JSON, $provider->getMode());

        $provider = new SchemaProvider(SchemaMode::DISABLED);
        self::assertSame(SchemaMode::DISABLED, $provider->getMode());
    }

    public function testJsonModeRequiresPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SchemaProvider(SchemaMode::JSON);
    }

    public function testCacheModeRequiresDbSchema(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SchemaProvider(SchemaMode::CACHE);
    }
}
