# MySQL on SQLite — vendored copy

`src/` is the `packages/mysql-on-sqlite/src` folder of [WordPress/sqlite-database-integration](https://github.com/WordPress/sqlite-database-integration) (GPL-2.0-or-later), version 3.0.2, commit `4163f69f3d543ec7d6085bbb0c4e0c36b36c0156`. Joomla 3.x UTD's SQLite database driver (`libraries/joomla/database/driver/mysqlonsqlite.php`) runs MySQL SQL through it.

It is unmodified except for the patches below, all in `src/sqlite/class-wp-mysql-on-sqlite.php` and marked "Joomla 3.x UTD patch". Reapply them when updating the package, unless upstream has fixed the same cases.

1. **`querySpecOption` / `selectOption`:** drop MySQL-only SELECT hints (`STRAIGHT_JOIN`, `SQL_NO_CACHE`, `SQL_CACHE`, `HIGH_PRIORITY`, ...), keeping `ALL` and `DISTINCT`. SQLite failed with a syntax error on them.
2. **`innerJoinType`:** translate `a STRAIGHT_JOIN b` (an inner join with a fixed join order) to `JOIN`.
3. **`sumExpr` → `translate_group_concat()`:** translate `GROUP_CONCAT([DISTINCT] expr[, expr...] [ORDER BY ...] [SEPARATOR str])` to SQLite's `GROUP_CONCAT(expr, separator [ORDER BY ...])`. Several expressions are concatenated; ORDER BY is kept from SQLite 3.44.0 on; with DISTINCT, a custom separator replaces the default "," afterwards (SQLite's DISTINCT aggregates take one argument).
4. **`queryTerm`:** a parenthesized query as an operand of UNION/EXCEPT/INTERSECT (`... UNION (SELECT ...)`), which SQLite rejects, becomes `SELECT * FROM (...)`.
5. **INSERT ... SELECT column names:** the SELECT's own column names were used to refer to its values, but unaliased values give duplicate names (several `0` or `''` columns, renamed by SQLite to e.g. `0:1234567890`), so `INSERT INTO t (a, b, c) SELECT 0, 0, '' FROM DUAL` failed with "no such column". Positional `columnN` names are used instead, like the package already does for VALUES.

Known gaps, not patched (unused by Joomla core): `DIV`, `TIMESTAMPDIFF(unit, ...)`, `LENGTH()` counts characters instead of bytes, `DATE_SUB()` of a DATE returns a DATETIME, `ADD COLUMN ... AFTER` adds the column at the end, `LAST_INSERT_ID()` after a multi-row INSERT gives the last ID instead of the first.

Functions the package lacks or gets wrong (`IF()`, Unicode `LOWER()`/`UPPER()`, `FIND_IN_SET()`, `SUBSTRING_INDEX()`, `RIGHT()`, `LPAD()`/`RPAD()`, `UUID()`, `LAST_INSERT_ID()`) are not patched here; the driver registers its own versions (`libraries/joomla/database/mysqlonsqlite/functions.php`).
