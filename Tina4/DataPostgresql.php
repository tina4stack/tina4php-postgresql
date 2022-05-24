<?php
/**
 * Tina4 - This is not a 4ramework.
 * Copy-right 2007 - current Tina4
 * License: MIT https://opensource.org/licenses/MIT
 */

namespace Tina4;

/**
 * DataPostgresql
 * The implementation for the firebird database engine
 * @package Tina4
 */
class DataPostgresql implements DataBase
{
    use DataBaseCore;

    /**
     * @var null database metadata
     */
    private $databaseMetaData;

    /**
     * Open a Firebird database connection
     * @param bool $persistent
     * @throws \Exception
     */
    final public function open(bool $persistent = true): void
    {
        if (!function_exists("pg_connect")) {
            throw new \Exception("Postgres extension for PHP needs to be installed");
        }

        $connectionString = "host=$this->hostName port=$this->port dbname=$this->databaseName user=$this->username password=$this->password";
        $this->dbh = (new PostgresqlConnection(
            $connectionString
        ))->getConnection();
    }

    /**
     * Gets the default database date format
     * @return mixed|string
     */
    final public function getDefaultDatabaseDateFormat(): string
    {
        return "Y-m-d";
    }

    /**
     * Close a Firebird database connection
     */
    final public function close(): void
    {
        pg_close($this->dbh);
    }

    /**
     * Execute a firebird query, format is query followed by params or variables
     * @return DataError|bool
     */
    final public function exec()
    {
        $params = $this->parseParams(func_get_args());

        $tranId = $params["tranId"];
        $params = $params["params"];

        if (isset($params[0]) && stripos($params[0], "returning") !== false) {
            return $this->fetch($params);
        }

        (new PostgresqlExec($this))->exec($params, $tranId);

        return $this->error();
    }

    /**
     * Firebird implementation of fetch
     * @param string|array $sql
     * @param int $noOfRecords
     * @param int $offSet
     * @param array $fieldMapping
     * @return bool|DataResult
     */
    final public function fetch($sql, int $noOfRecords = 10, int $offSet = 0, array $fieldMapping = []): ?DataResult
    {
        return (new PostgresqlQuery($this))->query($sql, $noOfRecords, $offSet, $fieldMapping);
    }

    /**
     * Returns an error
     * @return DataError
     */
    final public function error(): DataError
    {
        $errorCode = "";
        $errorMessage = pg_errormessage($this->dbh);
        if (!empty($errorMessage)) {
            $errorCode = "01";
        } else {
            $errorMessage = "";
        }

        return (new DataError($errorCode, $errorMessage));
    }

    /**
     * Commit
     * @param null $transactionId
     * @return bool
     */
    final public function commit($transactionId = null)
    {
        return true;
    }

    /**
     * Rollback
     * @param null $transactionId
     * @return bool
     */
    final public function rollback($transactionId = null)
    {
        //not doing this
    }

    /**
     * Auto commit on for Firebird
     * @param bool $onState
     * @return bool|void
     */
    final public function autoCommit(bool $onState = false): void
    {
        //Firebird has commit off by default
    }

    /**
     * Start Transaction
     * @return false|int|resource
     */
    final public function startTransaction()
    {
        return "Resource id #";
    }

    /**
     * Check if table exists
     * @param string $tableName
     * @return bool
     */
    final public function tableExists(string $tableName): bool
    {
        if (!empty($tableName)) {

            // table name must be in upper case
            $exists = $this->fetch("SELECT * FROM information_schema.tables 
                                           WHERE  table_schema in ('information_schema', 'public')
                                           AND    table_catalog = '{$this->databaseName}'  
                                           AND    table_name   = '{$tableName}'");



            return !empty($exists->records());
        }

        return false;
    }

    /**
     * Get the last id
     * @return string
     */
    final public function getLastId(): string
    {
        return "";
    }

    /**
     * Get the database metadata
     * @return array|mixed
     */
    final public function getDatabase(): array
    {
        if (!empty($this->databaseMetaData)) {
            return $this->databaseMetaData;
        }

        $this->databaseMetaData = (new PostgresqlMetaData($this))->getDatabaseMetaData();

        return $this->databaseMetaData;
    }

    /**
     * Gets the default database port
     * @return int|mixed
     */
    final public function getDefaultDatabasePort(): int
    {
        return 5432;
    }

    /**
     * Returns back the correct param type convention for parameterised queries
     * Default is normally ?
     * @param string $fieldName
     * @param int $fieldIndex
     * @return mixed
     */
    final public function getQueryParam(string $fieldName, int $fieldIndex): string
    {
        return "\${$fieldIndex}";
    }

    /**
     * Is it a No SQL database?
     * @return bool
     */
    final public function isNoSQL(): bool
    {
        return false;
    }

    /**
     * Get a short name for the database used for specific database migrations
     * @return string
     */
    public function getShortName(): string
    {
        return "postgresql";
    }
}
