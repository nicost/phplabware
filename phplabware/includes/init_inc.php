<?php

// essential includes
include ('includes/config_inc.php');
include ('adodb/adodb.inc.php');

// Open connection to the database
$db = NewADOConnection ($db_type);
if (!@$db->PConnect($db_host, $db_user, $db_pwd, $db_name)) {
   echo "<h3 color='red'>Fatal Error</h3>";
   echo "Could not connect to the database server.<br>";
   echo "Please report this problem to your system administrator.";
   exit();
}

// read in the database settings
$version=get_cell($db,"settings","version","id",1);
$settings=get_cell($db,"settings","settings","id",1);

?>
