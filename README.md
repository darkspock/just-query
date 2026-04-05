# JustQuery

A high-performance PHP Query Builder built for production SaaS applications where query control, observability, and deploy safety matter.

Full MySQL and PostgreSQL support. Zero framework coupling. Drop into any PHP 8.3+ project.

## Benchmarks

Compared against Eloquent 12.x and Doctrine DBAL 4.x (PHP 8.5, MySQL 8.0, 50k+ rows). Ties Doctrine on reads, **26x faster on batch inserts**, and matches Eloquent's code brevity — without the ORM overhead. [Full comparison](comparison.md).

## Requirements

- PHP >= 8.3
- PDO + pdo_mysql and/or pdo_pgsql

## Installation

```bash
composer require darkspock/just-query
```

## Connection

### MySQL

```php
use JustQuery\Driver\Mysql\Connection;

$db = Connection::fromDsn(
    dsn: 'mysql:host=127.0.0.1;dbname=myapp;port=3306',
    username: 'root',
    password: 'secret',
);

$db->open();
```

### PostgreSQL

```php
use JustQuery\Driver\Pgsql\Connection;

$db = Connection::fromDsn(
    dsn: 'pgsql:host=127.0.0.1;dbname=myapp;port=5432',
    username: 'postgres',
    password: 'secret',
);

$db->open();
```

### Shared PDO (existing connection)

```php
use JustQuery\Driver\Mysql\Connection;

// Reuse a PDO instance from your framework (CodeIgniter, Laravel, etc.)
// Use the matching Connection class for your driver.
$db = Connection::fromPdo($existingPdo);
```

### Advanced Configuration

```php
use JustQuery\Cache\SchemaCache;
use JustQuery\Driver\Mysql\{Connection, Driver};

$driver = new Driver(
    'mysql:host=127.0.0.1;dbname=myapp;port=3306',
    'root',
    'secret',
);

$schemaCache = new SchemaCache($psr16Cache);
$db = new Connection($driver, $schemaCache);
```

### Connection Lifecycle

```php
$db->open();                    // Establish connection
$db->isActive();                // true if connected
$db->close();                   // Close connection
$db->getDriverName();           // 'mysql' or 'pgsql'
$db->getLastInsertId();         // Last auto-increment ID
$db->getSchema();               // SchemaInterface
$db->getTableSchema('users');   // TableSchemaInterface for a specific table
$db->getQueryBuilder();         // QueryBuilderInterface
$db->getQuoter();               // QuoterInterface
```

### Table Prefix

```php
$db->setTablePrefix('tbl_');
// Now {{%users}} resolves to tbl_users in queries
$db->getTablePrefix(); // 'tbl_'
```

## Framework Integration

### Laravel

Register JustQuery as a singleton in a Service Provider. This reuses Laravel's existing PDO connection:

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\ServiceProvider;
use JustQuery\Driver\Mysql\Connection;
use JustQuery\Schema\Provider\{SchemaProvider, SchemaMode};

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Connection::class, function ($app) {
            $pdo = $app['db']->connection()->getPdo();
            $db = Connection::fromPdo($pdo);

            $db->setSchemaProvider(new SchemaProvider(
                SchemaMode::JSON,
                jsonPath: base_path('database/schema/'),
            ));

            return $db;
        });
    }
}
```

Usage anywhere via dependency injection or the container:

```php
use JustQuery\Driver\Mysql\Connection;
use JustQuery\Query\Query;

class UserController extends Controller
{
    public function index(Connection $db)
    {
        return (new Query($db))
            ->from('users')
            ->where(['status' => 'active'])
            ->all();
    }
}
```

### Symfony

Register JustQuery as a service in `services.yaml`:

```yaml
# config/services.yaml
services:
    JustQuery\Driver\Mysql\Connection:
        factory: ['JustQuery\Driver\Mysql\Connection', 'fromDsn']
        arguments:
            $dsn: '%env(DATABASE_DSN)%'
            $username: '%env(DATABASE_USER)%'
            $password: '%env(DATABASE_PASSWORD)%'

    JustQuery\Schema\Provider\SchemaProvider:
        arguments:
            $mode: !php/enum JustQuery\Schema\Provider\SchemaMode::JSON
            $jsonPath: '%kernel.project_dir%/config/schema/'
```

Or reuse Doctrine's existing PDO connection:

```yaml
# config/services.yaml
services:
    JustQuery\Driver\Mysql\Connection:
        factory: ['@App\Factory\JustQueryFactory', 'create']
```

```php
// src/Factory/JustQueryFactory.php
namespace App\Factory;

