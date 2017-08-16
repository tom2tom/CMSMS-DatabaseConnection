<?php
/*
-------------------------------------------------------------------------
Module: \CMSMS\Database\Connection (C) 2017 Robert Campbell
     <calguy1000@cmsmadesimple.org>
A class to define interaction with a database.
-------------------------------------------------------------------------
CMS Made Simple (C) 2004-2017 Ted Kulp <wishy@cmsmadesimple.org>
Visit our homepage at: http://www.cmsmadesimple.org
-------------------------------------------------------------------------
BEGIN_LICENSE
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

However, as a special exception to the GPL, this software is distributed
as an addon module to CMS Made Simple.  You may not use this software
in any Non GPL version of CMS Made simple, or in any version of CMS
Made simple that does not indicate clearly and obviously in its admin
section that the site was built with CMS Made simple.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
END_LICENSE
-------------------------------------------------------------------------
*/

namespace CMSMS\Database;

/**
 * A class defining a database connection, and mechanisms for working with a database.
 *
 * This library is largely compatible with adodb_lite with the pear, extended, transaction plugins
 * and with a few notable differences:
 *
 * Differences:
 * <ul>
 *  <li>genId will not automatically create a sequence table.
 *    <p>Consider using auto-increment fields instead of sequence tables where there's no possibility of a race.</p>
 *  </li>
 * </ul>
 *
 * @author Robert Campbell
 * @copyright Copyright (C) 2017 Robert Campbell <calguy1000@cmsmadesimple.org>
 *
 * @since 2.2
 *
 * @property-read float $query_time_total The total query time so far in this request (in seconds)
 * @property-read int $query_count The total number of queries executed so far
 */
abstract class Connection
{
    /**
     * This constant defines an error with connecting to the database.
     */
    const ERROR_CONNECT = 'CONNECT';

    /**
     * This constant defines an error with an execute statement.
     */
    const ERROR_EXECUTE = 'EXECUTE';

    /**
     * This constant defines an error with a transaction.
     */
    const ERROR_TRANSACTION = 'TRANSACTION';

    /**
     * This constant defines an error in a datadictionary command.
     */
    const ERROR_DATADICT = 'DATADICTIONARY';

    /**
     * @ignore
     */
    private $_debug = false;

    /**
     * @ignore
     */
    private $_debug_cb;

    /**
     * @ignore
     */
    private $_query_count = 0;

    /**
     * @ignore
     */
    private $_queries = [];

    /**
     * @ignore
     */
    private $_errorhandler;

    /**
     * The actual connectionspec object.
     *
     * @internal
     */
    protected $_connectionSpec;

    /**
     * The last SQL command executed.
     *
     * @internal
     *
     * @param string $sql
     */
    protected $sql;

    /**
     * Accumulated sql query time.
     *
     * @internal
     *
     * @param float $query_time_total
     */
    protected $query_time_total = 0;

    /**
     * Construct a new Connection.
     *
     * @param \CMSMS\Database\ConnectionSpec $spec
     */
    public function __construct(ConnectionSpec $spec)
    {
        $this->_connectionSpec = $spec;
    }

    /**
     * @ignore
     */
    public function __get($key)
    {
        if ($key == 'query_time_total') {
            return $this->query_time_total;
        }
        if ($key == 'query_count') {
            return $this->_query_count;
        }
    }

    /**
     * @ignore
     */
    public function __isset($key)
    {
        if ($key == 'query_time_total') {
            return true;
        }
        if ($key == 'query_count') {
            return true;
        }

        return false;
    }

    /**
     * Create a new data dictionary object.
     * Data Dictionary objects are used for manipulating tables, i.e: creating, altering and editing them.
     *
     * @deprecated - use new <namespace>DataDictionary(Connection-object)
     *
     * @return <namespace>DataDictionary
     */
    abstract public function NewDataDictionary();

