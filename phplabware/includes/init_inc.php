<?php

// init_inc.php - sets up database connection
// init_inc.php - author: Nico Stuurman <nicost@sourceforge.net>
  /***************************************************************************
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  Part of phplabware, a web-driven groupware suite for research labs      *
  *                                                                          *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/ 

// set php parameters here
set_magic_quotes_runtime(0); // seems to interfere with system settings

// register global really kills some stuff, so let's kill them first
if (ini_get("register_globals")) {
   reset($HTTP_POST_VARS);
   while (list($key,$val)=each($HTTP_POST_VARS)) {
      unset (${$key});
   }
}
reset($HTTP_POST_VARS);

// essential includes
include ('includes/config_inc.php');
include ('adodb/adodb.inc.php');
// be compatible with adodb version 1.80
$ADODB_FETCH_MODE=ADODB_FETCH_DEFAULT;

// Open connection to the database
$db = NewADOConnection ($db_type);
if (!@$db->Connect($db_host, $db_user, $db_pwd, $db_name)) {
   echo "<h3 color='red'>Fatal Error</h3>";
   echo "Could not connect to the database server.<br>";
   echo "Please report this problem to your system administrator.";
   exit();
}

// read in the database settings
$version=get_cell($db,"settings","version","id",1);
$system_settings=unserialize(get_cell($db,"settings","settings","id",1));
// set up temp dir for adodb
$ADODB_CACHE_DIR=$system_settings["tmpdir"];

$httptitle="PhpLabware: ";
?>
