<?php
  
// general.php -  List, modify, delete and add to general databases
// general.php - author: Nico Stuurman <nicost@soureforge.net>
  /***************************************************************************
  * This script displays a table with protocols in phplabware.               *
  *                                                                          *
  * Copyright (c) 2002 by Ethan Garner,Nico Stuurman<nicost@sf.net           *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

/// main include thingies
require("include.php");

// find id associated with table
$r=$db->Execute("SELECT id,shortname,tablename,real_tablename FROM tableoftables WHERE tablename='$HTTP_GET_VARS[tablename]'");
$tableid=$r->fields["id"];
if (!$tableid) {
   printheader($httptitle);
   navbar($USER["permissions"]);
   echo "<h3 align='center'> Table: <i>$HTTP_GET_VARS[tablename]</i> does not exist.</h3>";
   printfooter();
   exit();
}
$tableshort=$r->fields["shortname"];
$real_tablename=$r->fields["real_tablename"];
$table_desname=$real_tablename."_desc";
$queryname=$tableshort."_query";
$pagename=$tableshort."_curr_page";

// load plugin php code if it has been defined 
$plugin_code=get_cell($db,"tableoftables","plugin_code","id",$tableid);
if ($plugin_code)
   @include($plugin_code);

require("includes/db_inc.php");
require("includes/general_inc.php");

// register variables
$get_vars="tablename,md,showid,edit_type,add";
globalize_vars($get_vars, $HTTP_GET_VARS);
$post_vars = "add,md,submit,search,searchj,serialsortdirarray";
globalize_vars($post_vars, $HTTP_POST_VARS);
// Mode can be changed through a get var and is perpetuated through post vars
if ($HTTP_GET_VARS["md"])
   $md=$HTTP_GET_VARS["md"];

// check if sortup or sortdown arrow has been pressed
foreach($HTTP_POST_VARS as $key =>$value) {
   list($testkey,$testvalue)=explode("_",$key,2);
   // images add _x and _y, remove these here
   if ($testkey=="sortup"){
      $testvalue=substr($testvalue,0,-2);
      $sortup=$testvalue;
   }
   if ($testkey=="sortdown") {
      $testvalue=substr($testvalue,0,-2);
      $sortdown=$testvalue;
   }
} 
reset ($HTTP_POST_VARS);
if ($searchj || $sortup || $sortdown)
   $search="Search";

$httptitle .=$tablename;

/*****************************BODY*******************************/
printheader($httptitle);
navbar($USER["permissions"]);


// read all fields in from the description file
// $fields_label=comma_array_SQL($db,$table_desname,label);
$fields=comma_array_SQL($db,$table_desname,columnname);
$fields_table=comma_array_SQL($db,$table_desname,columnname,"WHERE display_table='Y'");

// check wether user may see this table
if (!may_see_table($db,$USER,$tableid)) {
   echo "<h3 align='center'>These data are not for you.  Sorry;(</h3>\n";
   printfooter();
   exit();
}

// check if something should be modified, deleted or shown
while((list($key, $val) = each($HTTP_POST_VARS))) {	
   // display form with information regarding the record to be changed
   if (substr($key, 0, 3) == "mod") {
      $modarray = explode("_", $key);
      $r=$db->Execute("SELECT $fields FROM $real_tablename WHERE id=$modarray[1]"); 
      add_g_form($db,$fields,$r->fields,$modarray[1],$USER,$PHP_SELF,$system_settings,$real_tablename,$tableid,$table_desname);
      printfooter();
      exit();
   }
   if (substr($key, 0, 3) == "chg") {
      $chgarray = explode("_", $key);
      if ($val=="Change") {
         $Fieldscomma=comma_array_SQL_where($db,$table_desname,"columnname","display_table","Y");
         $Fieldsarray=explode(",",$Fieldscomma);
         reset($HTTP_POST_VARS);
         while((list($key, $val) = each($HTTP_POST_VARS))) {	
            $testarray=explode("_",$key);
            if ( ($testarray[1]==$chgarray[1]) && (in_array ($testarray[0],$Fieldsarray))) 
               $change_values[$testarray[0]]=$val;  
         }
         if(check_g_data ($db,$change_values,$table_desname))
            modify($db,$real_tablename,$Fieldscomma,$change_values,$chgarray[1],$USER,$tableid); 
         break;
      }
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
      add_g_form ($db,$fields,$HTTP_POST_VARS,$id,$USER,$PHP_SELF,$system_settings,$real_tablename,$tableid,$table_desname);
      printfooter();
      exit();
   }
   // show the record
   if (substr($key, 0, 4) == "view") {
      $modarray = explode("_", $key);
      show_g($db,$fields,$modarray[1],$USER,$system_settings,$tableid,$real_tablename,$table_desname);
      printfooter();
      exit();
   }

// Add/modify/delete pulldown menu items 
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
   show_g($db,$fields,$showid,$USER,$system_settings,$tableid,$real_tablename,$table_desname);
   printfooter();
   exit();
}

