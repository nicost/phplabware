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
require ("includes/db_inc.php");

allowonly($READ, $USER["permissions"]);

// main global vars
$title .= "Antibodies";
$fields="id,access,ownerid,name,type1,type2,type3,type4,type5,antigen,epitope,concentration,buffer,notes,location,source,date";

// register variables
$get_vars = "id,";
globalize_vars ($get_vars,$HTTP_GET_VARS);
$post_vars = "add,submit,search,";
globalize_vars ($post_vars, $HTTP_POST_VARS);


/****************************FUNCTIONS***************************/



////
// !Checks input data.
// returns false if something can not be fixed
function check_ab_data ($field_values) {
   if (!$field_values["name"]) {
      echo "<h3>Please enter an antibody name.</h3>";
      return false;
   }
   if ($field_values["concentration"]) {
      if (! $field_values["concentration"]=(float)$field_values["concentration"] ) {
         echo "<h3>Use numbers only for the concentration field.</h3>";
         return false;
      }
   }
   return true;
}


////
// !Prints a form with antibody stuff
// $id=0 for a new entry, otherwise it is the id
function add_ab_form ($db,$fields,$field_values,$id,$USER,$PHP_SELF) {
   if (!may_write($db,"antibodies",$id,$USER))
      return false;

   // get values in a smart way
   $column=strtok($fields,",");
   while ($column) {
      ${$column}=$field_values[$column];
      $column=strtok(",");
   }
?>
<form method='post' id='antibodyform action='<?php echo $PHP_SELF?>?<?=SID?>'> 
<?php
   echo "<table border=0 align='center'>\n";
   if ($id) {
      echo "<tr><td colspan=7 align='center'><h3>Modify Antibody <i>$name</i></h3></td></tr>\n";
      echo "<input type='hidden' name='id' value='$id'>\n";
   }
   else
      echo "<tr><td colspan=7 align='center'><h3>New Antibody</h3></td></tr>\n";
   echo "<tr align='center'>\n";
   echo "<td colspan=2></td>\n";
   echo "<th>Primary/Second.</th>\n<th>Label</th>\n<th>Mono-/Polyclonal</th>\n";
   echo "<th>Host</th>\n<th>Class</th>\n";
   echo "</tr>\n";
   echo "<tr>\n";
   echo "<th>Name: <sup style='color:red'>&nbsp;*</sup></th>\n";
   echo "<td><input type='text' name='name' value='$name'></td>\n";

   $r=$db->Execute("SELECT type,id FROM ab_type1 ORDER BY sortkey");
   $text=$r->GetMenu2("type1",$type1,false);
   echo "<td>$text</td>\n";
   
   $r=$db->Execute("SELECT type,id FROM ab_type5 ORDER BY sortkey");
   $text=$r->GetMenu2("type5",$type5,false);
   echo "<td>$text</td>\n";
   
   $r=$db->Execute("SELECT type,id FROM ab_type2");
   $text=$r->GetMenu2("type2",$type2,false);
   echo "<td>$text</td>\n";

   $r=$db->Execute("SELECT type,id FROM ab_type3 ORDER BY sortkey");
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
function show_ab ($db,$fields,$id,$USER,$system_settings) {
   if (!may_read($db,"antibodies",$id,$USER))
      return false;

   // get values 
   $r=$db->Execute("SELECT $fields FROM antibodies WHERE id=$id");
   if ($r->EOF) {
      echo "<h3>Could not find this record in the database</h3>";
      return false;
   }
   $column=strtok($fields,",");
   while ($column) {
      ${$column}=$r->fields[$column];
      $column=strtok(",");
   }

   echo "<table border=0 align='center'>\n";
   echo "<tr align='center'>\n";
   echo "<td colspan=2></td>\n";
   echo "<th>Primary/Second.</th>\n<th>Label</th>\n<th>Mono-/Polyclonal</th>\n";
   echo "<th>Host</th>\n<th>Class</th>\n";
   echo "</tr>\n";
   echo "<tr>\n";
   echo "<th>Name: <sup style='color:red'>&nbsp;*</sup></th>\n";
   echo "<td>$name</td>\n";
   $r=$db->Execute("SELECT type,id FROM ab_type1");
   $text=get_cell($db,"ab_type1","type","id",$type1);
   echo "<td align='center'>$text</td>\n";
   
   $r=$db->Execute("SELECT type,id FROM ab_type5 ORDER BY sortkey");
   $text=get_cell($db,"ab_type5","type","id",$type5);
   echo "<td align='center'>$text</td>\n";
   
   $r=$db->Execute("SELECT type,id FROM ab_type2");
   $text=get_cell($db,"ab_type2","type","id",$type2);
   echo "<td align='center'>$text</td>\n";

   $r=$db->Execute("SELECT type,id FROM ab_type3");
   $text=get_cell($db,"ab_type3","type","id",$type3);
   echo "<td align='center'>$text</td>\n";

   $r=$db->Execute("SELECT type,id FROM ab_type4 ORDER BY sortkey");
   $text=get_cell($db,"ab_type4","type","id",$type4);
   echo "<td align='center'>$text</td>\n";

   echo "</tr>\n";

   echo "<tr>\n";
   echo "<th>Antigen: </th><td>$antigen</td>\n";
   echo "<td>&nbsp;</td>";
   echo "<th>Epitope: </th><td colspan=3>$epitope</td>\n";
   echo "</tr>\n";
   
   echo "<tr>";
   echo "<th>Buffer: </th><td>$buffer</td>\n";
   echo "<td>&nbsp;</td>";
   echo "<th>Concentration (mg/ml): </th><td colspan=3>$concentration</td>\n";
   echo "</tr>\n";
   
   echo "<tr>";
   echo "<th>Source: </th><td>$source</td>\n";
   echo "<td>&nbsp;</td>";
   echo "<th>Location: </th><td colspan=3>$location</td>\n";
   echo "</tr>\n";
   
   echo "<tr>";
   $query="SELECT firstname,lastname,email FROM users WHERE id=$ownerid";
   $r=$db->Execute($query);
   if ($r->fields["email"]) {
      echo "<th>Author: </th><td><a href='mailto:".$r->fields["email"]."'>";
      echo $r->fields["firstname"]." ".$r->fields["lastname"]."</a></td>\n";
   }
   else {
      echo "<th>Author: </th><td>".$r->fields["firstname"]." ";
      echo $r->fields["lastname"] ."</td>\n";
    }
   echo "<td>&nbsp;</td>";
   $dateformat=get_cell($db,"dateformats","dateformat","id",$system_settings["dateformat"]);
   $date=date($dateformat,$date);
   echo "<th>Date entered: </th><td colspan=3>$date</td>\n";
   echo "</tr>\n";
   
   echo "<tr>";
   $notes=nl2br(htmlentities($notes));
   echo "<th>Notes: </th><td colspan=6>$notes</td>\n";
   echo "</tr>\n";
?>   
<form method='post' id='antibodyview action='<?php echo $PHP_SELF?>?<?=SID?>'> 
<?php
   echo "<tr>";
   echo "<td colspan=7 align='center'><input type='submit' name='submit' value='Dismiss'></td>\n";
   echo "</tr>\n";
   

   echo "</table></form>\n";
}

/*****************************BODY*******************************/

printheader($title);
navbar($USER["permissions"]);

// check if something should be modified or shown
while((list($key, $val) = each($HTTP_POST_VARS))) {
   if (substr($key, 0, 3) == "mod") {
      $modarray = explode("_", $key);
      $ADODB_FETCH_MODE=ADODB_FETCH_ASSOC;
      $r=$db->Execute("SELECT $fields FROM antibodies WHERE id=$modarray[1]"); 
      $ADODB_FETCH_MODE=ADODB_FETCH_NUM;
      add_ab_form ($db,$fields,$r->fields,$modarray[1],$USER,$PHP_SELF);
      printfooter();
      exit();
   }
   // show the record
   if (substr($key, 0, 4) == "view") {
      $modarray = explode("_", $key);
      show_ab($db,$fields,$modarray[1],$USER,$system_settings);
      printfooter();
      exit();
   }
}


// when the 'Add' button has been chosen: 
if ($add)
   add_ab_form ($db,$fields,$field_values,0,$USER,$PHP_SELF);

else {
   // print header of table
   echo "<table border=\"1\" align=center >\n";
   echo "<caption>\n";
   // first handle addition of a new antibody
   if ($submit == "Add Antibody") {
      if (! (check_ab_data($HTTP_POST_VARS) && add ($db, "antibodies",$fields,$HTTP_POST_VARS,$USER) ) ){
         echo "</caption>\n</table>\n";
         add_ab_form ($db,$fields,$HTTP_POST_VARS,0,$USER,$PHP_SELF);
         printfooter ();
         exit;
      }
      else  // to not interfere with search form 
         unset ($HTTP_POST_VARS);
   }
   // then look whether it should be modified
   elseif ($submit =="Modify Antibody") {
      if (! (check_ab_data($HTTP_POST_VARS) && modify ($db,"antibodies",$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER)) ) {
         echo "</caption>\n</table>\n";
         add_ab_form ($db,$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER,$PHP_SELF);
         printfooter ();
         exit;
      }
      else  // to not interfere with search form 
         unset ($HTTP_POST_VARS);
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
   // print form
?>
<form name='form' method='post' action='<?php echo $PHP_SELF?>?<?=SID?>'>  
<?php

   if ($search=="Show All")
      unset ($HTTP_POST_VARS);
   $column=strtok($fields,",");
   while ($column) {
      ${$column}=$HTTP_POST_VARS[$column];
      $column=strtok(",");
   }

   // row with search form
   echo "<tr align='center'>\n";
   echo "<td><input type='text' name='name' value='$name' size=8></td>\n";
   echo "<td><input type='text' name='antigen' value='$antigen' size=8></td>\n";
   echo "<td><input type='text' name='notes' value='$notes' size=8></td>\n";
   $r=$db->Execute("SELECT typeshort,id FROM ab_type1");
   $text=$r->GetMenu2("type1",$type1,true,false,0,"style='width: 80%'");
   echo "<td style='width: 10%'>$text</td>\n";

   $r=$db->Execute("SELECT typeshort,id FROM ab_type5 ORDER BY sortkey");
   $text=$r->GetMenu2("type5",$type5,true,false,0,"style='width: 80%'");
   echo "<td style='width: 10%'>$text</td>\n";

   $r=$db->Execute("SELECT typeshort,id FROM ab_type2");
   $text=$r->GetMenu2("type2",$type2,true,false,0,"style='width: 80%'");
   echo "<td style='width: 10%'>$text</td>\n";

   $r=$db->Execute("SELECT typeshort,id FROM ab_type3 ORDER BY SORTKEY");
   $text=$r->GetMenu2("type3",$type3,true,false,0,"style='width: 80%'");
   echo "<td style='width: 10%'>$text</td>\n";

   $r=$db->Execute("SELECT typeshort,id FROM ab_type4 ORDER BY sortkey");
   $text=$r->GetMenu2("type4",$type4,true,false,0,"style='width: 80%'");
   echo "<td style='width: 10%'>$text</td>\n";

   echo "<td><input type='text' name='location' value='$location' size=8></td>\n";
   echo "<td><input type=\"submit\" name=\"search\" value=\"Search\">&nbsp;";
   echo "<input type=\"submit\" name=\"search\" value=\"Show All\"></td>";
   echo "</tr>\n";

   echo "<tr>\n";
   echo "<th>Name</th>";
   echo "<th>Antigen</th>\n";
   echo "<th>Notes</th>\n";
   echo "<th>Prim./Second.</th>\n";
   echo "<th>Label</th>\n";
   echo "<th>Mono-/Polycl.</th>\n";
   echo "<th>Host</th>\n";
   echo "<th>Class</th>\n";
   echo "<th>Location</th>\n";
   echo "<th>Action</th>\n";
   echo "</tr>\n";

   // retrieve all antibodies and their info from database
   $whereclause=may_read_SQL ($db,"antibodies",$USER);
   if ($search=="Search")
      $query=search("antibodies",$fields,$HTTP_POST_VARS," id IN ($whereclause) ORDER BY name");
   else
      $query = "SELECT $fields FROM antibodies WHERE id IN ($whereclause) ORDER BY date DESC";
   //$db->debug=true;
   $r=$db->Execute($query);
   $rownr=1;
   // print all entries
   while ($r && !($r->EOF)) {
 
      // get results of each row
      $id = $r->fields["id"];
      $name = $r->fields["name"];
      $at1=get_cell($db,"ab_type1","type","id",$r->fields["type1"]);
      $at2=get_cell($db,"ab_type2","type","id",$r->fields["type2"]);
      $at3=get_cell($db,"ab_type3","type","id",$r->fields["type3"]);
      $at4=get_cell($db,"ab_type4","type","id",$r->fields["type4"]);
      $at5=get_cell($db,"ab_type5","type","id",$r->fields["type5"]);
      $antigen = $r->fields["antigen"];
      $notes = $r->fields["notes"];
      $location = $r->fields["location"];
 
 
      // print start of row of selected group
      if ($rownr % 2)
         echo "<tr class='row_odd' align='center'>\n";
      else
         echo "<tr class='row_even' align='center'>\n";

      echo "<td>$name</td>\n";
      echo "<td>$antigen&nbsp;</td>\n";
      if ($notes)
         echo "<td>yes</td>\n";
      else
         echo "<td>no</td>\n";
      echo "<td>$at1</td>\n";
      echo "<td>$at5</td>\n";
      echo "<td>$at2</td>\n";
      echo "<td>$at3</td>\n";
      echo "<td>$at4</td>\n";
      echo "<td>$location&nbsp;</td>\n";
      
      echo "<td align='center'>&nbsp;\n";
      echo "<input type=\"submit\" name=\"view_" . $id . "\" value=\"View\">\n";
      if (may_write($db,"antibodies",$id,$USER)) {
         echo "<input type=\"submit\" name=\"mod_" . $id . "\" value=\"Modify\">\n";
         $delstring = "<input type=\"submit\" name=\"del_" . $id . "\" value=\"Remove\" ";
         $delstring .= "Onclick=\"if(confirm('Are you sure the antibody $name ";
         $delstring .= "should be removed?')){return true;}return false;\">";                                           
         echo "$delstring\n";
      }
      echo "</td>\n";
      echo "</tr>\n";
   
      $r->MoveNext();
      $rownr+=1;
   }

   // print footer of table
   if (may_write($db,"antibodies",false,$USER)) {
      echo "<tr><td colspan=10 align='center'>";
      echo "<input type=\"submit\" name=\"add\" value=\"Add Antibody\">";
      echo "</td></tr>";
   }

   echo "</table>\n";
   echo "</form>\n";

}

printfooter();

?>
