SqlBuilder
==========

[![Tests](https://github.com/atk14/SqlBuilder/actions/workflows/tests.yml/badge.svg)](https://github.com/atk14/SqlBuilder/actions/workflows/tests.yml)

PHP library for building parameterized SQL queries programmatically. Designed for PostgreSQL and integrates with [dbmole](https://github.com/atk14/DbMole).

Usage
-----

### Basic query

```php
use SqlBuilder\SqlTable;

$sql = new SqlTable('cards');
$sql->where('visible = :visible', ':visible', true);
$sql->where('title LIKE :q')->bind(':q', '%Soap%');

$result = $sql->result();
// SELECT * FROM cards WHERE (visible = :visible) AND (title LIKE :q)

$dbmole->selectRows($result->select('id, title'), $result->bind);
$dbmole->selectInt($result->count());
```

`result()` returns a `SqlResult` object with the following query methods, each returning a `BindedSql`:

| Method | SQL produced |
|---|---|
| `select($fields = '*')` | `SELECT … FROM …` |
| `count($field = '*')` | `SELECT COUNT(…) FROM …` |
| `exists()` | `SELECT EXISTS(SELECT … FROM …)` |
| `distinctOnSelect($field, $distinct_on)` | `SELECT DISTINCT ON (…) …` |

### Named where conditions

Named conditions can be toggled or negated when building the result:

```php
$sql = new SqlTable('cards');
$sql->where('deleted = false');                          // anonymous — always included
$sql->namedWhere('visible', 'visible = :visible')->bind(':visible', true);

$sql->result()->select();
// SELECT * FROM cards WHERE (deleted = false) AND (visible = :visible)

$sql->result(['disable_where' => 'visible'])->select();
// SELECT * FROM cards WHERE (deleted = false)

$sql->result(['not_where' => 'visible'])->select();
// SELECT * FROM cards WHERE (deleted = false) AND (NOT (visible = :visible))

$sql->result(['named_where_only' => true])->select();
// SELECT * FROM cards WHERE (visible = :visible)
```

Pass `null` to `namedWhere` to remove the condition entirely:

```php
$sql->namedWhere('visible', null);
```

### SQL options (ORDER BY, LIMIT, OFFSET, …)

```php
// Set on the table — applied to every result()
$sql->setSqlOptions(['order' => 'title', 'limit' => 20]);

// Or pass per-query
$result->select('id', ['order' => 'title ASC', 'limit' => 10, 'offset' => 20]);
$result->select('id', ['group' => 'brand_id', 'having' => 'COUNT(*) > 1']);
```

### Joins

```php
$cards = new SqlTable('cards');
$products = $cards->join('products');
$products->where('products.card_id = cards.id');
$products->where('products.active = true');

$cards->result()->select('cards.id, products.name');
// SELECT cards.id, products.name
//   FROM cards
//   JOIN products ON ((products.card_id = cards.id) AND (products.active = true))
```

Second argument to `join()` is a shorthand for adding a where condition on the joined table:

```php
$cards->join('products', 'products.card_id = cards.id');
```

Third argument sets join options, e.g. `LEFT JOIN`:

```php
$cards->join('price_items pi', 'products.id = pi.product_id', ['join' => 'LEFT JOIN']);
```

Alternatively, use `setJoinBy('exists')` to turn the join into a correlated EXISTS subquery:

```php
$join = $cards->join('products', 'cards.id = products.card_id');
$join->setJoinBy('exists');
// SELECT * FROM cards WHERE EXISTS(SELECT * FROM products WHERE (cards.id = products.card_id))
```

### Autojoins

With `autojoins` enabled a joined table is only included in the SQL when it has at least one named where condition or is explicitly activated:

```php
$sql = new SqlTable('cards', [], [], ['autojoins' => true]);
$prods = $sql->join('products')->where('cards.id = products.card_id');

$sql->result()->select();
// SELECT * FROM cards   ← products not included, no named condition

$prods->namedWhere('active', 'products.active = true');
$sql->result()->select();
// SELECT * FROM cards JOIN products ON (…) WHERE (products.active = true)

// Force-include a join by name
$sql->result(['active_join' => 'products'])->select();
```

Control activation explicitly with `setActive()`:

```php
$prods->setActive(true);   // always included
$prods->setActive(false);  // never included
$prods->setActive('auto'); // included only when it has named conditions (autojoins behaviour)
```

### Reusing a query as a pattern

`copy()` creates an independent clone; subsequent changes to either object do not affect the other:

```php
$base = new SqlTable('cards');
$base->where('deleted = false');

$visible = $base->copy();
$visible->where('visible = true');

// $base query is unchanged
```

### SqlJoinOrder — ordering by a joined column

When the `ORDER BY` field requires an extra join, wrap it in `SqlJoinOrder`:

```php
use SqlBuilder\SqlJoinOrder;

$order = new SqlJoinOrder(
    'rank',
    'JOIN (SELECT id, rank FROM ranking WHERE …) r ON r.id = cards.id'
);

$result->select('id', ['order' => $order]);
// SELECT id FROM cards JOIN (…) r ON r.id = cards.id … ORDER BY rank
```

`SqlJoinOrder` supports reversal for pagination:

```php
$reversed = $order->reversed(); // flips ASC↔DESC, NULLS FIRST↔LAST
```

### BindedSql

`select()` and related methods return a `BindedSql` object pairing the SQL string with its bind array. It can be used directly with dbmole in several ways:

```php
$query = $result->select('id');

// Destructure
[$sql, $bind] = $query;
$dbmole->selectIntoArray($sql, $bind);

// Delegate to dbmole via magic __call
$query->selectIntoArray($dbmole);

// Execute a write query
$query->doQuery($dbmole);

// Inline bind values (for logging/debugging)
echo $query->escaped($dbmole);
```

Concatenate two `BindedSql` instances:

```php
$a = new BindedSql('WHERE id = :id', [':id' => 1]);
$b = new BindedSql(' AND active = :active', [':active' => true]);
$c = $a->concat($b);
```

### SqlValues — bulk inserts and CTEs

```php
use SqlBuilder\SqlValues;

$values = new SqlValues(['id', 'name']);
$values->add([4, 'Jan']);
$values->add(6, 'Petr');

$values->sql();   // VALUES (4,:name_0),(6,:name_1)
$values->bind();  // [':name_0' => 'Jan', ':name_1' => 'Petr']

// Use as a CTE
$dbmole->selectRows(
    "WITH data(id,name) AS ({$values->sql()}) SELECT * FROM data",
    $values->bind()
);

// Or via withSql()
$values->withSql('data'); // data(id,name) AS (VALUES (4,:name_0),(6,:name_1))

// Create a temporary table
$dbmole->doQuery($values->createTemporaryTableSql('tmp_data'), $values->bind());

// PostgreSQL ARRAY literal with type casting
$values->sqlArray('integer'); // ARRAY[(4,:name_0),(6,:name_1)]::integer[]
```

Column types can be declared for casting:

```php
$values = new SqlValues(['id::integer', 'name']);
// or
$values = new SqlValues(['id', 'name'], ['types' => ['integer', 'varchar']]);
```

### MaterializedSqlTable — lazy temporary table

`MaterializedSqlTable` defers materialization of a query into a PostgreSQL temporary table until the result is first used, avoiding repeated execution of expensive subqueries:

```php
use SqlBuilder\MaterializedSqlTable;

$mt = new MaterializedSqlTable($sqlTable, $dbmole, ['fields' => 'id, title']);

// Temporary table is created on the first call to result()
$dbmole->selectInt($mt->result()->count());
$dbmole->selectRows($mt->result()->select('id, title'));
```

Or materialize directly via `SqlTable`:

```php
$mat = $sql->materialize($dbmole, 'id, title');
$dbmole->selectRows($mat->result()->select());
```

Installation
------------

    composer require atk14/sql-builder

Testing
-------

    composer update --dev

Prepare the PostgreSQL test database (once):

    psql -c "CREATE USER test WITH PASSWORD 'test';"
    psql -c "CREATE DATABASE test OWNER test;"

Run tests:

    cd test
    ../vendor/bin/run_unit_tests

Run a single test file:

    cd test
    ../vendor/bin/run_unit_tests tc_sql_table.php

License
-------

SqlBuilder is free software distributed [under the terms of the MIT license](http://www.opensource.org/licenses/mit-license)
