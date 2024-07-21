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
    public function rawQuery(string $query, array $binds = [], ?string $fetch = 'get');

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
    public function whereRaw($rawQuery, $binds = [], $whereType = 'AND');

    /**
     * Adds a where clause to the query.
     *
     * @param string|null $column The column name.
     * @param mixed $value The value to compare.
     * @param string $operator The comparison operator, default is '='.
     * @return $this
     */
    public function where($column = null, $value = null, $operator = '=');

    /**
     * Adds an OR where clause to the query.
     *
     * @param string|null $column The column name.
     * @param mixed $value The value to compare.
     * @param string $operator The comparison operator, default is '='.
     * @return $this
     */
    public function orWhere($column = null, $value = null, $operator = '=');

    /**
     * Adds a whereIn clause to the query.
     *
     * @param string $column The column name.
     * @param array $value The array of values to compare.
     * @return $this
     */
    public function whereIn($column, $value = []);

    /**
     * Adds an OR whereIn clause to the query.
     *
     * @param string $column The column name.
     * @param array $value The array of values to compare.
     * @return $this
     */
    public function orWhereIn($column, $value = []);

    /**
     * Adds a whereNotIn clause to the query.
     *
     * @param string $column The column name.
     * @param array $value The array of values to compare.
     * @return $this
     */
    public function whereNotIn($column, $value = []);

    /**
     * Adds an OR whereNotIn clause to the query.
     *
     * @param string $column The column name.
     * @param array $value The array of values to compare.
     * @return $this
     */
    public function orWhereNotIn($column, $value = []);

    /**
     * Adds a whereBetween clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $start The start value.
     * @param mixed $end The end value.
     * @return $this
     */
    public function whereBetween($column, $start, $end);

    /**
     * Adds an OR whereBetween clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $start The start value.
     * @param mixed $end The end value.
     * @return $this
     */
    public function orWhereBetween($column, $start, $end);

    /**
     * Adds a whereNotBetween clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $start The start value.
     * @param mixed $end The end value.
     * @return $this
     */
    public function whereNotBetween($column, $start, $end);

    /**
     * Adds an OR whereNotBetween clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $start The start value.
     * @param mixed $end The end value.
     * @return $this
     */
    public function orWhereNotBetween($column, $start, $end);

    /**
     * Adds a whereNull clause to the query.
     *
     * @param string $column The column name.
     * @return $this
     */
    public function whereNull($colum);

    /**
     * Adds an OR whereNull clause to the query.
     *
     * @param string $column The column name.
     * @return $this
     */
    public function orWhereNull($column);

    /**
     * Adds a whereNotNull clause to the query.
     *
     * @param string $column The column name.
     * @return $this
     */
    public function whereNotNull($column);

    /**
     * Adds an OR whereNotNull clause to the query.
     *
     * @param string $column The column name.
     * @return $this
     */
    public function orWhereNotNull($column);

    /**
     * Adds a whereDate clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $date The date to compare.
     * @param string $operator The comparison operator, default is '='.
     * @return $this
     */
    public function whereDate($column, $date, $operator = '=');

    /**
     * Adds a whereDate clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $date The date to compare.
     * @param string $operator The comparison operator, default is '='.
     * @return $this
     */
    public function orWhereDate($column, $date, $operator = '=');

    /**
     * Adds a whereDay clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $date The day to compare.
     * @param string $operator The comparison operator, default is '='.
     * @return $this
     */
    public function whereDay($column, $date, $operator = '=');

    /**
     * Adds a whereDay clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $date The day to compare.
     * @param string $operator The comparison operator, default is '='.
     * @return $this
     */
    public function orWhereDay($column, $date, $operator = '=');

    /**
     * Adds a whereYear clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $date The year to compare.
     * @param string $operator The comparison operator, default is '='.
     * @return $this
     */
    public function whereYear($column, $date, $operator = '=');

    /**
     * Adds a whereYear clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $date The year to compare.
     * @param string $operator The comparison operator, default is '='.
     * @return $this
     */
    public function orWhereYear($column, $date, $operator = '=');

    /**
     * Adds a join raw clause to the query.
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
    public function innerJoin($table, $foreignKey, $localKey, $conditions = null);

    /**
     * Adds an outer join clause to the query.
     *
     * @param string $table The table to join.
     * @param string $foreignKey The foreign key column.
     * @param string $localKey The local key column.
     * @param string|null $conditions Additional conditions for the join.
     * @return $this
     */
    public function outerJoin($table, $foreignKey, $localKey, $conditions = null);

    /**
     * Adds an order by clause to the query.
     *
     * @param string $column The column to order by.
     * @param string $direction The direction of the order ('ASC' or 'DESC').
     * @return $this
     */
    public function orderBy($column, $direction = 'ASC');

    /**
     * Adds a raw order by clause to the query.
     *
     * @param string $string The raw order by string.
     * @param array|null $bindParams Parameters to bind to the raw order by string.
     * @return $this
     */
    public function orderByRaw($string, $bindParams = null);

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
    public function having($column, $value, $operator = '=');

    /**
     * Adds a limit clause to the query.
     *
     * @param int $limit The number of rows to return.
     * @return $this
     */
    public function limit($limit);

    /**
     * Adds an offset clause to the query.
     *
     * @param int $offset The number of rows to skip.
     * @return $this
     */
    public function offset($offset);

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
    public function chunk($size, callable $callback);

    /**
     * Paginates the result set.
     *
     * @param int $currentPage The current page number.
     * @param int $limit The number of rows per page.
     * @param int $draw The draw counter for pagination.
     * @return mixed The paginated result set.
     */
    public function paginate($currentPage = 1, $limit = 10, $draw = 1);

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
    public function with($aliasKey, $table, $foreignKey, $localKey, \Closure $callback = null);

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
    public function withOne($aliasKey, $table, $foreignKey, $localKey, \Closure $callback = null);
}
