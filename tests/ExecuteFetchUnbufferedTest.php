<?php
use PHPUnit\Framework\TestCase;

final class ExecuteFetchUnbufferedTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = new MySQL(true, "testdb", "127.0.0.1", "root", "root");
    }

    protected function tearDown(): void
    {
        if ($this->db->IsConnected()) {
            $this->db->Close();
        }
        parent::tearDown();
    }

    /** @test Execute with unbuffered mode */
    public function testExecuteUnbuffered(): void
    {
        // Test Execute in unbuffered mode (default in v5.0)
        $this->assertTrue($this->db->Prepare("SELECT * FROM test_table WHERE id = ?"));
        $this->assertTrue($this->db->BindParam(1, 'i'));
        $this->assertTrue($this->db->Execute());
        $this->assertSame(1, $this->db->RowCount());
        $this->assertTrue($this->db->CloseStatement());
    }

    /** @test Execute with buffered mode explicitly disabled */
    public function testExecuteWithBufferedDisabled(): void
    {
        // Force unbuffered mode
        $this->db->SetUnbufferedMode(true);
        $this->assertTrue($this->db->Prepare("SELECT * FROM test_table WHERE id = ?"));
        $this->assertTrue($this->db->BindParam(1, 'i'));
        $this->assertTrue($this->db->Execute());
        $this->assertTrue($this->db->CloseStatement());
    }

    /** @test Fetch with unbuffered result set */
    public function testFetchUnbuffered(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM test_table WHERE id = ?"));
        $this->assertTrue($this->db->BindParam(1, 'i'));
        $this->assertTrue($this->db->Execute());
        
        // Fetch should work with unbuffered results
        $row = $this->db->Fetch(MYSQLI_ASSOC);
        $this->assertIsArray($row);
        $this->assertSame(1, $row['id']); // id is integer
        $this->assertTrue($this->db->CloseStatement());
    }

    /** @test Fetch loop with unbuffered */
    public function testFetchLoopUnbuffered(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM test_table WHERE id IN (?, ?)"));
        $this->assertTrue($this->db->BindParams([1, 2], 'ii'));
        $this->assertTrue($this->db->Execute());
        
        $rows = [];
        while ($row = $this->db->Fetch(MYSQLI_ASSOC)) {
            $rows[] = $row;
        }
        $this->assertCount(2, $rows);
        $this->assertTrue($this->db->CloseStatement());
    }

    /** @test FetchAll with unbuffered */
    public function testFetchAllUnbuffered(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM test_table WHERE id IN (?, ?)"));
        $this->assertTrue($this->db->BindParams([1, 2], 'ii'));
        $this->assertTrue($this->db->Execute());
        
        $rows = $this->db->FetchAll(MYSQLI_ASSOC);
        $this->assertCount(2, $rows);
        $this->assertTrue($this->db->CloseStatement());
    }

    /** @test PreparedRowCount with unbuffered */
    public function testPreparedRowCountUnbuffered(): void
    {
        $this->assertTrue($this->db->Prepare("SELECT * FROM test_table WHERE id = ?"));
        $this->assertTrue($this->db->BindParam(1, 'i'));
        $this->assertTrue($this->db->Execute());
        
        $count = $this->db->PreparedRowCount();
        // On systems with mysqlnd, returns row count
        // On systems without, returns false with error
        if ($count === false) {
            $this->assertStringContainsString("mysqlnd", $this->db->Error());
        } else {
            $this->assertSame(1, $count);
        }
        $this->assertTrue($this->db->CloseStatement());
    }
}