    /**
     * Return the database type.
     *
     * @return string
     */
    abstract public function DbType();

    /**
     * Open the database connection.
     *
     * @return bool Success or failure
     */
    abstract public function connect();

    /**
     * An alias for close.
     */
    final public function Disconnect()
    {
        return $this->close();
    }

    /**
     * Test if the connection object is connected to the database.
     *
     * @return bool
     */
    abstract public function isConnected();

    /**
     * Close the database connection.
     */
    abstract public function close();

    //// utilities

    /**
     * An alias for the qStr method.
     *
     * @deprecated
     *
     * @param string $str
     *
     * @return string
     */
    public function QMagic($str)
    {
        return $this->qStr($str);
    }

    /**
     * Quote a string in a database agnostic manner.
     * Warning: This method may require two way traffic with the database depending upon the database.
     *
     * @param string $str
     *
     * @return string
     */
    abstract public function qStr($str);

    /**
     * qStr without surrounding quotes.
     *
     * @param string $str
     *
     * @return string
     */
    abstract public function addQ($str);

    /**
     * output the mysql expression for a string concatenation.
     * This function accepts a variable number of string arguments.
     *
     * @param $str   First string to concatenate
     * @param $str,. Any number of strings to concatenate
     *
     * @return string
     */
    abstract public function concat();

    /**
     * Output the mysql expression to test if an item is null.
     *
     * @param string $field  The field to test
     * @param string $ifNull The value to use if $field is null
     *
     * @return string
     */
    abstract public function ifNull($field, $ifNull);

    /**
     * Output the number of rows affected by the last query.
     *
     * @return int
     */
    abstract public function affected_rows();

    /**
     * Return the numeric ID of the last insert query into a table with an auto-increment field.
     *
     * @return int
     */
    abstract public function insert_id();

    //// primary query functions

    /**
     * The primary function for communicating with the database.
     *
     * @internal
     *
     * @param string $sql The SQL query
     */
    abstract protected function do_sql($sql);

    /**
     * Prepare (compile) @sql for parameterized and/or repeated execution.
     *
     * @param string $sql The SQL query
     *
     * @return a Statement object if @sql is valid, or false
     */
    abstract public function prepare($sql);

    /**
     * Parse and execute an SQL prepared statement or query.
     *
     * @param string or Statement object $sql
     * @param optional array             $valsarr Value-parameters to fill placeholders (if any) in @sql
     *
     * @return <namespace>ResultSet or a subclass of that
     */
    abstract public function execute($sql, $valsarr = null);

    /**
     * Execute an SQL command, to retrieve (at most) @nrows records.
     *
     * @param string         $sql     The SQL to execute
     * @param optional int   $nrows   The number of rows to return, default all (-1)
     * @param optional int   $offset  0-based starting-offset of rows to return, default -1
     * @param optional array $valsarr Value-parameters to fill placeholders (if any) in @sql
     *
     * @return mixed <namespace>ResultSet or a subclass
     */
    public function selectLimit($sql, $nrows = -1, $offset = -1, $valsarr = null)
    {
        if ($nrows >= 1 || $offset >= 0) {
            $offset = ($offset >= 0) ? $offset.',' : '';
            if ($nrows < 1) {
                $nrows = PHP_INT_MAX;
            }
            $sql .= ' LIMIT '.$offset.$nrows;
        }

        return $this->execute($sql, $valsarr);
    }

    /**
     * Execute an SQL statement and return all the results as an array.
     *
     * @param string $sql     The SQL to execute
     * @param array  $valsarr Value-parameters to fill placeholders (if any) in @sql
     *
     * @return numeric-keyed array of matched results, or empty
     */
    public function getArray($sql, $valsarr = null)
    {
        $rs = $this->execute($sql, $valsarr);
        if (!$rs->EOF()) {
            return $rs->getArray();
        }

        return [];
    }

