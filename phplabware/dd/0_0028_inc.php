<?php

// 0_0027_inc.php - Adds 'user-defined' table 'files' 
// 0_0027_inc.php - author: Nico Stuurman

  /***************************************************************************
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  * This code is part of phplabware (http://phplabware.sf.net)               *
  *                                                                          *
  *                                                                          *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/ 


$id=$db->GenID("tableoftables_id_seq");
$db->Execute("INSERT INTO tableoftables (id,sortkey,tablename,shortname,Display,Permission,Custom) VALUES($id,'1000','settings','se','Y','Users','users.php?type=me&dummy=true')");

?>
