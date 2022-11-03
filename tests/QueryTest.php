<?php
use PHPUnit\Framework\TestCase;

final class QueryTest extends TestCase
{
    protected $db;

    public function setUp(): void
    {
        $this->db = new MySQL(true,"testdb","localhost","root","root");
    }
    
    public function testQuery()
    {
        # 1
        $actual = $this->db->Query("SELECT * FROM `test_table`");
        $this->assertIsObject($actual);

        # 2
        $actual = $this->db->Query("UPDATE `invalid_table` SET `value` = 1 WHERE `id` = 10");
        $this->assertFalse($actual);
    }

    public function testQueryArray()
    {
        # 1
        $expected = array(array("0"=>"1", "1"=>"John", "2"=>"2022-01-01", "3"=>"Red", "id"=>"1", "name"=>"John", "data"=>"2022-01-01", "value"=>"Red"));
        $actual = $this->db->QueryArray("SELECT * FROM `test_table` WHERE `id` = 1");
        $this->assertEqualsCanonicalizing($expected, $actual);

        # 2
        $expected = array(array("id"=>"1", "name"=>"John", "data"=>"2022-01-01", "value"=>"Red"));
        $actual = $this->db->QueryArray("SELECT * FROM `test_table` WHERE `id` = 1", MYSQLI_ASSOC);
        $this->assertEqualsCanonicalizing($expected, $actual);

        # 3
        $expected = array(array("0"=>"1", "1"=>"John", "2"=>"2022-01-01", "3"=>"Red"));
        $actual = $this->db->QueryArray("SELECT * FROM `test_table` WHERE `id` = 1", MYSQLI_NUM);
        $this->assertEqualsCanonicalizing($expected, $actual);

        # 4
        $actual = $this->db->QueryArray("SELECT * FROM `test_table` WHERE `id` = 10");
        $this->assertFalse($actual);
    }

    public function testQuerySingleRow()
    {
        # 1
        $actual = $this->db->QuerySingleRow("SELECT * FROM `test_table`");
        $this->assertIsObject($actual);

        # 2
        $actual = $this->db->QuerySingleRow("SELECT * FROM `test_table` WHERE `id` = 10");
        $this->assertFalse($actual);
    }

    public function testQuerySingleRowArray()
    {
        # 1
        $expected = array("0"=>"1", "1"=>"John", "2"=>"2022-01-01", "3"=>"Red", "id"=>"1", "name"=>"John", "data"=>"2022-01-01", "value"=>"Red");
        $actual = $this->db->QuerySingleRowArray("SELECT * FROM `test_table`");
        $this->assertEqualsCanonicalizing($expected, $actual);

        # 2
        $expected = array("id"=>"1", "name"=>"John", "data"=>"2022-01-01", "value"=>"Red");
        $actual = $this->db->QuerySingleRowArray("SELECT * FROM `test_table`", MYSQLI_ASSOC);
        $this->assertEqualsCanonicalizing($expected, $actual);

        # 3
        $expected = array("0"=>"1", "1"=>"John", "2"=>"2022-01-01", "3"=>"Red");
        $actual = $this->db->QuerySingleRowArray("SELECT * FROM `test_table`", MYSQLI_NUM);
        $this->assertEqualsCanonicalizing($expected, $actual);

        # 4
        $actual = $this->db->QuerySingleRowArray("SELECT * FROM `test_table` WHERE `id` = 10");
        $this->assertFalse($actual);
    }

    public function testQuerySingleValue()
    {
        # 1
        $expected = "1";
        $actual = $this->db->QuerySingleValue("SELECT * FROM `test_table`");
        $this->assertSame($expected, $actual);

        # 2
        $expected = "John";
        $actual = $this->db->QuerySingleValue("SELECT name FROM `test_table`");
        $this->assertSame($expected, $actual);

        # 3
        $expected = "John";
        $actual = $this->db->QuerySingleValue("SELECT name FROM `test_table` WHERE `id` = 1");
        $this->assertSame($expected, $actual);

        # 4
        $actual = $this->db->QuerySingleValue("SELECT * FROM `test_table` WHERE `id` = 10");
        $this->assertFalse($actual);
    }
}
