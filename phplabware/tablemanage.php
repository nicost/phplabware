<?php
require("include.php");
require("includes/db_inc.php");
require("includes/general_inc.php");
include ('includes/defines_inc.php');

$editfield=$HTTP_GET_VARS["editfield"];
$post_vars="newtable_name,newtable_sortkey,addtable,table_id,table_name,table_display,addcol_name,addcol_sort,addcol_dtable,addcol_drecord,addcol_required,addcol_datatype";
globalize_vars($post_vars, $HTTP_POST_VARS);

$permissions=$USER["permissions"];
printheader($httptitle);

if (!($permissions & $ADMIN))
	{
	navbar($USER["permissions"]);
	echo "<h3 align='center'><b>Sorry, this page is not for you</B></h3>";
	printfooter($db,$USER);
	exit;
	}
 
while((list($key, $val) = each($HTTP_POST_VARS))) {
	 if ($key== "linkdisplay"){
	 $linkch=$HTTP_POST_VARS["link_display"];
	 $r=$db->Execute("UPDATE  tableoftables SET Display = '$linkch' where tablename = 'linkbar'");
	 }	 
	 if (substr($key, 0, 7) == "addlink") {
	 $newlabel=$HTTP_POST_VARS["newlink_label"];
	 $newurl=$HTTP_POST_VARS["newlink_url"];
	 $newdis=$HTTP_POST_VARS["newlink_display"];
	 $newtarget=$HTTP_POST_VARS["newlink_target"];
	 $newsort=$HTTP_POST_VARS["newlink_sortkey"];
     $r=$db->Execute("Insert into linkbar(label,linkurl,sortkey,display,target) values('$newlabel','$newurl','$newsort','$newdis','$newtarget')");
     }
     if (substr($key, 0, 7) == "modlink") {
     $modarray = explode("_", $key);
     $newid=$HTTP_POST_VARS["link_id"][$modarray[1]];
	 $newlabel=$HTTP_POST_VARS["link_label"][$modarray[1]];
	 $newurl=$HTTP_POST_VARS["link_url"][$modarray[1]];
	 $newdis=$HTTP_POST_VARS["link_display"][$modarray[1]];
	 $newtarget=$HTTP_POST_VARS["link_target"][$modarray[1]];
	 $newsort=$HTTP_POST_VARS["link_sortkey"][$modarray[1]];
     $r=$db->Execute("UPDATE  linkbar SET label = '$newlabel',linkurl='$newurl',sortkey='$newsort',display='$newdis',target='$newtarget' where id = '$newid'");
     }
     if (substr($key, 0, 7) == "dellink") {
     $modarray = explode("_", $key);
     $newid=$HTTP_POST_VARS["link_id"][$modarray[1]];
	 $newlabel=$HTTP_POST_VARS["link_label"][$modarray[1]];
     $r=$db->Execute("Delete from linkbar where id='$newid' and label='$newlabel'");
     }     
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
      mod_table($db,$id,$tablename,$tablesort,$tabledisplay);
      }
   if (substr($key, 0, 8) == "deltable") {  
      $modarray = explode("_", $key);      
      $id=$HTTP_POST_VARS["table_id"][$modarray[1]]; 
      $tablename=$HTTP_POST_VARS["table_name"][$modarray[1]];      
  	del_table($db,$tablename,$id);   
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
if ($editfield)
	{
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

	$query = "SELECT id,sortkey,label,display_table,display_record,required,datatype FROM $currdesc order by sortkey";
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
	  foreach($noshow as $doshow)
	  	{if ($label==$doshow){$show=0;}} 	   
      // print start of row of selected group
	  if ($show==1)
	  	{	
	  	echo "<input type='hidden' name='column_id[]' value='$id'>\n";	    
	      echo "<input type='hidden' name='column_datatype[]' value='$datatype'>\n";
  	    if ($rownr % 2) echo "<tr class='row_odd' align='center'>\n";	
  	    else echo "<tr class='row_even' align='center'>\n";         
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
   	   $delstring .= "Onclick=\"if(confirm('Are you sure that the column $label should be removed?')){return true;}return false;\">";  
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
echo "<th>Table Name</th>";
echo "<th>Display</th>";
echo "<th>Sort key</th>";
echo "<th>Custom</th>\n";
echo "<th>Action</th>\n";
echo "<th>Fields</th>\n";

echo "</tr>\n";
echo "<tr><td><input type='text' name='newtable_name' value=''></td>\n";
echo "<td></td>\n";
echo "<td><input type='text' name='newtable_sortkey' value=''></td>\n";
echo "<td></td>\n";
echo "<td align='center'><input type='submit' name='addtable' value='Add'></td></tr>\n";
 
$query = "SELECT id,tablename,Display,sortkey,Custom FROM tableoftables where Display='Y' or Display='N' ORDER BY sortkey";
$r=$db->Execute($query);
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
   echo "<td><input type='text' name='table_sortkey[]' value='$sortkey'></td>\n";
   if ($Custom=="")
      echo "<td>Yes</td>\n";
   else
      echo "<td>Pre-Built</td>\n";
   $modstring = "<input type='submit' name='modtable"."_$rownr' value='Modify'>";
   $delstring = "<input type='submit' name='deltable"."_$rownr' value='Remove' ";
   $delstring .= "Onclick=\"if(confirm('Are you sure that the table $name should be removed?')){return true;}return false;\">";  
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
   
// user based links section
echo "<hr><table align='center'>\n";
$rr=$db->Execute("SELECT Display FROM tableoftables where tablename='linkbar'");
$linkdis=$rr->fields["display"];
echo "<h3 align='center'>Edit Linkbar</h3>\n";
echo "<tr><th>Linkbar Display</th>";
if($linkdis=="1")
	{echo "<td><input type='radio' checked value='1' name='link_display'>yes<input type='radio' value='0' name='link_display'>no</td>\n";}
else
{echo "<td><input type='radio' value='1' name='link_display'>yes<input type='radio' checked value='0' name='link_display'>no</td>\n";}	

echo "<td align='center'><input type='submit' name='linkdisplay' value='Modify'></td></tr>\n";
echo "</tr></table>";
	
echo "<table align='center'>\n";

if($linkdis=="1")
	{
	// add new link function	
	echo "<tr>\n";
	echo "<th>Link name</th>";
	echo "<th>Link URL</th>";
	echo "<th>Display</th>";
	echo "<th>Window</th>\n";
	echo "<th>Sortkey</th>\n";
	echo "<th> Action</th>\n";
	echo "</tr>\n";
	echo "<tr>";
	echo "<td><input type='text' name='newlink_label' value=''></td>\n";
	echo "<td><input type='text' name='newlink_url' value=''></td>\n";
	echo "<td><input type='radio' checked value='Y' name='newlink_display'>yes<input type='radio' value='N' name='newlink_display'>no</td>";
	echo "<td><input type='radio' checked value='S' name='newlink_target'>Same<input type='radio' value='N' name='newlink_target'>New</td>";
	echo "<td><input type='text' name='newlink_sortkey' value=''></td>\n";
	echo "<td align='center'><input type='submit' name='addlink' value='Add'></td></tr>\n";
 		
	// show existing links with modification functions
	$query = "SELECT id,label,linkurl,sortkey,Display,target FROM linkbar ORDER BY sortkey";
	$r=$db->Execute($query);
	$rownr=0;
	while (!($r->EOF) && $r) 
		{
		$Lid=$r->fields["id"];
		$Llabel=$r->fields["label"];
		$Lurl=$r->fields["linkurl"];
		$LDisplay=$r->fields["display"];
		$Lsortkey=$r->fields["sortkey"];
		$Ltarget=$r->fields["target"];

	// print start of row of selected group
      if ($rownr % 2) echo "<tr class='row_odd' align='center'>\n";
      else echo "<tr class='row_even' align='center'>\n";
         
      echo "<input type='hidden' name='link_id[]' value='$Lid'>\n";
      echo "<td><input type='text' name='link_label[]' value='$Llabel'></td>\n";
	  echo "<td><input type='text' name='link_url[]' value='$Lurl'></td>\n";	

	  if($LDisplay=="Y")
   	{echo "<td><input type='radio' checked value='Y' name='link_display[$rownr]'>yes<input type='radio' value='N' name='link_display[$rownr]'>no</td>\n";}
	  else
	   {echo "<td><input type='radio' value='Y' name='link_display[$rownr]'>yes<input type='radio' checked value='N' name='link_display[$rownr]'>no</td>\n";}

	  if($Ltarget=="S")
   	{echo "<td><input type='radio' checked value='S' name='link_target[$rownr]'>Same<input type='radio' value='N' name='link_target[$rownr]'>New</td>\n";}
	  else
	   {echo "<td><input type='radio' value='S' name='link_target[$rownr]'>Same<input type='radio' checked value='N' name='link_target[$rownr]'>New</td>\n";}
      echo "<td><input type='text' name='link_sortkey[]' value='$Lsortkey'></td>\n";
	  $modstring = "<input type='submit' name='modlink"."_$rownr' value='Modify'>";
      $delstring = "<input type='submit' name='dellink"."_$rownr' value='Remove' ";
      $delstring .= "Onclick=\"if(confirm('Are you sure that the link $Llabel should be removed?')){return true;}return false;\">";  

      echo "<td align='center'>$modstring $delstring</td>\n";		
	  $r->MoveNext();
      $rownr+=1;	
		}		
	}	   
	echo "</tr></table></form>\n";
printfooter($db,$USER);
?>
