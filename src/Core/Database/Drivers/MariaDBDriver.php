<?php

namespace Core\Database\Drivers;

/**
 * Database MariaDBDriver class
 *
 * @category Database
 * @package Core\Database
 * @author 
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link 
 * @version 0.0.1
 */

use Core\Database\BaseDatabase;

class MariaDBDriver extends BaseDatabase
{
    public function connect()
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

            $this->pdo[$this->connectionID] = $pdo;
            $this->setDatabase($this->config['database']);
        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
