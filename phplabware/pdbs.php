<?php
  
// pdbs.php - Users can upload pdb files, pull bibl. data from Pubmed 
// pdbs.php - author: Nico Stuurman <nicost@soureforge.net>
// TABLES: pdbs
  /***************************************************************************
  * This script displays a table with pdbs in phplabware.                    *
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
$httptitle .= "PDBs";
$fields="id,access,ownerid,magic,title,author,notes,pdbid,date,lastmodby,lastmoddate";

// register variables
$get_vars="add,edit_type,showid,search";
globalize_vars($get_vars, $HTTP_GET_VARS);
$post_vars = "add,submit,search,searchj";
globalize_vars ($post_vars, $HTTP_POST_VARS);
if ($searchj)
   $search="Search";

/****************************FUNCTIONS***************************/

////
// !Checks input data.
// returns false if something can not be fixed
function check_pb_data ($db,&$field_values) {
   global $HTTP_POST_VARS, $HTTP_POST_FILES;
   // read title, author, pdbid from file
   // we can not demand a file to there since this might be a modify
   if (is_readable($HTTP_POST_FILES["file"]["tmp_name"][0])) {
       $fh=fopen($HTTP_POST_FILES["file"]["tmp_name"][0],"r");  
       $test1=false;
       $test2=true;
       while ($fh && $test2 && !feof($fh)) {
          $line=chop(fgets($fh,1024));
          $lid=strtok($line," ");
          switch ($lid) {
             case "HEADER":
                $pdbid=trim(strrchr($line," "));
                if (!$field_values["pdbid"]) $field_values["pdbid"]=$pdbid;
                break;
             case "TITLE":
                $field_values["title"].=substr($line,10);
                break;
             case "AUTHOR":
                $field_values["author"].=substr($line,10);
                $test1=true;
                break;
             default:
                if ($test1) $test2=false;
         }
      }
   }
   if (!$field_values["title"]) {
      echo "<h3>Please enter a title for the PDB.</h3>";
      return false;
   }
   return true;
}


////
// !Prints a form with pdb stuff
// $fields is a comma-delimited list with column names
// $field_values is a hash with column names as keys
// $id=0 for a new entry, otherwise it is the id
function add_pb_form ($db,$fields,$field_values,$id,$USER,$PHP_SELF,$system_settings) {
   if (!may_write($db,"pdbs",$id,$USER)) 
      return false;
   global $db_type;

   // get values in a smart way
   $column=strtok($fields,",");
   while ($column) {
      ${$column}=$field_values[$column];
      $column=strtok(",");
   }
?>
<form method='post' id='pdbform' enctype='multipart/form-data' action='<?php echo $PHP_SELF?>?<?=SID?>'> 
<?php
   // generate a unique ID (magic) to avoid double uploads 
   if (!$magic)
      $magic=time();
   echo "<input type='hidden' name='magic' value='$magic'>\n";
   echo "<table border=0 align='center'>\n";
   if ($id) {
      echo "<tr><td colspan=5 align='center'><h3>Modify PDB:<br> <i>$title</i></h3></td></tr>\n";
      echo "<input type='hidden' name='id' value='$id'>\n";
   }
   else
      echo "<tr><td colspan=5 align='center'><h3>New PDB</h3></td></tr>\n";
   echo "<tr>\n";
   echo "<th>Title: <sup style='color:red'>&nbsp;*</sup></th>\n";
   echo "<td><input type='text' name='title' value='$pmid' size=14><br>";
   echo "</td></tr>\n";

   echo "<tr>";
   echo "<th>Notes: </th><td colspan=6><textarea name='notes' rows='5' cols='100%'>$notes</textarea></td>\n";
   echo "</tr>\n";
   
   $files=get_files($db,"pdbs",$id);
   echo "<tr>";
   echo "<th>Files: </th>\n";
   echo "<td colspan=4><table border=0>";
   for ($i=0;$i<sizeof($files);$i++) {
      echo "<tr><td colspan=2>".$files[$i]["link"];
      echo "&nbsp;&nbsp;(".$files[$i]["type"]." file)</td>\n";
      echo "<td><input type='submit' name='def_".$files[$i]["id"]."' value='Delete' Onclick=\"if(confirm('Are you sure the file ".$files[$i]["name"]." should be removed?')){return true;}return false;\"></td></tr>\n";
   }
   echo "<tr><th>Replace file(s) with:</th>\n";
   echo "<td><input type='file' name='file[]' value='$filename'></td>\n";
   
   echo "</tr>\n";
   echo "</table></td>\n\n";
   echo "<td colspan=4>";
   show_access($db,"pdbs",$id,$USER,$system_settings);
   echo "</td></tr>\n";
   
   echo "<tr>";
   if ($id)
      $value="Modify PDB";
   else
      $value="Add PDB";
   echo "<td colspan=7 align='center'><input type='submit' name='submit' value='$value'>\n";
   echo "&nbsp;&nbsp;<input type='submit' name='submit' value='Cancel'></td>\n";
   echo "</tr>\n";
   
   echo "</table></form>\n";
}

