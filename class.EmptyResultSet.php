<?php

/**
 * A class to describe an empty recordset.
 *
 * @ignore
 */

namespace CMSMS\Database;

/**
 * A class to describe a special (empty) recordset.
 *
 * @ignore
 */
final class EmptyResultSet extends ResultSet
{
    /**
     * @ignore
     */
    public function move($idx)
    {
        return false;
    }
    /**
     * @ignore
     */
    public function moveFirst()
    {
        return false;
    }
    /**
     * @ignore
     */
    public function moveNext()
    {
        return false;
    }
    /**
     * @ignore
     */
    public function getArray()
    {
        return [];
    }
    /**
     * @ignore
     */
    public function getAssoc($force_array = false, $first2cols = false)
    {
        return [];
    }
    /**
     * @ignore
     */
    public function getCol($trim = false)
    {
        return [];
    }
    /**
     * @ignore
     */
    public function EOF()
    {
        return true;
    }
    /**
     * @ignore
     */
    public function close()
    {
    }
    /**
     * @ignore
     */
    public function currentRow()
    {
        return false;
    }
    /**
     * @ignore
     */
    public function recordCount()
    {
        return 0;
    }
    /**
     * @ignore
     */
    public function fields($field = null)
    {
        return null;
    }
    /**
     * @ignore
     */
    protected function fetch_row()
    {
    }
}
