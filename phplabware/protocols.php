<?php
  
// protocols.php -  List, modify, delete and add protocols
// protocols.php - author: Nico Stuurman <nicost@soureforge.net>
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

// main include thingies
require("include.php");
require ("includes/db_inc.php");

// main global vars
$title .= "Protocols";
$fields="id,access,ownerid,magic,title,type1,type2,notes,date,lastmodby,lastmoddate";

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
function check_pr_data ($db,&$field_values) {
   if (!$field_values["title"]) {
      echo "<h3>Please enter a title for the protocol.</h3>";
      return false;
   }
   // When a new author was entered
   $firstname=$field_values["firstname"];
   $lastname=$field_values["lastname"];
   if ($firstname || $lastname) {
      // check if this entry exists already
      $r=$db->Execute("SELECT id FROM pr_type2 WHERE type='$firstname' AND typeshort='$lastname'");
      if ($r && !$r->EOF) {
         $field_values["type2"]=$r->fields["id"];
         return true;
      }
      $id=$db->GenID("pr_type2_id_seq");
      $db->Execute("INSERT INTO pr_type2 (id,type,typeshort) VALUES ('$id','".
           $field_values["firstname"]."','".$field_values["lastname"]."')");
      $field_values["type2"]=$id;
   }
   return true;
}


////
// !Prints a form with protocol stuff
// $id=0 for a new entry, otherwise it is the id
function add_pr_form ($db,$fields,$field_values,$id,$USER,$PHP_SELF,$system_settings) {
   if (!may_write($db,"protocols",$id,$USER))
      return false;
   global $db_type;

   // get values in a smart way
   $column=strtok($fields,",");
   while ($column) {
      ${$column}=$field_values[$column];
      $column=strtok(",");
   }
?>
<form method='post' id='protocolform' enctype='multipart/form-data' action='<?php echo $PHP_SELF?>?<?=SID?>'> 
<?php
   // generate a unique ID (magic) to avoid double uploads 
   if (!$magic)
      $magic=time();
   echo "<input type='hidden' name='magic' value='$magic'>\n";
   echo "<table border=0 align='center'>\n";
   if ($id) {
      echo "<tr><td colspan=5 align='center'><h3>Modify Protocol <i>$title</i></h3></td></tr>\n";
      echo "<input type='hidden' name='id' value='$id'>\n";
   }
   else
      echo "<tr><td colspan=5 align='center'><h3>New Protocol</h3></td></tr>\n";
   echo "<tr align='center'>\n";
   echo "<td colspan=2></td>\n";
   echo "<td>&nbsp;</td>\n<th>Category</th>\n";
   echo "</tr>\n";
   echo "<tr>\n";
   echo "<th>Title: <sup style='color:red'>&nbsp;*</sup></th>\n";
   echo "<td><input type='text' name='title' value='$title' size=60></td>\n";

   $r=$db->Execute("SELECT type,id FROM pr_type1 ORDER BY sortkey");
   $text=$r->GetMenu2("type1",$type1,true);
   echo "<td></td>\n<td>$text</td>\n";
   echo "</tr>\n";

   echo "<tr>\n";
   if ($db_type=="mysql") // mysql does not use the ansi SQL || operator
      $r=$db->Execute("SELECT CONCAT(type, ' ', typeshort),id  from pr_type2 ORDER by typeshort");
   else
      $r=$db->Execute("SELECT type || ' ' || typeshort,id  from pr_type2 ORDER by typeshort");
   $text=$r->GetMenu2("type2",$type2,true,false,0,"style='width: 80%'");
   echo "<th>Author:</th>\n<td>$text<br>";
   echo "Or: First<input type='text' size=8 name='firstname' value='$firstname'>\n";
   echo "Last<input type='text' size=12 name='lastname' value='$lastname'></td>\n";
   echo "</tr>\n";

   echo "<tr>";
   echo "<th>Notes: </th><td colspan=6><textarea name='notes' rows='5' cols='100%'>$notes</textarea></td>\n";
   echo "</tr>\n";
   
   $files=get_files($db,"protocols",$id);
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
   echo "<th>File Title:</th><td><input type='text' name='filetitle[]' value='$filetile' size=30></td><td>&nbsp;</td>\n";
   
   echo "</tr>\n";
   echo "</table></td>\n\n";
   echo "<td colspan=4>";
   show_access($db,"protocols",$id,$USER,$system_settings);
   echo "</td></tr>\n";
   
   echo "<tr>";
   if ($id)
      $value="Modify Protocol";
   else
      $value="Add Protocol";
   echo "<td colspan=7 align='center'><input type='submit' name='submit' value='$value'>\n";
   echo "&nbsp;&nbsp;<input type='submit' name='submit' value='Cancel'></td>\n";
   echo "</tr>\n";
   

   echo "</table></form>\n";
}

