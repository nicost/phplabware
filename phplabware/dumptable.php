<?php

// dumptable.php - Creates the php code needed to re-create the table structure
// dumptable.php - author: Nico Stuurman<nicost@sourceforge.net>

/***************************************************************************
* Creates the php code needed to re-create the table structure             *
* Takes 'tablename' as a get variable                                      *
*                                                                          *
* Copyright (c) 2002 by Nico Stuurman                                      *
* ------------------------------------------------------------------------ *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU General Public License as published by the   *
*  Free Software Foundation; either version 2 of the License, or (at your  *
*  option) any later version.                                              *
\**************************************************************************/                                                                                     

require ("include.php");

printheader($httptitle,false);
navbar ($USER["permissions"]);

if (!$USER["permissions"] & $SUPER) {
   echo "<h3 align='center'>Sorry, this page is not for you.</h3>\n";
   printfooter($db, $USER);
   exit();
}


$tablename=$HTTP_GET_VARS["tablename"];
$tableid=get_cell($db,"tableoftables","id","tablename",$tablename);
if (!$tableid) {
   echo "<h3>This script will create a php file with code that will re-create the table you selected in phplabware.  Only the table structure, not its content will be re-created</h3>";
   $r=$db->Execute('SELECT id,tablename FROM tableoftables');
   if ($r) {
      echo "<table align='center'>\n";
      echo "<tr><td><h3>Select one of the following tables:</h3></td></tr>\n";
      while ($r && !$r->EOF) {
         echo "<tr><td><a href='dumptable.php?tablename=".$r->fields[1]."'>".$r->fields[1]."</a></td></tr>\n";
         $r->MoveNext();
      }
   }
   printfooter($d, $USER);
   exit();
}

$table_desc=get_cell($db,"tableoftables","table_desc_name","tablename",$tablename);
$table_label=get_cell($db,"tableoftables","label","tablename",$tablename);
$table_plugin=get_cell($db,"tableoftables","plugin_code","tablename",$tablename);

// Check for associated tables:
unset($r);
$r=$db->Execute("SELECT DISTINCT associated_table FROM $table_desc WHERE associated_column !=''");
while ($r && !$r->EOF) {
   $asstable=get_cell($db,'tableoftables','tablename','id',$r->fields[0]);
   echo "Please make sure that you also export table <i>$asstable</i>, and restore table $asstable before restoring table <i>$table_label</i>, since the latter containes links to the former.<br>";
   $r->MoveNext();
}

// open file to write output to
$outfile=$system_settings["tmpdir"]."/$tablename.php";
$fp=fopen($outfile,"w");
if (!$fp) {
   echo "<h3 align='center'>Failed to open <i>$outfile</i> for output</h3>\n";
   printfooter($db, $USER);
   exit();
}

$header="<?php\n\n\n\n\n";
fwrite($fp,$header);

fwrite ($fp,'$newtableid=$db->GenID("tableoftables_gen_id_seq");'."\n");
fwrite ($fp,'$newtablename='."\"$tablename\";\n");
fwrite ($fp,'$newtablelabel='."\"$table_label\";\n");
fwrite ($fp,'for ($i=0;$i<$hownew;$i++) {$newtablelabel.="new";}'."\n");
fwrite ($fp, 'while (get_cell($db,"tableoftables","id","tablename",$newtablename)) {
   $newtablename.="n";
   $hownew++;
}
for ($i=0;$i<$hownew;$i++) 
   $newtablelabel.="new";
$newtableshortname=substr($newtablename,0,3).$newtableid;
$newtable_realname=$newtablename."_".$newtableid;
$newtable_desc_name=$newtable_realname."_desc";
$r=$db->Execute("INSERT INTO tableoftables (id,sortkey,tablename,shortname,display,permission,custom,real_tablename,table_desc_name,label,plugin_code) VALUES (\'$newtableid\',\'0\',\'$newtablename\',\'$newtableshortname\',\'Y\',\'Users\',NULL,\'$newtable_realname\',\'$newtable_desc_name\',\'$newtablelabel\',\''.$table_plugin.'\')");'."\n");

