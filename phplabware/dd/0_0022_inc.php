<?php

// 0_0022_inc.php - defines antibody related tables
// 0_0022_inc.php - author: Nico Stuurman <nicost@sourceforge.net>

  /***************************************************************************
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  * Creates tables protocols,pr_type1, and inserts initial values            *
  *                                                                          *
  *                                                                          *
  *                                                                          *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/ 
  
  
$query="CREATE TABLE protocols (
	id int PRIMARY KEY,
	access char(9),
	ownerid int,
        magic int,
	name text,
	type1 int,
	type2 int,
	notes text,
	lastmodby int,
        lastmoddate int,
	date int)";
if (!$db->Execute($query)) $test=false;
$db->Execute("CREATE INDEX protocols_id_index ON protocols (id)");
$db->Execute("CREATE INDEX protocols_ownerid_index ON protocols (ownerid)");
$db->Execute("CREATE INDEX protocols_date_index ON protocols (date)");
$db->Execute("CREATE INDEX protocols_magic_index ON protocolss (magic)");
$db->Execute("CREATE INDEX protocols_name_index ON protocols (name)");
$db->Execute("CREATE INDEX protocols_name_index ON protocols (name(10))");
$query="CREATE TABLE pr_type1 
	(id int PRIMARY KEY,
	 sortkey int,
	 type text,
	 typeshort text)";
if (!$db->Execute($query)) $test=false;
$db->Execute("CREATE INDEX pr_type1_id_index ON pr_type1 (id)");
$db->Execute("CREATE INDEX pr_type1_sortkey_index ON pr_type1 (sortkey)");
$query="CREATE TABLE pr_type2 
	(id int PRIMARY KEY,
         sortkey int, 
 	 type text,
	 typeshort text)";
if (!$db->Execute($query)) $test=false;
$db->Execute("CREATE INDEX pr_type2_id_index ON pr_type2 (id)");
$db->Execute("CREATE INDEX pr_type2_sortkey_index ON pr_type2 (sortkey)");

$tablesid=$db->GenID("tableoftables_id_seq");
$query="INSERT INTO tableoftables VALUES ($tablesid,200,'protocols','pr')";
if (!$db->Execute($query)) $test=false;

$query="ALTER TABLE files ADD COLUMN type int";
if (!$db->Execute($query)) $test=false;

$query="CREATE TABLE filetypes
        (id int PRIMARY KEY,
	 sortkey int,
	 type text,
	 typeshort text)";
if (!$db->Execute($query)) $test=false;
$db->Execute("CREATE INDEX filetypes_id_index ON filetypes (id)");
$db->Execute("CREATE INDEX filetypes_sortkey_index ON filetypes (sortkey)");

$fid=$db->GenID("filetypes_id_seq");
$query="INSERT INTO filetypes VALUES ($fid,100,'HTML','html')";
if (!$db->Execute($query)) $test=false;
$fid=$db->GenID("filetypes_id_seq");
$query="INSERT INTO filetypes VALUES ($fid,200,'MSWord','doc')";
if (!$db->Execute($query)) $test=false;

?>
