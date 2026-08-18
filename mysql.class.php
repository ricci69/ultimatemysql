<?php declare(strict_types=1);

/**
 * Ultimate MySQL Wrapper Class v5.0
 *
 * @version 5.0
 * @link https://github.com/ricci69/ultimatemysql/
 *
 * CHANGES v5.0:
 * 1. FIXED: Prepared Statement fallback (no mysqlnd) no longer calls mysqli_stmt_store_result().
 *    This was the primary cause of "tried to allocate 300MB+" OOM on large SELECTs.
 *    store_result() buffers entire result set in PHP RAM. metadata (result_metadata) works without it.
 *    Fetch() works via bind_result/fetch loop without store_result.
 *    RowCount() correctly throws error guiding to COUNT(*) or iteration.
 * 2. CHANGED: Default $forceBufferedResults = false (Unbuffered Mode ON by default).
 *    Aligns with v4.7.1 recommendation. Buffered mode must be explicitly requested per query or via SetUnbufferedMode(false).
 * 3. IMPROVED: Added memory safety guards in RecordsArray(), GetJSON(), FetchAll() to prevent silent OOM.
 *    Throws RuntimeException if result set exceeds 50k rows (configurable via constant) when buffering.
 * 4. SECURITY: Debug logging sanitizeSqlForLog() limit reduced to 64KB for extra safety.
 * 5. ADDED: MYSQL_DEBUG_ANONIMIZATION constant to control SQL logging verbosity (raw vs anonymized).
 */

if (!defined('MYSQL_BOTH')) {
    define('MYSQL_BOTH', MYSQLI_BOTH);
}
if (!defined('MYSQL_NUM')) {
    define('MYSQL_NUM', MYSQLI_NUM);
}
if (!defined('MYSQL_ASSOC')) {
    define('MYSQL_ASSOC', MYSQLI_ASSOC);
}

if (!defined('MYSQL_AUTO_DEBUG_DETECTION')) {
    define('MYSQL_AUTO_DEBUG_DETECTION', true);
}

/**
 * Safety limit for "Load All" methods (RecordsArray, GetJSON, FetchAll, GetHTML, GetXML).
 * Prevents accidental OOM on large tables. Set to 0 to disable (NOT RECOMMENDED).
 * Override in bootstrap: define('MYSQL_MAX_BUFFERED_ROWS', 100000);
 */
if (!defined('MYSQL_MAX_BUFFERED_ROWS')) {
    define('MYSQL_MAX_BUFFERED_ROWS', 50000);
}

/**
 * Enable SQL anonymization in debug logs (sanitizeSqlForLog).
 * If FALSE (default), logs raw SQL exactly as executed.
 * If TRUE, replaces literals (strings, numbers, hex, nulls) with '?' and strips comments.
 * 
 * Override in bootstrap: define('MYSQL_DEBUG_ANONIMIZATION', true);
 */
if (!defined('MYSQL_DEBUG_ANONIMIZATION')) {
    define('MYSQL_DEBUG_ANONIMIZATION', false);
}

final class MySQL
{
    /**
     * SQL Value Type: Text, Varchar, Char, etc. (escaped string)
     */
    public const string SQLVALUE_TEXT      = 'text';
    /**
     * SQL Value Type: Integer, Int, BigInt, etc. (raw number)
     */
    public const string SQLVALUE_NUMBER    = 'integer';
    /**
     * SQL Value Type: Date (YYYY-MM-DD)
     */
    public const string SQLVALUE_DATE      = 'date';
    /**
     * SQL Value Type: Datetime (YYYY-MM-DD HH:MM:SS)
     */
    public const string SQLVALUE_DATETIME  = 'datetime';
    /**
     * SQL Value Type: Time (HH:MM:SS)
     */
    public const string SQLVALUE_TIME      = 'time';
    /**
     * SQL Value Type: Boolean (1 or 0)
     */
    public const string SQLVALUE_BOOLEAN   = 'boolean';
    /**
     * SQL Value Type: Boolean ('Y' or 'N')
     */
    public const string SQLVALUE_YN        = 'y-n';
    /**
     * SQL Value Type: Boolean ('T' or 'F')
     */
    public const string SQLVALUE_TF        = 't-f';
    /**
     * SQL Value Type: Bit (1 or 0)
     */
    public const string SQLVALUE_BIT       = 'bit';

    private bool $autoEscapeValues = false;
    private static bool $globalAutoEscapeValues = false;

    // CHANGED v4.7.2: Default TRUE (Unbuffered). Saves memory by default.
    private bool $forceBufferedResults = false;

    private bool $autoReconnect = false;

    /** @var string */
    private readonly string $db_host;
    /** @var string */
    private readonly string $db_user;
    /** @var string */
    private readonly string $db_pass;
    /** @var string */
    private readonly string $db_dbname;
    /** @var string */
    private readonly string $db_charset;
    /** @var bool */
    private readonly bool $db_pcon;

    /** @var mysqli|null */
    private ?mysqli $mysql_link = null;
    /** @var int */
    private int $active_row = -1;
    /** @var string */
    private string $error_desc = "";
    /** @var int */
    private int $error_number = 0;
    /** @var bool */
    private bool $in_transaction = false;
    /** @var int|string|false */
    private int|string|false $last_insert_id = false;
    /** @var mysqli_result|mysqli_stmt|bool|null */
    private mysqli_result|mysqli_stmt|bool|null $last_result = null;
    /** @var string */
    private string $last_sql = "";
    /** @var float */
    private float $time_diff = 0.0;
    /** @var float */
    private float $time_start = 0.0;
    /** @var string */
    private string $debug = "";
    /** @var string */
    private string $debug_path = "";

    /** @var mysqli_stmt|null */
    private ?mysqli_stmt $stmt = null;
    /** @var array */
    private array $stmt_params = [];
    /** @var string */
    private string $stmt_param_types = "";
    /** @var bool */
    private bool $stmt_bound = false;
    /** @var mysqli_result|null */
    private ?mysqli_result $stmt_result = null;
    /** @var mysqli_result|null */
    private ?mysqli_result $stmt_meta = null;
    /** @var bool */
    private bool $warned_no_mysqlnd = false;

    private static array $typeMap = [
        MYSQLI_TYPE_TINY        => 'tinyint',
        MYSQLI_TYPE_SHORT       => 'smallint',
        MYSQLI_TYPE_LONG        => 'int',
        MYSQLI_TYPE_INT24       => 'mediumint',
        MYSQLI_TYPE_LONGLONG    => 'bigint',
        MYSQLI_TYPE_DECIMAL     => 'decimal',
        MYSQLI_TYPE_NEWDECIMAL  => 'decimal',
        MYSQLI_TYPE_FLOAT       => 'float',
        MYSQLI_TYPE_DOUBLE      => 'double',
        MYSQLI_TYPE_BIT         => 'bit',
        MYSQLI_TYPE_TIMESTAMP   => 'timestamp',
        MYSQLI_TYPE_DATE        => 'date',
        MYSQLI_TYPE_TIME        => 'time',
        MYSQLI_TYPE_DATETIME    => 'datetime',
        MYSQLI_TYPE_YEAR        => 'year',
        MYSQLI_TYPE_NEWDATE     => 'date',
        MYSQLI_TYPE_ENUM        => 'enum',
        MYSQLI_TYPE_SET         => 'set',
        MYSQLI_TYPE_TINY_BLOB   => 'tinyblob',
        MYSQLI_TYPE_MEDIUM_BLOB => 'mediumblob',
        MYSQLI_TYPE_LONG_BLOB   => 'longblob',
        MYSQLI_TYPE_BLOB        => 'blob',
        MYSQLI_TYPE_VAR_STRING  => 'varchar',
        MYSQLI_TYPE_STRING      => 'char',
        MYSQLI_TYPE_GEOMETRY    => 'geometry',
        MYSQLI_TYPE_JSON        => 'json',
    ];

    protected bool $ThrowExceptions = false;

    /**
     * Constructs the MySQL wrapper instance.
     *
     * @param bool $connect Whether to connect immediately.
     * @param string $database Database name.
     * @param string $server Hostname or IP.
     * @param string $username Username.
     * @param string $password Password.
     * @param string $charset Character set (default utf8mb4).
     * @param bool $persistent Use persistent connection.
     */
    public function __construct(
        bool $connect = true,
        string $database = '',
        string $server = 'localhost',
        string $username = '',
        string $password = '',
        string $charset = 'utf8mb4',
        bool $persistent = false
    ) {
        $this->db_host = $server;
        $this->db_user = $username;
        $this->db_pass = $password;
        $this->db_dbname = $database;
        $this->db_charset = $charset;
        $this->db_pcon = $persistent;

        if ($connect && $this->db_host !== '' && $this->db_user !== '') {
            $this->Open();
        }

        if (MYSQL_AUTO_DEBUG_DETECTION) {
            if (file_exists(dirname(__DIR__) . "/.debugmysql")) {
                $this->debug = dirname(__DIR__) . "/.debugmysql";
            } elseif (file_exists(dirname(__DIR__) . "/ultimatemysql/.debugmysql")) {
                $this->debug = dirname(__DIR__) . "/ultimatemysql/.debugmysql";
            }
        }

        $this->autoEscapeValues = self::$globalAutoEscapeValues;
    }

    /**
     * Destructor: closes the connection.
     */
    public function __destruct()
    {
        try {
            $this->Close();
        } catch (Exception $e) {
        }
    }

    /**
     * Sets auto-escape mode for this instance.
     *
     * @param bool $flag True to enable auto-escaping in BuildSQL* helpers.
     */
    public function SetAutoEscapeValues(bool $flag): void
    {
        $this->autoEscapeValues = $flag;
    }

    /**
     * Sets global auto-escape mode for all new instances.
     *
     * @param bool $flag True to enable auto-escaping globally.
     */
    public static function SetGlobalAutoEscapeValues(bool $flag): void
    {
        self::$globalAutoEscapeValues = $flag;
    }

    /**
     * Gets the current auto-escape mode.
     *
     * @return bool True if auto-escape is enabled.
     */
    private function getAutoEscapeMode(): bool
    {
        return $this->autoEscapeValues;
    }

    /**
     * Sets unbuffered (streaming) mode for SELECT queries.
     *
     * @param bool $flag True for UNBUFFERED (streaming, default), False for BUFFERED.
     */
    public function SetUnbufferedMode(bool $flag): void
    {
        $this->forceBufferedResults = !$flag;
    }

    /**
     * Enables or disables automatic reconnection on connection loss.
     *
     * @param bool $flag True to enable auto-reconnect.
     */
    public function SetAutoReconnect(bool $flag): void
    {
        $this->autoReconnect = $flag;
    }

    /**
     * Ensures a valid database connection, reconnecting if necessary and allowed.
     *
     * @return bool True if connected, false otherwise.
     */
    private function ensureConnected(): bool
    {
        if ($this->IsConnected()) {
            if (@mysqli_ping($this->mysql_link)) {
                return true;
            }
            $this->mysql_link = null;
        }

        if (!$this->in_transaction && $this->autoReconnect) {
            return $this->Open();
        }

        $this->SetError("Connection lost and auto-reconnect is disabled", -1);
        return false;
    }

