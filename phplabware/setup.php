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

$post_vars="action,authmethod,checkpwd,dateformat,pwd,secure_server_new,submit,";
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
   printheader("PhpLabware: Database connection failed");
   echo "<h3>Connection to database <i>$db_name</i> on host <i>$db_host</i>";
   echo " failed.<br>  Please make sure that the variables in file ";
   echo "<i>phplabware/includes/config_inc.php</i> are correct, your database ";
   echo "server is functioning, and you created a database named <i>$db_name";
   echo "</i>.</h3>";
   printfooter();
   exit ();
}

// if table settings does not exist, we'll need to create the initial tables
$version=get_cell($db, "settings", "version", "id", 1);


if (! ($version || $pwd) ) {
   // This must be the first time, ask for a sysadmin password
   printheader("Ready to install the database");
?>
<form enctype='multipart/form-data' method='post' action='<?php echo $PHP_SELF?>?<?=SID?>'>
<?php
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
   $query="CREATE TABLE dateformats 
	(id int PRIMARY KEY, 
	dateformat text, 
	sortkey int)";
   if (!$db->Execute($query)) $test=false;
   $query="INSERT INTO dateformats VALUES (1,'m-d-Y',100)";
   if (!$db->Execute($query)) $test=false;
   $query="INSERT INTO dateformats VALUES (2,'M-D-Y',200)";
   if (!$db->Execute($query)) $test=false;
   $query="INSERT INTO dateformats VALUES (3,'d-m-Y',300)";
   if (!$db->Execute($query)) $test=false;
   $query="INSERT INTO dateformats VALUES (4,'D-M-Y',400)";
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
   
   if (!$db->Execute("CREATE INDEX users_id_pkey ON users (id)"))
      $test=false;
   if (!$db->Execute("CREATE INDEX users_login_key ON users (login)"))
      $test=false;
   if (!$db->Execute("CREATE INDEX users_pwd_key ON users (pwd)"))
      $test=false;

$result=$db->Execute("CREATE TABLE groups 
	(id int PRIMARY KEY, 
	name text, 
	description text)");
   if (!$result) $test=false;
   if (!$db->Execute("CREATE INDEX groups_id_pkey ON groups (id)"))
      $test=false;
   if (!$db->Execute("CREATE INDEX groups_name_pkey ON groups (name)"))
      $test=false;

   $result=$db->Execute("CREATE TABLE usersxgroups
	(usersid int,
	groupsid int)");
   if (!$result) $test=false;
   if (!$db->Execute("CREATE INDEX usersxgroups_usersid_pkey ON usersxgroups (usersid)"))
      $test=false;
   if (!$db->Execute("CREATE INDEX usersxgroups_groupsid_pkey ON usersxgroups (groupsid)"))
      $test=false;
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
	    access char(9),
            ownerid int,
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
	    date int)";
	 if (!$db->Execute($query)) $test=false;
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
	     type text,
             typeshort text)";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type2 VALUES (1,'monoclonal','mono')";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type2 VALUES (2,'polyclonal','poly')";
	 if (!$db->Execute($query)) $test=false;
         $query="CREATE TABLE ab_type3 
            (id int PRIMARY KEY,
	     sortkey int,
	     type text,
             typeshort text)";
	 if (!$db->Execute($query)) $test=false;
	 $query="INSERT INTO ab_type3 VALUES (1,10,'?')";
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
      }
      
      $query="UPDATE settings SET version='$version_code' WHERE id=1";
      if (!$db->Execute($query)) $test=false;


      if ($test)
          echo "<h3 align='center'>Succefully updated the database to version $version_code.</h3>\n";
       else 
          echo "<h3 align='center'>Failed to update the database to version $version_code.</h3>\n";
   }

   if ($action) {
      if ($dateformat)
         $settings["dateformat"]=$dateformat;
      if ($filedir) 
         if (is_writable($filedir))
            $settings["filedir"]=$filedir;
	 else
	    echo "<h4 align='center>Directory $filedir is not writeable</h4>";
      if ($secure_server_new=="Yes")
         $settings["secure_server"]=true;
      else
         $settings["secure_server"]=false;
      if ($authmethod)
         $settings["authmethod"]=$authmethod;
      $settings["checkpwd"]=$checkpwd;
      $settings_ser=serialize($settings);
      $query="UPDATE settings SET settings='$settings_ser' WHERE id=1";
      $result=$db->Execute($query);
      if ($result)
         echo "<h3 align='center'>Succesfully updated the database settings.</h3>\n";
      else
         echo "<h3 align='center'>Failed to update settings!</h3>\n";
   }

   // display form with current settings
?>
<form enctype='multipart/form-data' method='post' name='globals-form' action='<?php echo $PHP_SELF ?>?<?=SID?>'>
<?php
   echo "<table border=1 align='center' width='70%'>\n";
   echo "<tr><th>Description</th><th>Setting</th></tr>\n";

   echo "<tr><td colspan='2' align='center'><i>Directories</i></th></tr>\n";
   echo "<tr><td>Location of directory <i>files</i>. The webdaemon should ";
   echo "have read and write priveleges there, but it should not be directly ";
   echo "accessible through the web.  If you changes this value, ";
   echo "the directory will be moved to the new location.</td>";
   if (!$settings["filedir"]) {
      $dir=getenv("SCRIPT_FILENAME");
      $dir=substr($dir,0,strrpos($dir,"/")+1)."files";
      $settings["filedir"]=$dir;
   }
   $filedir=$settings["filedir"];
   echo "<td><input type='text' name='filedir' value='$filedir'></td></tr>\n";

   echo "<tr><td colspan='2' align='center'><i>Localization</i></th></tr>\n";
   echo "<tr><td>Date Format:</td>\n";
   $query="SELECT dateformat,id FROM dateformats ORDER BY sortkey";
   $r=$db->Execute($query);
   echo "\n<td align='center'>";
   echo $r->GetMenu2("dateformat",$settings["dateformat"],false);
   echo "</td></tr>\n";

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
   echo "<tr><td>Authentification method.  For PAM you will need the utility 'testpwd' available <a href='http://sourceforge.net/project/showfiles.php?group_id=17393'>here</a>. </td>";
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
