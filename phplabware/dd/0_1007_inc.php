<?php
// updates some table description columns to type int

$db->debug=true;
$r=$db->Execute("SELECT id,table_desc_name FROM tableoftables");
while (!$r->EOF) {
   if ($r->fields[1]) {

      // rename old columns and create new ones
      $db->Execute ("ALTER {$r->fields[1]} RENAME COLUMN associated_table at_old");
      $db->Execute ("ALTER {$r->fields[1]} RENAME COLUMN associated_column ac_old");
      $db->Execute ("ALTER {$r->fields[1]} RENAME COLUMN associated_local_key alk_old");

      $rb=$db->Execute("ALTER {$r->fields[1]} ADD COLUMNS associated_table int, associated_column int, associated_local_key int");
      
      // copy data from old to new columns
      $rc=$db->Execute("SELECT id,at_old,ac_old,alk_old FROM {$r->fields[1]}");
      while (!$rc->Eof) {
         $at=(int)$rc->fields[0];
         if($at)
            $db->Execute("UPDATE {$r->fields[1]} SET associated_table=$at");
         $ac=(int)$rc->fields[1];
         if($ac)
            $db->Execute("UPDATE {$r->fields[1]} SET associated_column=$ac");
         $alt=(int)$rc->fields[2];
         if($alt)
            $db->Execute("UPDATE {$r->fields[1]} SET associated_local_key=$alt");
         $rc->MoveNext();
      }
      
      // drop the old columns
      $db->Execute ("ALTER TABLE {$r->fields[1]} DROP COLUMN at_old");
      $db->Execute ("ALTER TABLE {$r->fields[1]} DROP COLUMN ac_old");
      $db->Execute ("ALTER TABLE {$r->fields[1]} DROP COLUMN alk_old");
      
      $r->MoveNext();
   } 
}


?>

