<?php
  
// pdfs.php - Users can upload pdf files, pull bibl. data from Pubmed 
// pdfs.php - author: Nico Stuurman <nicost@soureforge.net>
  /***************************************************************************
  * This script displays a table with pdfs in phplabware.                    *
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
$title .= "PDFs";
$fields="id,access,ownerid,magic,pmid,title,author,type1,type2,notes,date,lastmodby,lastmoddate,volume,fpage,lpage,abstract,year";

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
function check_pd_data ($db,&$field_values) {
   if (!$field_values["pmid"]) {
      echo "<h3>Please enter the Pubmed ID the PDF reprint.</h3>";
      return false;
   }
   // data from pubmed and parse
   $pmid=$field_values["pmid"];
   $pubmedinfo=file("http://www.ncbi.nlm.nih.gov/entrez/utils/pmfetch.fcgi?db=PubMed&id=$pmid&report=abstract&report=abstract&mode=text");
   if ($pubmedinfo) {
      // lines appear to be broken randomly, but parts are separated by empty lines
      // get them into array $line
      for ($i=0; $i<sizeof($pubmedinfo);$i++) {
         $line[$lc].=rtrim($pubmedinfo[$i]);
         if ($pubmedinfo[$i]=="\n")
	    $lc++;
      }
      // parse the first line.  1: journal  date;Vol:fp-lp
      $jstart=strpos($line[1],": ");
      $jend=strpos($line[1],"  ");
      $journal=trim(substr($line[1],$jstart+1,$jend-$jstart));
      $dend=strpos($line[1],";");
      $date=trim(substr($line[1],$jend+1,$dend-$jend-1));
      $field_values["year"]=strtok($date," ");
      $vend=strpos($line[1],":",$dend);
      $volumeinfo=trim(substr($line[1],$dend+1,$vend-$dend-1));
      $field_values["volume"]=strtok($volumeinfo,"("); 
      $pages=trim(substr($line[1],$vend+1));
      $fpage=strtok($pages,"-");
      $lpage1=strtok("-");
      $lpage=substr_replace($fpage,$lpage1,strlen($fpage)-strlen($lpage1));
      // echo "$jstart,$jend,$journal,$date,$year,$volume,$fpage,$lpage1,$lpage.<br>";
      $field_values["fpage"]=$fpage;
      $field_values["lpage"]=$lpage;
      $field_values["title"]=$line[2];
      $field_values["author"]=$line[3];
      $field_values["abstract"]=$line[6];
      // check wether the journal is in pd_typ1, if not, add it
      $r=$db->Execute("SELECT id FROM pd_type1 WHERE typeshort='$journal'");
      if ($r && $r->fields("id"))
         $field_values["type1"]=$r->fields("id");
      else {
         $tid=$db->GenID("type1_id_seq");
	 if ($tid) {
	    $r=$db->Execute("INSERT INTO pd_type1 (id,type,typeshort,sortkey) VALUES ($tid,'$journal','$journal',0)");
	    if ($r)
	       $field_values["type1"]=$tid;
	 }
      }
   }
   return true;
}


////
// !Prints a form with pdf stuff
// $id=0 for a new entry, otherwise it is the id
function add_pd_form ($db,$fields,$field_values,$id,$USER,$PHP_SELF,$system_settings) {
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
<form method='post' id='pdfform' enctype='multipart/form-data' action='<?php echo $PHP_SELF?>?<?=SID?>'> 
<?php
   // generate a unique ID (magic) to avoid double uploads 
   if (!$magic)
      $magic=time();
   echo "<input type='hidden' name='magic' value='$magic'>\n";
   echo "<table border=0 align='center'>\n";
   if ($id) {
      echo "<tr><td colspan=5 align='center'><h3>Modify PDF metadata <i>$title</i></h3></td></tr>\n";
      echo "<input type='hidden' name='id' value='$id'>\n";
   }
   else
      echo "<tr><td colspan=5 align='center'><h3>New PDF</h3></td></tr>\n";
   echo "<tr>\n";
   echo "<th>PMID: <sup style='color:red'>&nbsp;*</sup></th>\n";
   echo "<td><input type='text' name='pmid' value='$pmid' size=14><br>";
   echo "Find the PMID for this article at <a href='http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?db=PubMed'>Pubmed</a></td></tr>\n";

   echo "<tr>";
   echo "<th>Notes: </th><td colspan=6><textarea name='notes' rows='5' cols='100%'>$notes</textarea></td>\n";
   echo "</tr>\n";
   
   $files=get_files($db,"pdfs",$id);
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
   show_access($db,"pdfs",$id,$USER,$system_settings);
   echo "</td></tr>\n";
   
   echo "<tr>";
   if ($id)
      $value="Modify PDF reprint";
   else
      $value="Add PDF reprint";
   echo "<td colspan=7 align='center'><input type='submit' name='submit' value='$value'>\n";
   echo "&nbsp;&nbsp;<input type='submit' name='submit' value='Cancel'></td>\n";
   echo "</tr>\n";
   

   echo "</table></form>\n";
}

////
// !Shows a page with nice information on the pdf
function show_pd ($db,$fields,$id,$USER,$system_settings) {
   global $PHP_SELF;

   if (!may_read($db,"pdfs",$id,$USER))
      return false;

   // get values 
   $r=$db->Execute("SELECT $fields FROM pdfs WHERE id=$id");
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

/*****************************BODY*******************************/