////
// !Shows a page with nice information on the pdb
function show_pb ($db,$fields,$id,$USER,$system_settings) {
   global $PHP_SELF;

   if (!may_read($db,"pdbs",$id,$USER))
      return false;

   // get values 
   $r=$db->Execute("SELECT $fields FROM pdbs WHERE id=$id");
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
   echo "<tr>\n";
   echo "<th>Title: </th>\n";
   echo "<td>$title<br>\n";
   $text=get_cell($db,"pb_type1","type","id",$type1);
   
   echo "<tr>";
   $query="SELECT firstname,lastname,email FROM users WHERE id=$ownerid";
   $r=$db->Execute($query);
   if ($r->fields["email"]) {
      echo "<th>Submitted by: </th><td><a href='mailto:".$r->fields["email"]."'>";
      echo $r->fields["firstname"]." ".$r->fields["lastname"]."</a> ";
   }
   else {
      echo "<th>Submitted by: </th><td>".$r->fields["firstname"]." ";
      echo $r->fields["lastname"] ." ";
   }
   $dateformat=get_cell($db,"dateformats","dateformat","id",$system_settings["dateformat"]);
   $date=date($dateformat,$date);
   echo "($date)</td>\n";
   echo "</tr>\n";

   if ($lastmodby && $lastmoddate) {
      echo "<tr>";
      $query="SELECT firstname,lastname,email FROM users WHERE id=$lastmodby";
      $r=$db->Execute($query);
      if ($r->fields["email"]) {
         echo "<th>Last modified by: </th><td><a href='mailto:".$r->fields["email"]."'>";
         echo $r->fields["firstname"]." ".$r->fields["lastname"]."</a>";
      }
      else {
         echo "<th>Last modified by: </th><td>".$r->fields["firstname"]." ";
         echo $r->fields["lastname"];
      }
      $dateformat=get_cell($db,"dateformats","dateformat","id",$system_settings["dateformat"]);
      $lastmoddate=date($dateformat,$lastmoddate);
      echo " ($lastmoddate)</td>\n";
      echo "</tr>\n";
   }

   echo "<tr>";
   $notes=nl2br(htmlentities($notes));
   echo "<th>Notes: </th><td>$notes</td>\n";
   echo "</tr>\n";

   $files=get_files($db,"pdbs",$id);
   if ($files) {
      echo "<tr><th>Files:</th>\n<td>";
      for ($i=0;$i<sizeof($files);$i++) {
         echo $files[$i]["link"]."&nbsp;&nbsp;(".$files[$i]["type"];
         echo " file)<br>\n";
      }
      echo "</tr>\n";
   }
   
   echo "<tr><th>Links:</th><td colspan=7><a href='$PHP_SELF?showid=$id&";
   echo SID;
   echo "'>".$system_settings["baseURL"].getenv("SCRIPT_NAME")."?showid=$id</a> (This page)<br>\n";

?>   
<form method='post' id='pdbview' action='<?php echo $PHP_SELF?>?<?=SID?>'> 
<?php
   echo "<tr>";
   echo "<td colspan=7 align='center'><input type='submit' name='submit' value='Back'></td>\n";
   echo "</tr>\n";

   echo "</table></form>\n";
}

/*****************************BODY*******************************/

printheader($httptitle);
navbar($USER["permissions"]);