use Doctrine\DBAL\Connection as DoctrineConnection;
use JustQuery\Driver\Mysql\Connection;

class JustQueryFactory
{
    public function __construct(private DoctrineConnection $doctrine) {}

    public function create(): Connection
    {
        return Connection::fromPdo(
            $this->doctrine->getNativeConnection(),
        );
    }
}
```

Usage via autowiring:

```php
use JustQuery\Driver\Mysql\Connection;
use JustQuery\Query\Query;

class UserController extends AbstractController
{
    public function index(Connection $db): JsonResponse
    {
        $users = (new Query($db))
            ->from('users')
            ->where(['status' => 'active'])
            ->all();

        return $this->json($users);
    }
}
```

## Quick Start

```php
use JustQuery\Query\Query;

// SELECT
$users = (new Query($db))
    ->from('users')
    ->where(['status' => 'active'])
    ->orderBy(['created_at' => SORT_DESC])
    ->limit(10)
    ->all();

// INSERT
$db->createCommand()->insert('users', [
    'name' => 'John',
    'is_active' => true,
    'config' => ['role' => 'admin'],
])->execute();

// UPDATE
$db->createCommand()->update('users', ['name' => 'Jane'], ['id' => 1])->execute();

// DELETE
$db->createCommand()->delete('users', ['id' => 1])->execute();
```

### Factory Methods

```php
// Create a Query from the connection
$query = $db->createQuery();

// Shorthand: create a Query with SELECT columns
$query = $db->select(['id', 'name'])->from('users');

// Create a raw command
$command = $db->createCommand('SELECT * FROM users WHERE id = :id', [':id' => 1]);
```

## Query Builder

### WHERE Conditions

Every standard SQL comparison, plus array/JSON operators:

```php
// Equality
$query->where(['status' => 'active']);
$query->where(['status' => null]);                // IS NULL

// Operators
$query->where(['>=', 'age', 18]);
$query->where(['between', 'age', 18, 65]);
$query->where(['like', 'name', 'John']);
$query->where(['in', 'id', [1, 2, 3]]);

// Logical
$query->where([
    'and',
    ['status' => 'active'],
    ['or', ['>', 'balance', 1000], ['role' => 'vip']],
]);

// Subquery in condition
$activeIds = (new Query($db))->select('id')->from('users')->where(['active' => 1]);
$query->where(['in', 'user_id', $activeIds]);

// EXISTS
$query->where(['exists', (new Query($db))->from('orders')->where('orders.user_id = users.id')]);

// JSON overlaps (MySQL)
$query->where(['json overlaps', 'tags', ['php', 'mysql']]);

// JSON contains — check if JSON column contains a value
$query->where(['json contains', 'options', 'en']);
$query->where(['json contains', 'options', ['role' => 'admin']]);
$query->where(['json contains', 'options', 'en', '$.languages']); // with path

// JSON length — compare length of JSON array/object
$query->where(['json length', 'tags', '>', 3]);
$query->where(['json length', 'data', '>=', 1, '$.items']); // with path

// Array overlaps (PostgreSQL)
$query->where(['array overlaps', 'tags', ['php', 'mysql']]);

// filterWhere — automatically ignores null/empty values
$query->filterWhere([
    'status' => $request->get('status'),   // skipped if null/empty
    'name'   => $request->get('name'),     // skipped if null/empty
]);
```

Full list of condition operators: `=`, `!=`, `<>`, `>`, `>=`, `<`, `<=`, `BETWEEN`, `NOT BETWEEN`, `IN`, `NOT IN`, `LIKE`, `NOT LIKE`, `OR LIKE`, `OR NOT LIKE`, `EXISTS`, `NOT EXISTS`, `ARRAY OVERLAPS`, `JSON OVERLAPS`, `JSON CONTAINS`, `JSON LENGTH`, `AND`, `OR`, `NOT`.

### Raw Integer IN (High Performance)

For large IN lists with integer IDs, `whereIntegerInRaw()` skips PDO parameter binding entirely. A normal `['in', 'id', $ids]` with 10,000 IDs creates 10,000 `:qp0, :qp1, ...` placeholders and 10,000 `PDOStatement::bindValue()` calls. The raw version inlines the integers directly, sanitized with `intval()`:

```php
// Normal IN: SELECT * FROM users WHERE id IN (:qp0, :qp1, ... :qp9999)  — 10k bindings
// Raw IN:    SELECT * FROM users WHERE id IN (1, 2, 3, ... 10000)        — zero bindings

$userIds = [1, 2, 3, /* ... thousands of IDs */];

$query->from('users')
    ->whereIntegerInRaw('id', $userIds)
    ->all();

