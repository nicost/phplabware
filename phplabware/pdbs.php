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
$tableid=get_cell($db,"tableoftables","id","tablename","pdbs");

// register variables
$get_vars="add,edit_type,showid,search";
globalize_vars($get_vars, $HTTP_GET_VARS);
$post_vars = "add,submit,search,searchj,serialsortdirarray";
globalize_vars ($post_vars, $HTTP_POST_VARS);

foreach($HTTP_POST_VARS as $key =>$value) {
   list($testkey,$testvalue)=explode("_",$key);
   if ($testkey=="sortup")
      $sortup=$testvalue;
   if ($testkey=="sortdown")
      $sortdown=$testvalue;
}   
reset ($HTTP_POST_VARS);
if ($searchj || $sortup || $sortdown)
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
      // force the mime-type to be pdb compliant
       $HTTP_POST_FILES["file"]["type"][0]="chemical/x-pdb";  
       $fh=fopen($HTTP_POST_FILES["file"]["tmp_name"][0],"r");  
       $test1=false;
       $test2=true;
       $llength=71;
       // protect quotes in imported data
       set_magic_quotes_runtime(1);
       while ($fh && $test2 && !feof($fh)) {
          $line=chop(fgets($fh,1024));
          $lid=strtok($line," ");
          switch ($lid) {
             case "HEADER":
                $pdbid=trim(strrchr($line," "));
                if (strlen($pdbid)<4) {
                   // this must be an old pdb format
                   $pdbid=substr($line,62,4);
                   $llength=62;
                }
                $field_values["pdbid"]=$pdbid;
                break;
             case "TITLE":
                $field_values["title"].=substr($line,10,$llength);
                break;
             case "COMPND":
                $compnd.=substr($line,10,$llength);
                break;
             case "AUTHOR":
                $field_values["author"].=substr($line,10,$llength);
                $test1=true;
                break;
             default:
                if ($test1) $test2=false;
         }
      }
   }
   if (!$field_values["title"])
      $field_values["title"]=$compnd;
   set_magic_quotes_runtime(0);
   return true;
}


