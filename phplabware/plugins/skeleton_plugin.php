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
// !This function is called after a record has been added
// As an example, it is used to write some data concerning the 
// new record to a file for inclusion in a webpage
/*
function plugin_add ($db,$tableid,$id)
{
}
*/

////
// !Assign a new record to the userid returned by this function
/*
function plugin_setowner($db)  
{
   return $otheruserid;
}
*/

////
// !Change/calculate/check values just before they are added/modified
// $fieldvalues is an array with the column names as key.
// Any changes you make in the values of $fieldvalues will result in 
// changes in the database. 
// You could, for instance, calculate a value of a field based on other fields
// make sure this function returns true (or does not exist), or additions/modification
// will fail!
/*
function plugin_check_data($db,&$fieldvalues,$table_desc,$modify=false) 
{
   return true;
}
*/
/*
// This example plugin will track all changes made and report them in the field 'history' (which you should first add to the table)
function plugin_check_data($db,&$fieldvalues,$table_desc,$modify=false) 
{
   global $USER;

   // this function is likely to be needed only here...
   function get_associated_values($db,$ass_table,$typeids) {
      if (is_array($typeids)) {
         $typeidlist=implode(',',$typeids);
         $rp=$db->Execute("SELECT typeshort,id FROM $ass_table WHERE id IN ($typeidlist) ORDER BY sortkey,typeshort");
          while ($rp && !$rp->EOF) {
             $valueArray[]=$rp->fields[0];
             $rp->MoveNext();
          }
          return ($valueArray);
      }
   }

   // this is the old and inefficient way of getting tableinfo in
   $table=get_cell($db,"tableoftables","real_tablename","table_desc_name",$table_desc);

   // store the contents of the history field
   $ra=$db->Execute("SELECT history FROM $table WHERE id={$fieldvalues['id']}");
   if (isset($ra)) {
      $fieldvalues['history']=addslashes($ra->fields[0]);
   }
   else {
      // This table does not have the field 'history', report and continue
      trigger_error ('The Column <b>history</b> has not been defined for this table.  ');
      return true;
   }
   $now=date('F j, Y, g:1 a'); 
   // when modifying, report to the 'history' field what was changed
   $ra=$db->Execute("SELECT columnname,datatype,key_table,associated_table FROM $table_desc WHERE modifiable='Y'");
   while ($ra && $ra->fields) {
      $columnname=$ra->fields[0]; 
      // the following is also done in function modify, but we need them here too:
      if (in_array($columnname, array('gr','gw','er','ew')))
         $fieldvalues[$columnname]=get_access($fieldvalues,$columnname);
      // we need to deal with datatypes table, mulldown and pulldown seperately
      if ($ra->fields[1]=='mpulldown') {
         $rp=$db->Execute ("SELECT typeid FROM {$ra->fields[2]} WHERE recordid={$fieldvalues['id']}");
         unset($typeids);
         unset ($oldValueArray);
         unset ($newValueArray);
         while ($rp && $rp->fields) {
             $typeids[]=$rp->fields[0];
             $rp->MoveNext();
         }
         if ($typeids <> $fieldvalues[$columnname]) {
            // get the values out to tell the user what changed
            // the values from the database
            $oldValueArray= get_associated_values($db,$ra->fields[3],$typeids);
            if (is_array($oldValueArray))
               $oldValue=implode(',',$oldValueArray);
            // the new values entered by the user
            $newValueArray= get_associated_values($db,$ra->fields[3],$fieldvalues[$columnname]);
            if (is_array($newValueArray))
               $newValue=implode(',',$newValueArray);
         }
      }
      elseif ($ra->fields[1]=='pulldown') {
         $rc=$db->Execute("SELECT {$ra->fields[0]} FROM $table WHERE id={$fieldvalues['id']}");
         if ( ((int)$rc->fields[0]) != ((int)$fieldvalues[$columnname])) {
            $oldValue=getcell($db,$ra->fields[3],'typeshort','id',$rc->fields[0]);
            $newValue=getcell($db,$ra->fields[3],'typeshort','id',$fieldvalues[$columnname]);
         }
      }

      else {
         $r=$db->Execute("Select $columnname FROM $table WHERE id={$fieldvalues['id']}");
         $oldValue=$r->fields[0];
         $newValue=$fieldvalues[$columnname];
         // echo "$columnname, $val,$oldvalue.<br>";
      }

      if (((string)$newValue) != ((string)$oldValue) ) {
         // we need to escape these before sticking them into the notes field!
         $oldValue=addslashes($oldValue);
         $newValue=addslashes($newValue);
         $text="\n\n$now: {$USER['firstname']} {$USER['lastname']} ({$USER['login']},{$USER['id']}) changed column \'$columnname\' from \'$oldValue\' to \'$newValue\'.";
      }

      $fieldvalues['history'].=$text;
      unset($text);
      $ra->Movenext();
   }

   return true;
}

*/

////
/*// !Overrides the standard 'show record'function
function plugin_show($db,$tableinfo,$id,$USER,$system_settings,$backbutton=true)
{
}
*/


////
// !Extends the search query
// $query is the complete query that you can change and must return
// $fieldvalues is an array with the column names as key.
// if there is an $existing_clause (boolean) you should prepend your additions
// with ' AND' or ' OR', otherwise you should not
/*
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
/*
function plugin_getvalues($db,&$allfields,$id,$tableid) 
{
}
*/


////
// !Extends function display_add
// This function let's you change values before they are display in the display_add function
/*
function plugin_display_add ($db,$tableid,&$nowfield)
{
}
*/


////
// !Extends function display_add
// This lets you add information to every specific item
/*
function plugin_display_add ($db,$tableid,$nowfield)
{
}
*/


?>
