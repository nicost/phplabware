<?php

unset ($ra);
$ra=$db->Execute("SELECT table_desc_name FROM tableoftables");

while (!($ra->EOF)) {
   if($ra->fields['table_desc_name']) {
      $db->Execute("ALTER TABLE {$ra->fields['table_desc_name']} ADD COLUMN key_table text");
   }
   $ra->MoveNext();
}
?>
