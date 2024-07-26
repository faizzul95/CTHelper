<?php

namespace Core\Database;

use PDO;
use PDOException;

use Core\Database\Interface\ConnectionInterface;
use Core\Database\Interface\CrudInterface;
use Core\Database\Interface\PrepareStatementInterface;
use Core\Database\Interface\UtilsInterface;
use Core\Database\Interface\ResultInterface;

abstract class BaseDatabase implements ConnectionInterface, PrepareStatementInterface, CrudInterface, UtilsInterface, ResultInterface
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
     * @var array The database config
     */
    protected $config = [];

    /**
     * @var string $driver The database driver being used (e.g., 'mysql', 'mssql', 'mariadb', 'oci', etc.).
     */
    protected $driver = 'mysql';

    /**
     * @var string the name of a default (main) pdo connection
     */
    public $connectionID = 'default';

    /**
     * @var string|null The database schema name.
     */
    protected $schema;

    /**
     * @var string The table name.
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
     * @var int|null The offset for the query.
     */
    protected $offset;

    /**
     * @var array|null The order by columns and directions.
     */
    protected $orderBy;

    /**
     * @var array|null The group by columns.
     */
    protected $groupBy;

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
     * @var string The return type for return result.
     */
    protected $returnType = 'array';


    // Implement ConnectionIntrface

    public function initialize(array $config)
    {
        $this->config = $config;
    }

    public function connect()
    {
        // Each driver will implement its own connection logic
        return $this;
    }

    public function setConnection($connectionID)
    {
        $this->connectionID = $connectionID;
    }

    public function getConnection($connectionID = null)
    {
        return !empty($connectionID) && isset($this->pdo[$this->connectionID]) ? $this->pdo[$this->connectionID] : $this->pdo;
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

        $mapPlatform = [
            'mysql' => 'MySQL',
            'mssql' => 'MSSQL',
            'oci' => 'Oci',
            'mariadb' => 'MariaDB',
            'fdb' => 'Firebird',
            '-' => 'Unknown'
        ];

        return $mapPlatform[$dbPlatform];
    }

    public function getVersion()
    {
        // Get database version 
        if (isset($this->pdo[$this->connectionID]) && $this->pdo[$this->connectionID] instanceof \PDO) {
            return $this->pdo[$this->connectionID]->getAttribute(\PDO::ATTR_SERVER_VERSION);
        } else {
            return 'Unknown';  // Handle cases where no database connection exists
        }
    }

    // Implement PrepareStatementInterface logic

    public function resetStatement() {
        $this->driver = 'mysql';
        $this->connectionID = 'default';
        $this->table = null;
        $this->fields = '*';
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
        return $this;
    }









    // Implement ResultInterface

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

    // 1) Convert result based on $returnType

    /**
     * Converts the result data to the specified return type.
     *
     * @param mixed $data The data to be converted.
     * @return mixed The converted data.
    */
    protected function _returnResult($data)
    {
        if(empty($data))
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
}