// check if something should be modified, deleted or shown
while((list($key, $val) = each($HTTP_POST_VARS))) {
   if (substr($key, 0, 3) == "mod") {
      $modarray = explode("_", $key);
      $r=$db->Execute("SELECT $fields FROM pdbs WHERE id=$modarray[1]"); 
      add_pb_form ($db,$fields,$r->fields,$modarray[1],$USER,$PHP_SELF,$system_settings);
      printfooter();
      exit();
   }
   // delete file and show form
   if (substr($key, 0, 3) == "def") {
      $modarray = explode("_", $key);
      $filename=delete_file($db,$modarray[1],$USER);
      $id=$HTTP_POST_VARS["id"];
      if ($filename)
         echo "<h3 align='center'>Deleted file <i>$filename</i>.</h3>\n";
      else
         echo "<h3 align='center'>Failed to delete file <i>$filename</i>.</h3>\n";
      add_pb_form ($db,$fields,$HTTP_POST_VARS,$id,$USER,$PHP_SELF,$system_settings);
      printfooter();
      exit();
   }
   // show the record
   if (substr($key, 0, 4) == "view") {
      $modarray = explode("_", $key);
      show_pb($db,$fields,$modarray[1],$USER,$system_settings);
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
      del_type($db,$table,$modarray[3],"pdbs");
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
   show_pb($db,$fields,$showid,$USER,$system_settings);
   printfooter();
   exit();
}

// when the 'Add' button has been chosen: 
if ($add)
   add_pb_form ($db,$fields,$field_values,0,$USER,$PHP_SELF,$system_settings);

else {
   // first handle addition of a new PDB
   if ($submit == "Add PDB") {
      if (! (check_pb_data($db, $HTTP_POST_VARS) && $id=add ($db, "pdbs",$fields,$HTTP_POST_VARS,$USER) ) ){
         echo "</caption>\n</table>\n";
         add_pb_form ($db,$fields,$HTTP_POST_VARS,0,$USER,$PHP_SELF,$system_settings);
         printfooter ();
         exit;
      }
      else {  
         if ($id>0)
	    $fileid=upload_files($db,"pdbs",$id,$USER,$system_settings);
         // to not interfere with search form 
         unset ($HTTP_POST_VARS);
	 // or we won't see the new record
	 unset ($HTTP_SESSION_VARS["pb_query"]);
      }
   }
   // then look whether it should be modified
   elseif ($submit=="Modify PDB") {
      if (! (check_pb_data($db,$HTTP_POST_VARS) && modify ($db,"pdbs",$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER)) ) {
         echo "</caption>\n</table>\n";
         add_pb_form ($db,$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER,$PHP_SELF,$system_settings);
         printfooter ();
         exit;
      }
      else { 
         if ($HTTP_POST_FILES["file"]["name"][0]) {
            // delete all existing file
            delete ($db,"pdbs",$HTTP_POST_VARS["id"],$USER,true);
            $fileid=upload_files($db,"pdbs",$HTTP_POST_VARS["id"],$USER,$system_settings);
         }
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
            delete ($db, "pdbs", $delarray[1], $USER);
         }
      }
   } 

   if ($search=="Show All") {
      $num_p_r=$HTTP_POST_VARS["num_p_r"];
      unset ($HTTP_POST_VARS);
      $pb_curr_page=1;
      session_unregister("pb_query");
   }

   // paging stuff
   $num_p_r=paging($num_p_r, $USER);

   // get current page
   $pb_curr_page =current_page($pb_curr_page,"pb"); 

   // prepare search SQL statement
   $pb_query=make_search_SQL($db,"pdbs","pb",$fields,$USER,$search);

   // loop through all entries for next/previous buttons
   $r=$db->PageExecute($pb_query,$num_p_r,$pb_curr_page);
   while (!($r->EOF) && $r) {
      $r->MoveNext();
   }
   // print form
?>
<form name='pb_form' method='post' action='<?php echo $PHP_SELF?>?<?=SID?>'>  
<?php

   // row with action links
   $sid=SID;
   if ($sid) $sid="&".$sid;
   echo "<table border=0 width='50%' align='center'>\n<tr>\n";
   if (may_write($db,"pdbs",false,$USER)) 
      echo "<td align='center'><a href='$PHP_SELF?add=Add PDB$sid'>Add PDB</a></td>\n";
   echo "</table>\n";

   next_previous_buttons($r,true,$num_p_r);

   // print header of table
   echo "<table border='1' align='center'>\n";
   echo "<caption>\n";
   echo "</caption>\n";

   // row with search form
   echo "<tr align='center'>\n";
   // javascript that submit the form with the search button when a selects was chosen
   $jscript="onChange='document.pb_form.searchj.value=\"Search\"; document.pb_form.submit()'";
   echo "<input type='hidden' name='searchj' value=''>\n";
   // get a list with ids we may see
   $r=$db->Execute($pb_query);
   $lista=make_SQL_csf ($r,false,"id",$nr_records);
   // and a list with all records we may see
   $listb=may_read_SQL($db,"pdbs",$USER);

   // show title we may see, when too many, revert to text box
   echo "<td><input type='text' name='title' value='$title' size=15></td>\n";

   // show a list with authors having stuff we may see
/*   if ($author) $list=$listb; else $list=$lista;
   if ($list && ($nr_records < $max_menu_length) ) {
      $r=$db->Execute("SELECT author FROM pdbs WHERE id IN ($list)");
      $text=$r->GetMenu("author",$author,true,false,0,"style='width: 80%' $jscript");
      echo "<td style='width: 10%'>$text</td>\n";
   }
   else 
*/


   echo "<td><input type='text' name='author' value='$author' size=15></td>\n";
   echo "<td><input type='text' name='notes' value='$notes' size=15></td>\n";
   
   echo "<td>&nbsp;</td>\n";
   echo "<td><input type='text' name='pdbid' value='$pdbid' size=6></td>\n";
   echo "<td>&nbsp;</td>\n";

   echo "<td>";
   echo "<input type=\"submit\" name=\"search\" value=\"Search\">&nbsp;\n";
   echo "<input type=\"submit\" name=\"search\" value=\"Show All\">&nbsp;\n";
   echo "</td></tr>\n";

   echo "<tr>\n";
   echo "<th>Title</th>";
   echo "<th>Author(s)</th>";
   echo "<th>Notes</th>";
   echo "<th>Submitted by</th>";
   echo "<th>pdbid</th>";
   echo "<th>File</th>\n";
   echo "<th>Action</th>\n";
   echo "</tr>\n";

   $r=$db->PageExecute($pb_query,$num_p_r,$pb_curr_page);
   $rownr=1;
   // print all entries
   while (!($r->EOF) && $r) {
 
      // get results of each row
      $id = $r->fields["id"];
      $title = $r->fields["title"];
      $author="&nbsp;".$r->fields["author"];
      $notes= "&nbsp;".substr($r->fields["notes"],0,75)."...";
      $owner="&nbsp;".get_cell($db,"users","firstname","id",$r->fields["ownerid"])." ".get_cell($db,"users","lastname","id",$r->fields["ownerid"]);
      $pdbid="&nbsp;".$r->fields["pdbid"];
 
      // print start of row of selected group
      if ($rownr % 2)
         echo "<tr class='row_odd' align='center'>\n";
      else
         echo "<tr class='row_even' align='center'>\n";

      echo "<td>$title</td>\n";
      echo "<td>$author</td>\n";
      echo "<td>$notes</td>\n";
      echo "<td>$owner</td>\n";
      echo "<td>$pdbid</td>\n";
      $files=get_files($db,"pdbs",$id,3);
      echo "<td>";
      if ($files) 
         for ($i=0;$i<sizeof($files);$i++)
	    echo $files[$i]["link"];
      else
         echo "&nbsp;";
      echo "</td>\n";

      echo "<td align='center'>&nbsp;\n";
      echo "<input type=\"submit\" name=\"view_" . $id . "\" value=\"View\">\n";
      if (may_write($db,"pdbs",$id,$USER)) {
         echo "<input type=\"submit\" name=\"mod_" . $id . "\" value=\"Modify\">\n";
         $delstring = "<input type=\"submit\" name=\"del_" . $id . "\" value=\"Remove\" ";
         $delstring .= "Onclick=\"if(confirm('Are you sure the PDB $title ";
         $delstring .= "should be removed?')){return true;}return false;\">";                                           
         echo "$delstring\n";
      }
      echo "</td>\n";
      echo "</tr>\n";
   
      $r->MoveNext();
      $rownr+=1;
   }

   echo "</table>\n";

   // next/previous buttons
   next_previous_buttons($r);

   echo "</form>\n";

}

printfooter($db,$USER);

?>
