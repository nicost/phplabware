<?php

// Adds columns gw, gr, ow, or to each table
// Adodb datadictionary is used to add these plus indices

$ra=$db->Execute("SELECT real_tablename FROM tableoftables");
$dict=NewDataDictionary($db);
$fields="
   gr L,
   gw L,
   er L,
   ew L
";
while (!($ra->EOF)) {
   if($ra->fields['real_tablename']) {
      $sqlArray=$dict->AddColumnSQL($ra->fields['real_tablename'],$fields);
      $dict->ExecuteSQLArray($sqlArray);
      $sqlArray=$dict->CreateIndexSQL('gr_index',$ra->fields['real_tablename'],'gr');
      $dict->ExecuteSQLArray($sqlArray);
      $sqlArray=$dict->CreateIndexSQL('gw_index',$ra->fields['real_tablename'],'gw');
      $dict->ExecuteSQLArray($sqlArray);
      $sqlArray=$dict->CreateIndexSQL('er_index',$ra->fields['real_tablename'],'er');
      $dict->ExecuteSQLArray($sqlArray);
      $sqlArray=$dict->CreateIndexSQL('ew_index',$ra->fields['real_tablename'],'ew');
      $dict->ExecuteSQLArray($sqlArray);
   }
   $ra->MoveNext();
}
