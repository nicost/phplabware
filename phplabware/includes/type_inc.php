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

/**
 *  Generates a javascript array used to dynamically alter pulldown items
 *
 * after the pulldown list has been changed by the user in another window
 */
function update_opener_js ($db,$table) {
   global $_GET;

   $result="<script type='text/javascript'>\n<!--\n";
   $result.="typeinfo=new Array(\n";
   $result.=" new Array(\n";
   //leave first choice blank:
   $result.="   new Array(\"\",\"\")";
   $query = "SELECT id,typeshort FROM $table ORDER BY sortkey,typeshort";
   $r=$db->Execute($query);
   $rownr=0;
   // enter all entries into array
   while (!($r->EOF) && $r) {
      $result.=",\n   new Array(\"".$r->fields[1]."\",\"".$r->fields[0]."\")";
      $r->MoveNext();
   }
   $result.="\n )\n)\n";
   $form=$_GET['formname'];
   $select=$_GET['selectname'];
   $result.="fillSelectFromArray(opener.document.$form.$select,typeinfo[0])\n";
   $result.="// End of Javascript -->\n</script>\n";
   return $result;
}


/**
 *  Generates the page with info on pulldown items
 *
 * Allows for addition, modifying and deleting pulldown items
 */
function show_type ($db,$table,$name, $tablename=false) {
   global $_POST,$PHP_SELF,$_GET, $_SESSION;

   $dbstring=$PHP_SELF.'?';
   if ($tablename)
      $dbstring.="tablename=$tablename&"; 
   $dbstring.="edit_type=$table&";
   // propagate the form and select name as GET variables to be able to manipulate the select list with javascript
   $dbstring.='formname='.$_GET['formname'].'&';
   $dbstring.='selectname='.$_GET['selectname'].'&';
   if(array_key_exists('type_name', $_POST)) {
      $name=$_POST['type_name'];
   }
   echo "<form method='post' id='typeform' enctype='multipart/form-data' ";
   echo "action='$dbstring".SID."'>\n"; 
   echo "<input type='hidden' name='edit_type' value='$table'>\n";

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
   $query = "SELECT id,type,typeshort,sortkey FROM $table ORDER BY sortkey,type";
   
   $r=$db->Execute($query);
   $rownr=0;
   // print all entries
   while (!($r->EOF) && $r) {
 
      // get results of each row
      $id = $r->fields['id'];
      $type = $r->fields['type'];
      $typeshort = $r->fields['typeshort'];
      $sortkey = $r->fields['sortkey'];
 
      // print start of row of selected group
      if ($rownr % 2)
         echo "<tr class='row_odd' align='center'>\n";
      else
         echo "<tr class='row_even' align='center'>\n";
      echo "<input type='hidden' name='type_id_$id' value='$id'>\n";
      echo "<input type='hidden' name='type_name_$id' value='$name'>\n";
      // Javascript tellServer sends modification to the server directly
      echo "<td><input type='text' id='type_type_$id' name='type_type_$id' value='$type' onchange='tellServer(\"$dbstring".SID."\", $id, \"type\");'></td>\n";
      echo "<td><input type='text' id='type_typeshort_$id' name='type_typeshort_$id' value='$typeshort' onchange='tellServer(\"$dbstring".SID."\", $id, \"typeshort\");'></td>\n";
      echo "<td><input type='text' id='type_sortkey_$id' name='type_sortkey_$id' value='$sortkey' onchange='tellServer(\"$dbstring".SID."\", $id, \"sortkey\");'></td>\n";
      // When Javascript is on we do not need Modify buttons here:
      $modstring='';
      if (!$_SESSION['javascript_enabled']) {
         $modstring = "<input type='submit' name='mdtype"."_$id' value='Modify'>";
      }
      $delstring = "<input type='submit' name='dltype"."_$id' value='Remove' ";
      $delstring .= "Onclick=\"if(confirm('Are you sure the $name \'$type\' ";
      $delstring .= "should be removed?')){return true;}return false;\">";                                           
      echo "<td align='center'>$modstring $delstring</td>\n";
      echo "</tr>\n";
   
      $r->MoveNext();
      $rownr+=1;
   }

   echo "</form>\n";
   // Back button
   echo "<tr><td colspan=4 align='center'>\n";
   $dbstring=$PHP_SELF."?";
   if ($tablename)
      $dbstring.="tablename=$tablename&";
   echo "<form method='post' id='typeform' enctype='multipart/form-data' ";
   echo "action='$dbstring".SID."'>\n"; 
   echo "<input type='submit' name='submit' value='Close' onclick='self.close();window.opener.focus();'>\n";
   echo "</form>\n";
   echo "</td></tr>\n";

   echo "</table>\n";
   echo "</form>\n";

}

