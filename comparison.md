# Query Builder Comparison: Eloquent vs Doctrine DBAL vs JustQuery

## Why JustQuery?

JustQuery was born out of a real problem: running a production SaaS (CoverManager) on a legacy PHP codebase with millions of rows, rolling deployments, and zero margin for database errors.

In that environment, the query builder is not a convenience — it is a safety layer. Every query that hits the database must be predictable, observable, and safe. When a CodeIgniter 3 controller builds a complex query with conditional filters, joins across large tables, and bulk operations on live data, the cost of a wrong query is not a test failure — it is a production incident.

We evaluated the existing options:

- **Eloquent** requires Laravel. It is excellent inside that ecosystem, but coupling a legacy app to Laravel's container, service providers, and event system just to get a query builder is not practical. The ORM layer also adds measurable overhead (40-160% on reads, 2-3x memory) that matters at scale.

- **Doctrine DBAL** is framework-agnostic and lightweight, but it is a string builder. It has no batch insert, no conditional clause building, no increment/decrement, no index hints. Every non-trivial operation requires manual SQL. In a legacy codebase where dozens of developers write queries, "write the SQL yourself" leads to inconsistencies, injection risks, and unoptimized patterns.

- **yiisoft/db** has a well-designed query builder with the right abstractions — condition classes, expression builders, type-safe parameter binding. But it is built for Yii 3 and has gaps that matter in production: no `FORCE INDEX`, no JSON schema mode, no built-in profiler, no computed column protection.

JustQuery forks yiisoft/db and adds what production needs:

- **Safe bulk operations** — `insertBatch()` and `increment()` generate correct SQL in one call. No loops, no manual string building, no forgotten parameter bindings.
- **Conditional query building** — `when()` eliminates if/else blocks that are a common source of bugs in dynamic filters. The query is always a single, readable expression.
- **Schema without introspection** — JSON schema files ship with the code. No runtime DB queries for type information, no cache invalidation issues during rolling deploys, no "column not found" errors when code and schema are momentarily out of sync.
- **Computed column protection** — columns marked as `computed` or `autoIncrement` are automatically excluded from writes. No more "Column 'total_amount' is a generated column" errors in production.
- **Optimizer control** — `FORCE INDEX` / `USE INDEX` / `IGNORE INDEX` let you override MySQL's query planner when it makes wrong choices on large tables. This is the difference between a 50ms query and a 12-second table scan.
- **Observability** — every query profiled with nanosecond timing, parameters, and errors. In any PHP runtime, with zero overhead when disabled.
- **Zero framework coupling** — works in CodeIgniter, Symfony, standalone scripts, queue workers, or any PHP 8.3+ project. Only depends on PSR interfaces.

The goal is not to be the fastest query builder (read performance is dominated by MySQL execution time and is roughly equal across all three). The goal is to be the safest, most practical query builder for teams that operate complex PHP applications against real databases under real load.

---

## Test Environment

- **PHP:** 8.5.4
- **MySQL:** 8.0 (Docker, tmpfs)
- **Dataset:** 50,000 users, 150,000 orders, 300,000 order items
- **Iterations:** 50 per test + 1 warmup
- **Versions:** Eloquent 12.56, Doctrine DBAL 4.4.3, JustQuery dev-main

> Differences under 5% are reported as **tie**. Eloquent is tested both as full ORM (`User::where()`) and as base query builder (`DB::table()` / `toBase()`).

---

## 1. Performance

### Read Queries

