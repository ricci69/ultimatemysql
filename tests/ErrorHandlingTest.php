<?php
use PHPUnit\Framework\TestCase;

final class ErrorHandlingTest extends TestCase
{
    /** @test SetThrowExceptions(true) throws RuntimeException on error */
    public function testThrowExceptionsMode(): void
    {
        $db = new MySQL(true, "testdb", "127.0.0.1", "root", "root");
        $db->SetThrowExceptions(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Table 'testdb.NonExistentTable' doesn't exist");
        
        $db->Query("SELECT * FROM NonExistentTable");
    }

    /** @test SetThrowExceptions(false) (default) doesn't throw, returns false */
    public function testDefaultNoThrow(): void
    {
        $db = new MySQL(true, "testdb", "127.0.0.1", "root", "root");
        $result = $db->Query("SELECT * FROM NonExistentTable");
        $this->assertFalse($result);
        $this->assertStringContainsString("doesn't exist", $db->Error());
    }
}