printheader($title);
navbar($USER["permissions"]);

// check if something should be modified, deleted or shown
while((list($key, $val) = each($HTTP_POST_VARS))) {
   if (substr($key, 0, 3) == "mod") {
      $modarray = explode("_", $key);
      $r=$db->Execute("SELECT $fields FROM pdfs WHERE id=$modarray[1]"); 
      add_pd_form ($db,$fields,$r->fields,$modarray[1],$USER,$PHP_SELF,$system_settings);
      printfooter();
      exit();
   }
   // delete file and show pdf form
   if (substr($key, 0, 3) == "def") {
      $modarray = explode("_", $key);
      $filename=delete_file($db,$modarray[1],$USER);
      $id=$HTTP_POST_VARS["id"];
      if ($filename)
         echo "<h3 align='center'>Deleted file <i>$filename</i>.</h3>\n";
      else
         echo "<h3 align='center'>Failed to delete file <i>$filename</i>.</h3>\n";
      add_pd_form ($db,$fields,$HTTP_POST_VARS,$id,$USER,$PHP_SELF,$system_settings);
      printfooter();
      exit();
   }
   // show the record
   if (substr($key, 0, 4) == "view") {
      $modarray = explode("_", $key);
      show_pd($db,$fields,$modarray[1],$USER,$system_settings);
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
      del_type($db,$table,$modarray[3],"pdfs");
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
   show_pd($db,$fields,$showid,$USER,$system_settings);
   printfooter();
   exit();
}

// when the 'Add' button has been chosen: 
if ($add)
   add_pd_form ($db,$fields,$field_values,0,$USER,$PHP_SELF,$system_settings);

else {
   // print header of table
   echo "<table border='1' align='center'>\n";
   echo "<caption>\n";
   // first handle addition of a new protocol
   if ($submit == "Add PDF reprint") {
      if (! (check_pd_data($db, $HTTP_POST_VARS) && $id=add ($db, "pdfs",$fields,$HTTP_POST_VARS,$USER) ) ){
         echo "</caption>\n</table>\n";
         add_pd_form ($db,$fields,$HTTP_POST_VARS,0,$USER,$PHP_SELF,$system_settings);
         printfooter ();
         exit;
      }
      else {  
	 $fileid=upload_files($db,"pdfs",$id,$USER,$system_settings);
         // to not interfere with search form 
         unset ($HTTP_POST_VARS);
	 // or we won't see the new record
	 unset ($HTTP_SESSION_VARS["pd_query"]);
      }
   }
   // then look whether it should be modified
   elseif ($submit=="Modify PDF reprint") {
      if (! (check_pd_data($db,$HTTP_POST_VARS) && modify ($db,"pdfs",$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER)) ) {
         echo "</caption>\n</table>\n";
         add_pr_form ($db,$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER,$PHP_SELF,$system_settings);
         printfooter ();
         exit;
      }
      else { 
         if ($HTTP_POST_FILES["file"]["name"][0]) {
            // delete all existing file
            delete ($db,"pdfs",$HTTP_POST_VARS["id"],$USER,true);
            $fileid=upload_files($db,"pdfs",$HTTP_POST_VARS["id"],$USER,$system_settings);
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
            delete ($db, "pdfs", $delarray[1], $USER);
         }
      }
   } 

   echo "</caption>\n";
   // print form
?>
<form name='pd_form' method='post' action='<?php echo $PHP_SELF?>?<?=SID?>'>  
<?php

   if ($search=="Show All") {
      $num_p_r=$HTTP_POST_VARS["num_p_r"];
      unset ($HTTP_POST_VARS);
      $curr_page=1;
      session_unregister("pd_query");
   }
   $column=strtok($fields,",");
   while ($column) {
      ${$column}=$HTTP_POST_VARS[$column];
      $column=strtok(",");
   }

   // prepare search SQL statement
   $whereclause=may_read_SQL ($db,"pdfs",$USER);
   if ($search=="Search")
      $pd_query=search("pdfs",$fields,$HTTP_POST_VARS," id IN ($whereclause) ORDER BY title");
   elseif (session_is_registered ("pd_query") && isset($HTTP_SESSION_VARS["pd_query"]))
      $pd_query=$HTTP_SESSION_VARS["pd_query"];
   else
      $pd_query = "SELECT $fields FROM pdfs WHERE id IN ($whereclause) ORDER BY date DESC";
   $HTTP_SESSION_VARS["pd_query"]=$pd_query;   
   session_register("pd_query");
   // row with search form
   echo "<tr align='center'>\n";
   // javascript that submit the form with the search button when a selects was chosen
   $jscript="onChange='document.pd_form.searchj.value=\"Search\"; document.pd_form.submit()'";
   echo "<input type='hidden' name='searchj' value=''>\n";
   // get a list with ids we may see
   $r=$db->Execute($pd_query);
   $lista=make_SQL_csf ($r,false,"id",$nr_records);
   // and a list with all records we may see
   $listb=may_read_SQL($db,"pdfs",$USER);
   // show title we may see, when too many, revert to text box
   if ($title) $list=$listb; else $list=$lista;
   if ($list && ($nr_records < $max_menu_length) ) {
      $r=$db->Execute("SELECT title FROM pdfs WHERE id IN ($list)");
      $text=$r->GetMenu("title",$title,true,false,0,"style='width: 80%' $jscript");
      echo "<td style='width: 10%'>$text</td>\n";
   }
   else
      echo "<td><input type='text' name='title' value='$title' size=8></td>\n";

   // show a list with authors having stuff we may see
   echo "<td style='width: 10%'>\n";
   echo "<td><input type='text' name='notes' value='$notes' size=8></td>\n";

   // print link to edit table 'Categories'
   echo "<td style='width: 10%'>";
   if ($USER["permissions"] & $LAYOUT) {
      echo "<a href='$PHP_SELF?edit_type=pd_type1&";
      echo SID;
      echo "'>Edit Categories</a><br>\n";
   }
   // print category drop-down menu
   if ($type1) $list=$listb; else $list=$lista;
   $r=$db->Execute("SELECT type1 FROM pdfs WHERE id IN ($list)");
   $list2=make_SQL_ids($r,false,"type1");
   if ($list2) {
      $r=$db->Execute("SELECT typeshort,id FROM pd_type1 WHERE id IN ($list2)");
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
   echo "<th>Author(s)</th>";
   echo "<th>Journal</th>\n";
   echo "<th>Volume</th>\n";
   echo "<th>First Page</th>\n";
   echo "<th>Notes</th>\n";
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
   if (!isset($pd_curr_page))
      $pd_curr_page=$HTTP_SESSION_VARS["pd_curr_page"];
   if (isset($HTTP_POST_VARS["next"]))
      $pd_curr_page+=1;
   if (isset($HTTP_POST_VARS["previous"]))
      $pd_curr_page-=1;
   if ($pd_curr_page<1)
      $pd_curr_page=1;
   $HTTP_SESSION_VARS["pd_curr_page"]=$pd_curr_page; 
   session_register("pd_curr_page");

   $r=$db->PageExecute($pd_query,$num_p_r,$pd_curr_page);
   $rownr=1;
   // print all entries
   while (!($r->EOF) && $r) {
 
      // get results of each row
      $id = $r->fields["id"];
      $title = $r->fields["title"];
      $at1=get_cell($db,"pd_type1","type","id",$r->fields["type1"]);
      $notes = $r->fields["notes"];
 
      // print start of row of selected group
      if ($rownr % 2)
         echo "<tr class='row_odd' align='center'>\n";
      else
         echo "<tr class='row_even' align='center'>\n";

      echo "<td>&nbsp;$title</td>\n";
      $author= "&nbsp;".$r->fields["author"];
      echo "<td>$author</td>\n";
      if ($notes)
         echo "<td>yes</td>\n";
      else
         echo "<td>no</td>\n";
      echo "<td>&nbsp;$at1</td>\n";
      $files=get_files($db,"pdfs",$id,3);
      echo "<td>";
      if ($files) 
         for ($i=0;$i<sizeof($files);$i++)
	    echo $files[$i]["link"];
      else
         echo "&nbsp;";
      echo "</td>\n";

      echo "<td align='center'>&nbsp;\n";
      echo "<input type=\"submit\" name=\"view_" . $id . "\" value=\"View\">\n";
      if (may_write($db,"pdfs",$id,$USER)) {
         echo "<input type=\"submit\" name=\"mod_" . $id . "\" value=\"Modify\">\n";
         $delstring = "<input type=\"submit\" name=\"del_" . $id . "\" value=\"Remove\" ";
         $delstring .= "Onclick=\"if(confirm('Are you sure the PDF reprint $title ";
         $delstring .= "should be removed?')){return true;}return false;\">";                                           
         echo "$delstring\n";
      }
      echo "</td>\n";
      echo "</tr>\n";
   
      $r->MoveNext();
      $rownr+=1;
   }

   // Add Protocol button
   if (may_write($db,"pdfs",false,$USER)) {
      echo "<tr><td colspan=7 align='center'>";
      echo "<input type=\"submit\" name=\"add\" value=\"Add PDF reprint\">";
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
