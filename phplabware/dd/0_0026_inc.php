<?php

// 0_0026_inc.php - redraws the tableoftables to new display format
// 0_0026_inc.php - author: Ethan Garner

  /***************************************************************************
  * Copyright (c) 2001 by Ethan Garner                                       *
  * ------------------------------------------------------------------------ *
  * Creates redraws the tableoftables to new display format                  *
  *                                                                          *
  *                                                                          *
  *                                                                          *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/ 


// Change table of tables to be the new display format
$db->Execute("ALTER TABLE tableoftables ADD display CHAR(1)");
$db->Execute("ALTER TABLE tableoftables ADD permission text");
$db->Execute("ALTER TABLE tableoftables ADD custom text");
$db->Execute("UPDATE tableoftables SET Custom='antibodies.php'  where tablename = 'antibodies'");
$db->Execute("UPDATE tableoftables SET Custom='pdfs.php'  where tablename = 'pdfs'");
$db->Execute("UPDATE tableoftables SET Custom='protocols.php'  where tablename = 'protocols'");
$db->Execute("UPDATE tableoftables SET Custom='pdbs.php'  where tablename = 'pdbs'");
//$db->Execute("ALTER TABLE tableoftables modify id int UNSIGNED NOT NULL AUTO_INCREMENT");

// Import existing tables into the updated navbar display funtion.
$db->Execute("UPDATE tableoftables SET permission='Users'  where sortkey > 1");
$db->Execute("UPDATE tableoftables SET display='Y'  where sortkey > 1");

// Create the linkbar table and associated entries
$db->Execute("Create table linkbar (
	id int PRIMARY KEY, 
	label text, 
	linkurl text, 
	sortkey int,
	display varchar(1),
	target varchar(1))");
// Avoid clashes between user generated and 'system' tables
// ids up to 9999 are reserved for system tables
$id=$db->GenID("tableoftables_gen_id_seq",10000);
$db->Execute("INSERT INTO tableoftables (id,sortkey,tablename,shortname,Display,Permission,Custom) VALUES($id,'0','linkbar','li','0','System','System')");
?>