| Query | Eloquent Model | Eloquent toBase | Doctrine DBAL | JustQuery | Verdict |
|-------|---------------|-----------------|---------------|---------|---------|
| SELECT by PK | 0.27ms | 0.26ms | **0.12ms** | 0.13ms | Doctrine/JustQuery tie, Eloquent 2.3x slower |
| SELECT 1000 rows | 14.26ms | 10.39ms | 11.45ms | **10.06ms** | JustQuery wins, Model 42% slower |
| SELECT 5000 rows | 11.00ms | 5.64ms | 4.36ms | 4.31ms | Tie Doctrine/JustQuery, Model 2.6x slower |
| COUNT 50k rows | 1.83ms | 1.81ms | 1.65ms | 1.65ms | Tie Doctrine/JustQuery |
| JOIN 2 tables | 3.57ms | 2.64ms | 2.92ms | **2.56ms** | JustQuery wins, Model 39% slower |
| 3-table JOIN+GROUP | 161.08ms | 145.12ms | **108.12ms** | 126.95ms | Doctrine wins (17% faster) |
| Subquery IN | 22.30ms | 21.53ms | 21.06ms | 20.27ms | Tie Doctrine/JustQuery |
| Complex WHERE (6 cond.) | 274.23ms | 265.46ms | 260.69ms | 253.65ms | Tie Doctrine/JustQuery |
| GROUP BY 150k rows | 59.04ms | 57.67ms | 55.98ms | 55.92ms | Tie Doctrine/JustQuery |

### Large IN Lists

| Query | Eloquent Model | Eloquent toBase | Doctrine DBAL | JustQuery | Verdict |
|-------|---------------|-----------------|---------------|---------|---------|
| IN 10k IDs (params) | 38.00ms | 28.14ms | 23.09ms | **20.22ms** | JustQuery wins (14% vs Doctrine) |
| IN 10k IDs (raw int) | 22.52ms | 23.71ms | 19.89ms | 19.91ms | Tie Doctrine/JustQuery |
| IN 30k IDs (raw int) | 48.72ms | 50.06ms | **42.88ms** | 48.41ms | Doctrine wins (13% faster) |

### Write Operations

| Query | Eloquent Model | Eloquent toBase | Doctrine DBAL | JustQuery | Verdict |
|-------|---------------|-----------------|---------------|---------|---------|
| INSERT single | 0.40ms | 0.36ms | 0.25ms | **0.18ms** | JustQuery wins (37% vs Doctrine) |
| Batch INSERT 500 | 4.06ms | 4.60ms | 86.76ms | **2.98ms** | JustQuery wins (29x vs Doctrine) |
| Batch INSERT 2000 | 15.75ms | 16.74ms | 340.83ms | **13.33ms** | JustQuery wins (26x vs Doctrine) |
| UPDATE single | 0.38ms | 0.35ms | 0.22ms | **0.18ms** | JustQuery wins (21% vs Doctrine) |

### Performance Summary

| | Wins | Ties | Losses |
|---|---|---|---|
| **JustQuery** | 7 | 7 | 2 |
| **Doctrine DBAL** | 2 | 7 | 7 |
| **Eloquent toBase** | 0 | 0 | 16 |
| **Eloquent Model** | 0 | 0 | 16 |

JustQuery and Doctrine tie on most read queries (the actual MySQL execution dominates). JustQuery wins decisively on writes and large parameterized IN lists. Doctrine wins on 3-table JOINs and 30k raw IN.

Eloquent never wins. The ORM model layer adds 40-160% overhead on reads.

---

## 2. Memory

### Read Queries

| Query | Eloquent Model | Eloquent toBase | Doctrine DBAL | JustQuery |
|-------|---------------|-----------------|---------------|---------|
| SELECT by PK | 48 KB | 47.1 KB | 19.5 KB | **18.8 KB** |
| SELECT 1000 rows | 1.58 MB | 1.09 MB | **1.04 MB** | 1.13 MB |
| SELECT 5000 rows | **7.96 MB** | 5.34 MB | **5.16 MB** | 5.62 MB |
| COUNT | 48.6 KB | 47.7 KB | **19.3 KB** | 19.6 KB |
| JOIN 2 tables | 1.12 MB | 575 KB | **506 KB** | 509 KB |
| 3-table JOIN+GROUP | 124 KB | 101 KB | **65.7 KB** | 69.1 KB |
| Subquery IN | 819 KB | 575 KB | **542 KB** | 590 KB |
| Complex WHERE | 825 KB | 597 KB | **547 KB** | 595 KB |
| GROUP BY 150k | 235 KB | 146 KB | **109 KB** | 116 KB |

### Large IN Lists

| Query | Eloquent Model | Eloquent toBase | Doctrine DBAL | JustQuery |
|-------|---------------|-----------------|---------------|---------|
| IN 10k (params) | 16.19 MB | 12.51 MB | 11.75 MB | **11.28 MB** |
| IN 10k (raw) | 11.26 MB | 11.26 MB | **10.41 MB** | 11.34 MB |
| IN 30k (raw) | 33.23 MB | 33.23 MB | **31.09 MB** | 33.87 MB |

