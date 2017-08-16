<?php
/*
-------------------------------------------------------------------------
Module: \CMSMS\Database\mysqli\Statement (C) 2017 Robert Campbell
         <calguy1000@cmsmadesimple.org>
A class to represent a prepared SQL statement
-------------------------------------------------------------------------
CMS Made Simple (C) 2004-2017 Ted Kulp <wishy@cmsmadesimple.org>
Visit our homepage at: http:www.cmsmadesimple.org
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
Or read it online: http:www.gnu.org/licenses/licenses.html#GPL
END_LICENSE
-------------------------------------------------------------------------
*/

namespace CMSMS\Database\mysqli;

/**
 * A class defining a prepared database statement.
 *
 * @author Robert Campbell
 * @copyright Copyright (C) 2017, Robert Campbell <calguy1000@cmsmadesimple.org>
 *
 * @since 2.2
 *
 * @property-read Connection $db The database connection
 * @property-read string $sql The SQL query
 */
class Statement
{
    /**
     * @ignore
     */
    protected $_conn; // Connection object
    /**
     * @ignore
     */
    protected $_sql;
    /**
     * @ignore
     */
    protected $_stmt; // mysqli_stmt object
    /**
     * @ignore
     */
    protected $_prep = false;
    protected $_bound = false;
    /**
     * @ignore
     */
    protected $_native = ''; //for PHP 5.4+, the MySQL native driver is a php.net compile-time default

    /**
     * Constructor.
     *
     * @param Connection      $conn The database connection
     * @param optional string $sql  The SQL query, default null
     */
    public function __construct(Connection $conn, $sql = null)
    {
        $this->_conn = $conn;
        $this->_sql = $sql;
    }

    public function __destruct()
    {
        if ($this->_stmt) {
            if ($this->_bound) {
                $this->_stmt->free_result();
            }
            if ($this->_prep) {
                $this->_stmt->close();
            }
        }
    }

    /**
     * @ignore
     */
    public function __get($key)
    {
        switch ($key) {
         case 'db':
         case 'conn':
            return $this->_conn;
         case 'sql':
            return $this->_sql;
        }
    }

    /**
     * @internal
     */
    protected function isNative()
    {
        if ($this->_native === '') {
            $this->_native = function_exists('mysqli_fetch_all');
        }

        return $this->_native;
    }

    /**
     * Prepare the query.
     *
     * @param optional string $sql parameterized SQL command default null
     */
    public function prepare($sql = null)
    {
        $mysql = $this->_conn->get_inner_mysql();
        if (!$mysql || !$this->_conn->isConnected()) {
            //$this->_conn->OnError(parent::ERROR_X, -1, 'message');
            throw new \Exception('Attempt to create prepared statement when database is not connected');
        }
        if (!($sql || $this->_sql)) {
            //$this->_conn->OnError(parent::ERROR_X, -1, 'message');
            throw new \Exception('No SQL to prepare');
        }
        if (!$sql) {
            $sql = $this->_sql;
        } else {
            $this->_sql = $sql;
        }
        $this->_stmt = $mysql->stmt_init();
        if ($this->_stmt->prepare((string) $sql)) {
            $this->_prep = true;
        } else {
            $this->_prep = false;
            //$this->_conn->OnError(parent::ERROR_X, -1, 'message');
            throw new \Exception('Failed to create prepared statement');
        }
    }

    /**
     * Bind data (suppied as argument(s) here) to the sql statement.
     */
    public function bind()
    {
        if (!$this->_stmt) {
            if ($this->_sql) {
                try {
                    $this->prepare($this->_sql);
                } catch (\Exception $e) {
                    throw $e;
                }
            } else {
                //$this->_conn->OnError(parent::ERROR_X, -1, 'message');
                throw new \Exception('No SQL to bind to');
            }
        }

        $args = func_get_args();
        if (is_array($args) && count($args) == 1 && is_array($args[0])) {
            $args = $args[0];
        }
        $types = '';
        $bound = [''];
        foreach ($args as $k => &$val) {
            switch (gettype($val)) {
             case 'double': //i.e. float
//          $val = strtr($val, ',', '.');
                $types .= 'd';
                break;
             case 'boolean':
                $args[$k] = $val ? 1 : 0;
             case 'integer':
                $types .= 'i';
                break;
//             case 'string':
//TODO handle blobs for data > max_allowed_packet, send them using ::send_long_data()
// to get the max_allowed_packet
//$mysql = $this->_conn->get_inner_mysql();
//$maxp = $mysql->query('SELECT @@global.max_allowed_packet')->fetch_array();
//             case 'array':
//             case 'object':
//             case 'resource':
//                $val = serialize($val);
//                $types .= 's';
//                break;
//             case 'NULL':
//             case 'unknown type':
             default:
                $types .= 's';
                break;
            }
            $bound[] = &$args[$k];
        }
        unset($val);
        $bound[0] = $types;

        if ($this->_bound) {
            $this->_stmt->free_result();
        }

        if (call_user_func_array([$this->_stmt, 'bind_param'], $bound)) {
            $this->_bound = true;

            return;
        }
        $this->_bound = false;
        //$this->_conn->OnError(parent::ERROR_X, -1, 'message');
        throw new \Exception('Failed to bind paramers to prepared statement');
    }

    /**
     * Execute the query, using supplied arguments (if any) as bound values.
     *
     * @return object: ResultSet or EmptyResultSet or PrepResultSet
     */
    public function execute()
    {
        if (!$this->_stmt) {
            if ($this->_sql) {
                try {
                    $this->prepare($this->_sql);
                } catch (\Exception $e) {
                    throw $e;
                }
            } else {
                //$this->_conn->OnError(parent::ERROR_X, -1, 'message');
                throw new \BadFunctionCallException('No SQL to prepare');
            }
        }

        $pc = $this->_stmt->param_count;
        $args = func_get_args();
        if ($args) {
            if (is_array($args) && count($args) == 1 && is_array($args[0])) {
                $args = $args[0];
            }
            if ($pc != count($args)) {
                //$this->_conn->OnError(parent::ERROR_X, -1, 'message');
                throw new \BadFunctionCallException('Incorrect number of bound parameters - should be '.$pc);
            }
            try {
                $this->bind($args);
            } catch (\Exception $e) {
                throw $e;
            }
        } elseif ($pc > 0 && !$this->_bound) {
            //$this->_conn->OnError(parent::ERROR_X, -1, 'message');
            throw new \BadFunctionCallException('No bound parameters, and no arguments passed');
        }

        if (!$this->_stmt->execute()) {
            //$this->_conn->OnError(parent::ERROR_X, -1, 'message');
            throw new \Exception('ERROR: '.$this->_stmt->error);
        }

        if ($this->_stmt->field_count > 0) {
            if ($this->isNative()) {
                $rs = $this->_stmt->get_result(); //mysqli_result or false
                if ($rs) {
                    return new ResultSet($this->conn, $rs);
                } elseif ($this->_stmt->errno > 0) {
                    //$this->_conn->OnError(parent::ERROR_X, -1, 'message');
                    throw new \Exception('ERROR: '.$this->_stmt->error);
                } else { //should never happen
                    return new \CMSMS\Database\EmptyResultSet();
                }
            } else {
                return new PrepResultSet($this->conn, $this->_stmt);
            }
        } else {
            return new \CMSMS\Database\EmptyResultSet();
        }
    }
}
