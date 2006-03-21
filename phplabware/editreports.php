<?php

// editreports.php - Design and change reports
// editreports.php - author: Nico Stuurman <nicost@soureforge.net>

  /***************************************************************************
  * This script edits templates for reports                                  *
  *                                                                          *
  * Copyright (c) 2006 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/


require('./include.php');
require('./includes/db_inc.php');
require('./includes/general_inc.php');
require('./includes/tablemanage_inc.php');
include_once ('./includes/defines_inc.php');

$editreport=$_GET['editreport'];
$editreport=true;
$post_vars='table_id,tablename';
globalize_vars($post_vars, $_POST);
globalize_vars($post_vars, $_GET);

// this needs to be done before headers are sent in printheader
while((list($key, $val) = each($_POST))) {
   if (substr($key, 0, 9) == 'expreport') { 
      $modarray = explode('_', $key);
      export_report($db,$modarray[1]);
      exit;
   }
}
reset($_POST);

printheader($httptitle,false,$jsfiles);

while((list($key, $val) = each($_POST))) {
  if (substr($key, 0, 9) == "modreport") {  
      $modarray = explode("_", $key);
      $tplmessage=mod_report($db,$modarray[1]);
      break;
   } elseif (substr($key, 0, 9) == "delreport") { 
      $modarray = explode("_", $key);
      rm_report($db,$modarray[1]);
      break;
   } elseif (substr($key, 0, 10) == "testreport") { 
      $modarray = explode("_", $key);
      $tplmessage=test_report($db,$modarray[1],$editreport);
      break;
   } elseif ($key=="addreport") {
      $tplmessage=add_report($db);
      break;
   }
}

navbar($USER['permissions']);
// when no tablename is given, list all tables this user can write to 
if (!$tablename) {
   echo "<br><br>\n";
   echo "<form name='views' method='post' action='$PHP_SELF'>\n";
   echo "<table width='100%' border='0'>\n";
   echo "<tr>\n";
   echo "<td align='center'><h3>Edit report templates for table: </h3>";

   // make dropdown with accessible tablenames, select the current tablename
   if ($USER['permissions'] & $SUPER) {
      $r=$db->Execute ("SELECT label,tablename FROM tableoftables ORDER by sortkey ");
   } else {
      $r=$db->Execute("SELECT tableoftables.label,tableoftables.tablename FROM tableoftables LEFT JOIN groupxtable_display ON tableoftables.id=groupxtable_display.tableid WHERE groupxtable_display.groupid IN ({$USER['group_list']}) ORDER BY tableoftables.sortkey");
   }                                                                            
   echo $r->GetMenu2('tablename',$tablename,true,false,0,'OnChange="document.views.submit()"');
   echo "</td></tr></table></form>\n";
   printfooter();
   exit;
}

echo $tplmessage;

$r=$db->Execute("SELECT id,table_desc_name,label FROM tableoftables WHERE tablename='$tablename'");
$tableid=$r->fields['id'];
$tablelabel=$r->fields['label'];
$tabledesc=$r->fields[1];
echo "<h3 align='center'>$string</h3>";
echo "<h3 align='center'>Edit report templates for table <i>$tablelabel</i></h3><br>";

echo "<form method='post' name='reportform' id='repedit' enctype='multipart/form-data' ";
$dbstring=$PHP_SELF;
echo "action='$dbstring?editreport=$editreport&".SID."'>\n"; 

// Tableheader
echo "<table align='center' border='0' cellpadding='2' cellspacing='0'>\n";
echo "<tr>\n";
echo "<th>Report Name</th>\n";
echo "<th>Sortkey</th>\n";
echo "<th>Template File Add/Change</th>\n";
echo "<th>File present</th>";
echo "<th>Action</th>\n";
echo "</tr>\n";

// New addition
echo "<input type='hidden' name='table_name' value='$editreport'>\n";
echo "<tr align='center' >\n";
echo "<td><input type='text' name='addrep_label' value='' size='10'></td>\n";
echo "<td><input type='text' name='addrep_sortkey' value='' size='5'></td>\n";
echo "<td><input type='file' name='addrep_template'</td>\n";
echo "<td>&nbsp;</td>\n";
echo "<td align='center'><input type='submit' name='addreport' value='Add'></td></tr>\n\n";

// Loop through existing templates
$rp=$db->Execute("SELECT id,label,sortkey,filesize FROM reports WHERE tableid='$tableid' ORDER BY sortkey");
$rownr=0;
while ($rp && !$rp->EOF) {
   $id=$rp->fields["id"];
   echo "<input type='hidden' name='report_id[$rownr]' value='$id'>\n";
   if ($rownr % 2) 
     echo "<tr class='row_odd' align='center'>\n";	
   else 
      echo "<tr class='row_even' align='center'>\n";         

   echo "<td><input type='text' name='report_label[$rownr]' value='".$rp->fields["label"]."' size=10></td>\n";
   echo "<td><input type='text' name='report_sortkey[$rownr]' value='".$rp->fields["sortkey"]."'size=5></td>\n";
   echo "<td><input type='file' name='report_template[$rownr]'</td>\n";
   if (is_readable($system_settings["templatedir"]."/$id.tpl"))
      echo "<td>Yes</td>\n";
   else
      echo "<td>No</td>\n";
   $modstring = "<input type='submit' name='modreport"."_$rownr' value='Modify'>\n";
   $exportstring = "<input type='submit' name='expreport"."_$rownr' value='Export'>\n";
   $teststring = "<input type='submit' name='testreport"."_$rownr' value='Test'>\n";
   $delstring = "<input type='submit' name='delreport"."_$rownr' value='Remove' ";
   $delstring .= "Onclick=\"if(confirm('Are you absolutely sure that the report ".$rp->fields["label"] ." should be removed? (No undo possible!)')){return true;}return false;\">";  
   echo "<td>$modstring &nbsp;\n$delstring &nbsp;\n$exportstring &nbsp;\n$teststring</td>\n";
   echo "</tr>\n";
   $rp->MoveNext();
   $rownr++;
}
echo "</table>\n";

echo "<br>\n";
// list all available fields and field labels :
echo "<table width=75% align='center' border='0' cellpadding='2' cellspacing='0'>\n";
echo "<tr><td> </td><td colspan=2 align='right' >Column names available:</td></tr>\n";
echo "<tr><td> </td><th>Column Name</th><th>Column Label</th></tr>\n";
$r=$db->Execute("SELECT columnname,label FROM {$tabledesc}");
// show the help text in the left most column
$rowspan=$r->RecordCount() + 1;
echo "<tr><td rowspan=$rowspan valign='top'>";
include 'documents/help_reports.php';
echo "</td>\n";
$first=true;
while ($r && !$r->EOF) {
   if ($first) {
      echo "<td>{$r->fields[0]}</td><td>{$r->fields[1]}</td></tr>\n";
      $first=false;
   } else {
      echo "<tr><td>{$r->fields[0]}</td><td>{$r->fields[1]}</td></tr>\n";
   }

   $r->MoveNext();
}
echo "</table\n";




printfooter();
exit;

?>
