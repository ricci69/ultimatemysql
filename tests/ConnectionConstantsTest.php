<?php
use PHPUnit\Framework\TestCase;

final class ConnectionConstantsTest extends TestCase
{
    /** @test MYSQL_MAX_BUFFERED_ROWS constant is defined */
    public function testMysqlMaxBufferedRowsDefined(): void
    {
        $this->assertTrue(defined('MYSQL_MAX_BUFFERED_ROWS'));
        $this->assertIsInt(MYSQL_MAX_BUFFERED_ROWS);
        $this->assertGreaterThanOrEqual(0, MYSQL_MAX_BUFFERED_ROWS);
    }

    /** @test MYSQL_DEBUG_ANONIMIZATION constant is defined */
    public function testMysqlDebugAnonymizationDefined(): void
    {
        $this->assertTrue(defined('MYSQL_DEBUG_ANONIMIZATION'));
        $this->assertIsBool(MYSQL_DEBUG_ANONIMIZATION);
    }

    /** @test MYSQL_AUTO_DEBUG_DETECTION constant is defined */
    public function testMysqlAutoDebugDetectionDefined(): void
    {
        $this->assertTrue(defined('MYSQL_AUTO_DEBUG_DETECTION'));
        $this->assertIsBool(MYSQL_AUTO_DEBUG_DETECTION);
    }

    /** @test MYSQL_BOTH, MYSQL_NUM, MYSQL_ASSOC constants are defined */
    public function testMysqlConstantsDefined(): void
    {
        $this->assertTrue(defined('MYSQL_BOTH'));
        $this->assertTrue(defined('MYSQL_NUM'));
        $this->assertTrue(defined('MYSQL_ASSOC'));
        
        $this->assertSame(MYSQLI_BOTH, MYSQL_BOTH);
        $this->assertSame(MYSQLI_NUM, MYSQL_NUM);
        $this->assertSame(MYSQLI_ASSOC, MYSQL_ASSOC);
    }
}