<?php
  
// antibodies.php -  List, modify, delete and add antibodies
// antibodies.php - author: Nico Stuurman <nicost@soureforge.net>
  /***************************************************************************
  * This script displays a table with antobdies in phplabware.               *
  *                                                                          *
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/                                             

// main include thingies
require("include.php");

allowonly($READ, $USER["permissions"]);

// main global vars
$title .= "Antibodies";
$fields="name,id,type1,type2,type3,type4,type5,species,antigen,epitope,concentration,buffer,notes,location,source,date";

// register variables
$get_vars = "id,";
globalize_vars ($get_vars,$HTTP_GET_VARS);
$post_vars = "add,submit,search,";
globalize_vars ($post_vars, $HTTP_POST_VARS);


/****************************FUNCTIONS***************************/

////
// !Inserts $fields with $fieldvalues into $table
// Returns the id of inserted record on succes, false otherwise.
// $fields is a comma separated list with all column names
// Fieldvalues must be an associative array containing all the $fields to be added.
// Fields named 'date' are automatically filled with a Unix timestamp
function add ($db,$table,$fields,$fieldvalues,$USER) {
   include('includes/defines_inc.php');
   if (!($USER["permissions"] & $WRITE) )
      return false;
   // generate the new ID
   $id=$db->GenID($table."_id_seq");
   if ($id) {
      $columns="id";
      $values="$id";
      $column=strtok($fields,",");
      while ($column) {
         if (!($column=="id")) {
            $columns.=",$column";
            if ($column=="date") {
               $date=(time());
               $values.=",$date";
            }
            else
	       $values.=",'$fieldvalues[$column]'";
         }
	 $column=strtok(",");
      }
      $query="INSERT INTO $table ($columns) VALUES ($values)";
      if ($db->Execute($query))
         return $id;
   }
}

////
// !Modifies $fields in $table with values $fieldvalues where id=$id
// Returns true on succes, false on failure
// Fieldvalues must be an associative array containing all the $fields to be added.
// If a field is not present in $fieldvalues, it will not be changed.  
// The entry 'id' in $fields will be ignored.
function modify ($db, $table,$fields,$fieldvalues,$id) {
   // check whether this is allowed

   $query="UPDATE $table SET ";
   $column=strtok($fields,",");
   while ($column) {
      $test=true;
      if (! ($column=="id" || $column=="date") ) 
         $query.="$column='$fieldvalues[$column]',";
      $column=strtok(",");
   }
   $query[strrpos($query,",")]=" ";
   if ($test) {
      $query.=" WHERE id='$id'";
      if ($db->Execute($query))
         return true;
   }
}


////
// !Deletes the etry with id=$id
// Returns true on succes, false on failure
// This is very generic, it is likely that you will need to do more cleanup
function delete ($db, $table, $id, $USER) {
   // check whether this user is allowed to do this


   // and now delete for real
   if ($db->Execute("DELETE FROM $table WHERE id=$id"))
      return true;
   else
      return false;
}


////
// !Checks input data.
// returns false if something can not be fixed
function check_ab_data ($field_values) {
   if (!$field_values["name"]) {
      echo "<h3>Please enter an antibody name.</h3>";
      return false;
   }
   if ($field_values["concentration"])
      if (! @settype ($field_values["concentration"],"double")) {
         echo "<h3>Use numbers only for the concentration field.</h3>";
         return false;
      }
   return true;
}


