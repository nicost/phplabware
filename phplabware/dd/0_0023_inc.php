<?php

// 0_0023_inc.php - defines pdf reprint related tables
// 0_0023_inc.php - author: Nico Stuurman <nicost@sourceforge.net>

  /***************************************************************************
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  * Creates tables pdfs,pd_type1, and inserts initial values                 *
  *                                                                          *
  *                                                                          *
  *                                                                          *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/ 

$query="CREATE TABLE pdfs (
	id int PRIMARY KEY,
	access char(9),
	ownerid int,
        magic int,
        pmid int,
	title text,
        author text,
	type1 int,
        volume int,
        fpage int,
        lpage int,
        year int,
        abstract text,
	notes text,
	lastmodby int,
        lastmoddate int,
	date int)";
if (!$db->Execute($query)) $test=false;
$db->Execute("CREATE INDEX pdfs_id_index ON pdfs (id)");
$db->Execute("CREATE INDEX pdfs_ownerid_index ON pdfs (ownerid)");
$db->Execute("CREATE INDEX pdfs_date_index ON pdfs (date)");
$db->Execute("CREATE INDEX pdfs_type1_index ON pdfs (type1)");
$db->Execute("CREATE INDEX pdfs_magic_index ON pdfs (magic)");
$db->Execute("CREATE INDEX pdfs_title_index ON pdfs (title)");
$db->Execute("CREATE INDEX pdfs_title_index ON pdfs (title(10))");
// holds journal names
$query="CREATE TABLE pd_type1 
	(id int PRIMARY KEY,
	 sortkey int,
	 type text,
	 typeshort text)";
if (!$db->Execute($query)) $test=false;
$db->Execute("CREATE INDEX pd_type1_id_index ON pd_type1 (id)");
$db->Execute("CREATE INDEX pd_type1_sortkey_index ON pd_type1 (sortkey)");
$query="CREATE TABLE pd_type2 
	(id int PRIMARY KEY,
         sortkey int, 
 	 type text,
	 typeshort text)";
if (!$db->Execute($query)) $test=false;
$db->Execute("CREATE INDEX pd_type2_id_index ON pd_type2 (id)");
$db->Execute("CREATE INDEX pd_type2_sortkey_index ON pd_type2 (sortkey)");

$tablesid=$db->GenID("tableoftables_id_seq");
$query="INSERT INTO tableoftables VALUES ($tablesid,300,'pdfs','pd')";
if (!$db->Execute($query)) $test=false;
?>
