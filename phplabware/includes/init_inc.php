<?php

// functions.php - sets up database connection
// functions.php - author: Nico Stuurman <nicost@sourceforge.net>
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
$settings=unserialize(get_cell($db,"settings","settings","id",1));

?>