////
// !Prints a form with antibody stuff
// $id=0 for a new entry, otherwise it is the id
function add_ab_form ($db, $fields,$field_values,$id) {
   // get values in a smart way
   $column=strtok($fields,",");
   while ($column) {
      ${$column}=$field_values[$column];
      $column=strtok(",");
   }

   echo "<form method='post' id='antibodyform action='$PHP_SELF'>\n"; 
   echo "<table border=0 align='center'>\n";
   if ($id) {
      echo "<tr><td colspan=7 align='center'><h3>Modify Antibody <i>$name</i></h3></td></tr>\n";
      echo "<input type='hidden' name='id' value='$id'>\n";
   }
   else
      echo "<tr><td colspan=7 align='center'><h3>New Antibody</h3></td></tr>\n";
   echo "<tr align='center'>\n";
   echo "<td colspan=2></td>\n";
   echo "<th>Primary/Secund.</th>\n<th>Label</th>\n<th>Mono-/Polyclonal</th>\n";
   echo "<th>Host</th>\n<th>Class</th>\n";
   echo "</tr>\n";
   echo "<tr>\n";
   echo "<th>Name: <sup style='color:red'>&nbsp;*</sup></th>\n";
   echo "<td><input type='text' name='name' value='$name'></td>\n";
   $r=$db->Execute("SELECT type,id FROM ab_type1");
   $text=$r->GetMenu2("type1",$type1,false);
   echo "<td>$text</td>\n";
   
   $r=$db->Execute("SELECT type,id FROM ab_type5 ORDER BY sortkey");
   $text=$r->GetMenu2("type5",$type5,false);
   echo "<td>$text</td>\n";
   
   $r=$db->Execute("SELECT type,id FROM ab_type2");
   $text=$r->GetMenu2("type2",$type2,false);
   echo "<td>$text</td>\n";

   $r=$db->Execute("SELECT type,id FROM ab_type3");
   $text=$r->GetMenu2("type3",$type3,false);
   echo "<td>$text</td>\n";

   $r=$db->Execute("SELECT type,id FROM ab_type4 ORDER BY sortkey");
   $text=$r->GetMenu2("type4",$type4,false);
   echo "<td>$text</td>\n";

   echo "</tr>\n";

   echo "<tr>\n";
   echo "<th>Antigen: </th><td><input type='text' name='antigen' value='$antigen'></td>\n";
   echo "<td>&nbsp;</td>";
   echo "<th>Epitope: </th><td colspan=3><input type='text' name='epitope' value='$epitope'></td>\n";
   echo "</tr>\n";
   
   echo "<tr>";
   echo "<th>Buffer: </th><td><input type='text' name='buffer' value='$buffer'></td>\n";
   echo "<td>&nbsp;</td>";
   echo "<th>Concentration (mg/ml): </th><td colspan=3><input type='text' name='concentration' value='$concentration'></td>\n";
   echo "</tr>\n";
   
   echo "<tr>";
   echo "<th>Source: </th><td><input type='text' name='source' value='$source'></td>\n";
   echo "<td>&nbsp;</td>";
   echo "<th>Location: </th><td colspan=3><input type='text' name='location' value='$location'></td>\n";
   echo "</tr>\n";
   
   echo "<tr>";
   echo "<th>Notes: </th><td colspan=6><textarea name='notes' rows='5' cols='100%'>$notes</textarea></td>\n";
   echo "</tr>\n";
   
   echo "<tr>";
   if ($id)
      $value="Modify Antibody";
   else
      $value="Add Antibody";
   echo "<td colspan=7 align='center'><input type='submit' name='submit' value='$value'></td>\n";
   echo "</tr>\n";
   

   echo "</table></form>\n";
}

////
// !Shows a page with nice information on the antibody
function show_ab ($id) {
}

/*****************************BODY*******************************/

printheader($title);
navbar($USER["permissions"]);

// check is something should be modified
while((list($key, $val) = each($HTTP_POST_VARS))) {
   if (substr($key, 0, 3) == "mod") {
      $modarray = explode("_", $key);
      $ADODB_FETCH_MODE=ADODB_FETCH_ASSOC;
      $r=$db->Execute("SELECT $fields FROM antibodies WHERE id=$modarray[1]"); 
      $ADODB_FETCH_MODE=ADODB_FETCH_NUM;
      add_ab_form ($db,$fields,$r->fields,$modarray[1]);
      printfooter();
      exit();
   }
}

// when the 'Add' button has been chosen: 
if ($add)
   add_ab_form ($db,$fields,$field_values,0);

elseif ($show)
   show_ab ($id);
   
