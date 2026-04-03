<?php

declare(strict_types=1);

namespace JustQuery\Tests\Integration\Pgsql;

use JustQuery\Query\Query;

final class TransactionTest extends PgsqlTestCase
{
    protected function setUp(): void
    {
        $this->createTestTable();
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
    }

    public function testCommit(): void
    {
        $db = $this->getDb();
        $transaction = $db->beginTransaction();

        $db->createCommand()->insert('test_users', ['name' => 'TX User'])->execute();
        $transaction->commit();

        $count = (new Query($db))->from('test_users')->count('*');
        self::assertSame(1, $count);
    }

    public function testRollback(): void
    {
        $db = $this->getDb();
        $transaction = $db->beginTransaction();

        $db->createCommand()->insert('test_users', ['name' => 'TX User'])->execute();
        $transaction->rollBack();

        $count = (new Query($db))->from('test_users')->count('*');
        self::assertSame(0, $count);
    }

    public function testTransactionClosure(): void
    {
        $db = $this->getDb();

        $db->transaction(function () use ($db) {
            $db->createCommand()->insert('test_users', ['name' => 'User 1'])->execute();
            $db->createCommand()->insert('test_users', ['name' => 'User 2'])->execute();
        });

        $count = (new Query($db))->from('test_users')->count('*');
        self::assertSame(2, $count);
    }

    public function testTransactionClosureRollbackOnException(): void
    {
        $db = $this->getDb();

        try {
            $db->transaction(function () use ($db) {
                $db->createCommand()->insert('test_users', ['name' => 'User 1'])->execute();
                throw new \RuntimeException('Simulated error');
            });
        } catch (\RuntimeException) {
            // expected
        }

        $count = (new Query($db))->from('test_users')->count('*');
        self::assertSame(0, $count);
    }
}
