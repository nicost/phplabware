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


require("include.php");
require("includes/db_inc.php");
require("includes/general_inc.php");
require("includes/tablemanage_inc.php");
include ('includes/defines_inc.php');

$editfield=$HTTP_GET_VARS["editfield"];
$post_vars="newtable_name,newtable_label,newtable_sortkey,newtable_plugincode,addtable,table_id,table_name,table_display,addcol_name,addcol_label,addcol_sort,addcol_dtable,addcol_drecord,addcol_required,addcol_modifiable,addcol_datatype";
globalize_vars($post_vars, $HTTP_POST_VARS);

$permissions=$USER["permissions"];
if ($addcol_datatype=="table") 
   $jsfile="includes/js/tablemanage.js";
printheader($httptitle,false,$jsfile);

if (!($permissions & $SUPER)) {
	navbar($USER["permissions"]);
	echo "<h3 align='center'><b>Sorry, this page is not for you</B></h3>";
	printfooter($db,$USER);
	exit;
}
 
while((list($key, $val) = each($HTTP_POST_VARS))) {
   if ($key == "addtable") {
      add_table($db,$newtable_name,$newtable_label,$newtable_sortkey,$newtable_plugincode);
      break;
   }
   elseif (substr($key, 0, 8) == "modtable") {
      $modarray = explode("_", $key);
      $id=$HTTP_POST_VARS["table_id"][$modarray[1]];
      mod_table($db,$id,$modarray[1]);
      break;
   }
   elseif (substr($key, 0, 8) == "deltable") {  
      $modarray = explode("_", $key);      
      $id=$HTTP_POST_VARS["table_id"][$modarray[1]]; 
      $tablename=$HTTP_POST_VARS["table_name"][$modarray[1]];      
      del_table($db,$tablename,$id,$USER);   
      break;
   }
   elseif ($key=="table_column_select") {
      add_associated_table($db,$table_name,$addcol_name,$HTTP_POST_VARS["table_select"],$HTTP_POST_VARS["table_column_select"]);
      break;
   }
   elseif ($key=="link_part_a") {
      add_active_link($db,$table_name,$addcol_name,$HTTP_POST_VARS["link_part_a"],$HTTP_POST_VARS["link_part_b"]);
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
   elseif (substr($key, 0, 9) == "modcolumn") {  
      $modarray = explode("_", $key);
      $tablename=$HTTP_POST_VARS["table_name"];
      $id=$HTTP_POST_VARS["column_id"][$modarray[1]]; 
      $colname=$HTTP_POST_VARS["column_name"][$modarray[1]];
      $collabel=$HTTP_POST_VARS["column_label"][$modarray[1]];
      $datatype=$HTTP_POST_VARS["column_datatype"][$modarray[1]];
      $Rdis=$HTTP_POST_VARS["column_drecord"][$modarray[1]];
      $Tdis=$HTTP_POST_VARS["column_dtable"][$modarray[1]];
      $sort=$HTTP_POST_VARS["column_sort"][$modarray[1]];
      $req=$HTTP_POST_VARS["column_required"][$modarray[1]];
      $modif=$HTTP_POST_VARS["column_modifiable"][$modarray[1]];
      mod_columnECG($db,$id,$sort,$tablename,$colname,$collabel,$datatype,$Rdis,$Tdis,$req,$modif);
      break;
   }   	
   elseif (substr($key, 0, 9) == "delcolumn") { 
      $modarray = explode("_", $key);
      $tablename=$HTTP_POST_VARS["table_name"];
      $id=$HTTP_POST_VARS["column_id"][$modarray[1]]; 
      $colname=$HTTP_POST_VARS["column_name"][$modarray[1]];
      $datatype=$HTTP_POST_VARS["column_datatype"][$modarray[1]];
      rm_columnecg($db,$tablename,$id,$colname,$datatype);
      break;
   }
   elseif (substr($key, 0, 11) == "alinkcolumn") { 
      $modarray = explode("_", $key);
      $tablename=$HTTP_POST_VARS["table_name"];
      $id=$HTTP_POST_VARS["column_id"][$modarray[1]]; 
      $colname=$HTTP_POST_VARS["column_name"][$modarray[1]];
      $collabel=$HTTP_POST_VARS["column_label"][$modarray[1]];
      $datatype=$HTTP_POST_VARS["column_datatype"][$modarray[1]];
      $table_desc=get_cell($db,"tableoftables","table_desc_name","tablename",$tablename);
      $link_a=get_cell($db,$table_desc,"link_first","id",$id);
      $link_b=get_cell($db,$table_desc,"link_last","id",$id);
      navbar($USER["permissions"]);
      show_active_link_page($db,$tablename,$colname,$collabel,$link_a,$link_b);
//      rm_columnecg($db,$tablename,$id,$colname,$datatype);
      printfooter();
      exit();
      break;
   }
}

if ($editfield)	{
   $noshow=array("id","access","ownerid","magic","lastmoddate","lastmodby","date");
   $nodel=array("title","date","lastmodby","lastmoddate");
   navbar($USER["permissions"]);

   $r=$db->Execute("SELECT id,table_desc_name,label FROM tableoftables WHERE tablename='$editfield'");
   $id=$r->fields["id"];
   $currdesc=$r->fields["table_desc_name"];
   $tablelabel=$r->fields["label"];
   echo "<h3 align='center'>$string</h3>";
   echo "<h3 align='center'>Edit columns of table <i>$tablelabel</i></h3><br>";

   echo "<form method='post' id='coledit' enctype='multipart/form-data' ";
   $dbstring=$PHP_SELF;echo "action='$dbstring?editfield=$editfield&".SID."'>\n"; 
   echo "<table align='center' border='0' cellpadding='2' cellspacing='0'>\n";
   echo "<tr>\n";
   echo "<th>(SQL) Column Name</th>";
   echo "<th>Label</th>";
   echo "<th>Sortkey</th>";
   echo "<th>Table display</th>";
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
   echo "<option value='table'>table</option>\n";
   echo "<option value='pulldown'>pulldown</option>\n";
   echo "<option value='link'>weblink</option>\n";
   echo "<option value='file'>file</option>\n";
   echo "</select></td>\n";
   echo "<td>&nbsp;</td>\n";
   echo "<td>&nbsp;</td>\n";
   echo "<td align='center'><input type='submit' name='addcolumn' value='Add'></td></tr>\n\n";
   
   $query = "SELECT id,sortkey,columnname,label,display_table,display_record,required,datatype,associated_table,associated_column,associated_local_key,link_first,modifiable FROM $currdesc order by sortkey,label";
   $r=$db->Execute($query);
   $rownr=0;
   // print all entries
   while (!($r->EOF) && $r) {
      $label = $r->fields["label"];
      $columnname = $r->fields["columnname"];
      $id = $r->fields["id"];
      $display_table = $r->fields["display_table"];
      $display_record = $r->fields["display_record"];
      $display_required= $r->fields["required"];
      $datatype = $r->fields["datatype"];
      $modifiable = $r->fields["modifiable"];
      $link_first = $r->fields["link_first"];
      $sort = $r->fields["sortkey"];
      unset ($ass_table);
      unset($ass_column);
      if ($r->fields["associated_table"]) {
         $ass_table=get_cell($db,"tableoftables","tablename","id",$r->fields["associated_table"]);
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
         echo "<input type='hidden' name='column_id[$rownr]' value='$id'>\n";
	 echo "<input type='hidden' name='column_datatype[$rownr]' value='$datatype'>\n";
  	 if ($rownr % 2) 
	     echo "<tr class='row_odd' align='center'>\n";	
  	 else 
	     echo "<tr class='row_even' align='center'>\n";         
 	 echo "<input type='hidden' name='column_name[$rownr]' value='$columnname'>\n";echo "<td>$columnname</td>\n";  
	 echo "<td><input type='text' name='column_label[$rownr]' value='$label' size='10'></td>\n";
	 echo "<td><input type='text' name='column_sort[$rownr]' value='$sort' size='5'></td>\n";
	 if($display_table=="Y") {
            echo "<td><input type='radio' name='column_dtable[$rownr]' value='Y' CHECKED >yes";
	    echo "<input type='radio' name='column_dtable[$rownr]' value='N'>no</td>\n";
	 }
         else {
            echo "<td><input type='radio' name='column_dtable[$rownr]' value='Y'>yes";
            echo" <input type='radio' name='column_dtable[$rownr]' value='N' CHECKED >no</td>";
         }
         if($display_record=="Y") {
            echo "<td><input type='radio' name='column_drecord[$rownr]' value='Y' CHECKED>yes";
            echo" <input type='radio' name='column_drecord[$rownr]' value='N'> no </td>\n";
         }
         else {
            echo "<td><input type='radio' name='column_drecord[$rownr]' value='Y'>yes";
            echo" <input type='radio' name='column_drecord[$rownr]' checked value='N'> no </td>\n";
         }
	  	
         if($display_required=="Y") {
            echo "<td><input type='radio' name='column_required[$rownr]' value='Y' CHECKED>yes";
            echo" <input type='radio' name='column_required[$rownr]' value='N'> no </td>\n";
         }
         else {
            echo "<td><input type='radio' name='column_required[$rownr]' value='Y'>yes";
            echo" <input type='radio' name='column_required[$rownr]' checked value='N'> no </td>\n";
         }
	  		 		
         if($modifiable=="Y") {
            echo "<td><input type='radio' name='column_modifiable[$rownr]' value='Y' CHECKED>yes";
            echo" <input type='radio' name='column_modifiable[$rownr]' value='N'> no </td>\n";
         }
         else {
            echo "<td><input type='radio' name='column_modifiable[$rownr]' value='Y'>yes";
            echo" <input type='radio' name='column_modifiable[$rownr]' checked value='N'> no </td>\n";
         }
	  		 		
      	 echo "<input type='hidden' name='column_datatype[]' value='$label'>\n";
         echo "<td>$datatype</td>\n";
         if ($ass_table || $ass_column) {
            echo "<td>";
            if (! $r->fields["associated_local_key"])
               echo "<b>primary key</b><br>";
            echo "$ass_table<br>$ass_column</td>\n";
         }
         else
            echo "<td>&nbsp;</td>\n";
         if ($link_first)
            echo "<td>Y</td>\n";
         else
            echo "<td>N</td>\n";
	 $modstring = "<input type='submit' name='modcolumn"."_$rownr' value='Modify'>";
	 $alinkstring = "<input type='submit' name='alinkcolumn"."_$rownr' value='Active Link'>";
         $delstring = "<input type='submit' name='delcolumn"."_$rownr' value='Remove' ";
         $delstring .= "Onclick=\"if(confirm('Are you absolutely sure that the column $label should be removed? (No undo possible!)')){return true;}return false;\">";  
   	 echo "<td align='center'>$modstring".$alinkstring;
   	 $candel=1;
   	 foreach($nodel as $checkme){if ($label==$checkme){$candel=0;}}
            if ($candel==1)
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

navbar($USER["permissions"]);
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
echo "<th>Custom</th>\n";
echo "<th>Action</th>\n";
echo "<th>Fields</th>\n";

echo "</tr>\n";
echo "<tr><td><input type='text' name='newtable_name' value='' ></td>\n";
echo "<td><input type='text' name='newtable_label' value=''></td>\n";
echo "<td></td>\n";
echo "<td></td>\n";
echo "<td><input type='text' name='newtable_sortkey' value='' size=6></td>\n";
echo "<td><input type='text' name='newtable_plugincode' value=''></td>\n";
echo "<td></td>\n";
echo "<td align='center'><input type='submit' name='addtable' value='Add'></td></tr>\n";
 
$query = "SELECT id,tablename,label,display,sortkey,custom,plugin_code FROM tableoftables where display='Y' or display='N' ORDER BY sortkey";
$r=$db->Execute($query);

// query for group select boxes
$rg=$db->Execute("SELECT name,id from groups");
$rownr=0;

// print all entries
while (!($r->EOF) && $r) {
   // get results of each row
   $id = $r->fields["id"];
   $name = $r->fields["tablename"];
   $label = $r->fields["label"];
   $Display = $r->fields["display"];
   $sortkey = $r->fields["sortkey"];
   $plugincode=$r->fields["plugin_code"];
   $Custom = $r->fields["custom"];
   
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
      $groups_table[]=$rgs->fields["groupid"];
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
   if ($Custom=="")
      echo "<td>Yes</td>\n";
   else
      echo "<td>Pre-Built</td>\n";
   $modstring = "<input type='submit' name='modtable"."_$rownr' value='Modify'>";
   $delstring = "<input type='submit' name='deltable"."_$rownr' value='Remove' ";
   $delstring .= "Onclick=\"if(confirm('Are you absolutely sure the table $name should be removed? (No Undo possible!)')){return true;}return false;\">";  
   if ($Custom=="") {
      echo "<td align='center'>$modstring $delstring</td>\n";
      echo "<td><a href='$PHP_SELF?editfield=$name&'>Edit Fields</td></a>";
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
