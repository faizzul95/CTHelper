<?php

namespace Core\Database\Drivers;

/**
 * Database MSSQLDriver class
 *
 * @category Database
 * @package Core\Database
 * @author 
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link 
 * @version 0.0.1
 */

use Core\Database\BaseDatabase;

class MSSQLDriver extends BaseDatabase
{
    public function connect($connectionID = null)
    {

        $connectionName = !empty($connectionID) ? $connectionID : $this->connectionName;

        if (!isset($this->config[$connectionName])) {
            die("Configuration for $connectionName not found");
        }

        $this->setConnection($connectionName);
        $this->setDatabase($this->config[$connectionName]['database']);

        $pdo = null;

        if (!isset($this->pdo[$connectionName])) {

            $dsn = "sqlsrv:Server={$this->config[$connectionName]['host']};Database={$this->config[$connectionName]['database']}";

            if (isset($this->config[$connectionName]['port'])) {
                $dsn .= ",{$this->config[$connectionName]['port']}";
            }

            try {
                // Connection options
                $options = [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ];

                $pdo = new \PDO($dsn, $this->config[$connectionName]['username'], $this->config[$connectionName]['password'], $options);
                $this->pdo[$connectionName] = $pdo;

            } catch (\PDOException $e) {
                throw new \Exception($e->getMessage());
            }
        }

        $this->driver = $this->config[$connectionName]['driver'];
        self::$_instance = $this;

        return $this;
    }

    public function whereDate($column, $date, $operator = '=')
    {
        try {
            $this->validateColumn($column);

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in whereDate(). Please use simpleQuery() function.');

            $formattedDate = $this->validateDate($date);
            $this->validateOperator($operator);

            $this->_buildWhereClause("CONVERT(date, $column)", $formattedDate, $operator, 'AND');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function orWhereDate($column, $date, $operator = '=')
    {
        try {
            $this->validateColumn($column);

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in orWhereDate(). Please use simpleQuery() function.');

            $formattedDate = $this->validateDate($date);
            $this->validateOperator($operator);

            $this->_buildWhereClause("CONVERT(date, $column)", $formattedDate, $operator, 'OR');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function whereDay($column, $day, $operator = '=')
    {
        try {
            $this->validateColumn($column);

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in whereDay(). Please use simpleQuery() function.');

            $this->validateDay($day);
            $this->validateOperator($operator);

            $this->_buildWhereClause("DAY($column)", (int)$day, $operator, 'AND');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function orWhereDay($column, $day, $operator = '=')
    {
        try {
            $this->validateColumn($column);

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in orWhereDay(). Please use simpleQuery() function.');

            $this->validateDay($day);
            $this->validateOperator($operator);

            $this->_buildWhereClause("DAY($column)", (int)$day, $operator, 'OR');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function whereMonth($column, $month, $operator = '=')
    {
        try {
            $this->validateColumn($column);

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in whereMonth(). Please use simpleQuery() function.');

            $this->validateMonth($month);
            $this->validateOperator($operator);

            $this->_buildWhereClause("MONTH($column)", (int)$month, $operator, 'AND');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function orWhereMonth($column, $month, $operator = '=')
    {
        try {
            $this->validateColumn($column);

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in orWhereMonth(). Please use simpleQuery() function.');

            $this->validateMonth($month);
            $this->validateOperator($operator);

            $this->_buildWhereClause("MONTH($column)", (int)$month, $operator, 'OR');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function whereYear($column, $year, $operator = '=')
    {
        try {
            $this->validateColumn($column);

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in whereYear(). Please use simpleQuery() function.');

            $this->validateYear($year);
            $this->validateOperator($operator);

            $this->_buildWhereClause("YEAR($column)", (int)$year, $operator, 'AND');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function orWhereYear($column, $year, $operator = '=')
    {
        try {
            $this->validateColumn($column);

            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in orWhereYear(). Please use simpleQuery() function.');

            $this->validateYear($year);
            $this->validateOperator($operator);

            $this->_buildWhereClause("YEAR($column)", (int)$year, $operator, 'OR');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

    public function whereJsonContains($columnName, $jsonPath, $value)
    {
        return $this;
    }
}
