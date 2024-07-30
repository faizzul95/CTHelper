<?php

namespace Core\Database\Drivers;

/**
 * Database OciDriver class
 *
 * @category Database
 * @package Core\Database
 * @author 
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link 
 * @version 0.0.1
 */

use Core\Database\BaseDatabase;

class OciDriver extends BaseDatabase
{
    public function connect($connectionName = 'default')
    {
        $dsn = "oci:dbname={$this->config['host']}/{$this->config['database']}";
        if (isset($this->config['charset'])) {
            $dsn .= ";charset={$this->config['charset']}";
        }

        try {
            // Connection options
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ];

            $pdo = new \PDO($dsn, $this->config['username'], $this->config['password'], $options);

            $this->setConnection($connectionName);
            $this->setDatabase($this->config['database']);
            $this->pdo[$this->connectionName] = $pdo;

        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function whereDate($column, $date, $operator = '=')
    {
        try {
            $this->validateColumn($column);
            
            // Check if variable contains a full SQL statement
            $this->_forbidRawQuery($column, 'Full/Sub SQL statements are not allowed in whereDate(). Please use simpleQuery() function.');

            $formattedDate = $this->validateDate($date);
            $this->validateOperator($operator);
    
            $this->_buildWhereClause("TO_DATE($column, 'YYYY-MM-DD')", $formattedDate, $operator, 'AND');
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
    
            $this->_buildWhereClause("TO_DATE($column, 'YYYY-MM-DD')", $formattedDate, $operator, 'OR');
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
