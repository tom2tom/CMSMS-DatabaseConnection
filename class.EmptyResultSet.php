<?php
/*
-------------------------------------------------------------------------
Module: \CMSMS\Database\mysqli\EmptyResultSet (C) 2017 Robert Campbell
         <calguy1000@cmsmadesimple.org>
A class to represent an empty recordset
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

namespace CMSMS\Database;

/**
 * A class to represent a special (empty) recordset (maybe after an error).
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
    public function getOne()
    {
        return null;
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
