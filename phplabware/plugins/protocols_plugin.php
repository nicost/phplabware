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
// ! outputs a reference plus link to a file
function report_protocol_addition ($db,$id,$tablename,$real_tablename,$authortable)
{
   global $system_settings;

   if (!$system_Settings["protocols_file"])
      return false;
   $r=$db->Execute("SELECT ownerid,title,type1,type2 FROM $real_tablename WHERE id=$id");
   $fid=fopen($system_settings["protocols_file"],w);
   if ($fid) {
      $link= $system_settings["baseURL"].getenv("SCRIPT_NAME")."?tablename=$tablename&showid=$id";
      $author=get_cell($db,$authortable,"type","id",$r->fields["type2"]);
      $author.=" ".get_cell($db,$authortable,"typeshort","id",$r->fields["type2"]);
      $submitter=get_person_link($db,$r->fields["ownerid"]);
      $text="<a href='$link'><b>".$r->fields["title"]."</b></a>";
      $text.= ". Written by $author.<br> Submitted by $submitter.";
      fwrite($fid,$text);
      fclose($fid);
   }
}


////
// !Change/calculate/check values just before they are added/modified
// $fieldvalues is an array with the column names as key.
// Any changes you make in the values of $fieldvalues will result in 
// changes in the database. 
// You could, for instance, calculate a value of a field based on other fields
function plugin_check_data($db,&$fieldvalues,$table_desc,$modify=false) 
{

   $protocoltable=get_cell($db,"tableoftables","real_tablename","table_desc_name",$table_desc);
   $protocoltablelabel=get_cell($db,"tableoftables","tablename","table_desc_name",$table_desc);
   $authortable=get_cell($db,$table_desc,"associated_table","columnname","type2");

   // When a new author was entered
   $firstname=$fieldvalues["firstname"];
   $lastname=$fieldvalues["lastname"];
   if ($firstname || $lastname) {
      // check if this entry exists already
      $r=$db->Execute("SELECT id FROM $authortable WHERE type='$firstname' AND typeshort='$lastname'");
      if ($r && !$r->EOF) {
         $fieldvalues["type2"]=$r->fields["id"];
         return true;
      }
      $id=$db->GenID($authortable."_id_seq");
      $db->Execute("INSERT INTO $authortable (id,type,typeshort) VALUES ('$id','".
           $fieldvalues["firstname"]."','".$fieldvalues["lastname"]."')");
      $fieldvalues["type2"]=$id;
   }
   report_protocol_addition ($db,$id,$protocoltablelabel,$protocoltable,$authortable);
   return true;
}


/*
////
// !Overrides the standard 'show record'function
function plugin_show($db,$fields,$id,$USER,$system_settings,$tableid,$real_tablename,$table_desname,$backbutton=false)
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
   $authortable=get_cell($db,$table_desc,"associated_table","columnname","type2");
   while (list($key,$value)=each ($allfields)) {
      if ($value[name]=="type2")
         $index=$key;
   }
   if ($index) {
      $firstname=get_cell($db,$authortable,"type","id",$allfields[$index][values]);
      $lastname=get_cell($db,$authortable,"typeshort","id",$allfields[$index][values]);
      if ($firstname || $lastname)
         $allfields[$index][text]="$firstname $lastname";
   }
}


////
// !Extends display_add
function plugin_display_add ($db,$tableid,$nowfield)
{
   global $HTTP_POST_VARS;

   if ($nowfield[name]=="type2") {
      echo "<br>Or: Firstname: <input type='text' name='firstname' value='$HTTP_POST_VARS[firstname]'>\n";
      echo "Lastname: <input type='text'name='lastname' value='$HTTP_POST_VARS[lastname]'>";
   }
}

?>