////
// !Shows a page with nice information on the protocol
function show_pr ($db,$fields,$id,$USER,$system_settings) {
   global $PHP_SELF;

   if (!may_read($db,"protocols",$id,$USER))
      return false;

   // get values 
   $r=$db->Execute("SELECT $fields FROM protocols WHERE id=$id");
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
   echo "<td></td><td></td>\n<th>Category</th>\n";
   echo "</tr>\n";
   echo "<tr>\n";
   echo "<th>Title: </th>\n";
   echo "<td colspan=2>$title</td>\n";
   $text=get_cell($db,"pr_type1","type","id",$type1);
   echo "<td></td><td align='center'>$text</td></tr>\n";
   
   echo "<tr>\n";
   echo "<th>Author: </th>\n";
   $r=$db->Execute("SELECT type,typeshort FROM pr_type2 WHERE id=$type2");
   echo "<td colspan=2>".$r->fields["type"]." ".$r->fields["typeshort"]."</td></tr>\n";

   echo "<tr>";
   $query="SELECT firstname,lastname,email FROM users WHERE id=$ownerid";
   $r=$db->Execute($query);
   if ($r->fields["email"]) {
      echo "<th>Submitted by: </th><td><a href='mailto:".$r->fields["email"]."'>";
      echo $r->fields["firstname"]." ".$r->fields["lastname"]."</a></td>\n";
   }
   else {
      echo "<th>Submitted by: </th><td>".$r->fields["firstname"]." ";
      echo $r->fields["lastname"] ."</td>\n";
   }
   echo "<td>&nbsp;</td>";
   $dateformat=get_cell($db,"dateformats","dateformat","id",$system_settings["dateformat"]);
   $date=date($dateformat,$date);
   echo "<th>Date entered: </th><td colspan=3>$date</td>\n";
   echo "</tr>\n";

   if ($lastmodby && $lastmoddate) {
      echo "<tr>";
      $query="SELECT firstname,lastname,email FROM users WHERE id=$lastmodby";
      $r=$db->Execute($query);
      if ($r->fields["email"]) {
         echo "<th>Last modified by: </th><td><a href='mailto:".$r->fields["email"]."'>";
         echo $r->fields["firstname"]." ".$r->fields["lastname"]."</a></td>\n";
      }
      else {
         echo "<th>Last modified by: </th><td>".$r->fields["firstname"]." ";
         echo $r->fields["lastname"] ."</td>\n";
      }
      echo "<td>&nbsp;</td>";
      $dateformat=get_cell($db,"dateformats","dateformat","id",$system_settings["dateformat"]);
      $lastmoddate=date($dateformat,$lastmoddate);
      echo "<th>Date modified: </th><td colspan=3>$lastmoddate</td>\n";
      echo "</tr>\n";
   }

   echo "<tr>";
   $notes=nl2br(htmlentities($notes));
   echo "<th>Notes: </th><td colspan=6>$notes</td>\n";
   echo "</tr>\n";

   $files=get_files($db,"protocols",$id);
   if ($files) {
      echo "<tr><th>Files:</th>\n<td colspan=5>";
      for ($i=0;$i<sizeof($files);$i++) {
         echo $files[$i]["link"]."&nbsp;&nbsp;(".$files[$i]["type"];
         echo " file)<br>\n";
      }
      echo "</tr>\n";
   }
   
   echo "<tr><th>Link:</th><td colspan=7><a href='$PHP_SELF?showid=$id&";
   echo SID;
   echo "'>".$system_settings["baseURL"].getenv("SCRIPT_NAME")."?showid=$id</a></td></tr>\n";

?>   
<form method='post' id='protocolview' action='<?php echo $PHP_SELF?>?<?=SID?>'> 
<?php
   echo "<tr>";
   echo "<td colspan=7 align='center'><input type='submit' name='submit' value='Dismiss'></td>\n";
   echo "</tr>\n";

   echo "</table></form>\n";
}

