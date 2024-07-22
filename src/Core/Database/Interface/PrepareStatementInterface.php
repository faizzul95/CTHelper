<?php

declare(strict_types=1);

namespace Core\Database\Interface;

/**
 * Database PrepareStatement Interface
 *
 * This interface defines methods for building and executing SELECT queries
 * in a secure and flexible way. It utilizes prepared statements to prevent
 * SQL injection vulnerabilities.
 *
 * @category Database
 * @package Core\Database
 * @author 
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link 
 * @version 0.0.1
 */

interface PrepareStatementInterface
{
    /**
     * Reset the query statement
     *
     * @return void
     */
    public function resetStatement();

    /**
     * Executes a raw SQL query with optional parameter binding.
     *
     * Use this method with caution for statements that are not covered
     * by the other specialized methods.
     *
     * @param string $query The raw SQL query to execute.
     * @param array $binds An associative array of parameter names and their values.
     * @param string $fetch   Optional. The fetch mode for retrieving results. Default is 'get'.
     *                        Possible values: 'get' to fetch a single row, 'all' to fetch all rows.
     * @return mixed The result of the query execution, depending on the database driver.
     */
    public function rawQuery(string $query, array $binds = [], string $fetch = 'get');

    /**
     * Specifies the table to perform the query on.
     *
     * @param string $table The name of the table.
     * @return $this
     */
    public function table(string $table);

    /**
     * Specifies the columns to select in the query.
     *
     * @param string $columns The columns to select, default is '*'.
     * @return $this
     */
    public function select(string $columns = '*');

    /**
     * Adds a raw where clause to the query.
     *
     * @param string $rawQuery The raw where query string.
     * @param array $binds An associative array of parameter names and their values.
     * @param string $whereType The type of where clause ('AND' or 'OR').
     * @return $this
     */
    public function whereRaw(string $rawQuery, array $binds = [], string $whereType = 'AND');

    /**
     * Adds a where clause to the query.
     *
     * @param string|null $column The column name.
     * @param mixed $value The value to compare.
     * @param string $operator The comparison operator, default is '='.
     * @return $this
     */
    public function where(string $column = null, ?string $value = null, string $operator = '=');

    /**
     * Adds an OR where clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $value The value to compare.
     * @param string $operator The comparison operator, default is '='.
     * @return $this
     */
    public function orWhere(string $column = null, ?string $value = null, string $operator = '=');

    /**
     * Adds a whereIn clause to the query.
     *
     * @param string $column The column name.
     * @param array $value The array of values to compare.
     * @return $this
     */
    public function whereIn(string $column, array $value = []);

    /**
     * Adds an OR whereIn clause to the query.
     *
     * @param string $column The column name.
     * @param array $value The array of values to compare.
     * @return $this
     */
    public function orWhereIn(string $column, array $value = []);

    /**
     * Adds a whereNotIn clause to the query.
     *
     * @param string $column The column name.
     * @param array $value The array of values to compare.
     * @return $this
     */
    public function whereNotIn(string $column, $value = []);

    /**
     * Adds an OR whereNotIn clause to the query.
     *
     * @param string $column The column name.
     * @param array $value The array of values to compare.
     * @return $this
     */
    public function orWhereNotIn(string $column, array $value = []);

    /**
     * Adds a whereBetween clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $start The start value.
     * @param mixed $end The end value.
     * @return $this
     */
    public function whereBetween(string $column, string $start, string $end);

    /**
     * Adds an OR whereBetween clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $start The start value.
     * @param mixed $end The end value.
     * @return $this
     */
    public function orWhereBetween(string $column, string $start, string $end);

    /**
     * Adds a whereNotBetween clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $start The start value.
     * @param mixed $end The end value.
     * @return $this
     */
    public function whereNotBetween(string $column, string $start, string $end);

    /**
     * Adds an OR whereNotBetween clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $start The start value.
     * @param mixed $end The end value.
     * @return $this
     */
    public function orWhereNotBetween(string $column, string $start, string $end);

