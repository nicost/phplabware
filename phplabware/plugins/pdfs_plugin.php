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
function plugin_check_data($db,&$field_values,$table_desc) 
{
   global $HTTP_POST_FILES;
$db->debug=true;
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
      echo "<h3 align='center'>Please enter the Pubmed ID of the PDF reprint.</h3>";
      return false;
   }
   // check whether we had this one already
   $existing_id=get_cell($db,$pdftable,"id","pmid",$field_values["pmid"]);
   if ($existing_id) {
      echo "<h3 align='center'><a href='pdfs.php?showid=$existing_id'>That paper </a>is already in the database.</h3>\n";
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
$db->debug=false;
   // some stuff goes wrong when this remains on
   set_magic_quotes_runtime(0);
print_r($field_values);
   return true;
}


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

?>
