<?php

// Adds columns gw, gr, ow, or to each table
// Adodb datadictionary is used to add these plus indices
$db->debug=true;

$ra=$db->Execute("SELECT real_tablename,table_desc_name,shortname FROM tableoftables");
$dict=NewDataDictionary($db);
$fields="
   gr L,
   gw L,
   er L,
   ew L
";
while (!($ra->EOF)) {
   if($ra->fields['real_tablename']) {
      $shortname=$ra->fields['shortname']; 
      $sqlArray=$dict->AddColumnSQL($ra->fields['real_tablename'],$fields);
      $dict->ExecuteSQLArray($sqlArray);
      $sqlArray=$dict->CreateIndexSQL($shortname.'_gr_index',$ra->fields['real_tablename'],'gr');
      $dict->ExecuteSQLArray($sqlArray);
      $sqlArray=$dict->CreateIndexSQL($shortname.'_gw_index',$ra->fields['real_tablename'],'gw');
      $dict->ExecuteSQLArray($sqlArray);
      $sqlArray=$dict->CreateIndexSQL($shortname.'_er_index',$ra->fields['real_tablename'],'er');
      $dict->ExecuteSQLArray($sqlArray);
      $sqlArray=$dict->CreateIndexSQL($shortname.'_ew_index',$ra->fields['real_tablename'],'ew');
      $dict->ExecuteSQLArray($sqlArray);

      // update description tables
      $fieldstring="id,label,columnname,sortkey,display_table,display_record, required, type, datatype, associated_table, associated_column"; 
      $desc=$ra->fields['table_desc_name'];
      $descid=$db->GenId("$desc"."_id");  
      $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'gr','group read','111','N','N','N','smallint','int',NULL,NULL)");
      $descid=$db->GenId("$desc"."_id");  
      $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'gw','group write','112','N','N','N','smallint','int',NULL,NULL)");
      $descid=$db->GenId("$desc"."_id");  
      $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'er','everyone read','113','N','N','N','smallint','int',NULL,NULL)");
      $descid=$db->GenId("$desc"."_id");  
      $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'ew','everyone write','114','N','N','N','smallint','int',NULL,NULL)");
   }
   $ra->MoveNext();
}
