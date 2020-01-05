<?php

// tablemanage.php - Design and change tables 
// tablemanage.php - author: Ethan Garner, Nico Stuurman <nicost@soureforge.net>

  /***************************************************************************
  * This script tables in phplabware.                                        *
  *                                                                          *
  * Copyright (c) 2001 by Ethan Garner, Nico Stuurman                        *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

$Custom='';
$string='';

require('./include.php');
require('./includes/db_inc.php');
require('./includes/general_inc.php');
require('./includes/tablemanage_inc.php');
include ('./includes/defines_inc.php');

if (array_key_exists('editfield', $_GET)) {
   $editfield=$_GET['editfield'];
}
if (array_key_exists('editreport', $_GET)) {
   $editreport=$_GET['editreport'];
}
$post_vars="newtable_name,newtable_label,newtable_sortkey,newtable_plugincode,addtable,table_id,table_name,table_display,addcol_name,addcol_label,addcol_sort,addcol_dtable,addcol_drecord,addcol_required,addcol_modifiable,addcol_datatype";
globalize_vars($post_vars, $_POST);

// this needs to be done before headers are sent in printheader
while((list($key, $val) = each($_POST))) {
   if (substr($key, 0, 9) == 'expreport') { 
      $modarray = explode("_", $key);
      export_report($db,$modarray[1]);
      exit;
   }
}
reset($_POST);

// when editing columns of a table include this javascript file:
$jsfiles = null;
if (isset($editfield)) {
   $jsfiles[]='./includes/js/editfields.js';
}

$permissions=$USER['permissions'];
if (!empty($addcol_datatype) && $addcol_datatype=='table') 
   $jsfiles[]='./includes/js/tablemanage.js';

// Except for ajax requests, we'll send the general headers now
if (!isset($_POST['jsrequest'])) {
   printheader($httptitle,false,$jsfiles);
} 

if (!( ($permissions & $SUPER) || ($permissions & $TABLECREATOR) )) {
	navbar($USER['permissions']);
	echo "<h3 align='center'><b>Sorry, this page is not for you</B></h3>";
	printfooter($db,$USER);
	exit;
}

while((list($key, $val) = each($_POST))) {
   if ($key == "addtable") {
      add_table($db,$newtable_name,$newtable_label,$newtable_sortkey,$newtable_plugincode);
      break;
   }
   elseif (substr($key, 0, 8) == 'modtable') {
      $modarray = explode("_", $key);
      $id=$_POST['table_id'][$modarray[1]];
      mod_table($db,$id,$modarray[1]);
      break;
   }
   elseif (substr($key, 0, 8) == 'deltable') {  
      $modarray = explode("_", $key);      
      $id=$_POST['table_id'][$modarray[1]]; 
      $tablename=$_POST['table_name'][$modarray[1]];      
      del_table($db,$tablename,$id,$USER);   
      break;
   }
   elseif ($key=='table_column_select') {
      add_associated_table($db,$table_name,$addcol_name,$_POST['table_select'],$_POST['table_column_select']);
      break;
   }
   elseif ($key=="ass_to") {
      add_associated_table($db,$table_name,$addcol_name,false,false);
      break;
   }
   elseif ($key=="link_part_a") {
      add_active_link($db,$table_name,$addcol_name,$_POST["link_part_a"],$_POST["link_part_b"]);
      break;
   }
   elseif ($key == "addcolumn") {  
      $result=add_columnECG($db,$table_name,$addcol_name,$addcol_label,$addcol_datatype,$addcol_drecord,$addcol_dtable,$addcol_required,$addcol_modifiable,$addcol_sort);
      if ($addcol_datatype=="table" && $result) {
         navbar($USER["permissions"]);
         show_table_column_page($db,$table_name,$addcol_name,$addcol_label);
         printfooter();
         exit();
      }
      break;
   }   	
   // when editing columns use Ajax to set this POST variable as well as the columnname that needs to be changed and the new value.  Handle this and exit (no HTML output needed).
   elseif (substr($key, 0, 11) == 'modcolumnjs') {  
      $modarray = explode("_", $key);
      mod_columnjs($db,$modarray[1]);
      exit;
      break;
   }
   elseif (substr($key, 0, 9) == "modcolumn") {  
      $modarray = explode("_", $key);
      mod_columnECG($db,$sort,$modarray[1]);
      break;
   }   	
   elseif (substr($key, 0, 9) == 'delcolumn') { 
      $modarray = explode("_", $key);
      $tablename=$_POST['table_name'];
      $id=$_POST['column_id_'.$modarray[1]]; 
      $colname=$_POST['column_name_'.$modarray[1]];
      $datatype=$_POST['column_datatype_'.$modarray[1]];
      rm_columnecg($db,$tablename,$id,$colname,$datatype);
      break;
   } elseif (substr($key, 0, 11) == 'alinkcolumn') { 
      $modarray = explode("_", $key);
      $tablename=$_POST['table_name'];
      $id=$_POST['column_id_'.$modarray[1]]; 
      $colname=$_POST['column_name_'.$modarray[1]];
      $collabel=$_POST['column_label_'.$modarray[1]];
      $datatype=$_POST['column_datatype_'.$modarray[1]];
      $table_desc=get_cell($db,'tableoftables','table_desc_name','tablename',$tablename);
      $link_a=get_cell($db,$table_desc,"link_first","id",$id);
      $link_b=get_cell($db,$table_desc,"link_last","id",$id);
      navbar($USER["permissions"]);
      show_active_link_page($db,$tablename,$colname,$collabel,$link_a,$link_b);
      printfooter();
      exit();
      break;
   }
   elseif (substr($key, 0, 9) == "modreport") {  
      $modarray = explode("_", $key);
      $tplmessage=mod_report($db,$modarray[1]);
      break;
   } 
   elseif (substr($key, 0, 9) == "delreport") { 
      $modarray = explode("_", $key);
      rm_report($db,$modarray[1]);
      break;
   }
   elseif (substr($key, 0, 10) == "testreport") { 
      $modarray = explode("_", $key);
      $tplmessage=test_report($db,$modarray[1],$editreport);
      break;
   }
   elseif ($key=="addreport") {
      $tplmessage=add_report($db);
      break;
   }
}

if (!empty($editfield) && $editfield)	{
   $noshow=array('id','access','magic','lastmoddate','lastmodby','gr','gw','er','ew');
   $nodel=array('title','date','ownerid','lastmodby','lastmoddate');
   $nomodifiable=array('ownerid','date','lastmodby','lastmoddate');
   navbar($USER["permissions"]);

   $r=$db->Execute("SELECT id,table_desc_name,label FROM tableoftables WHERE tablename='$editfield'");
   $id=$r->fields['id'];
   $currdesc=$r->fields['table_desc_name'];
   $tablelabel=$r->fields['label'];
   echo "<h3 align='center'>$string</h3>";
   echo "<h3 align='center'>Edit columns of table <i>$tablelabel</i></h3><br>";

   echo "<form method='post' name='tableform' id='coledit' enctype='multipart/form-data' ";
   $dbstring=$PHP_SELF;echo "action='$dbstring?editfield=$editfield&".SID."'>\n"; 
   echo "<table align='center' border='0' cellpadding='2' cellspacing='0'>\n";
   echo "<tr>\n";
   echo '<th>(SQL) Column Name</th>';
   echo '<th>Label</th>';
   echo '<th>Sortkey</th>';
   echo '<th>Table display</th>';
   echo "<th>Record display</th>\n";
   echo "<th>Required </th>\n";
   echo "<th>Modifiable </th>\n";
   echo "<th>Datatype</th>\n";
   echo "<th>Ass. Table/Column</th>\n";
   echo "<th>Active Link</th>\n";
   echo "<th>Action</th>\n";
   echo "</tr>\n";
   echo "<input type='hidden' name='table_name' value='$editfield'>\n";
   echo "<tr align='center' ><td><input type='text' name='addcol_name' value='' size='10'></td>\n";
   echo "<td><input type='text' name='addcol_label' value='' size='10'></td>\n";
   echo "<td><input type='text' name='addcol_sort' value='' size='5'></td>\n";
   echo "<td><input type='radio' name='addcol_dtable' checked value='Y'>yes<input type='radio' name='addcol_dtable'  value='N'>no</td>\n";
   echo "<td><input type='radio' name='addcol_drecord' checked value='Y'>yes<input type='radio' name='addcol_drecord'  value='N'>no</td>\n";
   echo "<td><input type='radio' name='addcol_required'  value='Y'>yes<input type='radio' name='addcol_required' checked value='N'>no</td>\n";
   echo "<td><input type='radio' name='addcol_modifiable' checked value='Y'>yes<input type='radio' name='addcol_modifiable' value='N'>no</td>\n";
   echo "<td><select name='addcol_datatype'>\n";
   echo "<option value='text'>text</option>\n";
   echo "<option value='textlong'>textlong</option>\n";
   echo "<option value='int'>int</option>\n";
   echo "<option value='float'>float</option>\n";
   echo "<option value='sequence'>sequence</option>\n";
   echo "<option value='date'>date</option>\n";
   echo "<option value='table'>table</option>\n";
   echo "<option value='pulldown'>pulldown</option>\n";
   echo "<option value='mpulldown'>mpulldown</option>\n";
   echo "<option value='link'>weblink</option>\n";
   echo "<option value='file'>file</option>\n";
   if (array_key_exists('convert', $system_settings) && $system_settings['convert'])
      echo "<option value='image'>image</option>\n";
   echo "</select></td>\n";
   echo "<td>&nbsp;</td>\n";
   echo "<td>&nbsp;</td>\n";
   echo "<td align='center'><input type='submit' name='addcolumn' value='Add'></td></tr>\n\n";
   
   $query = "SELECT id,sortkey,columnname,label,display_table,display_record,required,datatype,thumb_x_size,associated_table,associated_column,associated_local_key,link_first,link_last,modifiable FROM $currdesc order by sortkey,label";
   $r=$db->Execute($query);
   $rownr=0;
   // print all entries
   while (!($r->EOF) && $r) {
      $label = $r->fields['label'];
      $columnname = $r->fields['columnname'];
      $id = $r->fields['id'];
      $display_table = $r->fields['display_table'];
      $display_record = $r->fields['display_record'];
      $display_required= $r->fields['required'];
      $datatype = $r->fields['datatype'];
      $thumbsize=$r->fields['thumb_x_size'];
      $modifiable = $r->fields['modifiable'];
      $link_first = $r->fields['link_first'];
      $link_last = $r->fields['link_last'];
      $sort = $r->fields['sortkey'];
      $ass_table=false;
      $ass_column=false;
      if ($r->fields['associated_table']) {
         $ass_table=get_cell($db,'tableoftables','tablename','id',$r->fields['associated_table']);
         $ass_desc_table=get_cell($db,"tableoftables","table_desc_name","id",$r->fields["associated_table"]);
         $ass_column=get_cell($db,$ass_desc_table,"label","id",$r->fields["associated_column"]);
      }
      $show=1;
      foreach($noshow as $donotshow) {
	   if ($columnname==$donotshow)
              $show=0;
      } 	   
      // print start of row of selected group
      if ($show==1) {
         echo "<input type='hidden' name='column_id_$id' value='$id'>\n";
         echo "<input type='hidden' name='column_datatype_$id' value='$datatype'>\n";
  	      if ($rownr % 2) 
	         echo "<tr class='row_odd' align='center'>\n";	
  	      else 
	         echo "<tr class='row_even' align='center'>\n";         
 	      echo "<input type='hidden' name='column_name_$id' value='$columnname'>\n";
         echo "<td>$columnname</td>\n";  
         echo "<td><input type='text' name='column_label_$id' value='$label' size='10' onchange='tellServer(\"$dbstring\", $id, this.name, this.value)'></td>\n";
         echo "<td><input type='text' name='column_sort_$id' value='$sort' size='5' onchange='tellServer(\"$dbstring\",$id, this.name, this.value)'></td>\n";
         if ($display_table=='Y') {
            echo "<td><input type='radio' name='column_dtable_$id' value='Y' CHECKED onclick='tellServer(\"$dbstring\",$id, this.name, this.value)'>yes";
            echo "<input type='radio' name='column_dtable_$id' value='N' onclick='tellServer(\"$dbstring\",$id, this.name, this.value)'>no</td>\n";
	      } else {
            echo "<td><input type='radio' name='column_dtable_$id' value='Y' onclick='tellServer(\"$dbstring\",$id, this.name, this.value)'>yes";
            echo" <input type='radio' name='column_dtable_$id' value='N' CHECKED onclick='tellServer(\"$dbstring\",$id, this.name, this.value)'>no</td>";
         }
         if ($display_record=='Y') {
            echo "<td><input type='radio' name='column_drecord_$id' value='Y' onclick='tellServer(\"$dbstring\",$id, this.name, this.value)' CHECKED>yes";
            echo" <input type='radio' name='column_drecord_$id' value='N' onclick='tellServer(\"$dbstring\",$id, this.name, this.value)'> no </td>\n";
         } else {
            echo "<td><input type='radio' name='column_drecord_$id' value='Y' onclick='tellServer(\"$dbstring\",$id, this.name, this.value)'>yes";
            echo" <input type='radio' name='column_drecord_$id' checked value='N' onclick='tellServer(\"$dbstring\",$id, this.name, this.value)'> no </td>\n";
         }
	  	
         if($display_required=='Y') {
            echo "<td><input type='radio' name='column_required_$id' value='Y' onclick='tellServer(\"$dbstring\",$id, this.name, this.value)' CHECKED>yes";
            echo" <input type='radio' name='column_required_$id' value='N' onclick='tellServer(\"$dbstring\",$id, this.name, this.value)'> no </td>\n";
        } else {
            echo "<td><input type='radio' name='column_required_$id' value='Y' onclick='tellServer(\"$dbstring\",$id, this.name, this.value)'>yes";
            echo" <input type='radio' name='column_required_$id' checked value='N' onclick='tellServer(\"$dbstring\",$id, this.name, this.value)'> no </td>\n";
        }
	  	 		
        if (in_array($columnname,$nomodifiable))
           echo "<td>no</td>\n";
        elseif($modifiable=='Y') {
           echo "<td><input type='radio' name='column_modifiable_$id' value='Y' onclick='tellServer(\"$dbstring\",$id, this.name, this.value)' CHECKED>yes";
           echo" <input type='radio' name='column_modifiable_$id' value='N' onclick='tellServer(\"$dbstring\",$id, this.name, this.value)'> no </td>\n";
        }
        else {
           echo "<td><input type='radio' name='column_modifiable_$id' value='Y' onclick='tellServer(\"$dbstring\",$id, this.name, this.value)'>yes";
           echo" <input type='radio' name='column_modifiable_$id' checked value='N' onclick='tellServer(\"$dbstring\",$id, this.name, this.value)'> no </td>\n";
        }
	  	 		
        echo "<input type='hidden' name='column_datatype_$id' value='$label'>\n";
        echo "<td>$datatype</td>\n";
        if ($ass_table || $ass_column) {
           echo "<td>";
           if (! $r->fields['associated_local_key']) {
              echo "<b>primary key</b><br>";
              echo "$ass_table<br>$ass_column</td>\n";
           }
           else {
              $ass_local_column=get_cell($db,$currdesc,'label','id',$r->fields['associated_local_key']);
              echo "primary key: $ass_local_column<br>\n"; 
              echo "$ass_column</td>\n";
           }
        }
        else
           echo "<td>&nbsp;</td>\n";
        if ($link_first)
           echo "<td>$link_first &nbsp;<i>content</i>&nbsp; $link_last</td>\n";
        else
           echo "<td>&nbsp;</td>\n";
        $modstring = "<input type='submit' name='modcolumn"."_$id' value='Modify'>\n";
        if ($datatype=="image") {
           $alinkstring = "<input type='hidden' name='thumbsize"."_$id' value='$thumbsize'>\n";
           $alinkstring.="<input type='submit' name='modcolumn"."_$id' value='Thumbnail size' Onclick='var temp=window.prompt(\"Please enter the maximum thumbnail size (in pixels):\",\"$thumbsize\");if (temp) {document.tableform.thumbsize"."_$id.value=temp} else {return false}; return true;' >\n";
        } else
	        $alinkstring = "<input type='submit' name='alinkcolumn"."_$id' value='Active Link'>\n";
        $delstring = "<input type='submit' name='delcolumn"."_$id' value='Remove' ";
        $delstring .= "Onclick=\"if(confirm('Are you absolutely sure that the column $label should be removed? (No undo possible!)')){return true;}return false;\">";  

   	  echo "<td align='center'>";
        // the "modify button is only needed when javascript does not work
        if (! $_SESSION['javascript_enabled']) {
           echo $modstring;
        }
        echo $alinkstring;
   	  $candel=1;
   	  foreach($nodel as $checkme){
           if ($columnname==$checkme){
                $candel=0;
            }
         }
         if ($candel==1 && ($permissions & $SUPER) )
            echo "$delstring</td>\n";
         echo "</tr>\n";
      }
      $r->MoveNext();
      $rownr+=1;		
   }

   echo "</table></form>\n";
   printfooter($db,$USER);
   exit;
}

/**
 */