// when the 'Add' button has been chosen: 
if ($add) {
   add_g_form($db,$fields,$field_values,0,$USER,$PHP_SELF,$system_settings,$real_tablename,$tableid,$table_desname);
   }
else { 
    // first handle addition of a new record
   if ($submit == "Add Record") {
      if (!(check_g_data($db, $HTTP_POST_VARS, $table_desname) && 
            $id=add($db,$real_tablename,$fields,$HTTP_POST_VARS,$USER,$tableid) ) )
      	{
         add_g_form($db,$fields,$HTTP_POST_VARS,0,$USER,$PHP_SELF,$system_settings,$real_tablename,$tableid, $table_desname);
         printfooter ();
         exit;
      }
      else {  
         $rb=$db->Execute("SELECT id FROM $table_desname WHERE datatype='file'");
         while (!$rb->EOF) {
            $columnids[]=$rb->fields["id"];
            $rb->MoveNext(); 
         }
	 $fileid=upload_files($db,$tableid,$id,$columnids,$USER,$system_settings);
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
      if (! (check_g_data($db,$HTTP_POST_VARS,$table_desname) && modify($db,$real_tablename,$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER,$tableid)) ) {
         add_g_form ($db,$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER,$PHP_SELF,$system_settings,$real_tablename,$tableid,$table_desname);
         printfooter ();
         exit;
      }
      else { 
         if ($HTTP_POST_FILES["file"]["name"][0]) {
            // delete all existing file
            delete ($db,$tableid,$HTTP_POST_VARS["id"],$USER,true);
            $rb=$db->Execute("SELECT id FROM $table_desname WHERE datatype='file' ORDER BY sortkey");
            while (!$rb->EOF) {
               $columnids[]=$rb->fields["id"];
               $rb->MoveNext(); 
            }
            $fileid=upload_files($db,$tableid,$HTTP_POST_VARS["id"],$columnids,$USER,$system_settings);
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
            delete ($db,$tableid,$delarray[1], $USER);
         }
      }
   } 

   if ($search=="Show All") {
      $num_p_r=$HTTP_POST_VARS["num_p_r"];
      unset ($HTTP_POST_VARS);
      ${$pagename}=1;
      unset ($HTTP_SESSION_VARS[$queryname]);
      unset ($serialsortdirarray);
      session_unregister($queryname);
   }
   $column=strtok($fields,",");
   while ($column) {
      ${$column}=$HTTP_POST_VARS[$column];
      $column=strtok(",");
   }
 
   // sort stuff
   $sortdirarray=unserialize(stripslashes($serialsortdirarray));
   $sortstring=sortstring($sortdirarray,$sortup,$sortdown);

   // paging stuff
   $num_p_r=paging($num_p_r,$USER);

   // get current page
   ${$pagename}=current_page(${$pagename},$tableshort);
   // get a list with all records we may see, create temp table tempb
   $listb=may_read_SQL($db,$real_tablename,$tableid,$USER,"tempb");

   // prepare the search statement and remember it
   $fields_table="id,".$fields_table;

   ${$queryname}=make_search_SQL($db,$real_tablename,$tableshort,$tableid,$fields_table,$USER,$search,$sortstring,$listb["sql"]);
   $r=$db->Execute(${$queryname});

   // set variables needed for paging
   $numrows=$r->RecordCount();
   // work around bug in adodb/mysql
   $r->Move(1);
   if (${$pagename} < 2)
      $rp->AtFirstPage=true;
   else
      $rp->AtFirstPage=false;
   if ( ( (${$pagename}) * $num_p_r) >= $numrows)
      $rp->AtLastPage=true;
   else
      $rp->AtLastPage=false;
   // protect against pushing the reload button while at the last page
   if ( ((${$pagename}-1) * $num_p_r) >=$numrows)
      ${$pagename} -=1; 

   // get variables for links 
   $sid=SID;
   if ($sid) $sid="&".$sid;
   if ($tablename) $sid.="&tablename=$tablename";

   // print form;
   $dbstring=$PHP_SELF."?"."tablename=$tablename&";
   echo "<form name=g_form method='post' id='generalform' enctype='multipart/form-data' action='$PHP_SELF?$sid'>\n";
   echo "<input type='hidden' name='md' value='$md'>\n";

   echo "<table border=0 width='50%' align='center'>\n<tr>\n";
   
   // variable md containd edit/view mode setting.  Propagated as post var to remember state.  md can only be changed as a get variable
   $modetext="<a href='$PHP_SELF?tablename=$tablename&md=";
 
   $may_write=may_write($db,$tableid,false,$USER);
   if ($md=="edit") {
      $tabletext="Edit Table ";
      if ($may_write)
         $modetext.="view&".SID."'>(to view mode)</a>\n";
      else
         $modetext="";
   }
   else {
      $tabletext="View Table ";
      $modetext.="edit'>(to edit mode)</a>\n";
   }
   echo "<td align='center'>$tabletext <B>$tablename</B> $modetext<br>";
   if ($may_write)
      echo "<p><a href='$PHP_SELF?&add=Add&tablename=$tablename&".SID."'>Add Record</a></td>\n"; 
   echo "</tr>\n</table>\n";
   next_previous_buttons($rp,true,$num_p_r,$numrows,${$pagename});

   // print header of table
   echo "<table border='1' align='center'>\n";

   // get a list with ids we may see, $listb has all the ids we may see
   //$r=$db->CacheExecute(2,${$queryname});
   if ($db_type=="mysql") {
      $lista=make_SQL_csf ($r,false,"id",$nr_records);
      if (!$lista)
         $lista="-1";
      $lista=" id IN ($lista) ";
   }
   else {
      make_temp_table($db,"tempa",$r);
      $lista= " ($real_tablename.id=tempa.uniqueid) ";
   }

   //  get a list of all fields that are displayed in the table
   $Fieldscomma=comma_array_SQL_where($db,$table_desname,"columnname","display_table","Y");
//   $Fieldscomma .= ",id ";
   $Labelcomma=comma_array_SQL_where($db,$table_desname,"label","display_table","Y");
   $Allfields=getvalues($db,$real_tablename,$table_desname,$tableid,$Fieldscomma);	
   
   // javascript to automatically execute search when pulling down 
   $jscript="onChange='document.g_form.searchj.value=\"Search\"; document.g_form.submit()'";

   // row with search form
   echo "<tr align='center'>\n";
   echo "<input type='hidden' name='searchj' value=''>\n";

   //$lista_array=explode(",",$lista);
   //$counta=sizeof($lista_array);
   //$listb_array=explode(",",$listb);
   //$countb=sizeof($listb_array);

   foreach($Allfields as $nowfield)  {
      if ($HTTP_POST_VARS[$nowfield[name]]) {
         $list=$listb["sql"]; 
	 $count=$listb["numrows"];
      }
      else {
         $list=$lista;   
         $count=$listb["numrows"];
      }
      if ($nowfield[datatype]== "link")
         echo "<td style='width: 10%'>&nbsp;</td>\n";
      elseif ($nowfield[datatype]=="text" || $nowfield["datatype"]=="int" || $nowfield["datatype"]=="float") {
         // show titles we may see, when too many, revert to text box
         if ($list && ($count < $max_menu_length) )  {
  	     $rlist=$db->CacheExecute(2,"SELECT $nowfield[name] FROM $real_tablename WHERE $list");
             $text=$rlist->GetMenu("$nowfield[name]",${$nowfield[name]},true,false,0,"style='width: 80%' $jscript");
             echo "<td style='width: 10%'>$text</td>\n";
         }
	 else 
    	    echo  " <td style='width: 10%'><input type='text' name='$nowfield[name]' value='".${$nowfield[name]}."'size=8></td>\n";
      }
      elseif ($nowfield[datatype]== "textlong")
    	    echo  " <td style='width: 10%'><input type='text' name='$nowfield[name]' value='".${$nowfield[name]}."'size=8></td>\n";
      elseif ($nowfield["datatype"]== "pulldown") {
         echo "<td style='width: 10%'>";
         if ($USER["permissions"] & $LAYOUT)  {
            echo "<a href='$PHP_SELF?tablename=$tablename&edit_type=$nowfield[ass_t]&".SID;
            echo "'>Edit $nowfield[label]</a><br>\n";
         }	 		 			
         $rpull=$db->Execute("SELECT $nowfield[name] FROM $real_tablename WHERE $list");
         $list2=make_SQL_ids($rpull,false,"$nowfield[name]");
         if ($list2) { 
            $rpull=$db->Execute("SELECT typeshort,id from $nowfield[ass_t] WHERE id IN ($list2) ORDER by typeshort");
            if ($rpull)
	       $text=$rpull->GetMenu2("$nowfield[name]",${$nowfield[name]},true,false,0,"style='width: 80%' $jscript");   
	    else
	       $text="&nbsp;";
    	    echo "$text</td>\n";
         }  
      }
      elseif ($nowfield["datatype"]== "table") {
         echo "<td style='width: 10%'>";
         if ($nowfield["ass_local_key"])
            $text="&nbsp;"; 
         else {
            $rtable=$db->Execute("SELECT $nowfield[name] FROM $real_tablename WHERE $list");
            $list2=make_SQL_csf($rtable,false,"$nowfield[name]",$dummy);
            if ($list2) {
               $rtable=$db->Execute("SELECT $nowfield[ass_column_name],id FROM $nowfield[ass_table_name] WHERE id IN ($list2)");
               $text=$rtable->GetMenu2($nowfield["name"],${$nowfield[name]},true,false,0,"style='width: 80%' $jscript");
            }
            else
               $text="&nbsp;";
         }
         echo "$text</td>\n";
      }
      elseif ($nowfield["datatype"] == "file")
         echo "<td style='width: 10%'>&nbsp;</td>";
      elseif ($nowfield[datatype] == "table")
         echo "<td style='width: 10%'>&nbsp;</td>";
   }	 
   echo "<td><input type=\"submit\" name=\"search\" value=\"Search\">&nbsp;";
   echo "<input type=\"submit\" name=\"search\" value=\"Show All\"></td>";
   echo "</tr>\n\n";


   //display_midbar($Labelcomma);
   $labelarray=explode (",",$Labelcomma);
   $fieldarray=explode (",",$Fieldscomma);
   if ($sortdirarray)
      echo "<input type='hidden' name='serialsortdirarray' value='".serialize($sortdirarray)."'>\n";
   echo "<tr>\n";
   foreach ($labelarray As $key => $fieldlabel) 
      tableheader ($sortdirarray,$fieldarray[$key], $fieldlabel);
   echo "<th>Action</th>\n";
   echo "</tr>\n\n";

   if ($md=="edit")
      display_table_change($db,$tableid,$table_desname,$Fieldscomma,${$queryname},$num_p_r,${$pagename},$rp,$r);
   else
      display_table_info($db,$tableid,$table_desname,$Fieldscomma,${$queryname},$num_p_r,${$pagename},$rp,$r);
   printfooter($db,$USER);
}
?>
