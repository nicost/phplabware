<?php

// 0_0031_inc.php - See code
// 0_0031_inc.php - author: Nico Stuurman

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
$db->Execute("ALTER TABLE tableoftables ADD COLUMN real_tablename text");
$r=$db->Execute("SELECT id,tablename FROM tableoftables");
while (!$r->EOF) {
   $real_tablename=$r->fields["tablename"];
   if ($r->fields["id"]>10000)
      $real_tablename.="_".$r->fields["id"];
   $db->Execute("UPDATE tableoftables SET real_tablename='$real_tablename' WHERE id='".$r->fields["id"]."'");
   $r->MoveNext();
}

// Make it possible to assign a user to multiple groups:
$db->Execute("CREATE INDEX usersxgroups_userid ON usersxgroups(usersid)");
$db->Execute("CREATE INDEX usersxgroups_groupid ON usersxgroups(groupsid)");

// Make it possible to assign different 'powers' to different groups
$db->Execute("ALTER TABLE groups ADD COLUMN power int");

?>
