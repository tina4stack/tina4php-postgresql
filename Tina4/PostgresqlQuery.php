<?php
/**
 * Tina4 - This is not a 4ramework.
 * Copy-right 2007 - current Tina4
 * License: MIT https://opensource.org/licenses/MIT
 */

namespace Tina4;

/**
 * Queries the Firebird database and returns back results
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
        if (is_array($sql)) {
            $initialSQL = $sql[0];
            $queryName = "tina4".rand(10000,99999).md5($sql[0]);
            $params = array_merge([$this->getDbh(), $queryName], $sql);
            $sql = $sql[0];
        } else {
            $initialSQL = $sql;
        }

        $initialSQL = $sql;

        //Don't add a limit if there is a limit already or if there is a stored procedure call
        if (stripos($sql, "limit") === false && stripos($sql, "returning") === false) {
            $sql .= " limit {$noOfRecords} offset {$offSet}";
        }

        if (is_array($sql)) {
            $recordCursor = pg_query_params(...$params);
        } else {
            $recordCursor = pg_query($this->getDbh(), $sql);
        }
        
        $records = null;
        while ($record = pg_fetch_assoc($recordCursor)) {
            $record = (new PostgresqlBlobHandler($this->getConnection()))->decodeBlobs($record);
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
}
