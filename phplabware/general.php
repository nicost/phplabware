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
$getvars="tablename,showid,edit_type,add";
globalize_vars($get_vars, $HTTP_GET_VARS);
$post_vars = "add,submit,search,searchj";
globalize_vars($post_vars, $HTTP_POST_VARS);

if ($searchj)  $search="Search";
$httptitle .=$tablename;

/*****************************BODY*******************************/
printheader($httptitle);
navbar($USER["permissions"]);

// find id associated with table
if (!$edit_type) {
   $r=$db->Execute("SELECT id,shortname FROM tableoftables WHERE tablename='$tablename'");
   $id=$r->fields["id"];
   $tableshort=$r->fields["shortname"];
   if (!$id) {
      echo "<h3 align='center'> Table: <i>$tablename</i> does not exist.</h3>";
      printfooter();
      exit();
   }
   $real_tablename=$tablename."_".$id;
   $table_desname=$real_tablename."_desc";
   $queryname=$tableshort."_query";
   $pagename=$tableshort."_curr_page";
   // read all fields in from the description file
   $fields=comma_array_SQL($db,$table_desname,label);
}

// check if something should be modified, deleted or shown
while((list($key, $val) = each($HTTP_POST_VARS))) {	
   // display form with information regarding the record to be changed
   if (substr($key, 0, 3) == "mod") {
      $modarray = explode("_", $key);
      $r=$db->Execute("SELECT $fields FROM $real_tablename WHERE id=$modarray[1]"); 
      add_g_form($db,$fields,$r->fields,$modarray[1],$USER,$PHP_SELF,$system_settings,$real_tablename,$tablename,$table_desname);
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
      add_g_form ($db,$fields,$HTTP_POST_VARS,$id,$USER,$PHP_SELF,$system_settings,$real_tablename,$tablename,$table_desname);
      printfooter();
      exit();
   }
   // show the record
   if (substr($key, 0, 4) == "view") {
      $modarray = explode("_", $key);
      show_g($db,$fields,$modarray[1],$USER,$system_settings,$tablename,$real_tablename,$table_desname);
      printfooter();
      exit();
   }

////////////This is the modification of the type variables
   if (substr($key, 0, 7) == "addtype") {
      $modarray = explode("_", $key);
      $table=$modarray[1]."_".$modarray[2]."_".$modarray[3];
      include("includes/type_inc.php");
      add_type($db,$table);
      show_type($db,$table,"",$tablename);	
      printfooter();
      exit();
   }
   if (substr($key, 0, 6) == "mdtype") {
      $modarray = explode("_", $key);
      $table=$modarray[1]."_".$modarray[2]."_".$modarray[3];
      include("includes/type_inc.php");
      mod_type($db,$table,$modarray[4]);
      show_type($db,$table,"",$tablename);
      printfooter();
      exit();
   }
   if (substr($key, 0, 6) == "dltype") {
      $modarray = explode("_", $key);
      $table=$modarray[1]."_".$modarray[2]."_".$modarray[3];
      include("includes/type_inc.php");
      del_type($db,$table,$modarray[4],$real_tablename);
      show_type($db,$table,"",$tablename);
      printfooter();
      exit();
   }
}

if ($edit_type && ($USER["permissions"] & $LAYOUT)) {
   include("includes/type_inc.php");
   $assoc_name=get_cell($db,$table_desname,label,associated_table,$edit_type);
   show_type($db,$edit_type,$assoc_name,$tablename);
   printfooter();
   exit();

}

// provide a means to hyperlink directly to a record
if ($showid) {
   show_g($db,$fields,$showid,$USER,$system_settings,$tablename,$real_tablename,$table_desname);
   printfooter();
   exit();
}

/*
// when create new table has been chosen
if ($createnew){create_new_table($db);}
*/

// when the 'Add' button has been chosen: 
if ($add) {
   add_g_form($db,$fields,$field_values,0,$USER,$PHP_SELF,$system_settings,$real_tablename,$tablename,$table_desname);
   }
