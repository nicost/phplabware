<?php
// updates some table description columns to type int

$db->debug=true;
$r1007=$db->Execute("SELECT id,table_desc_name FROM tableoftables");
while ($r1007 && !$r1007->EOF) {
   if ($r1007->fields[1]) {

      // rename old columns and create new ones
      $db->Execute ("ALTER TABLE {$r1007->fields[1]} RENAME COLUMN associated_table TO at_old");
      $db->Execute ("ALTER TABLE {$r1007->fields[1]} RENAME COLUMN associated_column TO ac_old");
      $db->Execute ("ALTER TABLE {$r1007->fields[1]} RENAME COLUMN associated_local_key TO alk_old");

      $rb1007=$db->Execute("ALTER TABLE {$r1007->fields[1]} ADD COLUMN associated_table int");
      $rb1007=$db->Execute("ALTER TABLE {$r1007->fields[1]} ADD COLUMN associated_column int");
      $rb1007=$db->Execute("ALTER TABLE {$r1007->fields[1]} ADD COLUMN associated_local_key int");
      
      // copy data from old to new columns
      $rc1007=$db->Execute("SELECT id,at_old,ac_old,alk_old FROM {$r1007->fields[1]}");
      $rc1007->MoveFirst();
      while ($rc1007 && !$rc1007->EOF) {
         $at=(int)$rc1007->fields[1];
         if($at)
            $db->Execute("UPDATE {$r1007->fields[1]} SET associated_table=$at WHERE id={$r1007->fields[0]}");
         $ac=(int)$rc1007->fields[2];
         if($ac)
            $db->Execute("UPDATE {$r1007->fields[1]} SET associated_column=$ac WHERE id={$r1007->fields[0]}");
         $alt=(int)$rc1007->fields[3];
         if($alt)
            $db->Execute("UPDATE {$r1007->fields[1]} SET associated_local_key=$alt WHERE id={$r1007->fields[0]");
         $rc1007->MoveNext();
      }
      
      // drop the old columns
      $db->Execute ("ALTER TABLE {$r1007->fields[1]} DROP COLUMN at_old");
      $db->Execute ("ALTER TABLE {$r1007->fields[1]} DROP COLUMN ac_old");
      $db->Execute ("ALTER TABLE {$r1007->fields[1]} DROP COLUMN alk_old");
      
   } 
   else echo "poep.<br>";
   $r1007->MoveNext();
}

$db->debug=false;

?>

