<?php

// 0_0024_inc.php - defines pdb reprint related tables
// 0_0024_inc.php - author: Nico Stuurman <nicost@sourceforge.net>

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

$query="CREATE TABLE pdbs (
	id int PRIMARY KEY,
	access char(9),
	ownerid int,
        magic int,
        pdbid char(4),
	title text,
        author text,
	notes text,
	lastmodby int,
        lastmoddate int,
	date int)";
if (!$db->Execute($query)) $test=false;
$db->Execute("CREATE INDEX pdbs_id_index ON pdbs (id)");
$db->Execute("CREATE INDEX pdbs_ownerid_index ON pdbs (ownerid)");
$db->Execute("CREATE INDEX pdbs_date_index ON pdbs (date)");
$db->Execute("CREATE INDEX pdbs_magic_index ON pdbs (magic)");
$db->Execute("CREATE INDEX pdbs_title_index ON pdbs (title)");
$db->Execute("CREATE INDEX pdbs_title_index ON pdbs (title(10))");

$tablesid=$db->GenID("tableoftables_id_seq");
$query="INSERT INTO tableoftables VALUES ($tablesid,400,'pdbs','pb')";
if (!$db->Execute($query)) $test=false;
?>