else { 
    // first handle addition of a new record
   if ($submit == "Add Record") {
      if (!(check_g_data($db, $HTTP_POST_VARS, $table_desname) && 
            $id=add($db,$real_tablename,$fields,$HTTP_POST_VARS,$USER) ) )
      	{
         add_g_form($db,$fields,$HTTP_POST_VARS,0,$USER,$PHP_SELF,$system_settings,$real_tablename,$tablename, $table_desname);
         printfooter ();
         exit;
      }
      else {  
	 $fileid=upload_files($db,$tablename,$id,$USER,$system_settings);
      // insert stuff to deal with word/html files
         process_file($db,$fileid,$system_settings); 
         // to not interfere with search form 
         unset ($HTTP_POST_VARS);
	 // or we won't see the new record
	 unset ($HTTP_SESSION_VARS["{$queryname}"]);
      }
   }
   // then look whether it should be modified
   elseif ($submit=="Modify Record") {
      if (! (check_g_data($db,$HTTP_POST_VARS,$table_desname) && modify($db,$real_tablename,$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER)) ) {
         add_g_form ($db,$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER,$PHP_SELF,$system_settings,$real_tablename,$tablenmae,$table_desname);
         printfooter ();
         exit;
      }
      else { 
         if ($HTTP_POST_FILES["file"]["name"][0]) {
            // delete all existing file
            delete ($db,$real_tablename,$HTTP_POST_VARS["id"],$USER,true);
            $fileid=upload_files($db,$tablename,$HTTP_POST_VARS["id"],$USER,$system_settings);
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
            delete ($db, $real_tablename, $delarray[1], $USER);
         }
      }
   } 

   if ($search=="Show All") {
      $num_p_r=$HTTP_POST_VARS["num_p_r"];
      unset ($HTTP_POST_VARS);
      ${$pagename}=1;
      unset ($HTTP_SESSION_VARS[$queryname]);
      session_unregister($queryname);
   }
   $column=strtok($fields,",");
   while ($column) {
      ${$column}=$HTTP_POST_VARS[$column];
      $column=strtok(",");
   }

   // paging stuff
   $num_p_r=paging($num_p_r,$USER);

   // get current page
   ${$pagename}=current_page(${$pagename},$tableshort);
 
   // prepare the search statement and remember it
   $fields="id,".$fields;
   ${$queryname}=make_search_SQL($db,$real_tablename,$tableshort,$fields,$USER,$search);
   // loop through all entries for next/previous buttons
   $r=$db->PageExecute(${$queryname},$num_p_r,${$pagename});
   while (!($r->EOF) && $r) {
      $r->MoveNext();
   }

   // get variables for links 
   $sid=SID;
   if ($sid) $sid="&".$sid;
   if ($tablename) $sid.="&tablename=$tablename";

   // print form;
   $dbstring=$PHP_SELF."?"."tablename=$tablename&";
   echo "<form name=g_form method='post' id='generalform' enctype='multipart/form-data' action='$PHP_SELF?$sid'>\n";

   // Need to put in a singular name
   echo "<table border=0 width='50%' align='center'>\n<tr>\n";
   echo "<td align='center'><B>$tablename</B><br>";
   if (may_write($db,$real_tablename,false,$USER)) 
      echo "<p><a href='$PHP_SELF?&add=Add&tablename=$tablename&<?=SID?>'>Add Record</a></td>\n"; 
   echo "</tr>\n</table>\n";
   next_previous_buttons($r,true,$num_p_r);

   // print header of table
   echo "<table border='1' align='center'>\n";

   // get a list with ids we may see
   $r=$db->Execute(${$queryname});
   $lista=make_SQL_csf ($r,false,"id",$nr_records);

   // and a list with all records we may see
   $listb=may_read_SQL($db,$real_tablename,$USER);

   //  get a list of all fields that are displayed in the table
   $Fieldscomma=comma_array_SQL_where($db,$table_desname,"label","display_table","Y");
   $Allfields=getvalues($db,$real_tablename,$table_desname,$Fieldscomma,display_table,"Y");	
   
   // javascript to automatically execute search when pulling down 
   $jscript="onChange='document.g_form.searchj.value=\"Search\"; document.g_form.submit()'";

   // row with search form
   echo "<tr align='center'>\n";
   echo "<input type='hidden' name='searchj' value=''>\n";

   foreach($Allfields as $nowfield)  {
      if ($HTTP_POST_VARS[$nowfield[name]]) 
         $list=$listb; 
      else 
         $list=$lista;   
      if ($nowfield[datatype]== "link")
         echo "<td style='width: 10%'>&nbsp;</td>\n";
      if ($nowfield[datatype]== "text") {
         // show titles we may see, when too many, revert to text box
         if ($list && ($nr_records < $max_menu_length) )  {
  	     $r=$db->Execute("SELECT $nowfield[name] FROM $real_tablename WHERE id IN ($list)");
             $text=$r->GetMenu("$nowfield[name]",$HTTP_POST_VARS[$nowfield[name]],true,false,0,"style='width: 80%' $jscript");
             echo "<td style='width: 10%'>$text</td>\n";
         }
	 else
    	    echo  " <td style='width: 10%'><input type='text' name='$nowfield[name]' value='".$HTTP_POST_VARS[$nowfield[name]]."'size=8></td>\n";
      }
      if ($nowfield[datatype]== "textlong")
    	    echo  " <td style='width: 10%'><input type='text' name='$nowfield[name]' value='".$HTTP_POST_VARS[$nowfield[name]]."'size=8></td>\n";
        // echo "<td style='width: 10%'>&nbsp;</td>\n";
      if ($nowfield[datatype]== "pulldown") {
         echo "<td style='width: 10%'>";
         if ($USER["permissions"] & $LAYOUT)  {
            echo "<a href='$PHP_SELF?tablename=$tablename&edit_type=$nowfield[ass_t]&<?=SID?>";
            echo "'>Edit $nowfield[name]</a><br>\n";
         }	 		 			
         $r=$db->Execute("SELECT $nowfield[name] FROM $real_tablename WHERE id IN ($list)");
         $list2=make_SQL_ids($r,false,"$nowfield[name]");
         if ($list2) { 
            if ($nowfield[name]=="authors") {
               if ($db_type=="mysql") // mysql does not use the ansi SQL || operator
                  $r=$db->Execute("SELECT CONCAT(type, ' ', typeshort),id from $nowfield[ass_t] WHERE id IN ($list2) ORDER by typeshort");
               else
                  $r=$db->Execute("SELECT type || ' ' || typeshort,id from $nowfield[ass_t] WHERE id IN ($list) ORDER by typeshort");
            }
            else 
               $r=$db->Execute("SELECT typeshort,id from $nowfield[ass_t] WHERE id IN ($list2) ORDER by typeshort");
    		
            $text=$r->GetMenu2("$nowfield[name]","",true,false,0,"style='width: 80%' $jscript");   
    	    echo "$text</td>\n";
         }  
      }
      if ($nowfield[datatype] == "file")
         echo "<td style='width: 10%'>&nbsp;</td>";
   }	 
   echo "<td><input type=\"submit\" name=\"search\" value=\"Search\">&nbsp;";
   echo "<input type=\"submit\" name=\"search\" value=\"Show All\"></td>";
   echo "</tr>\n";


   display_midbar($Fieldscomma);
   display_table_info($db,$tablename,$real_tablename,$table_desname,$Fieldscomma,${$queryname},$num_p_r,${$pagename});
   printfooter($db,$USER);
}
?>
