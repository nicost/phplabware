<?php

// 0_001_inc.php - initial database definition
// 0_001_inc.php - author: Nico Stuurman <nicost@sourceforge.net>

  /***************************************************************************
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  * Creates tables settings,user,groups,userxgroups and inserts initial      *
  * values.                                                                  *
  *                                                                          *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/ 
  
  
   $test=true;
   $result=$db->Execute("CREATE TABLE settings 
	(id int PRIMARY KEY, 
	version  float(8), 
	settings text, 
	created datetime)");
   if (!$result) $test=false;
   $result=$db->Execute("INSERT INTO settings VALUES (1,0.001,'',".$db->DBDate(time()).")");
   if (!$result) $test=false;
   $query="CREATE TABLE authmethods 
      (id int PRIMARY KEY, 
       sortkey int,
       method text)";
   if (!$db->Execute($query)) $test=false;
   $query="INSERT INTO authmethods VALUES (1,10,'SQL')";
   if (!$db->Execute($query)) $test=false;
   $query="INSERT INTO authmethods VALUES (2,40,'PAM')";
   if (!$db->Execute($query)) $test=false;
   $query="CREATE TABLE dateformats 
	(id int PRIMARY KEY, 
	 sortkey int,
         dateformat text)"; 
   if (!$db->Execute($query)) $test=false;
   $query="INSERT INTO dateformats VALUES (1,100,'m-d-Y')";
   if (!$db->Execute($query)) $test=false;
   $query="INSERT INTO dateformats VALUES (2,200,'M-D-Y')";
   if (!$db->Execute($query)) $test=false;
   $query="INSERT INTO dateformats VALUES (3,300,'d-m-Y')";
   if (!$db->Execute($query)) $test=false;
   $query="INSERT INTO dateformats VALUES (4,400,'D-M-Y')";
   if (!$db->Execute($query)) $test=false;
   $result=$db->Execute("CREATE TABLE users 
	(id int PRIMARY KEY, 
	firstname text, 
	lastname text, 
	login text, 
	groupid int, 
	pwd text, 
	email text, 
	permissions int, 
	settings text,
	indir text,
	outdir text)");
   if (!$result) $test=false;
   $db->Execute("CREATE INDEX users_id_index ON users (id)"); 
   $db->Execute("CREATE INDEX users_groupid_index ON users (groupid)"); 
   $db->Execute("CREATE INDEX users_login_index ON users (login)"); 
   $db->Execute("CREATE INDEX users_login_index ON users (login(10))"); 
   $db->Execute("CREATE INDEX users_pwd_index ON users (pwd)"); 
   $db->Execute("CREATE INDEX users_pwd_index ON users (pwd(10))"); 
   $result=$db->Execute("CREATE TABLE groups 
	(id int PRIMARY KEY, 
	name text, 
	description text)");
   if (!$result) $test=false;
   $db->Execute("CREATE INDEX groups_id_index ON groups (id)"); 

   $result=$db->Execute("CREATE TABLE usersxgroups
	(usersid int,
	groupsid int)");
   if (!$result) $test=false;

   $result=$db->Execute("CREATE TABLE files
	(id int PRIMARY KEY,
	 filename text,
         tablesfk int,
         ftableid int,
	 mime text,
	 notes text,
         size numeric(16),
	 title text)");
   if (!$result) $test=false;
   $db->Execute("CREATE INDEX files_id_index ON files (id)"); 

   $result=$db->Execute("CREATE TABLE tables
         (id int PRIMARY KEY,
          sortkey int,
          tablename text,
          shortname text)");
   if (!$result) $test=false;
   $db->Execute("CREATE INDEX tables_id_index ON tables (id)"); 
   $db->Execute("CREATE INDEX tables_tablename_index ON tables (tablename)"); 
   $db->Execute("CREATE INDEX tables_tablename_index ON tables (tablename(10))"); 

   // insert sysadmin and admin group
   $pass= md5($pwd);
   $id=$db->GenID("users_id_seq");
   $idg=$db->GenID("groups_id_seq");
   if (!($id && $idg)) $test=false;
   $result=$db->Execute("INSERT INTO groups VALUES
	($idg, 'admins', 'Only for real important people')");
   if (!$result) $test=false;
   $result=$db->Execute("INSERT INTO users VALUES 
	($id, '','sysadmin','sysadmin', $idg, '$pass','', 127, '', '', '')");
   if (!$result) $test=false;
   // insert guest and guest group
   $pass= md5("guest");
   $id = $db->GenID("users_id_seq");
   $idg=$db->GenID("groups_id_seq");
   if (!($id && $idg)) $test=false;
   $result=$db->Execute("INSERT INTO groups VALUES
	($idg, 'guests', 'Only for our guests')");
   if (!$result) $test=false;
   $result=$db->Execute("INSERT INTO users VALUES 
	($id, '','guest','guest', $idg, '$pass','', 3, '','','')");
   if (!$result) $test=false;
   $idg=$db->GenID("groups_id_seq");
   $result=$db->Execute("INSERT INTO groups VALUES
	($idg, 'users', 'That is us')");
   if (!$result) $test=false;
   if (!$test) {
      echo "<h3 align='center'>Problems creating database tables!\n";
      echo "Some function might not work.</h3>\n";
   }
   else {
      $version=0.001;
   }
   
?>
