# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Running Tests

Tests require a PostgreSQL database. Set up the test database once:

```bash
psql -c "CREATE USER test WITH PASSWORD 'test';"
psql -c "CREATE DATABASE test OWNER test;"
psql -c "GRANT ALL PRIVILEGES ON DATABASE test TO test;"
```

Run the full test suite:

```bash
cd test
../vendor/bin/run_unit_tests
```

Run a single test file:

```bash
cd test
../vendor/bin/run_unit_tests tc_sql_table.php
```

Install dependencies:

```bash
composer install
```

## Architecture

SqlBuilder is a PHP library (namespace `SqlBuilder`) for programmatically building parameterized SQL queries against PostgreSQL. The main classes and their responsibilities:

**`SqlTable`** (`src/sql_table.php`) — The entry point. Represents a database table and accumulates where conditions, joins, and SQL options. Key methods: `where()`, `namedWhere()`, `bind()`, `join()`, `result()`, `copy()`.

**`SqlResult`** (`src/sql_result.php`) — Produced by `SqlTable::result()`. Flattens the table and its joins into a single executable query. Provides `select()`, `count()`, `exists()`, `distinctOnSelect()`. Supports GROUP BY, HAVING, ORDER BY, LIMIT, OFFSET.

**`BindedSql`** (`src/binded_sql.php`) — Immutable SQL string + bind parameters. Implements `ArrayAccess` for compatibility with `dbmole`. Forwards `select*` calls to an injected dbmole instance via `__call`. Produced by `SqlResult::select()` and related methods.

**`SqlWhere`** (`src/sql_where.php`) — Represents a WHERE clause, supporting `andWith()`, `orWith()`, `not()`. Named conditions added via `SqlTable::namedWhere()` can be later toggled or negated by name.

**`SqlJoinOrder`** (`src/sql_join_order.php`) — An ORDER BY clause that may carry an associated JOIN. Used when ordering by a joined column requires the join to be included. Supports reversal for pagination.

**`SqlValues`** (`src/sql_values.php`) — Builds SQL VALUES expressions for bulk inserts or temporary table creation with PostgreSQL type casting.

**`MaterializedSqlTable`** (`src/materialized_sql_table.php`) — Proxy around `SqlTable` that defers writing results to a PostgreSQL temporary table until `materialize()` is called. Optimizes repeated use of expensive subqueries.

**`FieldsUtils`** (`src/fields_utils.php`) — Static utility for parsing SQL field/order expressions, including handling of parentheses, quoted strings, and NULLS FIRST/LAST.

### Typical query flow

```
SqlTable → join(SqlTable) → result() → SqlResult → select() → BindedSql → dbmole execution
```

### Key design concepts

- **Named where conditions** (`namedWhere()`): conditions keyed by name so they can be selectively disabled or negated after the table is configured.
- **Autojoins**: when `autojoins` option is set, a joined table's conditions are only included in the final SQL when that join is explicitly activated or has named conditions — avoids unnecessary joins.
- **Query patterns** (`copy()`): clone a configured `SqlTable` to reuse base conditions across multiple queries.
- **Bind variable collection**: bind parameters are gathered automatically from all where clauses and joins and passed through to `BindedSql`.

## Test Conventions

Test classes extend `TcBase` and use `assertSqlEquals()` to compare SQL strings (normalizes whitespace). Test files follow the pattern `tc_<class_name>.php`.
