<?php
/*
-------------------------------------------------------------------------
Module: \CMSMS\Database\mysqli\PrepResultSet (C) 2017 Robert Campbell
         <calguy1000@cmsmadesimple.org>
A class to represent a prepared-query result
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

class PrepResultSet extends \CMSMS\Database\ResultSet
{
    private $_stmt; // mysqli_stmt object
    private $_fields;
    private $_nrows;
    private $_pos;

    /**
     * @param $statmt mysqli_stmt object
     */
    public function __construct(\mysqli_stmt &$statmt, $buffer = false)
    {
        if ($buffer) {
            $statmt->store_result(); //grab the complete result-set
            $this->_nrows = $statmt->num_rows;
//            $this->_pos = ($this->_nrows > 0) ? 0 : -1;
        } else {
            $this->_nrows = PHP_INT_MAX;
//            $this->_pos = -1;
        }
        //setup for row-wise data fetching
        $fields = [];
        $data = []; // for pass-by-reference
        $rs = $statmt->result_metadata();
//        $c = 0; //DEBUG
        while ($field = $rs->fetch_field()) {
            $nm = $field->name;
//            $fields[$nm] = $c++; //DEBUG
            $fields[$nm] = null;
            $data[] = &$fields[$nm];
        }
        $this->_stmt = $statmt;
        if ($data) {
            $ares = call_user_func_array([$statmt, 'bind_result'], $data);
            if ($ares) {
                $this->_fields = $fields;
//                $this->move(0);
                $this->_stmt->fetch();
                $this->_pos = 0;
                return;
            }
        }
        $this->_fields = [];
    }

    public function fields($key = null)
    {
        if ($this->_fields) {
            if (empty($key)) {
                return $this->_fields;
            }
            $key = (string) $key;
            if (array_key_exists($key, $this->_fields)) {
                return $this->_fields[$key];
            }
        }

        return null;
    }

    public function currentRow()
    {
        if (!$this->EOF()) {
            return $this->_pos;
        }

        return false;
    }

    public function recordCount()
    {
        return $this->_nrows;
    }

    public function EOF()
    {
        return $this->_nrows == 0 || $this->_pos < 0 || $this->_pos >= $this->_nrows;
    }

    protected function move($idx)
    {
        if ($idx == $this->_pos) {
            return true;
        }
        if ($this->_stmt->data_seek($idx)) { //TODO never succeeds
            if ($this->_stmt->fetch()) {
                $this->_pos = $idx;

                return true;
            }
        }
        $this->_pos = -1;
        $this->_fields = [];

        return false;
    }

    public function moveFirst()
    {
        if ($this->_pos == 0) {
            return true;
        }

        return $this->move(0);
    }

    public function moveNext()
    {
        if ($this->_pos < $this->_nrows) {
            return $this->move($this->_pos + 1);
        }

        return false;
    }

    public function getArray()
    {
        $results = [];
        if (($c = $this->_nrows) > 0) {
            for ($i = 0; $i < $c; ++$i) {
                if ($this->move($i)) {
                    $results[] = $this->_fields;
                } else {
                    //TODO handle error
                    $this->_nrows = $i;
                    break;
                }
            }
        }

        return $results;
    }

    public function getAssoc($force_array = false, $first2cols = false)
    {
        $results = [];
        $c = $this->_nrows;
        $n = $this->_stmt->field_count;
        if ($c > 0 && $n > 1) {
            $first = key($this->_fields);
            $short = ($n == 2 || $first2cols) && !$force_array;
            for ($i = 0; $i < $c; ++$i) {
                if ($this->move($i)) {
                    $row = $this->_fields;
                    $results[trim($row[$first])] = ($short) ? next($row) : array_slice($row, 1);
                } else {
                    //TODO handle error
                    $this->_nrows = $i;
                    break;
                }
            }
        }

        return $results;
    }

    public function getCol($trim = false)
    {
        $results = [];
        if (($c = $this->_nrows) > 0) {
            $key = key($this->_fields);
            for ($i = 0; $i < $c; ++$i) {
                if ($this->move($i)) {
                    $results[] = ($trim) ? trim($this->_fields[$key]) : $this->_fields[$key];
                } else {
                    //TODO handle error
                    $this->_nrows = $i;
                    break;
                 }
            }
        }

        return $results;
    }

    protected function fetch_row()
    {
        if (!$this->EOF()) {
            $this->_stmt->fetch();
        }
    }
}