    /**
     * Sets the debug log file path.
     *
     * @param string $path Absolute path to the debug log file.
     * @throws InvalidArgumentException If path is not absolute or directory not writable.
     */
    public function SetDebugPath(string $path): void
    {
        if ($path === '' || $path[0] !== '/') {
            throw new InvalidArgumentException("Debug path must be absolute");
        }
        $dir = dirname($path);
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new InvalidArgumentException("Debug directory not writable: $dir");
        }
        $webroots = ['/var/www', '/var/www/html', '/usr/share/nginx/html', '/home/*/public_html', '/home/*/www'];
        foreach ($webroots as $root) {
            if (strpos($path, $root) === 0) {
                trigger_error("MySQL::SetDebugPath: Debug file '$path' appears to be in webroot ($root). Security risk!", E_USER_WARNING);
                break;
            }
        }
        $this->debug_path = $path;
        $this->debug = $path;
    }

    /**
     * Enables or disables throwing exceptions on database errors.
     *
     * @param bool $flag True to throw RuntimeException on errors.
     */
    public function SetThrowExceptions(bool $flag): void
    {
        $this->ThrowExceptions = $flag;
    }

    /**
     * Automatically performs an INSERT or UPDATE based on record existence.
     * Uses a transaction with SELECT ... FOR UPDATE to prevent race conditions.
     *
     * @param string $tableName The table name.
     * @param array $valuesArray Associative array of column=>value data to insert/update.
     * @param array $whereArray Associative array of column=>value for the WHERE clause (required).
     * @return int|bool Returns INSERT ID on new record, TRUE on update, FALSE on error.
     */
    public function AutoInsertUpdate(string $tableName, array $valuesArray, array $whereArray): int|bool
    {
        $this->ResetError();
        if ($tableName === '') { $this->SetError("Table name cannot be empty", -1); return false; }
        if ($valuesArray === []) { $this->SetError("Values array cannot be empty", -1); return false; }
        if (empty($whereArray)) {
            $this->SetError("AutoInsertUpdate requires non-empty whereArray to prevent accidental mass updates.", -1);
            return false;
        }

        if (!$this->TransactionBegin()) return false;

        try {
            $selectSql = self::BuildSQLSelect($tableName, $whereArray, null, null, true, 1, null, $this->getAutoEscapeMode()) . " FOR UPDATE";

            if (!$this->Query($selectSql)) {
                $this->TransactionRollback();
                return false;
            }

            $exists = $this->RowCount() > 0;

            if ($exists) {
                $updateSql = self::BuildSQLUpdate($tableName, $valuesArray, $whereArray, $this->getAutoEscapeMode());
                if (!$this->Query($updateSql)) {
                    $this->TransactionRollback();
                    return false;
                }
                $this->TransactionEnd();
                return true;
            } else {
                $insertData = $valuesArray;
                foreach ($whereArray as $k => $v) {
                    if (!array_key_exists($k, $insertData)) $insertData[$k] = $v;
                }
                $insertSql = self::BuildSQLInsert($tableName, $insertData, $this->getAutoEscapeMode());
                if (!$this->Query($insertSql)) {
                    $this->TransactionRollback();
                    return false;
                }
                $insertId = $this->GetLastInsertID();
                $this->TransactionEnd();
                return $insertId;
            }
        } catch (Exception $e) {
            $this->TransactionRollback();
            $this->SetError($e->getMessage(), -1);
            return false;
        }
    }

    /**
     * Checks if the internal result pointer is at the first row (index 0).
     *
     * @return bool True if at the beginning of the result set.
     */
    public function BeginningOfSeek(): bool
    {
        $this->ResetError();
        return $this->IsConnected() && $this->active_row === 0;
    }

    /**
     * Escapes a database identifier (table/column name) with backticks.
     *
     * @param string $identifier The identifier to escape.
     * @return string The escaped identifier (e.g., `table`).
     * @throws InvalidArgumentException If identifier is empty or contains forbidden chars.
     */
    public static function EscapeIdentifier(string $identifier): string
    {
        if (!is_string($identifier) || $identifier === '') {
            throw new InvalidArgumentException("Identifier must be a non-empty string");
        }

        $trimmed = trim($identifier, '`');

        if (preg_match('/[;\'"\s\(\)\*\/\-\+]/', $trimmed) || stripos($trimmed, '--') !== false) {
            throw new InvalidArgumentException("Invalid identifier '" . $identifier . "': contains forbidden characters");
        }

        $escaped = str_replace('`', '``', $trimmed);
        return "`$escaped`";
    }

    /**
     * Builds a comma-separated column list for SQL queries, optionally with aliases.
     *
     * @param array|string $columns Column names or associative array [alias => column].
     * @param bool $addQuotes Whether to wrap identifiers in backticks.
     * @param bool $showAlias Whether to append "AS alias" for string keys.
     * @return string Formatted column list string.
     */
    private static function BuildSQLColumns(array|string $columns, bool $addQuotes = true, bool $showAlias = true): string
    {
        $quote = $addQuotes ? "`" : "";

        if (is_array($columns)) {
            $sql = "";
            foreach ($columns as $key => $value) {
                $safeValue = $addQuotes ? self::EscapeIdentifier($value) : $value;
                $sql .= (strlen($sql) === 0 ? "" : ", ") . $safeValue;
                if ($showAlias && is_string($key) && $key !== '') {
                    $safeAlias = self::EscapeIdentifier($key);
                    $sql .= " AS $safeAlias";
                }
            }
            return $sql;
        }

        return $addQuotes ? self::EscapeIdentifier($columns) : $columns;
    }

    /**
     * Builds a DELETE SQL statement.
     *
     * @param string $tableName Table name.
     * @param array|null $whereArray WHERE conditions (optional but recommended).
     * @param bool $autoEscape Whether to auto-escape values via SQLValue().
     * @return string The DELETE SQL statement.
     * @throws InvalidArgumentException If table name is empty.
     */
    public static function BuildSQLDelete(string $tableName, ?array $whereArray = null, bool $autoEscape = false): string
    {
        if ($tableName === '') throw new InvalidArgumentException("Table name cannot be empty");
        $sql = "DELETE FROM " . self::EscapeIdentifier($tableName);
        if ($whereArray !== null) $sql .= self::BuildSQLWhereClause($whereArray, $autoEscape);
        return $sql;
    }

    /**
     * Builds an INSERT SQL statement.
     *
     * @param string $tableName Table name.
     * @param array $valuesArray Associative array of column=>value.
     * @param bool $autoEscape Whether to auto-escape values via SQLValue().
     * @return string The INSERT SQL statement.
     * @throws InvalidArgumentException If table name empty or values array empty.
     */
    public static function BuildSQLInsert(string $tableName, array $valuesArray, bool $autoEscape = false): string
    {
        if ($tableName === '') throw new InvalidArgumentException("Table name cannot be empty");
        if ($valuesArray === []) throw new InvalidArgumentException("Values array cannot be empty");
        $safeColumns = array_map(array('self', 'EscapeIdentifier'), array_keys($valuesArray));
        $columns = implode(", ", $safeColumns);

        if ($autoEscape) {
            $valuesArray = array_map(
                function($v) { return self::SQLValue($v, self::detectSqlValueType($v)); },
                $valuesArray
            );
        }
        $values = self::BuildSQLColumns($valuesArray, false, false);
        return "INSERT INTO " . self::EscapeIdentifier($tableName) . " ($columns) VALUES ($values)";
    }

    /**
     * Builds a SELECT SQL statement with full clause support.
     *
     * @param string $tableName Table name.
     * @param array|null $whereArray WHERE conditions.
     * @param array|string|null $columns Columns to select (null = *).
     * @param array|string|null $sortColumns ORDER BY columns.
     * @param bool $sortAscending Sort direction (true=ASC).
     * @param int|null $limit LIMIT count.
     * @param int|null $offset OFFSET count.
     * @param bool $autoEscape Whether to auto-escape values in WHERE.
     * @return string The SELECT SQL statement.
     * @throws InvalidArgumentException If table name is empty.
     */
    public static function BuildSQLSelect(
        string $tableName,
        ?array $whereArray = null,
        array|string|null $columns = null,
        array|string|null $sortColumns = null,
        bool $sortAscending = true,
        int|null $limit = null,
        int|null $offset = null,
        bool $autoEscape = false
    ): string {
        if ($tableName === '') throw new InvalidArgumentException("Table name cannot be empty");

        if (is_string($columns) && strpos($columns, ',') !== false) {
            $columns = array_map('trim', explode(',', $columns));
        }

        $sql = "SELECT " . (is_null($columns) ? "*" : self::BuildSQLColumns($columns));
        $sql .= " FROM " . self::EscapeIdentifier($tableName);

        if ($whereArray !== null) $sql .= self::BuildSQLWhereClause($whereArray, $autoEscape);
        if ($sortColumns !== null) {
            $sql .= " ORDER BY " . self::BuildSQLColumns($sortColumns, true, false) . " " . ($sortAscending ? "ASC" : "DESC");
        }
        if ($limit !== null) $sql .= " LIMIT " . (int)$limit;
        if ($offset !== null) $sql .= " OFFSET " . (int)$offset;

        return $sql;
    }

    /**
     * Builds an UPDATE SQL statement.
     *
     * @param string $tableName Table name.
     * @param array $valuesArray Associative array of column=>value to set.
     * @param array|null $whereArray WHERE conditions.
     * @param bool $autoEscape Whether to auto-escape values.
     * @return string The UPDATE SQL statement.
     * @throws InvalidArgumentException If table name empty or values array empty.
     */
    public static function BuildSQLUpdate(string $tableName, array $valuesArray, ?array $whereArray = null, bool $autoEscape = false): string
    {
        if ($tableName === '') throw new InvalidArgumentException("Table name cannot be empty");
        if ($valuesArray === []) throw new InvalidArgumentException("Values array cannot be empty");
        $setClause = self::BuildSQLSetClause($valuesArray, $autoEscape);
        $sql = "UPDATE " . self::EscapeIdentifier($tableName) . " SET $setClause";
        if ($whereArray !== null) $sql .= self::BuildSQLWhereClause($whereArray, $autoEscape);
        return $sql;
    }

    /**
     * Builds the SET clause for an UPDATE statement.
     *
     * @param array $valuesArray Column=>value pairs.
     * @param bool $autoEscape Whether to auto-escape values.
     * @return string The SET clause (e.g., `col1` = 'val', `col2` = 1).
     */
    private static function BuildSQLSetClause(array $valuesArray, bool $autoEscape = false): string
    {
        $sql = "";
        foreach ($valuesArray as $key => $value) {
            $safeKey = self::EscapeIdentifier($key);
            if ($autoEscape) {
                $value = self::SQLValue($value, self::detectSqlValueType($value));
            }
            $sql .= (strlen($sql) === 0 ? "" : ", ") . "$safeKey = $value";
        }
        return $sql;
    }

    /**
     * Builds a WHERE clause from an associative array.
     * Supports operators in key (e.g., "age >"), IN/NOT IN arrays, NULL checks, and '_raw' key.
     *
     * @param array $whereArray Conditions.
     * @param bool $autoEscape Whether to auto-escape values.
     * @return string The WHERE clause (prefixed with " WHERE " or " AND ").
     * @throws InvalidArgumentException If identifier validation fails inside EscapeIdentifier.
     */
    public static function BuildSQLWhereClause(array $whereArray, bool $autoEscape = false): string
    {
        $where = "";
        foreach ($whereArray as $key => $value) {
            if ($key === '_raw') {
                if (strpos($value, ';') !== false || stripos($value, '--') !== false || stripos($value, '/*') !== false) {
                    trigger_error("MySQL::BuildSQLWhereClause: Raw fragment ('_raw') contains potentially dangerous characters (;, --, /*)", E_USER_WARNING);
                }
                $where .= (strlen($where) === 0 ? " WHERE " : (substr($where, -1) === ' ' ? "" : " ")) . $value;
                continue;
            }

            if (!is_string($key)) {
                $where .= (strlen($where) === 0 ? " WHERE " : " AND ") . $value;
                continue;
            }

            $operator = "=";
            $column = $key;
            if (preg_match('/^(.+?)\s*(>=|<=|!=|<>|>|<|LIKE|NOT LIKE|IN|NOT IN)\s*$/i', $key, $matches)) {
                $column = $matches[1];
                $operator = strtoupper($matches[2]);
            }

            $safeColumn = self::EscapeIdentifier($column);
            $where .= (strlen($where) === 0 ? " WHERE " : " AND ");

            if (is_null($value)) {
                $where .= $safeColumn . ($operator === "=" ? " IS NULL" : ($operator === "!=" || $operator === "<>" ? " IS NOT NULL" : " $operator NULL"));
            } elseif (in_array($operator, array('IN', 'NOT IN'), true) && is_array($value)) {
                if ($autoEscape) {
                    $vals = array_map(function($v) { return self::SQLValue($v, self::detectSqlValueType($v)); }, $value);
                } else {
                    $vals = $value;
                }
                $where .= "$safeColumn $operator (" . implode(", ", $vals) . ")";
            } else {
                if ($autoEscape) $value = self::SQLValue($value, self::detectSqlValueType($value));
                $where .= "$safeColumn $operator $value";
            }
        }
        return $where;
    }

    /**
     * Detects the SQL value type constant for a given PHP value.
     *
     * @param mixed $value The value to inspect.
     * @return string One of SQLVALUE_* constants.
     */
    private static function detectSqlValueType(mixed $value): string
    {
        if (is_null($value)) return self::SQLVALUE_TEXT;
        if (is_bool($value)) return self::SQLVALUE_BOOLEAN;
        if (is_int($value)) return self::SQLVALUE_NUMBER;
        if (is_float($value)) return self::SQLVALUE_NUMBER;
        if ($value instanceof DateTimeInterface) return self::SQLVALUE_DATETIME;
        if (is_resource($value)) return self::SQLVALUE_TEXT;
        return self::SQLVALUE_TEXT;
    }

    /**
     * Closes the database connection and frees resources.
     *
     * @return bool True on success, false on failure.
     */
    public function Close(): bool
    {
        $this->ResetError();
        $this->active_row = -1;

        if ($this->stmt !== null) {
            @mysqli_stmt_close($this->stmt);
            $this->stmt = null;
            $this->stmt_params = [];
            $this->stmt_param_types = "";
            $this->stmt_bound = false;
            $this->stmt_result = null;
            $this->stmt_meta = null;
        }

        if (!isset($this->mysql_link) || $this->mysql_link === null) return true;

        $this->Release();
        $success = @mysqli_close($this->mysql_link);

        if (!$success) $this->SetError();
        else {
            $this->last_sql = "";
            $this->last_result = null;
            $this->last_insert_id = false;
            $this->mysql_link = null;
        }
        return $success;
    }

    /**
     * Deletes rows matching the WHERE conditions.
     *
     * @param string $tableName Table name.
     * @param array $whereArray WHERE conditions (required to prevent mass delete).
     * @return bool True on success, false on error.
     */
    public function DeleteRows(string $tableName, array $whereArray = []): bool
    {
        $this->ResetError();
        if ($tableName === '') { $this->SetError("Table name cannot be empty", -1); return false; }
        if ($whereArray === []) { $this->SetError("Where array cannot be empty for DeleteRows (prevents mass delete)", -1); return false; }
        if (!$this->IsConnected()) { $this->SetError("No connection"); return false; }

        $sql = self::BuildSQLDelete($tableName, $whereArray, $this->getAutoEscapeMode());
        return $this->Query($sql) !== false;
    }

    /**
     * Checks if the internal result pointer is at or past the last row.
     *
     * @return bool True if at end of result set (or empty), false otherwise.
     */
    public function EndOfSeek(): bool
    {
        $this->ResetError();
        if (!$this->IsConnected() || !$this->last_result) { $this->SetError("No connection or result set"); return false; }
        $rowCount = $this->RowCount();
        if ($rowCount === false || $rowCount === 0) return true;
        return $this->active_row >= $rowCount;
    }

    /**
     * Gets the last error description.
     *
     * @return string|bool Error string with code (e.g., "Error (#1064)"), or false if no error.
     */
    public function Error(): string|bool
    {
        if (empty($this->error_desc)) {
            return $this->error_number !== 0 ? "Unknown Error (#{$this->error_number})" : false;
        }
        return $this->error_number > 0 ? "{$this->error_desc} (#{$this->error_number})" : $this->error_desc;
    }

    /**
     * Gets the last error number.
     *
     * @return int|bool MySQL error code, or false if no error.
     */
    public function ErrorNumber(): int|bool
    {
        return strlen($this->error_desc) > 0 ? ($this->error_number !== 0 ? $this->error_number : false) : false;
    }

    /**
     * Converts a value to a boolean using loose semantics (Y, T, 1, ON, etc.).
     *
     * @param mixed $value Value to check.
     * @return bool True if value represents truthy state.
     */
    public static function GetBooleanValue(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        if (is_numeric($value)) return $value > 0;
        $cleaned = strtoupper(trim((string)$value));
        return in_array($cleaned, array('ON', 'SELECTED', 'CHECKED', 'YES', 'Y', 'TRUE', 'T'), true);
    }

    /**
     * Retrieves column comments for a table or the current result set.
     *
     * @param string $table Table name (empty = current result set).
     * @param string $resultType Return format: ASSOC, NUM, BOTH.
     * @return array|bool Array of comments, or false on error.
     */
    public function GetColumnComments(string $table = '', string $resultType = 'ASSOC'): array|bool
    {
        if (!in_array($resultType, array("ASSOC", "NUM", "BOTH"), true)) return false;
        $this->ResetError();

        if (empty($table)) {
            if (!$this->last_result || !is_object($this->last_result)) {
                $this->SetError("No active result set to inspect", -1);
                return false;
            }
            $columns = array();
            $fields = mysqli_fetch_fields($this->last_result);
            foreach ($fields as $field) {
                $comment = isset($field->comment) ? $field->comment : '';
                if (in_array($resultType, array("NUM", "BOTH"), true)) $columns[] = $comment;
                if (in_array($resultType, array("ASSOC", "BOTH"), true)) $columns[$field->name] = $comment;
            }
            return $columns;
        }

        if ($table === '') { $this->SetError("Table name cannot be empty", -1); return false; }

        $savedState = $this->saveQueryState();
        try {
            $safeTable = self::EscapeIdentifier($table);
            $records = mysqli_query($this->mysql_link, "SHOW FULL COLUMNS FROM $safeTable");
        } catch (Exception $e) {
            $this->SetError($e->getMessage(), isset($e->code) ? $e->code : -1);
            $records = false;
        }

        if (!$records) { $this->restoreQueryState($savedState); return false; }

        $columns = array();
        if (is_a($records, "mysqli_result")) {
            while ($row = mysqli_fetch_assoc($records)) {
                $colName = $row['Field'];
                $comment = $row['Comment'] ?? '';
                if (in_array($resultType, ["NUM", "BOTH"], true)) $columns[] = $comment;
                if (in_array($resultType, ["ASSOC", "BOTH"], true)) $columns[$colName] = $comment;
            }
            mysqli_free_result($records);
        }
        $this->restoreQueryState($savedState);
        return $columns;
    }

    /**
     * Gets the number of columns in a table or the current result set.
     *
     * @param string $table Table name (empty = current result set).
     * @return int|bool Column count, or false on error.
     */
    public function GetColumnCount(string $table = ''): int|bool
    {
        $this->ResetError();
        if (empty($table)) {
            if (!$this->last_result || !is_object($this->last_result)) {
                $this->SetError("No active result set to inspect", -1);
                return false;
            }
            return mysqli_field_count($this->mysql_link);
        }

        if ($table === '') { $this->SetError("Table name cannot be empty", -1); return false; }

        $savedState = $this->saveQueryState();
        try {
            $safeTable = self::EscapeIdentifier($table);
            $records = mysqli_query($this->mysql_link, "SHOW COLUMNS FROM $safeTable");
        } catch (Exception $e) {
            $this->SetError($e->getMessage(), isset($e->code) ? $e->code : -1);
            $records = false;
        }

        $result = false;
        if ($records && is_a($records, "mysqli_result")) {
            $result = 0;
            while (mysqli_fetch_array($records)) $result++;
            mysqli_free_result($records);
        } else {
            $this->SetError("GetColumnCount did not return records");
        }
        $this->restoreQueryState($savedState);
        return $result;
    }

    /**
     * Gets the generic data type (e.g., 'int', 'varchar') for a column.
     *
     * @param string|int $column Column name or index.
     * @param string $table Table name (empty = current result set).
     * @return string|bool Type name string, or false on error.
     */
    public function GetColumnDataType(string|int $column, string $table = ''): string|bool
    {
        $this->ResetError();
        if (empty($table)) {
            if ($this->RowCount() > 0) {
                $field = is_int($column)
                    ? mysqli_fetch_field_direct($this->last_result, $column)
                    : mysqli_fetch_field_direct($this->last_result, $this->GetColumnID((string)$column));
                if (!$field) return false;
                $typeId = $field->type;
                return isset(self::$typeMap[$typeId]) ? self::$typeMap[$typeId] : "unknown_type_$typeId";
            }
            return false;
        }

        if ($table === '') { $this->SetError("Table name cannot be empty", -1); return false; }

        $savedState = $this->saveQueryState();
        try {
            if (is_int($column)) $column = $this->GetColumnName($column, $table);
            $safeTable = self::EscapeIdentifier($table);
            $safeColumn = self::EscapeIdentifier((string)$column);
            $result = mysqli_query($this->mysql_link, "SELECT $safeColumn FROM $safeTable LIMIT 1");
        } catch (Exception $e) {
            $this->SetError($e->getMessage(), isset($e->code) ? $e->code : -1);
            $result = false;
        }

        $returnVal = false;
        if (mysqli_field_count($this->mysql_link) > 0 && !is_bool($result)) {
            $field = mysqli_fetch_field_direct($result, 0);
            $typeId = $field->type;
            $returnVal = isset(self::$typeMap[$typeId]) ? self::$typeMap[$typeId] : "unknown_type_$typeId";
        } else {
            $this->SetError("Specified column or table does not exist, or no data returned", -1);
        }
        $this->restoreQueryState($savedState);
        return $returnVal;
    }

    /**
     * Gets the full MySQL column type definition (e.g., 'varchar(255)', 'int(11)').
     *
     * @param string|int $column Column name or index.
     * @param string $table Table name.
     * @return string|bool Type definition string, or false on error.
     */
    public function GetColumnDataTypeName(string|int $column, string $table = ''): string|bool
    {
        $this->ResetError();
        if (empty($table)) {
            if ($this->RowCount() > 0) {
                $field = is_int($column)
                    ? mysqli_fetch_field_direct($this->last_result, $column)
                    : mysqli_fetch_field_direct($this->last_result, $this->GetColumnID((string)$column));
                if (!$field) return false;
                $table = $field->table;
            } else return false;
        }

        if ($table === '') { $this->SetError("Table name cannot be empty", -1); return false; }

        if (is_int($column)) $column = $this->GetColumnName($column, $table);

        $savedState = $this->saveQueryState();
        try {
            $safeTable = self::EscapeIdentifier($table);
            $safeColumnValue = self::SQLValue((string)$column, self::SQLVALUE_TEXT);
            $result = $this->QueryArray("SHOW COLUMNS FROM $safeTable WHERE Field = $safeColumnValue", MYSQLI_ASSOC);
        } catch (Exception $e) {
            $this->SetError($e->getMessage(), -1);
            $this->restoreQueryState($savedState);
            return false;
        }
        $this->restoreQueryState($savedState);

        if ($result === false || count($result) !== 1) {
            $this->SetError("Specified column or table does not exist, or no data returned", -1);
            return false;
        }
        return $result[0]["Type"];
    }

    /**
     * Gets the zero-based index of a column by name.
     *
     * @param string $column Column name.
     * @param string $table Table name (empty = current result set).
     * @return int|bool Column index, or false if not found.
     */
    public function GetColumnID(string $column, string $table = ''): int|bool
    {
        $this->ResetError();
        $columnNames = $this->GetColumnNames($table);
        if (!$columnNames) return false;

        $index = array_search($column, $columnNames, true);
        if ($index !== false) return $index;

        $this->SetError("Column name not found", -1);
        return false;
    }

    /**
     * Gets the maximum length (display size) of a column.
     *
     * @param string|int $column Column name or index.
     * @param string $table Table name (empty = current result set).
     * @return int|bool Column length, or false on error.
     */
    public function GetColumnLength(string|int $column, string $table = ''): int|bool
    {
        $this->ResetError();
        if (empty($table)) {
            $columnID = is_int($column) ? $column : $this->GetColumnID((string)$column);
            if ($columnID === false) return false;
            $field = mysqli_fetch_field_direct($this->last_result, $columnID);
            return is_object($field) ? $field->length : false;
        }

        if ($table === '') { $this->SetError("Table name cannot be empty", -1); return false; }

        $savedState = $this->saveQueryState();
        try {
            if (is_int($column)) $column = $this->GetColumnName($column, $table);
            $safeTable = self::EscapeIdentifier($table);
            $safeColumn = self::EscapeIdentifier((string)$column);
            $records = mysqli_query($this->mysql_link, "SELECT $safeColumn FROM $safeTable LIMIT 1");
        } catch (Exception $e) {
            $this->SetError($e->getMessage(), isset($e->code) ? $e->code : -1);
            $records = false;
        }

        $returnVal = false;
        if ($records) {
            $field = mysqli_fetch_field_direct($records, 0);
            $returnVal = is_object($field) ? $field->length : false;
            if (!$returnVal) $this->SetError();
        }
        $this->restoreQueryState($savedState);
        return $returnVal;
    }

    /**
     * Gets the name of a column by its zero-based index.
     *
     * @param int $columnID Column index.
     * @param string $table Table name (empty = current result set).
     * @return string|bool Column name, or false on error.
     */
    public function GetColumnName(int $columnID, string $table = ''): string|bool
    {
        $this->ResetError();
        if (empty($table)) {
            if ($this->RowCount() > 0) {
                $field = mysqli_fetch_field_direct($this->last_result, $columnID);
                return is_object($field) ? $field->name : false;
            }
            return false;
        }

        if ($table === '') { $this->SetError("Table name cannot be empty", -1); return false; }

        $savedState = $this->saveQueryState();
        try {
            $safeTable = self::EscapeIdentifier($table);
            $records = mysqli_query($this->mysql_link, "SELECT * FROM $safeTable LIMIT 1");
        } catch (Exception $e) {
            $this->SetError($e->getMessage(), isset($e->code) ? $e->code : -1);
            $records = false;
        }

        $result = false;
        if ($records && mysqli_field_count($this->mysql_link) > 0 && is_a($records, 'mysqli_result') && $columnID < mysqli_field_count($this->mysql_link)) {
            $field = mysqli_fetch_field_direct($records, $columnID);
            $result = is_object($field) ? $field->name : false;
            if (!$result) $this->SetError();
        }
        $this->restoreQueryState($savedState);
        return $result;
    }

    /**
     * Gets an array of column names for a table or the current result set.
     *
     * @param string $table Table name (empty = current result set).
     * @return array|bool Array of column names, or false on error.
     */
    public function GetColumnNames(string $table = ''): array|bool
    {
        $this->ResetError();
        $columns = array();

        if (empty($table)) {
            if (!$this->last_result || !is_object($this->last_result)) {
                $this->SetError("No active result set to inspect", -1);
                return false;
            }
            $columnCount = mysqli_field_count($this->mysql_link);
            for ($i = 0; $i < $columnCount; $i++) {
                $field = mysqli_fetch_field_direct($this->last_result, $i);
                $columns[] = $field->name;
            }
        } else {
            if ($table === '') { $this->SetError("Table name cannot be empty", -1); return false; }
            $savedState = $this->saveQueryState();
            try {
                $safeTable = self::EscapeIdentifier($table);
                $result = mysqli_query($this->mysql_link, "SHOW COLUMNS FROM $safeTable");
            } catch (Exception $e) {
                $this->SetError($e->getMessage(), isset($e->code) ? $e->code : -1);
                $result = false;
            }

            if ($result && is_a($result, "mysqli_result")) {
                while ($array_data = mysqli_fetch_array($result)) $columns[] = $array_data[0];
                mysqli_free_result($result);
            } else $columns = false;
            $this->restoreQueryState($savedState);
        }
        return $columns;
    }

    /**
     * Gets a list of all tables in the current database.
     *
     * @return array|bool Array of table names, or false on error.
     */
    public function GetTables(): array|bool
    {
        $this->ResetError();
        $tables = array();
        $savedState = $this->saveQueryState();

        if ($this->IsConnected()) {
            try { $records = mysqli_query($this->mysql_link, "SHOW TABLES"); }
            catch (Exception $e) { $this->SetError($e->getMessage(), isset($e->code) ? $e->code : -1); $records = false; }
        } else $records = false;

        if (!$records) { $this->SetError("No tables found"); $this->restoreQueryState($savedState); return false; }

        if (is_a($records, "mysqli_result")) {
            while ($array_data = mysqli_fetch_array($records)) $tables[] = $array_data[0];
            mysqli_free_result($records);
        }
        $this->restoreQueryState($savedState);
        return count($tables) > 0 ? $tables : false;
    }

    /**
     * Generates an HTML table representation of the current result set.
     *
     * @param bool $showCount Whether to prepend row count.
     * @param string|null $styleTable Inline CSS for <table>.
     * @param string|null $styleHeader Inline CSS for header <td>.
     * @param string|null $styleData Inline CSS for data <td>.
     * @return string|bool HTML string, or false if no result set / error / safety limit exceeded.
     */
    public function GetHTML(bool $showCount = true, ?string $styleTable = null, ?string $styleHeader = null, ?string $styleData = null): string|bool
    {
        $tb = $styleTable ?? "border-collapse:collapse;empty-cells:show";
        $th = $styleHeader ?? "border-width:1px;border-style:solid;background-color:navy;color:white";
        $td = $styleData ?? "border-width:1px;border-style:solid";

        if (!$this->last_result) return false;
        $rowCount = $this->RowCount();
        if ($rowCount === false || $rowCount === 0) return "no records were returned.";

        // SAFETY: Prevent OOM on GetHTML for large results
        if (MYSQL_MAX_BUFFERED_ROWS > 0 && $rowCount > MYSQL_MAX_BUFFERED_ROWS) {
            $this->SetError("GetHTML() aborted: Result set ($rowCount rows) exceeds safety limit (" . MYSQL_MAX_BUFFERED_ROWS . "). Use streaming RowArray() loop.", -1);
            return false;
        }

        $html = "";
        if ($showCount) $html .= "Record Count: $rowCount\n";
        $html .= "<table style=\"$tb\" cellpadding=\"2\" cellspacing=\"2\">\n";

        if ($this->MoveFirst()) {
            $header = false;
            while ($member = $this->RowArray(null, MYSQLI_ASSOC)) {
                if (!$header) {
                    $html .= "\t<tr>\n";
                    foreach ($member as $key => $value) {
                        $html .= "\t\t<td style=\"$th\"><strong>" . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . "</strong></td>\n";
                    }
                    $html .= "\t</tr>\n";
                    $header = true;
                }
                $html .= "\t<tr>\n";
                foreach ($member as $key => $value) {
                    $html .= "\t\t<td style=\"$td\">" . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . "</td>\n";
                }
                $html .= "\t</tr>\n";
            }
        }
        $html .= "</table>";
        $this->MoveFirst();
        return $html;
    }

    /**
     * Returns the current result set as a JSON string.
     *
     * @return string JSON encoded string (pretty print), or 'null' if no result / error / safety limit exceeded.
     */
    public function GetJSON(): string
    {
        if (!$this->last_result) return 'null';

        $rowCount = $this->RowCount();
        // SAFETY: Prevent OOM on GetJSON for large results
        if (MYSQL_MAX_BUFFERED_ROWS > 0 && $rowCount !== false && $rowCount > MYSQL_MAX_BUFFERED_ROWS) {
            $this->SetError("GetJSON() aborted: Result set ($rowCount rows) exceeds safety limit (" . MYSQL_MAX_BUFFERED_ROWS . "). Use streaming Fetch() loop.", -1);
            return 'null';
        }

        $rows = array();
        if ($this->MoveFirst()) {
            while ($row = $this->RowArray(null, MYSQLI_ASSOC)) $rows[] = $row;
        }
        $this->MoveFirst();

        return json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Gets the last auto-generated INSERT ID.
     *
     * @return int|string|false The ID, or false if none.
     */
    public function GetLastInsertID(): int|string|false
    {
        return $this->last_insert_id;
    }

    /**
     * Gets the last executed SQL query string.
     *
     * @return string The SQL query.
     */
    public function GetLastSQL(): string
    {
        return $this->last_sql;
    }

    /**
     * Returns the current result set as an XML string.
     *
     * @return string XML document string.
     */
    public function GetXML(): string
    {
        $doc = new DomDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = true;

        $root = $doc->createElement('root');
        $root = $doc->appendChild($root);

        if (is_object($this->last_result)) {
            $rowCount = $this->RowCount();
            $root->setAttribute('rows', $rowCount !== false ? (string)$rowCount : '0');
            $root->setAttribute('query', $this->last_sql);
            $root->setAttribute('error', "");

            // SAFETY: Prevent OOM on GetXML for large results
            if (MYSQL_MAX_BUFFERED_ROWS > 0 && $rowCount !== false && $rowCount > MYSQL_MAX_BUFFERED_ROWS) {
                $this->SetError("GetXML() aborted: Result set ($rowCount rows) exceeds safety limit (" . MYSQL_MAX_BUFFERED_ROWS . ").", -1);
                $root->setAttribute('error', "Result set too large for XML export");
            } else {
                $rowIndex = 0;
                if ($this->MoveFirst()) {
                    while ($row = $this->RowArray(null, MYSQLI_ASSOC)) {
                        $rowIndex++;
                        $element = $doc->createElement('row');
                        $element = $root->appendChild($element);
                        $element->setAttribute('index', (string)$rowIndex);

                        foreach ($row as $fieldname => $fieldvalue) {
                            $fieldvalue = htmlspecialchars((string)$fieldvalue, ENT_QUOTES, 'UTF-8');
                            $valueNode = $doc->createTextNode($fieldvalue);
                            $child = $doc->createElement($fieldname);
                            $child->appendChild($valueNode);
                            $element->appendChild($child);
                        }
                    }
                }
                $this->MoveFirst();
            }
        } else {
            $root->setAttribute('rows', '0');
            $root->setAttribute('query', $this->last_sql);
            $root->setAttribute('error', $this->Error() ?: "No query has been executed.");
        }
        return $doc->saveXML();
    }

    /**
     * Checks if a query returns any rows (executes SQL if provided).
     *
     * @param string $sql Optional SQL to execute first.
     * @return bool True if rows exist, false otherwise.
     */
    public function HasRecords(string $sql = ''): bool
    {
        if (strlen($sql) > 0) {
            $this->Query($sql);
            if ($this->Error()) return false;
        }
        $count = $this->RowCount();
        return $count !== false && $count > 0;
    }

    /**
     * Inserts a single row into a table.
     *
     * @param string $tableName Table name.
     * @param array $valuesArray Associative array of column=>value.
     * @return int|bool Insert ID on success, false on error.
     */
    public function InsertRow(string $tableName, array $valuesArray): int|bool
    {
        $this->ResetError();
        if ($tableName === '') { $this->SetError("Table name cannot be empty", -1); return false; }
        if ($valuesArray === []) { $this->SetError("Values array cannot be empty", -1); return false; }
        if (!$this->IsConnected()) { $this->SetError("No connection"); return false; }

        $sql = self::BuildSQLInsert($tableName, $valuesArray, $this->getAutoEscapeMode());
        return $this->Query($sql) !== false ? $this->GetLastInsertID() : false;
    }

    /**
     * Checks if the database connection is active.
     *
     * @return bool True if connected.
     */
    public function IsConnected(): bool
    {
        return isset($this->mysql_link) && is_object($this->mysql_link);
    }

    /**
     * @deprecated Use DateTime objects or ISO 8601 strings with SQLValue().
     */
    public static function IsDate(mixed $value): bool
    {
        trigger_error("MySQL::IsDate() deprecated. Use DateTime objects or ISO 8601 strings (Y-m-d, Y-m-d H:i:s) with SQLValue().", E_USER_DEPRECATED);
        return (bool)@strtotime((string)$value);
    }

    /**
     * Terminates script execution with an error message.
     *
     * @param string $message Optional custom message (defaults to last error).
     * @return never
     */
    public function Kill(string $message = ''): never
    {
        die(strlen($message) > 0 ? $message : (string)$this->Error());
    }

    /**
     * Moves the internal result pointer to the first row (index 0).
     * Not supported for unbuffered prepared statements without mysqlnd.
     *
     * @return bool True on success, false on failure or empty result.
     */
    public function MoveFirst(): bool
    {
        $this->ResetError();
        if (!$this->IsConnected() || !$this->last_result) { $this->SetError("No result set"); return false; }

        $rc = $this->RowCount();
        if ($rc === false) return false;
        if ($rc === 0) { $this->active_row = 0; return false; }

        if ($this->last_result instanceof mysqli_stmt) {
            $this->SetError("MoveFirst not supported on unbuffered prepared statement result. Use FetchAll() or enable mysqlnd.", -1);
            return false;
        }

        $result = @mysqli_data_seek($this->last_result, 0);
        if ($result) { $this->active_row = 0; return true; }

        $this->SetError();
        return false;
    }

    /**
     * Moves the internal result pointer to the last row.
     * Not supported for unbuffered prepared statements without mysqlnd.
     *
     * @return bool True on success, false on failure or empty result.
     */
    public function MoveLast(): bool
    {
        $this->ResetError();
        $rowCount = $this->RowCount();
        if ($rowCount === false || $rowCount === 0) return false;

        if ($this->last_result instanceof mysqli_stmt) {
            $this->SetError("MoveLast not supported on unbuffered prepared statement result. Use FetchAll() or enable mysqlnd.", -1);
            return false;
        }

        $lastIndex = $rowCount - 1;
        $this->active_row = $lastIndex;
        return $this->Seek($lastIndex) !== false;
    }

    /**
     * Opens a database connection.
     *
     * @param string|null $database Database name (overrides constructor).
     * @param string|null $server Host (overrides constructor).
     * @param string|null $username User (overrides constructor).
     * @param string|null $password Pass (overrides constructor).
     * @param string|null $charset Charset (overrides constructor).
     * @param bool $persistent Use persistent connection.
     * @param int $connectTimeout Connection timeout in seconds.
     * @param array|null $sslOptions SSL options array (keys: key, cert, ca, capath, cipher).
     * @return bool True on success, false on failure.
     */
    public function Open(
        ?string $database = null,
        ?string $server = null,
        ?string $username = null,
        ?string $password = null,
        ?string $charset = null,
        bool $persistent = false,
        int $connectTimeout = 0,
        ?array $sslOptions = null
    ): bool {
        $this->ResetError();

        $host = $this->db_host;
        $user = $this->db_user;
        $pass = $this->db_pass;
        $dbname = $this->db_dbname;
        $charsetToUse = $this->db_charset;
        $pcon = $this->db_pcon;

        if ($database !== null) {
            $dbname = $database;
            if ($server !== null) $host = $server;
            if ($username !== null) $user = $username;
            if ($password !== null) $pass = $password;
            if ($charset !== null) $charsetToUse = $charset;
        }
        if ($persistent) $pcon = true;

        $this->active_row = -1;

        try {
            $link = mysqli_init();
            if ($connectTimeout > 0) {
                mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, $connectTimeout);
            }
            if ($sslOptions !== null && is_array($sslOptions)) {
                mysqli_ssl_set(
                    $link,
                    $sslOptions['key'] ?? null,
                    $sslOptions['cert'] ?? null,
                    $sslOptions['ca'] ?? null,
                    $sslOptions['capath'] ?? null,
                    $sslOptions['cipher'] ?? null
                );
            }

            $hostToUse = $pcon ? 'p:' . $host : $host;
            $connected = @mysqli_real_connect(
                $link,
                $hostToUse,
                $user,
                $pass,
                $dbname ?: null,
                0,
                null,
                MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT
            );
            if ($connected) {
                $this->mysql_link = $link;
            } else {
                throw new Exception(mysqli_connect_error(), mysqli_connect_errno());
            }
        } catch (Exception $e) {
            $this->SetError($e->getMessage(), isset($e->code) ? $e->code : -1);
            $this->last_result = false;
            $this->last_insert_id = false;
            return false;
        }

        if (!$this->IsConnected()) { $this->SetError(); return false; }

        $charsetToUse = $charsetToUse ?: 'utf8mb4';
        if ($pcon) {
            if (!@mysqli_set_charset($this->mysql_link, $charsetToUse)) {
                $this->SetError("Unable to set charset on persistent connection: $charsetToUse");
                return false;
            }
        }

        if (strlen($dbname) > 0) {
            if (!$this->SelectDatabase($dbname, $pcon ? '' : $charsetToUse)) return false;
        }
        return true;
    }

    /**
     * Prepares a SQL statement for execution.
     *
     * @param string $sql The SQL query with placeholders (?).
     * @return bool True on success, false on failure.
     */
    public function Prepare(string $sql): bool
    {
        $this->ResetError();
        if (!$this->ensureConnected()) { $this->SetError("Connection lost and auto-reconnect disabled/failed", -1); return false; }
        $this->last_sql = $sql;

        if (!empty($this->debug)) {
            file_put_contents($this->debug, date("Y-m-d H:i:s") . " : PREPARE: " . $this->sanitizeSqlForLog($sql) . PHP_EOL, FILE_APPEND);
        }

        if (!$this->IsConnected()) { $this->SetError("No connection", -1); return false; }

        if ($this->stmt !== null) { @mysqli_stmt_close($this->stmt); $this->stmt = null; }

        try {
            $this->stmt = @mysqli_prepare($this->mysql_link, $sql);
        } catch (mysqli_sql_exception $e) {
            $this->SetError($e->getMessage(), $e->getCode());
            return false;
        }

        if (!$this->stmt) { $this->SetError(); return false; }

        $this->stmt_params = [];
        $this->stmt_param_types = "";
        $this->stmt_bound = false;
        $this->stmt_result = null;
        $this->stmt_meta = null;
        $this->last_insert_id = false;

        return true;
    }

    /**
     * Binds a single parameter to the prepared statement.
     * Must be called before Execute() and before BindParams().
     *
     * @param mixed $value The value to bind (by reference).
     * @param string $type Type specifier: 'i' (int), 'd' (double), 's' (string), 'b' (blob).
     * @return bool True on success, false on error.
     */
    public function BindParam(mixed $value, string $type = 's'): bool
    {
        $this->ResetError();
        if ($this->stmt === null) { $this->SetError("No prepared statement. Call Prepare() first.", -1); return false; }
        if ($this->stmt_bound) { $this->SetError("Parameters already bound. Cannot add more.", -1); return false; }
        if (!in_array($type, array('i', 'd', 's', 'b'), true)) { $this->SetError("Invalid parameter type: $type. Use 'i', 'd', 's', 'b'.", -1); return false; }

        $this->stmt_params[] = $value;
        $this->stmt_param_types .= $type;
        return true;
    }

    /**
     * Binds multiple parameters at once.
     *
     * @param array $params Array of values.
     * @param string $types String of type specifiers (e.g., 'issd'). If empty, auto-detected.
     * @return bool True on success, false on error.
     */
    public function BindParams(array $params, string $types = ''): bool
    {
        $this->ResetError();
        if ($this->stmt === null) { $this->SetError("No prepared statement. Call Prepare() first.", -1); return false; }
        if ($this->stmt_bound) { $this->SetError("Parameters already bound.", -1); return false; }

        if ($types === '') {
            foreach ($params as $value) {
                if (is_int($value)) $types .= 'i';
                elseif (is_float($value)) $types .= 'd';
                elseif (is_string($value) || is_null($value)) $types .= 's';
                elseif (is_resource($value)) $types .= 'b';
                else $types .= 's';
            }
        }

        if (strlen($types) !== count($params)) {
            $this->SetError("Number of types (" . strlen($types) . ") != number of parameters (" . count($params) . ")", -1);
            return false;
        }

        foreach ($params as $i => $value) {
            $type = $types[$i];
            if (!in_array($type, array('i', 'd', 's', 'b'), true)) {
                $this->SetError("Invalid parameter type at position $i: $type", -1);
                return false;
            }
            $this->stmt_params[] = $value;
            $this->stmt_param_types .= $type;
        }
        return true;
    }

    /**
     * Executes the prepared statement.
     * Handles SELECT (buffered/unbuffered) and DML automatically.
     *
     * @return bool True on success, false on failure.
     */
    public function Execute(): bool
    {
        $this->ResetError();
        if (!$this->ensureConnected()) { $this->SetError("Connection lost and auto-reconnect disabled/failed", -1); return false; }
        if ($this->stmt === null) { $this->SetError("No prepared statement. Call Prepare() first.", -1); return false; }

        if (!$this->stmt_bound && !empty($this->stmt_params)) {
            if (!$this->bindParameters()) return false;
            $this->stmt_bound = true;
        }

        $result = @mysqli_stmt_execute($this->stmt);
        if (!$result) { $this->SetError(); return false; }

        $sql_lower = strtolower(trim($this->last_sql));
        $isSelect = strpos($sql_lower, 'select') === 0 || strpos($sql_lower, 'show') === 0 || strpos($sql_lower, 'describe') === 0 || strpos($sql_lower, 'explain') === 0;

        if ($isSelect) {
            if (function_exists('mysqli_stmt_get_result')) {
                $this->stmt_result = @mysqli_stmt_get_result($this->stmt);
                if ($this->stmt_result !== false) {
                    $this->last_result = $this->stmt_result;
                    $this->active_row = 0;
                    $this->last_insert_id = false;
                    return true;
                }
            }
            // FALLBACK (no mysqlnd):
            // REMOVED: @mysqli_stmt_store_result($this->stmt); // THIS CAUSED THE 300MB OOM
            // metadata is available immediately after execute without store_result
            $this->stmt_meta = @mysqli_stmt_result_metadata($this->stmt);
            $this->last_result = $this->stmt;
            $this->active_row = 0;
            $this->last_insert_id = false;
            
            if (!$this->warned_no_mysqlnd) {
                trigger_error("MySQL::Execute: mysqlnd extension not detected. Prepared SELECT uses unbuffered fallback. RowCount(), Seek(), MoveFirst(), RecordsArray() are DISABLED. Use Fetch() loop or install php-mysqlnd.", E_USER_WARNING);
                $this->warned_no_mysqlnd = true;
            }
        } else {
            $this->last_insert_id = @mysqli_stmt_insert_id($this->stmt);
            $this->last_result = true;
            $this->active_row = -1;
        }
        return true;
    }

    /**
     * Internal: Binds parameters to the mysqli_stmt using call_user_func_array.
     *
     * @return bool True on success, false on failure.
     */
    private function bindParameters(): bool
    {
        if (empty($this->stmt_params)) return true;

        $refs = array(&$this->stmt_param_types);
        foreach ($this->stmt_params as $key => $value) $refs[] = &$this->stmt_params[$key];

        $result = call_user_func_array(array($this->stmt, 'bind_param'), $refs);
        if (!$result) { $this->SetError(); return false; }
        return true;
    }

    /**
     * Fetches the next row from a prepared statement (buffered or unbuffered fallback).
     *
     * @param int $resultType Fetch mode (MYSQLI_ASSOC, MYSQLI_NUM, MYSQLI_BOTH).
     * @return array|bool Row array, or false if no more rows / error.
     */
    public function Fetch(int $resultType = MYSQLI_BOTH): array|bool
    {
        $this->ResetError();
        if ($this->stmt === null && $this->last_result === null) { $this->SetError("No result set. Execute a prepared statement first.", -1); return false; }

        if ($this->stmt_result !== null && is_object($this->stmt_result)) {
            $row = @mysqli_fetch_array($this->stmt_result, $resultType);
            if ($row === null || $row === false) return false;
            $this->active_row++;
            return $row;
        }

        if ($this->stmt_meta !== null) {
            $fields = array();
            mysqli_field_seek($this->stmt_meta, 0);
            while ($field = mysqli_fetch_field($this->stmt_meta)) $fields[] = $field->name;

            $bind_vars = array_fill_keys($fields, null);
            $refs = array();
            foreach ($bind_vars as $key => $val) $refs[] = &$bind_vars[$key];
            call_user_func_array(array($this->stmt, 'bind_result'), $refs);

            if (@mysqli_stmt_fetch($this->stmt)) {
                $this->active_row++;
                if ($resultType === MYSQLI_ASSOC) return $bind_vars;
                if ($resultType === MYSQLI_NUM) return array_values($bind_vars);
                return $bind_vars + array_values($bind_vars);
            }
            return false;
        }
        return false;
    }

    /**
     * Fetches all remaining rows from a prepared statement into an array.
     * Respects MYSQL_MAX_BUFFERED_ROWS safety limit.
     *
     * @param int $resultType Fetch mode.
     * @return array|bool Array of rows, or false if safety limit exceeded / error.
     */
    public function FetchAll(int $resultType = MYSQLI_BOTH): array|bool
    {
        $this->ResetError();
        
        // SAFETY: Prevent OOM on FetchAll for large results
        $maxRows = MYSQL_MAX_BUFFERED_ROWS;
        $rows = array();
        $count = 0;
        while ($row = $this->Fetch($resultType)) {
            $rows[] = $row;
            $count++;
            if ($maxRows > 0 && $count > $maxRows) {
                $this->SetError("FetchAll() aborted: Result set exceeds safety limit ($maxRows rows). Use Fetch() loop for streaming.", -1);
                return false;
            }
        }
        return $rows;
    }

    /**
     * Closes the current prepared statement and resets state.
     *
     * @return bool True if a statement was closed, false if none active.
     */
    public function CloseStatement(): bool
    {
        $this->ResetError();
        if ($this->stmt !== null) {
            @mysqli_stmt_close($this->stmt);
            $this->stmt = null;
            $this->stmt_params = [];
            $this->stmt_param_types = "";
            $this->stmt_bound = false;
            $this->stmt_result = null;
            $this->stmt_meta = null;
            return true;
        }
        return false;
    }

    /**
     * Gets the row count for the last prepared SELECT statement.
     * Requires mysqlnd (mysqli_stmt_get_result). Throws error if unavailable.
     *
     * @return int|bool Row count, or false if unsupported/error.
     */
    public function PreparedRowCount(): int|bool
    {
        $this->ResetError();
        if ($this->stmt === null) { $this->SetError("No prepared statement", -1); return false; }

        if ($this->stmt_result !== null && is_object($this->stmt_result)) return @mysqli_num_rows($this->stmt_result);
        if ($this->stmt_meta !== null) { 
            $this->SetError("PreparedRowCount() not supported without mysqlnd. Use SELECT COUNT(*) or iterate with Fetch().", -1);
            return false; 
        }
        return 0;
    }

    /**
     * Executes a raw SQL query directly.
     * Detects SELECT vs DML to handle buffering and insert IDs.
     * Blocks multi-statement queries for security.
     *
     * @param string $sql The SQL query.
     * @param bool|null $buffered Override default buffering mode (true=buffered, false=unbuffered, null=use class default).
     * @return mysqli_result|bool Result object on success (SELECT), true on success (DML), false on error.
     */
    public function Query(string $sql, ?bool $buffered = null): mysqli_result|bool
    {
        $this->ResetError();
        if (!$this->ensureConnected()) { $this->SetError("Connection lost and auto-reconnect disabled/failed", -1); return false; }
        $this->last_sql = $sql;

        $semicolonCount = 0;
        $inSingleQuote = $inDoubleQuote = $escapeNext = false;
        $len = strlen($sql);
        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            if ($escapeNext) { $escapeNext = false; continue; }
            if ($ch === '\\') { $escapeNext = true; continue; }
            if ($ch === '\'' && !$inDoubleQuote) { $inSingleQuote = !$inSingleQuote; continue; }
            if ($ch === '"' && !$inSingleQuote) { $inDoubleQuote = !$inDoubleQuote; continue; }
            if ($ch === ';' && !$inSingleQuote && !$inDoubleQuote) $semicolonCount++;
        }
        if ($semicolonCount > 1 || ($semicolonCount === 1 && substr(rtrim($sql), -1) !== ';')) {
            $this->SetError("Multi-statement queries not supported. Use Prepare/Execute or execute separately.", -1);
            if ($this->ThrowExceptions) throw new RuntimeException("Multi-statement detected");
            return false;
        }

        if (!empty($this->debug)) {
            $logSql = $this->sanitizeSqlForLog($sql);
            file_put_contents($this->debug, date("Y-m-d H:i:s") . " : $logSql" . PHP_EOL, FILE_APPEND);
        }

        if (!$this->IsConnected()) { $this->SetError("No connection", -1); return false; }

        $sql_trimmed = ltrim($sql);
        $sql_trimmed = preg_replace('/^\s*(?:--.*|\/\*.*?\*\/)\s*/s', '', $sql_trimmed);
        $sql_lower = strtolower($sql_trimmed);

        $isSelect = strpos($sql_lower, "select") === 0 || strpos($sql_lower, "show") === 0 || strpos($sql_lower, "describe") === 0 || strpos($sql_lower, "explain") === 0;
        $isInsert = strpos($sql_lower, "insert") === 0;

        $useBuffered = ($buffered !== null) ? $buffered : $this->forceBufferedResults;

        try {
            if ($isSelect && $useBuffered) $this->last_result = mysqli_query($this->mysql_link, $sql, MYSQLI_STORE_RESULT);
            else $this->last_result = mysqli_query($this->mysql_link, $sql);
        } catch (Exception $e) {
            $this->SetError($e->getMessage(), isset($e->code) ? $e->code : -1);
            $this->last_result = false;
        }

        if (!$this->last_result) {
            $this->active_row = -1;
            $this->SetError();
            return false;
        }

        if ($isInsert) {
            $this->last_insert_id = mysqli_insert_id($this->mysql_link);
            $this->active_row = -1;
        } elseif ($isSelect) {
            $this->active_row = -1;
            $this->last_insert_id = false;
        } else {
            $this->last_insert_id = false;
            $this->active_row = -1;
        }
        return $this->last_result;
    }

    /**
     * Sanitizes SQL for debug logging.
     * If MYSQL_DEBUG_ANONIMIZATION is true, replaces literals with '?'.
     * Truncates at 64KB.
     *
     * @param string $sql Raw SQL.
     * @return string Sanitized SQL for logging.
     */
    private function sanitizeSqlForLog(string $sql): string
    {
        // NEW: Flag check - return raw SQL immediately if anonymization disabled
        if (!MYSQL_DEBUG_ANONIMIZATION) {
            return $sql;
        }

        // Existing logic runs ONLY if MYSQL_DEBUG_ANONIMIZATION === true
        if (strlen($sql) > 65536) {
            return "[SQL Query too large for logging (" . strlen($sql) . " bytes). Truncated.]";
        }

        $sql = preg_replace("/'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'/s", "'?'", $sql);
        $sql = preg_replace("/\b0x[0-9a-fA-F]+\b/", "0x?", $sql);
        $sql = preg_replace("/[xX]'[0-9a-fA-F]*'/s", "x'?'", $sql);
        $sql = preg_replace("/(^|[^`\w.])(-?\d+(\.\d+)?([eE][+-]?\d+)?)(?=[^`\w.]|$)/", "$1?", $sql);
        $sql = preg_replace("/\b(TRUE|FALSE|NULL)\b/i", "?", $sql);
        $sql = preg_replace("/(--\s*).*$/m", "$1[comment]", $sql);
        $sql = preg_replace("/\/\*.*?\*\//s", "/* [comment] */", $sql);
        return $sql;
    }

    /**
     * Executes a query and returns all rows as an array.
     * Shortcut for Query() + RecordsArray().
     *
     * @param string $sql The SQL query.
     * @param int $resultType Fetch mode.
     * @return array|bool Array of rows, or false on error.
     */
    public function QueryArray(string $sql, int $resultType = MYSQLI_BOTH): array|bool
    {
        $this->Query($sql);
        return !$this->Error() ? $this->RecordsArray($resultType) : false;
    }

    /**
     * Executes a query and returns the first row as an object.
     *
     * @param string $sql The SQL query.
     * @return object|bool Row object, or false if no rows / error.
     */
    public function QuerySingleRow(string $sql): object|bool
    {
        $this->Query($sql);
        return $this->RowCount() > 0 ? $this->Row() : false;
    }

    /**
     * Executes a query and returns the first row as an array.
     *
     * @param string $sql The SQL query.
     * @param int $resultType Fetch mode.
     * @return array|bool Row array, or false if no rows / error.
     */
    public function QuerySingleRowArray(string $sql, int $resultType = MYSQLI_BOTH): array|bool
    {
        $this->Query($sql);
        return $this->RowCount() > 0 ? $this->RowArray(null, $resultType) : false;
    }

    /**
     * Executes a query and returns the first column of the first row.
     *
     * @param string $sql The SQL query.
     * @return mixed The value, or false if no rows / error.
     */
    public function QuerySingleValue(string $sql): mixed
    {
        $this->Query($sql);
        return ($this->RowCount() > 0 && $this->GetColumnCount() > 0) ? $this->RowArray(null, MYSQLI_NUM)[0] : false;
    }

    /**
     * Executes a query and times the execution duration.
     *
     * @param string $sql The SQL query.
     * @return mysqli_result|bool Result object or false.
     */
    public function QueryTimed(string $sql): mysqli_result|bool
    {
        $this->TimerStart();
        $result = $this->Query($sql);
        $this->TimerStop();
        return $result;
    }

    /**
     * Gets the internal mysqli_result or mysqli_stmt object.
     *
     * @return mysqli_result|mysqli_stmt|bool|null The result resource.
     */
    public function Records(): mysqli_result|mysqli_stmt|bool|null
    {
        return $this->last_result;
    }

    /**
     * Fetches all rows from the last query result into an array.
     * Buffers the entire result set in memory. Respects MYSQL_MAX_BUFFERED_ROWS.
     * Not supported for unbuffered prepared statements.
     *
     * @param int $resultType Fetch mode.
     * @return array|bool Array of rows, or false on error / safety limit.
     */
    public function RecordsArray(int $resultType = MYSQLI_BOTH): array|bool
    {
        $this->ResetError();
        $members = array();
        if ($this->last_result) {
            if (!is_object($this->last_result)) return array();
            if ($this->last_result instanceof mysqli_stmt) {
                $this->SetError("RecordsArray not supported on unbuffered prepared statement result. Use FetchAll() or enable mysqlnd.", -1);
                return false;
            }
            
            // SAFETY: Check row count before buffering all
            $rowCount = @mysqli_num_rows($this->last_result);
            if (MYSQL_MAX_BUFFERED_ROWS > 0 && $rowCount > MYSQL_MAX_BUFFERED_ROWS) {
                $this->SetError("RecordsArray() aborted: Result set ($rowCount rows) exceeds safety limit (" . MYSQL_MAX_BUFFERED_ROWS . "). Use RowArray() loop.", -1);
                return false;
            }

            $can_seek = @mysqli_data_seek($this->last_result, 0);
            if ($can_seek) {
                while ($member = mysqli_fetch_array($this->last_result, $resultType)) $members[] = $member;
                @mysqli_data_seek($this->last_result, 0);
                $this->active_row = 0;
            } else {
                while ($member = mysqli_fetch_array($this->last_result, $resultType)) $members[] = $member;
                $this->active_row = -1;
            }
        } else {
            $this->active_row = -1;
            $this->SetError("No existing query result", -1);
            return false;
        }
        return $members;
    }

    /**
     * Advances to the next result set in a multi-query execution.
     *
     * @return bool True if next result exists, false otherwise.
     */
    public function NextResult(): bool
    {
        $this->ResetError();
        if (!$this->IsConnected()) { $this->SetError("No connection"); return false; }
        if (!@mysqli_more_results($this->mysql_link)) return false;
        if (!@mysqli_next_result($this->mysql_link)) {
            if (mysqli_errno($this->mysql_link) > 0) { $this->SetError(); return false; }
            return false;
        }
        $this->last_result = @mysqli_store_result($this->mysql_link);
        $this->active_row = -1;
        $this->last_sql = "";
        $this->last_insert_id = @mysqli_insert_id($this->mysql_link);
        return true;
    }

    /**
     * Frees the memory associated with the last query result.
     *
     * @return bool True on success.
     */
    public function Release(): bool
    {
        $this->ResetError();
        if (is_object($this->last_result) && $this->last_result !== $this->stmt) {
            mysqli_free_result($this->last_result);
            $this->last_result = false;
        }
        return true;
    }

    /**
     * Resets the internal error state.
     */
    private function ResetError(): void
    {
        $this->error_desc = '';
        $this->error_number = 0;
    }

    /**
     * Sets the internal error state. Reads from mysqli if no message provided.
     * Throws RuntimeException if ThrowExceptions is enabled.
     *
     * @param string $message Custom error message (optional).
     * @param int $code Custom error code (optional).
     */
    private function SetError(string $message = '', int $code = 0): void
    {
        if ($message === '') {
            if ($this->IsConnected()) {
                $this->error_desc = mysqli_error($this->mysql_link);
                $this->error_number = mysqli_errno($this->mysql_link);
            } else {
                $this->error_desc = "No database connection";
                $this->error_number = -1;
            }
        } else {
            $this->error_desc = $message;
            $this->error_number = $code;
        }

        if ($this->ThrowExceptions && $this->error_number !== 0) {
            throw new \RuntimeException($this->error_desc, $this->error_number);
        }

        if (!empty($this->debug)) {
            file_put_contents($this->debug, date("Y-m-d H:i:s") . " : ERROR: " . $this->sanitizeSqlForLog($this->error_desc) . PHP_EOL, FILE_APPEND);
        }
    }

    /**
     * Gets the duration of the last timed query.
     *
     * @param int $decimals Decimal places for formatting.
     * @return string Formatted duration in seconds.
     */
    public function TimerDuration(int $decimals = 4): string
    {
        return number_format($this->time_diff, $decimals);
    }

    /**
     * Starts the internal timer.
     */
    public function TimerStart(): void
    {
        $this->time_diff = 0.0;
        $this->time_start = microtime(true);
    }

    /**
     * Stops the internal timer and calculates duration.
     */
    public function TimerStop(): void
    {
        $this->time_diff = microtime(true) - $this->time_start;
    }

    /**
     * Begins a database transaction.
     *
     * @return bool True on success, false on failure (e.g., already in transaction).
     */
    public function TransactionBegin(): bool
    {
        $this->ResetError();
        if (!$this->IsConnected()) { $this->SetError("No connection"); return false; }

        if ($this->in_transaction) {
            $this->SetError("Already in transaction", -1);
            return false;
        }

        try {
            if (!mysqli_query($this->mysql_link, "START TRANSACTION")) { $this->SetError(); return false; }
            $this->in_transaction = true;
            return true;
        } catch (Exception $e) { $this->in_transaction = false; $this->SetError($e->getMessage(), isset($e->code) ? $e->code : -1); return false; }
    }

    /**
     * Commits the current transaction.
     *
     * @return bool True on success, false on failure.
     */
    public function TransactionEnd(): bool
    {
        $this->ResetError();
        if (!$this->IsConnected()) { $this->SetError("No connection"); return false; }
        if (!$this->in_transaction) { $this->SetError("Not in a transaction", -1); return false; }

        try {
            if (!mysqli_query($this->mysql_link, "COMMIT")) { $this->SetError(); $this->in_transaction = false; return false; }
            $this->in_transaction = false;
            return true;
        } catch (Exception $e) { $this->in_transaction = false; $this->SetError($e->getMessage(), isset($e->code) ? $e->code : -1); return false; }
    }

    /**
     * Rolls back the current transaction.
     *
     * @return bool True on success, false on failure.
     */
    public function TransactionRollback(): bool
    {
        $this->ResetError();
        if (!$this->IsConnected()) { $this->SetError("No connection"); return false; }
        if (!$this->in_transaction) { $this->SetError("Not in a transaction", -1); return false; }

        try {
            if (!mysqli_query($this->mysql_link, "ROLLBACK")) { $this->SetError("Unable to rollback"); $this->in_transaction = false; return false; }
            $this->in_transaction = false;
            return true;
        } catch (Exception $e) { $this->in_transaction = false; $this->SetError($e->getMessage(), isset($e->code) ? $e->code : -1); return false; }
    }

    /**
     * Checks if currently inside a transaction.
     *
     * @return bool True if in transaction.
     */
    public function IsInTransaction(): bool
    {
        return $this->in_transaction;
    }

    /**
     * Gets the transaction nesting depth (emulated, always 0 or 1).
     *
     * @return int 1 if in transaction, 0 otherwise.
     */
    public function GetTransactionDepth(): int
    {
        return $this->in_transaction ? 1 : 0;
    }

    /**
     * Truncates a table (removes all rows, resets auto_increment).
     *
     * @param string $tableName Table name.
     * @return bool True on success, false on error.
     */
    public function TruncateTable(string $tableName): bool
    {
        $this->ResetError();
        if ($tableName === '') { $this->SetError("Table name cannot be empty", -1); return false; }
        if (!$this->IsConnected()) { $this->SetError("No connection"); return false; }
        $sql = "TRUNCATE TABLE " . self::EscapeIdentifier($tableName);
        return $this->Query($sql) !== false;
    }

    /**
     * Updates rows matching WHERE conditions.
     *
     * @param string $tableName Table name.
     * @param array $valuesArray Column=>value pairs to set.
     * @param array $whereArray WHERE conditions (required to prevent mass update).
     * @return bool True on success, false on error.
     */
    public function UpdateRows(string $tableName, array $valuesArray, array $whereArray = []): bool
    {
        $this->ResetError();
        if ($tableName === '') { $this->SetError("Table name cannot be empty", -1); return false; }
        if ($valuesArray === []) { $this->SetError("Values array cannot be empty", -1); return false; }
        if ($whereArray === []) { $this->SetError("Where array cannot be empty for UpdateRows (prevents mass update)", -1); return false; }
        if (!$this->IsConnected()) { $this->SetError("No connection"); return false; }

        $sql = self::BuildSQLUpdate($tableName, $valuesArray, $whereArray, $this->getAutoEscapeMode());
        return $this->Query($sql) !== false;
    }

    /**
     * Saves the current query state (result, sql, pointer, insert_id) for internal sub-queries.
     *
     * @return array State snapshot.
     */
    private function saveQueryState(): array
    {
        return array(
            'last_result' => $this->last_result,
            'last_sql' => $this->last_sql,
            'active_row' => $this->active_row,
            'last_insert_id' => $this->last_insert_id,
        );
    }

    /**
     * Restores query state from a snapshot.
     *
     * @param array $state State array from saveQueryState().
     */
    private function restoreQueryState(array $state): void
    {
        $this->last_result = isset($state['last_result']) ? $state['last_result'] : null;
        $this->last_sql = isset($state['last_sql']) ? $state['last_sql'] : "";
        $this->active_row = isset($state['active_row']) ? $state['active_row'] : -1;
        $this->last_insert_id = isset($state['last_insert_id']) ? $state['last_insert_id'] : false;
    }

    /**
     * Selects rows from a table with full query building capabilities (WHERE, COLUMNS, ORDER BY, LIMIT, OFFSET).
     * Executes the query and stores result internally. Returns true on success, false on error.
     * Use RowCount(), RecordsArray(), RowArray(), Fetch() etc. to access results.
     * This restores the legacy v4.x API behavior.
     *
     * @param string $tableName Table name.
     * @param array|null $whereArray WHERE conditions (supports '_raw', operators in keys, etc.).
     * @param array|string|null $columns Columns to select (null = *).
     * @param array|string|null $sortColumns Column(s) to sort by.
     * @param bool $sortAscending Sort direction (true=ASC, false=DESC).
     * @param int|null $limit LIMIT count.
     * @param int|null $offset OFFSET count.
     * @param int $resultType Fetch mode (MYSQLI_ASSOC, MYSQLI_NUM, MYSQLI_BOTH). Default MYSQLI_BOTH.
     * @return bool True on success, false on error.
     */
    public function SelectRows(
        string $tableName,
        ?array $whereArray = null,
        array|string|null $columns = null,
        array|string|null $sortColumns = null,
        bool $sortAscending = true,
        int|null $limit = null,
        int|null $offset = null,
        int $resultType = MYSQLI_BOTH
    ): bool {
        $this->ResetError();
        if ($tableName === '') { $this->SetError("Table name cannot be empty", -1); return false; }
        if (!$this->IsConnected()) { $this->SetError("No connection"); return false; }

        $sql = self::BuildSQLSelect(
            $tableName,
            $whereArray,
            $columns,
            $sortColumns,
            $sortAscending,
            $limit,
            $offset,
            $this->getAutoEscapeMode()
        );

        // Execute query, store result in $this->last_result
        $result = $this->Query($sql);
        
        // Query() returns mysqli_result|bool. Success if object.
        return is_object($result);
    }

    /**
     * Selects all rows from a table (SELECT * FROM table).
     * Shortcut for SelectRows() with no filters.
     *
     * @param string $tableName Table name.
     * @return bool True on success, false on error.
     */
    public function SelectTable(string $tableName): bool
    {
        return $this->SelectRows($tableName);
    }

    /**
     * Returns a SQL-formatted value for a boolean condition (ternary).
     *
     * @param mixed $value The boolean test value.
     * @param mixed $trueValue Value to use if true.
     * @param mixed $falseValue Value to use if false.
     * @param string $datatype SQLValue datatype for the output values.
     * @return string SQL formatted value (e.g., '1', '0', "'Y'", "'N'").
     */
    public static function SQLBooleanValue(mixed $value, mixed $trueValue, mixed $falseValue, string $datatype = self::SQLVALUE_TEXT): string
    {
        return self::GetBooleanValue($value) ? self::SQLValue($trueValue, $datatype) : self::SQLValue($falseValue, $datatype);
    }

    /**
     * Escapes a string for safe use in SQL queries (mysqli_real_escape_string).
     * Requires active connection.
     *
     * @param string $value The string to escape.
     * @return string|bool Escaped string, or false if no connection.
     */
    public function SQLFix(string $value): string|bool
    {
        if (!$this->IsConnected()) { $this->SetError("No connection", -1); return false; }
        return mysqli_real_escape_string($this->mysql_link, $value);
    }

    /**
     * Formats a PHP value into a SQL literal string based on datatype.
     * Handles NULL, strings, numbers, booleans, dates (DateTime/ISO8601), blobs.
     *
     * @param mixed $value The value to format.
     * @param string $datatype One of SQLVALUE_* constants (text, number, date, datetime, time, boolean, y-n, t-f, bit).
     * @return string SQL literal (e.g., 'hello', 123, NULL, '2023-01-01').
     */
    public static function SQLValue(mixed $value, string $datatype = 'text'): string
    {
        $dt = strtolower(trim($datatype));
        $return_value = "";

        switch ($dt) {
            case "text": case "string": case "varchar": case "char":
                if (is_null($value) || $value === "") $return_value = "NULL";
                else $return_value = "'" . str_replace("'", "''", (string)$value) . "'";
                break;

            case "number": case "integer": case "int": case "double": case "float":
                $return_value = is_numeric($value) ? (string)$value : "NULL";
                break;

            case "boolean": case "bool": case "bit":
                $return_value = self::GetBooleanValue($value) ? "1" : "0";
                break;

            case "y-n":
                $return_value = self::GetBooleanValue($value) ? "'Y'" : "'N'";
                break;

            case "t-f":
                $return_value = self::GetBooleanValue($value) ? "'T'" : "'F'";
                break;

            case "date": case "datetime": case "time":
                if ($value instanceof DateTimeInterface) {
                    $format = ($dt === 'date') ? 'Y-m-d' : (($dt === 'time') ? 'H:i:s' : 'Y-m-d H:i:s');
                    $return_value = "'" . $value->format($format) . "'";
                } elseif (is_string($value)) {
                    $isValid = false;
                    if ($dt === 'date') $isValid = (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
                    elseif ($dt === 'datetime') $isValid = (bool)preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value);
                    elseif ($dt === 'time') $isValid = (bool)preg_match('/^\d{2}:\d{2}:\d{2}$/', $value);

                    if ($isValid) {
                        $return_value = "'$value'";
                    } else {
                        trigger_error("MySQL::SQLValue: Non-ISO 8601 date format for type '$datatype': '$value'. Using strtotime() as fallback (deprecated).", E_USER_DEPRECATED);
                        $ts = @strtotime($value);
                        if ($ts !== false) {
                            $format = ($dt === 'date') ? 'Y-m-d' : (($dt === 'time') ? 'H:i:s' : 'Y-m-d H:i:s');
                            $return_value = "'" . date($format, $ts) . "'";
                        } else {
                            $return_value = "NULL";
                        }
                    }
                } else {
                    $return_value = "NULL";
                }
                break;

            default:
                $return_value = "";
        }
        return $return_value;
    }

    /**
     * Fetches the current/next row as an object (stdClass).
     * Advances internal pointer. Seeks if row number provided.
     *
     * @param int|null $optional_row_number Zero-based row index to seek to (optional).
     * @return object|bool Row object, or false if no row / error.
     */
    public function Row(?int $optional_row_number = null): object|bool
    {
        $this->ResetError();
        if (!$this->last_result) { $this->SetError("No existing query result", -1); return false; }

        if ($optional_row_number !== null) {
            if ($optional_row_number < 0) { $this->SetError("Row number cannot be negative", -1); return false; }
            if ($this->Seek($optional_row_number) === false) return false;
        } else {
            if ($this->active_row === -1) { if (!$this->MoveFirst()) return false; }
            elseif ($this->active_row >= $this->RowCount()) return false;
        }

        if ($this->last_result instanceof mysqli_stmt) {
            $row = $this->Fetch(MYSQLI_ASSOC);
            if ($row === false) return false;
            return (object)$row;
        }

        $row = mysqli_fetch_object($this->last_result);
        if ($row === false) { $this->active_row = -1; return false; }
        $this->active_row++;
        return $row;
    }

    /**
     * Fetches the current/next row as an array.
     * Advances internal pointer. Seeks if row number provided.
     *
     * @param int|null $optional_row_number Zero-based row index to seek to (optional).
     * @param int $resultType Fetch mode (MYSQLI_ASSOC, MYSQLI_NUM, MYSQLI_BOTH).
     * @return array|bool Row array, or false if no row / error.
     */
    public function RowArray(?int $optional_row_number = null, int $resultType = MYSQLI_BOTH): array|bool
    {
        $this->ResetError();
        if (!$this->last_result) { $this->SetError("No existing query result", -1); return false; }

        if ($optional_row_number !== null) {
            if ($optional_row_number < 0) { $this->SetError("Row number cannot be negative", -1); return false; }
            if ($this->Seek($optional_row_number) === false) return false;
        } else {
            if ($this->active_row === -1) { if (!$this->MoveFirst()) return false; }
            elseif ($this->active_row >= $this->RowCount()) return false;
        }

        if ($this->last_result instanceof mysqli_stmt) {
            return $this->Fetch($resultType);
        }

        $row = mysqli_fetch_array($this->last_result, $resultType);
        if ($row === false) { $this->active_row = -1; return false; }
        $this->active_row++;
        return $row;
    }

    /**
     * Gets the number of rows in the last result set (SELECT) or affected rows (DML).
     * For unbuffered prepared statements without mysqlnd, returns false with error.
     *
     * @return int|string|bool Row count, affected rows, or false on error/unsupported.
     */
    public function RowCount(): int|string|bool
    {
        $this->ResetError();
        if (!$this->IsConnected()) { $this->SetError("No connection", -1); return false; }
        if (!$this->last_result) { $this->SetError("No existing query result", -1); return false; }

        if ($this->stmt_result !== null && is_object($this->stmt_result)) return @mysqli_num_rows($this->stmt_result);
        if ($this->stmt !== null && $this->stmt_meta !== null) { 
            $this->SetError("RowCount() not supported on prepared statements without mysqlnd. Use SELECT COUNT(*) or iterate with Fetch().", -1);
            return false;
        }

        $sql_trimmed = ltrim($this->last_sql);
        $sql_trimmed = preg_replace('/^\s*(?:--.*|\/\*.*?\*\/)\s*/s', '', $sql_trimmed);
        $sql_lower = strtolower($sql_trimmed);
        $isSelect = strpos($sql_lower, "select") === 0 || strpos($sql_lower, "show") === 0 || strpos($sql_lower, "describe") === 0 || strpos($sql_lower, "explain") === 0;

        if ($isSelect) {
            if (is_object($this->last_result)) {
                $count = @mysqli_num_rows($this->last_result);
                if ($count !== false) return $count;
            }
            return 0;
        }

        $result = @mysqli_affected_rows($this->mysql_link);
        return $result === -1 ? false : $result;
    }

    /**
     * Seeks the internal result pointer to a specific row number.
     * Not supported for unbuffered prepared statements.
     *
     * @param int $row_number Zero-based row index.
     * @return bool True on success, false on failure / out of bounds.
     */
    public function Seek(int $row_number): bool
    {
        $this->ResetError();
        $row_count = $this->RowCount();
        if ($row_count === false) return false;
        if ($row_number < 0 || $row_number > $row_count) { $this->SetError("Seek parameter out of bounds (0 to $row_count)", -1); $this->active_row = -1; return false; }

        if ($row_number == $row_count) { $this->active_row = $row_count; return true; }

        if ($this->last_result instanceof mysqli_stmt) {
            $this->SetError("Seek not supported on unbuffered prepared statement result. Use FetchAll() or enable mysqlnd.", -1);
            $this->active_row = -1;
            return false;
        }

        $result = @mysqli_data_seek($this->last_result, $row_number);
        if (!$result) { $this->SetError(); $this->active_row = -1; return false; }
        $this->active_row = $row_number;
        return true;
    }

    /**
     * Gets the current zero-based row pointer position.
     *
     * @return int Current position (-1 if before first, count if after last).
     */
    public function SeekPosition(): int
    {
        return $this->active_row;
    }

    /**
     * Selects the default database for the connection.
     *
     * @param string $database Database name.
     * @param string $charset Charset to set (optional).
     * @return bool True on success, false on failure.
     */
    public function SelectDatabase(string $database, string $charset = ""): bool
    {
        $this->ResetError();
        if (!$this->ensureConnected()) { $this->SetError("Connection lost and auto-reconnect disabled/failed", -1); return false; }
        if (!$charset) $charset = $this->db_charset;

        try {
            if (!mysqli_select_db($this->mysql_link, $database)) { $this->SetError(); return false; }
        } catch (Exception $e) { $this->SetError($e->getMessage(), isset($e->code) ? $e->code : -1); return false; }

        if (strlen($charset) > 0 && !$this->db_pcon) {
            $validCharsets = array('utf8', 'utf8mb4', 'latin1', 'latin2', 'cp1251', 'cp1252', 'ascii', 'binary', 'utf8mb3');
            if (!in_array(strtolower($charset), $validCharsets, true)) { $this->SetError("Invalid charset: $charset", -1); return false; }
            if (!@mysqli_set_charset($this->mysql_link, $charset)) { $this->SetError(); return false; }
        }
        return true;
    }
}
