<?php

// plugin_inc.php - skeleton file for plugin codes
// plugin_inc.php - author: Nico Stuurman

/* 
Copyright 2002, Nico Stuurman

This is a skeleton file to code your own plugins.
To use it, rename this file to something meaningfull,
add the path and name to this file (relative to the phplabware root)
in the column 'plugin_code' of 'tableoftables', and code away.  
And, when you make something nice, please send us a copy!

This program is free software: you can redistribute it and/ormodify it under
the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

*/



////
// !Change/calculate/check values just before they are added/modified
// $fieldvalues is an array with the column names as key.
// Any changes you make in the values of $fieldvalues will result in 
// changes in the database. 
// You could, for instance, calculate a value of a field based on other fields
function plugin_check_data($db,&$field_values,$table_desc,$modify=false) 
{
   global $HTTP_POST_FILES;
   // we need some info from the database
   $pdftable=get_cell($db,"tableoftables","real_tablename","table_desc_name",$table_desc);
   $journaltable=get_cell($db,$table_desc,"associated_table","columnname","journal");

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
      if ($modify)
         return true;
      else {
         echo "<h3 align='center'>Please enter the Pubmed ID of the PDF reprint.</h3>";
         return false;
      }
   }

   // check whether we had this one already
   if (!$modify) {
      $existing_id=get_cell($db,$pdftable,"id","pmid",$field_values["pmid"]);
      if ($existing_id) {
         echo "<h3 align='center'><a href='pdfs.php?showid=$existing_id'>That paper </a>is already in the database.</h3>\n";
         return false;
      }
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
      $year=$field_values["pubyear"]=strtok($date," ");
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
      $r=$db->Execute("SELECT id FROM $journaltable WHERE typeshort='$journal'");
      if ($r && $r->fields("id"))
         $field_values["journal"]=$r->fields("id");
      else {
         $tid=$db->GenID("$journaltable_id_seq");
	 if ($tid) {
	    $r=$db->Execute("INSERT INTO $journaltable (id,type,typeshort,sortkey) VALUES ($tid,'$journal','$journal',0)");
	    if ($r)
	       $field_values["type1"]=$tid;
	 }
      }
   }
   else {
      echo "<h3>Failed to import the Pubmed data</h3>\n";
      set_magic_quotes_runtime(0);
      return true;
   }
   // some stuff goes wrong when this remains on
   set_magic_quotes_runtime(0);
   return true;
}


////
// !Overrides the standard 'show record'function
function plugin_show($db,$fields,$id,$USER,$system_settings,$tableid,$real_tablename,$table_desname)
{
/*
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

   $files=get_files($db,"pdfs",$id,1);
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
*/

}

/*

////
// !Extends the search query
// $query is the complete query that you can change and must return
// $fieldvalues is an array with the column names as key.
// if there is an $existing_clause (boolean) you should prepend your additions
// with ' AND' or ' OR', otherwise you should not
function plugin_search($query,$fieldvalues,$existing_clause) 
{
   return $query;
}


////
// !Extends function getvalues
// $allfields is a 2-D array containing the field names of the table in the first dimension
// and name,columnid,label,datatype,display_table,display_record,ass_t,ass_column,
// ass_local_key,required,modifiable,text,values in the 2nd D
function plugin_getvalues($db,&$allfields) 
{
}
*/
?>