/**
 *  Deletes an entry in the type table
 *
 * Currently, there can be only 1 related table ($table2)
 * When more are needed,make $table2 into an array 
 */

function del_type ($db,$table,$id,$tableinfo) {
   global $_POST, $_GET;

   if (! $id == $_POST["type_id_$id"]) {
      echo "ERROR";
   }
   if ($tableinfo->realname) {
      $recordref=get_cell($db,$tableinfo->desname,'columnname','associated_table',$table);
      if ($id) {
         $r=$db->Execute("UPDATE $tableinfo->realname SET $recordref=NULL WHERE $recordref='$id'");
         if ($r) 
	    $r=$db->Execute("DELETE FROM $table WHERE id=$id");
	 if ($r) {
	    $string="<h3 align='center'>Record removed</h3>\n";
            echo update_opener_js ($db,$table);
         }
      }
      else  
         $string="<h3 align='center'>Please enter all fields</h3>\n";
   }	   
   else { 
      if ($id) {
         $table_array=explode('_',$table);
      	 $r=$db->Execute("UPDATE $tableinfo->realname SET ".$table_array[1]."='' WHERE ".
                       $table_array[1]."=$id");
      	 if ($r) // keep database structure intact
      	    $r=$db->Execute("DELETE FROM $table WHERE id=$id");
      	 if ($r) {
	    $string="<h3 align='center'>Record removed</h3>\n";
            echo update_opener_js ($db,$table);
         }
      }
      else 
         $string="<h3 align='center'>Please enter all fields</h3>\n";
   }
     
   echo "$string";
   return false;
}

/**
 *  Modifies an entry in the type table
 *
 *
 */
function mod_type ($db,$table,$id) {
   global $_POST;
   
   if (! $id == $_POST["type_id_$id"]) {
      echo "ERROR";
   }
   $type=$_POST['type_type_' . $id]; 
   $typeshort=$_POST['type_typeshort_' . $id]; 
   $sortkey=(int) $_POST['type_sortkey_' . $id];
   if ($type && $typeshort && is_int($sortkey)) {
      $r=$db->Execute("UPDATE $table SET type='$type',typeshort='$typeshort',sortkey=$sortkey WHERE id=$id"); 
      if ($r) {
         echo "<h3 align='center'>Succesfully changed Record</h3>\n";
         echo update_opener_js ($db,$table);
         return ($id);
      }
	 else echo "<h3 align='center'>Please enter all fields</h3>\n";
   }
   
   return false;
}

/**
 *  Adds a new entry in the type table
 *
 *
 */
function add_type ($db,$table) {
   global $_POST;

   $id=$db->GenId($table.'_id_seq');
   $type=$_POST['newtype_type']; 
   $typeshort=$_POST['newtype_typeshort']; 
   $sortkey=(int) $_POST['newtype_sortkey'];
   if ($type && $typeshort && is_int($sortkey)) {
       $r=$db->query("INSERT INTO $table (id,type,typeshort,sortkey) 
                      VALUES ($id,'$type','$typeshort',$sortkey)");
       if ($r) {
         echo update_opener_js ($db,$table);
         return ($id);
      }
   }
   else
      echo "<h3 align='center'>Please enter all fields</h3>\n";
   return false;
}
?>
