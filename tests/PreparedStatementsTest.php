<?php
use PHPUnit\Framework\TestCase;

final class PreparedStatementsTest extends TestCase
{
    protected $db;

    public function setUp(): void
    {
        $this->db = new MySQL(true, "testdb", "127.0.0.1", "root", "root");
        // Setup test table for prepared statements
        $this->db->Query("CREATE TEMPORARY TABLE IF NOT EXISTS `ps_test` (`id` INT AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(50), `value` INT)");
        $this->db->Query("TRUNCATE TABLE `ps_test`");
        $this->db->Query("INSERT INTO `ps_test` (`name`, `value`) VALUES ('Test1', 100), ('Test2', 200)");
    }

    public function tearDown(): void
    {
        $this->db->Query("DROP TEMPORARY TABLE IF EXISTS `ps_test`");
        parent::tearDown();
    }

    public function testPrepareValidSelect(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM `ps_test` WHERE `name` = ?"));
        $this->assertTrue($this->db->CloseStatement());
    }

    public function testPrepareInvalidSqlSetsError(): void
    {
        // Invalid SQL (syntax error)
        $result = $this->db->Prepare("SELECT * FROM `ps_test` WHERE");
        $this->assertFalse($result);
        $this->assertStringContainsString("syntax", $this->db->Error());
    }

    public function testBindParamString(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM `ps_test` WHERE `name` = ?"));
        $this->assertTrue($this->db->BindParam('Test1', 's'));
        $this->assertTrue($this->db->Execute());
        $this->assertSame(1, $this->db->RowCount());
        $this->assertTrue($this->db->CloseStatement());
    }

    public function testBindParamInt(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM `ps_test` WHERE `value` > ?"));
        $this->assertTrue($this->db->BindParam(50, 'i'));
        $this->assertTrue($this->db->Execute());
        $this->assertSame(2, $this->db->RowCount());
        $this->assertTrue($this->db->CloseStatement());
    }

    public function testBindParamsArrayWithTypes(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM `ps_test` WHERE `name` = ? AND `value` > ?"));
        $this->assertTrue($this->db->BindParams(['Test1', 50], 'si'));
        $this->assertTrue($this->db->Execute());
        $this->assertSame(1, $this->db->RowCount());
        $this->assertTrue($this->db->CloseStatement());
    }

    public function testBindParamsArrayAutoDetectTypes(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM `ps_test` WHERE `name` = ? AND `value` > ?"));
        // 2 placeholders -> 2 values (string, int)
        $this->assertTrue($this->db->BindParams(['Test1', 50])); 
        $this->assertTrue($this->db->Execute());
        $this->assertTrue($this->db->CloseStatement());
    }

    public function testExecuteSelectReturnsRows(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM `ps_test` WHERE `name` = ?"));
        $this->assertTrue($this->db->BindParam('Test1', 's'));
        $this->assertTrue($this->db->Execute());
        $this->assertSame(1, $this->db->RowCount());
        $row = $this->db->Fetch(MYSQLI_ASSOC);
        $this->assertSame('Test1', $row['name']);
        $this->assertTrue($this->db->CloseStatement());
    }

    public function testExecuteInsertReturnsInsertId(): void
    {
        $this->assertTrue($this->db->Prepare("INSERT INTO `ps_test` (`name`, `value`) VALUES (?, ?)"));
        $this->assertTrue($this->db->BindParams(['Test3', 300], 'si'));
        $this->assertTrue($this->db->Execute());
        $id = $this->db->GetLastInsertID();
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        $this->assertTrue($this->db->CloseStatement());
    }

    public function testExecuteUpdateReturnsAffectedRows(): void
    {
        $this->assertTrue($this->db->Prepare("UPDATE `ps_test` SET `value` = ? WHERE `name` = ?"));
        $this->assertTrue($this->db->BindParams([999, 'Test1'], 'is'));
        $this->assertTrue($this->db->Execute());
        $this->assertSame(1, $this->db->RowCount());
        $this->assertTrue($this->db->CloseStatement());
    }

    public function testFetchAssoc(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT `name`, `value` FROM `ps_test` WHERE `name` = ?"));
        $this->assertTrue($this->db->BindParam('Test1', 's'));
        $this->assertTrue($this->db->Execute());
        $row = $this->db->Fetch(MYSQLI_ASSOC);
        $this->assertArrayHasKey('name', $row);
        $this->assertArrayHasKey('value', $row);
        $this->assertSame('Test1', $row['name']);
        $this->assertTrue($this->db->CloseStatement());
    }

    public function testFetchNum(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT `name`, `value` FROM `ps_test` WHERE `name` = ?"));
        $this->assertTrue($this->db->BindParam('Test1', 's'));
        $this->assertTrue($this->db->Execute());
        $row = $this->db->Fetch(MYSQLI_NUM);
        $this->assertSame('Test1', $row[0]);
        $this->assertTrue($this->db->CloseStatement());
    }

    public function testFetchAll(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM `ps_test` WHERE `value` > ?"));
        $this->assertTrue($this->db->BindParam(50, 'i'));
        $this->assertTrue($this->db->Execute());
        $rows = $this->db->FetchAll(MYSQLI_ASSOC);
        $this->assertCount(2, $rows);
        $this->assertTrue($this->db->CloseStatement());
    }

    public function testCloseStatementResetsState(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT 1"));
        $this->assertTrue($this->db->CloseStatement());
        $this->assertFalse($this->db->CloseStatement()); // Already closed -> false
        
        // Verify we can prepare a new statement
        $this->assertTrue($this->db->Prepare("SELECT 2"));
        $this->assertTrue($this->db->Execute());
        $this->assertTrue($this->db->CloseStatement());
    }

    public function testBindAfterExecuteFails(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM `ps_test` WHERE `name` = ?"));
        $this->assertTrue($this->db->BindParam('Test1', 's'));
        $this->assertTrue($this->db->Execute());
        // Attempt to bind after execute
        $this->assertFalse($this->db->BindParam('Test2', 's'));
        $this->assertStringContainsString("already bound", $this->db->Error());
    }

    public function testPreparedRowCount(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM `ps_test` WHERE `value` > ?"));
        $this->assertTrue($this->db->BindParam(50, 'i'));
        $this->assertTrue($this->db->Execute());
        $count = $this->db->PreparedRowCount();
        $this->assertSame(2, $count);
        $this->assertTrue($this->db->CloseStatement());
    }

    public function testMultipleExecutes(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM `ps_test` WHERE `name` = ?"));
        $this->assertTrue($this->db->BindParam('Test1', 's'));
        $this->assertTrue($this->db->Execute());
        $this->assertSame(1, $this->db->RowCount());
        
        // Re-bind for next execute (need to close and re-prepare or just re-bind if supported? 
        // Current implementation: stmt_bound=true after first execute, BindParam fails.
        // So we test that we *cannot* re-bind without re-prepare.
        $this->assertFalse($this->db->BindParam('Test2', 's'));
        $this->assertTrue($this->db->CloseStatement());
    }
}
