## CMSMS2 database connection mechanism
This is a fork of the cluster of classes used, in [CMS Made Simple](http://cmsmadesimple.org) version 2.2.2, for interacting with the underlying MySQL database for the website.
Those sources are (reportedly) largely compatible with ADOdb-lite with pear, extended and transaction plugins. ADOdb-lite was derived from ADOdb around 2004.
The changes here are:
* uses 'native' functionality in Mysqlnd where possible and helpful
* parameterized queries use the MySQLi prepare/[bind/]execute process instead of emulation (which is scarcely more secure than un-parameterized execution). This means
    * such prepared queries may of course be re-executed, with another bind if relevant
* various small optimisations & several fixes
* no need for external code to process PHP exceptions
* method names conformed to [current ADOdb practice](http://adodb.org/dokuwiki/doku.php?id=v5:reference:reference_index) i.e. various case-changes 
* code reformatted per PSR-2
### Incompatibilities
* parameterized commands
    * string-field values are length-constrained (MySQL's max_allowed_packet setting applies). A workaround could be coded if this limit becomes a problem in practice.
    * string-field content doesn't need to be escaped, of course
    * if only one parameter, that may be provided as a scalar instead of array
    * some must be modified - see below re deprecation
* text and blob fields can be sized e.g. X(1024), B(128), instead of defaulting to the respective 'LONG' form
* getArray(), getCol(), getAssoc() methods support optional extra arguments $nrows, $offset c.f. selectLimit()
* getRow(), getOne() methods support optional extra argument $offset
* reinstated ADOdb addQ() method
* several redundant/unusable methods omitted
### Deprecations
* method _NewDatadictionary($db)_ is merely an alias for _$db->NewDatadictionary()_
* some parameterized commands
    * [MySQL](https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-prepared-statements.html#idm139681852857552) and [MariaDB](https://mariadb.com/kb/en/library/prepare-statement) document their respective SQL commands which can be parameterized. The two are not entirely consistent. There's no reasonably possible way to get an 'inverse' of those lists, even if we did accept the overhead of checking each command. [DML queries](https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-data-manipulation.html) are ok.
    * For now at least, parameterisation is emulated if the database reports appropriately about an un-supported command.
    * Not possible if any parameter represents a string e.g. SHOW TABLES LIKE ?. In such cases, the command __must be manually converted__ to remove the offending parameter(s).
* bulk binding (per ADOdb 5.11+)
```php
$stmt = $db->prepare('SOME SQL');
```
OLD
```php 
$stmt->bind($array_of_params_for_SQL);
while (!$stmt->EOF()) {
  $stmt->execute();
  $stmt->movenext();
}
```
  NEW
```php
foreach ($array_of_params_for_SQL as $row) {
  $stmt->execute($row);
}
```
### Installation
Together, the various _.php_ files here are intended to constitute a drop-in replacement for all files in the folder [[CMSMS root directory]]/lib/classes/Database and below.
Patch which should be applied _MustPatch.diff_
Patch which should be applied if the Gallery module is used _Gallery.diff_
Optionally, you can apply the patch _CMSApp-2.2.2.diff_ to clean up the interface.
