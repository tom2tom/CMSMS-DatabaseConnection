## CMSMS2 database connection mechanism
This is a fork of the cluster of classes used, in [CMS Made Simple](http://cmsmadesimple.org) version 2.2.2, for interacting with the underlying MySQL database for the website.
Those sources are (reportedly) largely compatible with ADOdb-lite with pear, extended and transaction plugins. ADOdb-lite was derived from ADOdb around 2004.

The changes here are:
* uses 'native' functionality in Mysqlnd where possible and helpful
* execute() always returns an object, which may be an empty-result class, in which case with errno and errmsg properties if relevant
* parameterized queries use the MySQLi prepare/[bind/]execute process instead of emulation (which is scarcely more secure than un-parameterized execution). This means
  * such prepared queries may of course be re-executed, with another bind if relevant
  * it's __only for [DML queries](https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-data-manipulation.html)__. Other types must be (or be manually migrated to) non-parameterized (e.g. SHOW TABLES LIKE ? or LIMIT ?).
  * string-field values are length-constrained (MySQL's max_allowed_packet setting applies). A workaround could be coded if this limit becomes a problem in practice.
  * string-field content doesn't need to be escaped
* for a parameterized query with a single parameter, the latter may be provided as a scalar instead of array (this is not ADOdb-compatible)
* text and blob fields can be sized e.g. X(1024), B(128), instead of defaulting to the respective 'LONG' form
* various small optimisations & several fixes
* no need for external code to process PHP exceptions
* several ADOdb methods reinstated
  * addQ
  * getMedian
  * resultset::currentRow
* several redundant/deprecated/unusable/unwanted methods omitted e.g. bulk binding is gone (per ADOdb 5.11+)
* method names conformed to [current ADOdb practice](http://adodb.org/dokuwiki/doku.php?id=v5:reference:reference_index) i.e. various case-changes 
* code reformatted per PSR-2
### Installation
Together, the various _.php_ files here are intended to constitute a drop-in replacement for all files in the folder [[CMSMS root directory]]/lib/classes/Database and below.

Patch which should be applied _MustPatch.diff_
Patch which should be applied if the Gallery module is used _Gallery.diff_
Optionally, you can apply the patch _CMSApp-2.2.2.diff_ to clean up the interface.
### Deprecations
Method _NewDatadictionary($db)_ is merely an alias for _$db->NewDatadictionary()_
