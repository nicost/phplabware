<?php

// Changes ownerid, date, lastmoddate, lastmodby into viewable/editable fields
// Introduces datatype user and date

unset($rdesc);
$rdesc=$db->Execute("SELECT real_tablename,table_desc_name FROM tableoftables");
while (!($rdesc->EOF)) {
   if($rdesc->fields[table_desc_name]) {
      $rmax=$db->Execute("SELECT max(sortkey) FROM ".$rdesc->fields[table_desc_name]);
      $sortkeymax=$rmax->fields[0];
      $sortkeymax+=10;
      $db->Execute("UPDATE ".$rdesc->fields[table_desc_name]." SET label='Submitted by',display_record='Y',datatype='user',sortkey='$sortkeymax' WHERE label='ownerid' AND columnname='ownerid'"); 
      $sortkeymax+=10;
      $db->Execute("UPDATE ".$rdesc->fields[table_desc_name]." SET label='Date entered',display_record='Y',datatype='date',sortkey='$sortkeymax' WHERE label='date' AND columnname='date'"); 
      $sortkeymax+=10;
      $db->Execute("UPDATE ".$rdesc->fields[table_desc_name]." SET label='Last modified on',datatype='date',sortkey='$sortkeymax' WHERE label='lastmoddate' AND columnname='lastmoddate'"); 
      $sortkeymax+=10;
      $db->Execute("UPDATE ".$rdesc->fields[table_desc_name]." SET label='Last modified by',datatype='user',sortkey='$sortkeymax' WHERE label='lastmodby' AND columnname='lastmodby'"); 
   }
   $rdesc->MoveNext();
}

?>