    /**
     * Adds a whereNull clause to the query.
     *
     * @param string $column The column name.
     * @return $this
     */
    public function whereNull(string $column);

    /**
     * Adds an OR whereNull clause to the query.
     *
     * @param string $column The column name.
     * @return $this
     */
    public function orWhereNull(string $column);

    /**
     * Adds a whereNotNull clause to the query.
     *
     * @param string $column The column name.
     * @return $this
     */
    public function whereNotNull(string $column);

    /**
     * Adds an OR whereNotNull clause to the query.
     *
     * @param string $column The column name.
     * @return $this
     */
    public function orWhereNotNull(string $column);

    /**
     * Adds a whereDate clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $date The date to compare.
     * @param string $operator The comparison operator, default is '='.
     * @return $this
     */
    public function whereDate(string $column, string $date, string $operator = '=');

    /**
     * Adds a OR whereDate clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $date The date to compare.
     * @param string $operator The comparison operator, default is '='.
     * @return $this
     */
    public function orWhereDate(string $column, string $date, string $operator = '=');

    /**
     * Adds a whereDay clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $date The day to compare.
     * @param string $operator The comparison operator, default is '='.
     * @return $this
     */
    public function whereDay(string $column, string $date, string $operator = '=');

    /**
     * Adds a OR whereDay clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $date The day to compare.
     * @param string $operator The comparison operator, default is '='.
     * @return $this
     */
    public function orWhereDay(string $column, string $date, string $operator = '=');

    /**
     * Adds a whereYear clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $date The year to compare.
     * @param string $operator The comparison operator, default is '='.
     * @return $this
     */
    public function whereYear(string $column, string $date, string $operator = '=');

    /**
     * Adds a OR whereYear clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $date The year to compare.
     * @param string $operator The comparison operator, default is '='.
     * @return $this
     */
    public function orWhereYear(string $column, string $date, string $operator = '=');

    /**
     * Adds a raw join clause to the query.
     *
     * @param string $table The table to join.
     * @param string|null $conditions Conditions include key & other extra condition for the join.
     * @return $this
     */
    public function joinRaw($table, $conditions = null);

    /**
     * Adds a left join clause to the query.
     *
     * @param string $table The table to join.
     * @param string $foreignKey The foreign key column.
     * @param string $localKey The local key column.
     * @param string|null $conditions Additional conditions for the join.
     * @return $this
     */
    public function leftJoin($table, $foreignKey, $localKey, $conditions = null);

    /**
     * Adds a right join clause to the query.
     *
     * @param string $table The table to join.
     * @param string $foreignKey The foreign key column.
     * @param string $localKey The local key column.
     * @param string|null $conditions Additional conditions for the join.
     * @return $this
     */
    public function rightJoin($table, $foreignKey, $localKey, $conditions = null);

    /**
     * Adds an inner join clause to the query.
     *
     * @param string $table The table to join.
     * @param string $foreignKey The foreign key column.
     * @param string $localKey The local key column.
     * @param string|null $conditions Additional conditions for the join.
     * @return $this
     */
    public function innerJoin(string $table, string $foreignKey, string $localKey, ?string $conditions = null);

    /**
     * Adds an outer join clause to the query.
     *
     * @param string $table The table to join.
     * @param string $foreignKey The foreign key column.
     * @param string $localKey The local key column.
     * @param string|null $conditions Additional conditions for the join.
     * @return $this
     */
    public function outerJoin(string $table, string $foreignKey, string $localKey, ?string $conditions = null);

    /**
     * Adds an order by clause to the query.
     *
     * @param string $column The column to order by.
     * @param string $direction The direction of the order ('ASC' or 'DESC').
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'ASC');

    /**
     * Adds a raw order by clause to the query.
     *
     * @param string $string The raw order by string.
     * @param array|null $bindParams Parameters to bind to the raw order by string.
     * @return $this
     */
    public function orderByRaw(string $string, string $bindParams = null);

    /**
     * Adds a group by clause to the query.
     *
     * @param string|array $columns The columns to group by.
     * @return $this
     */
    public function groupBy($columns);

