<?php


$version_code=0.001;

include ('includes/functions_inc.php');
include ('includes/config_inc.php');
include ('adodb/adodb.inc.php');

$post_vars="pwd,";
globalize_vars($post_vars, $HTTP_POST_VARS);
$PHP_SELF = $HTTP_SERVER_VARS["PHP_SELF"];

// only allow connections from localhost

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
}

if (!$version && $pwd) {
   // we connected to an empty database and have the password
   // now create the initial tables
   $db->debug = true;
   $db->Execute("CREATE TABLE settings 
	(id int PRIMARY KEY, 
	version  float(8), 
	settings text, 
	created datetime)");
   $db->Execute("INSERT INTO settings VALUES (1,$version_code,'')");
   $db->Execute("CREATE TABLE users 
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
   $db->Execute("CREATE TABLE groups 
	(id int PRIMARY KEY, 
	name text, 
	description text)");
   $db->Execute("CREATE TABLE usersxgroups
	(usersid int,
	groupsid int)");
   // insert sysadmin and admin group
   $pass= md5($pwd);
   $id = $db->GenID("users_id_seq");
   $idg=$db->GenID("groups_id_seq");
   $db->Execute("INSERT INTO groups VALUES
	($idg, 'admins', 'Only for real important people')");
   $db->Execute("INSERT INTO users VALUES 
	($id, '','','sysadmin', $idg, '$pass','', 127, '', '', '')");
   // insert guest and guest group
   $pass= md5("guest");
   $id = $db->GenID("users_id_seq");
   $idg=$db->GenID("groups_id_seq");
   $db->Execute("INSERT INTO groups VALUES
	($idg, 'guests', 'Only for our guests')");
   $db->Execute("INSERT INTO users VALUES 
	($id, '','','guest', $idg, '$pass','', 3, '','','')");
   $idg=$db->GenID("groups_id_seq");
   $db->Execute("INSERT INTO groups VALUES
	($idg, 'users', 'That is us')");
}

// $version is known, so we have a working database and must now authenticate
if ($version) {
   include ("includes/defines_inc.php");
   include ("includes/auth_inc.php");
   allowonly($SUPER, $USER["permissions"]);
   printheader("Settings");
   navbar($USER["permissions"]);
   $settings=get_cell($db, "settings", "settings", "id", 1);
   // display form with current settings
   echo "<form enctype='multipart/form-data' method='post' ";
   echo "name='globals-form' action='$PHP_SELF'>\n";
   echo "<table border=1 width='100%'>\n";
   echo "<tr><th>Description</th><th>Setting</th></tr>\n";
   echo "<tr><td colspan='2' align='center'><i>Login Options</i></th></tr>\n";
   echo "<tr><td>Is PhpLabWare accessible through a secure server? ";
   echo "If so, passwords will be encrypted while in transit.</td>\n";
   echo "<td>&nbsp;</td>\n";
   echo "</tr>\n";
   echo "</table>\n</form>\n";
   printfooter();
}

?>