elseif (!empty($editreport) && $editreport)	{
   navbar($USER["permissions"]);
   echo $tplmessage;

   $r=$db->Execute("SELECT id,table_desc_name,label FROM tableoftables WHERE tablename='$editreport'");
   $tableid=$r->fields["id"];
   $tablelabel=$r->fields["label"];
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
      echo "<td>$modstring &nbsp;\n";
      echo "$delstring &nbsp;\n$exportstring &nbsp;\n$teststring</td>\n";
      echo "</tr>\n";
      $rp->MoveNext();
      $rownr++;
   }
   echo "</table>\n";
   printfooter();
   exit;
}

navbar($USER['permissions']);
echo "<h3 align='center'>$string</h3>";
echo "<h3 align='center'>Edit Tables</h3>\n";
echo "<form method='post' id='tablemanage' enctype='multipart/form-data' ";
$dbstring=$PHP_SELF;echo "action='$dbstring?".SID."'>\n"; 
echo "<table align='center' border='0' cellpadding='2' cellspacing='0'>\n";

echo "<tr>\n";
echo "<th>Table Name</th>\n";
echo "<th>Name in linkbar</th>\n";
echo "<th>Display</th>\n";
echo "<th>Groups</th>\n";
echo "<th>Sort key</th>\n";
echo "<th>Plugin code</th>\n";
echo "<th>Action</th>\n";
echo "<th>Fields</th>\n";
echo "<th>Reports</th>\n";

