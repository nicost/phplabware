<?php
  
// views.php - Editor for users table layout preferences
// views.php - author: Nico Stuurman <nicost@soureforge.net>
  /***************************************************************************
  * Copyright (c) 2003 by Nico Stuurman<nicost@sf.net>                       *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

// Needs get/postvars:tablename
// optional get/postvar: viewnameid

/// main include thingies
require('include.php');
require('includes/db_inc.php');
require('includes/general_inc.php');
require('includes/report_inc.php');

printheader($httptitle);
navbar($USER['permissions']);

// First section list tables that can be edited, existing views, edit and delete buttons
if (isset $HTTP_POST_VARS['tablename'])
   $HTTP_GET_VARS['tablename']=$HTTP_POST_VARS['tablename'];
$tableinfo=new tableinfo($db);

// open form
echo "<form name=views>\n";

echo "Edit views for table: "
// make dropdown with accessible tablenames, select the current tablename



/*
if (!$tableinfo->id) {
   printheader($httptitle);
   navbar($USER['permissions']);
   echo "<h3 align='center'> Table: <i>$HTTP_GET_VARS[tablename]</i> does not exist.</h3>";
   printfooter();
   exit();
}
*/
?>