### Write Operations

| Query | Eloquent Model | Eloquent toBase | Doctrine DBAL | JustQuery |
|-------|---------------|-----------------|---------------|---------|
| INSERT single | 30.9 KB | 27.5 KB | **2.7 KB** | 3.8 KB |
| Batch INSERT 500 | 576 KB | 575 KB | **2.3 KB** | 750 KB |
| Batch INSERT 2000 | 2.17 MB | 2.17 MB | **2.3 KB** | 2.95 MB |
| UPDATE single | 30 KB | 28.7 KB | 2.9 KB | **2.3 KB** |

### Memory Summary

Doctrine DBAL uses the least memory in most scenarios — it returns raw arrays from PDO with no wrapping. JustQuery is close behind. Eloquent Model is the heaviest: hydrating 5,000 Model objects costs **7.96 MB** vs 5.16 MB in Doctrine (54% more).

For batch inserts, Doctrine uses only 2.3 KB (it inserts row by row, no SQL buffering). JustQuery and Eloquent build the full INSERT statement in memory: 750 KB for 500 rows, 2.95 MB for 2,000 rows. The tradeoff: Doctrine uses 3,000x less memory but is 26x slower.

The `toBase()` pattern reduces Eloquent memory by 33% on reads by skipping Model object hydration.

---

## 3. Code Verbosity

### Simple SELECT with filter

```php
// Eloquent — 4 lines
User::where('status', 'active')
    ->where('age', '>=', 25)
    ->orderBy('balance', 'desc')
    ->limit(100)->get();

// Doctrine DBAL — 9 lines
$doctrine->createQueryBuilder()
    ->select('*')->from('users')
    ->where('status = :status')
    ->andWhere('age >= :age')
    ->setParameter('status', 'active')
    ->setParameter('age', 25)
    ->orderBy('balance', 'DESC')
    ->setMaxResults(100)
    ->executeQuery()->fetchAllAssociative();

// JustQuery — 5 lines
(new Query($db))->from('users')
    ->where(['status' => 'active'])
    ->andWhere(['>=', 'age', 25])
    ->orderBy(['balance' => SORT_DESC])
    ->limit(100)->all();
```

### Conditional query (API filter with 5 optional params)

```php
// Eloquent — 8 lines
$q = User::query();
if ($status) $q->where('status', $status);
if ($minAge) $q->where('age', '>=', $minAge);
if ($search) $q->where('name', 'like', "%$search%");
if ($sortBy) $q->orderBy($sortBy, 'desc');
else         $q->orderBy('id');
if ($limit)  $q->limit($limit);
$results = $q->get();

// Doctrine DBAL — 10 lines
$qb = $doctrine->createQueryBuilder()->select('*')->from('users');
if ($status) { $qb->andWhere('status = :s')->setParameter('s', $status); }
if ($minAge) { $qb->andWhere('age >= :a')->setParameter('a', $minAge); }
if ($search) { $qb->andWhere('name LIKE :n')->setParameter('n', "%$search%"); }
if ($sortBy) { $qb->orderBy($sortBy, 'DESC'); }
else         { $qb->orderBy('id', 'ASC'); }
if ($limit)  { $qb->setMaxResults($limit); }
$results = $qb->executeQuery()->fetchAllAssociative();

// JustQuery — 7 lines (no if/else needed)
$results = (new Query($db))->from('users')
    ->when($status, fn($q, $s) => $q->andWhere(['status' => $s]))
    ->when($minAge, fn($q, $a) => $q->andWhere(['>=', 'age', $a]))
    ->when($search, fn($q, $s) => $q->andWhere(['like', 'name', $s]))
    ->when($sortBy, fn($q, $s) => $q->orderBy([$s => SORT_DESC]),
                    fn($q) => $q->orderBy(['id' => SORT_ASC]))
    ->when($limit,  fn($q, $l) => $q->limit($l))
    ->all();
```

### Batch insert + increment

