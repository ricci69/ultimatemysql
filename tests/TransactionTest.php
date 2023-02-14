<?php
use PHPUnit\Framework\TestCase;

final class TransactionTest extends TestCase
{
    protected $db;

    public function setUp(): void
    {
        $this->db = new MySQL(true,"testdb","127.0.0.1","root","root");
    }
    
    public function testTransactionBegin()
    {
        $this->assertTrue($this->db->TransactionBegin());
    }

    public function testTransactionAlreadyStarted()
    {

        $this->assertTrue($this->db->TransactionBegin());
        $this->assertFalse($this->db->TransactionBegin());

        $expected = "Already in transaction";
        $actual = $this->db->Error();
        $this->assertSame($expected, $actual);
    }
    
    public function testTransactionEnd()
    {
        $this->assertTrue($this->db->TransactionBegin());
        $this->assertTrue($this->db->TransactionEnd());
    }

    public function testTransactionNotStarted()
    {
        $this->assertFalse($this->db->TransactionEnd());

        $expected = "Not in a transaction";
        $actual = $this->db->Error();
        $this->assertSame($expected, $actual);
    }

    public function testRollbackWithoutTransaction()
    {
        $this->assertFalse($this->db->TransactionRollback());

        $expected = "Not in a transaction";
        $actual = $this->db->Error();
        $this->assertSame($expected, $actual);
    }

    public function testRollbackTransactionAndClose()
    {
        $this->assertTrue($this->db->TransactionBegin());
        $this->assertTrue($this->db->TransactionRollback());
        $this->assertFalse($this->db->TransactionEnd());

        $expected = "Not in a transaction";
        $actual = $this->db->Error();
        $this->assertSame($expected, $actual);
    }

    public function testRollbackDeletedRow()
    {
        $expected = $this->db->QuerySingleValue("SELECT `name` FROM `test_table` WHERE `id`=1");

        $this->assertTrue($this->db->TransactionBegin());
        $this->assertTrue($this->db->DeleteRows("test_table", array("id = 1")));
        $this->assertTrue($this->db->TransactionRollback());

        $actual = $this->db->QuerySingleValue("SELECT `name` FROM `test_table` WHERE `id`=1");

        $this->assertSame($expected, $actual);
    }

}
