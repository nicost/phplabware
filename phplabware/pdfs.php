<?php
  
// pdfs.php - Users can upload pdf files, pull bibl. data from Pubmed 
// pdfs.php - author: Nico Stuurman <nicost@soureforge.net>
// TABLES: pdfs
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
$httptitle .= "PDFs";
$fields="id,access,ownerid,magic,pmid,title,author,type1,type2,notes,date,lastmodby,lastmoddate,volume,fpage,lpage,abstract,year";
$tableid=get_cell($db,"tableoftables","id","tablename","pdfs");

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
// This function queries ncbi and pulls data associated with a paper into the database
function check_pd_data ($db,&$field_values) {
   global $HTTP_POST_FILES;
   // some browsers do not send a mime type??  
   if (is_readable($HTTP_POST_FILES["file"]["tmp_name"][0])) {
      if (!$HTTP_POST_FILES["file"]["type"][0]) {
         // we simply force it to be a pdf risking users making a mess
         $HTTP_POST_FILES["file"]["type"][0]="application/pdf";
      }
   }
   // avoid problems with spaces and the like
   $field_values["pmid"]=trim($field_values["pmid"]);
   // no fun without a pmid
   if (!$field_values["pmid"]) {
      echo "<h3 align='center'>Please enter the Pubmed ID of the PDF reprint.</h3>";
      return false;
   }
   // this will protect quotes in the imported data
   set_magic_quotes_runtime(1);
   // data from pubmed and parse
   $pmid=$field_values["pmid"];
   $pubmedinfo=@file("http://www.ncbi.nlm.nih.gov/entrez/utils/pmfetch.fcgi?db=PubMed&id=$pmid&report=abstract&report=abstract&mode=text");
   if ($pubmedinfo) {
      // lines appear to be broken randomly, but parts are separated by empty lines
      // get them into array $line
      for ($i=0; $i<sizeof($pubmedinfo);$i++) {
         $line[$lc].=str_replace("\n"," ",$pubmedinfo[$i]);
         if ($pubmedinfo[$i]=="\n")
	    $lc++;
      }
      // parse the first line.  1: journal  date;Vol:fp-lp
      $jstart=strpos($line[1],": ");
      $jend=strpos($line[1],"  ");
      $journal=trim(substr($line[1],$jstart+1,$jend-$jstart));
      $dend=strpos($line[1],";");
      $date=trim(substr($line[1],$jend+1,$dend-$jend-1));
      $year=$field_values["year"]=strtok($date," ");
      $vend=strpos($line[1],":",$dend);
      // if we can not find this, it might not have vol. first/last page
      if ($vend) {
         $volumeinfo=trim(substr($line[1],$dend+1,$vend-$dend-1));
         $volume=$field_values["volume"]=trim(strtok($volumeinfo,"(")); 
         $pages=trim(substr($line[1],$vend+1));
         $fpage=strtok($pages,"-");
         $lpage1=strtok("-");
         $lpage=substr_replace($fpage,$lpage1,strlen($fpage)-strlen($lpage1));
      }
      //echo "$jstart,$jend,$journal,$date,$year,$volume,$fpage,$lpage1,$lpage.<br>";
      $field_values["fpage"]=$fpage;
      $field_values["lpage"]=$lpage;
      // there can be a line 2 with 'Comment in:' put in notes and delete
      // ugly shuffle to get everything right again
      if (substr($line[2],0,11)=="Comment in:") {
         $field_values["notes"]=$line[2].$field_values["notes"];
	 $line[2]=$line[3];
	 $line[3]=$line[4];
	 $line[5]=$line[6];
      }
      $field_values["title"]=$line[2];
      $field_values["author"]=$line[3];
      // check whether there is an abstract
     if ((substr($line[5],0,4)!="PMID"))
         $field_values["abstract"]=$line[5];
      // check wether the journal is in pd_type1, if not, add it
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
   else {
      echo "<h3>Failed to import the Pubmed data</h3>\n";
      set_magic_quotes_runtime(0);
      return false;
   }
   // some stuff goes wrong when this remains on
   set_magic_quotes_runtime(0);
   return true;
}


////
// !Prints a form with pdf stuff
// $fields is a comma-delimited list with column names
// $field_values is a hash with column names as keys
// $id=0 for a new entry, otherwise it is the id
function add_pd_form ($db,$tableid,$fields,$field_values,$id,$USER,$PHP_SELF,$system_settings) {
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
<form method='post' id='pdfform' enctype='multipart/form-data' action='<?php echo $PHP_SELF?>?<?=SID?>'> 
<?php
   // generate a unique ID (magic) to avoid double uploads 
   if (!$magic)
      $magic=time();
   echo "<input type='hidden' name='magic' value='$magic'>\n";
   echo "<table border=0 align='center'>\n";
   if ($id) {
      echo "<tr><td colspan=5 align='center'><h3>Modify reprint:<br> <i>$title</i></h3></td></tr>\n";
      echo "<input type='hidden' name='id' value='$id'>\n";
   }
   else
      echo "<tr><td colspan=5 align='center'><h3>New PDF</h3></td></tr>\n";
   echo "<tr>\n";
   echo "<th>PMID: <sup style='color:red'>&nbsp;*</sup></th>\n";
   echo "<td><input type='text' name='pmid' value='$pmid' size=14><br>";
   echo "Find the PMID for this article at <a href='http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?db=PubMed'>Pubmed</a></td>\n";

   echo "<th>Category</th>\n<td>";
   $r=$db->Execute("SELECT type,id FROM pd_type2 ORDER BY sortkey");
   $text=$r->GetMenu2("type2",$type2,true);
   echo "<td>$text</td>\n";
   echo "</tr>\n";

   echo "<tr>";
   echo "<th>Notes: </th><td colspan=5><textarea name='notes' rows='5' cols='100%'>$notes</textarea></td>\n";
   echo "</tr>\n";
   
   $files=get_files($db,"pdfs",$id);
   echo "<tr>";
   echo "<th>Files: </th>\n";
   echo "<td colspan=1>\n   <table border=0>\n";
   for ($i=0;$i<sizeof($files);$i++) {
      echo "<tr><td colspan=2>".$files[$i]["link"];
      echo "&nbsp;&nbsp;(".$files[$i]["type"]." file)</td>\n";
      echo "<td><input type='submit' name='def_".$files[$i]["id"]."' value='Delete' Onclick=\"if(confirm('Are you sure the file ".$files[$i]["name"]." should be removed?')){return true;}return false;\"></td></tr>\n";
   }
   echo "<tr><th>Replace file(s) with:</th>\n";
   echo "<td><input type='file' name='file[]' value='$filename'></td>\n";
   echo "</tr>\n";
   echo "</table></td>\n\n";

   echo "<td colspan=3>";
   show_access($db,$tableid,$id,$USER,$system_settings);
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
// ! outputs a reference plus link to a file
function report_pdf_addition ($db,$id,$system_settings,$PHP_SELF) {
   $r=$db->Execute("SELECT ownerid,title,type1,year,volume,fpage,lpage,author FROM pdfs WHERE id=$id");
   $fid=fopen($system_settings["pdfs_file"],w);
   if ($fid) {
      $link= $system_settings["baseURL"].getenv("SCRIPT_NAME")."?showid=$id";
      $journal=get_cell($db,"pd_type1","type","id",$r->fields["type1"]);
      $submitter=get_person_link($db,$r->fields["ownerid"]);
      $text="<a href='$link'><b>".$r->fields["title"];
      $text.="</b></a> $journal (".$r->fields["year"]."), <b>".$r->fields["volume"];
      $text.="</b>:".$r->fields["fpage"]."-".$r->fields["lpage"];
      $text.= ". ".$r->fields["author"]." Submitted by $submitter.";
      fwrite($fid,$text);
      fclose($fid);
   }
}

////
// !Shows a page with nice information on the pdf
function show_pd ($db,$tableid,$fields,$id,$USER,$system_settings) {
   global $PHP_SELF;

   if (!may_read($db,$tableid,$id,$USER))
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
   echo "<tr>\n";
   echo "<th>Article: </th>\n";
   echo "<td>$title<br>\n";
   $text=get_cell($db,"pd_type1","type","id",$type1);
   echo "$text ($year), <b>$volume</b>:$fpage-$lpage<br>\n";
   echo "$author</td></tr>\n";
   
   if ($abstract) {
      echo "<tr>\n<th>Abstract</th>\n";
      echo "<td>$abstract</td>\n</tr>\n";
   }
   // Category
   if ($type2) {
      $type2=get_cell($db,"pd_type2","type","id",$type2);
      echo "<tr>\n<th>Category</th>\n";
      echo "<td>$type2</td>\n</tr>\n";
   }

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

   $files=get_files($db,"pdfs",$id);
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

   echo "<a href='http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?";
   if ($system_settings["pdfget"])
      $addget="&".$system_settings["pdfget"];
   echo "cmd=Retrieve&db=PubMed&list_uids=$pmid&dopt=Abstract$addget'>This article at Pubmed</a><br>\n";
   echo "<a href='http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?";
   echo "cmd=Link&db=PubMed&dbFrom=PubMed&from_uid=$pmid$addget'>Related articles at Pubmed</a></td></tr>\n";

?>   
<form method='post' id='pdfview' action='<?php echo $PHP_SELF?>?<?=SID?>'> 
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
      $r=$db->Execute("SELECT $fields FROM pdfs WHERE id=$modarray[1]"); 
      add_pd_form ($db,$tableid,$fields,$r->fields,$modarray[1],$USER,$PHP_SELF,$system_settings);
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
      add_pd_form ($db,$tableid,$fields,$HTTP_POST_VARS,$id,$USER,$PHP_SELF,$system_settings);
      printfooter();
      exit();
   }
   // show the record
   if (substr($key, 0, 4) == "view") {
      $modarray = explode("_", $key);
      show_pd($db,$tableid,$fields,$modarray[1],$USER,$system_settings);
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
   show_pd($db,$tableid,$fields,$showid,$USER,$system_settings);
   printfooter();
   exit();
}

// when the 'Add' button has been chosen: 
if ($add)
   add_pd_form ($db,$tableid,$fields,$field_values,0,$USER,$PHP_SELF,$system_settings);

else {
   // first handle addition of a new reprint
   if ($submit == "Add PDF reprint") {
      if (! (check_pd_data($db, $HTTP_POST_VARS) && $id=add ($db, "pdfs",$fields,$HTTP_POST_VARS,$USER,$tableid) ) ){
         echo "</caption>\n</table>\n";
         add_pd_form ($db,$tableid,$fields,$HTTP_POST_VARS,0,$USER,$PHP_SELF,$system_settings);
         printfooter ();
         exit;
      }
      else {  
         $HTTP_POST_FILES["file"]["name"][0]=$HTTP_POST_VARS["pmid"].".pdf";
	 $fileid=upload_files($db,$tableid,$id,$USER,$system_settings);
	 if ($system_settings["pdfs_file"])
	    report_pdf_addition ($db,$id,$system_settings,$PHP_SELF);
         // to not interfere with search form 
         unset ($HTTP_POST_VARS);
	 // or we won't see the new record
	 unset ($HTTP_SESSION_VARS["pd_query"]);
      }
   }
   // then look whether it should be modified
   elseif ($submit=="Modify PDF reprint") {
      if (! (check_pd_data($db,$HTTP_POST_VARS) && modify ($db,"pdfs",$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER,$tableid)) ) {
         echo "</caption>\n</table>\n";
         add_pd_form ($db,$tableid,$fields,$HTTP_POST_VARS,$HTTP_POST_VARS["id"],$USER,$PHP_SELF,$system_settings);
         printfooter ();
         exit;
      }
      else { 
         if ($HTTP_POST_FILES["file"]["name"][0]) {
            // delete all existing file
            delete ($db,$tableid,$HTTP_POST_VARS["id"],$USER,true);
            $fileid=upload_files($db,$tableid,$HTTP_POST_VARS["id"],$USER,$system_settings);
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
      $pd_curr_page=1;
      session_unregister("pd_query");
   }

   // sort stuff
   $sortdirarray=unserialize(stripslashes($serialsortdirarray));
   $sortstring=sortstring($sortdirarray,$sortup,$sortdown);

   // paging stuff
   $num_p_r=paging($num_p_r, $USER);

   // get current page
   $pd_curr_page =current_page($pd_curr_page,"pd"); 

   // prepare search SQL statement
   $pd_query=make_search_SQL($db,"pdfs","pd",$tableid,$fields,$USER,$search,$sortstring);

   // get the total number of hits
   $r=$db->CacheExecute(1,$pd_query);
   $numrows=$r->RecordCount();

   // loop through all entries for next/previous buttons
   $r=$db->CachePageExecute(1,$pd_query,$num_p_r,$pd_curr_page);
   while (!($r->EOF) && $r) {
      $r->MoveNext();
   }
   // print form
?>
<form name='pd_form' method='post' action='<?php echo $PHP_SELF?>?<?=SID?>'>  
<?php

   // row with action links
   $sid=SID;
   if ($sid) $sid="&".$sid;
   echo "<table border=0 width='50%' align='center'>\n<tr>\n";
   if (may_write($db,"pdfs",false,$USER)) 
      echo "<td align='center'><a href='$PHP_SELF?add=Add PDF$sid'>Add PDF</a></td>\n";
   //echo "<td align='center'><a href='$PHP_SELF?search=Show%20All$sid'>Show All</a></td>\n</tr>\n";
   //echo "<td align='center'><button type='submit' name='search' value='Show All'>";
   //echo "Show All</button></td></tr>\n";
   echo "</table>\n";

   next_previous_buttons($r,true,$num_p_r,$numrows,$pd_curr_page);

   // print header of table
   echo "<table border='1' align='center'>\n";
   echo "<caption>\n";
   echo "</caption>\n";

   // row with search form
   echo "<tr align='center'>\n";
   // javascript that submit the form with the search button when a selects was chosen
   $jscript="onChange='document.pd_form.searchj.value=\"Search\"; document.pd_form.submit()'";
   echo "<input type='hidden' name='searchj' value=''>\n";
   // get a list with ids we may see
   $r=$db->CacheExecute(1,$pd_query);
   $lista=make_SQL_csf ($r,false,"id",$nr_records);
   // and a list with all records we may see
   $listb=may_read_SQL($db,"pdfs",$tableid,$USER);

   // show title we may see, when too many, revert to text box
/*   if ($title) $list=$listb; else $list=$lista;
   if ($list && ($nr_records < $max_menu_length) ) {
      $r=$db->Execute("SELECT title FROM pdfs WHERE id IN ($list)");
      $text=$r->GetMenu("title",$title,true,false,0,"style='width: 80%' $jscript");
      echo "<td style='width: 10%'>$text</td>\n";
   }
   else 
*/

   echo "<td><input type='text' name='title' value='$title' size=15></td>\n";

   // show a list with authors having stuff we may see
/*   if ($author) $list=$listb; else $list=$lista;
   if ($list && ($nr_records < $max_menu_length) ) {
      $r=$db->Execute("SELECT author FROM pdfs WHERE id IN ($list)");
      $text=$r->GetMenu("author",$author,true,false,0,"style='width: 80%' $jscript");
      echo "<td style='width: 10%'>$text</td>\n";
   }
   else 
*/

   echo "<td><input type='text' name='author' value='$author' size=15></td>\n";

   // print link to edit table 'Categories'
   echo "<td style='width: 10%'>";
   if ($USER["permissions"] & $LAYOUT) {
      echo "<a href='$PHP_SELF?edit_type=pd_type2&";
      echo SID;
      echo "'>Edit Categories</a><br>\n";
   }
   // print category drop-down menu
   if ($type2) $list=$listb; else $list=$lista;
   $r=$db->Execute("SELECT type2 FROM pdfs WHERE id IN ($list)");
   $list2=make_SQL_ids($r,false,"type2");
   if ($list2) {
      $r=$db->Execute("SELECT typeshort,id FROM pd_type2 WHERE id IN ($list2)");
      $text=$r->GetMenu2("type2",$type2,true,false,0,"style='width: 80%' $jscript");
      echo "$text</td>\n";
   }
   else
      echo "&nbsp;</td>\n";

   echo "<td><input type='text' name='abstract' value='$abstract' size=15></td>\n";
   
   // print link to edit table 'Journals'
   echo "<td style='width: 10%'>";
   if ($USER["permissions"] & $LAYOUT) {
      echo "<a href='$PHP_SELF?edit_type=pd_type1&";
      echo SID;
      echo "'>Edit Journals</a><br>\n";
   }
   // print journals drop-down menu
   if ($type1) $list=$listb; else $list=$lista;
   $r=$db->Execute("SELECT type1 FROM pdfs WHERE id IN ($list)");
   $list2=make_SQL_ids($r,false,"type1");
   if ($list2) {
      $r=$db->Execute("SELECT type,id FROM pd_type1 WHERE id IN ($list2) ORDER BY type");
      $text=$r->GetMenu2("type1",$type1,true,false,0,"style='width: 80%' $jscript");
      echo "$text</td>\n";
   }
   else
      echo "&nbsp;</td>\n";


   echo "<td><input type='text' name='volume' value='$volume' size=7></td>\n";
   echo "<td><input type='text' name='fpage' value='$fpage' size=7></td>\n";
   echo "<td>&nbsp;</td>\n";

   echo "<td>";
   echo "<input type=\"submit\" name=\"search\" value=\"Search\">&nbsp;\n";
   echo "<input type=\"submit\" name=\"search\" value=\"Show All\">&nbsp;\n";
   echo "</td></tr>\n";

   if ($sortdirarray)
      echo "<input type='hidden' name='serialsortdirarray' value='".serialize($sortdirarray)."'>\n";
   echo "<tr>\n";
   tableheader ($sortdirarray,"title","Title");
   tableheader ($sortdirarray,"author","Author(s)");
   tableheader ($sortdirarray,"type2","Category");
   echo "<th>Abstract</th>\n";
   tableheader ($sortdirarray,"type1","Journal");
   tableheader ($sortdirarray,"volume","Volume");
   tableheader ($sortdirarray,"fpage","First Page");
   echo "<th>Files</th>\n";
   echo "<th>Action</th>\n";
   echo "</tr>\n";

   $r=$db->CachePageExecute(1,$pd_query,$num_p_r,$pd_curr_page);
   $rownr=1;
   // print all entries
   while (!($r->EOF) && $r) {
 
      // get results of each row
      $id = $r->fields["id"];
      $title = "&nbsp;".$r->fields["title"];
      $author= "&nbsp;".$r->fields["author"];
      $category="&nbsp;".get_cell($db,"pd_type2","type","id",$r->fields["type2"]);
      $abstract= "&nbsp;".substr($r->fields["abstract"],0,15)."...";
      $journal="&nbsp;".get_cell($db,"pd_type1","type","id",$r->fields["type1"]);
      $volume = "&nbsp;".$r->fields["volume"];
      $fpage = "&nbsp;".$r->fields["fpage"];
 
      // print start of row of selected group
      if ($rownr % 2)
         echo "<tr class='row_odd' align='center'>\n";
      else
         echo "<tr class='row_even' align='center'>\n";

      echo "<td>$title</td>\n";
      echo "<td>$author</td>\n";
      echo "<td>$category</td>\n";
      echo "<td>$abstract</td>\n";
      echo "<td>$journal</td>\n";
      echo "<td>$volume</td>\n";
      echo "<td>$fpage</td>\n";
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
      if (may_write($db,$tableid,$id,$USER)) {
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
   
   // Add Pdf button
   if (may_write($db,$tableid,false,$USER)) {
      echo "<tr><td colspan=9 align='center'>";
      echo "<input type=\"submit\" name=\"add\" value=\"Add Pdf\">";
      echo "</td></tr>";
   }


   echo "</table>\n";

   // next/previous buttons
   next_previous_buttons($r);

   echo "</form>\n";

}

printfooter($db,$USER);

?>