////
// !Prints a form with pdb stuff
// $fields is a comma-delimited list with column names
// $field_values is a hash with column names as keys
// $id=0 for a new entry, otherwise it is the id
function add_pb_form ($db,$tableid,$fields,$field_values,$id,$USER,$PHP_SELF,$system_settings) {
   if (!may_write($db,$tableid,$id,$USER)) 
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
/*   echo "<tr>\n";
   echo "<th>Title: <sup style='color:red'>&nbsp;*</sup></th>\n";
   echo "<td><input type='text' name='title' value='$pmid' size=14><br>";
   echo "</td></tr>\n";
*/

   if ($id) {
      echo "<tr><th>PDBID:</th><td>$pdbid</td></tr>\n";
      echo "<input type='hidden' name='pdbid' value='$pdbid'>\n";
      echo "<tr><th>Title:</th><td>$title</td></tr>\n";
      echo "<input type='hidden' name='title' value='$title'>\n";
      echo "<tr><th>Authors:</th><td>$author</td></tr>\n";
      echo "<input type='hidden' name='author' value='$author'>\n";
   }
   else {
 	 echo "<tr><th></th><td>";
?>
<a href="#" onClick="MyWindow=window.open('getpdb.php','MyWindow','toolbar=yes,location=no,directories=no,status=no,menubar=yes,scrollbars=yes,resizable=yes,width=600,height=300'); return false;">
Click here to download pdb files</A><?php

      echo "</td></tr>\n";
      echo "<tr><th>PDB file:</th>\n";
      echo "<td><input type='file' name='file[]' value='$filename'></td>\n";
	  
   }

   echo "<tr>";
   echo "<th>Notes: </th><td colspan=6><textarea name='notes' rows='5' cols='100%'>$notes</textarea></td>\n";
   echo "</tr>\n";
   
   echo "<tr><td colspan=4 align='right'>";
   show_access($db,$tableid,$id,$USER,$system_settings);
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
function show_pb ($db,$tableid,$fields,$id,$USER,$system_settings) {
   global $PHP_SELF;

   if (!may_read($db,$tableid,$id,$USER))
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

   $files=get_files($db,"pdbs",$id,1);
   if ($files) {
      echo "<tr><th>Files:</th>\n<td>";
      for ($i=0;$i<sizeof($files);$i++) {
         echo $files[$i]["link"]." (".$files[$i]["type"]." file, ".$files[$i]["size"].")<br>\n";
      }
      echo "</tr>\n";
   }
   
   echo "<tr><th>Links:</th><td colspan=7><a href='$PHP_SELF?showid=$id&";
   echo SID;
   echo "'>".$system_settings["baseURL"].getenv("SCRIPT_NAME")."?showid=$id</a> (This page)<br>\n";
   if ($pdbid) {
      $Pdblink="<a href='http://www.rcsb.org/pdb/cgi/explore.cgi?pdbId=$pdbid'>$pdbid</a>(Entry at PDB database)\n";
      $Webmollink="<a href='webmol.php?pdbid=$pdbid'>$pdbid</a>(View Molecule with WebMol)\n";
   }
   echo "$Pdblink<br>$Webmollink</td></tr>\n";

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

# check wether user may see this table
if (!may_see_table($db,$USER,$tableid)) {
   echo "<h3 align='center'>These data are not for you.  Sorry;(</h3>\n";
   printfooter();
   exit();
}
// check if something should be modified, deleted or shown
while((list($key, $val) = each($HTTP_POST_VARS))) {
   if (substr($key, 0, 3) == "mod") {
      $modarray = explode("_", $key);
      $r=$db->Execute("SELECT $fields FROM pdbs WHERE id=$modarray[1]"); 
      add_pb_form ($db,$tableid,$fields,$r->fields,$modarray[1],$USER,$PHP_SELF,$system_settings);
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
      add_pb_form ($db,$tableid,$fields,$HTTP_POST_VARS,$id,$USER,$PHP_SELF,$system_settings);
      printfooter();
      exit();
   }
   // show the record
   if (substr($key, 0, 4) == "view") {
      $modarray = explode("_", $key);
      show_pb($db,$tableid,$fields,$modarray[1],$USER,$system_settings);
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
   show_pb($db,$tableid,$fields,$showid,$USER,$system_settings);
   printfooter();
   exit();
}

// when the 'Add' button has been chosen: 
if ($add)
   add_pb_form ($db,$tableid,$fields,$field_values,0,$USER,$PHP_SELF,$system_settings);

else {
   // first handle addition of a new PDB
   if ($submit == "Add PDB") 
   	{
   	if ($HTTP_POST_VARS[downpdb])
   		{
		if (!$filedir=$system_settings["filedir"]) {
   	 		echo "<h3><i>Filedir</i> was not set.  The file was not uploaded. Please contact your system administrator</h3>";
   	   	  return false;
   	   	 }
	  	  $pdbin=escapeshellcmd($val);
	  	  $filedir=$system_settings["filedir"];
  		  $response=system("perl files/getpdb.pl $pdbin $filedir");		
  		  if ($response=="YES")
  		  	echo "File uploaded<br>";
			}
		  	
			   
      if (! (check_pb_data($db, $HTTP_POST_VARS) && $id=add ($db, "pdbs",$fields,$HTTP_POST_VARS,$USER,$tableid) ) ){
         echo "</caption>\n</table>\n";
         add_pb_form ($db,$tableid,$fields,$HTTP_POST_VARS,0,$USER,$PHP_SELF,$system_settings);
         printfooter ();
         exit;
      }
      else {  
         if ($id>0)
	    $fileid=upload_files($db,$tableid,$id,1,$USER,$system_settings);
         // to not interfere with search form 
         unset ($HTTP_POST_VARS);
	 // or we won't see the new record
	 unset ($HTTP_SESSION_VARS["pb_query"]);
      }
   }
   // then look whether it should be modified
   elseif ($submit=="Modify PDB") {
      if (! (check_pb_data($db,$HTTP_POST_VARS) && modify ($db,"pdbs",$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER,$tableid)) ) {
         echo "</caption>\n</table>\n";
         add_pb_form ($db,$tableid,$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER,$PHP_SELF,$system_settings);
         printfooter ();
         exit;
      }
      else { 
         if ($HTTP_POST_FILES["file"]["name"][0]) {
            // delete all existing file
            delete ($db,$tableid,$HTTP_POST_VARS["id"],$USER,true);
            $fileid=upload_files($db,$tableid,$HTTP_POST_VARS["id"],1,$USER,$system_settings);
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
            delete ($db,$tableid,$delarray[1],$USER);
         }
      }
   } 

   if ($search=="Show All") {
      $num_p_r=$HTTP_POST_VARS["num_p_r"];
      unset ($HTTP_POST_VARS);
      unset ($serialsortdirarray);
      $pb_curr_page=1;
      session_unregister("pb_query");
   }

   // sort stuff
   $sortdirarray=unserialize(stripslashes($serialsortdirarray));
   $sortstring=sortstring($sortdirarray,$sortup,$sortdown);

   // paging stuff
   $num_p_r=paging($num_p_r, $USER);

   // get current page
   $pb_curr_page =current_page($pb_curr_page,"pb"); 

   // and a list with all records we may see
   $listb=may_read_SQL($db,"pdbs",$tableid,$USER,"tempb");

   // prepare search SQL statement
   $pb_query=make_search_SQL($db,"pdbs","pb",$tableid,$fields,$USER,$search,$sortstring,$listb["sql"]);

   // get the total number of hits
   $r_master=$db->Execute($pb_query);
   $numrows=$r_master->RecordCount();

   first_last_page ($r_master, $pb_curr_page,$num_p_r,$numrows);
   
   // print form
?>
<form name='pb_form' method='post' action='<?php echo $PHP_SELF?>?<?=SID?>'>  
<?php

   // row with action links
   $sid=SID;
   if ($sid) $sid="&".$sid;
   echo "<table border=0 width='50%' align='center'>\n<tr>\n";
   if (may_write($db,$tableid,false,$USER)) 
      echo "<td align='center'><a href='$PHP_SELF?add=Add PDB$sid'>Add PDB</a></td>\n";
   echo "</table>\n";

   next_previous_buttons($r_master,true,$num_p_r,$numrows,$pb_curr_page);

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
   $lista=make_SQL_csf ($r_master,false,"id",$nr_records);

   // show title we may see, when too many, revert to text box
   echo "<td><input type='text' name='title' value='$title' size=15></td>\n";

   echo "<td><input type='text' name='author' value='$author' size=15></td>\n";
   echo "<td><input type='text' name='notes' value='$notes' size=15></td>\n";
   
   if ($ownerid) $list=$listb["sql"]; else $list=$listai["sql"];
   $r=$db->Execute("SELECT ownerid FROM pdbs WHERE $list");
   $list2=make_SQL_ids($r,false,"ownerid");
   if ($list2) {
      if ($db_type=="mysql") // mysql does not use the ansi SQL || operator
         $r=$db->Execute("SELECT CONCAT(firstname, ' ', lastname),id  from users WHERE id IN ($list2) ORDER by lastname");
      else
         $r=$db->Execute("SELECT firstname || ' ' || lastname,id  from users WHERE id IN ($list2) ORDER by lastname");
      $text=$r->GetMenu2("ownerid",$ownerid,true,false,0,"style='width: 80%' $jscript");
   }
   else
      $text="&nbsp;";
   echo "<td>$text</td>\n";

   //echo "<td>&nbsp;</td>\n";
   echo "<td><input type='text' name='pdbid' value='$pdbid' size=6></td>\n";
   echo "<td>&nbsp;</td>\n<td>&nbsp;</td>\n";

   echo "<td>";
   echo "<input type=\"submit\" name=\"search\" value=\"Search\">&nbsp;\n";
   echo "<input type=\"submit\" name=\"search\" value=\"Show All\">&nbsp;\n";
   echo "</td></tr>\n";

   if ($sortdirarray)
      echo "<input type='hidden' name='serialsortdirarray' value='".serialize($sortdirarray)."'>\n";
   echo "<tr>\n";
   tableheader ($sortdirarray,"title","Title");
   tableheader ($sortdirarray,"author","Author(s)");
   tableheader ($sortdirarray,"notes","Notes");
   tableheader ($sortdirarray,"ownerid","Submitted by");
   echo "<th>Pdb</th>";
   echo "<th>Webmol</th>";
   echo "<th>File</th>\n";
   echo "<th>Action</th>\n";
   echo "</tr>\n";

   $r=$db->CachePageExecute(1,$pb_query,$num_p_r,$pb_curr_page);
   $rownr=1;
   // print all entries
   while (!($r->EOF) && $r) {
 
      // get results of each row
      $id = $r->fields["id"];
      $title = "&nbsp;".$r->fields["title"];
      $author="&nbsp;".$r->fields["author"];
      $notes= "&nbsp;".substr($r->fields["notes"],0,150)."...";
      $owner="&nbsp;".get_cell($db,"users","firstname","id",$r->fields["ownerid"])." ".get_cell($db,"users","lastname","id",$r->fields["ownerid"]);
      $pdbid=ltrim($r->fields["pdbid"]);
      if ($pdbid) {
         $Pdblink="<a href='http://www.rcsb.org/pdb/cgi/explore.cgi?pdbId=$pdbid'>$pdbid</a>\n";
         $Webmollink="<a href='webmol.php?pdbid=$pdbid'>$pdbid</a>\n";
      }
      else
         $Webmollink=$Pdblink="&nbsp;";
 
      // print start of row of selected group
      if ($rownr % 2)
         echo "<tr class='row_odd' align='center'>\n";
      else
         echo "<tr class='row_even' align='center'>\n";

      echo "<td>$title</td>\n";
      echo "<td>$author</td>\n";
      echo "<td>$notes</td>\n";
      echo "<td>$owner</td>\n";
      echo "<td>$Pdblink</td>\n";
      echo "<td>$Webmollink</td>\n";
      $files=get_files($db,"pdbs",$id,1,3);
      echo "<td>";
      if ($files) 
         for ($i=0;$i<sizeof($files);$i++)
	    echo $files[$i]["link"];
      else
         echo "&nbsp;";
      echo "</td>\n";

      echo "<td align='center'>&nbsp;\n";
      echo "<input type=\"submit\" name=\"view_" . $id . "\" value=\"View\">\n";
      if (may_write($db,$tableid,$id,$USER)) {
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
   next_previous_buttons($r_master);

   echo "</form>\n";

}

printfooter($db,$USER);

?>
