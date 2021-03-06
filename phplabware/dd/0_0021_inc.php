<?php

// 0_0021_inc.php - defines antibody related tables
// 0_0021_inc.php - author: Nico Stuurman <nicost@sourceforge.net>

  /***************************************************************************
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  * Creates tables antibodies,ab_type1-5, and inserts initial values         *
  *                                                                          *
  *                                                                          *
  *                                                                          *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/ 
  
$query="CREATE TABLE antibodies (
	id int PRIMARY KEY,
	access char(9),
	ownerid int,
        magic int,
	name text,
	type1 int,
	type2 int,
	type3 int,
	type4 int,
	type5 int,
	antigen text,
	epitope text,
	concentration float,
	buffer text,
	notes text,
	location text,
	source text,
	date int)";
if (!$db->Execute($query)) $test=false;
$db->Execute("CREATE INDEX antibodies_id_index ON antibodies (id)");
$db->Execute("CREATE INDEX antibodies_ownerid_index ON antibodies (ownerid)");
$db->Execute("CREATE INDEX antibodies_date_index ON antibodies (date)");
$db->Execute("CREATE INDEX antibodies_magic_index ON antibodies (magic)");
$db->Execute("CREATE INDEX antibodies_name_index ON antibodies (name)");
$db->Execute("CREATE INDEX antibodies_name_index ON antibodies (name(10))");
$query="CREATE TABLE ab_type1 
	(id int PRIMARY KEY,
	 sortkey int,
	 type text,
	 typeshort text)";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type1 VALUES (1,10,'Primary','1')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type1 VALUES (2,20,'Secondary','2')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type1 VALUES (3,30,'other','-')";
if (!$db->Execute($query)) $test=false;
$query="CREATE TABLE ab_type2 
	(id int PRIMARY KEY,
         sortkey int, 
 	 type text,
	 typeshort text)";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type2 VALUES (1,10,'monoclonal','mono')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type2 VALUES (2,20,'polyclonal','poly')";
if (!$db->Execute($query)) $test=false;
$query="CREATE TABLE ab_type3 
	(id int PRIMARY KEY,
 	 sortkey int,
 	 type text,
	 typeshort text)";
if (!$db->Execute($query)) $test=false;
$db->Execute("CREATE INDEX ab_type3_id_index ON ab_type3 (id)");
$db->Execute("CREATE INDEX ab_type3_sortkey_index ON ab_type3 (sortkey)");
$query="INSERT INTO ab_type3 VALUES (1,1010,'unknown','?')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type3 VALUES (2,50,'human','human')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type3 VALUES (3,20,'mouse','mouse')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type3 VALUES (4,10,'rabbit','rabbit')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type3 VALUES (5,30,'rat','rat')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type3 VALUES (6,40,'goat','goat')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type3 VALUES (7,1000,'other','other')";
if (!$db->Execute($query)) $test=false;
 $query="CREATE TABLE ab_type4 
	(id int PRIMARY KEY,
 	 sortkey int,
 	 type text,
	 typeshort text)";
if (!$db->Execute($query)) $test=false;
$db->Execute("CREATE INDEX ab_type4_id_index ON ab_type4 (id)");
$db->Execute("CREATE INDEX ab_type4_sortkey_index ON ab_type4 (sortkey)");
$query="INSERT INTO ab_type4 VALUES (1,100,'IgG','IgG')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type4 VALUES (2,200,'IgM','IgM')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type4 VALUES (3,300,'IgG1','IgG1')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type4 VALUES (4,400,'IgG2a','IgG2a')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type4 VALUES (5,500,'IgG2b','IgG2b')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type4 VALUES (6,600,'IgE','IgE')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type4 VALUES (7,20,'mix','mix')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type4 VALUES (8,10,'unknown','?')";
if (!$db->Execute($query)) $test=false;
 $query="CREATE TABLE ab_type5 
	(id int PRIMARY KEY,
 	 sortkey int,
 	 type text,
	 typeshort text)";
if (!$db->Execute($query)) $test=false;
$db->Execute("CREATE INDEX ab_type5_id_index ON ab_type5 (id)");
$db->Execute("CREATE INDEX ab_type5_sortkey_index ON ab_type5 (sortkey)");
$query="INSERT INTO ab_type5 VALUES (1,100,'Alkaline Phosph.','AP')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type5 VALUES (2,200,'Horseradish Perox.','HP')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type5 VALUES (3,300,'FITC','FITC')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type5 VALUES (4,400,'Rhodamine','Rhod.')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type5 VALUES (5,500,'Cy3','Cy3')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type5 VALUES (6,600,'Cy5','Cy5')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type5 VALUES (7,700,'Alexa-488','Alex488')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type5 VALUES (8,2000,'Beads','Beads')";
if (!$db->Execute($query)) $test=false;
$query="INSERT INTO ab_type5 VALUES (9,0,'None','None')";
if (!$db->Execute($query)) $test=false;

$tablesid=$db->GenID("tableoftables_id_seq");
$query="INSERT INTO tableoftables VALUES ($tablesid,100,'antibodies','ab')";
if (!$db->Execute($query)) $test=false;


?>
