<?php

namespace Core\Database;

/**
 * Database Class
 *
 * @category  Database Access
 * @package   Database
 * @author    Mohd Fahmy Izwan Zulkhafri <faizzul14@gmail.com>
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link      -
 * @version   0.0.1
 */

use PDO;
use PDOException;

use Core\Database\Interface\ConnectionInterface;
use Core\Database\Interface\BuilderCrudInterface;
use Core\Database\Interface\BuilderStatementInterface;

use Core\Database\Interface\QueryInterface;
use Core\Database\Interface\ResultInterface;
use Core\Database\DatabaseCache;

abstract class BaseDatabase implements ConnectionInterface, BuilderStatementInterface, QueryInterface, BuilderCrudInterface, ResultInterface
{
    /**
     * Static instance of self
     *
     * @var Database
     */
    protected static $_instance;

    /**
     * @var \PDO The PDO instance for database connection.
     */
    protected $pdo;

    /**
     * @var string $driver The database driver being used (e.g., 'mysql', 'oracle', etc.).
     */
    protected $driver = 'mysql';

    /**
     * @var array The database config
     */
    protected $config = [];

    /**
     * @var string the name of a default (main) pdo connection
     */
    public $connectionName = 'default';

    /**
     * @var string|null The database schema name.
     */
    protected $schema;

    /**
     * @var string|null The table name.
     */
    protected $table;

    /**
     * @var string The column to select.
     */
    protected $column = '*';

    /**
     * @var int|null The limit for the query.
     */
    protected $limit;

    /**
     * @var int|null The offset for the query.
     */
    protected $offset;

    /**
     * @var array|null The order by columns and directions.
     */
    protected $orderBy;

    /**
     * @var array|string|null The group by columns.
     */
    protected $groupBy;

    /**
     * @var array The having columns.
     */
    protected $having;

    /**
     * @var string|null The conditions for WHERE clause.
     */
    protected $where = null;

    /**
     * @var string|null The join clauses.
     */
    protected $joins = null;

    /**
     * @var array The relations use for eager loading (N+1).
     */
    protected $relations = [];

    /**
     * @var array The previously executed error query
     */
    protected $_error;

    /**
     * @var bool The flag for sanitization for insert/update method.
     */
    protected $_secureInput = false;

    /**
     * @var bool The flag for sanitization for get/fetch/pagination.
     */
    protected $_secureOutput = false;

    /**
     * @var array An array to store the bound parameters.
     */
    protected $_binds = [];

    /**
     * @var string The raw SQL query string.
     */
    protected $_query;

    /**
     * @var array An array to store profiling information (optional).
     */
    protected $_profiler = [];

    /**
     * @var array An array to store profiling config to display.
     */
    protected $_profilerShowConf = [
        'php_ver' => true,
        'os_ver' => true,
        'db_driver' => true,
        'db_ver' => true,
        'method' => true,
        'start_time' => true,
        'end_time' => true,
        'query' => true,
        'binds' => true,
        'full_query' => true,
        'execution_time' => true,
        'execution_status' => true,
        'memory_usage' => true,
        'memory_usage_peak' => true,
        'stack_trace' => false
    ];

    /**
     * @var string An string to store current active profiler
     */
    protected $_profilerActive = 'main';

    /**
     * @var string|null The cache key.
     */
    protected $cacheFile = null;

    /**
     * @var string|integer The cache to expire in seconds.
     */
    protected $cacheFileExpired = 1800;

    /**
     * @var bool Whether parallel processing is enabled.
     */
    protected $_parallelEnabled = false;

    /**
     * @var int The number of worker processes to use for parallel processing (if enabled).
     */
    protected $_parallelWorker = 3;

    /**
     * @var float The duration (in seconds) to sleep before next processing.
     */
    protected $_parallelSleep = 1;

    /**
     * @var int The number of tasks to process in a batch during parallel processing for eager loader.
     */
    protected $_parallelBatchSize = 1000;

    /**
     * @var array The list of database support.
     */
    protected $listDatabaseDriverSupport = [
        'mysql' => 'MySQL',
        'mssql' => 'MSSQL',
        'oci' => 'Oci',
        'mariadb' => 'MariaDB',
        'fdb' => 'Firebird',
        '-' => 'Unknown Driver'
    ];

    /**
     * @var string The return type for return result.
     */
    protected $returnType = 'array';

    # Implement ConnectionInterface logic

    public function initialize(array $config)
    {
        $this->config = $config;
        $this->driver = isset($this->config['driver']) ? strtolower($this->config['driver']) : '-';
        self::$_instance = $this;
    }

    // Override function logic based on driver
    public function connect($connectionName = 'default')
    {
        return $this;
    }

    public function setConnection($connectionID)
    {
        if (!isset($this->config[$connectionID]))
            throw new \Exception('Connection ' . $connectionID . ' was not added.');

        $this->connectionName = $connectionID;
    }

    public function getConnection($connectionID = null)
    {
        return $this->connectionName;
    }

    public function setDatabase($databaseName = null)
    {
        $this->schema = $databaseName;
    }

    public function getDatabase()
    {
        return $this->schema ?? null;
    }

    public function getPlatform()
    {
        $dbPlatform = isset($this->config['driver']) ? strtolower($this->config['driver']) : '-';
        return $this->listDatabaseDriverSupport[$dbPlatform];
    }

    public function getVersion()
    {
        // Get database version 
        if (isset($this->pdo[$this->connectionName]) && $this->pdo[$this->connectionName] instanceof \PDO) {
            return $this->pdo[$this->connectionName]->getAttribute(\PDO::ATTR_SERVER_VERSION);
        } else {
            return 'Unknown';  // Handle cases where no database connection exists
        }
    }

    public function disconnect($connection = 'default', $remove = false)
    {
        if (!isset($this->pdo[$connection])) {
            return;
        }

        $this->pdo[$connection] = null;
        unset($this->pdo[$connection]);

        if ($connection == $this->connectionName) {
            $this->connectionName = 'default';
        }

        // Remove connection settings if $remove is true
        if ($remove && isset($this->config[$connection])) {
            unset($this->config[$connection]);
        }
    }

    # Implement BuilderStatementInterface logic

    public function reset()
    {
        $this->driver = 'mysql';
        $this->connectionName = 'default';
        $this->table = null;
        $this->column = '*';
        $this->limit = null;
        $this->offset = null;
        $this->orderBy = null;
        $this->groupBy = null;
        $this->where = null;
        $this->joins = null;
        $this->_error = [];
        $this->_secureInput = false;
        $this->_binds = [];
        $this->_query = [];
        $this->relations = [];
        $this->cacheFile = null;
        $this->cacheFileExpired = 1800;
        $this->_profilerActive = 'main';

        return $this;
    }

    public function table($table)
    {
        $this->table = trim($table);
        return $this;
    }

    public function select($column = '*')
    {
        $this->column = is_array($column) ? implode(', ', $column) : $column;
        return $this;
    }

