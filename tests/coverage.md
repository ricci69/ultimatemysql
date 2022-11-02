# Test cases coverage of all library functions

## How to use
```console
user@pc:/var/www/html/ultimatemysql$ composer update
user@pc:/var/www/html/ultimatemysql$ ./vendor/bin/phpunit --testdox tests
```

## Groups of functions
**Actual coverage: 18/65** ![28%](https://progress-bar.dev/28)
  
***MYSQL***
- [x] MySQL __construct ([boolean $connect = true], [string $database = ""], [string $server = ""], [string $username = ""], [string $password = ""], [string $charset = ""])
- [x] void __destruct ()
- [x] object Returns Close ()
- [x] boolean IsConnected ()
- [x] boolean Open ([string $database = ""], [string $server = ""], [string $username = ""], [string $password = ""], [string $charset = ""], [boolean $pcon = false])
- [x] boolean Release ()


***BUILD***
- [x] string BuildSQLDelete (string $tableName, array $whereArray)
- [x] string BuildSQLInsert (string $tableName, array $valuesArray)
- [x] string BuildSQLSelect (string $tableName, [array $whereArray = null], [array/string $columns = null], [array/string $sortColumns = null], [boolean $sortAscending = true], [integer/string $limit = null])
- [x] string BuildSQLUpdate (string $tableName, array $valuesArray, [array $whereArray = null])
- [x] string BuildSQLWhereClause (array $whereArray)


***COLUMNS***
- [x] array GetColumnComments (string $table)
- [x] integer GetColumnCount ([string $table = ""])
- [x] string GetColumnDataType (string $column, [string $table = ""])
- [x] integer GetColumnID (string $column, [string $table = ""])
- [x] integer GetColumnLength (string $column, [string $table = ""])
- [x] integer GetColumnName (string $columnID, [string $table = ""])
- [x] array GetColumnNames ([string $table = ""])


***EXPORT***
- [ ] string GetHTML ([boolean $showCount = true], [string $styleTable = null], [string $styleHeader = null], [string $styleData = null])
- [ ] string GetJSON ()
- [ ] array GetTables ()
- [ ] boolean GetXML ()


***TIMER***
- [ ] Float TimerDuration ([integer $decimals = 4])
- [ ] void TimerStart ()
- [ ] void TimerStop ()
- [ ] object PHP QueryTimed (string $sql)


***QUERY***
- [ ] object PHP Query (string $sql)
- [ ] array QueryArray (string $sql, [integer $resultType = MYSQL_BOTH])
- [ ] object PHP QuerySingleRow (string $sql)
- [ ] array QuerySingleRowArray (string $sql, [integer $resultType = MYSQL_BOTH])
- [ ] mixed QuerySingleValue (string $sql)
- [ ] boolean AutoInsertUpdate (string $tableName, array $valuesArray, array $whereArray)
- [ ] integer InsertRow (string $tableName, array $valuesArray)
- [ ] boolean UpdateRows (string $tableName, array $valuesArray, [array $whereArray = null]) 
- [ ] boolean DeleteRows (string $tableName, [array $whereArray = null])
- [ ] integer GetLastInsertID ()
- [ ] string GetLastSQL ()
- [ ] string HasRecords ([string $sql = ""])
- [ ] object PHP Records ()
- [ ] Records RecordsArray ([integer $resultType = MYSQL_BOTH])
- [ ] object PHP Row ([integer $optional_row_number = null])
- [ ] array RowArray ([integer $optional_row_number = null], [integer $resultType = MYSQL_BOTH])
- [ ] integer RowCount ()
- [ ] boolean TruncateTable (string $tableName)
- [ ] boolean SelectDatabase (string $database, [string $charset = ""])
- [ ] boolean SelectRows (string $tableName, [array $whereArray = null], [array/string $columns = null], [array/string $sortColumns = null], [boolean $sortAscending = true], [integer/string $limit = null])
- [ ] boolean SelectTable (string $tableName)


***VALUES***
- [ ] boolean GetBooleanValue (any $value)
- [ ] boolean IsDate (date/string $value)
- [ ] string SQLBooleanValue (any $value, any $trueValue, any $falseValue, [string $datatype = self::SQLVALUE_TEXT])
- [ ] string SQLFix (string $value)
- [ ] string SQLUnfix (string $value)
- [ ] string SQLValue (any $value, [string $datatype = self::SQLVALUE_TEXT])


***ERRORI***
- [ ] string Error ()
- [ ] integer ErrorNumber ()
- [ ] void Kill ([mixed $message = ''])

***SEEK***
- [ ] boolean MoveFirst ()
- [ ] boolean MoveLast ()
- [ ] boolean BeginningOfSeek ()
- [ ] boolean EndOfSeek ()
- [ ] object Fetched Seek (integer $row_number)
- [ ] integer SeekPosition ()

***TRANSACTION***
- [ ] boolean TransactionBegin ()
- [ ] boolean TransactionEnd ()
- [ ] boolean TransactionRollback ()
