<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Connection;

use Closure;
use Throwable;
use FastPHP\QueryBuilder\Expression\ExpressionInterface;
use FastPHP\QueryBuilder\Query\BatchQueryResult;
use FastPHP\QueryBuilder\Query\BatchQueryResultInterface;
use FastPHP\QueryBuilder\Query\Query;
use FastPHP\QueryBuilder\Query\QueryInterface;
use FastPHP\QueryBuilder\Schema\Column\ColumnBuilder;
use FastPHP\QueryBuilder\Schema\TableSchemaInterface;
use FastPHP\QueryBuilder\Transaction\TransactionInterface;

/**
 * Represents a connection to a database.
 *
 * It provides methods for interacting with the database, such as executing SQL queries and performing data
 * manipulation.
 */
abstract class AbstractConnection implements ConnectionInterface
{
    protected ?TransactionInterface $transaction = null;
    private bool $enableSavepoint = true;
    private string $tablePrefix = '';

    public function beginTransaction(?string $isolationLevel = null): TransactionInterface
    {
        $this->open();
        $this->transaction = $this->getTransaction();

        if ($this->transaction === null) {
            $this->transaction = $this->createTransaction();
        }

        $this->transaction->begin($isolationLevel);

        return $this->transaction;
    }

    public function createBatchQueryResult(QueryInterface $query): BatchQueryResultInterface
    {
        return (new BatchQueryResult($query))
            ->indexBy($query->getIndexBy())
            ->resultCallback($query->getResultCallback());
    }

    public function createQuery(): QueryInterface
    {
        return new Query($this);
    }

    public function getColumnBuilderClass(): string
    {
        return ColumnBuilder::class;
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function getTableSchema(string $name, bool $refresh = false): ?TableSchemaInterface
    {
        return $this->getSchema()->getTableSchema($name, $refresh);
    }

    public function getTransaction(): ?TransactionInterface
    {
        return $this->transaction && $this->transaction->isActive() ? $this->transaction : null;
    }

    public function isSavepointEnabled(): bool
    {
        return $this->enableSavepoint;
    }

    public function setEnableSavepoint(bool $value): void
    {
        $this->enableSavepoint = $value;
    }

    public function select(
        array|bool|float|int|string|ExpressionInterface $columns = [],
        ?string $option = null,
    ): QueryInterface {
        return $this->createQuery()->select($columns, $option);
    }

    public function setTablePrefix(string $value): void
    {
        $this->tablePrefix = $value;
    }

    public function transaction(Closure $closure, ?string $isolationLevel = null): mixed
    {
        $transaction = $this->beginTransaction($isolationLevel);
        $level = $transaction->getLevel();

        try {
            $result = $closure($this);

            if ($transaction->isActive() && $transaction->getLevel() === $level) {
                $transaction->commit();
            }
        } catch (Throwable $e) {
            $this->rollbackTransactionOnLevel($transaction, $level);

            throw $e;
        }

        return $result;
    }

    /**
     * Rolls back given {@see TransactionInterface} an object if it's still active and level match.
     *
     * Sometimes, rollback can fail, so this method is fail-safe.
     *
     * @param TransactionInterface $transaction TransactionInterface object given from {@see beginTransaction()}.
     * @param int $level TransactionInterface level just after {@see beginTransaction()} call.
     *
     * @throws Throwable If transaction wasn't rolled back.
     */
    protected function rollbackTransactionOnLevel(TransactionInterface $transaction, int $level): void
    {
        if ($transaction->isActive() && $transaction->getLevel() === $level) {
            /**
             * @link https://github.com/yiisoft/yii2/pull/13347
             */
            try {
                $transaction->rollBack();
            } catch (Throwable) {
                /** hide this exception to be able to continue throwing original exception outside */
            }
        }
    }
}