    public function whereRaw($rawQuery, $value = [], $whereType = 'AND')
    {
        try {
            if (!is_string($rawQuery)) {
                throw new \InvalidArgumentException('Invalid query format. Must be a string');
            }

            if (!empty($value) && !is_array($value)) {
                throw new \InvalidArgumentException("Value for " . __FUNCTION__ . " must be an array");
            }

            // Ensure where type AND / OR
            if (!in_array($whereType, ['AND', 'OR'])) {
                throw new \InvalidArgumentException('Invalid where type. Supported operators are: AND/OR');
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($rawQuery, 'Full/Sub SQL statements are not allowed in whereRaw(). Please use simpleQuery() function.');

            $this->_buildWhereClause($rawQuery, $value, 'RAW', $whereType);
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
            throw $e; // Rethrow the exception after logging it
        }
    }

    public function where($columnName, $value = NULL, $operator = '=')
    {
        try {

            if (!is_callable($columnName) && !is_string($columnName) && !is_array($columnName)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string or an associative array.');
            }

            // Check if operator is supported
            $supportedOperators = ['=', '<', '>', '<=', '>=', '<>', '!=', 'LIKE'];
            if (!in_array($operator, $supportedOperators)) {
                throw new \InvalidArgumentException('Invalid operator. Supported operators are: ' . implode(', ', $supportedOperators));
            }

            if (is_callable($columnName)) {
                $db = clone $this; // Clone current query builder instance
                $db->reset(); // reset all variable
                $columnName($db); // Pass the current object to the closure

                if (!empty($db->where) && is_string($db->where)) {
                    // Check if variable contains a full SQL statement
                    $this->_forbidRawQuery($db->where, 'Full/Sub SQL statements are not allowed in where(). Please use simpleQuery() function.');

                    $this->whereRaw($db->where, $db->_binds, 'AND');
                } else {
                    throw new \InvalidArgumentException('Callable must return a valid SQL clause string.');
                }

                unset($db);
                return $this;
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery([$columnName, $value], 'Full/Sub SQL statements are not allowed in where(). Please use simpleQuery() function.');

            if (is_array($columnName)) {
                foreach ($columnName as $column => $val) {
                    if (!is_string($column)) {
                        throw new \InvalidArgumentException('Invalid column name in array. Must be a string.');
                    }
                    $this->_buildWhereClause($column, $val, $operator, 'AND');
                }
            } else {
                $this->_buildWhereClause($columnName, $value, $operator, 'AND');
            }

            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function orWhere($columnName, $value = NULL, $operator = '=')
    {
        try {

            if (!is_callable($columnName) && !is_string($columnName) && !is_array($columnName)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string or an associative array.');
            }

            // Check if operator is supported
            $supportedOperators = ['=', '<', '>', '<=', '>=', '<>', '!=', 'LIKE'];
            if (!in_array($operator, $supportedOperators)) {
                throw new \InvalidArgumentException('Invalid operator. Supported operators are: ' . implode(', ', $supportedOperators));
            }

            if (is_callable($columnName)) {
                $db = clone $this; // Clone current query builder instance
                $db->reset(); // reset all variable
                $columnName($db); // Pass the current object to the closure

                if (!empty($db->where) && is_string($db->where)) {
                    // Check if variable contains a full SQL statement
                    $this->_forbidRawQuery($db->where, 'Full/Sub SQL statements are not allowed in orWhere(). Please use simpleQuery() function.');

                    $this->whereRaw($db->where, $db->_binds, 'OR');
                } else {
                    throw new \InvalidArgumentException('Callable must return a valid SQL clause string.');
                }

                unset($db);
                return $this;
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery([$columnName, $value], 'Full/Sub SQL statements are not allowed in orWhere(). Please use simpleQuery() function.');

            if (is_array($columnName)) {
                foreach ($columnName as $column => $val) {
                    if (!is_string($column)) {
                        throw new \InvalidArgumentException('Invalid column name in array. Must be a string.');
                    }
                    $this->_buildWhereClause($column, $val, $operator, 'OR');
                }
            } else {
                $this->_buildWhereClause($columnName, $value, $operator, 'OR');
            }

            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function whereIn($column, $value = [])
    {
        try {

            if (!is_string($column)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            if (!is_array($value)) {
                throw new \InvalidArgumentException("Value for 'IN' operator must be an array");
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in whereIn(). Please use simpleQuery() function.');

            $this->_buildWhereClause($column, $value, 'IN', 'AND');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function orWhereIn($column, $value = [])
    {
        try {

            if (!is_string($column)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            if (!is_array($value)) {
                throw new \InvalidArgumentException("Value for 'IN' operator must be an array");
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in orWhereIn(). Please use simpleQuery() function.');

            $this->_buildWhereClause($column, $value, 'IN', 'OR');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function whereNotIn($column, $value = [])
    {
        try {

            if (!is_string($column)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            if (!is_array($value)) {
                throw new \InvalidArgumentException("Value for 'NOT IN' operator must be an array");
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in whereNotIn(). Please use simpleQuery() function.');

            $this->_buildWhereClause($column, $value, 'NOT IN', 'AND');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function orWhereNotIn($column, $value = [])
    {
        try {

            if (!is_string($column)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            if (!is_array($value)) {
                throw new \InvalidArgumentException("Value for 'NOT IN' operator must be an array");
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in orWhereNotIn(). Please use simpleQuery() function.');

            $this->_buildWhereClause($column, $value, 'NOT IN', 'OR');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function whereBetween($columnName, $start, $end)
    {
        try {
            // Validate column name type
            if (!is_string($columnName)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            // Validate and format start and end values
            $formattedValues = [];
            foreach ([$start, $end] as $value) {
                if (is_int($value) || is_float($value)) {
                    // Numeric value: no formatting needed
                    $formattedValues[] = $value;
                } else if (preg_match('/^\d{1,4}-\d{2}-\d{2}$/', $value)) {
                    // Check for YYYY-MM-DD format (date)
                    $formattedValues[] = $this->pdo[$this->connectionName]->quote($value);
                } else if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
                    // Check for HH:MM:SS format (time)
                    $formattedValues[] = $this->pdo[$this->connectionName]->quote($value);
                } else {
                    throw new \InvalidArgumentException('Invalid start or end value for BETWEEN. Must be numeric, date (YYYY-MM-DD), or time (HH:MM:SS).');
                }
            }

            // Ensure start is less than or equal to end for valid range
            if (!($formattedValues[0] <= $formattedValues[1])) {
                throw new \InvalidArgumentException('Start value must be less than or equal to end value for BETWEEN.');
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($columnName, 'Full/Sub SQL statements are not allowed in whereBetween(). Please use simpleQuery() function.');

            $this->_buildWhereClause($columnName, $formattedValues, 'BETWEEN', 'AND');

            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function orWhereBetween($columnName, $start, $end)
    {
        try {
            // Validate column name type
            if (!is_string($columnName)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            // Validate and format start and end values
            $formattedValues = [];
            foreach ([$start, $end] as $value) {
                if (is_int($value) || is_float($value)) {
                    // Numeric value: no formatting needed
                    $formattedValues[] = $value;
                } else if (preg_match('/^\d{1,4}-\d{2}-\d{2}$/', $value)) {
                    // Check for YYYY-MM-DD format (date)
                    $formattedValues[] = $this->pdo[$this->connectionName]->quote($value);
                } else if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
                    // Check for HH:MM:SS format (time)
                    $formattedValues[] = $this->pdo[$this->connectionName]->quote($value);
                } else {
                    throw new \InvalidArgumentException('Invalid start or end value for BETWEEN. Must be numeric, date (YYYY-MM-DD), or time (HH:MM:SS).');
                }
            }

            // Ensure start is less than or equal to end for valid range
            if (!($formattedValues[0] <= $formattedValues[1])) {
                throw new \InvalidArgumentException('Start value must be less than or equal to end value for BETWEEN.');
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($columnName, 'Full/Sub SQL statements are not allowed in orWhereBetween(). Please use simpleQuery() function.');

            $this->_buildWhereClause($columnName, $formattedValues, 'BETWEEN', 'AND');

            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function whereNotBetween($columnName, $start, $end)
    {
        try {

            if (!is_string($columnName)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            $formattedValues = [];
            foreach ([$start, $end] as $value) {
                if (is_int($value) || is_float($value)) {
                    // Numeric value: no formatting needed
                    $formattedValues[] = $value;
                } else if (preg_match('/^\d{1,4}-\d{2}-\d{2}$/', $value)) {
                    // Check for YYYY-MM-DD format (date)
                    $formattedValues[] = $this->pdo[$this->connectionName]->quote($value);
                } else if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
                    // Check for HH:MM:SS format (time)
                    $formattedValues[] = $this->pdo[$this->connectionName]->quote($value);
                } else {
                    throw new \InvalidArgumentException('Invalid start or end value for NOT BETWEEN. Must be numeric, date (YYYY-MM-DD), or time (HH:MM:SS).');
                }
            }

            // Ensure start is less than or equal to end for valid range
            if (!($formattedValues[0] <= $formattedValues[1])) {
                throw new \InvalidArgumentException('Start value must be less than or equal to end value for NOT BETWEEN.');
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($columnName, 'Full/Sub SQL statements are not allowed in whereNotBetween(). Please use simpleQuery() function.');

            $this->_buildWhereClause($columnName, $formattedValues, 'NOT BETWEEN', 'AND');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function orWhereNotBetween($columnName, $start, $end)
    {
        try {

            if (!is_string($columnName)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            $formattedValues = [];
            foreach ([$start, $end] as $value) {
                if (is_int($value) || is_float($value)) {
                    // Numeric value: no formatting needed
                    $formattedValues[] = $value;
                } else if (preg_match('/^\d{1,4}-\d{2}-\d{2}$/', $value)) {
                    // Check for YYYY-MM-DD format (date)
                    $formattedValues[] = $this->pdo[$this->connectionName]->quote($value);
                } else if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
                    // Check for HH:MM:SS format (time)
                    $formattedValues[] = $this->pdo[$this->connectionName]->quote($value);
                } else {
                    throw new \InvalidArgumentException('Invalid start or end value for NOT BETWEEN. Must be numeric, date (YYYY-MM-DD), or time (HH:MM:SS).');
                }
            }

            // Ensure start is less than or equal to end for valid range
            if (!($formattedValues[0] <= $formattedValues[1])) {
                throw new \InvalidArgumentException('Start value must be less than or equal to end value for NOT BETWEEN.');
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($columnName, 'Full/Sub SQL statements are not allowed in orWhereNotBetween(). Please use simpleQuery() function.');

            $this->_buildWhereClause($columnName, $formattedValues, 'NOT BETWEEN', 'AND');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function whereNull($column)
    {
        try {

            if (!is_string($column)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in whereNull(). Please use simpleQuery() function.');

            $this->_buildWhereClause($column, null, 'IS NULL', 'AND');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function orWhereNull($column)
    {
        try {

            if (!is_string($column)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in orWhereNull(). Please use simpleQuery() function.');

            $this->_buildWhereClause($column, null, 'IS NULL', 'OR');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function whereNotNull($column)
    {
        try {

            // Validate column name type
            if (!is_string($column)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in whereNotNull(). Please use simpleQuery() function.');

            $this->_buildWhereClause($column, null, 'IS NOT NULL', 'AND');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function orWhereNotNull($column)
    {
        try {

            // Validate column name type
            if (!is_string($column)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in orWhereNotNull(). Please use simpleQuery() function.');

            $this->_buildWhereClause($column, null, 'IS NOT NULL', 'OR');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    // Override function logic based on driver
    public function whereDate($column, $date, $operator = '=')
    {
        try {

            if (!is_string($column)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            // Check if date is valid
            $timestamp = strtotime($date);
            if ($timestamp === false) {
                throw new \InvalidArgumentException('Invalid date format. Date must be in a recognizable format. Suggested format : Y-m-d OR d-m-Y');
            }

            // Convert to Y-m-d format
            $formattedDate = date('Y-m-d', $timestamp);

            // Check if operator is supported
            $supportedOperators = ['=', '<', '>', '<=', '>=', '<>', '!='];
            if (!in_array($operator, $supportedOperators)) {
                throw new \InvalidArgumentException('Invalid operator. Supported operators are: ' . implode(', ', $supportedOperators));
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in whereDate(). Please use simpleQuery() function.');

            $this->_buildWhereClause($column, $formattedDate, $operator, 'AND');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    // Override function logic based on driver
    public function orWhereDate($column, $date, $operator = '=')
    {
        try {

            if (!is_string($column)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            // Check if date is valid
            $timestamp = strtotime($date);
            if ($timestamp === false) {
                throw new \InvalidArgumentException('Invalid date format. Date must be in a recognizable format. Suggested format : Y-m-d OR d-m-Y');
            }

            // Convert to Y-m-d format
            $formattedDate = date('Y-m-d', $timestamp);

            // Check if operator is supported
            $supportedOperators = ['=', '<', '>', '<=', '>=', '<>', '!='];
            if (!in_array($operator, $supportedOperators)) {
                throw new \InvalidArgumentException('Invalid operator. Supported operators are: ' . implode(', ', $supportedOperators));
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in orWhereDate(). Please use simpleQuery() function.');

            $this->_buildWhereClause($column, $formattedDate, $operator, 'OR');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    // Override function logic based on driver
    public function whereDay($column, $day, $operator = '=')
    {
        try {

            if (!is_string($column)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            if (!is_numeric($day) || $day < 1 || $day > 31) {
                throw new \InvalidArgumentException('Invalid day. Must be a number between 1 and 31.');
            }

            // Check if operator is supported
            $supportedOperators = ['=', '<', '>', '<=', '>=', '<>', '!='];
            if (!in_array($operator, $supportedOperators)) {
                throw new \InvalidArgumentException('Invalid operator. Supported operators are: ' . implode(', ', $supportedOperators));
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in whereDay(). Please use simpleQuery() function.');

            $this->_buildWhereClause($column, (int)$day, $operator, 'AND');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    // Override function logic based on driver
    public function orWhereDay($column, $day, $operator = '=')
    {
        try {

            if (!is_string($column)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            if (!is_numeric($day) || $day < 1 || $day > 31) {
                throw new \InvalidArgumentException('Invalid day. Must be a number between 1 and 31.');
            }

            // Check if operator is supported
            $supportedOperators = ['=', '<', '>', '<=', '>=', '<>', '!='];
            if (!in_array($operator, $supportedOperators)) {
                throw new \InvalidArgumentException('Invalid operator. Supported operators are: ' . implode(', ', $supportedOperators));
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in orWhereDay(). Please use simpleQuery() function.');

            $this->_buildWhereClause($column, (int)$day, $operator, 'OR');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    // Override function logic based on driver
    public function whereMonth($column, $month, $operator = '=')
    {
        try {

            if (!is_string($column)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            if (!is_numeric($month) || $month < 1 || $month > 12) {
                throw new \InvalidArgumentException('Invalid month. Must be a number between 1 and 12.');
            }

            // Check if operator is supported
            $supportedOperators = ['=', '<', '>', '<=', '>=', '<>', '!='];
            if (!in_array($operator, $supportedOperators)) {
                throw new \InvalidArgumentException('Invalid operator. Supported operators are: ' . implode(', ', $supportedOperators));
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in whereMonth(). Please use simpleQuery() function.');

            $this->_buildWhereClause($column, (int)$month, $operator, 'AND');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    // Override function logic based on driver
    public function orWhereMonth($column, $month, $operator = '=')
    {
        try {

            if (!is_string($column)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            if (!is_numeric($month) || $month < 1 || $month > 12) {
                throw new \InvalidArgumentException('Invalid month. Must be a number between 1 and 12.');
            }

            // Check if operator is supported
            $supportedOperators = ['=', '<', '>', '<=', '>=', '<>', '!='];
            if (!in_array($operator, $supportedOperators)) {
                throw new \InvalidArgumentException('Invalid operator. Supported operators are: ' . implode(', ', $supportedOperators));
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in orWhereMonth(). Please use simpleQuery() function.');

            $this->_buildWhereClause($column, (int)$month, $operator, 'OR');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    // Override function logic based on driver
    public function whereYear($column, $year, $operator = '=')
    {
        try {
            if (!is_string($column)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            if (!is_numeric($year) || strlen((string)$year) !== 4) {
                throw new \InvalidArgumentException('Invalid year. Must be a four-digit number.');
            }

            // Check if operator is supported
            $supportedOperators = ['=', '<', '>', '<=', '>=', '<>', '!='];
            if (!in_array($operator, $supportedOperators)) {
                throw new \InvalidArgumentException('Invalid operator. Supported operators are: ' . implode(', ', $supportedOperators));
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in whereYear(). Please use simpleQuery() function.');

            $this->_buildWhereClause($column, (int)$year, $operator, 'AND');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    // Override function logic based on driver
    public function orWhereYear($column, $year, $operator = '=')
    {
        try {
            if (!is_string($column)) {
                throw new \InvalidArgumentException('Invalid column name. Must be a string.');
            }

            if (!is_numeric($year) || strlen((string)$year) !== 4) {
                throw new \InvalidArgumentException('Invalid year. Must be a four-digit number.');
            }

            // Check if operator is supported
            $supportedOperators = ['=', '<', '>', '<=', '>=', '<>', '!='];
            if (!in_array($operator, $supportedOperators)) {
                throw new \InvalidArgumentException('Invalid operator. Supported operators are: ' . implode(', ', $supportedOperators));
            }

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in orWhereYear(). Please use simpleQuery() function.');

            $this->_buildWhereClause($column, (int)$year, $operator, 'OR');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    // Override function logic based on driver
    public function whereJsonContains($columnName, $jsonPath, $value)
    {
        // Check if the column is not null
        $this->whereNotNull($columnName);

        // Construct the JSON search condition
        $jsonCondition = "JSON_CONTAINS($columnName, '" . json_encode([$jsonPath => $value]) . "', '$')";

        // Add the condition to the query builder
        $this->where($jsonCondition, null, 'JSON');
        return $this;
    }

    public function join($table, $foreignKey, $localKey, $joinType = 'LEFT')
    {
        // Validate input parameters
        if (!is_string($table) || !is_string($foreignKey) || !is_string($localKey)) {
            throw new \InvalidArgumentException('Invalid column names or table name provided.');
        }

        $validJoinTypes = ['INNER', 'LEFT', 'RIGHT', 'OUTER', 'LEFT OUTER', 'RIGHT OUTER'];
        if (!in_array(strtoupper($joinType), $validJoinTypes)) {
            throw new \InvalidArgumentException('Invalid join type. Valid types are: ' . implode(', ', $validJoinTypes));
        }

        if (empty($this->table)) {
            throw new \Exception('No table selected', 400);
        }

        // Build the join clause
        $this->joins .= " $joinType JOIN `$table` ON `$table`.`$foreignKey` = `$this->table`.`$localKey`";

        return $this;
    }

    public function leftJoin($table, $foreignKey, $localKey, $conditions = null)
    {
        // Validate input parameters
        if (!is_string($table) || !is_string($foreignKey) || !is_string($localKey)) {
            throw new \InvalidArgumentException('Invalid column names or table name provided.');
        }

        if (empty($this->table)) {
            throw new \Exception('No table selected', 400);
        }

        // Build the join clause
        $this->joins .= " LEFT JOIN `$table` ON `$table`.`$foreignKey` = `$this->table`.`$localKey` $conditions";

        return $this;
    }

    public function rightJoin($table, $foreignKey, $localKey, $conditions = null)
    {
        // Validate input parameters
        if (!is_string($table) || !is_string($foreignKey) || !is_string($localKey)) {
            throw new \InvalidArgumentException('Invalid column names or table name provided.');
        }

        if (empty($this->table)) {
            throw new \Exception('No table selected', 400);
        }

        // Build the join clause
        $this->joins .= " RIGHT JOIN `$table` ON `$table`.`$foreignKey` = `$this->table`.`$localKey` $conditions";

        return $this;
    }

    public function innerJoin($table, $foreignKey, $localKey, $conditions = null)
    {
        // Validate input parameters
        if (!is_string($table) || !is_string($foreignKey) || !is_string($localKey)) {
            throw new \InvalidArgumentException('Invalid column names or table name provided.');
        }

        if (empty($this->table)) {
            throw new \Exception('No table selected', 400);
        }

        // Build the join clause
        $this->joins .= " INNER JOIN `$table` ON `$table`.`$foreignKey` = `$this->table`.`$localKey` $conditions";

        return $this;
    }

    public function outerJoin($table, $foreignKey, $localKey, $conditions = null)
    {
        // Validate input parameters
        if (!is_string($table) || !is_string($foreignKey) || !is_string($localKey)) {
            throw new \InvalidArgumentException('Invalid column names or table name provided.');
        }

        if (empty($this->table)) {
            throw new \Exception('No table selected', 400);
        }

        // Build the join clause
        $this->joins .= " FULL OUTER JOIN `$table` ON `$table`.`$foreignKey` = `$this->table`.`$localKey` $conditions";

        return $this;
    }

    public function orderBy($columns, $direction = 'DESC')
    {
        // Check if direction is valid
        if (!in_array(strtoupper($direction), ['ASC', 'DESC'])) {
            throw new \InvalidArgumentException('Order direction must be "ASC" or "DESC".');
        }

        if (is_array($columns)) {
            foreach ($columns as $column => $dir) {
                $direction = strtoupper(!in_array(strtoupper($dir), ['ASC', 'DESC']) ? 'DESC' : $dir);
                $this->orderBy[] = "$column $direction"; // Push to the order by array
            }
        } else {
            $this->orderBy[] = "$columns $direction"; // Push a single order by clause
        }

        return $this;
    }

    public function orderByRaw($string, $bindParams = null)
    {
        // Check if string is empty
        if (empty($string)) {
            throw new \InvalidArgumentException('Order by cannot be null in `orderByRaw`.');
        }

        // Check for DESC or ASC
        if (!preg_match('/\b(DESC|ASC)\b/i', $string)) {
            throw new \InvalidArgumentException('Order by clause must contain either DESC or ASC in `orderByRaw`.');
        }

        // Check if orderByRaw contains a full SQL statement
        $this->_forbidRawQuery($string, 'Full SQL statements are not allowed in `orderByRaw`.');

        // Store the raw order by string
        $this->orderBy[] = $string;

        if (!empty($bindParams)) {
            if (is_array($bindParams)) {
                $this->_binds = array_merge($this->_binds, $bindParams);
            } else {
                $this->_binds[] = $bindParams;
            }
        }

        return $this;
    }

    public function groupBy($columns)
    {
        if (is_string($columns)) {
            // Validate column name, Allow commas for multiple columns
            if (!preg_match('/^[a-zA-Z0-9._, ]+$/', $columns)) {
                throw new \InvalidArgumentException('Invalid column name(s) for groupBy.');
            }
            $this->groupBy = "$columns";
        } else if (is_array($columns)) {

            $groupBy = [];
            foreach ($columns as $column) {
                // Validate column name
                if (!preg_match('/^[a-zA-Z0-9._]+$/', $column)) {
                    throw new \InvalidArgumentException('Invalid column name in groupBy array.');
                }
                $groupBy[] = "`$column`";
            }

            $this->groupBy = implode(', ', $groupBy);
        } else {
            throw new \InvalidArgumentException('groupBy expects a string or an array of column names.');
        }

        return $this;
    }

    public function having($column, $value, $operator = '=')
    {
        // Check if string is empty
        if (empty($column)) {
            throw new \InvalidArgumentException('Column cannot be null in `having`.');
        }

        $this->having[] = "$column $operator '$value'";
        return $this;
    }

    public function havingRaw($conditions)
    {
        // Check if string is empty
        if (empty($conditions)) {
            throw new \InvalidArgumentException('Conditions cannot be null in `havingRaw`.');
        }

        $this->having[] = $conditions;
        return $this;
    }

    // Override function logic based on driver
    public function limit($limit)
    {
        // Try to cast the input to an integer
        $limit = filter_var($limit, FILTER_VALIDATE_INT);

        // Check if the input is not an integer after casting
        if ($limit === false) {
            throw new \InvalidArgumentException('Limit must be an integer.');
        }

        // Check if the input is less then 1
        if ($limit < 1) {
            throw new \InvalidArgumentException('Limit must be integer with higher then zero');
        }

        $this->limit =  " LIMIT " . $limit;
        return $this;
    }

    // Override function logic based on driver
    public function offset($offset)
    {
        // Try to cast the input to an integer
        $offset = filter_var($offset, FILTER_VALIDATE_INT);

        // Check if the input is not an integer after casting
        if ($offset === false) {
            throw new \InvalidArgumentException('Offset must be an integer.');
        }

        // Check if the input is less then 0
        if ($offset < 0) {
            throw new \InvalidArgumentException('Offset must be integer with higher or equal to zero');
        }

        $this->offset = " OFFSET " . $offset;
        return $this;
    }

    public function with($alias, $table, $foreign_key, $local_key, \Closure $callback = null)
    {
        $this->relations[$alias] = ['type' => 'get', 'details' => compact('table', 'foreign_key', 'local_key', 'callback')];
        return $this;
    }

    public function withOne($alias, $table, $foreign_key, $local_key, \Closure $callback = null)
    {
        $this->relations[$alias] = ['type' => 'fetch', 'details' => compact('table', 'foreign_key', 'local_key', 'callback')];
        return $this;
    }

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
    protected function _buildWhereClause($columnName, $value = null, $operator = '=', $whereType = 'AND')
    {
        if (!isset($this->where)) {
            $this->where = "";
        } else {
            $this->where .= " $whereType ";
        }

        $placeholder = '?'; // Use a single placeholder for all conditions

        switch ($operator) {
            case 'IN':
            case 'NOT IN':
                if (!is_array($value)) {
                    throw new \InvalidArgumentException('Value for IN or NOT IN operator must be an array');
                }
                $this->where .= "$columnName $operator (" . implode(',', array_fill(0, count($value), $placeholder)) . ")";
                break;
            case 'BETWEEN':
            case 'NOT BETWEEN':
                if (!is_array($value) || count($value) !== 2) {
                    throw new \InvalidArgumentException("Value for 'BETWEEN' or 'NOT BETWEEN' operator must be an array with two elements (start and end)");
                }
                $this->where .= "($columnName $operator $placeholder AND $placeholder)";
                break;
            case 'JSON':
                $this->where .= "$columnName";
                break;
            case 'IS NULL':
            case 'IS NOT NULL':
                $this->where .= "$columnName $operator";
                break;
            case 'RAW':
                $this->where .= "($columnName)";
                break;
            default:
                $this->where .= "$columnName $operator $placeholder";
                break;
        }

        if (!empty($value)) {
            if (is_array($value)) {
                $this->_binds = array_merge($this->_binds, $value);
            } else {
                $this->_binds[] = $value;
            }
        }
    }

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
    protected function _buildSelectQuery()
    {
        // Build the basic SELECT clause with fields
        $this->_query = "SELECT " . ($this->column === '*' ? '*' : $this->column) . " FROM ";

        // Append table name with schema (if provided)
        if (empty($this->schema)) {
            $this->_query .= "`$this->table`";
        } else {
            $this->_query .= "`$this->schema`.`$this->table`";
        }

        // Add JOIN clauses if available
        if ($this->joins) {
            $this->_query .= $this->joins;
        }

        // Add WHERE clause if conditions exist
        if ($this->where) {
            $this->_query .= " WHERE " . $this->where;
        }

        // Add GROUP BY clause if specified
        if ($this->groupBy) {
            $this->_query .= " GROUP BY " . $this->groupBy;
        }

        // Add HAVING clause if specified
        if ($this->having) {
            $having = implode(' AND ', $this->having);
            $this->_query .= " HAVING " . $this->having;
        }

        // Add ORDER BY clause if specified
        if ($this->orderBy) {
            $orderBy = implode(', ', $this->orderBy);
            $this->_query .= " ORDER BY " . $orderBy;
        }

        // Add LIMIT clause if specified
        if ($this->limit) {
            if (!isset($this->listDatabaseDriverSupport[$this->driver])) {
                throw new \Exception("LIMIT clause not supported for driver: " . $this->driver);
            }

            $this->_query .= $this->limit;
        }

        // Add OFFSET clause if offset is set
        if ($this->offset) {
            $this->_query .= $this->offset;
        }

        // Expand asterisks in the query (replace with actual column names)
        $this->_query = $this->_expandAsterisksInQuery($this->_query);

        return $this;
    }

    # Implement QueryInterface logic

    public function simpleQuery($query)
    {
        // Check if string is empty
        if (empty($query)) {
            throw new \InvalidArgumentException('Query string cannot be null in `simpleQuery`.');
        }

        $this->_query = $query;
        $this->_binds = [];
        return $this;
    }

    public function bindQuery($query, $binds = [])
    {
        // Check if string is empty
        if (empty($query)) {
            throw new \InvalidArgumentException('Query string cannot be null in `bindQuery`.');
        }

        // check if not an array or it empty
        if (!is_array($binds) || empty($binds)) {
            throw new \InvalidArgumentException('Bind parameters must be provided as an array');
        }

        $this->_query = $query;
        $this->_binds = $binds;
        return $this;
    }

    public function get()
    {
        $result = null;

        // Build the final SELECT query string
        $this->_buildSelectQuery();

        $cachePrefix = 'get_';
        if (!empty($this->cacheFile)) {
            $result = $this->_getCacheData($cachePrefix . $this->cacheFile);
        }

        if (empty($result)) {

            // Start profiler for performance measurement 
            $this->_startProfiler(__FUNCTION__);

            // Prepare the query statement
            $stmt = $this->pdo[$this->connectionName]->prepare($this->_query);

            // Bind parameters if any
            if (!empty($this->_binds)) {
                $this->_bindParams($stmt, $this->_binds);
            }

            try {
                // Log the query for debugging 
                $this->_profiler['profiling'][$this->_profilerActive]['query'] = $this->_query;

                // Generate the full query string with bound values 
                $this->_generateFullQuery($this->_query, $this->_binds);

                // Execute the prepared statement
                $stmt->execute();

                // Fetch all results as associative arrays
                $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                // Log database errors
                $this->db_error_log($e, __FUNCTION__);
                throw $e; // Re-throw the exception
            }

            // Stop profiler 
            $this->_stopProfiler();

            // Save connection name, relations & caching info temporarily
            $_temp_connection = $this->connectionName;
            $_temp_relations = $this->relations;
            $_temp_cacheKey = $this->cacheFile;
            $_temp_cacheExpired = $this->cacheFileExpired;

            // Check if need to sanitize output
            $result = $this->_safeOutputSanitize($result);

            // Reset internal properties for next query
            $this->reset();

            // Process eager loading if implemented 
            if (!empty($result) && !empty($_temp_relations)) {
                $result = $this->_processEagerLoading($result, $_temp_relations, $_temp_connection, 'get');
            }

            if (!empty($_temp_cacheKey) && !empty($result)) {
                $this->_setCacheData($cachePrefix . $_temp_cacheKey, $result, $_temp_cacheExpired);
            }

            unset($_temp_connection, $_temp_relations, $_temp_cacheKey, $_temp_cacheExpired, $cachePrefix);
        }

        // Reset safeOutput
        $this->safeOutput(false);

        return $this->_returnResult($result);
    }

    public function fetch()
    {
        $result = null;

        // Set limit to 1 to ensure only 1 data return
        $this->limit(1);

        // Build the final SELECT query string
        $this->_buildSelectQuery();

        $cachePrefix = 'fetch_';
        if (!empty($this->cacheFile)) {
            $result = $this->_getCacheData($cachePrefix . $this->cacheFile);
        }

        if (empty($result)) {

            // Start profiler for performance measurement
            $this->_startProfiler(__FUNCTION__);

            // Prepare the query statement
            $stmt = $this->pdo[$this->connectionName]->prepare($this->_query);

            // Bind parameters if any
            if (!empty($this->_binds)) {
                $this->_bindParams($stmt, $this->_binds);
            }

            try {
                // Log the query for debugging
                $this->_profiler['profiling'][$this->_profilerActive]['query'] = $this->_query;

                // Generate the full query string with bound values
                $this->_generateFullQuery($this->_query, $this->_binds);

                // Execute the prepared statement
                $stmt->execute();

                // Fetch only the first result as an associative array
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                // Log database errors
                $this->db_error_log($e, __FUNCTION__);
                throw $e; // Re-throw the exception
            }

            // Stop profiler
            $this->_stopProfiler();

            // Save connection name, relations & caching info temporarily
            $_temp_connection = $this->connectionName;
            $_temp_relations = $this->relations;
            $_temp_cacheKey = $this->cacheFile;
            $_temp_cacheExpired = $this->cacheFileExpired;

            // Check if need to sanitize output
            $result = $this->_safeOutputSanitize($result);

            // Reset internal properties for next query
            $this->reset();

            // Process eager loading if implemented 
            if (!empty($result) && !empty($_temp_relations)) {
                $result = $this->_processEagerLoading($result, $_temp_relations, $_temp_connection, 'fetch');
            }

            if (!empty($_temp_cacheKey) && !empty($result)) {
                $this->_setCacheData($cachePrefix . $_temp_cacheKey, $result, $_temp_cacheExpired);
            }

            unset($_temp_connection, $_temp_relations, $_temp_cacheKey, $_temp_cacheExpired, $cachePrefix);
        }

        // Reset secureOutput
        $this->safeOutput(false);

        // Return the first result or null if not found
        return $this->_returnResult($result);
    }

    // Override function logic based on driver
    public function count()
    {
        try {

            // Start profiler for performance measurement
            $this->_startProfiler(__FUNCTION__);

            // Check if query is empty then generate it first.
            if (empty($this->_query)) {
                $this->_buildSelectQuery();
            }

            // Create a separate query to get total count
            $sqlTotal = 'SELECT COUNT(*) count ' . preg_replace('/\s+ORDER BY\s+.*?(?=\s+LIMIT|\s+OFFSET|\s+GROUP BY|$)/i', '', substr($this->_query, strpos($this->_query, 'FROM')));

            // Execute the total count query
            $stmtTotal = $this->pdo[$this->connectionName]->prepare($sqlTotal);

            // Bind parameters if any
            if (!empty($this->_binds)) {
                $this->_bindParams($stmtTotal, $this->_binds);
            }

            // Log the query for debugging 
            $this->_profiler['profiling'][__FUNCTION__]['query'] = $sqlTotal;

            // Generate the full query string with bound values 
            $this->_generateFullQuery($sqlTotal, $this->_binds);

            $stmtTotal->execute();
            $totalResult = $stmtTotal->fetch(\PDO::FETCH_ASSOC);

            // Stop profiler
            $this->_stopProfiler();

            return $totalResult['count'] ?? 0;
        } catch (\PDOException $e) {
            // Log database errors
            $this->db_error_log($e, __FUNCTION__);
            throw $e; // Re-throw the exception
        }
    }

    public function chunk($size, callable $callback)
    {
        $offset = 0;

        // Set the temporary data to holds the original value
        $_tempConnection = $this->connectionName;
        $_tempTable = $this->table;
        $_tempColumn = $this->column;
        $_tempOrderBy = $this->orderBy;
        $_tempGroupBy = $this->groupBy;
        $_tempWhere = $this->where;
        $_tempJoins = $this->joins;
        $_tempBinds = $this->_binds;
        $_tempRelation = $this->relations;

        while (true) {

            // Set back to original value for next details
            $this->connectionName = $_tempConnection;
            $this->table = $_tempTable;
            $this->column = $_tempColumn;
            $this->orderBy = $_tempOrderBy;
            $this->groupBy = $_tempGroupBy;
            $this->where = $_tempWhere;
            $this->joins = $_tempJoins;
            $this->_binds = $_tempBinds;
            $this->relations = $_tempRelation;

            $this->_setProfilerIdentifier('chunk_size' . $size . '_offset' . $offset);

            $this->limit($size)->offset($offset);
            $results = $this->get();

            if (empty($results)) {
                break;
            }

            if (call_user_func($callback, $results) === false) {
                break;
            }

            $offset += $size;
        }

        // Unset the variables to free memory
        unset($_tempConnection, $_tempTable, $_tempColumn, $_tempOrderBy, $_tempGroupBy, $_tempWhere, $_tempJoins, $_tempBinds, $_tempRelation);

        // Reset internal properties for next query
        $this->reset();

        return $this;
    }

    // Override function logic based on driver
    public function paginate($currentPage = 1, $limit = 10, $draw = 1)
    {
        // Reset the offset & limit to ensure the $this->_query not generate with that when call _buildSelectQuery() function
        $this->offset = $this->limit = null;

        // Build the final SELECT query string
        $this->_buildSelectQuery();

        // Start profiler for performance measurement 
        $this->_startProfiler(__FUNCTION__);

        try {

            // Calculate offset
            $offset = ($currentPage - 1) * $limit;

            // Get total count
            $this->_setProfilerIdentifier('count'); // set new profiler
            $total = $this->count();
            $this->_setProfilerIdentifier(); // reset back to paginate profiler

            // Calculate total pages
            $totalPages = ceil($total / $limit);

            // Add LIMIT and OFFSET clauses to the main query
            $this->_query .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;

            // Execute the main query
            $stmt = $this->pdo[$this->connectionName]->prepare($this->_query);

            // Bind parameters if any
            if (!empty($this->_binds)) {
                $this->_bindParams($stmt, $this->_binds);
            }

            // Log the query for debugging 
            $this->_profiler['profiling'][$this->_profilerActive]['query'] = $this->_query;

            // Generate the full query string with bound values 
            $this->_generateFullQuery($this->_query, $this->_binds);

            // Execute the prepared statement
            $stmt->execute();

            // Fetch the result in associative array
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Calculate next page
            $nextPage = ($currentPage < $totalPages) ? $currentPage + 1 : null;

            // Calculate previous page
            $previousPage = ($currentPage > 1) ? $currentPage - 1 : null;

            // Adjust array keys to start from previous count
            $startIndex = ($currentPage - 1) * $limit;

            if (!empty($result)) {
                $result = array_combine(range($startIndex, $startIndex + count($result) - 1), $result);
            }

            $paginate = [
                'draw' => $draw,
                'recordsTotal' => $total ?? 0,
                'recordsFiltered' => count($result) ?? 0,
                'data' => $this->_safeOutputSanitize($result) ?? null,
                'current_page' => $currentPage,
                'next_page' => $nextPage,
                'previous_page' => $previousPage,
                'last_page' => $totalPages,
                'error' => $currentPage > $totalPages ? "current page ({$currentPage}) is more than total page ({$totalPages})" : ''
            ];
        } catch (\PDOException $e) {
            // Log database errors
            $this->db_error_log($e, __FUNCTION__);
            throw $e; // Re-throw the exception
        }

        // Stop profiler 
        $this->_stopProfiler();

        // Save connection name and relations temporarily
        $_temp_connection = $this->connectionName;
        $_temp_relations = $this->relations;

        // Reset internal properties for next query
        $this->reset();

        // Process eager loading if implemented 
        if (!empty($paginate['data']) && !empty($_temp_relations)) {
            $paginate['data'] = $this->_processEagerLoading($paginate['data'], $_temp_relations, $_temp_connection, 'get');
        }

        unset($_temp_connection, $_temp_relations);

        // Reset safeOutput
        $this->safeOutput(false);

        return $paginate;
    }

    // Implement BuilderCrudInterface logic

    # CREATE NEW DATA OPERATION

    public function insert($data)
    {
        // Check if string is empty
        if (empty($data) || !is_array($data)) {
            throw new \InvalidArgumentException('Invalid column data. Must be a associative array.');
        }

        if (empty($this->table)) {
            throw new \InvalidArgumentException('Please specify the table.');
        }

        // Start profiler for performance measurement 
        $this->_startProfiler(__FUNCTION__);

        // sanitize column to ensure column is exists.
        $sanitizeData = $this->sanitizeColumn($data);

        // Build the final INSERT query string
        $this->_buildInsertQuery($sanitizeData);

        // Prepare the query statement
        $stmt = $this->pdo[$this->connectionName]->prepare($this->_query);

        // Bind parameters 
        $this->_bindParams($stmt, array_values($sanitizeData));

        try {
            // Log the query for debugging 
            $this->_profiler['profiling'][$this->_profilerActive]['query'] = $this->_query;

            // Generate the full query string with bound values 
            $this->_generateFullQuery($this->_query, $this->_binds);

            // Execute the statement
            $success = $stmt->execute($this->_binds);

            // Get the number of affected rows
            $affectedRows = $stmt->rowCount();

            // Get the last inserted ID
            $lastInsertId = $success ? $this->pdo[$this->connectionName]->lastInsertId() : null;

            // Return information about the insertion operation
            $result = [
                'code' => $success ? 201 : 422,
                'id' => $lastInsertId,
                'message' => $success ? 'Data inserted successfully' : 'Failed to insert data',
                'data' => $this->_safeOutputSanitize($sanitizeData)
            ];
        } catch (\PDOException $e) {
            // Log database errors
            $this->db_error_log($e, __FUNCTION__);
            throw $e; // Re-throw the exception
        }

        // Stop profiler 
        $this->_stopProfiler();

        // Reset internal properties for next query
        $this->reset();

        return $this->_returnResult($result) ?? false;
    }

    /**
     * Builds the SQL INSERT query string based on provided data.
     *
     * This internal function is used by the `insert` function to construct the SQL statement for inserting data into a database table. 
     * It takes the data array as input and builds the INSERT query with column names, placeholders for values, and the table name.
     *
     * @param array $data The associative array containing the data to be inserted. Keys represent column names, and values represent the data for those columns.
     *
     *
     * @return $this This object instance (used for method chaining).
     */
    protected function _buildInsertQuery($data)
    {
        // Check if data is empty or not an associative array (key-value pairs)
        if (empty($data) || !is_array($data)) {
            throw new \InvalidArgumentException('Invalid column data. Must be an associative array with column names as keys.');
        }

        // Construct column names string
        $columns = implode(', ', array_keys($data));

        // Construct placeholders for values
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        // Construct the SQL insert statement
        $this->_query = "INSERT INTO ";

        // Append table name with schema (if provided)
        if (empty($this->schema)) {
            $this->_query .= "`$this->table` ($columns)";
        } else {
            $this->_query .= "`$this->schema`.`$this->table` ($columns)";
        }

        $this->_query .= " VALUES ($placeholders)";

        return $this;
    }

    # UPDATE DATA OPERATION

    /**
     * Updates a record in the database based on the provided data.
     *
     * @param array $data An associative array containing column names as keys and new values as values.
     * @throws InvalidArgumentException If the provided data is empty, not an array, or not an associative array with column names as keys.
     * @throws InvalidArgumentException If the table name is not specified.
     * @return array An associative array containing information about the update operation, including code, affected rows, message, and data.
     */
    public function update($data)
    {
        // Check if string is empty
        if (empty($data) || !is_array($data)) {
            throw new \InvalidArgumentException('Invalid column data. Must be a associative array.');
        }

        if (empty($this->table)) {
            throw new \InvalidArgumentException('Please specify the table.');
        }

        // Start profiler for performance measurement 
        $this->_startProfiler(__FUNCTION__);

        // sanitize column to ensure column is exists.
        $sanitizeData = $this->sanitizeColumn($data);

        // Build the final INSERT query string
        $this->_buildUpdateQuery($sanitizeData);

        // Prepare the query statement
        $stmt = $this->pdo[$this->connectionName]->prepare($this->_query);

        // Bind parameters 
        $this->_bindParams($stmt, array_merge(array_values($sanitizeData), $this->_binds));

        try {
            // Log the query for debugging 
            $this->_profiler['profiling'][$this->_profilerActive]['query'] = $this->_query;

            // Generate the full query string with bound values 
            $this->_generateFullQuery($this->_query, $this->_binds);

            // Execute the statement
            $success = $stmt->execute($this->_binds);

            // Get the number of affected rows
            $affectedRows = $stmt->rowCount();

            // Return information about the insertion operation
            $result = [
                'code' => $success ? 200 : 422,
                'affected_rows' => $affectedRows,
                'message' => $success ? 'Data updated successfully' : 'Failed to update data',
                'data' => $this->_safeOutputSanitize($sanitizeData)
            ];
        } catch (\PDOException $e) {
            // Log database errors
            $this->db_error_log($e, __FUNCTION__);
            throw $e; // Re-throw the exception
        }

        // Stop profiler 
        $this->_stopProfiler();

        // Reset internal properties for next query
        $this->reset();

        return $this->_returnResult($result) ?? false;
    }

    /**
     * Builds the SQL UPDATE query string based on the provided data.
     *
     * @param array $data An associative array containing column names as keys and new values as values.
     * @throws InvalidArgumentException If the provided data is empty, not an array, or not an associative array with column names as keys.
     * @return object $this The current object instance for chaining methods.
     */
    protected function _buildUpdateQuery($data)
    {
        // Check if data is empty or not an associative array (key-value pairs)
        if (empty($data) || !is_array($data)) {
            throw new \InvalidArgumentException('Invalid column data. Must be an associative array with column names as keys.');
        }

        // Construct a comma-separated list of SET clauses
        $set = [];
        foreach ($data as $column => $value) {
            $set[] = "`$column` = ?";
        }
        $set = implode(', ', $set);

        // Construct the SQL UPDATE statement with table name
        $this->_query = "UPDATE ";
        if (empty($this->schema)) {
            $this->_query .= "`$this->table` ";
        } else {
            $this->_query .= "`$this->schema`.`$this->table` ";
        }

        // Append SET clause and placeholder for values
        $this->_query .= "SET $set";

        // Add WHERE clause if conditions exist
        if ($this->where) {
            $this->_query .= " WHERE " . $this->where;
        }

        return $this;
    }

    # DELETE DATA OPERATION

    /**
     * Deletes records from the database based on the previously configured criteria.
     *
     * This function executes a DELETE query against the database table associated with the object.
     * It returns an associative array containing information about the deletion operation,
     * including the success status, number of affected rows, and optional deleted data.
     *
     * @throws \PDOException If a database error occurs during the deletion process.
     *
     * @return array An associative array with the following keys:
     *   - code: HTTP status code (200 for success, 422 for failure)
     *   - affected_rows: The number of rows affected by the DELETE query
     *   - message: A human-readable message indicating success or failure
     *   - data (optional): An array containing the data of the deleted records (if retrieved beforehand)
     */
    public function delete()
    {
        // Build to get all the data before delete
        $newDb = clone $this;
        $deletedData = $newDb->get();
        unset($newDb); // remove to free memory

        // Start profiler for performance measurement 
        $this->_startProfiler(__FUNCTION__);

        // Build the final DELETE query string
        $this->_buildDeleteQuery();

        // Prepare the query statement
        $stmt = $this->pdo[$this->connectionName]->prepare($this->_query);

        // Bind parameters if any
        if (!empty($this->_binds)) {
            $this->_bindParams($stmt, $this->_binds);
        }

        try {
            // Log the query for debugging 
            $this->_profiler['profiling'][$this->_profilerActive]['query'] = $this->_query;

            // Generate the full query string with bound values 
            $this->_generateFullQuery($this->_query, $this->_binds);

            // Execute the SQL DELETE statement
            $success = $stmt->execute();

            // Get the number of affected rows
            $affectedRows = $stmt->rowCount();

            // Return information about the deletion operation
            $result = [
                'code' => $success ? 200 : 422,
                'affected_rows' => $affectedRows,
                'message' => $success ? 'Data deleted successfully' : 'Failed to delete data',
                'data' => $deletedData,
            ];
        } catch (\PDOException $e) {
            // Log database errors
            $this->db_error_log($e, __FUNCTION__);
            throw $e; // Re-throw the exception
        }

        // Stop profiler 
        $this->_stopProfiler();

        // Reset internal properties for next query
        $this->reset();

        return $this->_returnResult($result) ?? false;
    }

    /**
     * Build an SQL DELETE statement with PDO binding.
     *
     * @param string $table The name of the table to delete from.
     * @param string|array $conditions Optional. The condition(s) to specify which rows to delete.
     * @return string|null The generated SQL DELETE statement or null if $condition is not valid.
     */
    protected function _buildDeleteQuery()
    {
        // Construct the SQL delete statement
        $this->_query = "DELETE FROM ";

        // Append table name with schema (if provided)
        if (empty($this->schema)) {
            $this->_query .= "`$this->table`";
        } else {
            $this->_query .= "`$this->schema`.`$this->table`";
        }

        // Add WHERE clause if conditions exist
        if ($this->where) {
            $this->_query .= " WHERE " . $this->where;
        }

        return $this;
    }

    # BATCH INSERT/UPDATE OPERATION

    public function batchInsert($data)
    {
        return $this;
    }

    public function batchUpdate($data)
    {
        return $this;
    }

    public function insertOrUpdate($data)
    {
        return $this;
    }

    // Implement ResultInterface logic

    public function toArray()
    {
        $this->returnType = 'array';
        return $this;
    }

    public function toObject()
    {
        $this->returnType = 'object';
        return $this;
    }

    public function toJson()
    {
        $this->returnType = 'json';
        return $this;
    }

    # HELPER

    /**
     * A method of returning the static instance to allow access to the
     * instantiated object from within another class.
     * Inheriting this class would require reloading connection info.
     *
     * @uses $db = Database::getInstance();
     *
     * @return Database Returns the current instance.
     */
    public static function getInstance()
    {
        return self::$_instance;
    }

    /**
     * Converts the result data to the specified return type.
     *
     * @param mixed $data The data to be converted.
     * @return mixed The converted data.
     */
    protected function _returnResult($data)
    {
        if (empty($data))
            return $data;

        switch ($this->returnType) {
            case 'object':
                return json_decode(json_encode($data));
            case 'json':
                return json_encode($data);
            case 'array':
            default:
                return $data;
        }
    }

    /**
     * Enable or disable secure input.
     *
     * @param bool $secure Whether to enable or disable secure input.
     * @return $this
     */
    public function safeInput()
    {
        $this->_secureInput = true;
        return $this;
    }

    /**
     * Enable or disable secure output.
     *
     * @param bool $secure Whether to enable or disable secure output.
     * @return $this
     */
    public function safeOutput($enable = true)
    {
        $this->_secureOutput = $enable;
        return $this;
    }

    /**
     * Sanitize column data to ensure that only valid columns are used.
     *
     * @param string $table The table name.
     * @param array $data An associative array where keys represent column names and values represent corresponding data.
     * @return array The sanitized column data.
     * @throws \Exception If there's an error accessing the database or if the table does not exist.
     */
    protected function sanitizeColumn($data)
    {
        $table = $this->table;
        $param = null;

        // Get columns from table schema based on database driver
        switch ($this->driver) {
            case 'mysql':
                $sql = "DESCRIBE {$this->schema}.$table";
                break;
            case 'mssql':
                $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?";
                $param = [$table, $this->schema];
                break;
            case 'oracle':
            case 'oci':
                $sql = "SELECT COLUMN_NAME FROM ALL_TAB_COLUMNS WHERE TABLE_NAME = ? AND OWNER = ?";
                $param = [$table, $this->schema];
                break;
            case 'firebird':
            case 'fdb':
                $sql = "SELECT RDB\$FIELD_NAME FROM RDB\$RELATION_FIELDS WHERE RDB\$RELATION_NAME = ? AND RDB\$OWNER_NAME = ?";
                $param = [$table, $this->schema];
                break;
            default:
                throw new \Exception("Unsupported database driver '{$this->driver}'", 500);
        }

        $stmt = $this->pdo[$this->connectionName]->prepare($sql);
        $stmt->execute($param);
        $columns_table = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        // Filter $data array based on $columns_table
        $data = array_intersect_key($data, array_flip($columns_table));

        if ($this->_secureInput) {
            // Sanitize each value in the $data array
            $data = array_map(function ($value) {
                return !is_null($value) && $value !== '' ? $this->sanitize($value) : $value;
            }, $data);
        }

        return $data;
    }

    /**
     * Sanitize input data to prevent XSS and SQL injection attacks based on the secure flag.
     *
     * @param mixed $value The input data to sanitize.
     * @return mixed|null The sanitized input data or null if $value is null or empty.
     */
    protected function sanitize($value = null)
    {
        // Check if $value is not null or empty
        if (!isset($value) || is_null($value)) {
            return $value;
        }

        // Sanitize input based on data type
        switch (gettype($value)) {
            case 'string':
                return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');  // Apply XSS protection and trim
            case 'integer':
            case 'double':
                return $value;
            case 'boolean':
                return (bool) $value;
            case 'array':
                return array_map([$this, 'sanitize'], $value);
            default:
                // Handle unexpected data types (consider throwing an exception)
                throw new \InvalidArgumentException("Unsupported data type for sanitization: " . gettype($value));
        }
    }

    /**
     * Sanitizes the output data to prevent XSS attacks by applying htmlspecialchars
     * and trimming values. It handles single values, arrays, and multidimensional arrays.
     *
     * @param mixed $data The data to be sanitized.
     * @return mixed The sanitized data.
     */
    protected function _safeOutputSanitize($data)
    {
        if (!$this->_secureOutput) {
            return $data;
        }

        // Early return if data is null or empty
        if (is_null($data) || $data === '') {
            return $data;
        }

        return $this->sanitize($data);
    }

    # EAGER LOADER SECTION

    /**
     * Load relations and attach them to the main data efficiently.
     *
     * @param array $data The result for the main query/subquery.
     * @param array $relations The relations to be loaded.
     * @param string $connectionName The database connection name.
     * @param string $typeFetch The fetch type ('fetch' or 'get').
     */
    protected function _processEagerLoading(&$data, $relations, $connectionName, $typeFetch)
    {
        $data = $typeFetch == 'fetch' ? [$data] : $data;
        $connectionObj = $this->getInstance()->connection($connectionName);

        foreach ($relations as $alias => $eager) {

            $method = $eager['type']; // Get the type (get or fetch)
            $config = $eager['details']; // Get the configuration details

            $table = $config['table']; // Table name of the related data
            $fk_id = $config['foreign_key']; // Foreign key column in the related table
            $pk_id = $config['local_key']; // Local key column in the current table
            $callback = $config['callback']; // Optional callback for customizing the query

            // Extract all primary keys from the main result set
            $primaryKeys = array_values(array_unique(array_column($data, $pk_id), SORT_REGULAR));

            // Check if batch processing is needed
            if (count($primaryKeys) > $this->_parallelBatchSize) {
                $this->_processEagerLoadingInBatches($data, $primaryKeys, $table, $fk_id, $pk_id, $connectionName, $method, $alias, $callback);
            } else {
                // Set profiler
                $this->_setProfilerIdentifier('with_' . $alias);

                // Process directly without parallelism
                $relatedRecords = $this->_processEagerByChunk($primaryKeys, $callback, $connectionObj, $table, $fk_id);

                // Logic to process and attach data to main/subquery data
                $this->attachEagerLoadedData($method, $data, $relatedRecords, $alias, $fk_id, $pk_id);
            }
        }

        return $typeFetch == 'fetch' ? $data[0] : $data;
    }

    /**
     * Process eager loading for a large dataset in batches.
     *
     * This function splits the primary keys into chunks and fetches related
     * data for each chunk in separate queries.
     *
     * @param array $data The main result data.
     * @param array $primaryKeys The array of primary keys from the main data.
     * @param string $table The related table name.
     * @param string $fk_id The foreign key column in the related table.
     * @param string $pk_id The local key column in the current table.
     * @param string $connectionName The database connection name.
     * @param string $method The method type ('get' or 'fetch').
     * @param string $alias The alias for the relationship.
     * @param Closure|null $callback An optional callback to customize the query.
     */
    protected function _processEagerLoadingInBatches(&$data, $primaryKeys, $table, $fk_id, $pk_id, $connectionName, $method, $alias, \Closure $callback = null)
    {
        $connectionObj = $this->getInstance()->connection($connectionName);

        $chunks = array_chunk($primaryKeys, $this->_parallelBatchSize);

        // Initialize an empty array to store all related records
        $allRelatedRecords = [];

        // Check if parallel is enabled and total $chunks more than total that need parallel to handle
        if ($this->_parallelEnabled && count($chunks) > $this->_parallelWorker) {
            die('Parallel mode using proc_open is not ready.');
        } else {
            foreach ($chunks as $key => $chunk) {

                // Set profiler
                $this->_setProfilerIdentifier('with_' . $alias . '_' . ($key + 1));

                // Process chunk directly without parallelism
                $chunkRelatedRecords = $this->_processEagerByChunk($chunk, $callback, $connectionObj, $table, $fk_id);

                // Merge chunk results into the allRelatedRecords array
                $allRelatedRecords = array_merge($allRelatedRecords, $chunkRelatedRecords);
            }
        }

        // Attach related data to the main data
        $this->attachEagerLoadedData($method, $data, $allRelatedRecords, $alias, $fk_id, $pk_id);
    }

    /**
     * Process a chunk of primary keys and return related records.
     *
     * @param array $chunk The chunk of primary keys to process.
     * @param \Closure|null $callback An optional callback to customize the query.
     * @param Object $connectionObj The database connection object.
     * @param string $table The related table name.
     * @param string $fk_id The foreign key column in the related table.
     * @return array The related records fetched for the chunk.
     */
    protected function _processEagerByChunk($chunk, \Closure $callback = null, $connectionObj, $table, $fk_id)
    {
        $relatedRecordsQuery = $connectionObj->table($table)->whereIn($fk_id, $chunk);

        // Apply callback if provided for customization
        if ($callback instanceof \Closure) {
            $callback($relatedRecordsQuery);
        }

        $data = $relatedRecordsQuery->get();

        return $this->_safeOutputSanitize($data);
    }

    /**
     * Helper function to attach related data to the main result set.
     *
     * @param string $method The method type ('get' or 'fetch').
     * @param array $data The result for the main query/subquery.
     * @param array $relatedRecords The fetched related data.
     * @param string $alias The alias for the relationship.
     * @param string $fk_id The foreign key column in the related table.
     * @param string $pk_id The local key column in the current table.
     */
    protected function attachEagerLoadedData($method, &$data, &$relatedRecords, $alias, $fk_id, $pk_id)
    {
        // Organize related records by foreign key using an associative array
        $relatedMap = [];
        foreach ($relatedRecords as $relatedRow) {
            $relatedMap[$relatedRow[$fk_id]][] = $relatedRow;
        }

        // Attach related data to the main data set
        foreach ($data as &$row) {
            $row[$alias] = $method === 'fetch' && isset($relatedMap[$row[$pk_id]])
                ? $relatedMap[$row[$pk_id]][0]
                : ($relatedMap[$row[$pk_id]] ?? []);
        }
    }

    # PROFILER SECTION

    /**
     * Returns the internal profiler data.
     *
     * This function allows you to access the profiler information collected
     * during query execution, including method name, start and end times, query,
     * binds, execution time, and status.
     *
     * @return array The profiler data.
     */
    public function profiler()
    {
        return $this->_profiler;
    }

    /**
     * Sets the active profiler identifier.
     *
     * This function allows you to designate a specific profiler instance within the
     * `_profilers` array to be used for subsequent profiling operations. By
     * default, the profiler with the identifier 'main' is activated.
     *
     * Using this function enables you to manage and track data for multiple concurrent
     * profiling sessions within your application.
     *
     * @param string $identifier (optional) A unique identifier for the profiler to activate.
     *                             Defaults to 'main' if not provided.
     *
     * @return string The currently active profiler identifier.
     *
     */
    protected function _setProfilerIdentifier($identifier = 'main')
    {
        $this->_profilerActive = $identifier;
        return $this;
    }

    /**
     * Starts the profiler for a specific method.
     *
     * This function initializes the profiler data structure when a query building
     * method is called. It stores the method name, start time, and formatted start time.
     *
     * @param string $method The name of the method that initiated profiling.
     */
    protected function _startProfiler($method)
    {
        $startTime = microtime(true);

        // Get PHP version
        $this->_profiler['php_ver'] = phpversion();  // Simpler approach for version string

        // Get OS version
        if (function_exists('php_uname')) {
            $this->_profiler['os_ver'] = php_uname('s') . ' ' . php_uname('r');  // OS and release
        } else {
            // Handle cases where php_uname is not available
            $this->_profiler['os_ver'] = 'Unknown';
        }

        // Get database driver
        $this->_profiler['db_connection'] = $this->connectionName;
        $this->_profiler['db_driver'] = $this->driver ?? 'mysql';

        // Get database version 
        if (isset($this->pdo[$this->connectionName]) && $this->pdo[$this->connectionName] instanceof \PDO) {
            $this->_profiler['db_ver'] = $this->pdo[$this->connectionName]->getAttribute(\PDO::ATTR_SERVER_VERSION);
        } else {
            // Handle cases where no database connection exists
            $this->_profiler['db_ver'] = 'Unknown';
        }

        // Get database schema
        $this->_profiler['db_schema'] = $this->schema;

        $this->_profiler['profiling'][$this->_profilerActive] = [
            'method' => $method,
            'start' => $startTime,
            'end' => null,
            'start_time' => date('Y-m-d h:i A', (int) $startTime),
            'end_time' => null,
            'query' => null,
            'binds' => null,
            'execution_time' => null,
            'execution_status' => null,
            'memory_usage' => memory_get_usage(),
            'memory_usage_peak' => memory_get_peak_usage()
        ];
    }

    /**
     * Stops the profiler and calculates execution time and status.
     *
     * This function is called after query execution. It calculates the execution
     * time, formats it, and sets the execution status based on predefined thresholds.
     * It also updates the profiler data with end time, formatted end time, execution time, and status.
     * 
     */
    protected function _stopProfiler()
    {
        if (!isset($this->_profiler['profiling'][$this->_profilerActive])) {
            return;  // Profiler not started
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $this->_profiler['profiling'][$this->_profilerActive]['start'];

        $this->_profiler['profiling'][$this->_profilerActive]['memory_usage'] = $this->_formatBytes(memory_get_usage() - $this->_profiler['profiling'][$this->_profilerActive]['memory_usage'], 2);
        $this->_profiler['profiling'][$this->_profilerActive]['memory_usage_peak'] = $this->_formatBytes(memory_get_peak_usage() - $this->_profiler['profiling'][$this->_profilerActive]['memory_usage_peak'], 4);

        $this->_profiler['profiling'][$this->_profilerActive]['end'] = $endTime;
        $this->_profiler['profiling'][$this->_profilerActive]['end_time'] = date('Y-m-d h:i A', (int) $endTime);

        // Calculate and format execution time with milliseconds
        $milliseconds = round(($executionTime - floor($executionTime)) * 1000, 2);
        $totalSeconds = floor($executionTime);
        $seconds = $totalSeconds % 60;
        $minutes = floor(($totalSeconds % 3600) / 60);
        $hours = floor($totalSeconds / 3600);

        $formattedExecutionTime = '';
        if ($totalSeconds == 0) {
            $formattedExecutionTime = sprintf("%dms", $milliseconds);
        } else if ($hours > 0) {
            $formattedExecutionTime = sprintf("%dh %dm %ds %dms", $hours, $minutes, $seconds, $milliseconds);
        } else if ($minutes > 0) {
            $formattedExecutionTime = sprintf("%dm %ds %dms", $minutes, $seconds, $milliseconds);
        } else {
            $formattedExecutionTime = sprintf("%ds %dms", $seconds, $milliseconds);
        }

        $this->_profiler['profiling'][$this->_profilerActive]['execution_time'] = $formattedExecutionTime;
        $this->_profiler['stack_trace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10); // Capture starting stack trace

        // Set execution status based on predefined thresholds
        $this->_profiler['profiling'][$this->_profilerActive]['execution_status'] = ($executionTime >= 3.5) ? 'very slow' : (($executionTime >= 1.5 && $executionTime < 3.5) ? 'slow' : (($executionTime > 0.5 && $executionTime < 1.49) ? 'fast' : 'very fast'));

        // Removed unused profiler from being display & free resources
        unset(
            $milliseconds,
            $totalSeconds,
            $seconds,
            $minutes,
            $hours,
            $endTime,
            $executionTime,
            $formattedExecutionTime,
            $this->_profiler['profiling'][$this->_profilerActive]['start'],
            $this->_profiler['profiling'][$this->_profilerActive]['end'],
        );

        // Get profiler config and removed to free resources
        foreach ($this->_profilerShowConf as $config => $value) {
            if (!$value) {
                if (!in_array($config, ['php_ver', 'os_ver', 'db_driver', 'db_ver', 'stack_trace'])) {
                    unset($this->_profiler['profiling'][$this->_profilerActive][$config]);
                } else {
                    unset($this->_profiler[$config]);
                }
            }
        }
    }

    # HELPER SECTION

    /**
     * Begin a transaction.
     *
     * @return void
     */
    public function beginTransaction()
    {
        $this->pdo[$this->connectionName]->beginTransaction();
    }

    /**
     * Commit a transaction.
     *
     * @return void
     */
    public function commit()
    {
        $this->pdo[$this->connectionName]->commit();
    }

    /**
     * Rollback a transaction.
     *
     * @return void
     */
    public function rollback()
    {
        $this->pdo[$this->connectionName]->rollBack();
    }

    /**
     * Caches data with an expiration time.
     *
     * This method sets a cache entry identified by the provided key with optional expiration time.
     *
     * @param string $key The unique key identifying the cache entry.
     * @param int $expire The expiration time of the cache entry in seconds (default: 1800 seconds).
     * @return $this Returns the current object instance for method chaining.
     */
    public function cache($key, $expire = 1800)
    {
        $this->cacheFile = $key;
        $this->cacheFileExpired = $expire;

        return $this;
    }

    /**
     * Retrieves cached data for the given key.
     *
     * This method retrieves cached data identified by the provided key using the Cache class.
     *
     * @param string $key The unique key identifying the cached data.
     * @return mixed|null Returns the cached data if found; otherwise, returns null.
     */
    protected function _getCacheData($key)
    {
        $cache = new DatabaseCache();
        return $cache->get($key);
    }

    /**
     * Stores data in the cache with an optional expiration time.
     *
     * This method stores data identified by the provided key in the cache using the Cache class.
     *
     * @param string $key The unique key identifying the cache entry.
     * @param mixed $data The data to be stored in the cache.
     * @param int $expire The expiration time of the cache entry in seconds (default: 1800 seconds).
     * @return bool Returns true on success, false on failure.
     */
    protected function _setCacheData($key, $data, $expire = 1800)
    {
        $cache = new DatabaseCache();
        return $cache->set($key, $data, $expire);
    }

    /**
     * Validates a raw query string to prevent full SQL statement execution.
     *
     * This function ensures the provided string only contains allowed expressions. 
     * It throws an exception if the string contains keywords associated with full SQL statements like `SELECT`, `INSERT`,
     * etc.
     *
     * @param string|array $string The raw query string to validate.
     * @param string $message (Optional) The exception message to throw (defaults to "Not supported to run full query").
     * @throws \InvalidArgumentException If the string contains forbidden keywords.
     */
    protected function _forbidRawQuery($string, $message = 'Not supported to run full query')
    {
        $stringArr = is_string($string) ? [$string] : $string;

        $forbiddenKeywords = '/\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|TRUNCATE|REPLACE|GRANT|REVOKE|SHOW)\b/i';

        foreach ($stringArr as $str) {
            if (preg_match($forbiddenKeywords, $str)) {
                throw new \InvalidArgumentException($message);
            }
        }
    }

    /**
     * Binds parameters to a prepared statement.
     *
     * This function iterates through the provided bind values and binds them
     * to the prepared statement based on their data types. It supports positional
     * and named parameters, throwing exceptions for invalid key formats or query
     * structures. It also records the bound values for debugging purposes.
     *
     * @param \PDOStatement $stmt The prepared statement object.
     * @param array $binds An associative array of values to bind to the query.
     * @throws \PDOException If positional parameters use non-numeric keys or the query
     *                       format is invalid for placeholders.
     */
    protected function _bindParams(\PDOStatement $stmt, array $binds)
    {
        $query = $stmt->queryString;

        // Check if the query contains positional or named parameters
        $hasPositional = strpos($query, '?') !== false;
        $hasNamed = preg_match('/:\w+/', $query);

        // Reset
        $this->_binds = [];
        $this->_profiler['profiling'][$this->_profilerActive]['binds'] = [];

        foreach ($binds as $key => $value) {

            $type = \PDO::PARAM_STR; // Default type to string

            if (is_int($value)) {
                $type = \PDO::PARAM_INT;
            } else if (is_bool($value)) {
                $type = \PDO::PARAM_BOOL;
            }

            if ($hasPositional) {
                // Positional parameter
                if (is_numeric($key)) {
                    $stmt->bindValue($key + 1, $value, $type);
                } else {
                    throw new \PDOException('Positional parameters require numeric keys', 400);
                }
            } else if ($hasNamed) {
                // Named parameter
                $stmt->bindValue(':' . $key, $value, $type);
            } else {
                throw new \PDOException('Query must contain either positional (?) or named (:number, :param) placeholders', 400);
            }

            $this->_binds[] = $value;
            $this->_profiler['profiling'][$this->_profilerActive]['binds'][] = $value; // Record only the value
        }
    }

    /**
     * Generates the full query string by replacing placeholders with bound values.
     *
     * This function analyzes the query string and bound parameters to determine
     * if they use positional or named placeholders. It then iterates through
     * the binds and replaces the corresponding placeholders in the query with
     * quoted values. It also sets the full query string in the profiler data.
     *
     * @param string $query The SQL query string with placeholders.
     * @param array $binds (optional) An associative array of values to bind to the query.
     * @throws \PDOException If positional parameters use non-numeric keys or the query
     *                       format is invalid for placeholders.
     * @return $this This object for method chaining.
     */
    protected function _generateFullQuery($query, $binds = null)
    {
        if (!empty($binds)) {
            // Check if positional or named parameters are used
            $hasPositional = strpos($query, '?') !== false;
            $hasNamed = preg_match('/:\w+/', $query);

            foreach ($binds as $key => $value) {
                $quotedValue = is_numeric($value) ? $value : (is_string($value) ? $this->pdo[$this->connectionName]->quote($value, \PDO::PARAM_STR) : htmlspecialchars($value ?? ''));

                if ($hasPositional) {
                    // Positional parameter: replace with quoted value
                    if (is_numeric($key)) {
                        $query = preg_replace('/\?/', $quotedValue, $query, 1);
                    } else {
                        throw new \PDOException('Positional parameters require numeric keys', 400);
                    }
                } else if ($hasNamed) {
                    // Named parameter: replace with quoted value
                    $query = str_replace(':' . $key, $quotedValue, $query);
                } else {
                    throw new \PDOException('Query must contain either positional (?) or named (:number, :param) placeholders', 400);
                }
            }
        }

        $this->_profiler['profiling'][$this->_profilerActive]['full_query'] = $query;

        return $this;
    }

    /**
     * Expands asterisks (*) in the SELECT clause to include all table columns.
     *
     * This function handles two scenarios:
     * 1. SELECT * FROM table: Replaces * with all columns from the table.
     * 2. SELECT fields FROM table: Adds .* to tables not already specified in fields.
     * It uses regular expressions to identify the query pattern and replace the asterisk
     * accordingly.
     *
     * @param string $query The SQL query string.
     * @return string The modified query string with expanded columns.
     */
    protected function _expandAsterisksInQuery($query)
    {
        // Scenario 1: SELECT * FROM table
        if (preg_match('/SELECT\s+\*\s+FROM\s+([\w]+)/i', $query, $matches)) {
            $tables = [$matches[1]];

            // Add JOINed tables if present
            if (preg_match_all('/JOIN\s+([\w]+)\s+/i', $query, $joinMatches)) {
                $tables = array_merge($tables, $joinMatches[1]);
            }

            // Construct new SELECT part with table.*
            $selectPart = implode(', ', array_map(fn ($table) => "`$table`.*", $tables));
            $query = preg_replace('/SELECT\s+\*\s+FROM/i', "SELECT $selectPart FROM", $query, 1);
        } else if (preg_match('/SELECT\s+(.*)\s+FROM\s+([\w]+)/i', $query, $matches)) {
            // Scenario 2: SELECT fields FROM table
            $selectFields = $matches[1];
            $tables = [$matches[2]];

            // Add JOINed tables if present
            if (preg_match_all('/JOIN\s+([\w]+)\s+/i', $query, $joinMatches)) {
                $tables = array_merge($tables, $joinMatches[1]);
            }

            // Add .* only for tables not in select fields
            foreach ($tables as $table) {
                if (!preg_match("/\b$table\.\*/", $selectFields)) {
                    $selectFields .= ", $table.*";
                }
            }

            $query = preg_replace('/SELECT\s+(.*)\s+FROM/i', "SELECT $selectFields FROM", $query, 1);
        }

        return $query;
    }

    /**
     * Logs database errors and throws an exception.
     *
     * This function handles database errors by logging the error message and code,
     * and then throws a new exception with the details.
     *
     * @param \Exception $e The exception object representing the database error.
     * @param string $function (optional) The name of the function where the error occurred.
     * @param string $customMessage (optional) The description of error.
     * @throws \Exception A new exception with details from the database error.
     */
    protected function db_error_log(\Exception $e, $function = '', $customMessage = 'Error executing')
    {
        try {
            // Log the error message and code
            $this->_error = [
                'code' => (int) $e->getCode(),
                'message' => "$customMessage '{$function}()': " . $e->getMessage(),
            ];

            // log_message('error', "db->{$function}() : " . $e->getMessage());

            // Throw a new exception with formatted message and code
            throw new \Exception("$customMessage '{$function}()': " . $e->getMessage(), (int) $e->getCode());
        } catch (\Exception $e) {
            throw new \Exception('Database error occurred.', 0, $e);
        }
    }

    /**
     * Formats a number of bytes into a human-readable string with units.
     *
     * This function takes a number of bytes and converts it to a human-readable
     * format with appropriate units (B, KB, MB, GB, TB). It uses a specified
     * precision for rounding the value.
     *
     * @param int $bytes The number of bytes to format.
     * @param int $precision (optional) The number of decimal places to round to. Defaults to 2.
     * @return string The formatted string with units (e.g., 1023.45 KB).
     */
    protected function _formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0); // Ensure non-negative bytes
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); // Calculate power of 1024
        $pow = min($pow, count($units) - 1); // Limit to valid unit index

        $bytes /= (1 << (10 * $pow)); // Divide by appropriate factor

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
