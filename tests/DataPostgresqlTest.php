<?php
/**
 * Tina4 - This is not a 4ramework.
 * Copy-right 2007 - current Tina4
 * License: MIT https://opensource.org/licenses/MIT
 *
 * Uniform database driver test suite.
 * Start the database:  docker compose up -d
 * Run tests:           composer test
 */

use PHPUnit\Framework\TestCase;

require_once "./Tina4/DataPostgresql.php";

class DataPostgresqlTest extends TestCase
{
    public $DBA;

    final public function setUp(): void
    {
        $this->DBA = new \Tina4\DataPostgresql("localhost/55432:postgres", "postgres", "pass1234");
    }

    // --- Connection ---

    final public function testOpen(): void
    {
        $this->assertNotEmpty($this->DBA, "Database connection should not be empty");
    }

    final public function testGetShortName(): void
    {
        $this->assertEquals("postgresql", $this->DBA->getShortName());
    }

    final public function testGetDefaultPort(): void
    {
        $this->assertEquals(5432, $this->DBA->getDefaultDatabasePort());
    }

    final public function testIsNotNoSQL(): void
    {
        $this->assertFalse($this->DBA->isNoSQL());
    }

    // --- Table operations ---

    final public function testDropCreateTable(): void
    {
        if ($this->DBA->tableExists("sub_testing")) {
            $this->DBA->exec("drop table sub_testing");
        }
        if ($this->DBA->tableExists("testing")) {
            $this->DBA->exec("drop table testing");
        }
        $this->DBA->commit();

        $this->DBA->exec("create table testing (
            id integer default 0,
            name varchar(200) default 'Name',
            contact_number varchar(20) default '',
            age integer default 22,
            salary numeric(10,2),
            my_date timestamp default '01/01/1900',
            my_date_2 timestamp default now(),
            my_date_3 date default '01/01/1900',
            primary key(id)
        )");
        $this->DBA->commit();

        $exists = $this->DBA->tableExists("testing");
        $this->assertTrue($exists, "Table 'testing' should exist after creation");

        $this->DBA->exec("create table sub_testing (
            id integer default 1 not null,
            testing_id integer default 0 not null references testing(id) on delete cascade,
            primary key(id)
        )");
        $this->DBA->commit();

        $this->assertTrue($this->DBA->tableExists("sub_testing"), "Table 'sub_testing' should exist");
    }

    final public function testTableExistsTrue(): void
    {
        $this->assertTrue($this->DBA->tableExists("testing"), "Table 'testing' should exist");
    }

    final public function testTableExistsFalse(): void
    {
        $this->assertFalse($this->DBA->tableExists("nonexistent_table_xyz"), "Non-existent table should return false");
    }

    // --- CRUD operations ---

    final public function testInsertWithParams(): void
    {
        $this->DBA->exec("insert into testing (id, name) values ($1, $2)", 1, "Alice");
        $record = $this->DBA->fetchOne("select * from testing where id = 1")->asArray();
        $this->assertEquals("Alice", $record["name"], "Name should be 'Alice'");
    }

    final public function testInsertWithoutParams(): void
    {
        $this->DBA->exec("insert into testing (id, name) values (2, 'Bob')");
        $record = $this->DBA->fetchOne("select * from testing where id = 2")->asArray();
        $this->assertEquals("Bob", $record["name"], "Name should be 'Bob'");
    }

    final public function testInsertReturning(): void
    {
        $result = $this->DBA->exec("insert into testing (id, name) values (3, 'Charlie') returning id")->asArray();
        $this->assertEquals(3, $result[0]["id"], "RETURNING should give back id = 3");
    }

    final public function testFetchAll(): void
    {
        $records = $this->DBA->fetch("select * from testing")->asArray();
        $this->assertGreaterThanOrEqual(2, count($records), "Should have at least 2 records");
    }

    final public function testFetchWithLimit(): void
    {
        $result = $this->DBA->fetch("select * from testing", 1, 0);
        $this->assertLessThanOrEqual(1, count($result->records()), "Should return at most 1 record");
    }

    final public function testFetchEmpty(): void
    {
        $result = $this->DBA->fetch("select * from testing where id = 99999");
        $this->assertCount(0, $result->records(), "Should return 0 records for non-existent id");
    }

    final public function testUpdate(): void
    {
        $this->DBA->exec("update testing set name = 'Updated' where id = 1");
        $record = $this->DBA->fetchOne("select * from testing where id = 1")->asArray();
        $this->assertEquals("Updated", $record["name"], "Name should be 'Updated' after update");
    }

    final public function testDelete(): void
    {
        $this->DBA->exec("insert into testing (id, name) values (100, 'ToDelete')");
        $this->DBA->exec("delete from testing where id = 100");
        $result = $this->DBA->fetch("select * from testing where id = 100");
        $this->assertCount(0, $result->records(), "Deleted record should not exist");
    }

    final public function testFieldNameConversion(): void
    {
        $this->DBA->exec("insert into testing (id, contact_number) values (50, '0836464535')");
        $record = $this->DBA->fetchOne("select * from testing where id = 50")->asArray();
        $this->assertEquals("0836464535", $record["contactNumber"], "snake_case should convert to camelCase");
    }

    // --- Metadata ---

    final public function testGetDatabase(): void
    {
        $database = $this->DBA->getDatabase();
        $this->assertArrayHasKey("testing", $database, "Metadata should contain 'testing' table");
        $this->assertArrayHasKey("sub_testing", $database, "Metadata should contain 'sub_testing' table");
    }

    final public function testGetDefaultDateFormat(): void
    {
        $format = $this->DBA->getDefaultDatabaseDateFormat();
        $this->assertNotEmpty($format, "Date format should not be empty");
    }
}
