# FORCE INDEX / USE INDEX / IGNORE INDEX Implementation Tasks

## Overview
Implement MySQL index hints (`FORCE INDEX`, `USE INDEX`, `IGNORE INDEX`) support in FastPHPQueryBuilder. These hints go between the table name and alias in FROM and JOIN clauses.

**SQL syntax:**
```sql
SELECT * FROM users FORCE INDEX (idx_email) AS u WHERE email = 'test@example.com'
SELECT * FROM users u USE INDEX (idx_email, idx_name) JOIN orders IGNORE INDEX (idx_date) ON users.id = orders.user_id
```

## Tasks

### 1. Add `indexHints` property and methods to `Query.php`
- Add `protected array $indexHints = []` property
- Add `forceIndex(string $table, array|string $indexes): static`
- Add `useIndex(string $table, array|string $indexes): static`
- Add `ignoreIndex(string $table, array|string $indexes): static`
- Add `getIndexHints(): array` getter
- Storage format: `['tableName' => [['type' => 'FORCE INDEX', 'indexes' => ['idx1', 'idx2']]]]`

### 2. Add method signatures to `QueryInterface.php`
- Declare `forceIndex()`, `useIndex()`, `ignoreIndex()`, `getIndexHints()` in interface
- Add proper PHPDoc with examples

### 3. Override `buildFrom()` in MySQL `DQLQueryBuilder.php`
- Override `buildFrom()` to inject index hints after table names
- Hints go AFTER table name, BEFORE alias: `` `users` FORCE INDEX (`idx`) `u` ``
- Must handle: plain tables, aliased tables, multiple tables

### 4. Override `buildJoin()` in MySQL `DQLQueryBuilder.php`
- Override `buildJoin()` to inject index hints into JOIN clauses
- Format: `INNER JOIN `users` FORCE INDEX (`idx`) ON ...`

### 5. Pass index hints from Query to builder
- Modify `build()` in `AbstractDQLQueryBuilder.php` to pass index hints through, OR
- Access index hints directly from QueryInterface in MySQL DQLQueryBuilder

### 6. PHPStan compliance
- Ensure all new code passes PHPStan level max with 0 errors

## API Design
```php
// Force index on FROM table
$query->from('users')->forceIndex('users', 'idx_email');

// Use index with multiple indexes
$query->from('users')->useIndex('users', ['idx_email', 'idx_name']);

// Ignore index on JOIN table
$query->from('users')
    ->innerJoin('orders', 'users.id = orders.user_id')
    ->ignoreIndex('orders', 'idx_date');
```

## Files to modify
1. `src/Query/QueryInterface.php` — interface methods
2. `src/Query/Query.php` — storage + methods
3. `src/Driver/Mysql/DQLQueryBuilder.php` — SQL generation
4. `src/QueryBuilder/AbstractDQLQueryBuilder.php` — pass hints through build()