// NOT IN variant
$query->from('users')
    ->whereIntegerNotInRaw('id', $excludedIds)
    ->all();
```

**Safety**: all values are cast through `intval()`, so non-integer values become `0`. Empty arrays produce `WHERE 0=1` (IN) or are a no-op (NOT IN).

**When to use**: large lists of IDs from subqueries, caches, or external systems where binding overhead is measurable. For small lists (under ~100 values), normal `['in', 'id', $values]` is fine.

### Conditional Clauses (`when`)

Apply query clauses only when a condition is truthy. Eliminates verbose if/else blocks:

```php
$query->from('users')
    ->when($request->status, fn($q, $status) => $q->andWhere(['status' => $status]))
    ->when($request->sortBy, fn($q, $sort) => $q->orderBy($sort), fn($q) => $q->orderBy('id'))
    ->when($request->limit, fn($q, $limit) => $q->limit($limit));
```

The second closure (optional) is the default, called when the condition is falsy.

### Incremental WHERE Building

`where()` sets the initial condition (throws `LogicException` if called twice). Use `andWhere()` / `orWhere()` to add conditions incrementally:

```php
$query->from('users')
    ->where(['status' => 'active'])
    ->andWhere(['>', 'age', 18])
    ->orWhere(['role' => 'admin']);

// Overwrite WHERE without exception
$query->setWhere(['status' => 'banned']);
```

### Smart Filter Comparison

`andFilterCompare()` detects the operator from the value string:

```php
// Useful for user-submitted filter forms
$query->from('products')
    ->where(['category' => 'electronics'])
    ->andFilterCompare('price', '>=100')   // price >= 100
    ->andFilterCompare('name', 'phone')    // name = 'phone'
    ->andFilterCompare('stock', '<>0');     // stock <> 0
```

Recognized prefixes: `>=`, `<=`, `<>`, `>`, `<`, `=`. Default operator is `=`.

### SELECT Options

```php
// SELECT DISTINCT
$query->from('users')->distinct()->select('country');

// Add columns to existing SELECT
$query->select('id')->addSelect(['name', 'email']);

// MySQL-specific: SQL_CALC_FOUND_ROWS
$query->from('users')->select('*', 'SQL_CALC_FOUND_ROWS');
// or
$query->selectOption('SQL_CALC_FOUND_ROWS');
```

### JOINs

```php
$query->from('orders o')
    ->innerJoin('users u', 'u.id = o.user_id')
    ->leftJoin('products p', 'p.id = o.product_id')
    ->rightJoin('categories c', 'c.id = p.category_id')
    ->where(['o.status' => 'confirmed']);

// Join with array condition (auto-quoted column names)
$query->innerJoin('users u', ['u.id' => 'o.user_id']);
```

### GROUP BY and HAVING

```php
$query->from('orders')
    ->select(['user_id', 'total' => 'SUM(amount)'])
    ->groupBy('user_id')
    ->addGroupBy('status')             // add to existing GROUP BY
    ->having(['>', 'SUM(amount)', 1000])
    ->andHaving(['status' => 'completed'])
    ->orHaving(['>', 'COUNT(*)', 5]);

// Overwrite HAVING
$query->setHaving(['>', 'SUM(amount)', 500]);

// Filter HAVING (ignores null/empty values)
$query->filterHaving(['status' => $userInput]);
$query->andFilterHaving(['category' => $category]);
$query->orFilterHaving(['region' => $region]);
```

### ORDER BY

```php
$query->orderBy(['created_at' => SORT_DESC]);
$query->addOrderBy(['name' => SORT_ASC]);  // add to existing ORDER BY
$query->orderBy('created_at DESC, name ASC'); // string format
```

### Aggregates

```php
(new Query($db))->from('users')->count('*');
(new Query($db))->from('orders')->sum('total');
(new Query($db))->from('users')->average('age');
(new Query($db))->from('users')->min('age');
(new Query($db))->from('users')->max('age');
```

### Result Methods

```php
$query = (new Query($db))->from('users')->where(['status' => 'active']);

$rows = $query->all();           // All rows as array of arrays
$row = $query->one();            // First row or null
$exists = $query->exists();      // true if any rows match
$ids = $query->column();         // First column of all rows as flat array
$value = $query->scalar();       // Single value (first column, first row)
```

### Index Results By Column

```php
// Index results by the 'id' column
$users = (new Query($db))->from('users')->indexBy('id')->all();
// Result: [1 => ['id' => 1, 'name' => 'Alice'], 2 => ['id' => 2, 'name' => 'Bob']]

