<?php

// 0_0034_inc.php - See code
// 0_0034_inc.php - author: Nico Stuurman

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

$db->Execute("ALTER TABLE files ADD COLUMN ftablecolumnid int");
$r=$db->Execute("SELECT id,tablesfk,ftableid FROM files");
while (!$r->EOF) {
   $rb=$db->Execute("SELECT id,real_tablename,table_desc_name FROM tableoftables WHERE id='".$r->fields["tablesfk"]."'");
   if ($rb->fields["id"]> 9999) {
      $rc=$db->Execute("SELECT id FROM ".$rb->fields["table_desc_name"]." WHERE datatype='file'");
      $rd=$db->Execute("UPDATE files SET ftablecolumnid='".$rc->fields["id"]."' WHERE id='".$r->fields["id"]."'"); 
   }
   // for the old tables, simply set columnid to 1.  This might be important
   // if table descriptions for the old tables will ever be made
   else
      $rd=$db->Execute("UPDATE files SET ftablecolumnid='1' WHERE id='".$r->fields["id"]."'");
   $r->MoveNext();
}

?>
