<?php
/**
 * Tina4 - This is not a 4ramework.
 * Copy-right 2007 - current Tina4
 * License: MIT https://opensource.org/licenses/MIT
 */

namespace Tina4;

/**
 * PostgresqlMetaData retrieves the Firebird metadata from the database
 */
class PostgresqlMetaData extends DataConnection implements DataBaseMetaData
{
    /**
     * Get all the tables for the database
     * @return array
     */
    final public function getTables() : array
    {
        $sqlTables = "SELECT DISTINCT table_name, table_type
                        FROM INFORMATION_SCHEMA.tables
                     WHERE upper(table_catalog) = upper('{$this->getConnection()->databaseName}')
                      AND upper(table_schema) = upper('public') 
                     ORDER BY table_type ASC, table_name DESC";

        $tables = $this->getConnection()->fetch($sqlTables, 1000, 0);

        if (!empty($tables)) {
            return $tables->asObject();
        }

        return [];
    }

    /**
     * Gets the information for a specific table
     * @param string $tableName
     * @return array
     */
    final public function getTableInformation(string $tableName) : array
    {
        $tableInformation = [];
        $sqlInfo = "select * from INFORMATION_SCHEMA.columns where upper(table_name) = upper('$tableName')";

        $columns = $this->getConnection()->fetch($sqlInfo, 1000, 0)->AsObject();



        $primaryKeys = $this->getPrimaryKeys($tableName);
        $primaryKeyLookup = [];
        foreach ($primaryKeys as $primaryKey) {
            $primaryKeyLookup[$primaryKey->fieldName] = true;
        }

        $foreignKeys = $this->getForeignKeys($tableName);
        $foreignKeyLookup = [];
        foreach ($foreignKeys as $foreignKey) {
            $foreignKeyLookup[$foreignKey->fieldName] = true;
        }

        foreach ($columns as $columnIndex => $columnData) {

            $fieldData = new \Tina4\DataField(
                $columnIndex,
                trim($columnData->columnName),
                trim($columnData->columnName),
                trim($columnData->dataType),
                (int)trim($columnData->numericPrecision),
                (int)trim($columnData->numericScale)
            );

            $fieldData->isNotNull = false;
            if ($columnData->isNullable === "NO") {
                $fieldData->isNotNull = true;
            }

            $fieldData->isPrimaryKey = false;
            if (isset($primaryKeyLookup[$fieldData->fieldName])) {
                $fieldData->isPrimaryKey = true;
            }

            $fieldData->isForeignKey = false;
            if (isset($foreignKeyLookup[$fieldData->fieldName])) {
                $fieldData->isForeignKey = true;
            }

            $fieldData->defaultValue = $columnData->columnDefault;
            $tableInformation[] = $fieldData;
        }

        return $tableInformation;
    }

    /**
     * Gets the complete database metadata
     * @return array
     */
    final public function getDatabaseMetaData(): array
    {
        $database = [];
        $tables = $this->getTables();

        foreach ($tables as $record) {
            $tableInfo = $this->getTableInformation($record->tableName);

            $database[strtolower($record->tableName)] = $tableInfo;
        }

        return $database;
    }

    /**
     * Gets the primary keys for a table
     * @param string $tableName
     * @return array
     */
    final public function getPrimaryKeys(string $tableName): array
    {
        return [];
    }

    /**
     * Gets the foreign keys for given table
     * @param string $tableName
     * @return array
     */
    final public function getForeignKeys(string $tableName): array
    {
        return [];
    }
}
