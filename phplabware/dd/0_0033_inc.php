<?php

// 0_0033_inc.php - See code
// 0_0033_inc.php - author: Nico Stuurman

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

// rename associated_sql, add columns link_first, link_second, and modifiable
$r=$db->Execute("SELECT table_desc_name FROM tableoftables WHERE table_desc_name IS NOT NULL");
while (!$r->EOF) {
   $table_desc_name=$r->fields["table_desc_name"];
   $db->Execute("ALTER TABLE $table_desc_name CHANGE associated_sql associated_column text");
   $db->Execute("ALTER TABLE $table_desc_name RENAME associated_sql TO associated_column");
   $db->Execute("ALTER TABLE $table_desc_name ADD COLUMN link_first text");
   $db->Execute("ALTER TABLE $table_desc_name ADD COLUMN link_last text");
   $db->Execute("ALTER TABLE $table_desc_name ADD COLUMN modifiable varchar(1)");
   $r->MoveNext();
}

?>
