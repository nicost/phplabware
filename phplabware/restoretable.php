<?php

// restoretable.php - Restores the table structure from a file created by dumptable.php
// restoretable.php - author: Nico Stuurman<nicost@sourceforge.net>

  /***************************************************************************
  * restores a table from a file created by dumptable                        *
  * Takes 'filename' as a get variable                                       *
  *                                                                          *
  * Copyright (c) 2002 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/                                                                                     

require ('include.php');

printheader($httptitle,false);
navbar ($USER['permissions']);

if (!$USER["permissions"] & $SUPER) {
   echo "<h3 align='center'>Sorry, this page is not for you.</h3>\n";
   printfooter($db, $USER);
}

$filename=$HTTP_GET_VARS['filename'];
if (!isset($filename))
   $filename=$HTTP_POST_FILES['filename']['tmp_name'];

if (!$filename) {
   echo "<table align='center'>\n";
   echo "<form method='post' name='restoretableform' enctype='multipart/form-data' action='$PHP_SELF'>\n";
   echo "<tr><td align='center'>This script restores a table (without content) from a file created by the script dumptable.php.  Upload the file containing php code to create a phplabware table: ";
   echo "<input type='file' name='filename'></td></tr>\n";
   echo "<tr><td align='center'><input type='submit' name='Upload File'></td></tr>\n";
   echo "</form></table>\n";
//   echo "<h3 align='center'>Usage: restoretable.php?filename=myfilename.</h3>\n";
//   echo "<h3 align='center'>This script restores a table (without content) created by the script dumptable.php.</h3>\n";
   printfooter ();
   exit();
}

if (@is_readable($filename)) {
   include ($filename);
   if (isset($newtablename))
      echo "<br><h3 align='center'>Created the table: <a href='general.php?tablename=$newtablename'><b>$newtablelabel</b></a></h3>.<br>\n";
   else
      echo "<br><h3 align='center'>This file does not appear to contain the necessary php code.  Please try again.</h3>.<br>\n";
}
else {
   echo "Could not read the file '$filename'.<br>";
}

printfooter ();

?>