    /**
     * An alias for the getArray method.
     *
     * @param string $sql     The SQL statement to execute
     * @param array  $valsarr Value-parameters to fill placeholders (if any) in @sql
     *
     * @return array Numeric-keyed matched results, or empty
     */
    public function getAll($sql, $valsarr = null)
    {
        return $this->getArray($sql, $valsarr);
    }

    /**
     * Execute an SQL statement and return all the results as an array, with
     * the value of the first-requested-column as the key for each row.
     *
     * @param string $sql         The SQL statement to execute
     * @param array  $valsarr     VAlue-parameters to fill placeholders (if any) in @sql
     * @param bool   $force_array Optionally force each element of the output to be an associative array
     * @param bool   $first2cols  Optionally output only the first 2 columns in an associative array.  Does not work with force_array
     *
     * @return associative array of matched results, or empty
     */
    public function getAssoc($sql, $valsarr = null, $force_array = false, $first2cols = false)
    {
        $rs = $this->execute($sql, $valsarr);
        if (!$rs->EOF()) {
            return $rs->getAssoc($force_array, $first2cols);
        }

        return [];
    }

    /**
     * Execute an SQL statement that returns one column, and return all of the
     * matches as an array.
     *
     * @param string $sql     The SQL statement to execute
     * @param array  $valsarr Value-parameters to fill placeholders (if any) in @sql
     * @param bool   $trim    Optionally trim the output results
     *
     * @return array of results, one member per row matched, or empty
     */
    public function getCol($sql, $valsarr = null, $trim = false)
    {
        $rs = $this->execute($sql, $valsarr);
        if (!$rs->EOF()) {
            return $rs->getCol($trim);
        }

        return [];
    }

    /**
     * Execute an SQL statement that returns one row of results, and return that row
     * as an associative array.
     *
     * @param string $sql     The SQL statement to execute
     * @param array  $valsarr Value-parameters to fill placeholders (if any) in @sql
     *
     * @return associative array representing a single ResultSet row, or empty
     */
    public function getRow($sql, $valsarr = null)
    {
        if (stripos($sql, 'LIMIT') !== false) {
            $sql .= ' LIMIT 1';
        }
        $rs = $this->execute($sql, $valsarr);
        if (!$rs->EOF()) {
            return $rs->fields();
        }

        return [];
    }

    /**
     * Execute an SQL statement and return a single value.
     *
     * @param string $sql     The SQL statement to execute
     * @param array  $valsarr Parameters to fill placeholders (if any) in @sql
     *
     * @return mixed value or null
     */
    public function getOne($sql, $valsarr = null)
    {
        if (stripos($sql, 'LIMIT') !== false) {
            $sql .= ' LIMIT 1';
        }
        $rs = $this->execute($sql, $valsarr);
        if (!$rs->EOF()) {
            return reset($rs->fields());
        }

        return null;
    }

    /**
     * Get the median value of data recorded in a table field.
     *
     * @param string          $table  The name of the table
     * @param string          $column The name of the column in @table
     * @param optional string $where  SQL condition, must include the
     *                                requested column e.g. “WHERE name > 'A'”
     *
     * @return mixed value or null
     */
    public function getMedian($table, $column, $where = '')
    {
        if ($where && stripos($sql, 'WHERE') === false) {
            return null;
        }
        $rs = $this->execute("SELECT COUNT(*) AS num FROM $table $where");
        if (!$rs->EOF()) {
            $mid = (int) $rs->fields('num') / 2;
            $rs = $this->selectLimit("SELECT $column FROM $table $where ORDER BY $column", 1, $mid);

            return $rs->fields($column);
        }

        return null;
    }

    //// transactions

    /**
     * Begin a transaction.
     */
    abstract public function beginTrans();

    /**
     * Begin a smart transaction.
     */
    abstract public function startTrans();

    /**
     * Complete a smart transaction.
     * This method will either do a rollback or a commit depending upon if errors
     * have been detected.
     */
    abstract public function completeTrans();