echo "</tr>\n";
echo "<tr><td><input type='text' name='newtable_name' value='' ></td>\n";
echo "<td><input type='text' name='newtable_label' value=''></td>\n";
echo "<td></td>\n";
echo "<td></td>\n";
echo "<td><input type='text' name='newtable_sortkey' value='' size=6></td>\n";
echo "<td><input type='text' name='newtable_plugincode' value=''></td>\n";
echo "<td align='center'><input type='submit' name='addtable' value='New'></td>\n";
echo "<td></td>\n<td></td>\n</tr>\n";
 
$query = "SELECT id,tablename,label,display,sortkey,plugin_code FROM tableoftables where display='Y' or display='N' ORDER BY sortkey, label";
$r=$db->Execute($query);

// query for group select boxes
$rg=$db->Execute("SELECT name,id from groups");
$rownr=0;

// print all entries
while (!($r->EOF) && $r) {
   // get results of each row
   $id = $r->fields['id'];
   $name = $r->fields['tablename'];
   $label = $r->fields['label'];
   $Display = $r->fields['display'];
   $sortkey = $r->fields['sortkey'];
   $plugincode=$r->fields['plugin_code'];
   
   // print start of row of selected group
   if ($rownr % 2) 
      echo "<tr class='row_odd' align='center'>\n";
   else 
      echo "<tr class='row_even' align='center'>\n";
         
   echo "<input type='hidden' name='table_id[]' value='$id'>\n";
   echo "<input type='hidden' name='table_name[]' value='$name'>\n";
   echo "<td><b>$name</b></td>";
   echo "<td><input type='text' name='table_label[]' value='$label'></td>\n";
   if($Display=="Y")
      echo "<td><input type='radio' checked value='Y' name='table_display[$rownr]'>yes<input type='radio' value='N' name='table_display[$rownr]'>no</td>\n";
   else
      echo "<td><input type='radio' value='Y' name='table_display[$rownr]'>yes<input type='radio' checked value='N' name='table_display[$rownr]'>no</td>\n";
   $rgs=$db->Execute("SELECT groupid FROM groupxtable_display WHERE tableid='$id'");
   while ($rgs && !$rgs->EOF) {
      $groups_table[]=$rgs->fields['groupid'];
      $rgs->MoveNext();
   }
   echo "<td>".$rg->GetMenu2("tablexgroups[$id][]",$groups_table,true,true,3)."</td>\n";
   $rg->MoveFirst();
   unset($groups_table);
   echo "<td><input type='text' name='table_sortkey[]' value='$sortkey' size=6></td>\n";
   if ($Custom=="")
      echo "<td><input type='text' name='table_plugincode[]' value='$plugincode'></td>\n";
   else
      echo "<td>&nbsp;<input type='hidden' name='table_plugincode[]' value=''></td>\n";
   $modstring = "<input type='submit' name='modtable"."_$rownr' value='Modify'>";
   $delstring = "<input type='submit' name='deltable"."_$rownr' value='Remove' ";
   $delstring .= "Onclick=\"if(confirm('Are you absolutely sure the table $name should be removed? (No Undo possible!)')){return true;}return false;\">";  
   if (! ($permissions & $SUPER))
      $delstring = "";
   if ($Custom=="") {
      echo "<td align='center'>$modstring $delstring</td>\n";
      echo "<td><a href='$PHP_SELF?editfield=$name&'>Edit Fields</td></a>";
      echo "<td><a href='editreports.php?tablename=$name'>Edit Reports</td></a>";
   }
   else
      echo "<td align='center'>$modstring</td><td></td>";

   echo "</tr>\n";
   $r->MoveNext();
   $rownr+=1;
}

echo "</table>\n";
   
printfooter($db,$USER);
?>
