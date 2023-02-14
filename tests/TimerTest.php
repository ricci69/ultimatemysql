<?php
use PHPUnit\Framework\TestCase;

final class TimerTest extends TestCase
{
    protected $db;

    public function setUp(): void
    {
        $this->db = new MySQL(true,"testdb","127.0.0.1","root","root");
    }
    
    public function testQueryTimed()
    {
        $actual = $this->db->QueryTimed("SELECT * FROM test_table");
        $this->assertIsObject($actual);
    }
    
    public function testTimerDuration()
    {
        $this->db->QueryTimed("SELECT * FROM test_table"); 
        $format = "%f";
        
        $actual = $this->db->TimerDuration();        
        
        $this->assertStringMatchesFormat($format, $actual);
    }
    
    public function testTimerStartAndStop()
    {
        $this->db->TimerStart();
        $this->db->TimerStop();
        $format = "%f";
        
        $actual = $this->db->TimerDuration();        
        
        $this->assertStringMatchesFormat($format, $actual);
    }    
 
    public function testTimerStartAndStopWithQuery()
    {
        $this->db->TimerStart();
        $this->db->Query("SELECT * FROM test_table"); 
        $this->db->TimerStop();
        $format = "%f";
        
        $actual = $this->db->TimerDuration();        
        
        $this->assertStringMatchesFormat($format, $actual);
    }    
  
    public function testTimerStartAndStopWithQueryTimed()
    {
        $this->db->TimerStart();
        $this->db->QueryTimed("SELECT * FROM test_table"); 
        $this->db->TimerStop();
        $format = "%f";
        
        $actual = $this->db->TimerDuration();        
        
        $this->assertStringMatchesFormat($format, $actual);
    }    
 
}
