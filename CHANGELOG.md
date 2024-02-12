Change Log
==========

All notable changes to SqlBuilder will be documented in this file.

## [4.4.2] - 2024-02-12

* 237567d - Fixes for PHP8.2

## [4.4.1] - 2024-02-12

* ced291b - Fixes for PHP8.2

## [3.2.3] - 2021-05-27
- BindedSql->doQuery

All notable changes to SqlBuilder will be documented in this file.
## [3.2.2] - 2021-05-27
- BindedSql->AddBindFrom and fix of BindedSql->addBind

## [3.2.1] - 2021-05-26
- methods for modification and concatenating of BindedSqls
- SqlTable accepts where conditions as BindedSql class

## [3.1.0] - 2021-05-26
- SqlBindQuery renamed to BindedSql. BC BREAK!

## [3.0.0] - 2021-05-25

- Content of the package moved to namespace SqlBuilder. BC BREAK!
- new class Fieldstils

## [2.3.0] - 2021-05-25

- SqlResult fixies and improvments (tableString method, 'exists' join metod)

## [2.2.0] - 2021-05-25

- MaterializedSqlTable class added

## [2.1.0] - 2021-05-25

- SqlResult returns an instance of SqlBindQuery

## [2.0.1] - 2021-05-25

- The package atk14/dbmole is a requirement only for the development environment.

## [2.0] - 2021-05-24

- Class renamed: SqlConditions -> SqlTable. BC BREAK!
- Return value changed: SqlResult->distinctOnSelect. BC BREAK!

## [1.1] - 2021-05-14

- Class renamed: SQLJoinOrder -> SqlJoinOrder. BC BREAK!

## [1.0] - 2021-05-13

The project was extracted form the Atk14Eshop.
