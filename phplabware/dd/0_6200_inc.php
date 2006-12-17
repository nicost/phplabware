<?php

// Adds table usersettings
$tablename ='usersettings';
$dict_062=NewDataDictionary($db);
$fields='id I NotNull, userid I NotNull, ip0 I NotNull, ip1 I NotNull, ip2 I NotNull,ip3 I NotNull,settings X\n';
$options=array('mysql'=>' TYPE=ISAM');
$sqlArray=$dict_062->CreateTableSQL($tablename,$fields,$options);
$dict_062->ExecuteSQLArray($sqlArray);
// and make indices on them
$sqlArray=$dict_062->CreateIndexSQL($tablename . '_id_index',$tablename,'id');
$dict_062->ExecuteSQLArray($sqlArray);
$sqlArray=$dict_062->CreateIndexSQL($tablename . '_userid_index',$tablename,'userid');
$dict_062->ExecuteSQLArray($sqlArray);
$sqlArray=$dict_062->CreateIndexSQL($tablename . '_ip0_index',$tablename,'ip0');
$dict_062->ExecuteSQLArray($sqlArray);
$sqlArray=$dict_062->CreateIndexSQL($tablename . '_ip1_index',$tablename,'ip1');
$dict_062->ExecuteSQLArray($sqlArray);
$sqlArray=$dict_062->CreateIndexSQL($tablename . '_ip2_index',$tablename,'ip2');
$dict_062->ExecuteSQLArray($sqlArray);
$sqlArray=$dict_062->CreateIndexSQL($tablename . '_ip3_index',$tablename,'ip3');
$dict_062->ExecuteSQLArray($sqlArray);
?>
