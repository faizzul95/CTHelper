<?php

namespace Core\Database\Drivers;

/**
 * Database MySQLDriver class
 *
 * @category Database
 * @package Core\Database
 * @author 
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link 
 * @version 0.0.1
 */

use Core\Database\BaseDatabase;

class MySQLDriver extends BaseDatabase
{
    public function connect($connectionName = 'default')
    {
        $dsn = "mysql:host={$this->config['host']};dbname={$this->config['database']}";
        if (isset($this->config['charset'])) {
            $dsn .= ";charset={$this->config['charset']}";
        }
        if (isset($this->config['port'])) {
            $dsn .= ";port={$this->config['port']}";
        }
        if (isset($this->config['socket'])) {
            $dsn .= ";unix_socket={$this->config['socket']}";
        }

        try {
            // Connection options
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ];

            if (isset($this->config['charset']) && !empty($this->config['charset'])) {
                $options[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES " . $this->config['charset'];
            }

            $pdo = new \PDO($dsn, $this->config['username'], $this->config['password'], $options);

            $this->pdo[$this->connectionName] = $pdo;
            $this->setDatabase($this->config['database']);
        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        }
    }

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

            $this->_buildWhereClause("DATE_FORMAT($column, '%Y-%m-%d')", $formattedDate, $operator, 'AND');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

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

            $this->_buildWhereClause("DATE_FORMAT($column, '%Y-%m-%d')", $formattedDate, $operator, 'OR');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

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

            $this->_buildWhereClause("DAY($column)", (int)$day, $operator, 'AND');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

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

            $this->_buildWhereClause("DAY($column)", (int)$day, $operator, 'OR');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

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

            $this->_buildWhereClause("MONTH($column)", (int)$month, $operator, 'AND');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

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

            $this->_buildWhereClause("MONTH($column)", (int)$month, $operator, 'OR');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

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

            $this->_buildWhereClause("YEAR($column)", (int)$year, $operator, 'AND');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

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

            $this->_buildWhereClause("YEAR($column)", (int)$year, $operator, 'OR');
            return $this;
        } catch (\InvalidArgumentException $e) {
            $this->db_error_log($e, __FUNCTION__);
        }
    }

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
}
