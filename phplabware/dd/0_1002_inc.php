<?php

// Adds column 'indexed' to table files
// Adds associated tables for indexing to every column of type 'file'
$db->Execute("ALTER TABLE files ADD COLUMN indexed integer");
$db->Execute("CREATE INDEX files_indexed ON files (indexed)");

$rdesc=$db->Execute("SELECT real_tablename,table_desc_name FROM tableoftables");
while (!($rdesc->EOF)) {
   if($rdesc->fields[table_desc_name]) {
      $rfile=$db->Execute("SELECT id FROM ".$rdesc->fields[table_desc_name]." WHERE datatype='file'");
      while (!($rfile->EOF)) {
         if ($rfile->fields[id]) {
            $tablestr=$rdesc->fields[real_tablename]."_wi_".$rfile->fields[id];
            $rs=$db->Execute("CREATE TABLE $tablestr (wordid int, fileid int,pagenr int, recordid int,UNIQUE (wordid,fileid,pagenr,recordid))");
            $db->Execute("CREATE INDEX $tablestr"."_wi ON $tablestr(wordid)");
            $db->Execute("CREATE INDEX $tablestr"."_fi ON $tablestr(fileid)");
            $db->Execute("CREATE INDEX $tablestr"."_ri ON $tablestr(recordid)");
         }
         $rfile->MoveNext();
      }
   }
   $rdesc->MoveNext();
}

?>
