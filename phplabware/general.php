<?php
  
// general.php -  List, modify, delete and add to general databases
// general.php - author: Nico Stuurman <nicost@soureforge.net>
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

/// main include thingies
require("include.php");
require("includes/db_inc.php");
require("includes/general_inc.php");

// register variables
$getvars="dbname,showid,edit_type,add";
globalize_vars($get_vars, $HTTP_GET_VARS);
$post_vars = "add,submit,search,searchj";
globalize_vars($post_vars, $HTTP_POST_VARS);

if ($searchj)  $search="Search";
$DBNAME=$dbname;
$DB_DESNAME=$DBNAME; $DB_DESNAME.="_desc";
$httptitle .=$DBNAME;

// read all fields in from the description file
$fields=comma_array_SQL($db,$DB_DESNAME,label);

/*****************************BODY*******************************/
printheader($httptitle);
navbar($USER["permissions"]);

// check if something should be modified, deleted or shown
while((list($key, $val) = each($HTTP_POST_VARS))) {	
   // display form with information regarding the record to be changed
   if (substr($key, 0, 3) == "mod") {
      $modarray = explode("_", $key);
      $r=$db->Execute("SELECT $fields FROM $DBNAME WHERE id=$modarray[1]"); 
      add_g_form($db,$fields,$r->fields,$modarray[1],$USER,$PHP_SELF,$system_settings,$DB_DESNAME);
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
      add_g_form ($db,$fields,$HTTP_POST_VARS,$id,$USER,$PHP_SELF,$system_settings,$DB_DESNAME);
      printfooter();
      exit();
   }
   // show the record
   if (substr($key, 0, 4) == "view") {
      $modarray = explode("_", $key);
      show_g($db,$fields,$modarray[1],$USER,$system_settings,$DBNAME,$DB_DESNAME);
      printfooter();
      exit();
   }

////////////This is the modification of the type variables
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
      del_type($db,$table,$modarray[3],$DBNAME);
      show_type($db,$table,"");
      printfooter();
      exit();
   }
}

if ($edit_type && ($USER["permissions"] & $LAYOUT)) {
   include("includes/type_inc.php");
   $assoc_name=get_cell($db,$DB_DESNAME,label,associated_table,$edit_type);
   show_type($db,$edit_type,$assoc_name);
   printfooter();
   exit();

}

// provide a means to hyperlink directly to a record
if ($showid) {
   show_g($db,$fields,$showid,$USER,$system_settings,$DBNAME,$DB_DESNAME);
   printfooter();
   exit();
}

// when the create new table has been chosen
if ($createnew){create_new_table($db,$DBNAME);}

// when the 'Add' button has been chosen: 
if ($add)
	{add_g_form($db,$fields,$field_values,0,$USER,$PHP_SELF,$system_settings,$DB_DESNAME);}
else { 
    // first handle addition of a new record
   if ($submit == "Add Record") 
   	{
      if (!(check_g_data($db, $HTTP_POST_VARS) && $id=add($db,$DBNAME,$fields,$HTTP_POST_VARS,$USER) ) )
      	{
         add_g_form($db,$fields,$HTTP_POST_VARS,0,$USER,$PHP_SELF,$system_settings,$DB_DESNAME);
         printfooter ();
         exit;
      }
      else {  
	 $fileid=upload_files($db,$DBNAME,$id,$USER,$system_settings);
      // insert stuff to deal with word/html files
         process_file($db,$fileid,$system_settings); 
         // to not interfere with search form 
         unset ($HTTP_POST_VARS);
	 // or we won't see the new record
	 unset ($HTTP_SESSION_VARS["pr_query"]);
      }
   }
   // then look whether it should be modified
   elseif ($submit=="Modify Record") {
      if (! (check_g_data($db,$HTTP_POST_VARS) && modify($db,$DBNAME,$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER)) ) {
         add_g_form ($db,$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER,$PHP_SELF,$system_settings,$DB_DESNAME);
         printfooter ();
         exit;
      }
      else { 
         if ($HTTP_POST_FILES["file"]["name"][0]) {
            // delete all existing file
            delete ($db,$DBNAME,$HTTP_POST_VARS["id"],$USER,true);
            $fileid=upload_files($db,$DBNAME,$HTTP_POST_VARS["id"],$USER,$system_settings);
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
            delete ($db, $DBNAME, $delarray[1], $USER);
         }
      }
   } 

   if ($search=="Show All") {
      $num_p_r=$HTTP_POST_VARS["num_p_r"];
      unset ($HTTP_POST_VARS);
      $pr_curr_page=1;
      session_unregister("pr_query");
   }
   $column=strtok($fields,",");
   while ($column) {
      ${$column}=$HTTP_POST_VARS[$column];
      $column=strtok(",");
   }

   // paging stuff
   $num_p_r=paging($num_p_r,$USER);

   // get current page
   $pr_curr_page=current_page($pr_curr_page,"pr");
 
   // prepare the search statement and remember it
   $pr_query=make_search_SQL($db,$DBNAME,$DBNAME,$fields,$USER,$search);

   // loop through all entries for next/previous buttons
   $r=$db->PageExecute($pr_query,$num_p_r,$pr_curr_page);
   while (!($r->EOF) && $r) {
      $r->MoveNext();
   }

   // print form;
   $dbstring=$PHP_SELF;$dbstring.="?";$dbstring.="dbname=$DBNAME&";
   echo "<form method='post' id='protocolform' enctype='multipart/form-data' action='$dbstring";
	?><?=SID?>'><?php

   // row with action links
   $sid=SID;
   if ($sid) $sid="&".$sid;
   if ($DBNAME) $sid.="&dbname=$DBNAME";
   
   if (may_write($db,$DBNAME,false,$USER)) 

   // get a list with ids we may see
   $r=$db->Execute($pr_query);
   $lista=make_SQL_csf ($r,false,"id",$nr_records);

   // and a list with all records we may see
   $listb=may_read_SQL($db,$DBNAME,$USER);
   if ($title) $list=$listb; else $list=$lista;   
   
   // Need to put in a singular name
   echo "<table border=0 width='50%' align='center'>\n<tr>\n";
   echo "<td align='center'><B>$DBNAME</B><br><p><a href='$PHP_SELF?&add=Add record&dbname=$DBNAME&<?=SID?>'>Add Record</a></td>\n"; echo "</table>\n";
   next_previous_buttons($r,true,$num_p_r);

   // print header of table
   echo "<table border='1' align='center'>\n";



//   get a list of all fields that are displayed in the table
$Fieldscomma=comma_array_SQL_where($db,$DB_DESNAME,label,display_table,Y);
$Allfields=getvalues($db,$DBNAME,$DB_DESNAME,$Fieldscomma,display_table,"Y");	
display_tablehead($Allfields,$list);
display_midbar($Fieldscomma);
display_table_info($Fieldscomma,$pr_query,$num_p_r,$pr_curr_page);
printfooter($db,$USER);
}
?>
