<?php
  
// antibodies.php -  List, modify, delete and add antibodies
// antibodies.php - author: Nico Stuurman <nicost@soureforge.net>
  /***************************************************************************
  * This script displays a table with antibodies in phplabware.              *
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

// main global vars
$title .= "Antibodies";
$fields="id,access,ownerid,magic,name,type1,type2,type3,type4,type5,antigen,epitope,concentration,buffer,notes,location,source,date";

// register variables
$showid=$HTTP_GET_VARS["showid"];
$edit_type=$HTTP_GET_VARS["edit_type"];
$post_vars = "add,submit,search,searchj";
globalize_vars ($post_vars, $HTTP_POST_VARS);
if ($searchj)
   $search="Search";


/****************************FUNCTIONS***************************/

////
// !Checks input data.
// returns false if something can not be fixed
function check_ab_data (&$field_values) {
   if (!$field_values["name"]) {
      echo "<h3>Please enter an antibody name.</h3>";
      return false;
   }
   if ($field_values["concentration"]) {
      if (! $field_values["concentration"]=(float)$field_values["concentration"] ) {
	 unset ($field_values["concentration"]);
         echo "<h3>Use numbers only for the concentration field.</h3>";
         return false;
      }
   }
   return true;
}


////
// !Prints a form with antibody stuff
// $id=0 for a new entry, otherwise it is the id
function add_ab_form ($db,$fields,$field_values,$id,$USER,$PHP_SELF,$system_settings) {
   if (!may_write($db,"antibodies",$id,$USER))
      return false;

   // get values in a smart way
   $column=strtok($fields,",");
   while ($column) {
      ${$column}=$field_values[$column];
      $column=strtok(",");
   }
?>
<form method='post' id='antibodyform' enctype='multipart/form-data' action='<?php echo $PHP_SELF?>?<?=SID?>'> 
<?php
   // generate a unique ID (magic) to avoid double uploads 
   if (!$magic)
      $magic=time();
   echo "<input type='hidden' name='magic' value='$magic'>\n";
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
   
   $files=get_files($db,"antibodies",$id);
   echo "<tr>";
   echo "<th>Files: </th>\n";
   echo "<td colspan=4><table border=0>";
   for ($i=0;$i<sizeof($files);$i++) {
      echo "<tr><td>".$files[$i]["link"]."</td>\n";
      echo "<td><input type='submit' name='def_".$files[$i]["id"]."' value='Delete' Onclick=\"if(confirm('Are you sure the file ".$files[$i]["name"]." should be removed?')){return true;}return false;\"></td></tr>\n";
   }
   echo "<tr><td colspan=2><input type='file' name='file[]' value='$filename'></td>\n";
   echo "<td></td><th>File Title:</th><td><input type='text' name='filetitle[]' value='$filetile' size=30></td><td>&nbsp;</td>\n";
   echo "</tr>\n";
   echo "</table></td>\n\n";
   echo "<td colspan=4>";
   show_access($db,"antibodies",$id,$USER,$system_settings);
   echo "</td></tr>\n";
   
   echo "<tr>";
   if ($id)
      $value="Modify Antibody";
   else
      $value="Add Antibody";
   echo "<td colspan=7 align='center'><input type='submit' name='submit' value='$value'>\n";
   echo "&nbsp;&nbsp;<input type='submit' name='submit' value='Cancel'></td>\n";
   echo "</tr>\n";
   

   echo "</table></form>\n";
}

////
// !Shows a page with nice information on the antibody
function show_ab ($db,$fields,$id,$USER,$system_settings) {
   global $PHP_SELF;

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

   $files=get_files($db,"antibodies",$id);
   if ($files) {
      echo "<tr><th>Files:</th>\n<td colspan=5>";
      for ($i=0;$i<sizeof($files);$i++) {
         echo $files[$i]["link"]."<br>\n";
      }
      echo "</tr>\n";
   }
   
   echo "<tr><th>Link:</th><td colspan=7><a href='$PHP_SELF?showid=$id&";
   echo SID;
   echo "'>".$system_settings["baseURL"].getenv("SCRIPT_NAME")."?showid=$id</a></td></tr>\n";

?>   
<form method='post' id='antibodyview' action='<?php echo $PHP_SELF?>?<?=SID?>'> 
<?php
   echo "<tr>";
   echo "<td colspan=7 align='center'><input type='submit' name='submit' value='Dismiss'></td>\n";
   echo "</tr>\n";
   

   echo "</table></form>\n";
}