    /**
     * Adds a having clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $value The value to compare.
     * @param string $operator The comparison operator, default is '='.
     * @return $this
     */
    public function having(string $column, ?string $value, string $operator = '=');

    /**
     * Adds a limit clause to the query.
     *
     * @param int $limit The number of rows to return.
     * @return $this
     */
    public function limit(int $limit);

    /**
     * Adds an offset clause to the query.
     *
     * @param int $offset The number of rows to skip.
     * @return $this
     */
    public function offset(int $offset);

    /**
     * Executes the query and returns the results.
     *
     * @return mixed The result of the query execution, depending on the database driver.
     */
    public function get();

    /**
     * Fetches a single row from the result set.
     *
     * @return mixed The first row of the result set.
     */
    public function fetch();

    /**
     * Fetches the first row from the result set.
     *
     * @return mixed The first row of the result set.
     */
    public function first();

    /**
     * Fetches the last row from the result set.
     *
     * @return mixed The last row of the result set.
     */
    public function last();

    /**
     * Counts the number of rows in the result set.
     *
     * @return int The number of rows in the result set.
     */
    public function count();

    /**
     * Processes the query in chunks and applies a callback function to each chunk.
     *
     * @param int $size The size of each chunk.
     * @param callable $callback The callback function to apply to each chunk.
     * @return mixed The result of processing the chunks.
     */
    public function chunk(int $size, callable $callback);

    /**
     * Paginates the result set.
     *
     * @param int $currentPage The current page number.
     * @param int $limit The number of rows per page.
     * @param int $draw The draw counter for pagination.
     * @return mixed The paginated result set.
     */
    public function paginate(int $currentPage = 1, int $limit = 10, int $draw = 1);

    /**
     * Specifies a relationship to load with the query.
     *
     * @param string $aliasKey The alias key for the relationship.
     * @param string $table The related table.
     * @param string $foreignKey The foreign key column.
     * @param string $localKey The local key column.
     * @param \Closure|null $callback A callback function to apply to the relationship.
     * @return $this
     */
    public function with(string $aliasKey, string $table, string $foreignKey, string $localKey, \Closure $callback = null);

    /**
     * Specifies a one-to-one relationship to load with the query.
     *
     * @param string $aliasKey The alias key for the relationship.
     * @param string $table The related table.
     * @param string $foreignKey The foreign key column.
     * @param string $localKey The local key column.
     * @param \Closure|null $callback A callback function to apply to the relationship.
     * @return $this
     */
    public function withOne(string $aliasKey, string $table, string $foreignKey, string $localKey, \Closure $callback = null);

    /**
     * Builds a WHERE clause fragment based on provided conditions.
     *
     * This function is used internally to construct WHERE clause parts based on
     * column name, operator, value(s), and WHERE type (AND or OR). It handles
     * different operators like `=`, `IN`, `NOT IN`, `BETWEEN`, and `NOT BETWEEN`.
     * It uses placeholders (`?`) for values and builds the appropriate clause structure.
     * This function also merges the provided values into the internal `_binds` array
     * for later binding to the prepared statement.
     *
     * @param string $columnName The name of the column to compare.
     * @param mixed $value The value or an array of values for the comparison.
     * @param string $operator (optional) The comparison operator (e.g., =, IN, BETWEEN). Defaults to =.
     * @param string $whereType (optional) The type of WHERE clause (AND or OR). Defaults to AND.
     * @throws \InvalidArgumentException If invalid operator or value format is provided.
     */
    public function _buildWhereClause(string $columnName, ?string $value = null, string $operator = '=', string $whereType = 'AND');

     /**
     * Builds the final SELECT query string based on the configured options.
     *
     * This function combines all the query components like selected fields, table,
     * joins, WHERE clause, GROUP BY, ORDER BY, and LIMIT into a single SQL query string.
     *
     * @return $this This object for method chaining.
     * @throws \InvalidArgumentException If an asterisk (*) is used in the select clause
     *                                   and no table is specified.
     */
    public function _buildPrepareSelectQuery();
}
