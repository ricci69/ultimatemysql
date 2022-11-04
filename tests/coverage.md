# Test cases coverage of all library functions

## How to use
First of all you need to create the test database
```sql
DROP DATABASE IF EXISTS `testdb`;
CREATE DATABASE IF NOT EXISTS `testdb`;
USE `testdb`;

CREATE TABLE `test_query` (
  `id` int NOT NULL,
  `key` varchar(25) NOT NULL,
  `value` varchar(50) NOT NULL
);

CREATE TABLE `test_table` (
  `id` int NOT NULL,
  `name` varchar(25) NOT NULL COMMENT 'It contains the name',
  `date` date NOT NULL,
  `value` varchar(15) NOT NULL
);

INSERT INTO `test_table` (`id`, `name`, `date`, `value`) VALUES (1, 'John', '2022-01-01', 'Red');
INSERT INTO `test_table` (`id`, `name`, `date`, `value`) VALUES (2, 'John2', '2022-06-01', 'Yellow');

ALTER TABLE `test_query`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `test_table`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `test_query`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `test_table`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
```

OR, just type

```console
mysql -uroot -p < tests/db.sql
```

after this, you can use PHPUnit

```console
user@pc:/var/www/html/ultimatemysql$ composer update
user@pc:/var/www/html/ultimatemysql$ ./vendor/bin/phpunit --testdox tests
```

## Groups of functions
**Actual coverage: 65/65** ![100%](https://progress-bar.dev/100)
  
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
- [x] array GetTables ()

***EXPORT***
- [x] string GetHTML ([boolean $showCount = true], [string $styleTable = null], [string $styleHeader = null], [string $styleData = null])
- [x] string GetJSON ()
- [x] boolean GetXML ()


***TIMER***
- [x] Float TimerDuration ([integer $decimals = 4])
- [x] void TimerStart ()
- [x] void TimerStop ()
- [x] object PHP QueryTimed (string $sql)


***QUERY***
- [x] object PHP Query (string $sql)
- [x] array QueryArray (string $sql, [integer $resultType = MYSQL_BOTH])
- [x] object PHP QuerySingleRow (string $sql)
- [x] array QuerySingleRowArray (string $sql, [integer $resultType = MYSQL_BOTH])
- [x] mixed QuerySingleValue (string $sql)
- [x] boolean AutoInsertUpdate (string $tableName, array $valuesArray, array $whereArray)
- [x] integer InsertRow (string $tableName, array $valuesArray)
- [x] boolean UpdateRows (string $tableName, array $valuesArray, [array $whereArray = null]) 
- [x] boolean DeleteRows (string $tableName, [array $whereArray = null])
- [x] integer GetLastInsertID ()
- [x] string GetLastSQL ()
- [x] string HasRecords ([string $sql = ""])
- [x] object PHP Records ()
- [x] Records RecordsArray ([integer $resultType = MYSQL_BOTH])
- [x] object PHP Row ([integer $optional_row_number = null])
- [x] array RowArray ([integer $optional_row_number = null], [integer $resultType = MYSQL_BOTH])
- [x] integer RowCount ()
- [x] boolean TruncateTable (string $tableName)
- [x] boolean SelectDatabase (string $database, [string $charset = ""])
- [x] boolean SelectRows (string $tableName, [array $whereArray = null], [array/string $columns = null], [array/string $sortColumns = null], [boolean $sortAscending = true], [integer/string $limit = null])
- [x] boolean SelectTable (string $tableName)


***VALUES***
- [x] boolean GetBooleanValue (any $value)
- [x] boolean IsDate (date/string $value)
- [x] string SQLBooleanValue (any $value, any $trueValue, any $falseValue, [string $datatype = self::SQLVALUE_TEXT])
- [x] string SQLFix (string $value)
- [x] string SQLValue (any $value, [string $datatype = self::SQLVALUE_TEXT])


***ERRORS***
- [x] string Error ()
- [x] integer ErrorNumber ()

***SEEK***
- [x] boolean MoveFirst ()
- [x] boolean MoveLast ()
- [x] boolean BeginningOfSeek ()
- [x] boolean EndOfSeek ()
- [x] object Fetched Seek (integer $row_number)
- [x] integer SeekPosition ()

***TRANSACTIONS***
- [x] boolean TransactionBegin ()
- [x] boolean TransactionEnd ()
- [x] boolean TransactionRollback ()
