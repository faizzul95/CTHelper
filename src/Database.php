<?php

namespace CT;

/**
 * Database Class
 *
 * @category  Database Access
 * @package   Database
 * @author    Mohd Fahmy Izwan Zulkhafri <faizzul14@gmail.com>
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link      -
 * @version   1.0.0
 */

class Database
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
     * @var string $driver The database driver being used (e.g., 'mysql', 'oracle', 'sqlite', etc.).
     */
    protected $driver = 'mysql';

    /**
     * @var array connections settings [profile_name=>[same_as_contruct_args]]
     */
    protected $connectionsSettings = array();

    /**
     * @var string the name of a default (main) pdo connection
     */
    public $connectionName = 'default';

    /**
     * @var string|null The table name.
     */
    protected $table;

    /**
     * @var string The fields to select.
     */
    protected $fields = '*';

    /**
     * @var int|null The limit for the query.
     */
    protected $limit;

    /**
     * @var array|null The order by columns and directions.
     */
    protected $orderBy;

    /**
     * @var array|null The group by columns.
     */
    protected $groupBy;

    /**
     * @var array The conditions for WHERE clause.
     */
    protected $where = [];

    /**
     * @var array The conditions for WHERE IN clause.
     */
    protected $whereIn = [];

    /**
     * @var array The conditions for WHERE NOT IN clause.
     */
    protected $whereNotIn = [];

    /**
     * @var array The conditions for WHERE BETWEEN clause.
     */
    protected $whereBetween = [];

    /**
     * @var array The conditions for OR WHERE clause.
     */
    protected $orWhere = [];

    /**
     * @var array The conditions for OR WHERE IN clause.
     */
    protected $orWhereIn = [];

    /**
     * @var array The conditions for OR WHERE NOT IN clause.
     */
    protected $orWhereNotIn = [];

    /**
     * @var array The conditions for OR WHERE BETWEEN clause.
     */
    protected $orWhereBetween = [];

    /**
     * @var array The conditions for HAVING clause.
     */
    protected $having = [];

    /**
     * @var array The join clauses.
     */
    protected $joins = [];

    /**
     * @var array The relations use for eager loading (N+1).
     */
    protected $relations = [];

    /**
     * @var string The previously executed SQL query
     */
    protected $_lastQuery;

    /**
     * @var array The previously executed error query
     */
    protected $_error;

    /**
     * @var bool The flag for sanitization.
     */
    protected $secure = true;

    /**
     * @var bool The flag for eager loader.
     */
    protected $isEagerMode = false;

    /**
     * Constructor.
     *
     * @param string $driver The database driver to be used (e.g., mysql, mssql, oracle, sqlite, firebird).
     * @param string|null $host The host of the database server.
     * @param string|null $username The username for the database connection.
     * @param string|null $password The password for the database connection.
     * @param string|null $database The name of the database.
     * @param int|null $port The port number of the database server.
     * @param string|null $charset The character set for the database connection (default is 'utf8mb4').
     * @param string|null $socket The socket name or path to the Unix socket for the connection.
     */
    public function __construct($driver = 'mysql', $host = null, $username = null, $password = null, $database = null, $port = null, $charset = 'utf8mb4', $socket = null)
    {
        if (!in_array($driver, ['mysql', 'mssql', 'oracle', 'sqlite', 'firebird'])) {
            throw new \InvalidArgumentException("Invalid database driver '{$driver}' provided.");
        }

        if (!isset($this->connectionsSettings['default'])) {
            $this->addConnection(
                'default',
                array(
                    'driver' => $driver,
                    'host' => $host,
                    'username' => $username,
                    'password' => $password,
                    'db' => $database,
                    'port' => $port,
                    'charset' => $charset,
                    'socket' => $socket
                )
            );
        }

        if (!isset($this->pdo[$this->connectionName]))
            $this->connect($this->connectionName);

        self::$_instance = $this;
    }

    /**
     * A method to connect to the database
     *
     * @param string|null $connectionName
     *
     * @throws \Exception
     * @return void
     */
    public function connect($connectionName = 'default')
    {
        try {
            if (!isset($this->connectionsSettings[$connectionName])) {
                throw new \PDOException('Connection profile not set', 404);
            }

            $pro = $this->connectionsSettings[$connectionName];

            // Set default charset to utf8mb4 if not specified
            $charset = isset($pro['charset']) ? $pro['charset'] : 'utf8mb4';

            // Define database driver
            $this->driver = isset($pro['driver']) ? strtolower($pro['driver']) : 'mysql';

            // Build DSN (Data Source Name) based on database driver
            switch ($this->driver) {
                case 'mysql':
                    $dsn = "mysql:host={$pro['host']};dbname={$pro['db']};charset={$charset}";
                    if (isset($pro['port'])) {
                        $dsn .= ";port={$pro['port']}";
                    }
                    if (isset($pro['socket'])) {
                        $dsn .= ";unix_socket={$pro['socket']}";
                    }
                    break;
                case 'mssql':
                    $dsn = "sqlsrv:Server={$pro['host']};Database={$pro['db']}";
                    if (isset($pro['port'])) {
                        $dsn .= ";port={$pro['port']}";
                    }
                    break;
                case 'oracle':
                    $dsn = "oci:dbname={$pro['host']}/{$pro['db']}";
                    break;
                case 'sqlite':
                    $dsn = "sqlite:{$pro['host']}";
                    break;
                case 'firebird':
                    $dsn = "firebird:dbname={$pro['host']}";
                    break;
                default:
                    throw new \PDOException('Unsupported database driver', 404);
            }

            // Connection options
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ];

            // Specific options for MySQL
            if ($this->driver === 'mysql') {
                $options[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES {$charset}";
            }

            $pdo = new \PDO($dsn, $pro['username'], $pro['password'], $options);
            $this->pdo[$connectionName] = $pdo;
        } catch (\PDOException $e) {
            $this->_error = ['code' => (int) $e->getCode(), 'message' => 'Error connection: ' . $e->getMessage()];
            throw new \Exception('Connect Error: ' . $e->getCode() . ': ' . $e->getMessage(), $e->getCode());
        }
    }

    /**
     * Create & store a new PDO instance
     *
     * @param string $name
     * @param array  $params
     *
     * @return $this
     */
    public function addConnection($name, array $params)
    {
        $this->connectionsSettings[$name] = array();
        foreach (array('driver', 'host', 'username', 'password', 'db', 'port', 'socket', 'charset') as $k) {
            $prm = isset($params[$k]) ? $params[$k] : null;

            if ($k == 'host') {
                if (is_object($prm)) {
                    $this->pdo[$name] = $prm;
                }

                if (!is_string($prm)) {
                    $prm = null;
                }
            }

            $this->connectionsSettings[$name][$k] = $prm;
        }

        return $this;
    }

    /**
     * Set the connection name to use in the next query
     *
     * @param string $name
     *
     * @return $this
     * @throws \Exception
     */
    public function connection($name)
    {
        if (!isset($this->connectionsSettings[$name]))
            throw new \Exception('Connection ' . $name . ' was not added.');

        $this->connectionName = $name;
        return $this;
    }

    /**
     * A method to disconnect from the database
     *
     * @param string $connection Connection name to disconnect
     * @param bool $remove Flag indicating whether to remove connection settings
     *
     * @return void
     */
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
        if ($remove && isset($this->connectionsSettings[$connection])) {
            unset($this->connectionsSettings[$connection]);
        }
    }

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
     * Execute a raw SQL query.
     *
     * @param string $query      The SQL query to execute.
     * @param array|null $bindParams Optional. An array of parameters to bind to the SQL statement.
     * @param string $fetch      Optional. The fetch mode for retrieving results. Default is 'get'.
     *                           Possible values: 'get' to fetch a single row, 'all' to fetch all rows.
     *
     * @return array|mixed|null Returns the fetched row(s) from the query.
     * @throws \Exception If an error occurs during query execution.
     */
    public function rawQuery($query, $bindParams = null, $fetch = 'get')
    {
        try {
            $stmt = $this->pdo[$this->connectionName]->prepare($query);

            if ($bindParams !== null) {
                // Bind parameters based on their type
                if (is_array($bindParams)) {
                    // Determine if bindParams is positional or named
                    $isPositional = array_values($bindParams) === $bindParams;

                    if ($isPositional) {
                        $stmt->execute($bindParams);
                    } else {
                        foreach ($bindParams as $param => $value) {
                            $stmt->bindValue(':' . $param, $value);
                        }
                        $stmt->execute();
                    }
                } else {
                    throw new \PDOException('Bind parameters must be provided as an array', 400);
                }
            } else {
                $stmt->execute();
            }

            // Store the last executed SQL query
            if (!$this->isEagerMode)
                $this->_lastQuery = $query;

            // Fetch results based on the fetch parameter
            $result = ($fetch === 'get') ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result;
        } catch (\PDOException $e) {
            $this->_error = ['code' => (int) $e->getCode(), 'message' => 'Error executing raw SQL query: ' . $e->getMessage()];
            throw new \Exception('Error executing raw SQL query: ' . $e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * Inserts data into a specified table using a dynamically generated SQL INSERT statement.
     *
     * @param string $table The name of the table to insert data into.
     * @param array $data An associative array where keys represent column names and values represent corresponding data to be inserted.
     *
     * @return array Returns an array containing information about the insertion status:
     *               - 'code' (int): The status code indicating the success or failure of the insertion (201 for success, 422 for failure).
     *               - 'id' (mixed): The last inserted ID if the insertion was successful; otherwise, null.
     *               - 'data' (array): The sanitized data that was attempted to be inserted.
     *               - 'message' (string): A status message indicating the outcome of the insertion operation.
     */
    public function insert($table, $data)
    {
        if ($this->isMultiDimensionalArray($data)) {
            return $this->insertBatch($table, $data);
        }

        try {
            $this->beginTransaction(); // Begin transaction

            $sanitize = $this->sanitizeColumn($table, $data);

            // Build the SQL INSERT statement
            $sql = $this->_lastQuery = $this->buildInsertQuery($table, $sanitize);

            // Prepare the SQL statement
            $stmt = $this->pdo[$this->connectionName]->prepare($sql);

            // Bind each value to its placeholder
            foreach ($sanitize as $key => $value) {
                $stmt->bindValue(":$key", $this->sanitize($value));
            }

            // Execute the statement
            $success = $stmt->execute();

            // Get the last inserted ID
            $lastInsertId = $success ? $this->pdo[$this->connectionName]->lastInsertId() : null;

            $this->commit(); // Commit transaction

            // Return information about the insert operation
            $response = [
                'code' => $success ? 201 : 422,
                'id' => $lastInsertId,
                'message' => $success ? 'Data inserted successfully' : 'Failed to insert data',
                'data' => $sanitize,
            ];

            // Reset the query builder's state
            $this->reset();

            return $response;
        } catch (\PDOException $e) {
            $this->rollback(); // Rollback transaction
            $this->_error = ['code' => (int) $e->getCode(), 'message' => 'Error executing insert query: ' . $e->getMessage()];
            throw new \Exception('Error executing insert query: ' . $e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * Insert multiple rows into the database table.
     *
     * @param string $table The name of the table to insert data into.
     * @param array $data An array of data to insert. Each element represents a row to insert.
     * @return array An array containing information about the insert operation.
     * @throws \Exception If an error occurs during the insert operation.
     */
    public function insertBatch($table, $data)
    {
        // Arrays to store inserted IDs, successful data, and unsuccessful data
        $insertedIds = [];
        $successfulData = [];
        $unsuccessfulData = [];

        try {
            $this->beginTransaction(); // Begin transaction

            foreach ($data as $row) {
                // Sanitize the data for the current row
                $sanitize = $this->sanitizeColumn($table, $row);

                // Build the SQL INSERT statement
                $sql = $this->_lastQuery = $this->buildInsertQuery($table, $sanitize);

                // Prepare the SQL statement
                $stmt = $this->pdo[$this->connectionName]->prepare($sql);

                // Bind each value to its placeholder
                foreach ($sanitize as $key => $value) {
                    $stmt->bindValue(":$key", $this->sanitize($value));
                }

                // Execute the statement
                $success = $stmt->execute();

                // If the execution was successful
                if ($success) {
                    // Get the last inserted ID
                    $lastInsertId = $this->pdo[$this->connectionName]->lastInsertId();
                    // Store the inserted ID
                    $insertedIds[] = $lastInsertId;
                    // Store the sanitized data
                    $successfulData[] = $sanitize;
                } else {
                    // Store the unsuccessful data
                    $unsuccessfulData[] = $sanitize;
                }
            }

            $this->commit(); // Commit transaction

            // Return information about the insert operation
            $response = [
                'code' => 201,
                'message' => 'Data inserted successfully',
                'id' => $insertedIds,
                'successful_data' => $successfulData,
                'unsuccessful_data' => $unsuccessfulData,
            ];

            // Reset the query builder's state
            $this->reset();

            return $response;
        } catch (\PDOException $e) {
            $this->rollback(); // Rollback transaction
            $this->_error = ['code' => (int) $e->getCode(), 'message' => 'Error executing insert query: ' . $e->getMessage()];
            // Throw an exception if an error occurs during the insert operation
            throw new \Exception('Error executing insert query: ' . $e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * Updates data in a specified table using a dynamically generated SQL UPDATE statement.
     *
     * @param string $table The name of the table to update data in.
     * @param array $data An associative array where keys represent column names and values represent corresponding data to be updated.
     * @param string|array $condition (Optional) The condition to apply in the WHERE clause.
     *
     * @return array Returns an array containing information about the update status:
     *               - 'code' (int): The status code indicating the success or failure of the update (200 for success, 422 for failure).
     *               - 'affected_rows' (int): The number of rows affected by the update operation.
     *               - 'message' (string): A status message indicating the outcome of the update operation.
     *               - 'data' (array): The sanitized data that was attempted to be updated.
     */
    public function update($table, $data, $condition = NULL)
    {
        try {
            if ($this->isMultiDimensionalArray($data)) {
                throw new \PDOException('Batch updates are not supported using this function.', 422);
            }

            $this->beginTransaction(); // Begin transaction

            // Sanitize the column names
            $sanitize = $this->sanitizeColumn($table, $data);

            // Build the SQL UPDATE statement
            $sql = $this->_lastQuery = $this->buildUpdateQuery($table, $sanitize, $condition);

            // Prepare the SQL statement
            $stmt = $this->pdo[$this->connectionName]->prepare($sql);

            // Bind each value to its placeholder
            foreach ($sanitize as $key => $value) {
                $stmt->bindValue(":$key", $this->sanitize($value));
            }

            // Execute the statement
            $success = $stmt->execute();

            // Get the number of affected rows
            $affectedRows = $stmt->rowCount();

            $this->commit(); // Commit transaction

            // Return information about the update operation
            $response = [
                'code' => $success ? 200 : 422,
                'affected_rows' => $affectedRows,
                'message' => $success ? 'Data updated successfully' : 'Failed to update data',
                'data' => $sanitize,
            ];

            // Reset the query builder's state
            $this->reset();

            return $response;
        } catch (\PDOException $e) {
            $this->rollback(); // Rollback transaction
            $this->_error = ['code' => (int) $e->getCode(), 'message' => 'Error executing update query: ' . $e->getMessage()];
            throw new \Exception('Error executing update query: ' . $e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * Deletes data from a specified table based on the provided condition and returns the deleted data.
     *
     * @param string $table The name of the table to delete data from.
     * @param string $condition (Optional) The condition to apply in the WHERE clause.
     *
     * @return array Returns an array containing information about the deletion status and the deleted data:
     *               - 'code' (int): The status code indicating the success or failure of the deletion (200 for success, 422 for failure).
     *               - 'affected_rows' (int): The number of rows affected by the delete operation.
     *               - 'message' (string): A status message indicating the outcome of the deletion operation.
     *               - 'data' (array): The data that was deleted.
     */
    public function delete($table, $condition = [])
    {
        try {
            if (!is_array($condition) || empty($condition)) {
                throw new \Exception('The condition must be an array and cannot be empty.', 422);
            }

            $this->beginTransaction(); // Begin transaction

            // Build the SQL SELECT statement to fetch data before deletion
            $selectSql = $this->table($table)->where($condition)->getFullSql();

            // Prepare and execute the SELECT statement
            $selectStmt = $this->pdo[$this->connectionName]->prepare($selectSql);
            $selectStmt->execute();

            // Fetch the data before deletion
            $deletedData = $selectStmt->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($deletedData)) {
                // Build the SQL DELETE statement
                $deleteSql = $this->_lastQuery = $this->buildDeleteQuery($table, $condition);

                // Prepare the SQL DELETE statement
                $deleteStmt = $this->pdo[$this->connectionName]->prepare($deleteSql);

                // Execute the SQL DELETE statement
                $success = $deleteStmt->execute();

                // Get the number of affected rows
                $affectedRows = $deleteStmt->rowCount();

                $this->commit(); // Commit transaction

                // Return information about the deletion operation
                $response = [
                    'code' => $success ? 200 : 422,
                    'affected_rows' => $affectedRows,
                    'message' => $success ? 'Data deleted successfully' : 'Failed to delete data',
                    'data' => $deletedData,
                ];
            } else {
                $response = [
                    'code' => 400,
                    'message' => 'No data has been deleted',
                    'data' => [],
                ];
            }

            // Reset the query builder's state
            $this->reset();

            return $response;
        } catch (\PDOException $e) {
            $this->rollback(); // Rollback transaction
            $this->_error = ['code' => (int) $e->getCode(), 'message' => 'Error executing delete query: ' . $e->getMessage()];
            throw new \Exception('Error executing delete query: ' . $e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * Set the table name.
     *
     * @param string $table The table name.
     * @return $this
     * @throws \Exception if the table does not exist or if there's an error accessing the database.
     */
    public function table($table)
    {
        try {
            // Check if the table exists based on the database driver
            switch ($this->driver) {
                case 'mysql':
                    $query = "SHOW TABLES LIKE '$table'";
                    break;
                case 'mssql':
                    $query = "IF EXISTS (SELECT * FROM sysobjects WHERE name = '$table' AND xtype = 'U') SELECT 1";
                    break;
                case 'oracle':
                    $query = "SELECT table_name FROM user_tables WHERE table_name = '$table'";
                    break;
                case 'firebird':
                    $query = "SELECT RDB\$RELATION_NAME FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = '$table'";
                    break;
                case 'sqlite':
                    $query = "SELECT name FROM sqlite_master WHERE type='table' AND name='$table'";
                    break;
                default:
                    throw new \Exception("Unsupported database driver '{$this->driver}'", 500);
            }

            // Execute the query to check table existence
            $stmt = $this->pdo[$this->connectionName]->query($query);

            // Check if the table exists
            if ($stmt->fetchColumn() === false) {
                throw new \Exception("Table '$table' does not exist", 404);
            }

            // Assign the table name
            $this->table = trim($table);
            return $this;
        } catch (\PDOException $e) {
            // Handle PDO exception
            $this->_error = ['code' => (int) $e->getCode(), 'message' => 'Error accessing database: ' . $e->getMessage()];
            throw new \Exception('Error accessing database: ' . $e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * Reset all parameters.
     *
     * @return $this
     */
    public function reset()
    {
        $this->driver = 'mysql';
        $this->table = null;
        $this->fields = '*';
        $this->limit = null;
        $this->orderBy = null;
        $this->groupBy = null;
        $this->where = [];
        $this->whereIn = [];
        $this->whereNotIn = [];
        $this->whereBetween = [];
        $this->orWhere = [];
        $this->orWhereIn = [];
        $this->orWhereNotIn = [];
        $this->orWhereBetween = [];
        $this->having = [];
        $this->joins = [];
        $this->relations = [];
        $this->connectionName = 'default';
        $this->_error = [];
        $this->secure = true;
        $this->isEagerMode = false;
        return $this;
    }

    /**
     * Set the fields to select.
     *
     * @param string|array $fields The fields to select. Can be either a string or an array.
     * @return $this
     */
    public function select($fields)
    {
        $this->fields = is_array($fields) ? implode(', ', $fields) : $fields;
        return $this;
    }

    /**
     * Set the limit for the query.
     *
     * @param int $limit The limit for the query.
     * @return $this
     * @throws \InvalidArgumentException If the $limit parameter is not an integer.
     */
    public function limit($limit)
    {
        if (!is_int($limit)) {
            throw new \InvalidArgumentException('Limit must be an integer.');
        }

        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the order by columns and directions.
     *
     * @param string|array $columns The order by columns.
     * @param string $direction The order direction.
     * @return $this
     * @throws \InvalidArgumentException If the $direction parameter is not "ASC" or "DESC".
     */
    public function orderBy($columns, string $direction = 'ASC')
    {
        // Check if direction is valid
        if (!in_array(strtoupper($direction), ['ASC', 'DESC'])) {
            throw new \InvalidArgumentException('Order direction must be "ASC" or "DESC".');
        }

        $this->orderBy = is_array($columns) ? $columns : [[$columns, $direction]];
        return $this;
    }

    /**
     * Set the group by columns.
     *
     * @param string|array $columns The group by columns.
     * @return $this
     */
    public function groupBy($columns)
    {
        $this->groupBy = is_array($columns) ? $columns : [$columns];
        return $this;
    }

    /**
     * Add a condition for WHERE clause.
     *
     * @param string|array $column The column name or an array of column names.
     * @param mixed $value The value.
     * @param string|null $param The comparison operator.
     * @return $this
     */
    public function where($column, $value = null, $param = null)
    {
        if (is_array($column)) {
            foreach ($column as $col => $val) {
                $this->where[] = [$col, '=', $this->sanitize($val)];
            }
        } else {
            // If $param is provided, use it for the comparison operator, otherwise use '='
            $operator = $param !== null ? $param : '=';

            // Add the condition to the where array
            $this->where[] = [$column, $operator, $this->sanitize($value)];
        }
        return $this;
    }

    /**
     * Add a condition for WHERE IN clause.
     *
     * @param string $column The column name.
     * @param array $values The values.
     * @return $this
     */
    public function whereIn($column, $values)
    {
        $this->whereIn[] = [$column, $this->sanitize($values)];
        return $this;
    }

    /**
     * Add a condition for WHERE NOT IN clause.
     *
     * @param string $column The column name.
     * @param array $values The values.
     * @return $this
     */
    public function whereNotIn($column, $values)
    {
        $this->whereNotIn[] = [$column, $this->sanitize($values)];
        return $this;
    }

    /**
     * Add a condition for WHERE BETWEEN clause.
     *
     * @param string $column The column name.
     * @param mixed $start The start value.
     * @param mixed $end The end value.
     * @return $this
     */
    public function whereBetween($column, $start, $end)
    {
        $this->whereBetween[] = [$column, $this->sanitize($start), $this->sanitize($end)];
        return $this;
    }

    /**
     * Add a condition for OR WHERE clause.
     *
     * @param string $column The column name.
     * @param mixed $value The value.
     * @param string|null $param The comparison operator.
     * @return $this
     */
    public function orWhere($column, $value, $param = null)
    {
        if (is_array($column)) {
            foreach ($column as $col => $val) {
                $this->orWhere[] = [$col, '=', $this->sanitize($val)];
            }
        } else {
            // If $param is provided, use it for the comparison operator, otherwise use '='
            $operator = $param !== null ? $param : '=';

            // Add the condition to the where array
            $this->orWhere[] = [$column, $operator, $this->sanitize($value)];
        }
        return $this;
    }

    /**
     * Add a condition for OR WHERE IN clause.
     *
     * @param string $column The column name.
     * @param array $values The values.
     * @return $this
     */
    public function orWhereIn($column, $values)
    {
        $this->orWhereIn[] = [$column, $this->sanitize($values)];
        return $this;
    }

    /**
     * Add a condition for OR WHERE NOT IN clause.
     *
     * @param string $column The column name.
     * @param array $values The values.
     * @return $this
     */
    public function orWhereNotIn($column, $values)
    {
        $this->orWhereNotIn[] = [$column, $this->sanitize($values)];
        return $this;
    }

    /**
     * Add a condition for OR WHERE BETWEEN clause.
     *
     * @param string $column The column name.
     * @param mixed $start The start value.
     * @param mixed $end The end value.
     * @return $this
     */
    public function orWhereBetween($column, $start, $end)
    {
        $this->orWhereBetween[] = [$column, $this->sanitize($start), $this->sanitize($end)];
        return $this;
    }

    /**
     * Add a HAVING clause to the query.
     *
     * @param string $column The column name.
     * @param mixed $value The value.
     * @param string|null $param The comparison operator.
     * @return $this
     */
    public function having($column, $value = null, $param = null)
    {
        if (is_array($column)) {
            foreach ($column as $col => $val) {
                $this->having[] = [$col, '=', $this->sanitize($val)];
            }
        } else {
            // If $param is provided, use it for the comparison operator, otherwise use '='
            $operator = $param !== null ? $param : '=';

            // Add the condition to the having array
            $this->having[] = [$column, $operator, $this->sanitize($value)];
        }
        return $this;
    }

    /**
     * Add a join clause to the query.
     *
     * @param string $table The table to join.
     * @param string $condition The join condition.
     * @param string $joinType The type of join (e.g., INNER, LEFT, RIGHT, OUTER, LEFT OUTER, RIGHT OUTER, NATURAL).
     * @return $this
     * @throws \InvalidArgumentException If the $joinType parameter is not a valid join type.
     */
    public function join($table, $condition, $joinType = 'LEFT')
    {
        // List of valid join types
        $validJoinTypes = ['INNER', 'LEFT', 'RIGHT', 'OUTER', 'LEFT OUTER', 'RIGHT OUTER', 'NATURAL'];

        // Check if joinType is valid
        if (!in_array(strtoupper($joinType), $validJoinTypes)) {
            throw new \InvalidArgumentException('Invalid join type. Allowed values are INNER, LEFT, RIGHT, OUTER, LEFT OUTER, RIGHT OUTER, NATURAL.');
        }

        $this->joins[] = [strtoupper($joinType), $table, $condition];
        return $this;
    }

    /**
     * Specify eager loading for a relationship using 'get' type.
     *
     * @param string $alias The alias for the relationship.
     * @param string $table The related table name.
     * @param string $fk_id The foreign key column in the related table.
     * @param string $pk_id The primary key column in the current table.
     * @param Closure|null $callback An optional callback to customize the eager load.
     * @return $this
     */
    public function with($alias, $table, $fk_id, $pk_id, \Closure $callback = null)
    {
        $this->relations[$alias] = ['type' => 'get', 'details' => compact('table', 'fk_id', 'pk_id', 'callback')];
        return $this;
    }

    /**
     * Specify eager loading for a relationship using 'fetch' type.
     *
     * @param string $alias The alias for the relationship.
     * @param string $table The related table name.
     * @param string $fk_id The foreign key column in the related table.
     * @param string $pk_id The primary key column in the current table.
     * @param Closure|null $callback An optional callback to customize the eager load.
     * @return $this
     */
    public function withOne($alias, $table, $fk_id, $pk_id, \Closure $callback = null)
    {
        $this->relations[$alias] = ['type' => 'fetch', 'details' => compact('table', 'fk_id', 'pk_id', 'callback')];
        return $this;
    }

    /**
     * Retrieve the result of the query with pagination.
     *
     * @param int $currentPage The current page number.
     * @param int $limit The limit per page.
     * @return array The paginated query result.
     * @throws \Exception If there's an error accessing the database or if the table does not exist.
     */
    public function paginate($currentPage = 1, $limit = 10)
    {
        // Calculate offset
        $offset = ($currentPage - 1) * $limit;

        // Build the main query
        $query = $this->buildQuery();

        try {
            // Create a separate query to get total count
            $sqlTotal = 'SELECT COUNT(*) count ' . substr($query['sql'], strpos($query['sql'], 'FROM'));

            // Execute the total count query
            $stmtTotal = $this->pdo[$this->connectionName]->prepare($sqlTotal);
            $stmtTotal->execute($query['bindings']);
            $totalResult = $stmtTotal->fetch(\PDO::FETCH_ASSOC);

            // Get total count
            $total = $totalResult['count'];

            // Calculate total pages
            $totalPages = ceil($total / $limit);

            // Add LIMIT and OFFSET clauses to the main query
            $query['sql'] .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;

            // Execute the main query
            $stmt = $this->pdo[$this->connectionName]->prepare($query['sql']);
            $stmt->execute($query['bindings']);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Calculate next page
            $nextPage = ($currentPage < $totalPages) ? $currentPage + 1 : null;

            // Calculate previous page
            $previousPage = ($currentPage > 1) ? $currentPage - 1 : null;

            // Adjust array keys to start from previous count
            $startIndex = ($currentPage - 1) * $limit;
            $result = array_combine(range($startIndex, $startIndex + count($result) - 1), $result);

            // Store the last executed SQL query
            if (!$this->isEagerMode)
                $this->_lastQuery = $query['sql'];

            // Save connection name and relations temporarily
            $_temp_connection = $this->connectionName;
            $_temp_relations = $this->relations;

            // Reset the query builder's state
            $this->reset();

            // Eager loading
            if (!empty($_temp_relations)) {
                $this->isEagerMode = true;
                $eager = Database::$_instance->connection($_temp_connection);
                foreach ($_temp_relations as $alias => $relation) {
                    $type = $relation['type'] === 'get' ? 'get' : 'fetch';
                    $this->loadRelation($alias, $relation['details'], $result, $eager, $type);
                }
            }

            return [
                'data' => $result,
                'total' => $total,
                'current_page' => $currentPage,
                'next_page' => $nextPage,
                'previous_page' => $previousPage,
                'last_page' => $totalPages
            ];
        } catch (\PDOException $e) {
            // Handle PDO exception
            $this->_error = ['code' => (int) $e->getCode(), 'message' => 'Error accessing database: ' . $e->getMessage()];
            throw new \Exception('Error accessing database: ' . $e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * Retrieve the result of the query.
     *
     * @return array The query result.
     */
    public function get()
    {
        // Build the main query
        $query = $this->buildQuery();

        // Prepare and execute the main SQL statement
        $stmt = $this->pdo[$this->connectionName]->prepare($query['sql']);
        $stmt->execute($query['bindings']);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Store the last executed SQL query
        if (!$this->isEagerMode)
            $this->_lastQuery = $query['sql'];

        // Save connection name and relations temporarily
        $_temp_connection = $this->connectionName;
        $_temp_relations = $this->relations;

        // Reset the query builder's state
        $this->reset();

        // Eager loading
        if (!empty($_temp_relations)) {
            $this->isEagerMode = true;
            $eager = Database::$_instance->connection($_temp_connection);
            foreach ($_temp_relations as $alias => $relation) {
                $type = $relation['type'] === 'get' ? 'get' : 'fetch';
                $this->loadRelation($alias, $relation['details'], $result, $eager, $type);
            }
        }

        return $result;
    }

    /**
     * Retrieve a single row result of the query.
     *
     * @return mixed The single row result.
     */
    public function fetch()
    {
        // Build the main query
        $query = $this->buildQuery();

        // Prepare and execute the main SQL statement
        $stmt = $this->pdo[$this->connectionName]->prepare($query['sql']);
        $stmt->execute($query['bindings']);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Store the last executed SQL query
        if (!$this->isEagerMode)
            $this->_lastQuery = $query['sql'];

        // Save connection name and relations temporarily
        $_temp_connection = $this->connectionName;
        $_temp_relations = $this->relations;

        // Reset the query builder's state
        $this->reset();

        // Eager loading
        if (!empty($_temp_relations)) {
            $this->isEagerMode = true;
            $eager = Database::$_instance->connection($_temp_connection);
            foreach ($_temp_relations as $alias => $relation) {
                $type = $relation['type'] === 'get' ? 'get' : 'fetch';
                $this->loadRelation($alias, $relation['details'], $result, $eager, $type);
            }

            if ($this->isMultiDimensionalArray($result) && count($result) === 1) {
                $result = $result[0] ?? [];
            }
        }

        return $result;
    }

    /**
     * Retrieve the total count of rows returned by the query.
     *
     * @return int The total count of rows.
     */
    public function count()
    {
        // Build the main query to count rows
        $query = $this->buildQuery();

        // Adjust the query to perform a count
        $query['sql'] = "SELECT COUNT(*) count FROM (" . $query['sql'] . ") as count_query";

        // Prepare and execute the main SQL statement
        $stmt = $this->pdo[$this->connectionName]->prepare($query['sql']);
        $stmt->execute($query['bindings']);

        // Fetch the count of rows
        $count = $stmt->fetchColumn();

        // Store the last executed SQL query
        if (!$this->isEagerMode)
            $this->_lastQuery = $query['sql'];

        // Reset the query builder's state
        $this->reset();

        return $count;
    }

    /**
     * Load a single relation and attach it to the main result.
     *
     * @param string $alias The alias for the relation.
     * @param array $relation The relation details.
     * @param mixed $result The main query result (array or single data).
     * @param Database $eager The eager loading database instance.
     * @param string $type The type of relation ('get' or 'fetch').
     * @return mixed The main query result with attached related records.
     */
    private function loadRelation($alias, $relation, &$result, $eager, $type = 'get')
    {
        // Check if $result is not empty
        if (empty($result)) {
            return $result;
        }

        $table = $relation['table'];
        $fk_id = $relation['fk_id'];
        $pk_id = $relation['pk_id'];
        $callback = $relation['callback'];

        // Check if $result is a multidimensional array
        $isMultiDimensional = $this->isMultiDimensionalArray($result);

        // If $result is not an array, convert it into an array with one item
        if (!$isMultiDimensional || !is_array($result)) {
            $result = [$result];
        }

        // Extract all primary keys from the main result
        $primaryKeys = array_column($result, $pk_id);

        // Fetch related records using existing database instance
        $relatedRecordsQuery = $eager
            ->table($table)
            ->whereIn($fk_id, $primaryKeys);

        // Apply callback if provided
        if ($callback instanceof \Closure) {
            $callback($relatedRecordsQuery);
        }

        // Execute the related records query
        if ($type === 'get') {
            $relatedRecords = $relatedRecordsQuery->get();

            // Group related records by foreign key
            $groupedRelatedRecords = [];
            foreach ($relatedRecords as $key => $relatedRecord) {
                if (isset($relatedRecord[$fk_id]))
                    $groupedRelatedRecords[$relatedRecord[$fk_id]][] = $relatedRecord;
                else
                    $groupedRelatedRecords[0][] = $relatedRecord;
            }

            // Attach related records to the main result
            foreach ($result as &$item) {
                if ($this->isMultiDimensionalArray($groupedRelatedRecords)) {
                    foreach ($groupedRelatedRecords as $group) {
                        $item[$alias] = $group ?? [];
                    }
                } else {
                    $pkValue = $item[$pk_id];
                    $item[$alias] = $groupedRelatedRecords[$pkValue] ?? [];
                }
            }
        } else {
            $relatedRecords = $relatedRecordsQuery->fetch();
            if (count($result) === 1) {
                $result[0][$alias] = $relatedRecords ?? [];
            } else {
                $result[$alias] = $relatedRecords ?? [];
            }
        }

        // Reset the query builder's state
        $this->reset();

        return $result;
    }

    /**
     * Build the SQL SELECT statement query string and bindings array based on the set parameters.
     *
     * @return array An array containing the SQL query string and the bindings array.
     */
    protected function buildQuery()
    {
        if (empty($this->table)) {
            throw new \Exception('No table selected', 400);
        }

        $sql = 'SELECT ' . $this->fields . ' FROM ' . $this->table;
        $bindings = [];

        // JOIN clauses
        if (!empty($this->joins)) {
            $sql .= implode('', array_map(function ($join) {
                return ' ' . $join[0] . ' JOIN ' . $join[1] . ' ON ' . $join[2];
            }, $this->joins));
        }

        // WHERE clause
        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' AND ', array_map(function ($condition) use (&$bindings) {
                $bindings[] = $condition[2];
                return $condition[0] . ' ' . $condition[1] . ' ?';
            }, $this->where));
        }

        // WHERE IN clause
        if (!empty($this->whereIn)) {
            $sql .= $this->containsWhere($sql, 'AND');
            $sql .= implode(' AND ', array_map(function ($condition) use (&$bindings) {
                if (is_array($condition[1])) {
                    $values = implode(', ', array_fill(0, count($condition[1]), '?'));
                    $bindings = array_merge($bindings, $condition[1]);
                    return $condition[0] . ' IN (' . $values . ')';
                } else if ($this->isSqlSubquery($condition[1])) {
                    // Check if $condition[1] starts and ends with parentheses
                    $isParenthesized = (substr($condition[1], 0, 1) === '(' && substr($condition[1], -1) === ')');
                    // Concatenate the condition based on whether parentheses are present or not
                    return $condition[0] . ' IN ' . ($isParenthesized ? $condition[1] : '(' . $condition[1] . ')');
                } else {
                    return $condition;
                }
            }, $this->whereIn));
        }

        // WHERE NOT IN clause
        if (!empty($this->whereNotIn)) {
            $sql .= $this->containsWhere($sql, 'AND');
            $sql .= implode(' AND ', array_map(function ($condition) use (&$bindings) {
                if (is_array($condition[1])) {
                    $values = implode(', ', array_fill(0, count($condition[1]), '?'));
                    $bindings = array_merge($bindings, $condition[1]);
                    return $condition[0] . ' NOT IN (' . $values . ')';
                } else if ($this->isSqlSubquery($condition[1])) {
                    // Check if $condition[1] starts and ends with parentheses
                    $isParenthesized = (substr($condition[1], 0, 1) === '(' && substr($condition[1], -1) === ')');
                    // Concatenate the condition based on whether parentheses are present or not
                    return $condition[0] . ' NOT IN ' . ($isParenthesized ? $condition[1] : '(' . $condition[1] . ')');
                } else {
                    return $condition;
                }
            }, $this->whereNotIn));
        }

        // WHERE BETWEEN clause
        if (!empty($this->whereBetween)) {
            foreach ($this->whereBetween as $condition) {
                $sql .= $this->containsWhere($sql, 'AND');
                $sql .= $condition[0] . ' BETWEEN ? AND ?';
                $bindings[] = $condition[1];
                $bindings[] = $condition[2];
            }
        }

        // OR WHERE clause
        if (!empty($this->orWhere)) {
            $sql .= $this->containsWhere($sql, 'OR');
            $sql .= implode(' OR ', array_map(function ($condition) use (&$bindings) {
                $bindings[] = $condition[2];
                return $condition[0] . ' ' . $condition[1] . ' ?';
            }, $this->orWhere));
        }

        // OR WHERE IN clause
        if (!empty($this->orWhereIn)) {
            $sql .= $this->containsWhere($sql, 'OR');
            $sql .= implode(' OR ', array_map(function ($condition) use (&$bindings) {
                if (is_array($condition[1])) {
                    $values = implode(', ', array_fill(0, count($condition[1]), '?'));
                    $bindings = array_merge($bindings, $condition[1]);
                    return $condition[0] . ' IN (' . $values . ')';
                } else if ($this->isSqlSubquery($condition[1])) {
                    // Check if $condition[1] starts and ends with parentheses
                    $isParenthesized = (substr($condition[1], 0, 1) === '(' && substr($condition[1], -1) === ')');
                    // Concatenate the condition based on whether parentheses are present or not
                    return $condition[0] . ' IN ' . ($isParenthesized ? $condition[1] : '(' . $condition[1] . ')');
                } else {
                    return $condition;
                }
            }, $this->orWhereIn));
        }

        // OR WHERE NOT IN clause
        if (!empty($this->orWhereNotIn)) {
            $sql .= $this->containsWhere($sql, 'OR');
            $sql .= implode(' OR ', array_map(function ($condition) use (&$bindings) {
                if (is_array($condition[1])) {
                    $values = implode(', ', array_fill(0, count($condition[1]), '?'));
                    $bindings = array_merge($bindings, $condition[1]);
                    return $condition[0] . ' NOT IN (' . $values . ')';
                } else if ($this->isSqlSubquery($condition[1])) {
                    // Check if $condition[1] starts and ends with parentheses
                    $isParenthesized = (substr($condition[1], 0, 1) === '(' && substr($condition[1], -1) === ')');
                    // Concatenate the condition based on whether parentheses are present or not
                    return $condition[0] . ' NOT IN ' . ($isParenthesized ? $condition[1] : '(' . $condition[1] . ')');
                } else {
                    return $condition;
                }
            }, $this->orWhereNotIn));
        }

        // OR WHERE BETWEEN clause
        if (!empty($this->orWhereBetween)) {
            foreach ($this->orWhereBetween as $condition) {
                $sql .= $this->containsWhere($sql, 'OR');
                $sql .= $condition[0] . ' BETWEEN ? AND ?';
                $bindings[] = $condition[1];
                $bindings[] = $condition[2];
            }
        }

        // GROUP BY clause
        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        // HAVING clause
        if (!empty($this->having)) {
            $sql .= ' HAVING ' . implode(' AND ', array_map(function ($condition) use (&$bindings) {
                $bindings[] = $condition[2];
                return $condition[0] . ' ' . $condition[1] . ' ?';
            }, $this->having));
        }

        // ORDER BY clause
        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', array_map(function ($order) {
                return $order[0] . ' ' . strtoupper($order[1]);
            }, $this->orderBy));
        }

        // LIMIT clause
        if (!empty($this->limit)) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        return ['sql' => $sql, 'bindings' => $bindings];
    }

    /**
     * Build an SQL INSERT statement with PDO binding.
     *
     * @param string $table The name of the table to insert data into.
     * @param array $data An associative array where keys are column names and values are the corresponding values to insert.
     * @return string|null The generated SQL INSERT statement or null if $data is not valid.
     */
    protected function buildInsertQuery($table, $data)
    {
        // Check if $data is an array and not empty
        if (!is_array($data) || empty($data)) {
            return;
        }

        // Construct column names string
        $columns = implode(', ', array_keys($data));

        // Construct placeholders for values
        $placeholders = implode(', ', array_map(function ($key) {
            return ":$key";
        }, array_keys($data)));

        // Construct the SQL insert statement
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";

        // Return the SQL statement
        return $sql;
    }

    /**
     * Build an SQL UPDATE statement with PDO binding.
     *
     * @param string $table The name of the table to update data in.
     * @param array $data An associative array where keys are column names and values are the corresponding values to update.
     * @param string|array $conditions Optional. The condition(s) to specify which rows to update.
     * @return string|null The generated SQL UPDATE statement or null if $data is not valid.
     */
    protected function buildUpdateQuery($table, $data, $conditions = NULL)
    {
        // Check if $data is an array and not empty
        if (!is_array($data) || empty($data)) {
            return null;
        }

        // Construct column-value pairs
        $setPairs = array_map(function ($key) {
            return "$key = :$key";
        }, array_keys($data));

        // Construct the SET clause
        $setClause = implode(', ', $setPairs);

        // Construct the SQL update statement
        $sql = "UPDATE $table SET $setClause";

        if (!empty($conditions)) {
            if (is_array($conditions)) {
                $whereClause = [];
                foreach ($conditions as $key => $value) {
                    $whereClause[] = "$key = " . $this->sanitize($value);
                }
                $sql .= " WHERE " . implode(' AND ', $whereClause);
            } else {
                $sql .= " WHERE " . $this->sanitize($conditions);
            }
        }

        // Return the SQL statement
        return $sql;
    }

    /**
     * Build an SQL DELETE statement with PDO binding.
     *
     * @param string $table The name of the table to delete from.
     * @param string|array $conditions Optional. The condition(s) to specify which rows to delete.
     * @return string|null The generated SQL DELETE statement or null if $condition is not valid.
     */
    protected function buildDeleteQuery($table, $conditions = NULL)
    {
        // Construct the SQL delete statement
        $sql = "DELETE FROM $table";

        // Add the condition if provided
        if (!empty($conditions)) {
            if (is_array($conditions)) {
                // Construct WHERE conditions
                $whereClause = [];
                foreach ($conditions as $key => $value) {
                    $whereClause[] = "$key = " . $this->sanitize($value);
                }
                $sql .= " WHERE " . implode(' AND ', $whereClause);
            }
        }

        // Return the SQL statement
        return $sql;
    }

    // HELPER

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
     * Get the generated SQL query string.
     *
     * @return string The SQL query string.
     */
    public function toSql()
    {
        return $this->buildQuery()['sql'];
    }

    /**
     * Get the generated bind params.
     *
     * @return array The bind query array.
     */
    public function getBindings()
    {
        return $this->buildQuery()['bindings'];
    }

    /**
     * Get the generated SQL query string with actual values.
     *
     * @return string The SQL query string with actual values.
     */
    public function getFullSql()
    {
        $query = $this->buildQuery();
        $sql = $query['sql'];
        $bindings = $query['bindings'];

        // Replace placeholders with actual values
        return vsprintf(str_replace('?', '%s', $sql), array_map(function ($value) {
            return is_numeric($value) || $this->isSqlSubquery($value) ? $value : "'" . $value . "'";
        }, $bindings));
    }

    /**
     * Returns the last executed query.
     *
     * @return string|null The last executed query, or null if no query has been executed yet.
     */
    public function lastQuery()
    {
        return $this->_lastQuery;
    }

    /**
     * Returns the last error that occurred during query execution.
     *
     * @return array|null The last error message and query, or null if no error has occurred yet.
     */
    public function lastError()
    {
        return $this->_error;
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

        // Check if secure mode is enabled
        if ($this->secure) {
            // Sanitize input to prevent XSS
            if (is_array($value)) {
                // Sanitize each value in the array
                foreach ($value as &$val) {
                    // Check if $val is not null and not empty, and not equal to 0
                    if (!is_null($val) && !empty($val) && !is_integer($val)) {
                        $val = htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8'); // Apply XSS protection to $val
                    }
                }
                return $value;
            } else {
                // Sanitize a single value
                return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            }
        } else {
            // Return input as-is if secure mode is disabled
            return $value;
        }
    }

    /**
     * Enable or disable secure input.
     *
     * @param bool $secure Whether to enable or disable secure input.
     * @return $this
     */
    public function secureInput($secure = true)
    {
        $this->secure = $secure;
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
    protected function sanitizeColumn($table, $data)
    {
        // Get columns from table schema based on database driver
        $driver = $this->connectionsSettings[$this->connectionName]['driver'];
        switch ($this->driver) {
            case 'mysql':
                $sql = "DESCRIBE $table";
                $stmt = $this->pdo[$this->connectionName]->prepare($sql);
                $stmt->execute();
                $columns_table = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                break;
            case 'mssql':
                $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?";
                $stmt = $this->pdo[$this->connectionName]->prepare($sql);
                $stmt->execute([$table]);
                $columns_table = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                break;
            case 'oracle':
                $sql = "SELECT COLUMN_NAME FROM ALL_TAB_COLUMNS WHERE TABLE_NAME = ?";
                $stmt = $this->pdo[$this->connectionName]->prepare($sql);
                $stmt->execute([$table]);
                $columns_table = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                break;
            case 'sqlite':
                $sql = "PRAGMA table_info($table)";
                $stmt = $this->pdo[$this->connectionName]->prepare($sql);
                $stmt->execute();
                $columns_table = $stmt->fetchAll(\PDO::FETCH_COLUMN, 1);
                break;
            case 'firebird':
                $sql = "SELECT RDB\$FIELD_NAME FROM RDB\$RELATION_FIELDS WHERE RDB\$RELATION_NAME = ?";
                $stmt = $this->pdo[$this->connectionName]->prepare($sql);
                $stmt->execute([$table]);
                $columns_table = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                break;
            default:
                throw new \Exception("Unsupported database driver '{$this->driver}'", 500);
        }

        // Filter $data array based on $columns_table
        $data = array_intersect_key($data, array_flip($columns_table));

        // Sanitize each value in the $data array
        $data = array_map(function ($value) {
            return !is_null($value) && $value !== '' ? $this->sanitize($value) : $value;
        }, $data);

        return $data;
    }

    /**
     * Check if the given string contains the word "WHERE".
     *
     * @param string $str The input string to check.
     * @return string True if the string contains "WHERE", false otherwise.
     */
    protected function containsWhere($str, $change)
    {
        return strpos($str, 'WHERE') !== false ? ' ' . $change . ' ' : ' WHERE ';
    }

    /**
     * Check if an array is multi-dimensional.
     *
     * @param array $array The array to check.
     * @return bool True if the array is multi-dimensional, false otherwise.
     */
    private function isMultiDimensionalArray($array)
    {
        foreach ($array as $element) {
            if (is_array($element)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a value represents a SQL subquery.
     *
     * @param string $value The value to check.
     * @return bool True if the value is a SQL subquery, false otherwise.
     */
    private function isSqlSubquery($value)
    {
        if (is_string($value) && !empty($value)) {
            // Regular expression pattern to match SQL subquery syntax
            $pattern = '/\b(?:SELECT|INSERT|UPDATE|DELETE)\b.*?\bFROM\b/';

            // Check if the pattern matches the value
            return preg_match($pattern, $value) === 1;
        }

        return false;
    }

    /**
     * Get the primary key column name for a given table.
     *
     * @param string $table The name of the table.
     * @return string|null The name of the primary key column, or null if not found.
     */
    protected function getPrimaryKeyColumn($table)
    {
        switch ($this->driver) {
            case 'mysql':
                // Query the information schema to get the primary key column
                $sql = "SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = :table
                AND CONSTRAINT_NAME = 'PRIMARY'";
                break;

            case 'mssql':
                // Query to get the primary key column name for MSSQL
                $sql = "SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE OBJECTPROPERTY(OBJECT_ID(CONSTRAINT_SCHEMA + '.' + CONSTRAINT_NAME), 'IsPrimaryKey') = 1 
                AND TABLE_NAME = :table";
                break;

            case 'oracle':
                // Query to get the primary key column name for Oracle
                $sql = "SELECT cols.column_name
                FROM all_constraints cons, all_cons_columns cols
                WHERE cons.constraint_type = 'P'
                AND cons.constraint_name = cols.constraint_name
                AND cons.owner = cols.owner
                AND cols.table_name = :table";
                break;

            case 'sqlite':
                // Query to get the primary key column name for SQLite
                $sql = "PRAGMA table_info($table)";
                break;

            case 'firebird':
                // Query to get the primary key column name for Firebird
                $sql = "SELECT TRIM(rdb\$index_segments.rdb\$field_name) AS column_name
                FROM rdb\$indices
                LEFT JOIN rdb\$index_segments ON rdb\$indices.rdb\$index_name = rdb\$index_segments.rdb\$index_name
                WHERE rdb\$indices.rdb\$relation_name = :table
                AND rdb\$indices.rdb\$unique_flag IS NOT NULL";
                break;

            default:
                throw new \PDOException('Unsupported database driver', 404);
        }

        // Prepare and execute the SQL statement
        $stmt = $this->pdo[$this->connectionName]->prepare($sql);
        $stmt->execute([':table' => $table]);

        // Fetch the primary key column name
        $primaryKeyColumn = $stmt->fetchColumn();

        return $primaryKeyColumn ?: null;
    }
}
