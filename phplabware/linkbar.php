<?php

// linkbar.php - Manages links on the top most bar 
// linkbar.php - author: Ethan Garner, Nico Stuurman <nicost@soureforge.net>

  /***************************************************************************
  * This script manages the linkbar.                                         *
  *                                                                          *
  * Copyright (c) 2001 by Nico Stuurman                                      *
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


printheader($httptitle);

if (!($USER["permissions"] & $SUPER)) {
	navbar($USER["permissions"]);
	echo "<h3 align='center'><b>Sorry, this page is not for you</B></h3>";
	printfooter($db,$USER);
	exit;
}


while((list($key, $val) = each($HTTP_POST_VARS))) {
  if ($key== "linkdisplay"){
      $linkch=$HTTP_POST_VARS["link_display"];
      $r=$db->Execute("UPDATE tableoftables SET display = '$linkch' where tablename = 'linkbar'");
   }
   if (substr($key, 0, 7) == "addlink") {
      $newlabel=$HTTP_POST_VARS["newlink_label"];
      $newurl=$HTTP_POST_VARS["newlink_url"];
      $newdis=$HTTP_POST_VARS["newlink_display"];
      $newtarget=$HTTP_POST_VARS["newlink_target"];
      $newsort=$HTTP_POST_VARS["newlink_sortkey"];
      $linkbarid=$db->GenID("linkbar_id_seq");
      $r=$db->Execute("Insert into linkbar(id,label,linkurl,sortkey,display,target) values('$linkbarid','$newlabel','$newurl','$newsort','$newdis','$newtarget')");
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
}

navbar($USER["permissions"]);

// user based links section

echo "<form method='post' id='linkbarform' enctype='multipart/form-data' ";
$dbstring=$PHP_SELF;
echo "action='$dbstring?".SID."'>\n"; 

echo "<table align='center'>\n";
$rr=$db->Execute("SELECT display FROM tableoftables where tablename='linkbar'");
$linkdis=$rr->fields["display"];

echo "<h3 align='center'>Edit Linkbar</h3>\n";
echo "<tr><th>Linkbar Display</th>";
if($linkdis=="1")
   echo "<td><input type='radio' checked value='1' name='link_display'>yes<input type='radio' value='0' name='link_display'>no</td>\n";
else
   echo "<td><input type='radio' value='1' name='link_display'>yes<input type='radio' checked value='0' name='link_display'>no</td>\n";

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
	$query = "SELECT id,label,linkurl,sortkey,display,target FROM linkbar ORDER BY sortkey";
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

