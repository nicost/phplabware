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
$post_vars="newtable_name,newtable_sortkey,addtable,table_id,table_name,table_display,addcol_name,addcol_sort,addcol_dtable,addcol_drecord,addcol_required,addcol_datatype";
globalize_vars($post_vars, $HTTP_POST_VARS);

$permissions=$USER["permissions"];
printheader($httptitle);

if (!($permissions & $SUPER)) {
	navbar($USER["permissions"]);
	echo "<h3 align='center'><b>Sorry, this page is not for you</B></h3>";
	printfooter($db,$USER);
	exit;
}
 
while((list($key, $val) = each($HTTP_POST_VARS))) {
   if (substr($key, 0, 8) == "addtable") {
       $tablename=$HTTP_POST_VARS["newtable_name"];
       $tablesort=$HTTP_POST_VARS["newtable_sortkey"];
       add_table($db,$tablename,$tablesort);
   }
   if (substr($key, 0, 8) == "modtable") {
       $modarray = explode("_", $key);
       $id=$HTTP_POST_VARS["table_id"][$modarray[1]];
       $tablename=$HTTP_POST_VARS["table_name"][$modarray[1]];
       $tablesort=$HTTP_POST_VARS["table_sortkey"][$modarray[1]];
       $tabledisplay=$HTTP_POST_VARS["table_display"][$modarray[1]];
       $tablegroups=$HTTP_POST_VARS["tablexgroups"][$id];
       mod_table($db,$id,$tablename,$tablesort,$tabledisplay,$tablegroups);
   }
   if (substr($key, 0, 8) == "deltable") {  
       $modarray = explode("_", $key);      
       $id=$HTTP_POST_VARS["table_id"][$modarray[1]]; 
       $tablename=$HTTP_POST_VARS["table_name"][$modarray[1]];      
  	del_table($db,$tablename,$id,$USER);   
   }
   if (substr($key, 0, 9) == "addcolumn") {  
       $tablename=$HTTP_POST_VARS["table_name"];
       $colname=$HTTP_POST_VARS["addcol_name"];
       $datatype=$HTTP_POST_VARS["addcol_datatype"];
       $Rdis=$HTTP_POST_VARS["addcol_drecord"];
       $Tdis=$HTTP_POST_VARS["addcol_dtable"];
       $req=$HTTP_POST_VARS["addcol_required"];
       $sort=$HTTP_POST_VARS["addcol_sort"];
       add_columnECG($db,$tablename,$colname,$datatype,$Rdis,$Tdis,$req,$sort);
   }   	
   if (substr($key, 0, 9) == "modcolumn") {  
      $modarray = explode("_", $key);
      $tablename=$HTTP_POST_VARS["table_name"];
      $id=$HTTP_POST_VARS["column_id"][$modarray[1]]; 
      $colname=$HTTP_POST_VARS["column_name"][$modarray[1]];
      $datatype=$HTTP_POST_VARS["column_datatype"][$modarray[1]];
      $Rdis=$HTTP_POST_VARS["column_drecord"][$modarray[1]];
      $Tdis=$HTTP_POST_VARS["column_dtable"][$modarray[1]];
      $sort=$HTTP_POST_VARS["column_sort"][$modarray[1]];
      $req=$HTTP_POST_VARS["column_required"][$modarray[1]];
      mod_columnECG($db,$id,$sort,$tablename,$colname,$datatype,$Rdis,$Tdis,$req);
   }   	
   if (substr($key, 0, 9) == "delcolumn") { 
      $modarray = explode("_", $key);
      $tablename=$HTTP_POST_VARS["table_name"];
      $id=$HTTP_POST_VARS["column_id"][$modarray[1]]; 
      $colname=$HTTP_POST_VARS["column_name"][$modarray[1]];
      $datatype=$HTTP_POST_VARS["column_datatype"][$modarray[1]];
      rm_columnecg($db,$tablename,$id,$colname,$datatype);
   }
}
if ($editfield)	{
	$noshow=array("id","access","ownerid","magic","lastmoddate","lastmodby","date");
	$nodel=array("title","date","lastmodby","lastmoddate");
	navbar($USER["permissions"]);
	$r=$db->Execute("SELECT id FROM tableoftables WHERE tablename='$editfield'");
	$id=$r->fields[0];
	$currdesc=$editfield."_".$id."_desc";
	echo "<h3 align='center'>$string</h3>";
	echo "<h3 align='center'>Edit columns of $editfield</h3><br>";
	echo "<table align='center'>\n";

	echo "<form method='post' id='coledit' enctype='multipart/form-data' ";
	$dbstring=$PHP_SELF;echo "action='$dbstring?editfield=$editfield&".SID."'>\n"; 
	echo "<table align='center'>\n";
	echo "<tr>\n";
	echo "<th>Label</th>";
	echo "<th>Sortkey</th>";
	echo "<th>Table display</th>";
	echo "<th>Record display</th>\n";
	echo "<th>Required </th>\n";
	echo "<th>Datatype</th>\n";
       echo "<th>Action</th>\n";
	echo "</tr>\n";
	echo "<input type='hidden' name='table_name' value='$editfield'>\n";
	echo "<tr align='center' ><td><input type='text' name='addcol_name' value=''></td>\n";
	echo "<td><input type='text' name='addcol_sort' value=''></td>\n";
	echo "<td><input type='radio' name='addcol_dtable' checked value='Y'>yes<input type='radio' name='addcol_dtable'  value='N'>no</td>\n";
	echo "<td><input type='radio' name='addcol_drecord' checked value='Y'>yes<input type='radio' name='addcol_drecord'  value='N'>no</td>\n";
	echo "<td><input type='radio' name='addcol_required'  value='Y'>yes<input type='radio' name='addcol_required' checked value='N'>no</td>\n";

	echo "<td><select name='addcol_datatype'>";
	echo "<option value='text'>text";
	echo "<option value='textlong'>textlong";
	echo "<option value='pulldown'>pulldown";
	echo "<option value='link'>weblink";
	echo "<option value='file'>file";
	echo "</select>";
	echo "<td align='center'><input type='submit' name='addcolumn' value='Add'></td></tr>\n";

	$query = "SELECT id,sortkey,label,display_table,display_record,required,datatype FROM $currdesc order by sortkey,label";
	$r=$db->Execute($query);
	$rownr=0;
	// print all entries
	while (!($r->EOF) && $r) 
		{
        $label = $r->fields["label"];
	$id = $r->fields["id"];
        $display_table = $r->fields["display_table"];
        $display_record = $r->fields["display_record"];
	$display_required= $r->fields["required"];
	$datatype = $r->fields["datatype"];
	$sort = $r->fields["sortkey"];
	$show=1;
	foreach($noshow as $doshow) {
	   if ($label==$doshow)
              $show=0;
	} 	   
        // print start of row of selected group
	if ($show==1) {
	   echo "<input type='hidden' name='column_id[]' value='$id'>\n";	    
	   echo "<input type='hidden' name='column_datatype[]' value='$datatype'>\n";
  	   if ($rownr % 2) 
	      echo "<tr class='row_odd' align='center'>\n";	
  	   else 
	      echo "<tr class='row_even' align='center'>\n";         
 	   echo "<input type='hidden' name='column_name[]' value='$label'>\n";echo "<td>$label</td>\n";  
	   echo "<td><input type='text' name='column_sort[]' value='$sort'></td>\n";
	   if($display_table=="Y"){
	  		echo "<td><input type='radio' name='column_dtable[$rownr]' value='Y' CHECKED >yes";
	  		echo "<input type='radio' name='column_dtable[$rownr]' value='N'>no</td>\n";}
	  	else{
	  		echo "<td><input type='radio' name='column_dtable[$rownr]' value='Y'>yes";
	  		echo" <input type='radio' name='column_dtable[$rownr]' value='N' CHECKED >no</td>";}
	  				
	  	if($display_record=="Y")
	  		{echo "<td><input type='radio' name='column_drecord[$rownr]' value='Y' CHECKED>yes";
	  		echo" <input type='radio' name='column_drecord[$rownr]' value='N'> no </td>\n";}
	  	else{
	  		echo "<td><input type='radio' name='column_drecord[$rownr]' value='Y'>yes";
	  		echo" <input type='radio' name='column_drecord[$rownr]' checked value='N'> no </td>\n";}
	  	
	  	if($display_required=="Y")
	  		{echo "<td><input type='radio' name='column_required[$rownr]' value='Y' CHECKED>yes";
	  		echo" <input type='radio' name='column_required[$rownr]' value='N'> no </td>\n";}
	  	else{
	  		echo "<td><input type='radio' name='column_required[$rownr]' value='Y'>yes";
	  		echo" <input type='radio' name='column_required[$rownr]' checked value='N'> no </td>\n";}	  			 
	  		 		
      	echo "<input type='hidden' name='column_datatype[]' value='$label'>\n";echo "<td>$datatype</td>\n";
	  	$modstring = "<input type='submit' name='modcolumn"."_$rownr' value='Modify'>";
   	   $delstring = "<input type='submit' name='delcolumn"."_$rownr' value='Remove' ";
   	   $delstring .= "Onclick=\"if(confirm('Are you absolutely sure that the column $label should be removed? (No undo possible!)')){return true;}return false;\">";  
   	   echo "<td align='center'>$modstring";
   	   $candel=1;
   	   foreach($nodel as $checkme){if ($label==$checkme){$candel=0;}}
 		if ($candel==1){echo "$delstring</td>\n";}		
 	 	echo "</tr>\n";
   		}
   	else 
   		{
	   	echo "<input type='hidden' name='column_id[]' value='$id'>\n";	
	   	echo "<input type='hidden' name='column_sort[]' value='$sort'>\n";	
	   	echo "<input type='hidden' name='column_name[]' value='$label'>\n";
		   if($display_table=="Y"){echo "<input type='hidden' name='column_dtable[]'' value=$display_table>";}
		   else{echo "<td><input type='hidden' value='Y' name='column_dtable[]'>\n";}
		   if($display_record=="Y"){echo "<input type='hidden' name='column_drecord[]'' value=$display_record  >\n";}
		   else{echo "<input type='hidden' value='Y' name='column_drecord[]'></td>\n";}
	       echo "<input type='hidden' name='column_datatype[]' value='$datatype'>\n";
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
echo "<table align='center'>\n";

echo "<tr>\n";
echo "<th>Table Name</th>\n";
echo "<th>Display</th>\n";
echo "<th>Groups</th>\n";
echo "<th>Sort key</th>\n";
echo "<th>Custom</th>\n";
echo "<th>Action</th>\n";
echo "<th>Fields</th>\n";

echo "</tr>\n";
echo "<tr><td><input type='text' name='newtable_name' value=''></td>\n";
echo "<td></td>\n";
echo "<td></td>\n";
echo "<td><input type='text' name='newtable_sortkey' value=''></td>\n";
echo "<td></td>\n";
echo "<td align='center'><input type='submit' name='addtable' value='Add'></td></tr>\n";
 
$query = "SELECT id,tablename,display,sortkey,custom FROM tableoftables where display='Y' or display='N' ORDER BY sortkey";
$r=$db->Execute($query);
// query for group select boxes
$rg=$db->Execute("SELECT name,id from groups");
$rownr=0;

// print all entries
while (!($r->EOF) && $r) {
   // get results of each row
   $id = $r->fields["id"];
   $name = $r->fields["tablename"];
   $Display = $r->fields["display"];
   $sortkey = $r->fields["sortkey"];
   $Custom = $r->fields["custom"];
   
   // print start of row of selected group
   if ($rownr % 2) 
      echo "<tr class='row_odd' align='center'>\n";
   else 
      echo "<tr class='row_even' align='center'>\n";
         
   echo "<input type='hidden' name='table_id[]' value='$id'>\n";
   echo "<input type='hidden' name='table_name[]' value='$name'>\n";
   echo "<td><b>$name</b></td>";
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
   echo "<td><input type='text' name='table_sortkey[]' value='$sortkey'></td>\n";
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
