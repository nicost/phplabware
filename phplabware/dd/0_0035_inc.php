<?php

// 0_0035_inc.php - See code
// 0_0035_inc.php - author: Nico Stuurman

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

// Create indices on some widely used fields
$r=$db->Execute("SELECT real_tablename FROM tableoftables WHERE table_desc_name IS NOT NULL");
while (!$r->EOF) {
   $real_tablename=$r->fields["real_tablename"];
   echo "$real_tablename\n";
   $db->Execute("CREATE INDEX $real_tablename"."_id_index ON $real_tablename (id)");
   $db->Execute("CREATE INDEX $real_tablename"."_title_index ON $real_tablename (title)");
   $db->Execute("CREATE INDEX $real_tablename"."_title_index ON $real_tablename (title(10))");
   $db->Execute("CREATE INDEX $real_tablename"."_access_index ON $real_tablename (access)");
   $db->Execute("CREATE INDEX $real_tablename"."_access_index ON $real_tablename (access(9))");
   $db->Execute("CREATE INDEX $real_tablename"."_ownerid_index ON $real_tablename (ownerid)");
   $db->Execute("CREATE INDEX $real_tablename"."_date_index ON $real_tablename (date)");
  
   $r->MoveNext();
}

?>
