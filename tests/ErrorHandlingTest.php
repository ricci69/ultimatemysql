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

    /** @test Kill method exists and is declared as never returning */
    public function testKillMethodExists(): void
    {
        $db = new MySQL(true, "testdb", "127.0.0.1", "root", "root");
        $ref = new ReflectionClass($db);
        $method = $ref->getMethod('Kill');
        $this->assertTrue($method->isPublic());

        $returnType = $method->getReturnType();
        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
        $this->assertTrue($returnType->isBuiltin());
        $this->assertSame('never', $returnType->getName());
    }

    /** @test SetThrowExceptions can be toggled on/off */
    public function testSetThrowExceptionsToggle(): void
    {
        $db = new MySQL(true, "testdb", "127.0.0.1", "root", "root");

        // Default is false
        $ref = new ReflectionClass($db);
        $prop = $ref->getProperty('ThrowExceptions');
        $this->assertFalse($prop->getValue($db));

        // Enable
        $db->SetThrowExceptions(true);
        $this->assertTrue($prop->getValue($db));

        // Disable
        $db->SetThrowExceptions(false);
        $this->assertFalse($prop->getValue($db));
    }

    /** @test Error() returns false when no error */
    public function testErrorReturnsFalseWhenNoError(): void
    {
        $db = new MySQL(true, "testdb", "127.0.0.1", "root", "root");
        $db->Query("SELECT 1");
        $this->assertFalse($db->Error());
    }

    /** @test ErrorNumber() returns false when no error */
    public function testErrorNumberReturnsFalseWhenNoError(): void
    {
        $db = new MySQL(true, "testdb", "127.0.0.1", "root", "root");
        $db->Query("SELECT 1");
        $this->assertFalse($db->ErrorNumber());
    }
}
