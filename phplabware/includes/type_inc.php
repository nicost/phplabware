<?php

// type_inc.php -  List, modify, delete and add entries in 'type' tables
// type_inc.php - author: Nico Stuurman <nicost@soureforge.net>
  /***************************************************************************
  * This script displays a table with protocols in phplabware.               *
  *                                                                          *
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

function show_type ($db,$table,$name) {
   global $HTTP_POST_VARS,$PHP_SELF,$HTTP_GET_VARS, $DBNAME;

   $dbstring=$PHP_SELF;$dbstring.="?";
   if ($DBNAME){$dbstring.="dbname=$DBNAME&";} 
   $dbstring.="edit_type=$table&";
   if($HTTP_POST_VARS[type_name]) {$name=$HTTP_POST_VARS[type_name];}
   echo "<form method='post' id='typeform' enctype='multipart/form-data' ";
   echo "action='$dbstring".SID."'>\n"; 

   echo "<table align='center'>\n";
   echo "<center><h3>Edit $name</h3></center>\n";
   echo "<tr>\n";
   echo "<th>Name</th>";
   echo "<th>Shortname</th>";
   echo "<th>Sort key</th>\n";
   echo "<th>Action</th>\n";
   echo "</tr>\n";

   // Column with new record
   echo "<input type='hidden' name='type_name' value='$name'>\n";
   echo "<tr><td><input type='text' name='newtype_type' value=''></td>\n";
   echo "<td><input type='text' name='newtype_typeshort' value=''></td>\n";
   echo "<td><input type='text' name='newtype_sortkey' value=''></td>\n";
   echo "<td align='center'><input type='submit' name='addtype_$table' value='Add'></td></tr>\n";

   // retrieve records from database
   $query = "SELECT id,type,typeshort,sortkey FROM $table ORDER BY sortkey";
   
   $r=$db->Execute($query);
   $rownr=0;
   // print all entries
   while (!($r->EOF) && $r) {
 
      // get results of each row
      $id = $r->fields["id"];
      $type = $r->fields["type"];
      $typeshort = $r->fields["typeshort"];
      $sortkey = $r->fields["sortkey"];
 
      // print start of row of selected group
      if ($rownr % 2)
         echo "<tr class='row_odd' align='center'>\n";
      else
         echo "<tr class='row_even' align='center'>\n";
      echo "<input type='hidden' name='type_id[]' value='$id'>\n";
      echo "<input type='hidden' name='type_name' value='$name'>\n";
      echo "<td><input type='text' name='type_type[]' value='$type'></td>\n";
      echo "<td><input type='text' name='type_typeshort[]' value='$typeshort'></td>\n";
      echo "<td><input type='text' name='type_sortkey[]' value='$sortkey'></td>\n";
      $modstring = "<input type='submit' name='mdtype_$table"."_$rownr' value='Modify'>";
      $delstring = "<input type='submit' name='dltype_$table"."_$rownr' value='Remove' ";
      $delstring .= "Onclick=\"if(confirm('Are you sure the $name \'$type\' ";
      $delstring .= "should be removed?')){return true;}return false;\">";                                           
      echo "<td align='center'>$modstring $delstring</td>\n";
      echo "</tr>\n";
   
      $r->MoveNext();
      $rownr+=1;
   }

   // Back button
   echo "<tr><td colspan=4 align='center'>\n";
   echo "<input type='submit' name='submit' value='Back'>\n";
   echo "</td></tr>\n";

   echo "</table>\n";
   echo "</form>\n";

}

////
// !Deletes an entry in the type table
// Currently, there can be only 1 related table ($table2)
// When more are needed,make $table2 into an array 

function del_type ($db,$table,$index,$table2) {
   global $HTTP_POST_VARS, $DBNAME, $HTTP_GET_VARS, $DB_DESNAME;

   $id=$HTTP_POST_VARS["type_id"][$index]; 
   $string="";
   if ($DBNAME)
   	{   
	   $recordref=get_cell($db,$DB_DESNAME,label,associated_table,$table);
	   if ($id) 
	   	{
	      $r=$db->Execute("UPDATE $table2 SET $recordref='' WHERE $recordref='$id'");
	      if ($r) 
	         {$r=$db->Execute("DELETE FROM $table WHERE id=$id");}
	      if ($r){$string="<h3 align='center'>Record removed</h3>\n";}
	   	}
	   else  {$string="<h3 align='center'>Please enter all fields</h3>\n";}
	   }	   
	else 
		{
		 if ($id) {
      	$table_array=explode("_",$table);
      	$r=$db->Execute("UPDATE $table2 SET ".$table_array[1]."='' WHERE ".
                       $table_array[1]."=$id");
      	if ($r) // keep database structure intact
      	   $r=$db->Execute("DELETE FROM $table WHERE id=$id");
      	if ($r){$string="<h3 align='center'>Record removed</h3>\n";}
   		}
		else {$string="<h3 align='center'>Please enter all fields</h3>\n";}
     	}
     
	echo "$string";
   return false;
}

////
// !Modifies an entry in the type table
//
function mod_type ($db,$table,$index) {
   global $HTTP_POST_VARS;
   $id=$HTTP_POST_VARS["type_id"][$index]; 
   $type=$HTTP_POST_VARS["type_type"][$index]; 
   $typeshort=$HTTP_POST_VARS["type_typeshort"][$index]; 
   $sortkey=(int) $HTTP_POST_VARS["type_sortkey"][$index];
   if ($type && $typeshort && is_int($sortkey)) {
      $r=$db->Execute("UPDATE $table SET type='$type',typeshort='$typeshort',sortkey=$sortkey WHERE id=$id"); 
      if ($r) {
         echo "<h3 align='center'>Succesfully changed Record</h3>\n";
         return ($id);
      }
	 else echo "<h3 align='center'>Please enter all fields</h3>\n";
   }
   
   return false;
}

////
// !Adds a new entry in the type table
//
function add_type ($db,$table) {
   global $HTTP_POST_VARS;
   $id=$db->GenId($table."_id_seq");
   $type=$HTTP_POST_VARS["newtype_type"]; 
   $typeshort=$HTTP_POST_VARS["newtype_typeshort"]; 
   $sortkey=(int) $HTTP_POST_VARS["newtype_sortkey"];
   if ($type && $typeshort && is_int($sortkey)) {
       $r=$db->query("INSERT INTO $table (id,type,typeshort,sortkey) 
                      VALUES ($id,'$type','$typeshort',$sortkey)");
       if ($r)
         return ($id);
   }
   else
      echo "<h3 align='center'>Please enter all fields</h3>\n";
   return false;
}
?>
