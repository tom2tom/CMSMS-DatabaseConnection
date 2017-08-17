# CMSMS database connection mechanism
This is a fork of the cluster of classes used, in [CMS Made Simple](http://cmsmadesimple.org) version 2.2.2, for interacting with the underlying MySQL database for the website.
Those sources are (reportedly) largely compatible with ADOdb-lite with pear, extended amd transaction plugins. ADOdb-lite was derived from ADOdb around 2004.

The changes here are:
* various small optimisations
* uses 'native' functionality in Mysqlnd where possible and helpful
* execute() always returns an object, which may be an empty-result class
* parameterized queries use the MySQLi prepare/[bind/]execute process instead of emulation (which was scarcely more secure than un-parameterized execution). This means
  * such queries may be re-used after further bind/execute
  * works only for DML and some DQL queries, others must be, or be migtated to, non-parameterized form (e.g. SHOW TABLES LIKE ? which was found just once)
  * string-field values are length-constrained (MySQL's max_allowed_packet setting applies)
* for a parameterized query with a single parameter, the latter may be provided as a scalar instead of array (this is not ADOdb-compatible) 
* several ADOdb methods reinstated
  * addQ
  * getMedian
  * resultset::currentRow
* several redundant/deprecated/unusable methods omitted
* method names conformed to current ado_db practice i.e. various case-changes 
* code reformatted per PSR-2
## Installation
Together, the files here constitute a drop-in replacement for all files in the folder [[CMSMS root directory]]/lib/classes/Database

Optionally, you can apply the patch CMSApp-2.2.2.diff to clean up the interface.
