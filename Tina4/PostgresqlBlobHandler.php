<?php
/**
 * Tina4 - This is not a 4ramework.
 * Copy-right 2007 - current Tina4
 * License: MIT https://opensource.org/licenses/MIT
 */

namespace Tina4;

/**
 * Fetches blob data from the database
 */
class PostgresqlBlobHandler extends DataConnection
{
    /**
     * Decodes the blobs for a returned record
     * @param $record
     * @return mixed
     */
    final public function decodeBlobs($record)
    {
        foreach ($record as $key => $value) {
            if (empty($value)){
                continue;
            }
            if (strpos($value, "0x") === 0) { //@todo how to know if blob ?
                pg_query($this->getDbh(), "begin");
                $handle = pg_lo_open($this->getDbh(), $key, "r");
                //Find the end of the blob
                pg_lo_seek($handle, 0, PGSQL_SEEK_END);
                $size = pg_lo_tell($handle);
                //Find the beginning of the blob
                pg_lo_seek($handle, 0, PGSQL_SEEK_SET);
                //Read the whole blob
                $content = pg_lo_read($handle, $size);
                pg_query($this->getDbh(), "commit");
                $record[$key] = $content;
            }
        }

        return $record;
    }
}
