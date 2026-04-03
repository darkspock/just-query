<?php

declare(strict_types=1);

namespace JustQuery\Tests\Unit;

use JustQuery\Schema\Provider\JsonSchemaReader;
use PHPUnit\Framework\TestCase;

final class JsonSchemaReaderTest extends TestCase
{
    private string $tmpFile;

    protected function setUp(): void
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'schema_') . '.json';
        file_put_contents($this->tmpFile, json_encode([
            'users' => [
                'id' => ['type' => 'integer', 'primaryKey' => true, 'autoIncrement' => true],
                'name' => ['type' => 'string', 'size' => 255, 'notNull' => true],
                'email' => ['type' => 'string'],
                'is_active' => ['type' => 'boolean'],
                'balance' => ['type' => 'float', 'scale' => 2],
                'config' => ['type' => 'json'],
                'score' => ['type' => 'float', 'computed' => true],
            ],
            'orders' => [
                'id' => ['type' => 'integer', 'primaryKey' => true],
                'total' => ['type' => 'float'],
            ],
        ]));
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    public function testLoadsTablesFromFile(): void
    {
        $reader = new JsonSchemaReader($this->tmpFile);

        self::assertTrue($reader->hasTable('users'));
        self::assertTrue($reader->hasTable('orders'));
        self::assertFalse($reader->hasTable('nonexistent'));
    }

    public function testReturnsNullForUnknownTable(): void
    {
        $reader = new JsonSchemaReader($this->tmpFile);

        self::assertNull($reader->getTableSchema('nonexistent'));
    }

    public function testTableName(): void
    {
        $reader = new JsonSchemaReader($this->tmpFile);
        $schema = $reader->getTableSchema('users');

        self::assertNotNull($schema);
        self::assertSame('users', $schema->getName());
    }

    public function testColumnNames(): void
    {
        $reader = new JsonSchemaReader($this->tmpFile);
        $schema = $reader->getTableSchema('users');

        self::assertSame(['id', 'name', 'email', 'is_active', 'balance', 'config', 'score'], $schema->getColumnNames());
    }

    public function testColumnTypes(): void
    {
        $reader = new JsonSchemaReader($this->tmpFile);
        $schema = $reader->getTableSchema('users');
        $columns = $schema->getColumns();

        self::assertSame('integer', $columns['id']->getType());
        self::assertSame('string', $columns['name']->getType());
        self::assertSame('boolean', $columns['is_active']->getType());
        self::assertSame('double', $columns['balance']->getType());
        self::assertSame('json', $columns['config']->getType());
    }

    public function testColumnFlags(): void
    {
        $reader = new JsonSchemaReader($this->tmpFile);
        $schema = $reader->getTableSchema('users');
        $columns = $schema->getColumns();

        self::assertTrue($columns['id']->isPrimaryKey());
        self::assertTrue($columns['id']->isAutoIncrement());
        self::assertTrue($columns['name']->isNotNull());
        self::assertTrue($columns['score']->isComputed());
        self::assertFalse($columns['email']->isPrimaryKey());
        self::assertFalse($columns['email']->isComputed());
    }

    public function testColumnSize(): void
    {
        $reader = new JsonSchemaReader($this->tmpFile);
        $schema = $reader->getTableSchema('users');
        $columns = $schema->getColumns();

        self::assertSame(255, $columns['name']->getSize());
        self::assertSame(2, $columns['balance']->getScale());
    }

    public function testLoadsFromDirectory(): void
    {
        $dir = sys_get_temp_dir() . '/fastphp_schema_test_' . uniqid();
        mkdir($dir);
        file_put_contents($dir . '/products.json', json_encode([
            'products' => [
                'id' => ['type' => 'integer', 'primaryKey' => true],
                'name' => ['type' => 'string'],
            ],
        ]));
        file_put_contents($dir . '/categories.json', json_encode([
            'categories' => [
                'id' => ['type' => 'integer', 'primaryKey' => true],
                'label' => ['type' => 'string'],
            ],
        ]));

        $reader = new JsonSchemaReader($dir);

        self::assertTrue($reader->hasTable('products'));
        self::assertTrue($reader->hasTable('categories'));
        self::assertFalse($reader->hasTable('users'));

        unlink($dir . '/products.json');
        unlink($dir . '/categories.json');
        rmdir($dir);
    }

    public function testInvalidPathReturnsNull(): void
    {
        $reader = new JsonSchemaReader('/nonexistent/path');

        self::assertNull($reader->getTableSchema('anything'));
        self::assertFalse($reader->hasTable('anything'));
    }
}
