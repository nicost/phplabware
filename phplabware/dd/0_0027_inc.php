<?php

// 0_0027_inc.php - Adds 'user-defined' table 'files' 
// 0_0027_inc.php - author: Nico Stuurman

  /***************************************************************************
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  * This code is part of phplabware (http://phplabware.sf.net)               *
  *                                                                          *
  *                                                                          *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/ 


// Avoid clashes between user generated and 'system' tables
// ids up to 9999 are reserved for system tables
$id=$db->GenID("tableoftables"."_gen_id_seq",10000);
$db->Execute("INSERT INTO tableoftables (id,sortkey,tablename,shortname,Display,Permission,Custom) VALUES($id,'0','files','fi','Y','Users','')");
$db->Execute("CREATE TABLE files_$id (
		id int PRIMARY KEY,
		title text,
		access varchar(9),
		ownerid int,
		magic int,
		lastmodby int,
		lastmoddate int,
		date int,
		file int,
		notes text,
		category int)");
$desc="files_$id"."_desc";
$db->Execute("CREATE TABLE $desc(
		id int PRIMARY KEY,
		sortkey int,
		label text, 
		display_table char(1), 
		display_record char(1), 
		required char(1), 
		type text, 
		datatype text, 
		associated_table text, 
		associated_sql text)");  
$fieldstring="id,label,sortkey,display_table,display_record, required, type, datatype, associated_table, associated_sql"; 
$descid=$db->GenId("$desc"."_id");  
$db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'id','100','N','N','N','int(11)','text','','')");
$descid=$db->GenId("$desc"."_id");  
$db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'access','110','N','N','N','varchar(9)','text','','')");
$descid=$db->GenId("$desc"."_id");  
$db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'ownerid','120','N','N','N','int(11)','text','','')");
$descid=$db->GenId("$desc"."_id");  
$db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'magic','130','N','N','N','int(11)','text','','')");
$descid=$db->GenId("$desc"."_id");  
$db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'title','140','Y','Y','Y','text','text','','')");
$descid=$db->GenId("$desc"."_id");  
$db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'lastmoddate','150','N','N','N','int(11)','text','','')");
$descid=$db->GenId("$desc"."_id");  
$db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'lastmodby','160','N','N','N','int(11)','text','','')");
$descid=$db->GenId("$desc"."_id");  
$db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'date','170','N','N','N','int(11)','text','','')");
$descid=$db->GenId("$desc"."_id");  
$db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'file','180','Y','Y','N','int','file','','')");
$descid=$db->GenId("$desc"."_id");  
$db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'notes','200','Y','Y','N','text','textlong','','')");
$descid=$db->GenId("$desc"."_id");  
$ass_tablename="files_$id"."ass_1";
$db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'category','190','Y','Y','N','id','pulldown','$ass_tablename','category FROM $ass_tablename WHERE ')");
$db->Execute("CREATE TABLE $ass_tablename (
		id int PRIMARY KEY,
		sortkey int,
		type text,
		typeshort text)");

?>
