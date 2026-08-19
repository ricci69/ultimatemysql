<?php
use PHPUnit\Framework\TestCase;

final class NextResultTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = new MySQL(true, "testdb", "127.0.0.1", "root", "root");
    }

    protected function tearDown(): void
    {
        if ($this->db->IsConnected()) {
            // Release any active result before closing
            $ref = new ReflectionClass($this->db);
            $prop = $ref->getProperty('last_result');
            $lastResult = $prop->getValue($this->db);

            if (is_object($lastResult) && $lastResult !== $this->db) {
                try {
                    @mysqli_free_result($lastResult);
                } catch (\Throwable) {}
            }

            // Set last_result to null to prevent double-free in destructor
            $prop->setValue($this->db, null);

            $this->db->Close();
        }
        parent::tearDown();
    }

    /** @test NextResult returns false when no more results */
    public function testNextResultNoMoreResults(): void
    {
        $this->db->Query("SELECT 1 AS test");
        $result = $this->db->NextResult();
        $this->assertFalse($result);
    }

    /** @test NextResult works with multi-query via mysqli_multi_query */
    public function testNextResultWithMultiQuery(): void
    {
        // Get underlying mysqli connection via reflection
        $ref = new ReflectionClass($this->db);
        $prop = $ref->getProperty('mysql_link');
        $link = $prop->getValue($this->db);
        $this->assertIsObject($link);

        // Use multi_query directly
        $multiSql = "SELECT 1 AS a; SELECT 2 AS b; SELECT 3 AS c";
        $result = mysqli_multi_query($link, $multiSql);
        $this->assertTrue($result);

        // First result
        $firstResult = mysqli_store_result($link);
        $this->assertIsObject($firstResult);
        $row = mysqli_fetch_assoc($firstResult);
        $this->assertSame('1', $row['a']);
        mysqli_free_result($firstResult);

        // Next result
        $hasNext = $this->db->NextResult();
        $this->assertTrue($hasNext);
        $secondResult = $this->db->Records();
        $this->assertIsObject($secondResult);
        $row = mysqli_fetch_assoc($secondResult);
        $this->assertSame('2', $row['b']);
        mysqli_free_result($secondResult);

        // Third result
        $hasNext = $this->db->NextResult();
        $this->assertTrue($hasNext);
        $thirdResult = $this->db->Records();
        $this->assertIsObject($thirdResult);
        $row = mysqli_fetch_assoc($thirdResult);
        $this->assertSame('3', $row['c']);
        mysqli_free_result($thirdResult);

        // No more results
        $hasNext = $this->db->NextResult();
        $this->assertFalse($hasNext);
    }

    /** @test NextResult handles error in multi-query */
    public function testNextResultWithError(): void
    {
        $ref = new ReflectionClass($this->db);
        $prop = $ref->getProperty('mysql_link');
        $link = $prop->getValue($this->db);
        $this->assertIsObject($link);

        // Multi-query with error in second statement
        $multiSql = "SELECT 1 AS a; SELECT * FROM non_existent_table; SELECT 3 AS c";
        $result = mysqli_multi_query($link, $multiSql);
        $this->assertTrue($result);

        // First result
        $firstResult = mysqli_store_result($link);
        $this->assertIsObject($firstResult);
        mysqli_free_result($firstResult);

        // Next result should fail
        try {
            $hasNext = $this->db->NextResult();
            $this->assertFalse($hasNext);
            $this->assertNotEmpty($this->db->Error());
        } catch (mysqli_sql_exception $e) {
            // If exception is thrown, that's also valid behavior
            $this->assertStringContainsString("doesn't exist", $e->getMessage());
        }
    }

    /** @test NextResult returns false when not connected */
    public function testNextResultNoConnection(): void
    {
        $this->db->Close();
        $result = $this->db->NextResult();
        $this->assertFalse($result);
        $this->assertStringContainsString("No connection", $this->db->Error());
    }
}