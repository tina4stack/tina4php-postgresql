<?php
/**
 * Tina4 - This is not a 4ramework.
 * Copy-right 2007 - current Tina4
 * License: MIT https://opensource.org/licenses/MIT
 */

namespace Tina4;

/**
 * PostgresqlConnection
 * Establishes a connection to a Firebird database
 */
class PostgresqlConnection
{
    /**
     * Database connection
     * @var false|resource
     */
    private $connection;

    /**
     * Creates a Firebird Database Connection
     * @param string $connectionString
     * @param bool $persistent
     */
    public function __construct(string $connectionString, $persistent=true)
    {
        if ($persistent) {
            $this->connection = pg_connect($connectionString);
        } else {
            $this->connection = pg_connect($connectionString, PGSQL_CONNECT_FORCE_NEW);
        }
    }

    /**
     * Returns a databse connection or false if failed
     * @return false|resource
     */
    final public function getConnection()
    {
        return $this->connection;
    }

}