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

$version_code=0.0021;
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

$post_vars="action,authmethod,checkpwd,pwd,secure_server_new,submit,";
globalize_vars($post_vars, $HTTP_POST_VARS);

if ($set_local) {
   // only allow connections from localhost
   $host=getenv("HTTP_HOST");
   if (! ($host=="localhost" ||$host=="127.0.0.1") ) {
      printheader("Phplabware setup.  Localhost only");
      echo "<table align='center' border=0><caption><h3>This script can only be reached from the localhost.</h3></caption></table>\n";
      printfooter();
      exit();
   }
}

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
   // $db->debug = true;
   // $db->BeginTrans();
   $test=true;
   //$db->debug=true;
/*
CREATE SEQUENCE settings_id_seq
CREATE TABLE settings
   id INT4 DEAFAULT nextval('settings_id_seq');
*/
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
      method text)";
   if (!$db->Execute($query)) $test=false;
   $query="INSERT INTO authmethods VALUES (1,'SQL')";
   if (!$db->Execute($query)) $test=false;
   $query="INSERT INTO authmethods VALUES (2,'PAM')";
   if (!$db->Execute($query)) $test=false;
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
      echo "<h3 align='center'>Problems creating database tables!</h3>\n";
      // $db->RollBackTrans();
   }
   else {
      // echo "<h3 align='center'>Succesfully created database tables!</h3>\n";
      // $db->CommitTrans();
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

   // insert database updates here
   if ($version<$version_code) {
      $test=true;
      if ($version<0.0021) {
         $query="CREATE TABLE antibodies (
	    id int PRIMARY KEY,
	    access int,
	    name text,
	    type1 int,
	    type2 int,
	    type3 int,
	    type4 int,
	    type5 int,
	    species int,
	    antigen text,
	    epitope text,
	    concentration float,
	    buffer text,
	    notes text,
	    location text,
	    source text,
	    date datetime)";
	 if (!$db->Execute($query)) $test=false;
         $query="CREATE TABLE ab_type1 
            (id int PRIMARY KEY, 
	     type text)";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type1 VALUES (1,'Primary')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type1 VALUES (2,'Secundary')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type1 VALUES (3,'other')";
	 if (!$db->Execute($query)) $test=false;
         $query="CREATE TABLE ab_type2 
            (id int PRIMARY KEY, 
	     type text)";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type2 VALUES (1,'monoclonal')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type2 VALUES (2,'polyclonal')";
	 if (!$db->Execute($query)) $test=false;
         $query="CREATE TABLE ab_type3 
            (id int PRIMARY KEY,
	     sortkey int,
	     type text)";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type3 VALUES (1,10,'human')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type3 VALUES (2,20,'mouse')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type3 VALUES (3,30,'rabbit')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type3 VALUES (4,40,'rat')";
	 if (!$db->Execute($query)) $test=false;
         $query="CREATE TABLE ab_type4 
            (id int PRIMARY KEY,
	     sortkey int,
	     type text)";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type4 VALUES (1,100,'IgG')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type4 VALUES (2,200,'IgM')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type4 VALUES (3,300,'IgG1')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type4 VALUES (4,400,'IgG2a')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type4 VALUES (5,500,'IgG2b')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type4 VALUES (6,600,'IgE')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type4 VALUES (7,20,'mix')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type4 VALUES (8,10,'unknown')";
	 if (!$db->Execute($query)) $test=false;
         $query="CREATE TABLE ab_type5 
            (id int PRIMARY KEY,
	     sortkey int,
	     type text)";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type5 VALUES (1,100,'Phosphatase (Alk.)')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type5 VALUES (2,200,'Peroxidase (H.)')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type5 VALUES (3,300,'FITC')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type5 VALUES (4,400,'Rhodamine')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type5 VALUES (5,500,'Cy3')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type5 VALUES (6,600,'Cy5')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type5 VALUES (7,700,'Alex-488')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type5 VALUES (8,0,'None')";
	 if (!$db->Execute($query)) $test=false;
      }
      
      $query="UPDATE settings SET version='$version_code' WHERE id=1";
      if (!$db->Execute($query)) $test=false;


      if ($test)
          echo "<h3 align='center'>Succefully updated the database to version $version_code.</h3>\n";
       else 
          echo "<h3 align='center'>Failed to update the database to version $version_code.</h3>\n";
   }

   if ($action) {
      
      if ($secure_server_new=="Yes")
         $settings["secure_server"]=true;
      else
         $settings["secure_server"]=false;
      if ($authmethod)
         $settings["authmethod"]=$authmethod;
      if ($checkpwd)
         $settings["checkpwd"]=$checkpwd;
      $settings_ser=serialize($settings);
      $query="UPDATE settings SET settings='$settings_ser' WHERE id=1";
      $result=$db->Execute($query);
      if ($result)
         echo "<h3 align='center'>Succefully updated the database settings.</h3>\n";
      else
         echo "<h3 align='center'>Failed to update settings!</h3>\n";
   }

   // display form with current settings
   echo "<form enctype='multipart/form-data' method='post' ";
   echo "name='globals-form' action='$PHP_SELF'>\n";
   echo "<table border=1 align='center' width='70%'>\n";
   echo "<tr><th>Description</th><th>Setting</th></tr>\n";
   echo "<tr><td colspan='2' align='center'><i>Login Options</i></th></tr>\n";
   echo "<tr><td>Is PhpLabWare accessible through a secure server? ";
   echo "If so, passwords will be encrypted while in transit.\n";
   echo "Do <b>not</b> enter yes if you don't have a secure server.</td>\n";
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
   echo "<tr><td>Authentification method.  For PAM you will need the utility 'testpwd' available from <a href='http://sourceforge.net/project/showfiles.php?group_id=17393'>in the package check_user</a>. </td>";
   $query="SELECT method,id FROM authmethods";
   $r=$db->Execute($query);
   echo "\n<td align='center'>";
   echo $r->GetMenu2("authmethod",$settings["authmethod"],false);
   echo "</td></tr>\n";
   echo "<tr><td>(When using PAM:) Location of check_pwd. ";
   echo "Please use this only in conjunction with the sudo command</td>\n";
   echo "<td>\n";
   $checkpwd=$settings["checkpwd"];
   echo "<input type='text' name='checkpwd' value='$checkpwd'></td></tr>\n";

   echo "<tr><td colspan=2 align='center'><input align=center type=submit 
         name=action value=submit></td></tr>\n";  

   echo "</table>\n</form>\n";
   printfooter();
}

?>
