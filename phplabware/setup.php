<?php

// setup.php - Initiates and updates database, manages system wide prefs
// setup.php - author: Nico Stuurman <nicost@sourceforge.net>

  /***************************************************************************
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/                                                                             

$version_code=0.001;
$localdir=exec("pwd");
include ('includes/functions_inc.php');
if (!file_exists("includes/config_inc.php")) {
   printheader("Not ready yet");
   echo "<h3 align='center'>Please edit the file <i>$localdir/includes/config_inc.exp</i> and save it as <i>$localdir/includes/config_inc.php</i>.  Then come back to this page.</h3>";
   printfooter();
   exit();
}
include ('includes/config_inc.php');
include ("includes/defines_inc.php");
include ('adodb/adodb.inc.php');

$post_vars="action,pwd,secure_server_new";
globalize_vars($post_vars, $HTTP_POST_VARS);

// only allow connections from localhost

// we want associative arrays from the database
//$ADODB_FETCH_MODE=ADODB_FETCH_ASSOC;

// test whether the database exists
$db=NewADOConnection($db_type);
if (!@$db->Connect($db_host, $db_user, $db_pwd, $db_name)) {
   echo "<h3>Connection to database <i>$db_name</i> on host <i>$db_host</i>";
   echo " failed.<br>  Please make sure that the variables in file ";
   echo "phplabware/includes/config_inc.php are correct, and your database ";
   echo "server is functioning.</h3>";
   exit ();
}

// if table settings does not exist, we'll need to create the initial tables
$version=get_cell($db, "settings", "version", "id", 1);


if (! ($version || $pwd) ) {
   // This must be the first time, ask for a sysadmin password
   printheader("Ready to install the database");
   echo "<form enctype='multipart/form-data' method='post' ";
   echo "action='$PHP_SELF'>\n";
   echo "<h3>After submitting the following form the phplabware database will ";
   echo "be created and you will be asked to login.<br>";
   echo "Login as <i>sysadmin</i> using the password you enter here.</h3>\n";
   echo "<table border=0>\n";
   echo "<tr>\n";
   echo "<td><h3>Please provide a password for <i>sysadmin</i>:</td>\n";
   echo "<td><input type='text' name='pwd'></td>\n";
   echo "</tr>\n";
   echo "<tr>\n";
   echo "<td colspan=2 align='center'><input type='submit' name='submit' ";
   echo "value='submit'></td>\n";
   echo "</tr>\n";
   echo "</table>\n";
   printfooter();
}

if (!$version && $pwd) {
   // we connected to an empty database and have the password
   // now create the initial tables
   //$db->debug = true;
   //$db->BeginTrans();
   $test=true;
   $result=$db->Execute("CREATE TABLE settings 
	(id int PRIMARY KEY, 
	version  float(8), 
	settings text, 
	created datetime)");
   if (!$result) $test=false;
   $result=$db->Execute("INSERT INTO settings VALUES (1,$version_code,".$db->DBDate(time()).")");
   if (!$result) $test=false;
   $result=$db->Execute("CREATE TABLE users 
	(id int PRIMARY KEY, 
	firstname text, 
	lastname text, 
	login text, 
	groupid text, 
	pwd text, 
	email text, 
	permissions int, 
	settings text,
	indir text,
	outdir text)");
   if (!$result) $test=false;
   $result=$db->Execute("CREATE TABLE groups 
	(id int PRIMARY KEY, 
	name text, 
	description text)");
   if (!$result) $test=false;
   $result=$db->Execute("CREATE TABLE usersxgroups
	(usersid int,
	groupsid int)");
   if (!$result) $test=false;
   // insert sysadmin and admin group
   $pass= md5($pwd);
   $id = $db->GenID("users_id_seq");
   $idg=$db->GenID("groups_id_seq");
   if (!($id && $idg)) $test=false;
   $result=$db->Execute("INSERT INTO groups VALUES
	($idg, 'admins', 'Only for real important people')");
   if (!$result) $test=false;
   $result=$db->Execute("INSERT INTO users VALUES 
	($id, '','','sysadmin', $idg, '$pass','', 127, '', '', '')");
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
	($id, '','','guest', $idg, '$pass','', 3, '','','')");
   if (!$result) $test=false;
   $idg=$db->GenID("groups_id_seq");
   $result=$db->Execute("INSERT INTO groups VALUES
	($idg, 'users', 'That is us')");
   if (!$result) $test=false;
   if (!$test) {
      echo "<h3 align='center'>Problems creating database tables!</h3>\n";
      //$db->RollBackTrans();
   }
   else {
      //echo "<h3 align='center'>Succesfully created database tables!</h3>\n";
      //$db->CommitTrans();
      $version=$version_code;
   }
} 

// $version is known, so we have a working database and must now authenticate
if ($version) {
   include ("includes/auth_inc.php");
   allowonly($SUPER, $USER["permissions"]);
   printheader("Settings");
   navbar($USER["permissions"]);
   $settings=unserialize(get_cell($db, "settings", "settings", "id", 1));
   // display form with current settings
   if ($action) {
      if ($secure_server_new=="Yes")
         $settings["secure_server"]=true;
      else
         $settings["secure_server"]=false;
      $settings_ser=serialize($settings);
      $query="UPDATE settings SET settings='$settings_ser' WHERE id=1";
      $result=$db->Execute($query);
      if ($result)
         echo "<h3 align='center'>Succefully updated the database settings.</h3>\n";
      else
         echo "<h3 align='center'>Failed to update settings!</h3>\n";
   }
   echo "<form enctype='multipart/form-data' method='post' ";
   echo "name='globals-form' action='$PHP_SELF'>\n";
   echo "<table border=1 width='100%'>\n";
   echo "<tr><th>Description</th><th>Setting</th></tr>\n";
   echo "<tr><td colspan='2' align='center'><i>Login Options</i></th></tr>\n";
   echo "<tr><td>Is PhpLabWare accessible through a secure server? ";
   echo "If so, passwords will be encrypted while in transit.</td>\n";
   echo "<td>";
   if ($settings["secure_server"])
      echo "Yes <input type='radio' name='secure_server_new' checked value='Yes'>
            &nbsp&nbsp No<input type='radio' name='secure_server_new' value='No'>
            \n";
   else 
      echo "Yes <input type='radio' name='secure_server_new' value='Yes'>
            &nbsp&nbsp No<input type='radio' name='secure_server_new' checked 
            value='No'>\n";
   echo "</td></tr>\n";

   echo "<tr><td colspan=2 align='center'><input align=center type=submit 
         name=action value=submit></td></tr>\n";  

   echo "</table>\n</form>\n";
   printfooter();
}

?>