// Index by a closure
$users = (new Query($db))->from('users')
    ->indexBy(fn(array $row) => $row['email'])
    ->all();
```

### Result Callback

Transform result rows before they are returned:

```php
$users = (new Query($db))
    ->from('users')
    ->resultCallback(function (array $rows): array {
        foreach ($rows as &$row) {
            $row['name'] = strtoupper($row['name']);
        }
        return $rows;
    })
    ->all();
```

### FOR UPDATE / FOR SHARE

Lock rows for update within a transaction:

```php
$query->from('accounts')
    ->where(['id' => 1])
    ->for('UPDATE');

// Multiple FOR clauses
$query->for('UPDATE')->addFor('NOWAIT');

// Overwrite FOR clause
$query->setFor('SHARE');
```

### Emulate Execution

Skip actual DB execution (useful for conditional query building):

```php
$query->from('users')
    ->emulateExecution(true);

$query->all();    // returns []
$query->one();    // returns null
$query->exists(); // returns false
$query->count();  // returns 0

$query->shouldEmulateExecution(); // true
```

### Subqueries

Any `Query` object is embeddable as a subquery anywhere:

```php
// In FROM
$sub = (new Query($db))->select('user_id, SUM(total) as total')->from('orders')->groupBy('user_id');
$query->from(['totals' => $sub])->where(['>', 'totals.total', 1000]);

// In SELECT
$query->select(['name', 'order_count' => (new Query($db))->select('COUNT(*)')->from('orders')->where('orders.user_id = users.id')]);
```

### Common Table Expressions (CTE)

```php
$cte = (new Query($db))
    ->select(['id', 'parent_id', 'name'])
    ->from('categories')
    ->where(['parent_id' => null]);

$query->withQuery($cte, 'tree', recursive: true)
    ->from('tree');

// Add more CTEs
$query->addWithQuery($anotherCte, 'summary');
```

### UNION

```php
$active = (new Query($db))->from('users')->where(['status' => 'active']);
$vip = (new Query($db))->from('users')->where(['role' => 'vip']);

$active->union($vip)->all();
$active->union($vip, all: true)->all(); // UNION ALL
```

### Batch Processing

```php
foreach ((new Query($db))->from('users')->batch(1000) as $batch) {
    // $batch is an array of up to 1000 rows
}

foreach ((new Query($db))->from('users')->each() as $row) {
    // $row is a single row, fetched in batches internally
}
```

### Chunk By ID (Safe Iteration)

Unlike `batch()` which uses OFFSET/LIMIT, `chunkById()` uses cursor-based pagination (`WHERE id > last_id`). This is safe when modifying records during iteration and faster on large tables:

```php
(new Query($db))->from('users')->where(['active' => false])
    ->chunkById(100, function (array $rows) use ($db) {
        foreach ($rows as $row) {
            $db->createCommand()->update('users', ['active' => true], ['id' => $row['id']])->execute();
        }
    });

// Custom primary key column
(new Query($db))->from('orders')->chunkById(500, $callback, 'order_id');

// Stop early by returning false
(new Query($db))->from('users')->chunkById(100, function (array $rows) {
    // process...
    return false; // stops after this chunk
});
```

### Upsert

```php
// MySQL: INSERT ... ON DUPLICATE KEY UPDATE
// PostgreSQL: INSERT ... ON CONFLICT DO UPDATE
$db->createCommand()->upsert('users', [
    'email' => 'john@example.com',
    'name' => 'John',
    'login_count' => new Expression('login_count + 1'),
])->execute();
```

### Increment / Decrement

Atomic counter operations without writing Expression objects manually:

```php
// Increment a single column
$db->createCommand()->increment('users', 'login_count', 1, ['id' => 1])->execute();

// Increment with extra columns to update
$db->createCommand()->increment('users', 'balance', 50.00, ['id' => 1], ['last_deposit' => '2024-01-15'])->execute();

// Decrement
$db->createCommand()->decrement('products', 'stock', 1, ['id' => 5])->execute();

// Increment multiple columns at once
$db->createCommand()->incrementEach('users', ['votes' => 5, 'balance' => 100], ['id' => 1])->execute();
```

### Batch Insert

```php
$db->createCommand()->insertBatch('users', [
    ['name' => 'Alice', 'email' => 'alice@example.com'],
    ['name' => 'Bob', 'email' => 'bob@example.com'],
])->execute();
```

### Parameter Binding

```php
use JustQuery\Expression\Value\Param;
use JustQuery\Constant\DataType;

$query->from('users')
    ->where('status = :status')
    ->params([':status' => 'active'])
    ->addParams([':role' => 'admin']);

