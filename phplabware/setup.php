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
   include ("dd/0_001_inc.php");
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
         include ("dd/0_0021_inc.php");
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
	    echo "<h4 align='center'>Directory $filedir is not writeable</h4>";
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
