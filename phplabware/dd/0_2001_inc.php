<?php

// Adds columns gw, gr, ow, or to each table
// Adodb datadictionary is used to add these plus indices

// This might take a while:
ini_set("max_execution_time","0");

$ra=$db->Execute("SELECT real_tablename,table_desc_name,shortname FROM tableoftables");
$dict=NewDataDictionary($db);
$fields="
   gr L,
   gw L,
   er L,
   ew L
";
$fieldstring="id,label,columnname,sortkey,display_table,display_record, required, type, datatype, associated_table, associated_column, modifiable"; 
while (!($ra->EOF)) {
   if($ra->fields['table_desc_name']) {
      $shortname=$ra->fields['shortname']; 
      // add the new columns
      $sqlArray=$dict->AddColumnSQL($ra->fields['real_tablename'],$fields);
      $dict->ExecuteSQLArray($sqlArray);
      // and make indices on them
      $sqlArray=$dict->CreateIndexSQL($shortname.'_gr_index',$ra->fields['real_tablename'],'gr');
      $dict->ExecuteSQLArray($sqlArray);
      $sqlArray=$dict->CreateIndexSQL($shortname.'_gw_index',$ra->fields['real_tablename'],'gw');
      $dict->ExecuteSQLArray($sqlArray);
      $sqlArray=$dict->CreateIndexSQL($shortname.'_er_index',$ra->fields['real_tablename'],'er');
      $dict->ExecuteSQLArray($sqlArray);
      $sqlArray=$dict->CreateIndexSQL($shortname.'_ew_index',$ra->fields['real_tablename'],'ew');
      $dict->ExecuteSQLArray($sqlArray);

      // update description tables
      $desc=$ra->fields['table_desc_name'];
      // check that $desc_id (sequence) is in sync with the table, and get it in sync if it was not:
      $rt=$db->Execute("SELECT max(id) FROM $desc");
      $max=$rt->fields[0];
      if ($max) {
         while ($descid <=$max)
            $descid=$db->GenId("$desc"."_id");  
      }
      else
         $descid=$db->GenId("$desc"."_id");  
      // now let's be sure we did not do this already:
      unset($rt);
      $rt=$db->Execute ("SELECT id FROM $desc WHERE columnname='gr'");
      if (!$rt->fields[0]) {
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'group read','gr','111','N','N','N','smallint','int',NULL,NULL,'Y')");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'group write','gw','112','N','N','N','smallint','int',NULL,NULL,'Y')");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'everyone read','er','113','N','N','N','smallint','int',NULL,NULL,'Y')");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'everyone write','ew','114','N','N','N','smallint','int',NULL,NULL,'Y')");
      }
   }
   $ra->MoveNext();
}
