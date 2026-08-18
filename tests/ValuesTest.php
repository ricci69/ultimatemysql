<?php
use PHPUnit\Framework\TestCase;

// Load MySQL class if not already loaded by autoloader
if (!class_exists('MySQL')) {
    require_once __DIR__ . '/../src/mysql.class.php';
}

final class ValuesTest extends TestCase
{
    protected $db;
    private int $originalErrorReporting;

    public function setUp(): void
    {
        // Silence deprecated warnings (E_USER_DEPRECATED, E_DEPRECATED) for clean output
        // Tests verify legacy behavior, we don't want noise to fail them
        $this->originalErrorReporting = error_reporting();
        error_reporting($this->originalErrorReporting & ~E_USER_DEPRECATED & ~E_DEPRECATED);

        $this->db = new MySQL(true, "testdb", "127.0.0.1", "root", "root");
    }

    public function tearDown(): void
    {
        // Restore original error_reporting
        error_reporting($this->originalErrorReporting);
        parent::tearDown();
    }
    
    public function testGetBooleanValue()
    {
        $this->assertTrue(MySQL::GetBooleanValue("Y"));
        $this->assertFalse(MySQL::GetBooleanValue("no"));
        $this->assertTrue(MySQL::GetBooleanValue("TRUE"));
        $this->assertTrue(MySQL::GetBooleanValue("1"));
        $this->assertTrue(MySQL::GetBooleanValue("SELECTED"));
    }
    
    public function testIsDate()
    {
        // IsDate is deprecated: test legacy behavior without expectDeprecation()
        $this->assertTrue(MySQL::IsDate("January 1, 2000"));
        $this->assertTrue(MySQL::IsDate("today"));
        $this->assertFalse(MySQL::IsDate("blue"));
    }    
 
    public function testSqlBooleanValue()
    {
        // false with NUMBER type -> "0"
        $this->assertSame("0", MySQL::SQLBooleanValue(false, "1", "0", MySQL::SQLVALUE_NUMBER));
        
        // true with DATE type -> uses DateTime object (not locale-dependent strings)
        $this->assertSame("'2022-01-01'", MySQL::SQLBooleanValue(true, new DateTime('2022-01-01'), "2020/01/01", MySQL::SQLVALUE_DATE));
        
        // "ON" is true -> uses trueValue "Yes" as TEXT (default)
        $this->assertSame("'Yes'", MySQL::SQLBooleanValue("ON", "Yes", "No"));
        
        // 0 is false -> uses falseValue "-" as TEXT
        $this->assertSame("'-'", MySQL::SQLBooleanValue(0, '+', '-')); 
    }
    
    public function testSqlValue()
    {
        // 1: Escape single quotes in TEXT
        $this->assertSame("'it''s a string'", MySQL::SQLValue("it's a string", "text"));
        
        // 2: Default datatype is SQLVALUE_TEXT ('text')
        $this->assertSame("'it''s a string'", MySQL::SQLValue("it's a string"));
        
        // 3: Alias 'string' and 'char' equal 'text'
        $this->assertSame("'it''s a string'", MySQL::SQLValue("it's a string", "string"));
        $this->assertSame("'it''s a string'", MySQL::SQLValue("it's a string", "char"));
        
        // 4: 'varchar' equals 'text'
        $this->assertSame("'it''s a string'", MySQL::SQLValue("it's a string", "varchar"));
        
        // 5: Constant SQLVALUE_TEXT
        $this->assertSame("'it''s a string'", MySQL::SQLValue("it's a string", MySQL::SQLVALUE_TEXT));
        
        // 6: Constant SQLVALUE_NUMBER -> no quotes for valid numbers
        $this->assertSame("1", MySQL::SQLValue("1", MySQL::SQLVALUE_NUMBER));
        
        // 7: Usage in concatenated query
        $expected = "SELECT * FROM test_table WHERE id = 1";
        $actual = "SELECT * FROM test_table WHERE id = " . MySQL::SQLValue("1", MySQL::SQLVALUE_NUMBER);
        $this->assertSame($expected, $actual);  
        
        // 8: DATE type with DateTime object (fixed for BUG-021)
        $expected = "UPDATE test_table SET value = '2007-07-04'";
        $actual =  "UPDATE test_table SET value = " . MySQL::SQLValue(new DateTime('2007-07-04'), MySQL::SQLVALUE_DATE);  
        $this->assertSame($expected, $actual);
        
        // 9: Empty string and null become NULL in SQL (TEXT type)
        $this->assertSame("NULL", MySQL::SQLValue("", "text"));
        $this->assertSame("NULL", MySQL::SQLValue(null, MySQL::SQLVALUE_TEXT));
    }
    
    public function testSqlFix()
    {
        // SQLFix requires active connection (setUp creates it)
        // mysqli_real_escape_string doubles backslashes: \ -> \\
        // Input in memory: \hello\ /world/
        // Expected in memory: \\hello\\ /world/
        // In PHP code (double quotes): "\\\\hello\\\\ /world/"
        $input = "\hello\ /world/"; 
        $expected = "\\\\hello\\\\ /world/"; 
        $actual = $this->db->SQLFix($input);
        $this->assertSame($expected, $actual);  
    }
 
}
