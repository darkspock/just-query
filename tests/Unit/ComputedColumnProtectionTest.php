<?php

declare(strict_types=1);

namespace JustQuery\Tests\Unit;

use JustQuery\Connection\ConnectionInterface;
use JustQuery\Connection\ServerInfoInterface;
use JustQuery\Constraint\Index;
use JustQuery\Driver\Mysql\QueryBuilder;
use JustQuery\Driver\Mysql\Quoter;
use JustQuery\Expression\Value\Param;
use JustQuery\Schema\Column\ColumnBuilder;
use JustQuery\Schema\SchemaInterface;
use JustQuery\Schema\TableSchema;
use PHPUnit\Framework\TestCase;

final class ComputedColumnProtectionTest extends TestCase
{
    private QueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        $tableSchema = (new TableSchema('products'))
            ->column('id', ColumnBuilder::primaryKey())
            ->column('name', ColumnBuilder::string())
            ->column('price', ColumnBuilder::decimal(10, 2))
            ->column('score', ColumnBuilder::float()->computed());

        $schema = $this->createMock(SchemaInterface::class);
        $schema->method('getTableSchema')->willReturnCallback(
            static fn (string $name): ?TableSchema => $name === 'products' ? $tableSchema : null,
        );
        $schema->method('getTableUniques')->willReturnCallback(
            static fn (string $name): array => $name === 'products'
                ? [new Index('uq_products_name', ['name'], true, false)]
                : [],
        );

        $serverInfo = $this->createMock(ServerInfoInterface::class);
        $serverInfo->method('getVersion')->willReturn('8.0.36');
        $serverInfo->method('getTimezone')->willReturn('+00:00');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('getQuoter')->willReturn(new Quoter('`', '`'));
        $connection->method('getSchema')->willReturn($schema);
        $connection->method('getServerInfo')->willReturn($serverInfo);

        $this->queryBuilder = new QueryBuilder($connection);
    }

    public function testInsertExcludesComputedAndAutoIncrementColumns(): void
    {
        $params = [];
        $sql = $this->queryBuilder->insert('products', [
            'id' => 42,
            'name' => 'Widget',
            'price' => 9.99,
            'score' => 4.5,
        ], $params);

        self::assertSame(
            'INSERT INTO `products` (`name`, `price`) VALUES (:qp0, 9.99)',
            $sql,
        );
        $this->assertParamValues([':qp0' => 'Widget'], $params);
    }

    public function testInsertFallsBackToDefaultValuesWhenOnlyExcludedColumnsRemain(): void
    {
        $params = [];
        $sql = $this->queryBuilder->insert('products', [
            'id' => 42,
            'score' => 4.5,
        ], $params);

        self::assertSame('INSERT INTO `products` VALUES ()', $sql);
        self::assertSame([], $params);
    }

    public function testUpdateExcludesComputedAndAutoIncrementColumns(): void
    {
        $params = [];
        $sql = $this->queryBuilder->update('products', [
            'id' => 42,
            'price' => 9.99,
            'score' => 4.5,
        ], ['name' => 'Widget'], null, $params);

        self::assertSame(
            'UPDATE `products` SET `price`=9.99 WHERE `name` = :qp0',
            $sql,
        );
        $this->assertParamValues([':qp0' => 'Widget'], $params);
    }

    public function testUpdateThrowsWhenNoWritableColumnsRemain(): void
    {
        $params = [];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("No writable columns supplied for update of table 'products'.");

        $this->queryBuilder->update('products', [
            'id' => 42,
            'score' => 4.5,
        ], ['name' => 'Widget'], null, $params);
    }

    public function testUpsertSkipsComputedColumnsAndDoesNotUpdateAutoIncrementKey(): void
    {
        $params = [];
        $sql = $this->queryBuilder->upsert('products', [
            'id' => 42,
            'name' => 'Widget',
            'price' => 9.99,
            'score' => 4.5,
        ], true, $params);

        self::assertStringContainsString(
            'INSERT INTO `products` (`id`, `name`, `price`)',
            $sql,
        );
        self::assertStringContainsString(
            'ON DUPLICATE KEY UPDATE `price`=EXCLUDED.`price`',
            $sql,
        );
        self::assertStringNotContainsString('`score`', $sql);
        self::assertStringNotContainsString('`id`=EXCLUDED.`id`', $sql);
        $this->assertParamValues([':qp0' => 'Widget'], $params);
    }

    public function testInsertBatchKeepsValueAlignmentWhenSkippingComputedColumns(): void
    {
        $params = [];
        $sql = $this->queryBuilder->insertBatch(
            'products',
            [['Widget', 4.5, 9.99]],
            ['name', 'score', 'price'],
            $params,
        );

        self::assertSame(
            'INSERT INTO `products` (`name`, `price`) VALUES (:qp0, 9.99)',
            $sql,
        );
        $this->assertParamValues([':qp0' => 'Widget'], $params);
    }

    /**
     * @param array<string, mixed> $expected
     * @param array<string, Param> $params
     */
    private function assertParamValues(array $expected, array $params): void
    {
        $actual = [];

        foreach ($params as $name => $param) {
            $actual[$name] = $param->value;
        }

        self::assertSame($expected, $actual);
    }
}
