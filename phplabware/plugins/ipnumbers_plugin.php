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
function plugin_check_data($db,&$fieldvalues,$table_desc) 
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


////
// !Extends function getvalues
// $allfields is a 2-D array containing the field names of the table in the first dimension
// and name,columnid,label,datatype,display_table,display_record,ass_t,ass_column,
// ass_local_key,required,modifiable,text,values in the 2nd D
function plugin_getvalues($db,&$allfields,$id) 
{
   if (!$id)
      return false;
   for ($i=0; $i<sizeof($allfields); $i++) {
      if ($allfields[$i]["name"]=="ipnumber") {
         $ipnumber=$allfields[$i]["values"];
         $ipoffset=$i;
      }
      if ($allfields[$i]["name"]=="online") {
         $onlineoffset=$i;
      }
   }
   $resultcode=true;
   $ipnumber=$allfields[$ipoffset]["values"];
   if ($ipnumber) 
       $result=exec("ping -c 1 -w 1 $ipnumber",$dummy,$resultcode);
   
   if (!$resultcode)
      $allfields[$onlineoffset]["text"]="<b>Online</b>";
   else
      $allfields[$onlineoffset]["text"]="Offline";
}

?>
