<?php

// 0_0032_inc.php - See code
// 0_0032_inc.php - author: Nico Stuurman

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

// get the real tablenames into a column
$db->Execute("ALTER TABLE tableoftables ADD COLUMN table_desc_name text");
$r=$db->Execute("SELECT id,real_tablename FROM tableoftables");
while (!$r->EOF) {
   $real_tablename=$r->fields["real_tablename"];
   $tableid=$r->fields["id"];
   if ($id>10000) {
      $table_desc_name=$real_tablename."_desc";
      $db->Execute("UPDATE tableoftables SET table_desc_name='$table_desc_name' WHERE id='$tableid'");
      $db->Execute("ALTER TABLE $table_desc_name ADD COLUMN columnname text");
      $db->Execute("ALTER TABLE $table_desc_name ADD COLUMN associated_local_key int");
      $db->Execute("ALTER TABLE $table_desc_name ADD COLUMN thumb_x_size int");
      $db->Execute("ALTER TABLE $table_desc_name ADD COLUMN thumb_y_size int");
      $rd=$db->Execute("SELECT id,label FROM $table_desc_name");
      while (!$rd->Eof()) {
         $db->Execute("UPDATE $table_desc_name SET (columnname='".$rd->fields["label"]."') WHERE id='$id'");
         $rd->MoveNExt();
      }
   }
   $r->MoveNext();
}

?>