////
// !Tries to convert a MsWord file into html 
// When succesfull, the file is added to the database
function process_file($db,$fileid,$system_settings) {
   global $HTTP_POST_FILES,$HTTP_POST_VARS;
   $mimetype=get_cell($db,"files","mime","id",$fileid);
   if (!strstr($mimetype,"html")) {
      $word2html=$system_settings["word2html"];
      $filepath=file_path($db,$fileid);
      $temp=$system_settings["tmpdir"]."/".uniqid("file");
      `$word2html $filepath $temp`;
      if (@is_readable($temp)) {
         unset ($HTTP_POST_FILES);
         $r=$db->query ("SELECT filename,mime,title,tablesfk,ftableid FROM files WHERE id=$fileid");
         if ($r && !$r->EOF) {
            $filename=$r->fields("filename");
            // change .doc to .html in a lousy way
            $filename=str_replace(".doc",".htm",$filename); 
            $mime="text/html";
            $type=substr(strrchr($mime,"/"),1);
            $size=filesize($temp);
            $id=$db->GenID("files_id_seq");
            $query="INSERT INTO files (id,filename,mime,size,title,tablesfk,ftableid,type) VALUES ($id,'$filename','$mime','$size','".$r->fields("title")."','".$r->fields("tablesfk")."','".$r->fields("ftableid")."','$type')";
            if ($db->execute($query)) {
                $newloc=file_path($db,$id);
               `mv $temp $newloc`;
            }
            else
               unlink($temp); 
         }    
      }
   }
}

/*****************************BODY*******************************/

printheader($title);
navbar($USER["permissions"]);

