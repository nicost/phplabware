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
