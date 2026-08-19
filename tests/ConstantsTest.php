<?php
use PHPUnit\Framework\TestCase;

final class ConstantsTest extends TestCase
{
    /** @test MYSQL_* constants are defined */
    public function testMysqlConstantsDefined(): void
    {
        $this->assertTrue(defined('MYSQL_BOTH'));
        $this->assertTrue(defined('MYSQL_NUM'));
        $this->assertTrue(defined('MYSQL_ASSOC'));
        $this->assertTrue(defined('MYSQL_AUTO_DEBUG_DETECTION'));
        $this->assertTrue(defined('MYSQL_MAX_BUFFERED_ROWS'));
        $this->assertTrue(defined('MYSQL_DEBUG_ANONIMIZATION'));
    }

    /** @test MYSQL_MAX_BUFFERED_ROWS default value */
    public function testMysqlMaxBufferedRowsDefault(): void
    {
        $this->assertSame(50000, MYSQL_MAX_BUFFERED_ROWS);
    }

    /** @test MYSQL_DEBUG_ANONIMIZATION default value */
    public function testMysqlDebugAnonimizationDefault(): void
    {
        $this->assertFalse(MYSQL_DEBUG_ANONIMIZATION);
    }

    /** @test MYSQL_AUTO_DEBUG_DETECTION default value */
    public function testMysqlAutoDebugDetectionDefault(): void
    {
        $this->assertTrue(MYSQL_AUTO_DEBUG_DETECTION);
    }
}