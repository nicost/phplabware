<?php

// pdbs_plugin.php - skeleton file for plugin codes
// pdbs_plugin.php - author: Nico Stuurman

/* 
Copyright 2002, Nico Stuurman

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
   global $HTTP_POST_VARS, $HTTP_POST_FILES;
   // read title, author, pdbid from file
   // we can not demand a file to be there since this might be a modify
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

/*
////
// !Overrides the standard 'show record'function
function plugin_show($db,$fields,$id,$USER,$system_settings,$tableid,$real_tablename,$table_desname,$backbutton=true)
{
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
*/

////
// !Extends function getvalues
// $allfields is a 2-D array containing the field names of the table in the first dimension
// and name,columnid,label,datatype,display_table,display_record,ass_t,ass_column,
// ass_local_key,required,modifiable,text,values in the 2nd D
function plugin_getvalues($db,&$allfields,$id,$tableid) 
{
   if (!$id)
      return true;
   $table_desc=get_cell($db,"tableoftables","table_desc_name","id",$tableid);
   $webmollink=get_cell($db,$table_desc,"link_first","columnname","webmol");

   while (list($key,$value)=each($allfields)) {
      if ($value[name]=="webmol")
         $index=$key;
      if ($value[name]=="pdbid")
         $pdindex=$key;
   }
   if ($index) {
      $pdbid=$allfields[$pdindex]["values"];
      if ($pdbid) {
         $link=$webmollink . $pdbid;
         $allfields[$index]["text"]="<a href=$link>$pdbid</a>";
      }
   }
}


////
// !Extends function display_add
// This lets you add information to every specific item
function plugin_display_add ($db,$tableid,$nowfield)
{
   if ($nowfield["name"]=="pdbid") {
      echo "<br>If a pdb file is uploaded (below), the fields PDBID,Title, and Author(s) will be extracted from the file.";
   }
}


?>
