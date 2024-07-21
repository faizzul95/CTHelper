<?php

namespace Core\Database;

/**
 * Database Class
 *
 * @category  Database
 * @package   Core\Database
 * @author    
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link      -
 * @version   0.0.1
 */

class Database
{
    protected $db;

    public function __construct(string $connectionID = 'default', array $config)
    {
        if (empty($config)) {
            throw new \InvalidArgumentException("No configuration found.");
        }

        $dbPlatform = isset($config['driver']) ? strtolower($config['driver']) : '-';

        $mapPlatform = [
            'mysql' => 'MySQL',
            'mssql' => 'MSSQL',
            'oci' => 'Oci',
            'mariadb' => 'MariaDB',
            'fdb' => 'Firebird'
        ];

        if (!isset($mapPlatform[$dbPlatform])) {
            throw new \InvalidArgumentException("Unsupported database driver: {$dbPlatform}");
        }

        $driverClass = "Core\\Drivers\\" . $mapPlatform[$dbPlatform] . "Driver";
        $this->db = new $driverClass;
        $this->db->setConnection($connectionID);
        $this->db->initialize($config);
    }

    public function __call($method, $args)
    {
        if (method_exists($this->db, $method)) {
            return call_user_func_array([$this->db, $method], $args);
        }
        throw new \BadMethodCallException("Method $method does not exist");
    }
}