    /**
     * Commit a simple transaction.
     *
     * @param bool $ok Indicates whether there is success or not
     */
    abstract public function commitTrans($ok = true);

    /**
     * Roll back a simple transaction.
     */
    abstract public function rollbackTrans();

    /**
     * Mark a transaction as failed.
     */
    abstract public function failTrans();

    /**
     * Test if a transaction has failed.
     *
     * @return bool
     */
    abstract public function hasFailedTrans();

    //// sequence table stuff
    // these methods are compatibility-stubs for DataDictionary methods

    /**
     * For use with sequence tables, this method will generate a new ID value.
     *
     * This function will not automatically create the sequence table if not specified.
     *
     * @param string $seqname The name of the sequence table
     *
     * @return int
     *
     * @deprecated
     */
    abstract public function genId($seqname);

    /**
     * Create a new sequence table.
     *
     * @param string $seqname the name of the sequence table
     * @param int    $startID
     *
     * @return bool
     *
     * @deprecated
     */
    abstract public function createSequence($seqname, $startID = 0);

    /**
     * Drop a sequence table.
     *
     * @param string $seqname The name of the sequence table
     *
     * @return bool
     */
    abstract public function dropSequence($seqname);

    //// time and date stuff

    /**
     * A utility method to convert a unix timestamp into a database specific string suitable
     * for use in queries.
     *
     * @param int $timestamp
     *
     * @return string
     */
    public function DBTimeStamp($timestamp)
    {
        if (empty($timestamp) && $timestamp !== 0) {
            return 'null';
        }

        // strlen(14) allows YYYYMMDDHHMMSS format
        if (is_string($timestamp)) {
            if (!preg_match('/[0-9-\s:]*/', $timestamp)) {
                return 'null';
            }
            $tmp = strtotime($timestamp);
            if ($tmp < 1) {
                return;
            }
            $timestamp = $tmp;
        }
        if ($timestamp > 0) {
            return date("'Y-m-d H:i:s'", $timestamp);
        }
    }

    /**
     * A convenience method for converting a database specific string representing a date and time
     * into a unix timestamp.
     *
     * @param string $str
     *
     * @return int
     */
    public function UnixTimeStamp($str)
    {
        return strtotime($str);
    }

    /**
     * Convert a date into something that is suitable for writing to a database.
     *
     * @param mixed $date Either a string date, or an integer timestamp
     *
     * @return string
     */
    public function DBDate($date)
    {
        if (empty($date) && $date !== 0) {
            return 'null';
        }

        if (is_string($date) && !is_numeric($date)) {
            if ($date === 'null' || strncmp($date, "'", 1) === 0) {
                return $date;
            }
            $date = $this->UnixDate($date);
        }

        return strftime('%x', $date);
    }

    /**
     * Generate a unix timestamp representing the current date at midnight.
     *
     * @deprecated
     *
     * @return int
     */
    public function UnixDate()
    {
        return strtotime('today midnight');
    }

    /**
     * An alias for the UnixTimestamp method.
     *
     * @return int
     */
    public function Time()
    {
        return $this->UnixTimeStamp();
    }

    /**
     * An Alias for the UnixDate method.
     *
     * @return int
     */
    public function Date()
    {
        return $this->UnixDate();
    }

    //// error and debug message handling

    /**
     * Return a string describing the latest error (if any).
     *
     * @return string
     */
    abstract public function errorMsg();

    /**
     * Return the latest error number (if any).
     *
     * @return int
     */
    abstract public function ErrorNo();

    /**
     * Set an error handler function.
     *
     * @param callable $fn
     */
    public function SetErrorHandler($fn = null)
    {
        if ($fn && is_callable($fn)) {
            $this->_errorhandler = $fn;
        } else {
            $this->_errorhandler = null;
        }
    }

