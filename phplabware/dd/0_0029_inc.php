<?php

// 0_0029_inc.php - Adds 'user-defined' table 'files' 
// 0_0029_inc.php - author: Nico Stuurman

  /***************************************************************************
  * Copyright (c) 2002 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  * This code is part of phplabware (http://phplabware.sf.net)               *
  *                                                                          *
  *                                                                          *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/ 


$db->Execute("ALTER TABLE users ADD COLUMN createdbyid int");
$db->Execute("ALTER TABLE users ADD COLUMN createdbyip int");
$db->Execute("ALTER TABLE users ADD COLUMN createddate int");
$db->Execute("ALTER TABLE users ADD COLUMN modbyid int");
$db->Execute("ALTER TABLE users ADD COLUMN modbyip int");
$db->Execute("ALTER TABLE users ADD COLUMN moddate int");


?>
