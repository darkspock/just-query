# FastPHP QueryBuilder

A fast, lightweight PHP Query Builder with configurable JSON schema support, automatic type casting, and computed column protection.

Built for production SaaS applications with rolling deployments where schema safety and zero overhead matter.

## Features

- **Fluent query builder** for SELECT, INSERT, UPDATE, DELETE with full MySQL support
- **Configurable schema provider** with 4 modes: disabled, JSON files, DB+cache, or JSON with DB fallback
- **JSON schema** for deploy-safe, zero-overhead type casting (no DB introspection, no Redis dependency)
- **Automatic type casting** — PDO returns strings; FastPHP casts to int, float, bool, array automatically
- **Computed column protection** — computed and read-only columns are automatically excluded from writes
- **Query profiler** — activable SQL logging with timing, params, and error tracking
- **Shared PDO connection** — reuse existing connections from other frameworks (CI3, etc.)
- **Subquery support** — any query is embeddable as a subquery anywhere
- **CTE support** — Common Table Expressions including recursive
- **`filterWhere()`** — automatically ignores empty values in search conditions
- **Expression system** — extensible Expression/Builder (visitor pattern) for custom SQL constructs

## Requirements

- PHP >= 8.1
- PDO + pdo_mysql extension

## Installation

```bash
composer require covermanager/fast-php-query-builder
```

## Quick Start

```php
use FastPHP\QueryBuilder\Query\Query;
use FastPHP\QueryBuilder\Driver\Mysql\Connection;

// SELECT
$users = (new Query($db))
    ->from('users')
    ->where(['status' => 'active'])
    ->orderBy(['created_at' => SORT_DESC])
    ->limit(10)
    ->all();

// WHERE with operators
$query->where(['>=', 'age', 18]);
$query->where(['like', 'name', 'John']);
$query->where(['in', 'id', [1, 2, 3]]);

// JOINs
$query->from('orders o')
    ->leftJoin('users u', 'u.id = o.user_id')
    ->where(['o.status' => 'confirmed']);

// INSERT
$db->createCommand()->insert('users', [
    'name' => 'John',
    'is_active' => true,    // cast to 1
    'config' => ['k' => 'v'], // cast to JSON string
])->execute();

// UPDATE
$db->createCommand()->update('users', ['name' => 'Jane'], ['id' => 1])->execute();

// DELETE
$db->createCommand()->delete('users', ['id' => 1])->execute();

// Aggregates
(new Query($db))->from('users')->count('*');
(new Query($db))->from('orders')->sum('total');

// Subqueries
$subQuery = (new Query($db))->select('id')->from('users')->where(['active' => 1]);
$query->where(['in', 'user_id', $subQuery]);

// filterWhere — ignores empty values automatically
$query->filterWhere([
    'status' => $request->get('status'),   // skipped if null/empty
    'name'   => $request->get('name'),     // skipped if null/empty
]);

// Raw SQL
$db->createCommand('SELECT * FROM users WHERE id = :id', [':id' => 1])->queryOne();
```

## Schema Provider

Configure how the query builder understands your database schema:

```php
use FastPHP\QueryBuilder\Schema\Provider\{SchemaProvider, SchemaMode};

// DISABLED — pure query builder, no type casting
$provider = new SchemaProvider(SchemaMode::DISABLED);

// JSON — read from JSON files, zero DB overhead
$provider = new SchemaProvider(SchemaMode::JSON, jsonPath: '/path/to/schema/');

// CACHE — read from DB, cache in Redis/APCu (original behavior)
$provider = new SchemaProvider(SchemaMode::CACHE, dbSchema: $schema);

// JSON_CACHE — JSON first, DB+cache fallback for tables not in JSON
$provider = new SchemaProvider(SchemaMode::JSON_CACHE, dbSchema: $schema, jsonPath: '/path/to/schema/');
```

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

Supported types: `string`, `integer`, `float`, `boolean`, `json`.

The `type` defines what **PHP type you want**, not what MySQL has. A `TEXT` column storing `"42"` with `"type": "integer"` returns `42` in PHP.

## Query Profiler

```php
use FastPHP\QueryBuilder\Profiler\QueryProfiler;

$profiler = new QueryProfiler();
$connection->setProfiler($profiler);

// ... run queries ...

$profiler->getCount();       // 23
$profiler->getTotalTime();   // 45.2 (ms)
$profiler->getSlowest(5);    // top 5 slowest queries
$profiler->getErrors();      // queries that threw exceptions
$profiler->getQueries();     // all queries with sql, time, params, error
$profiler->reset();          // clear collected data
```

When no profiler is set, there is zero overhead (nullsafe operator).

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).

## Attribution

This project is a fork of [yiisoft/db](https://github.com/yiisoft/db) v2.0.1 and [yiisoft/db-mysql](https://github.com/yiisoft/db-mysql), both created by [Yii Software](https://www.yiiframework.com/).

We are deeply grateful to the **Yii framework team and community** for building and open-sourcing an exceptionally well-designed database abstraction layer. The query builder architecture, the Expression/Builder pattern, the condition system, and the overall code quality of yiisoft/db are outstanding work that made this project possible.

Special thanks to:

- **Qiang Xue** ([@qiangxue](https://github.com/qiangxue)) — creator of Yii Framework
- **Alexander Makarov** ([@samdark](https://github.com/samdark)) — long-time Yii core maintainer
- **Sergei Tigrov** ([@Tigrov](https://github.com/Tigrov)) and **Sergei Predvoditelev** ([@vjik](https://github.com/vjik)) — primary maintainers of yiisoft/db who wrote the vast majority of the v2.0 codebase

The Yii project has been a cornerstone of the PHP ecosystem since 2008. If you are looking for a full-featured PHP framework, check out [yiiframework.com](https://www.yiiframework.com/).

Original licenses preserved in [LICENSE.md](LICENSE.md) and [LICENSE-mysql.md](LICENSE-mysql.md).