    /**
     * Toggle debug mode.
     *
     * @param bool     $flag          Enable or Disable debug mode
     * @param callable $debug_handler
     */
    public function SetDebugMode($flag = true, $debug_handler = null)
    {
        $this->_debug = (bool) $flag;
        if ($debug_handler && is_callable($this->_debug_handler)) {
            $this->_debug_cb = $debug_handler;
        }
    }

    /**
     * Set the debug callback.
     *
     * @param callable $debug_handler
     */
    public function SetDebugCallback(callable $debug_handler = null)
    {
        $this->_debug_cb = $debug_handler;
    }

    /**
     * Add a query to the debug log.
     *
     * @internal
     *
     * @param string $sql the SQL statement
     */
    protected function add_debug_query($sql)
    {
        if ($this->_debug) {
            ++$this->_query_count;
            if ($this->_debug_cb) {
                call_user_func($this->_debug_cb, $sql);
            }
        }
    }

    /**
     * A callback that is called when a database error occurs.
     * This method will by default call the error handler if it has been set.
     * If no error handler is set, an exception will be thrown.
     *
     * @internal
     *
     * @param string $errtype       The type of error
     * @param int    $error_number  The error number
     * @param string $error_message The error message
     */
    protected function OnError($errtype, $error_number, $error_message)
    {
        if ($this->_errorhandler && is_callable($this->_errorhandler)) {
            call_user_func($this->_errorhandler, $this, $errtype, $error_number, $error_message);

            return;
        }

        switch ($errtype) {
         case self::ERROR_CONNECT:
            throw new DatabaseConnectionException($error_message, $error_number);
         case self::ERROR_EXECUTE:
            throw new DatabaseException($error_message, $error_number, $this->sql, $this->_connectionSpec);
        }
    }

    //// initialization

    /**
     * Create a new database connection object.
     * This is the preferred way to open a new database connection.
     *
     * @param \CMSMS\Database\Connectionspec $spec An object describing the database to connect to
     *
     * @return \CMSMS\Database\Connection
     *
     * @todo  Move this into a factory class
     */
    public static function Initialize(ConnectionSpec $spec)
    {
        if (!$spec->valid()) {
            throw new ConnectionSpecException('Invalid or incorrect configuration information');
        }
        $connection_class = '\\CMSMS\\Database\\'.$spec->type.'\\Connection';
        if (!class_exists($connection_class)) {
            throw new \LogicException('Could not find a database abstraction layer named '.$spec->type);
        }

        $obj = new $connection_class($spec);
        if (!($obj instanceof self)) {
            throw new \LogicException("$connection_class is not derived from the primary database class.");
        }
        if ($spec->debug) {
            $obj->SetDebugMode();
        }
        $obj->connect();

        if ($spec->auto_exec) {
            $obj->execute($spec->auto_exec);
        }

        return $obj;
    }
}

/**
 * A special type of exception related to database queries.
 */
class DatabaseException extends \LogicException
{
    /**
     * @internal
     */
    protected $_connection;

    /**
     * @internal
     */
    protected $_sql;

    /**
     * Constructor.
     *
     * @param string $msg    The message string
     * @param int    $number The error number
     * @param string $sql    The related SQL statement, if any
     * @param \CMSMS\Database\ConnectionSpec The connection specification
     */
    public function __construct($msg, $number, $sql, ConnectionSpec $connection)
    {
        parent::__construct($msg, $number);
        $this->_connection = $connection;
        $this->_sql = $sql;
    }

    /**
     * Get the SQL statement related to this exception.
     *
     * @return string
     */
    public function getSQL()
    {
        return $this->_sql;
    }

    /**
     * Get the Connectionspec that was used when generating the error.
     *
     * @return \CMSMS\Database\ConnectionSpec
     */
    public function getConnectionSpec()
    {
        return $this->_connection;
    }
}

/**
 * A special exception indicating a problem connecting to the database.
 */
class DatabaseConnectionException extends \Exception
{
}
