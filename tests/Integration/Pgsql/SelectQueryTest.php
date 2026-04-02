<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Tests\Integration\Pgsql;

use FastPHP\QueryBuilder\Query\Query;

final class SelectQueryTest extends PgsqlTestCase
{
    protected function setUp(): void
    {
        $this->createTestTable();
        $this->seedUsers();
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
    }

    public function testSelectAll(): void
    {
        $rows = (new Query($this->getDb()))
            ->from('test_users')
            ->all();

        self::assertCount(5, $rows);
    }

    public function testSelectColumns(): void
    {
        $row = (new Query($this->getDb()))
            ->select(['name', 'email'])
            ->from('test_users')
            ->where(['name' => 'Alice'])
            ->one();

        self::assertSame('Alice', $row['name']);
        self::assertSame('alice@example.com', $row['email']);
        self::assertArrayNotHasKey('id', $row);
    }

    public function testWhereEquals(): void
    {
        $rows = (new Query($this->getDb()))
            ->from('test_users')
            ->where(['is_active' => true])
            ->all();

        self::assertCount(4, $rows);
    }

    public function testWhereOperator(): void
    {
        $rows = (new Query($this->getDb()))
            ->from('test_users')
            ->where(['>', 'age', 28])
            ->all();

        self::assertCount(2, $rows);
    }

    public function testWhereIn(): void
    {
        $rows = (new Query($this->getDb()))
            ->from('test_users')
            ->where(['in', 'name', ['Alice', 'Bob', 'Eve']])
            ->all();

        self::assertCount(3, $rows);
    }

    public function testWhereLike(): void
    {
        $rows = (new Query($this->getDb()))
            ->from('test_users')
            ->where(['like', 'email', 'example.com'])
            ->all();

        self::assertCount(4, $rows);
    }

    public function testWhereBetween(): void
    {
        $rows = (new Query($this->getDb()))
            ->from('test_users')
            ->where(['between', 'age', 25, 30])
            ->all();

        self::assertCount(3, $rows);
    }

    public function testWhereNull(): void
    {
        $rows = (new Query($this->getDb()))
            ->from('test_users')
            ->where(['email' => null])
            ->all();

        self::assertCount(1, $rows);
        self::assertSame('Eve', $rows[0]['name']);
    }

    public function testWhereOr(): void
    {
        $rows = (new Query($this->getDb()))
            ->from('test_users')
            ->where([
                'or',
                ['name' => 'Alice'],
                ['name' => 'Bob'],
            ])
            ->all();

        self::assertCount(2, $rows);
    }

    public function testWhereAndOr(): void
    {
        $rows = (new Query($this->getDb()))
            ->from('test_users')
            ->where([
                'and',
                ['is_active' => true],
                ['or', ['>', 'balance', 1000], ['name' => 'Eve']],
            ])
            ->all();

        self::assertCount(3, $rows);
    }

    public function testOrderBy(): void
    {
        $rows = (new Query($this->getDb()))
            ->select(['name'])
            ->from('test_users')
            ->orderBy(['age' => SORT_ASC])
            ->all();

        self::assertSame('Eve', $rows[0]['name']);
        self::assertSame('Charlie', $rows[4]['name']);
    }

    public function testLimitOffset(): void
    {
        $rows = (new Query($this->getDb()))
            ->from('test_users')
            ->orderBy(['id' => SORT_ASC])
            ->limit(2)
            ->offset(1)
            ->all();

        self::assertCount(2, $rows);
        self::assertSame('Bob', $rows[0]['name']);
        self::assertSame('Charlie', $rows[1]['name']);
    }

    public function testDistinct(): void
    {
        $rows = (new Query($this->getDb()))
            ->select(['is_active'])
            ->distinct()
            ->from('test_users')
            ->all();

        self::assertCount(2, $rows);
    }

    public function testCount(): void
    {
        $count = (new Query($this->getDb()))
            ->from('test_users')
            ->where(['is_active' => true])
            ->count('*');

        self::assertSame(4, $count);
    }

    public function testSum(): void
    {
        $sum = (new Query($this->getDb()))
            ->from('test_users')
            ->sum('balance');

        self::assertEqualsWithDelta(5051.25, (float) $sum, 0.01);
    }

    public function testMax(): void
    {
        $max = (new Query($this->getDb()))
            ->from('test_users')
            ->max('age');

        self::assertSame(35, (int) $max);
    }

    public function testMin(): void
    {
        $min = (new Query($this->getDb()))
            ->from('test_users')
            ->min('age');

        self::assertSame(22, (int) $min);
    }

    public function testAvg(): void
    {
        $avg = (new Query($this->getDb()))
            ->from('test_users')
            ->avg('age');

        self::assertEqualsWithDelta(28.0, (float) $avg, 0.01);
    }

    public function testGroupByHaving(): void
    {
        $this->createOrdersTable();
        $this->seedOrders();

        $rows = (new Query($this->getDb()))
            ->select(['user_id', 'COUNT(*) as order_count'])
            ->from('test_orders')
            ->groupBy('user_id')
            ->having(['>', 'COUNT(*)', 1])
            ->all();

        self::assertCount(2, $rows);
    }

    public function testOneReturnsNullWhenEmpty(): void
    {
        $row = (new Query($this->getDb()))
            ->from('test_users')
            ->where(['name' => 'NonExistent'])
            ->one();

        self::assertNull($row);
    }

    public function testScalar(): void
    {
        $name = (new Query($this->getDb()))
            ->select(['name'])
            ->from('test_users')
            ->where(['id' => 1])
            ->scalar();

        self::assertSame('Alice', $name);
    }

    public function testColumn(): void
    {
        $names = (new Query($this->getDb()))
            ->select(['name'])
            ->from('test_users')
            ->orderBy(['name' => SORT_ASC])
            ->column();

        self::assertSame(['Alice', 'Bob', 'Charlie', 'Diana', 'Eve'], $names);
    }

    public function testFilterWhereSkipsEmpty(): void
    {
        $rows = (new Query($this->getDb()))
            ->from('test_users')
            ->filterWhere([
                'name' => 'Alice',
                'email' => '',
                'config' => null,
            ])
            ->all();

        self::assertCount(1, $rows);
        self::assertSame('Alice', $rows[0]['name']);
    }

    public function testExists(): void
    {
        $exists = (new Query($this->getDb()))
            ->from('test_users')
            ->where(['name' => 'Alice'])
            ->exists();

        self::assertTrue($exists);

        $notExists = (new Query($this->getDb()))
            ->from('test_users')
            ->where(['name' => 'NonExistent'])
            ->exists();

        self::assertFalse($notExists);
    }
}
