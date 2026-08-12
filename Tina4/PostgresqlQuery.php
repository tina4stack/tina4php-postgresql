<?php
/**
 * Tina4 - This is not a 4ramework.
 * Copy-right 2007 - current Tina4
 * License: MIT https://opensource.org/licenses/MIT
 */

namespace Tina4;

/**
 * Queries the PostgreSQL database and returns back results
 */
class PostgresqlQuery extends DataConnection implements DataBaseQuery
{
    /**
     * Runs a query against the database and returns a DataResult
     * @param $sql
     * @param int $noOfRecords
     * @param int $offSet
     * @param array $fieldMapping
     * @return DataResult|null
     */
    final public function query($sql, int $noOfRecords = 10, int $offSet = 0, array $fieldMapping = []): ?DataResult
    {
        $params = [];
        $sqlIsArray = is_array($sql);
        if ($sqlIsArray) {
            $initialSQL = $sql[0];
            $params = $sql;
            $sql = $sql[0];
        } else {
            $initialSQL = $sql;
        }

        $initialSQL = $sql;

        //Don't add a limit if there is a limit already or if there is a stored procedure call
        if (stripos($sql, "limit") === false && stripos($sql, "returning") === false) {
            $sql .= " limit {$noOfRecords} offset {$offSet}";
        }

        if ($sqlIsArray) {
            $recordCursor = pg_query_params($this->getDbh(), $sql, array_slice($params, 1));
        } else {
            $recordCursor = pg_query($this->getDbh(), $sql);
        }
        
        $records = null;
        while ($record = pg_fetch_assoc($recordCursor)) {
            $record = (new PostgresqlBlobHandler($this->getConnection()))->decodeBlobs($record);
            $record = $this->castRowValues($record, $recordCursor);
            $records[] = (new DataRecord(
                $record,
                $fieldMapping,
                $this->getConnection()->getDefaultDatabaseDateFormat(),
                $this->getConnection()->dateFormat
            ));
        }

        //populate the fields
        $fields = [];
        if (is_array($records) && count($records) > 0) {
            if (stripos($initialSQL, "returning") === false) {
                if (!empty($records)) {
                    $record = $records[0];
                    $fid = 0;
                    foreach ($record as $field) {
                        $fields[] = (new DataField(
                            $fid,
                            pg_field_name($recordCursor, $fid),
                            pg_field_name($recordCursor, $fid),
                            pg_field_type($recordCursor, $fid),
                            pg_field_size($recordCursor, $fid)
                        ));

                        $fid++;
                    }
                }

                $sqlCount = "select count(*) as COUNT_RECORDS from ($initialSQL) c";
                $recordCount = pg_query($this->getDbh(), $sqlCount);
                $resultCount = pg_fetch_assoc($recordCount);

                $resultCount["COUNT_RECORDS"] = $resultCount["count_records"];


            } else {
                $resultCount["COUNT_RECORDS"] = count($records); //used for insert into or update
            }
        } else {
            $resultCount["COUNT_RECORDS"] = 0;
        }

        $error = $this->getConnection()->error();

        return (new DataResult($records, $fields, $resultCount["COUNT_RECORDS"], $offSet, $error));
    }

    /**
     * Casts PostgreSQL string values returned by pg_fetch_assoc to their native PHP types
     * based on the column type reported by pg_field_type().
     * @param array $row A single row from pg_fetch_assoc
     * @param resource $cursor The PostgreSQL result cursor
     * @return array Row with integer, float and boolean values properly typed
     */
    private function castRowValues(array $row, $cursor): array
    {
        $integerTypes = ['int2', 'int4', 'int8', 'oid'];
        $floatTypes   = ['float4', 'float8', 'numeric'];
        $fid = 0;
        foreach ($row as $fieldName => $value) {
            if ($value === null) {
                $fid++;
                continue;
            }
            $pgType = pg_field_type($cursor, $fid);
            if (in_array($pgType, $integerTypes, true)) {
                $row[$fieldName] = (int) $value;
            } elseif (in_array($pgType, $floatTypes, true)) {
                $row[$fieldName] = (float) $value;
            } elseif ($pgType === 'bool') {
                $row[$fieldName] = ($value === 't');
            }
            $fid++;
        }
        return $row;
    }
}
