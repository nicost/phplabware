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

// Make a table that allows showing different tables to different groups
$db->Execute("CREATE TABLE groupxtable_display (
		groupid int,
		tableid int,
		CONSTRAINT gxtspecial PRIMARY KEY (groupid,tableid))");
$db->Execute("CREATE INDEX groupxtable_display_groupid ON groupxtable_display(groupid)");
$db->Execute("CREATE INDEX groupxtable_display_tableid ON groupxtable_display(tableid)");
// Make all current tables visible to all groups
$rg=$db->Execute("SELECT id FROM groups");
while (!$rg->EOF) {
  $ag[]=$rg->fields["id"];
  $rg->MoveNext();
}
$rt=$db->Execute("SELECT id FROM tableoftables");
while (!$rt->EOF) {
  $at[]=$rt->fields["id"];
  $rt->MoveNext();
}
foreach ($ag AS $groupid)
   foreach ($at AS $tableid)
      $db->Execute("INSERT INTO groupxtable_display VALUES ('$groupid','$tableid')");

// Add some indices to tableoftables
$db->Execute("CREATE INDEX tableoftables_id ON tableoftables(id)");
$db->Execute("CREATE INDEX tableoftables_tablename ON tableoftables(tablename)");
?>
