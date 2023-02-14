<?php
use PHPUnit\Framework\TestCase;

final class MYSQLTest extends TestCase
{
   
    public function testCanBeCreatedWithoutAutoConnection(): void
    {
        $this->assertInstanceOf(
            MySQL::class,
            $db = new MySQL(false)
        );

        $this->assertFalse($db->isConnected());
    }    

    public function testCanBeCreatedWithoutParameters(): void
    {
        $this->assertInstanceOf(
            MySQL::class,
            $db = new MySQL()
        );
        
        $this->assertFalse($db->isConnected());
    }   
    
    public function testCanBeCreatedWithOnlyDbName(): void
    {
        $this->assertInstanceOf(
            MySQL::class,
            $db = new MySQL(true,"testdb")
        );
        $this->assertFalse($db->isConnected());
    }       
    
    public function testCanBeCreatedAndConnected(): void
    {
        $this->assertInstanceOf(
            MySQL::class,
            $db = new MySQL(true,"testdb","127.0.0.1","root","root")
        );
        $this->assertTrue($db->isConnected());
    }      
    
    public function testConnectionCanBeClosed(): void
    {
        $this->assertInstanceOf(
            MySQL::class,
            $db = new MySQL(true,"testdb","127.0.0.1","root","root")
        );
        
        $this->assertTrue($db->Close());
    }   
    
}
