## CMSMS database connection mechanism
This is a fork of the cluster of classes used, in [CMS Made Simple](http://cmsmadesimple.org) version 2.2.2, for interacting with the underlying MySQL database for the website.
Those sources are (reportedly) largely compatible with ADOdb-lite with pear, extended and transaction plugins. ADOdb-lite was derived from ADOdb around 2004.

The changes here are:
* uses 'native' functionality in Mysqlnd where possible and helpful
* execute() always returns an object, which may be an empty-result class, in which case with errno and errmsg properties if relevant
* parameterized queries use the MySQLi prepare/[bind/]execute process instead of emulation (which is scarcely more secure than un-parameterized execution). This means
  * such queries may be re-used after further bind/execute
  * it's __only for [DML queries](https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-data-manipulation.html)__. Other types must be (or be manually migrated to) non-parameterized (e.g. SHOW TABLES LIKE ? which was found just once).
  * string-field values are length-constrained (MySQL's max_allowed_packet setting applies). A workaround could be coded if this limit becomes a problem in practice.
* for a parameterized query with a single parameter, the latter may be provided as a scalar instead of array (this is not ADOdb-compatible)
* text and blob fields can be sized e.g. X(1024), B(128)
* various small optimisations & several fixes
* several ADOdb methods reinstated
  * addQ
  * getMedian
  * resultset::currentRow
* several redundant/deprecated/unusable methods omitted
* method names conformed to [current ADOdb practice](http://adodb.org/dokuwiki/doku.php?id=v5:reference:reference_index) i.e. various case-changes 
* code reformatted per PSR-2
### Installation
Together, the various _.php_ files here constitute a drop-in replacement for all files in the folder [[CMSMS root directory]]/lib/classes/Database and below.

Optionally, you can apply the patch _CMSApp-2.2.2.diff_ to clean up the interface.
### Deprecations
Method _NewDatadictionary($db)_ is merely an alias for _$db->NewDatadictionary()_