/*****************************BODY*******************************/

printheader($title);
navbar($USER["permissions"]);

// check if something should be modified, deleted or shown
while((list($key, $val) = each($HTTP_POST_VARS))) {
   if (substr($key, 0, 3) == "mod") {
      $modarray = explode("_", $key);
      $r=$db->Execute("SELECT $fields FROM antibodies WHERE id=$modarray[1]"); 
      add_ab_form ($db,$fields,$r->fields,$modarray[1],$USER,$PHP_SELF,$system_settings);
      printfooter();
      exit();
   }
   // delete file and show antibody form
   if (substr($key, 0, 3) == "def") {
      $modarray = explode("_", $key);
      $filename=delete_file($db,$modarray[1],$USER);
      $id=$HTTP_POST_VARS["id"];
      if ($filename)
         echo "<h3 align='center'>Deleted file <i>$filename</i>.</h3>\n";
      else
         echo "<h3 align='center'>Failed to delete file <i>$filename</i>.</h3>\n";
      add_ab_form ($db,$fields,$HTTP_POST_VARS,$id,$USER,$PHP_SELF,$system_settings);
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
   if (substr($key, 0, 7) == "addtype") {
      $modarray = explode("_", $key);
      $table=$modarray[1]."_".$modarray[2];
      include("includes/type_inc.php");
      add_type($db,$table);
      show_type($db,$table,"");
      printfooter();
      exit();
   }
   if (substr($key, 0, 6) == "mdtype") {
      $modarray = explode("_", $key);
      $table=$modarray[1]."_".$modarray[2];
      include("includes/type_inc.php");
      mod_type($db,$table,$modarray[3]);
      show_type($db,$table,"");
      printfooter();
      exit();
   }
   if (substr($key, 0, 6) == "dltype") {
      $modarray = explode("_", $key);
      $table=$modarray[1]."_".$modarray[2];
      include("includes/type_inc.php");
      del_type($db,$table,$modarray[3],"antibodies");
      show_type($db,$table,"");
      printfooter();
      exit();
   }
}

if ($edit_type && ($USER["permissions"] & $LAYOUT)) {
   include("includes/type_inc.php");
   show_type($db,$edit_type,"");
   printfooter();
   exit();
}

// provide a means to hyperlink directly to a record
if ($showid) {
   show_ab($db,$fields,$showid,$USER,$system_settings);
   printfooter();
   exit();
}


// when the 'Add' button has been chosen: 
if ($add)
   add_ab_form ($db,$fields,$field_values,0,$USER,$PHP_SELF,$system_settings);

else {
   // print header of table
   echo "<table border='1' align='center' width='100%'>\n";
   echo "<caption>\n";
   // first handle addition of a new antibody
   if ($submit == "Add Antibody") {
      if (! (check_ab_data($HTTP_POST_VARS) && $id=add ($db, "antibodies",$fields,$HTTP_POST_VARS,$USER) ) ){
         echo "</caption>\n</table>\n";
         add_ab_form ($db,$fields,$HTTP_POST_VARS,0,$USER,$PHP_SELF,$system_settings);
         printfooter ();
         exit;
      }
      else {  
	 upload_files($db,"antibodies",$id,$USER,$system_settings);
         // to not interfere with search form 
         unset ($HTTP_POST_VARS);
	 // or we won't see the new record
	 unset ($HTTP_SESSION_VARS["ab_query"]);
      }
   }
   // then look whether it should be modified
   elseif ($submit =="Modify Antibody") {
      if (! (check_ab_data($HTTP_POST_VARS) && modify ($db,"antibodies",$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER)) ) {
         echo "</caption>\n</table>\n";
         add_ab_form ($db,$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER,$PHP_SELF,$system_settings);
         printfooter ();
         exit;
      }
      else { 
	 upload_files($db,"antibodies",$HTTP_POST_VARS["id"],$USER,$system_settings);
         // to not interfere with search form 
         unset ($HTTP_POST_VARS);
      }
   } 
   elseif ($submit=="Cancel")
      // to not interfere with search form 
      unset ($HTTP_POST_VARS);
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
<form name='ab_form' method='post' action='<?php echo $PHP_SELF?>?<?=SID?>'>  
<?php

   if ($search=="Show All") {
      $num_p_r=$HTTP_POST_VARS["num_p_r"];
      unset ($HTTP_POST_VARS);
      $curr_page=1;
      session_unregister("ab_query");
   }
   $column=strtok($fields,",");
   while ($column) {
      ${$column}=$HTTP_POST_VARS[$column];
      $column=strtok(",");
   }

   // prepare SQl search statement
   $whereclause=may_read_SQL ($db,"antibodies",$USER);
   if ($search=="Search")
      $ab_query=search("antibodies",$fields,$HTTP_POST_VARS," id IN ($whereclause) ORDER BY name");
   elseif (session_is_registered ("ab_query") && isset($HTTP_SESSION_VARS["ab_query"]))
      $ab_query=$HTTP_SESSION_VARS["ab_query"];
   else
      $ab_query = "SELECT $fields FROM antibodies WHERE id IN ($whereclause) ORDER BY date DESC";
   $HTTP_SESSION_VARS["ab_query"]=$ab_query;   
   session_register("ab_query");

   // row with search form
   echo "<tr align='center'>\n";   
   //javascript that submits the form when a select was chosen
   $jscript="onChange='document.ab_form.searchj.value=\"Search\"; document.ab_form.submit()'";
   echo "<input type='hidden' name='searchj' value=''>\n";
   $r=$db->Execute($ab_query);
   $lista=make_SQL_csf ($r,false,"id",$nr_records);
   // and a list with all records we may see
   $listb=may_read_SQL($db,"antibodies",$USER);
   // show title we may see, when too many, revert to text box
   if ($name) $list=$listb; else $list=$lista;
   if ($list && ($nr_records < $max_menu_length) ) {
      $r=$db->Execute("SELECT name FROM antibodies WHERE id IN ($list) ORDER BY name");
      $text=$r->GetMenu("name",$name,true,false,0,"style='width: 80%' $jscript");
      echo "<td style='width: 10%'>$text</td>\n";
   }
   else
      echo "<td><input type='text' name='name' value='$name' size=8></td>\n";
   echo "<td><input type='text' name='antigen' value='$antigen' size=8></td>\n";
   echo "<td><input type='text' name='notes' value='$notes' size=8></td>\n";

   echo "<td style='width: 10%'>";
   if ($type1) $list=$listb; else $list=$lista;
   $r=$db->Execute("SELECT type1 FROM antibodies WHERE id IN ($list)");
   $list2=make_SQL_ids($r,false,"type1");
   if ($list2) {
      $r=$db->Execute("SELECT typeshort,id FROM ab_type1 WHERE id IN ($list2) ORDER BY sortkey");
      $text=$r->GetMenu2("type1",$type1,true,false,0,"style='width: 80%' $jscript");
      echo "$text</td>\n";
   }
   else
      echo "&nbsp;</td>\n";

   echo "<td style='width: 10%'>";
   if ($USER["permissions"] & $LAYOUT) {
      echo "<a href='$PHP_SELF?edit_type=ab_type5&";
      echo SID;
      echo "'>Edit Labels</a><br>\n";
   }   
   if ($type5) $list=$listb; else $list=$lista;
   $r=$db->Execute("SELECT type5 FROM antibodies WHERE id IN ($list)");
   $list2=make_SQL_ids($r,false,"type5");
   if ($list2) {
      $r=$db->Execute("SELECT typeshort,id FROM ab_type5 WHERE id IN ($list2) ORDER BY sortkey");
      $text=$r->GetMenu2("type5",$type5,true,false,0,"style='width: 80%' $jscript");
      echo "$text</td>\n";
   }
   else
      echo "&nbsp;</td>\n";

   echo "<td style='width: 10%'>";
   if ($type2) $list=$listb; else $list=$lista;
   $r=$db->Execute("SELECT type2 FROM antibodies WHERE id IN ($list)");
   $list2=make_SQL_ids($r,false,"type2");
   if ($list2) {
      $r=$db->Execute("SELECT typeshort,id FROM ab_type2 WHERE id IN ($list2) ORDER BY sortkey");
      $text=$r->GetMenu2("type2",$type2,true,false,0,"style='width: 80%' $jscript");
      echo "$text</td>\n";
   }
   else
      echo "&nbsp;</td>\n";

   echo "<td style='width: 10%'>";
   if ($USER["permissions"] & $LAYOUT) {
      echo "<a href='$PHP_SELF?edit_type=ab_type3&";
      echo SID;
      echo "'>Edit Hosts</a><br>\n";
   }    
   if ($type3) $list=$listb; else $list=$lista;
   $r=$db->Execute("SELECT type3 FROM antibodies WHERE id IN ($list)");
   $list2=make_SQL_ids($r,false,"type3");
   if ($list2) {
      $r=$db->Execute("SELECT typeshort,id FROM ab_type3 WHERE id IN ($list2) ORDER BY sortkey");
      $text=$r->GetMenu2("type3",$type3,true,false,0,"style='width: 80%' $jscript");
      echo "$text</td>\n";
   }
   else
      echo "&nbsp;</td>\n";  

   echo "<td style='width: 10%'>";
   if ($USER["permissions"] & $LAYOUT) {
      echo "<a href='$PHP_SELF?edit_type=ab_type4&";
      echo SID;
      echo "'>Edit Classes</a><br>\n";
   }    
   if ($type4) $list=$listb; else $list=$lista;
   $r=$db->Execute("SELECT type4 FROM antibodies WHERE id IN ($list)");
   $list2=make_SQL_ids($r,false,"type4");
   if ($list2) {
      $r=$db->Execute("SELECT typeshort,id FROM ab_type4 WHERE id IN ($list2) ORDER BY sortkey");
      $text=$r->GetMenu2("type4",$type4,true,false,0,"style='width: 80%' $jscript");
      echo "$text</td>\n";
   }
   else
      echo "&nbsp;</td>\n";

   echo "<td><input type='text' name='location' value='$location' size=8></td>\n";
   echo "<td>&nbsp;</td>";
   echo "<td><input type=\"submit\" name=\"search\" value=\"Search\">&nbsp;";
   echo "<input type=\"submit\" name=\"search\" value=\"Show All\"></td>";
   echo "</tr>\n";

   echo "<tr>\n";
   echo "<th>Name</th>";
   echo "<th>Antigen</th>\n";
   echo "<th>Notes</th>\n";
   echo "<th>Prim./Sec.</th>\n";
   echo "<th>Label</th>\n";
   echo "<th>Mono-/Polycl.</th>\n";
   echo "<th>Host</th>\n";
   echo "<th>Class</th>\n";
   echo "<th>Location</th>\n";
   echo "<th>Files</th>\n";
   echo "<th>Action</th>\n";
   echo "</tr>\n";
   
   // paging stuff
   if (!$num_p_r)
      $num_p_r=$USER["settings"]["num_p_r"];
   if (isset($HTTP_POST_VARS["num_p_r"]))
      $num_p_r=$HTTP_POST_VARS["num_p_r"];
   if (!isset($num_p_r))
      $num_p_r=10;
   $USER["settings"]["num_p_r"]=$num_p_r;
   if (!isset($curr_page))
      $curr_page=$HTTP_SESSION_VARS["curr_page"];
   if (isset($HTTP_POST_VARS["next"]))
      $curr_page+=1;
   if (isset($HTTP_POST_VARS["previous"]))
      $curr_page-=1;
   if ($curr_page<1)
      $curr_page=1;
   $HTTP_SESSION_VARS["curr_page"]=$curr_page; 
   session_register("curr_page");

   $r=$db->PageExecute($ab_query,$num_p_r,$curr_page);
   $rownr=1;
   // print all entries
   while (!($r->EOF) && $r) {
 
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
      $files=get_files($db,"antibodies",$id,3);
      echo "<td>";
      if ($files) 
         for ($i=0;$i<sizeof($files);$i++)
	    echo $files[$i]["link"]."<br>";
      else
         echo "&nbsp;";
      echo "</td>\n";

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

   // Add Antibody button
   if (may_write($db,"antibodies",false,$USER)) {
      echo "<tr><td colspan=11 align='center'>";
      echo "<input type=\"submit\" name=\"add\" value=\"Add Antibody\">";
      echo "</td></tr>";
   }

   // next/previous buttons
   echo "<tr><td colspan=2 align='center'>";
   if ($r && !$r->AtFirstPage())
      echo "<input type=\"submit\" name=\"previous\" value=\"Previous\"></td>\n";
   else
      echo "&nbsp;</td>\n";
   echo "<td colspan=8 align='center'>";
   echo "<input type='text' name='num_p_r' value='$num_p_r' size=3>";
   echo "Records per page</td>\n";
   echo "<td colspan=1 align='center'>";
   if ($r && !$r->AtLastPage())
      echo "<input type=\"submit\" name=\"next\" value=\"Next\"></td>\n";
   else
      echo "&nbsp;</td>\n";
   echo "</tr>\n";

   echo "</table>\n";
   echo "</form>\n";

}

printfooter($db,$USER);

?>
