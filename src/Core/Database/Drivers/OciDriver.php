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
    public function connect()
    {
        $dsn = "oci:dbname={$this->config['host']}/{$this->config['database']}";

        try {
            
            // Connection options
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ];

            $pdo = new \PDO($dsn, $this->config['username'], $this->config['password'], $options);

            $this->pdo[$this->connectionID] = $pdo;
            $this->setDatabase($this->config['database']);

        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