else {
   // print header of table
   echo "<table border=\"1\" align=center >\n";
   echo "<caption>\n";
   // first handle addition of a new antibody
   if ($submit == "Add Antibody") {
      if (! add ($db, "antibodies",$fields,$HTTP_POST_VARS,$USER) ) {
         echo "</caption>\n</table>\n";
         add_ab_form ($db,$fields,$HTTP_POST_VARS,0);
         printfooter ();
         exit;
      }
   }
   // then look whether it should be modified
   elseif ($submit =="Modify Antibody") {
      if (! (check_ab_data($HTTP_POST_VARS) && modify ($db,"antibodies",$fields,$HTTP_POST_VARS,$id)) ) {
         echo "</caption>\n</table>\n";
         add_ab_form ($db,$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"]);
         printfooter ();
         exit;
      }
   } 
  // or deleted
   elseif ($HTTP_POST_VARS) {
      reset ($HTTP_POST_VARS);
      while((list($key, $val) = each($HTTP_POST_VARS))) {
         if (substr($key, 0, 3) == "del") {
            $delarray = explode("_", $key);
            delete ($db, "antibodies", $delarray[1], $USER);
         }
      }
   } 

   echo "</caption>\n";
   // print form needed for 'delete' buttons
   echo "<form name='form' method='post' action='$PHP_SELF'>\n";  

   echo "<tr>\n";
   echo "<th>Name</th>";
   echo "<th>Antigen</th>\n";
   echo "<th>mg/ml</th>\n";
   echo "<th>Primary/Secundary</th>\n";
   echo "<th>Label</th\n";
   echo "<th>Mono-/Polyclonal</th>\n";
   echo "<th>Host</th>\n";
   echo "<th>Class</th>\n";
   echo "<th>Location</th>\n";
   echo "<th colspan=\"2\">Action</th>\n";
   echo "</tr>\n";

//$db->debug=true;

   // retrieve all antibodies and their info from database
   $query = "SELECT $fields FROM antibodies ORDER BY date DESC";
   $r=$db->Execute($query);
   // print all entries
   while (!($r->EOF)) {
 
      // get results of each row
      $id = $r->fields["id"];
      $name = $r->fields["name"];
      $at1=get_cell($db,"ab_type1","type","id",$r->fields["type1"]);
      $at2=get_cell($db,"ab_type2","type","id",$r->fields["type2"]);
      $at3=get_cell($db,"ab_type3","type","id",$r->fields["type3"]);
      $at4=get_cell($db,"ab_type4","type","id",$r->fields["type4"]);
      $at5=get_cell($db,"ab_type5","type","id",$r->fields["type5"]);
      $antigen = $r->fields["antigen"];
      $concentration = $r->fields["concentration"];
      $location = $r->fields["location"];
      
      // print start of row of selected group
      echo "<tr align='center'>\n";
// DEAL WITH SID HERE
?>
<td><a href="antibodies.php?view=true&id=<?php echo $id;?>&<?=SID?>"><?php echo $name;?></td>
<?php
      echo "<td>$antigen&nbsp;</td>\n";
      echo "<td>$concentration&nbsp;</td>\n";
      echo "<td>$at1</td>\n";
      echo "<td>$at5</td>\n";
      echo "<td>$at2</td>\n";
      echo "<td>$at3</td>\n";
      echo "<td>$at4</td>\n";
      echo "<td>$location&nbsp;</td>\n";
      
      
      // print last columns with links to adjust group
      $modstring = "<input type=\"submit\" name=\"mod_" . $id . "\" value=\"Modify\">";
      echo "<td align='center'>$modstring</td>\n";
      $delstring = "<input type=\"submit\" name=\"del_" . $id . "\" value=\"Remove\" ";
      $delstring .= "Onclick=\"if(confirm('Are you sure the antibody $name ";
      $delstring .= "should be removed?')){return true;}return false;\">";                                           
      echo "<td align='center'>$delstring</td>\n";
      echo "</tr>\n";
   
      $r->MoveNext();
   }

   // print footer of table
   echo "<tr><td colspan=11 align='center'>";
   echo "<input type=\"submit\" name=\"add\" value=\"Add Antibody\">";
   echo "</td></tr>";

   echo "</table>\n";
   echo "</form>\n";

}

printfooter();

?>
