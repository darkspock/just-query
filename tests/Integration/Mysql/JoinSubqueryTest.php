<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Tests\Integration\Mysql;

use FastPHP\QueryBuilder\Query\Query;

final class JoinSubqueryTest extends MysqlTestCase
{
    protected function setUp(): void
    {
        $this->createTestTable();
        $this->createOrdersTable();
        $this->seedUsers();
        $this->seedOrders();
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
    }

    public function testInnerJoin(): void
    {
        $rows = (new Query($this->getDb()))
            ->select(['test_users.name', 'test_orders.product', 'test_orders.amount'])
            ->from('test_users')
            ->innerJoin('test_orders', 'test_orders.user_id = test_users.id')
            ->orderBy(['test_orders.amount' => SORT_DESC])
            ->all();

        self::assertCount(6, $rows);
        self::assertSame('Gadget Y', $rows[0]['product']); // 450.00
    }

    public function testLeftJoin(): void
    {
        $rows = (new Query($this->getDb()))
            ->select(['test_users.name', 'test_orders.product'])
            ->from('test_users')
            ->leftJoin('test_orders', 'test_orders.user_id = test_users.id')
            ->where(['test_orders.product' => null])
            ->all();

        self::assertCount(1, $rows);
        self::assertSame('Eve', $rows[0]['name']); // no orders
    }

    public function testSubqueryInWhere(): void
    {
        $subQuery = (new Query($this->getDb()))
            ->select(['user_id'])
            ->from('test_orders')
            ->where(['status' => 'completed']);

        $rows = (new Query($this->getDb()))
            ->from('test_users')
            ->where(['in', 'id', $subQuery])
            ->all();

        self::assertCount(2, $rows); // Alice(1) and Diana(4) have completed orders
    }

    public function testSubqueryNotIn(): void
    {
        $subQuery = (new Query($this->getDb()))
            ->select(['user_id'])
            ->from('test_orders');

        $rows = (new Query($this->getDb()))
            ->from('test_users')
            ->where(['not in', 'id', $subQuery])
            ->all();

        self::assertCount(1, $rows); // Eve has no orders
        self::assertSame('Eve', $rows[0]['name']);
    }

    public function testJoinWithGroupBy(): void
    {
        $rows = (new Query($this->getDb()))
            ->select(['test_users.name', 'SUM(test_orders.amount) as total_spent'])
            ->from('test_users')
            ->innerJoin('test_orders', 'test_orders.user_id = test_users.id')
            ->where(['test_orders.status' => 'completed'])
            ->groupBy('test_users.id')
            ->orderBy(['total_spent' => SORT_DESC])
            ->all();

        self::assertCount(2, $rows);
        self::assertSame('Diana', $rows[0]['name']); // 450.00
        self::assertSame('Alice', $rows[1]['name']);  // 99.99 + 149.50 = 249.49
    }
}
