<?php

// plugin_inc.php - skeleton file for plugin codes
// plugin_inc.php - author: Nico Stuurman

/* 
Copyright 2002, Nico Stuurman

This is a skeleton file to code your own plugins.
To use it, rename this file to something meaningfull,
add the path and name to this file (relative to the phplabware root)
in the column 'plugin_code' of 'tableoftables', and code away.  
And, when you make something nice, please send us a copy!

This program is free software: you can redistribute it and/ormodify it under
the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

*/


function plugin_display_add_post($db,$nowfield)
{
	global $title, $date, $USER, $system_settings; 
	
	$dateformat=get_cell($db,"dateformats","dateformat","id",$system_settings["dateformat"]);
	$date=(time()); $date=date($dateformat,$date);	
	echo "<input type='hidden' name='date_requested' value='$date'>\n";  

	$r=$db->Execute("SELECT firstname,lastname,email FROM users WHERE id=$USER");
	$myname=$USER["firstname"];$myname.=$USER["lastname"];
 	echo "<input type='hidden' name='ordered_by' value='$myname'>\n";


}



////
// !Extends function display_show
// modified to duplicatean entry for reordering
function plugin_display_show ($db,$Allfields,$id)
{
echo "<script language='JavaScript'><!--window.name='mainwin'; </script>";

   global $PHP_SELF, $db_type, $md, $USER, $tableinfo,$system_settings;
   $dbstring=$PHP_SELF;$dbstring.="?";$dbstring.="tablename=".$tableinfo->name."&";
   $j2="target=\"mainwin\" onSubmit=\"setTimeout('window.close()',2000)\"";
   echo "<form method='post' id='protocolform' enctype='multipart/form-data' $j2 action='$dbstring";
?><?=SID?>'><?php

   echo "<input type='hidden' name='md' value='$md'>\n";
   echo "<table border=0 align='center'>\n";   

/* initailize a blank array*/
$Allfields2=array();

/* Define variables to be cleared from the arrays*/
$badnames=array("date_ordered","confirmed");

/*variables to not be messed with, rather these are explicitly defined later or system set*/
$notouch=array("date","ordered_by","date_requested","ownerid","magic");

foreach ($Allfields as $nowfield2) 
	{
	$flag=1;
	foreach($badnames as $nolike)
		{if ($nowfield2[label] == $nolike){$flag=0;}}	
	foreach($notouch as $nolike2)
		{if ($nowfield2[label] == $nolike2){$flag=666;}}	
	if ($flag == 1)
		{echo "<input type='hidden' name='$nowfield2[name]' value='$nowfield2[values]'>\n";}
	elseif($flag == 0)
		{echo "<input type='hidden' name='$nowfield2[name]' value=''>\n";}	

}

/* set the magic variable*/
$magic=time(); echo "<input type='hidden' name='magic' value='$magic'>\n";

/* Put in the date requested, who requested it, etc*/
$dateformat=get_cell($db,"dateformats","dateformat","id",$system_settings["dateformat"]);
$date=(time()); $date=date($dateformat,$date);
$date.="-reorder";
echo "<input type='hidden' name='date_requested' value='$date'>\n";  


$r=$db->Execute("SELECT firstname,lastname,email FROM users WHERE id=$USER");
$myname=$USER["firstname"];$myname.=" "; $myname.=$USER["lastname"];
echo "<input type='hidden' name='ordered_by' value='$myname'>\n"; 



   echo "<th>Click this button to reorder</th>";
   $value="Add Record";

   echo "<td colspan=7 align='center'><input type='submit' name='submit' value='$value'>\n";
   echo "&nbsp;&nbsp;<input type='submit' name='submit' value='Cancel'></td>\n";
   echo "</tr>\n</table>\n</form>\n";

   //end of table
    $dbstring=$PHP_SELF;$dbstring.="?";$dbstring.="tablename=$tableinfo[name]&";
    echo "<form method='post' id='protocolform' enctype='multipart/form-data' action='$dbstring";
 ?><?=SID?>'><?php

}