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
$fields="name,id,type1,type2,type3,species,antigen,epitope,concentration,buffer,notes,location,source,date";

// register variables
$get_vars = "id,";
globalize_vars ($get_vars,$HTTP_GET_VARS);
$post_vars = $fields . "add,submit,search,";
globalize_vars ($post_vars, $HTTP_POST_VARS);


/****************************FUNCTIONS***************************/

////
// !Inserts $fields with $fieldvalues into $table
// Returns the id of inserted record on succes, false otherwise.
// Fieldvalues must be an associative array containing all the $fields to be added.
function add ($db, $table,$fields,$fieldvalues){
}

////
// !Modifies $fields in $table with values $fieldvalues where id=$id
// Returns true on succes, false on failure
// Fieldvalues must be an associative array containing all the $fields to be added.
// If a field is not present in $fieldvalues, it will not be changed.  
// The entry 'id' in $fields will be ignored.
function modify ($db, $table,$fields,$fieldvalues,$id) {
}


////
// !Deletes the etry with id=$id
// Returns true on succes, false on failure
// This is very generic, it is likely that you will need to do more cleanup
function delete ($db, $table, $id) {
}

////
// !Prints a form with antibody stuff
// $id=0 for a new entry, otherwise it is the id
function add_ab_form ($db, $fields,$field_values,$id) {
}

////
// !Shows a page with nice information on the antibody
function show_ab ($id) {
}

/*****************************BODY*******************************/

printheader($title);
navbar($USER["permissions"]);

// when the 'Add' button has been chosen: 
if ($add)
   add_ab_form ($db,$fields,$field_values,0);

// when modify has been pressed:
elseif ($mod == "true")
   add_ab_form ($db,$fields,$fieldvalues,$id);

elseif ($id)
   show_ab ($id);
   
else {
   // print header of table
   echo "<table border=\"1\" align=center >\n";
   echo "<caption>\n";
   // first handle addition of a new antibody
   if ($submit == "Add Antibody") {
      if (! $test = add ($db, "antibodies",$fields,$fieldvalues) ) {
         echo "</caption>\n</table>\n";
         add_ab_form ($fields,$field_values,0);
         printfooter ();
         exit;
      }
   }
   // then look whether groupname should be modified
   elseif ($submit =="Modify Antibody") {
      if (! $test = modify ($db,"antibodies",$fields,$fieldvalues,$id)) {
         echo "</caption>\n</table>\n";
         add_ab_form ($fields,$fieldvalues,$id);
         printfooter ();
         exit;
      }
   } 
  //determine wether or not the remove-command is given and act on it
   elseif ($HTTP_POST_VARS) {
      while((list($key, $val) = each($HTTP_POST_VARS))) {
         if (substr($key, 0, 3) == "del") {
            $delarray = explode("_", $key);
            delete ($db, "antibodies", $delarray[1]);
         }
         if (substr($key, 0, 3) == "mod") {
            $modarray = explode("_", $key);
            echo "</caption>\n</table>\n";
            add_ab_form ($fields,$fieldvalues,$id);
            printfooter();
            exit();
         }
      }
   } 

   echo "</caption>\n";
   // print form needed for 'delete' buttons
   echo "<form name='form' method='post' action='$PHP_SELF'>\n";  

   echo "<tr>\n";
   echo "<th>Name</th>";
   echo "<th>Primary/Secundary</th>\n";
   echo "<th>Mono-/Polyclonal</th>\n";
   echo "<th>Host</th>\n";
   echo "<th>Antigen</th>\n";
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
      $at1=get_cell($db,"ab_type1","type","id",$r->fields["ab_type1"]);
      $at2=get_cell($db,"ab_type2","type","id",$r->fields["ab_type2"]);
      $at3=get_cell($db,"ab_type3","type","id",$r->fields["ab_type2"]);
      
      // print start of row of selected group
      echo "<tr>\n";
// DEAL WITH SID HERE
?>
<td><a href="antibodies.php?id=<?php echo $id;?>&<?=SID?>"><?php echo $name;?></td>
<?php
      echo "<td>$at1</td>\n";
      echo "<td>$at2</td>\n";
      echo "<td>$at3</td>\n";
      echo "<td>$antigen</td>\n";
      echo "<td>$location</td>\n";
      
      
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
   echo "<tr><td colspan=8 align='center'>";
   echo "<input type=\"submit\" name=\"add\" value=\"Add Antibody\">";
   echo "</td></tr>";

   echo "</table>\n";
   echo "</form>\n";

}

printfooter();

?>
