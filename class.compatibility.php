<?php
/*
A collection of compatibility tools for the database connectivity layer.
Copyright (C) 2017-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

namespace CMSMS\Database {

    /**
     * A class for providing some compatibility functionality with older module code.
     *
     * @todo: Move this class to a different function and rename.
     */
    final class compatibility
    {
        /**
         * Initialize the database connection according to config settings.
         *
         * @return \CMSMS\Database\mysqli\Connection
         *
         * @deprecated
         */
        public static function init()
        {
            return new mysqli\Connection();
        }

        public static function on_error()
        {
            // do nothing
        }

        /**
         * No-op function that allows the autoloader to load this file.
         */
        public static function noop()
        {
            // do nothing
        }

        /**
         * For parameterized SQL commands which cannot be natively prepared.
         * Interpret '?'-parameterized $sql and corresponding $valsarr
         * into a non-parameterized command, i.e. emulate parameterization.
		 *
         * @param object $conn the database-connection object
         * @param string $sql the command
         * @param mixed  $valsarr array of command-parameter value[s], or a single scalar value
         * @return mixed replacment command or null
         *
		 * @since 2.3
         */
        public static function interpret(Connection &$conn, $sql, $valsarr)
        {
            if ($valsarr) {
                if (!is_array($valsarr)) {
                    $valsarr = [$valsarr];
                }

                $sqlarr = explode('?', $sql);
                $i = 0;
                $sql = '';
                foreach ($valsarr as $v) {
                    $sql .= $sqlarr[$i];
                    switch (gettype($v)) {
                        case 'string':
                            $sql .= $conn->qstr($v); //or after FILTER_SANITIZE_* filtering ?
                            break;
                        case 'boolean':
                            $sql .= $v ? '1' : '0';
                            break;
                        case 'integer':
                            $sql .= $v;
                            break;
                        case 'double': //a.k.a. float
                            $sql .= strtr($v, ',', '.');
                            break;
                        default:
                            if ($v === null) {
                                $sql .= 'NULL';
                            } else {
                                return null;
                            }
                    }
                    ++$i;
                }
                if (sizeof($sqlarr) != $i+1) {
                    return null;
                }
                $sql .= $sqlarr[$i];
            }
            return $sql;
        }
    }
} // end of namespace

namespace {
    // root namespace stuff

    /*
     * A constant to assist with date and time flags in the data dictionary.
     *
     * @name CMS_ADODB_DT
     */
    define('CMS_ADODB_DT', 'DT'); // backwards compatibility.

    /**
     * A method to create a new data dictionary object.
     *
     * @param \CMSMS\Database\Connection $conn The existing database connection
     *
     * @return \CMSMS\Database\DataDictionary
     *
     * @deprecated
     */
    function NewDataDictionary(\CMSMS\Database\Connection $conn)
    {
        // called by module installation routines.
        return $conn->NewDataDictionary();
    }

    /**
     * A function co create a new adodb database connection.
     *
     * @param string $dbms
     * @param string $flags
     *
     * @return \CMSMS\Database\Connection
     *
     * @deprecated
     */
    function ADONewConnection($dbms, $flags)
    {
        // now that our connection object is stateless... this is just a wrapper
        // for our global db instance.... but should not be called.
        return \CmsApp::get_instance()->GetDb();
    }

    /**
     * A function formerly used to load the adodb library.
     * This method currently has no functionality.
     *
     * @deprecated
     */
    function load_adodb()
    {
        // this should only have been called by the core
        // but now does nothing, just in case it is called.
    }

    /**
     * An old method formerly used to ensure that we were re-connected to the proper database.
     * This method currently has no functionality.
     *
     * @deprecated
     */
    function adodb_connect()
    {
        // this may be called by UDT's etc. that are talking to other databases
        // or using manual mysql methods.
    }

    /**
     * An old function for handling a database error.
     *
     * @param string $dbtype
     * @param string $function_performed
     * @param int    $error_number
     * @param string $error_message
     * @param string $host
     * @param string $database
     * @param mixed  $connection_obj
     *
     * @deprecated
     */
    function adodb_error($dbtype, $function_performed, $error_number, $error_message,
                         $host, $database, &$connection_obj)
    {
        // does nothing.... remove me later.
    }
}
