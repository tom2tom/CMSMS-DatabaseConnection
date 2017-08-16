<?php
/*
-------------------------------------------------------------------------
Module: \CMSMS\Database\mysqli\Connection (C) 2017 Robert Campbell
         <calguy1000@cmsmadesimple.org>
A class to represent a MySQL database connection
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

class Connection extends \CMSMS\Database\Connection
{
    protected $_mysql;
    protected $_in_transaction = 0;
    protected $_in_smart_transaction = 0;
    protected $_transaction_status = true;

    public function dbType()
    {
        return 'mysqli';
    }

    public function connect()
    {
        if (!class_exists('\mysqli')) {
            //$this->OnError(parent::ERROR_X, -1, 'message');
            throw new \Exception('Configuration error... mysqli functions are not available');
        }

        mysqli_report(MYSQLI_REPORT_STRICT);
        try {
            $this->_mysql = new \mysqli($this->_connectionSpec->host, $this->_connectionSpec->username,
                                         $this->_connectionSpec->password,
                                         $this->_connectionSpec->dbname,
                                         (int) $this->_connectionSpec->port);
            if ($this->_mysql->connect_error) {
                $this->_mysql = null;
                $this->OnError(parent::ERROR_CONNECT, mysqli_connect_errno(), mysqli_connect_error());

                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->_mysql = null;
            $this->OnError(parent::ERROR_CONNECT, mysqli_connect_errno(), mysqli_connect_error());

            return false;
        }
    }

    public function NewDataDictionary()
    {
        return new DataDictionary($this);
    }

    public function close()
    {
        if ($this->_mysql) {
            $this->_mysql->close();
            $this->_mysql = null;
        }
    }

    public function get_inner_mysql()
    {
        return $this->_mysql;
    }

    public function isConnected()
    {
        return is_object($this->_mysql);
    }

    public function errorMsg()
    {
        if ($this->_mysql) {
            return $this->_mysql->error;
        }

        return mysqli_connect_error();
    }

    public function ErrorNo()
    {
        if ($this->_mysql) {
            return $this->_mysql->errno;
        }

        return mysqli_connect_errno();
    }

    public function affected_rows()
    {
        return $this->_mysql->affected_rows;
    }

    public function insert_id()
    {
        return $this->_mysql->insert_id;
    }

    public function qStr($str)
    {
        // note... this could be a two way tcp/ip or socket communication
        return "'".$this->_mysql->escape_string($str)."'";
    }

    public function addQ($str)
    {
        return $this->_mysql->escape_string($str);
    }

    public function concat()
    {
        $arr = func_get_args();
        $list = implode(', ', $arr);

        if (strlen($list) > 0) {
            return "CONCAT($list)";
        }
    }

    public function ifNull($field, $ifNull)
    {
        return " IFNULL($field, $ifNull)";
    }

    protected function do_multisql($sql)
    {
        // no error checking for this stuff
        // and no return data
        $_t = $this->_mysql->multi_query($sql);
        if ($_t) {
            do {
                $res = $this->_mysql->store_result();
            } while ($this->_mysql->more_results() && $this->_mysql->next_result());
        }
    }

    /**
     * @param string sql SQL statment to be executed
     *
     * @return ResultSet object, or null
     */
    protected function do_sql($sql)
    {
        $this->sql = $sql;
        if ($this->_debug) {
            $time_start = microtime(true);
            $result = $this->_mysql->query($sql); //mysqli_result or boolean
            $this->query_time_total += microtime(true) - $time_start;
        } else {
            $result = $this->_mysql->query($sql);
        }
        if ($result) {
            $this->add_debug_query($sql);

            return new ResultSet($this, $result);
        }
        $this->failTrans();
        $this->OnError(parent::ERROR_EXECUTE, $this->_mysql->errno, $this->_mysql->error);

        return new \CMSMS\Database\EmptyResultSet();
    }

    public function prepare($sql)
    {
        $stmt = new Statement($this, $sql);
        try {
            $stmt->prepare($sql);

            return $stmt;
        } catch (\LogicException $e) {
            return false;
        }
    }

    public function execute($sql, $valsarr = null)
    {
        if ($valsarr) {
            if (!is_array($valsarr)) {
                $valsarr = [$valsarr];
            }
            if (is_string($sql)) {
                $stmt = new Statement($this);
                try {
                    $stmt->prepare($sql);

                    return $stmt->execute($valsarr);
                } catch (\LogicException $e) {
                    //TODO log debug messsage
                    $adbg = $e->messsage;
                    $X1 = $CRASH;
                }
            } elseif (is_object($sql) && $sql instanceof CMSMS\Database\mysqli\Statement) {
                try {
                    return $sql->execute($valsarr);
                } catch (\LogicException $e) {
                    //TODO log debug messsage
                    $adbg = $e->messsage;
                    $X2 = $CRASH;
                }
            }

            return new \CMSMS\Database\EmptyResultSet();
        }

        return $this->do_sql($sql);
    }

    public function beginTrans()
    {
        if (!$this->_in_smart_transaction) {
            // allow nesting in this case.
            ++$this->_in_transaction;
            $this->_transaction_failed = false;
            $this->do_sql('BEGIN');
        }

        return true;
    }

    public function startTrans()
    {
        if ($this->_in_smart_transaction) {
            ++$this->_in_smart_transaction;

            return true;
        }

        if ($this->_in_transaction) {
            $this->OnError(parent::ERROR_TRANSACTION, -1, 'Bad Transaction: startTrans called within beginTrans');

            return false;
        }

        $this->_transaction_status = true;
        ++$this->_in_smart_transaction;
        $this->beginTrans();

        return true;
    }

    public function rollbackTrans()
    {
        if (!$this->_in_transaction) {
            $this->OnError(parent::ERROR_TRANSACTION, -1, 'beginTrans has not been called');

            return false;
        }

        --$this->_in_transaction;
        $this->do_sql('ROLLBACK');

        return true;
    }

    public function commitTrans($ok = true)
    {
        if (!$ok) {
            return $this->rollbackTrans();
        }

        if (!$this->_in_transaction) {
            $this->OnError(parent::ERROR_TRANSACTION, -1, 'beginTrans has not been called');

            return false;
        }

        --$this->_in_transaction;
        $this->do_sql('COMMIT');

        return true;
    }

    public function completeTrans($autoComplete = true)
    {
        if ($this->_in_smart_transaction > 0) {
            --$this->_in_smart_transaction;

            return true;
        }

        if ($this->_transaction_status && $autoComplete) {
            if (!$this->commitTrans()) {
                $this->_transaction_status = false;
            }
        } else {
            $this->rollbackTrans();
        }
        $this->_in_smart_transaction = 0;

        return $this->_transaction_status;
    }

    public function failTrans()
    {
        $this->_transaction_status = false;
    }

    public function hasFailedTrans()
    {
        if ($this->_in_smart_transaction > 0) {
            return $this->_transaction_status == false;
        }

        return false;
    }

    public function genId($seqname)
    {
        $this->do_sql("UPDATE $seqname SET id=id+1");

        return (int) $this->getOne("SELECT id FROM $seqname");
    }

    public function createSequence($seqname, $startID = 0)
    {
        $res = $this->do_sql("CREATE TABLE $seqname (id INT NOT NULL) ENGINE MyISAM");
        if ($res) {
            $v = (int) $startID;
            $res = $this->do_sql("INSERT INTO $seqname (id) values ($v)");
        }

        return $res !== false;
    }

    public function dropSequence($seqname)
    {
        $res = $this->do_sql("DROP TABLE $seqname");

        return $res !== false;
    }
}
