<?php

/*
Adds table tableviews which holds user's table layout preferences
userid tableid viewmode(table=1, view=2) viewnameid columnid
Also adds supporting table viewnames
viewnameid viewname
*/

// This might take a while:
ini_set("max_execution_time","0");

$dict=NewDataDictionary($db);

$fields='userid I NotNull, tableid I NotNull, viewmode I NotNull, viewnameid I, columnid I NotNull\n';
$options=array('mysql'=>'TYPE=ISAM');
$sqlArray=$dict->CreateTableSQL('tableviews',$fields,$options);
$dict->ExecuteSQLArray($sqlArray);
// and make indices on them
$sqlArray=$dict->CreateIndexSQL('tableviews_userid_index','tableviews','userid');
$dict->ExecuteSQLArray($sqlArray);
$sqlArray=$dict->CreateIndexSQL('tableviews_tableid_index','tableviews','tableid');
$dict->ExecuteSQLArray($sqlArray);
$sqlArray=$dict->CreateIndexSQL('tableviews_viewmode_index','tableviews','viewmode');
$dict->ExecuteSQLArray($sqlArray);
$sqlArray=$dict->CreateIndexSQL('tableviews_viewnameid_index','tableviews','viewnameid');
$dict->ExecuteSQLArray($sqlArray);
$sqlArray=$dict->CreateIndexSQL('tableviews_columnid_index','tableviews','columnid');
$dict->ExecuteSQLArray($sqlArray);

$fields='viewnameid I NotNull, viewname C\n';
$sqlArray=$dict->CreateTableSQL('viewnames',$fields,$options);
$dict->ExecuteSQLArray($sqlArray);
// and make indices on them
$sqlArray=$dict->CreateIndexSQL('viewnames_viewnameid_index','viewnames','viewnameid');
$dict->ExecuteSQLArray($sqlArray);