// On commands with explicit type
$command = $db->createCommand('SELECT * FROM users WHERE id = :id');
$command->bindValue(':id', 42, DataType::INTEGER);
$command->bindValues([':name' => 'John', ':profile' => new Param($blob, DataType::LOB)]);
```

### Raw SQL and Direct Command Queries

```php
// Raw SQL
$row = $db->createCommand('SELECT * FROM users WHERE id = :id', [':id' => 1])->queryOne();

// Direct command query methods
$command = $db->createCommand('SELECT * FROM users');
$rows = $command->queryAll();       // All rows
$row = $command->queryOne();        // First row
$column = $command->queryColumn();  // First column as flat array
$value = $command->queryScalar();   // Single value

// SQL access
$command->getSql();     // The SQL with placeholders
$command->getRawSql();  // SQL with values inserted (for logging)
$command->setSql($sql); // Set new SQL (with quoting)
$command->setRawSql($sql); // Set SQL without modification

// Execute non-query (INSERT, UPDATE, DELETE)
$affectedRows = $command->execute();
```

## Transactions

```php
// Automatic transaction with closure
$result = $db->transaction(function (ConnectionInterface $db) {
    $db->createCommand()->insert('orders', ['user_id' => 1, 'total' => 99.99])->execute();
    $db->createCommand()->update('users', ['order_count' => new Expression('order_count + 1')], ['id' => 1])->execute();
    return $db->getLastInsertId();
});

// Manual transaction
$transaction = $db->beginTransaction();
try {
    $db->createCommand()->insert('orders', ['total' => 50])->execute();
    $transaction->commit();
} catch (\Throwable $e) {
    $transaction->rollBack();
    throw $e;
}

// Check active transaction
$tx = $db->getTransaction(); // null if none active

// Savepoints
$db->setEnableSavepoint(true);
$db->isSavepointEnabled(); // true
```

## Retry Handler

Handle transient database errors (deadlocks, connection drops) with automatic retry:

```php
$command = $db->createCommand('INSERT INTO orders ...');
$command->setRetryHandler(function (\JustQuery\Exception\Exception $e, int $attempt): bool {
    // $attempt starts at 1
    if ($attempt > 3) {
        return false; // give up, throw the exception
    }
    // Retry on deadlock (MySQL error 1213)
    return str_contains($e->getMessage(), 'Deadlock');
});
$command->execute();
```

## Index Hints (MySQL)

Control which indexes MySQL uses for query execution. Essential for large tables where the optimizer makes suboptimal choices.

```php
// Force the optimizer to use a specific index
$query->from('users')
    ->forceIndex('users', 'idx_email')
    ->where(['email' => $email]);

// Suggest indexes (optimizer may still ignore)
$query->from('users')
    ->useIndex('users', ['idx_email', 'idx_status']);

// Prevent the optimizer from using an index
$query->from('users')
    ->ignoreIndex('users', 'idx_created_at');

// Multiple hints on the same table
$query->from('users')
    ->forceIndex('users', 'idx_email')
    ->ignoreIndex('users', 'idx_old_status');

// Hints on JOIN tables
$query->from('users')
    ->innerJoin('orders', 'users.id = orders.user_id')
    ->forceIndex('orders', 'idx_user_id');

// Works with aliases
$query->from(['u' => 'users'])
    ->forceIndex('users', 'idx_email')
    ->where(['u.email' => $email]);
```

Generated SQL:

```sql
SELECT * FROM `users` FORCE INDEX (`idx_email`) WHERE `email` = 'john@example.com'
SELECT * FROM `users` `u` FORCE INDEX (`idx_email`) WHERE `u`.`email` = 'john@example.com'
SELECT * FROM `users` INNER JOIN `orders` FORCE INDEX (`idx_user_id`) ON `users`.`id` = `orders`.`user_id`
```

## Expression System

Build complex SQL safely using the Expression/Builder pattern. Every expression class has a corresponding Builder that generates the SQL.

### Raw Expression

```php
use JustQuery\Expression\Expression;

$query->select([new Expression('COUNT(DISTINCT user_id) as unique_users')]);
$query->where(new Expression('DATE(created_at) = CURDATE()'));
```

### CASE Expression

```php
use JustQuery\Expression\Statement\CaseX;
use JustQuery\Expression\Statement\WhenThen;

$case = new CaseX(
    new WhenThen(['status' => 'active'], 'Active'),
    new WhenThen(['status' => 'banned'], 'Banned'),
    else: 'Unknown',
);
$query->select(['name', 'label' => $case]);
```

### Function Expressions

```php
use JustQuery\Expression\Function\Greatest;
use JustQuery\Expression\Function\Least;
use JustQuery\Expression\Function\Length;
use JustQuery\Expression\Function\Longest;
use JustQuery\Expression\Function\Shortest;