```php
// Eloquent — 2 lines
User::insert($rows);
User::where('id', 1)->increment('login_count');

// Doctrine DBAL — 4 lines
foreach ($rows as $row) { $doctrine->insert('users', $row); }
$doctrine->executeStatement(
    'UPDATE users SET login_count = login_count + 1 WHERE id = ?', [1]
);

// JustQuery — 2 lines
$db->createCommand()->insertBatch('users', $rows)->execute();
$db->createCommand()->increment('users', 'login_count', 1, ['id' => 1])->execute();
```

### Lines of code summary

| Scenario | Eloquent | Doctrine | JustQuery |
|----------|----------|----------|---------|
| Simple SELECT | 4 | 9 | 5 |
| JOIN + aggregate | 7 | 10 | 7 |
| Subquery IN | 3 | 6 | 5 |
| Conditional filter (5 params) | 8 | 10 | 7 |
| Batch insert + increment | 2 | 4 | 2 |
| **Total** | **24** | **39** | **26** |

Eloquent and JustQuery are equally concise. Doctrine requires 50-60% more code due to manual `setParameter()` calls and lack of `insertBatch` / `increment` / `when()`.

---

## 4. Features

| Feature | Eloquent | Doctrine DBAL | JustQuery |
|---------|----------|---------------|---------|
| Query builder | Yes | Yes | Yes |
| ORM / Model layer | Yes | No | No |
| Batch INSERT (single SQL) | Yes | No (row by row) | Yes |
| `when()` conditional clauses | Yes | No | Yes |
| `increment()` / `decrement()` | Yes (Model) | No (raw SQL) | Yes |
| `whereIntegerInRaw()` | Yes | No (manual SQL) | Yes |
| `chunkById()` (cursor pagination) | Yes | No | Yes |
| `insertBatch()` | Yes | No | Yes |
| JSON conditions (`JSON_CONTAINS`) | No (raw SQL) | No (raw SQL) | Yes (native) |
| `FORCE INDEX` / `USE INDEX` | No | No | Yes |
| JSON schema (zero-DB introspection) | No | No | Yes |
| Computed column protection | No | No | Yes |
| Built-in query profiler | No | No | Yes |
| Type casting from schema | Via `$casts` on Model | Via type mapping | Via JSON schema |
| PostgreSQL support | Yes | Yes | Yes |
| Subquery in WHERE | Yes (closure) | Manual SQL concat | Yes (Query object) |
| CTE (WITH) | No | No | Yes |
| UNION | Yes | Yes | Yes |
| Upsert | Yes | No | Yes |
| Framework coupling | Laravel | None | None |
| Min PHP version | 8.2 | 8.1 | 8.3 |

---

## 5. Conclusions

### When to use each

**Eloquent** — when you are inside Laravel and want the full ORM (relationships, events, observers, scopes). Accept the performance and memory overhead in exchange for developer experience within the Laravel ecosystem.

**Doctrine DBAL** — when you need a minimal, zero-opinion query builder with the lowest possible memory footprint. Good for read-heavy workloads where you don't need batch operations, conditional building, or index hints.

**JustQuery** — when you need production-grade query control without ORM overhead. Best for:
- High-throughput batch operations (26x faster than Doctrine on bulk inserts)
- Large ID-based queries (14% faster with parameterized IN, `whereIntegerInRaw` for extreme cases)
- Dynamic query building with `when()` (no if/else boilerplate)
- MySQL optimizer control (`FORCE INDEX`)
- Zero-introspection schema via JSON files
- Any PHP project regardless of framework

### The numbers

| Metric | Eloquent Model | Eloquent toBase | Doctrine DBAL | JustQuery |
|--------|---------------|-----------------|---------------|---------|
| Performance wins | 0 | 0 | 2 | 7 |
| Performance ties | 0 | 0 | 7 | 7 |
| Memory (lowest) | 0 / 16 | 0 / 16 | 12 / 16 | 3 / 16 |
| Code lines (total) | 24 | — | 39 | 26 |
| Features | 12 / 17 | — | 7 / 17 | 17 / 17 |

JustQuery matches Doctrine on reads, dominates on writes, ties Eloquent on code brevity, and offers the most features of the three — without requiring a framework.
