<?php
/**
 * Tina4 - This is not a 4ramework.
 * Copy-right 2007 - current Tina4
 * License: MIT https://opensource.org/licenses/MIT
 */

namespace Tina4;

/**
 * Executes queries on a Postgres database
 */
class PostgresqlExec extends DataConnection implements DataBaseExec
{
    /**
     * Execute a Postgres Query Statement which ordinarily does not retrieve results
     * @param $params
     * @param $tranId
     * @return DataResult|void|null
     */
    final public function exec($params, $tranId): void
    {
        \Tina4\Debug::message("Running query ".$params[0]);
        $queryName = "tina4".rand(10000,99999).md5($params[0]);

        $preparedQuery = pg_prepare($this->getDbh(), $queryName, $params[0]);

        if (!empty($preparedQuery)) {
            unset($params[0]);
            $params = [ $this->getDbh(), $queryName, $params];
            pg_execute(...$params);
        }
    }
}