// GREATEST / LEAST — returns max/min of multiple columns
$query->select([new Greatest('col1', 'col2', 'col3')]);
$query->select([new Least('price', 'sale_price')]);

// LENGTH — string length of a column
$query->select(['name', 'name_len' => new Length('name')]);

// LONGEST / SHORTEST — returns the longest/shortest string among columns
$query->select([new Longest('first_name', 'last_name')]);
$query->select([new Shortest('city', 'state')]);
```

### Value Expressions

```php
use JustQuery\Expression\Value\JsonValue;
use JustQuery\Expression\Value\Value;
use JustQuery\Expression\Value\Param;
use JustQuery\Expression\Value\ColumnName;
use JustQuery\Expression\Value\DateTimeValue;
use JustQuery\Constant\DataType;

// JSON — encodes PHP array as JSON for the database
$query->where(['config' => new JsonValue(['role' => 'admin'])]);

// Value — wrap a raw PHP value for type-safe binding
$query->where(['=', 'score', new Value(42)]);

// Param — bind with explicit PDO data type
$query->where(['=', 'avatar', new Param($binaryData, DataType::LOB)]);

// ColumnName — reference a column name as an expression
$query->select([new ColumnName('users.name')]);

// DateTimeValue — bind DateTime objects
$query->where(['>=', 'created_at', new DateTimeValue(new \DateTimeImmutable('2024-01-01'))]);
```

### Composite Expressions

```php
use JustQuery\Expression\CompositeExpression;

// Group multiple expressions into one
$composite = new CompositeExpression('AND', [
    new Expression('age > 18'),
    new Expression('status = 1'),
]);
```

## Schema Provider

Configure how the query builder understands your database schema:

```php
use JustQuery\Schema\Provider\{SchemaProvider, SchemaMode};

// DISABLED — pure query builder, no type casting
$provider = new SchemaProvider(SchemaMode::DISABLED);

// JSON — read from JSON files, zero DB overhead, deploy-safe
$provider = new SchemaProvider(SchemaMode::JSON, jsonPath: '/path/to/schema/');

// CACHE — read from DB, cache in Redis/APCu (traditional approach)
$provider = new SchemaProvider(SchemaMode::CACHE, dbSchema: $schema);

// JSON_CACHE — JSON first, DB+cache fallback for unknown tables
$provider = new SchemaProvider(SchemaMode::JSON_CACHE, dbSchema: $schema, jsonPath: '/path/to/schema/');
```

### Why JSON Schema?

In rolling deployments:

1. Database migration runs (adds column `score FLOAT GENERATED ALWAYS AS (...)`)
2. Old code is still running — it doesn't know about `score`
3. New code deploys — it reads `score` from JSON schema, knows it's computed, skips it in writes

With DB introspection (upstream approach), step 2 can fail if the cache is stale or cold. With JSON schema, the schema definition travels with the code.

### JSON Schema Format

```json
{
  "users": {
    "id":        {"type": "integer", "primaryKey": true, "autoIncrement": true},
    "name":      {"type": "string", "size": 255, "notNull": true},
    "is_active": {"type": "boolean"},
    "balance":   {"type": "float", "scale": 2},
    "options":   {"type": "json"},
    "score":     {"type": "float", "computed": true}
  }
}
```

The `type` defines the **PHP type you want**, not the MySQL column type. A `TEXT` column storing `"42"` with `"type": "integer"` returns `42` in PHP.

Supported types: `string`, `integer`, `float`, `boolean`, `json`.

### Automatic Type Casting

PDO returns everything as strings. JustQuery casts automatically based on schema:

```php
$row = $query->from('users')->where(['id' => 1])->withTypecasting()->one();

$row['id'];        // int(1)         — not string("1")
$row['is_active']; // bool(true)     — not string("1")
$row['balance'];   // float(1500.5)  — not string("1500.50")
$row['options'];   // array(...)     — not string('{"role":"admin"}')
```

### Granular Type Casting Control

```php
// Enable type casting only when reading from DB
$command = $db->createCommand('SELECT * FROM users');
$command = $command->withPhpTypecasting();  // cast DB → PHP on reads

// Enable type casting only when writing to DB
$command = $command->withDbTypecasting();   // cast PHP → DB on inserts/updates

// Enable both at once
$command = $command->withTypecasting();

