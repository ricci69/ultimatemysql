<?php
use PHPUnit\Framework\TestCase;

final class SecurityIdentifiersTest extends TestCase
{
    public function testEscapeIdentifierSimple(): void
    {
        $this->assertSame('`users`', MySQL::EscapeIdentifier('users'));
    }

    public function testEscapeIdentifierWithBackticks(): void
    {
        // Internal backticks are doubled (escaped)
        $this->assertSame('`user``name`', MySQL::EscapeIdentifier("user`name"));
    }

    public function testEscapeIdentifierRemovesOuterBackticks(): void
    {
        // Outer backticks removed, internal doubled
        $this->assertSame('`user``name`', MySQL::EscapeIdentifier("`user`name`"));
    }

    public function testEscapeIdentifierRejectsSemicolon(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MySQL::EscapeIdentifier("users; DROP TABLE users");
    }

    public function testEscapeIdentifierRejectsComment(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MySQL::EscapeIdentifier("users -- comment");
    }

    public function testEscapeIdentifierRejectsQuote(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MySQL::EscapeIdentifier("users' OR '1'='1");
    }

    public function testEscapeIdentifierRejectsSpace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MySQL::EscapeIdentifier("user name");
    }

    public function testBuildSqlSelectColumnInjection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MySQL::BuildSQLSelect("users", null, ["id`; DROP TABLE users; --"]);
    }

    public function testBuildSQLOrderByInjection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MySQL::BuildSQLSelect("users", null, null, "id; DROP TABLE users; --");
    }

    public function testBuildSQLWhereClauseColumnInjection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MySQL::BuildSQLWhereClause(["id` = 1; --" => "value"]);
    }

    public function testBuildSQLUpdateColumnInjection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MySQL::BuildSQLUpdate("users", ["email` = 'x' --" => "'test@test.com'"]);
    }

    public function testBuildSQLInsertColumnInjection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MySQL::BuildSQLInsert("users", ["id`; DROP TABLE users; --" => "1"]);
    }

    public function testValidIdentifiersWork(): void
    {
        $sql = MySQL::BuildSQLSelect("users", ["name" => "John"], ["id", "name", "email"], ["created_at"], true, 10);
        $this->assertStringContainsString("`name`", $sql);
        $this->assertStringContainsString("`created_at`", $sql);
    }
}