fwrite ($fp, '$rg=$db->Execute ("SELECT id FROM groups");
while (!$rg->EOF) {
   $groupid=$rg->fields[0];
   $db->Execute ("INSERT INTO groupxtable_display VALUES ($groupid,$newtableid)");
   $rg->MoveNext();
}

');

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
      associated_local_key text,
      key_table text,
      thumb_x_size int,
      thumb_y_size int,
      link_first text,
      link_last text,
      modifiable char(1) )");
   if ($rb) {
      ');
$desc_fields="sortkey,label,columnname,display_table,display_record,required,type,datatype,associated_table,associated_column,associated_local_key,key_table,thumb_x_size,thumb_y_size,link_first,link_last,modifiable";
$ADODB_FETCH_MODE=ADODB_FETCH_NUM;
$s=$db->Execute("SELECT $desc_fields FROM $table_desc");
while (!$s->EOF) {
   fwrite ($fp,'$newid=$db->GenID("$newtable_desc_name"."_id");');
   fwrite ($fp,'
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid');
   // rewrite types to standard SQL
   $value=$s->fields[6];
   if (substr ($value,0,3)=="int")
      $s->fields[6]="int";
   //elseif (substr ($value,0,7)=="varchar")
   for ($i=0; $i<sizeof($s->fields);$i++) {
      $value=$s->fields[$i];
      fwrite ($fp,",'$value'");
   }
   fwrite ($fp,')");
      ');
   // recreate pull down tables
   if ($s->fields[7]=="pulldown") {
      fwrite($fp,'$ass_table=$newtable_realname."ass";
      $id_ass=$db->GenId($ass_table,20);
      $ass_table.="_$id_ass";
      $db->Execute("CREATE TABLE $ass_table (
         id int PRIMARY KEY,
         sortkey int,
         type text,
         typeshort text)");
      $db->Execute("UPDATE $newtable_desc_name SET associated_table=\'$ass_table\' WHERE id=$newid");
      ');
   }
   // destroy links to tables, since those will fail
   elseif ($s->fields[7]=='table') {
      $asstable_name=get_cell($db,'tableoftables','tablename','id',$s->fields[8]);
      $asstable_descname=get_cell($db,'tableoftables','table_desc_name','id',$s->fields[8]);
      $asscolumnname=get_cell($db,$asstable_descname,'columnname','id',$s->fields[9]);
// also do associated local column

      fwrite($fp,'$asstable_id=get_cell($db,"tableoftables","id","tablename",'.$asstable_name.')');
      fwrite($fp,'$asstable_desc=get_cell($db,"tableoftables","table_desc_name","tablename",'.$asstable_name.')');
      fwrite($fp,'$asscolumn_id=get_cell($db,"$asstable_desc","id","columnname",'.$asscolumnname.')');
      fwrite ($fp,'$db->Execute("UPDATE $newtable_desc_name SET associated_table=asstable_id,associated_column=$asscolumn_id,key_table=NULL WHERE id=$newid");
      ');
   }
   $s->MoveNext();
}

fwrite ($fp,'// and finally create the table
      $rc=$db->Execute(" CREATE TABLE $newtable_realname (
');
$s->MoveFirst();
$fieldname=$s->fields[2];
$fieldtype=$s->fields[6];
if (substr ($fieldtype,0,3)=="int")
   $fieldtype="int";
if ($fieldname=="id")
   $extra=" NOT NULL";
fwrite ($fp,"         $fieldname $fieldtype $extra");
$s->MoveNext();
while (!$s->EOF) {
   unset ($extra);
   $fieldname=$s->fields[2];
   $fieldtype=$s->fields[6];
   if (substr ($fieldtype,0,3)=="int")
      $fieldtype="int";
   if ($fieldname=="id")
      $extra=" NOT NULL";
   fwrite ($fp,",\n         $fieldname $fieldtype $extra");
   $s->MoveNext();
}
fwrite ($fp,' ) ");
');   

$ADODB_FETCH_MODE=ADODB_FETCH_BOTH;
fwrite($fp,"
   }
}
");


fwrite ($fp,"?>");
fclose($fp);

echo "<h3>Wrote script to $outfile.</h3>";

printfooter($db, $USER);

?>
