<?php

require ("include.php");

printheader($httptitle,false);
navbar ($USER["permissions"]);

if (!$USER["permissions"] & $SUPER) {
   echo "<h3 align='center'>Sorry, this page is not for you.</h3>\n";
   printfooter($db, $USER);
}

$tablename=$HTTP_GET_VARS["tablename"];
if (!$tablename) {
   echo "<h3 align='center'>Usage: dumptable.php?tablename=mytablename.</h3>\n";
   printfooter($d, $USER);
}

$tableid=get_cell($db,"tableoftables","id","tablename",$tablename);
if (!$tableid) {
   echo "<h3 align='center'>Table <i>$tablename</i> does not exist.</h3>\n";
   printfooter($d, $USER);
}

$table_desc=get_cell($db,"tableoftables","table_desc_name","tablename",$tablename);
$table_label=get_cell($db,"tableoftables","label","tablename",$tablename);


// open file to write output to
$outfile=$system_settings["tmpdir"]."/dumptable.php";
$fp=fopen($outfile,"w");
if (!$fp) {
   echo "<h3 align='center'>Failed to open <i>$outfile</i> for output</h3>\n";
   printfooter($d, $USER);
}

$header="<?php\n\n\n\n\n";
fwrite($fp,$header);

fwrite ($fp,'$newtableid=$db->GenID("tableoftables_id_seq");'."\n");
fwrite ($fp,'$newtablename='."$tablename;\n");
fwrite ($fp, 'while (get_cell($db,"tableoftables","id","tablename",$newtablename)) {
   $newtablename.="-new";
}
$newtableshortname=substr($newtablename,0,3);
$newtable_realname=$newtablename."_".$newtableid;
$newtable_desc_name=$newtable_realname."_desc";
$r=$db->Execute("INSERT INTO tableoftables id,sortkey,tablename,shortname,display,permission,custom,real_tablename,table_desc_name,label VALUES (\'$newtableid\',\'0\',\'$newtablename\',\'$newtableshortname\',\'Y\',\'Users\',NULL,\'$newtable_realname\',\'$newtable_desc_name\','."'$table_label')\");\n");

fwrite ($fp, 'if ($r) {
   $rb=$db->Execute("CREATE TABLE $newtable_desc_name (
      id int NOT NULL,
      sortkey int,
      label text,
      columnname text,
      display_table char(1),
      display_record char(1),
      required char(1),
      type text,
      datatype text,
      associated_table text,
      associated_column text,
      thumb_x_size int,
      thumb_y_size int,
      link_first text,
      link_last text,
      modifiable char(1) )");
   if ($rb) {
      '

);




fwrite ($fp,"?>");
fclose($fp);

printfooter($d, $USER);

?>