// On Query objects
$query = (new Query($db))->from('users')->withTypecasting();
```

### Computed Column Protection

Columns marked as `computed` or `autoIncrement` are automatically excluded from INSERT and UPDATE:

```php
// Schema: score is GENERATED ALWAYS AS (reviews_sum / reviews_count)
$db->createCommand()->insert('products', [
    'name' => 'Widget',
    'price' => 9.99,
    'score' => 4.5,  // silently excluded — won't cause a MySQL error
])->execute();
```

## Query Profiler

Zero-overhead when disabled. Nanosecond-precision timing when active.

```php
use JustQuery\Profiler\QueryProfiler;

$profiler = new QueryProfiler();
$connection->setProfiler($profiler);

// ... run queries ...

$profiler->getCount();          // 47
$profiler->getTotalTime();      // 123.45 (ms)
$profiler->getSlowest(5);       // top 5 slowest queries
$profiler->getErrors();         // queries that threw exceptions
$profiler->getQueries();        // all queries: sql, time, params, error
$profiler->reset();             // clear collected data
```

Each query record contains:

| Field    | Description                              |
|----------|------------------------------------------|
| `sql`    | The executed SQL statement               |
| `time`   | Execution time in milliseconds           |
| `params` | Bound parameter values                   |
| `error`  | Exception message if the query failed    |

When no profiler is set, there is zero overhead — all profiler calls are nullsafe no-ops.

## PostgreSQL Support

Full PostgreSQL support with native types:

### RETURNING Clause

```php
// INSERT and return primary key values
$pks = $db->createCommand()->insertReturningPks('users', ['name' => 'John', 'email' => 'john@example.com']);
// ['id' => 42]

// UPSERT and return specific columns
$row = $db->createCommand()->upsertReturning('users', $columns, true, ['id', 'name', 'email']);
// ['id' => 42, 'name' => 'John', 'email' => 'john@example.com']

// UPSERT and return just primary keys
$pks = $db->createCommand()->upsertReturningPks('users', $columns, true);
// ['id' => 42]
```

### Array Types

```php
use JustQuery\Expression\Value\ArrayValue;

// ARRAY[1, 2, 3]::integer[]
$query->where(['=', 'ids', new ArrayValue([1, 2, 3], 'integer')]);
```

### Structured/Composite Types

```php
use JustQuery\Expression\Value\StructuredValue;

// ROW(10, 'USD')::money_type
$query->where(['=', 'price', new StructuredValue(['amount' => 10, 'currency' => 'USD'], 'money_type')]);
```

### Range Types

Full support for all PostgreSQL range types:

```php
use JustQuery\Driver\Pgsql\Expression\Int4RangeValue;
use JustQuery\Driver\Pgsql\Expression\DateRangeValue;

// int4range '[1, 10)'
new Int4RangeValue(1, 10);

