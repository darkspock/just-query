<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Tests\Integration\Mysql;

use FastPHP\QueryBuilder\Cache\SchemaCache;
use FastPHP\QueryBuilder\Driver\Mysql\Connection;
use FastPHP\QueryBuilder\Driver\Mysql\Driver;
use FastPHP\QueryBuilder\Driver\Mysql\Dsn;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

abstract class MysqlTestCase extends TestCase
{
    protected static ?Connection $db = null;

    protected function getDb(): Connection
    {
        if (self::$db !== null && self::$db->isActive()) {
            return self::$db;
        }

        $dsn = getenv('MYSQL_DSN') ?: 'mysql:host=127.0.0.1;port=13306;dbname=fastphp_test';
        $user = getenv('MYSQL_USER') ?: 'root';
        $password = getenv('MYSQL_PASSWORD') ?: 'root';

        $driver = new Driver($dsn, $user, $password);
        $schemaCache = new SchemaCache($this->createNullCache());
        self::$db = new Connection($driver, $schemaCache);

        return self::$db;
    }

    protected function createTestTable(): void
    {
        $db = $this->getDb();
        $db->createCommand('DROP TABLE IF EXISTS `test_users`')->execute();
        $db->createCommand(<<<SQL
            CREATE TABLE `test_users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `email` VARCHAR(255),
                `age` INT DEFAULT 0,
                `balance` DECIMAL(10,2) DEFAULT 0.00,
                `is_active` TINYINT(1) DEFAULT 1,
                `config` TEXT,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL)->execute();
    }

    protected function createOrdersTable(): void
    {
        $db = $this->getDb();
        $db->createCommand('DROP TABLE IF EXISTS `test_orders`')->execute();
        $db->createCommand(<<<SQL
            CREATE TABLE `test_orders` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `product` VARCHAR(255) NOT NULL,
                `amount` DECIMAL(10,2) NOT NULL,
                `status` VARCHAR(50) DEFAULT 'pending'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL)->execute();
    }

    protected function seedUsers(): void
    {
        $db = $this->getDb();
        $db->createCommand(<<<SQL
            INSERT INTO `test_users` (`name`, `email`, `age`, `balance`, `is_active`, `config`) VALUES
            ('Alice', 'alice@example.com', 30, 1500.50, 1, '{"role":"admin"}'),
            ('Bob', 'bob@example.com', 25, 250.00, 1, '{"role":"user"}'),
            ('Charlie', 'charlie@example.com', 35, 0.00, 0, NULL),
            ('Diana', 'diana@example.com', 28, 3200.75, 1, '{"role":"user","tags":["vip"]}'),
            ('Eve', NULL, 22, 100.00, 1, NULL)
        SQL)->execute();
    }

    protected function seedOrders(): void
    {
        $db = $this->getDb();
        $db->createCommand(<<<SQL
            INSERT INTO `test_orders` (`user_id`, `product`, `amount`, `status`) VALUES
            (1, 'Widget A', 99.99, 'completed'),
            (1, 'Widget B', 149.50, 'completed'),
            (2, 'Gadget X', 299.00, 'pending'),
            (3, 'Widget A', 99.99, 'cancelled'),
            (4, 'Gadget Y', 450.00, 'completed'),
            (4, 'Widget C', 75.25, 'pending')
        SQL)->execute();
    }

    protected function cleanUp(): void
    {
        $db = $this->getDb();
        $db->createCommand('DROP TABLE IF EXISTS `test_orders`')->execute();
        $db->createCommand('DROP TABLE IF EXISTS `test_users`')->execute();
    }

    private function createNullCache(): CacheInterface
    {
        return new class implements CacheInterface {
            public function get(string $key, mixed $default = null): mixed { return $default; }
            public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool { return true; }
            public function delete(string $key): bool { return true; }
            public function clear(): bool { return true; }
            public function getMultiple(iterable $keys, mixed $default = null): iterable { return []; }
            public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool { return true; }
            public function deleteMultiple(iterable $keys): bool { return true; }
            public function has(string $key): bool { return false; }
        };
    }
}