// check if something should be modified, deleted or shown
while((list($key, $val) = each($HTTP_POST_VARS))) {
   if (substr($key, 0, 3) == "mod") {
      $modarray = explode("_", $key);
      $r=$db->Execute("SELECT $fields FROM protocols WHERE id=$modarray[1]"); 
      add_pr_form ($db,$fields,$r->fields,$modarray[1],$USER,$PHP_SELF,$system_settings);
      printfooter();
      exit();
   }
   // delete file and show protocol form
   if (substr($key, 0, 3) == "def") {
      $modarray = explode("_", $key);
      $filename=delete_file($db,$modarray[1],$USER);
      $id=$HTTP_POST_VARS["id"];
      if ($filename)
         echo "<h3 align='center'>Deleted file <i>$filename</i>.</h3>\n";
      else
         echo "<h3 align='center'>Failed to delete file <i>$filename</i>.</h3>\n";
      add_pr_form ($db,$fields,$HTTP_POST_VARS,$id,$USER,$PHP_SELF,$system_settings);
      printfooter();
      exit();
   }
   // show the record
   if (substr($key, 0, 4) == "view") {
      $modarray = explode("_", $key);
      show_pr($db,$fields,$modarray[1],$USER,$system_settings);
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
      del_type($db,$table,$modarray[3],"protocols");
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
   show_pr($db,$fields,$showid,$USER,$system_settings);
   printfooter();
   exit();
}

// when the 'Add' button has been chosen: 
if ($add)
   add_pr_form ($db,$fields,$field_values,0,$USER,$PHP_SELF,$system_settings);

else {
   // print header of table
   echo "<table border='1' align='center'>\n";
   echo "<caption>\n";
   // first handle addition of a new protocol
   if ($submit == "Add Protocol") {
      if (! (check_pr_data($db, $HTTP_POST_VARS) && $id=add ($db, "protocols",$fields,$HTTP_POST_VARS,$USER) ) ){
         echo "</caption>\n</table>\n";
         add_pr_form ($db,$fields,$HTTP_POST_VARS,0,$USER,$PHP_SELF,$system_settings);
         printfooter ();
         exit;
      }
      else {  
	 $fileid=upload_files($db,"protocols",$id,$USER,$system_settings);
         // insert stuff to deal with word/html files
         process_file($db,$fileid,$system_settings); 
         // to not interfere with search form 
         unset ($HTTP_POST_VARS);
	 // or we won't see the new record
	 unset ($HTTP_SESSION_VARS["pr_query"]);
      }
   }
   // then look whether it should be modified
   elseif ($submit=="Modify Protocol") {
      if (! (check_pr_data($db,$HTTP_POST_VARS) && modify ($db,"protocols",$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER)) ) {
         echo "</caption>\n</table>\n";
         add_pr_form ($db,$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER,$PHP_SELF,$system_settings);
         printfooter ();
         exit;
      }
      else { 
         if ($HTTP_POST_FILES["file"]["name"][0]) {
            // delete all existing file
            delete ($db,"protocols",$HTTP_POST_VARS["id"],$USER,true);
            $fileid=upload_files($db,"protocols",$HTTP_POST_VARS["id"],$USER,$system_settings);
            process_file($db,$fileid,$system_settings);
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
            delete ($db, "protocols", $delarray[1], $USER);
         }
      }
   } 


   echo "</caption>\n";
   // print form
?>
<form name='pr_form' method='post' action='<?php echo $PHP_SELF?>?<?=SID?>'>  
<?php

   if ($search=="Show All") {
      $num_p_r=$HTTP_POST_VARS["num_p_r"];
      unset ($HTTP_POST_VARS);
      $curr_page=1;
      session_unregister("pr_query");
   }
   $column=strtok($fields,",");
   while ($column) {
      ${$column}=$HTTP_POST_VARS[$column];
      $column=strtok(",");
   }

   // prepare search SQL statement
   $whereclause=may_read_SQL ($db,"protocols",$USER);
   if ($search=="Search")
      $pr_query=search("protocols",$fields,$HTTP_POST_VARS," id IN ($whereclause) ORDER BY title");
   elseif (session_is_registered ("pr_query") && isset($HTTP_SESSION_VARS["pr_query"]))
      $pr_query=$HTTP_SESSION_VARS["pr_query"];
   else
      $pr_query = "SELECT $fields FROM protocols WHERE id IN ($whereclause) ORDER BY date DESC";
   $HTTP_SESSION_VARS["pr_query"]=$pr_query;   
   session_register("pr_query");
   // row with search form
   echo "<tr align='center'>\n";
   // javascript that submit the form with the search button when a selects was chosen
   $jscript="onChange='document.pr_form.searchj.value=\"Search\"; document.pr_form.submit()'";
   echo "<input type='hidden' name='searchj' value=''>\n";
   //$jscript="onChange='alert(\"You clicked wrong\")'";
   // get a list with ids we may see
   $r=$db->Execute($pr_query);
   $lista=make_SQL_csf ($r,false,"id",$nr_records);
   // and a list with all records we may see
   $listb=may_read_SQL($db,"protocols",$USER);
   // show title we may see, when too many, revert to text box
   if ($title) $list=$listb; else $list=$lista;
   if ($list && ($nr_records < $max_menu_length) ) {
      $r=$db->Execute("SELECT title FROM protocols WHERE id IN ($list)");
      $text=$r->GetMenu("title",$title,true,false,0,"style='width: 80%' $jscript");
      echo "<td style='width: 10%'>$text</td>\n";
   }
   else
      echo "<td><input type='text' name='title' value='$title' size=8></td>\n";

   // show a list with authors having stuff we may see
   echo "<td style='width: 10%'>\n";
   if ($USER["permissions"] & $LAYOUT) {
      echo "<a href='$PHP_SELF?edit_type=pr_type2&";
      echo SID;
      echo "'>Edit Authors</a><br>\n";
   }
   if ($type2) $list=$listb; else $list=$lista;
   $r=$db->Execute("SELECT type2 FROM protocols WHERE id IN ($list)");
   $list2=make_SQL_ids($r,false,"type2");
   if ($list2) {
      if ($db_type=="mysql") // mysql does not use the ansi SQL || operator
         $r=$db->Execute("SELECT CONCAT(type, ' ', typeshort),id  from pr_type2 WHERE id IN ($list2) ORDER by typeshort");
      else
         $r=$db->Execute("SELECT type || ' ' || typeshort,id  from pr_type2 WHERE id IN ($list2) ORDER by typeshort");
      $text=$r->GetMenu2("type2",$type2,true,false,0,"style='width: 80%' $jscript");
   }
   else
      $text="&nbsp;";
   echo "$text</td>\n";

   echo "<td><input type='text' name='notes' value='$notes' size=8></td>\n";

   // print link to edit table 'Categories'
   echo "<td style='width: 10%'>";
   if ($USER["permissions"] & $LAYOUT) {
      echo "<a href='$PHP_SELF?edit_type=pr_type1&";
      echo SID;
      echo "'>Edit Categories</a><br>\n";
   }
   // print category drop-down menu
   if ($type1) $list=$listb; else $list=$lista;
   $r=$db->Execute("SELECT type1 FROM protocols WHERE id IN ($list)");
   $list2=make_SQL_ids($r,false,"type1");
   if ($list2) {
      $r=$db->Execute("SELECT typeshort,id FROM pr_type1 WHERE id IN ($list2)");
      $text=$r->GetMenu2("type1",$type1,true,false,0,"style='width: 80%' $jscript");
      echo "$text</td>\n";
   }
   else
      echo "&nbsp;</td>\n";

   echo "<td>&nbsp;</td>";
   echo "<td><input type=\"submit\" name=\"search\" value=\"Search\">&nbsp;";
   echo "<input type=\"submit\" name=\"search\" value=\"Show All\"></td>";
   echo "</tr>\n";

   echo "<tr>\n";
   echo "<th>Title</th>";
   echo "<th>Author</th>";
   echo "<th>Notes</th>\n";
   echo "<th>Category</th>\n";
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
   if (!isset($pr_curr_page))
      $pr_curr_page=$HTTP_SESSION_VARS["pr_curr_page"];
   if (isset($HTTP_POST_VARS["next"]))
      $pr_curr_page+=1;
   if (isset($HTTP_POST_VARS["previous"]))
      $pr_curr_page-=1;
   if ($curr_page<1)
      $pr_curr_page=1;
   $HTTP_SESSION_VARS["pr_curr_page"]=$pr_curr_page; 
   session_register("pr_curr_page");

   $r=$db->PageExecute($pr_query,$num_p_r,$pr_curr_page);
   $rownr=1;
   // print all entries
   while (!($r->EOF) && $r) {
 
      // get results of each row
      $id = $r->fields["id"];
      $title = $r->fields["title"];
      $at1=get_cell($db,"pr_type1","type","id",$r->fields["type1"]);
      $notes = $r->fields["notes"];
 
      // print start of row of selected group
      if ($rownr % 2)
         echo "<tr class='row_odd' align='center'>\n";
      else
         echo "<tr class='row_even' align='center'>\n";

      echo "<td>$title</td>\n";
      $author=get_cell($db,"pr_type2","type","id",$r->fields["type2"]);
      $author.= "&nbsp;".get_cell($db,"pr_type2","typeshort","id",$r->fields["type2"]);
      echo "<td>$author</td>\n";
      if ($notes)
         echo "<td>yes</td>\n";
      else
         echo "<td>no</td>\n";
      echo "<td>&nbsp;$at1</td>\n";
      $files=get_files($db,"protocols",$id,3);
      echo "<td>";
      if ($files) 
         for ($i=0;$i<sizeof($files);$i++)
	    echo $files[$i]["link"];
      else
         echo "&nbsp;";
      echo "</td>\n";

      echo "<td align='center'>&nbsp;\n";
      echo "<input type=\"submit\" name=\"view_" . $id . "\" value=\"View\">\n";
      if (may_write($db,"protocols",$id,$USER)) {
         echo "<input type=\"submit\" name=\"mod_" . $id . "\" value=\"Modify\">\n";
         $delstring = "<input type=\"submit\" name=\"del_" . $id . "\" value=\"Remove\" ";
         $delstring .= "Onclick=\"if(confirm('Are you sure the protocol $title ";
         $delstring .= "should be removed?')){return true;}return false;\">";                                           
         echo "$delstring\n";
      }
      echo "</td>\n";
      echo "</tr>\n";
   
      $r->MoveNext();
      $rownr+=1;
   }

   // Add Protocol button
   if (may_write($db,"protocols",false,$USER)) {
      echo "<tr><td colspan=7 align='center'>";
      echo "<input type=\"submit\" name=\"add\" value=\"Add Protocol\">";
      echo "</td></tr>";
   }

   // next/previous buttons
   echo "<tr><td colspan=1 align='center'>";
   if ($r && !$r->AtFirstPage())
      echo "<input type=\"submit\" name=\"previous\" value=\"Previous\"></td>\n";
   else
      echo "&nbsp;</td>\n";
   echo "<td colspan=4 align='center'>";
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