// daterange '[2024-01-01, 2024-12-31]'
new DateRangeValue(new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-12-31'));
```

Supported: `int4range`, `int8range`, `numrange`, `daterange`, `tsrange`, `tstzrange`, and all corresponding multirange types.

### Array Merge

```php
use JustQuery\Expression\Function\ArrayMerge;

// Merge PostgreSQL arrays
$query->select([new ArrayMerge('tags1', 'tags2')]);
```

### Index Methods

```php
// CREATE INDEX ... USING GIN
$db->createCommand()->createIndex('users', 'idx_tags', 'tags', indexMethod: 'gin')->execute();
```

## DDL Operations

Full DDL support for both MySQL and PostgreSQL. All DDL methods are available on both the `QueryBuilder` and `Command` interfaces:

```php
// Via Command (executes immediately)
$cmd = $db->createCommand();

// Via QueryBuilder (returns SQL string)
$qb = $db->getQueryBuilder();
```

### Tables

```php
$cmd->createTable('users', [
    'id' => ColumnBuilder::primaryKey(),
    'name' => ColumnBuilder::string(255)->notNull(),
    'email' => ColumnBuilder::string(255)->unique(),
    'balance' => ColumnBuilder::decimal(10, 2)->defaultValue(0),
])->execute();

$cmd->dropTable('users')->execute();
$cmd->dropTable('users', ifExists: true, cascade: true)->execute();
$cmd->renameTable('users', 'accounts')->execute();
$cmd->truncateTable('users')->execute();
```

### Columns

```php
$cmd->addColumn('users', 'age', ColumnBuilder::integer())->execute();
$cmd->alterColumn('users', 'name', ColumnBuilder::string(500))->execute();
$cmd->dropColumn('users', 'age')->execute();
$cmd->renameColumn('users', 'name', 'full_name')->execute();
```

### Indexes

```php
$cmd->createIndex('users', 'idx_email', 'email', indexType: 'UNIQUE')->execute();
$cmd->createIndex('users', 'idx_tags', 'tags', indexMethod: 'gin')->execute(); // PostgreSQL GIN
$cmd->dropIndex('users', 'idx_email')->execute();
```

### Foreign Keys

```php
$cmd->addForeignKey('orders', 'fk_user', 'user_id', 'users', 'id', 'CASCADE', 'CASCADE')->execute();
$cmd->dropForeignKey('orders', 'fk_user')->execute();
```

### Constraints

```php
$cmd->addPrimaryKey('users', 'pk_users', 'id')->execute();
$cmd->dropPrimaryKey('users', 'pk_users')->execute();
$cmd->addUnique('users', 'uq_email', 'email')->execute();
$cmd->dropUnique('users', 'uq_email')->execute();
$cmd->addCheck('users', 'ck_age', 'age >= 0')->execute();
$cmd->dropCheck('users', 'ck_age')->execute();
$cmd->addDefaultValue('users', 'df_status', 'status', 'active')->execute();
$cmd->dropDefaultValue('users', 'df_status')->execute();
```

### Comments

```php
$cmd->addCommentOnTable('users', 'Main user accounts table')->execute();
$cmd->addCommentOnColumn('users', 'balance', 'Account balance in cents')->execute();
$cmd->dropCommentFromTable('users')->execute();
$cmd->dropCommentFromColumn('users', 'balance')->execute();
```

### Views

```php
$cmd->createView('active_users', (new Query($db))->from('users')->where(['status' => 'active']))->execute();
$cmd->dropView('active_users')->execute();
```

### Sequences and Integrity

```php
// Reset auto-increment sequence
$cmd->resetSequence('users', 100)->execute(); // Next ID will be 100
$cmd->resetSequence('users')->execute();       // Next ID = max(id) + 1

// Disable/enable foreign key checks (useful for migrations)
$cmd->checkIntegrity('', 'users', false)->execute(); // Disable
$cmd->checkIntegrity('', 'users', true)->execute();  // Enable

// List all databases
$databases = $cmd->showDatabases(); // ['myapp', 'information_schema', ...]
```

## Testing

```bash
# Start test databases
docker compose up -d

# Run tests
vendor/bin/phpunit

# Static analysis
vendor/bin/phpstan analyse
```

## Project Status

| Area | Status |
|------|--------|
| MySQL query builder | Production |
| PostgreSQL query builder | Production |
| JSON schema provider | Production |
| Query profiler | Production |
| Index hints (MySQL) | Production |
| PHPStan level max | 0 errors, no baseline |

## Roadmap

- **Query plan analysis** — `EXPLAIN` integration with automatic slow-query detection
- **Connection-level metrics** — connection pool stats, reconnection tracking
- **Read/write splitting** — automatic routing to read replicas
- **Query result caching** — PSR-16 cache layer with tag-based invalidation
- **MySQL 8.0+ optimizer hints** — `/*+ ... */` comment-style hints beyond index hints
- **Prepared statement caching** — reuse server-side prepared statements across queries

## Why a Fork?

JustQuery is a fork of [yiisoft/db](https://github.com/yiisoft/db) v2.0.1 and [yiisoft/db-mysql](https://github.com/yiisoft/db-mysql). We forked because we needed features that don't fit upstream's scope: MySQL index hints (`FORCE INDEX`, `USE INDEX`, `IGNORE INDEX`), JSON-based schema for rolling deployments, a built-in query profiler, computed column protection, and shared PDO connections. See the [full comparison](comparison.md) for details.

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).

We are deeply grateful to the **Yii framework team and community** for building and open-sourcing an exceptionally well-designed database abstraction layer. The query builder architecture, the Expression/Builder pattern, the condition system, and the overall code quality of yiisoft/db are outstanding work that made this project possible.

Special thanks to:

- **Qiang Xue** ([@qiangxue](https://github.com/qiangxue)) — creator of Yii Framework
- **Alexander Makarov** ([@samdark](https://github.com/samdark)) — long-time Yii core maintainer
- **Sergei Tigrov** ([@Tigrov](https://github.com/Tigrov)) and **Sergei Predvoditelev** ([@vjik](https://github.com/vjik)) — primary maintainers of yiisoft/db who wrote the vast majority of the v2.0 codebase

The Yii project has been a cornerstone of the PHP ecosystem since 2008. If you are looking for a full-featured PHP framework, check out [yiiframework.com](https://www.yiiframework.com/).

Original licenses preserved in [LICENSE.md](LICENSE.md) and [LICENSE-mysql.md](LICENSE-mysql.md).
