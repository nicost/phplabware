<?php
  
// db_inc.php -  functions interfacing with the database
// db_inc.php - author: Nico Stuurman <nicost@soureforge.net>
  /***************************************************************************
  * This script contain functions interfacing with the database              *
  * Although they are geared towards phplabware, they might be more generally*
  *   useful.                                                                *
  *                                                                          *
  *                                                                          *
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/       

////
// !Inserts $fields with $fieldvalues into $table
// Returns the id of inserted record on succes, false otherwise.
// $fields is a comma separated list with all column names
// Fieldvalues must be an associative array containing all the $fields to be added.
// Fields named 'date' are automatically filled with a Unix timestamp
function add ($db,$table,$fields,$fieldvalues,$USER) {
   if (!may_write($db,$table,false,$USER)) {
      echo "<h3>You are not allowed to do this.<br>";
      return false;
   }
   include('includes/defines_inc.php');
   if (!($USER["permissions"] & $WRITE) )
      return false;
   // generate the new ID
   $id=$db->GenID($table."_id_seq");
   if ($id) {
      $columns="id";
      $values="$id";
      $column=strtok($fields,",");
      while ($column) {
         if (!($column=="id")) {
            $columns.=",$column";
            // set userid
            if ($column=="ownerid")
               $fieldvalues["ownerid"]=$USER["id"];
            // set default access rights, 
            elseif ($column=="access")
               if (!$fieldvalues["access"])
                  $fieldvalues["access"]="rw-r-----";
            // set timestamp
            if ($column=="date") {
               $date=(time());
               $values.=",$date";
            }
            else
	       $values.=",'$fieldvalues[$column]'";
         }
	 $column=strtok(",");
      }
      $query="INSERT INTO $table ($columns) VALUES ($values)";
      if ($db->Execute($query))
         return $id;
      else
         echo "<h3>Database error.  Contact your system administrator.</h3>\n";
   }
}


////
// !Modifies $fields in $table with values $fieldvalues where id=$id
// Returns true on succes, false on failure
// Fieldvalues must be an associative array containing all the $fields to be added.
// If a field is not present in $fieldvalues, it will not be changed.  
// The entry 'id' in $fields will be ignored.
function modify ($db,$table,$fields,$fieldvalues,$id,$USER) {
   if (!may_write($db,$table,$id,$USER))
      return false;
   $query=" UPDATE $table SET ";
     $column=strtok($fields,",");
   while ($column) {
      if (! ($column=="id" || $column=="date" || $column=="ownerid" || $column=="access")) {
         $test=true;
         $query.="$column='$fieldvalues[$column]',";
      }
      $column=strtok(",");
   }
   $query[strrpos($query,",")]=" ";

   if ($test) {
      $query.=" WHERE id='$id'";
      if ($db->Execute($query))
         return true;
   }
}


////
// !Deletes the entry with id=$id
// Returns true on succes, false on failure
// Checks whether the delete is allowed
// This is very generic, it is likely that you will need to do more cleanup
function delete ($db, $table, $id, $USER) {
   if (!may_write($db,$table,$id,$USER))
      return false;
   include ('includes/defines_inc.php');
   if ($USER["permissions"] & $ADMIN)
      $test=true;

   // and now delete for real
   if ($db->Execute("DELETE FROM $table WHERE id=$id"))
      return true;
   else
      return false;
}


////
// !Returns an SQL SELECT statement with ids of records the user may see
// Since it uses subqueries it does not work with MySQL
function may_read_SQL_subselect ($db,$table,$USER,$clause=false) {
   include ('includes/defines_inc.php');
   $query="SELECT id FROM $table ";
   if ($USER["permissions"] & $SUPER) {
      if ($clause)
         $query .= "WHERE $clause";
   }
   else {
      $usergroup=get_cell($db,"users","groupid","id",$USER["id"]);
      $userid=$USER["id"];
      $query .= " WHERE ";
      if ($clause) 
         $query .= " $clause AND ";
      // owner
      $query .= "( (ownerid=$userid AND SUBSTRING (access FROM 1 FOR 1)='r') ";
      // group
      $query .= "OR ($usergroup=CAST( (SELECT groupid FROM users WHERE users.id=$table.ownerid) AS int) AND SUBSTRING (access FROM 4 FOR 1)='r') ";
      // world
      $query .= "OR (SUBSTRING (access FROM 7 FOR 1)='r')";
      $query .=")";
   }
   return $query;
}

////
// !returns an comma-separated list of quoted values from a SQL search
// helper function for may_read_SQL
function make_SQL_ids ($r,$ids) {
   $id=$r->fields["id"];
   if (!$id)
      return false;
   $ids .="'$id'";
   $r->MoveNext();
   while (!$r->EOF) {
      $id=$r->fields["id"];
      $ids .=",'$id'";
      $r->MoveNext();
   }
   return ($ids);
}


////
// !Returns an array with ids of records the user may see in SQL format
function may_read_SQL_JOIN ($db,$table,$USER) {
   include ('includes/defines_inc.php');
   if (!($USER["permissions"] & $SUPER)) {
      $query="SELECT id FROM $table ";
      $usergroup=get_cell($db,"users","groupid","id",$USER["id"]);
      $userid=$USER["id"];
      $query .= " WHERE ";
      // owner
      $query .= "( (ownerid=$userid AND SUBSTRING(access FROM 1 FOR 1)='r') ";
      $query .= "OR (SUBSTRING(access FROM 7 FOR 1)='r')";
      $query .=")";
      $r=$db->Execute($query);
      if ($r) {
         $ids=make_SQL_ids($r,$ids);
      }
      // group
      $query="SELECT $table.id FROM $table LEFT JOIN users ON $table.ownerid=users.id WHERE users.groupid=$usergroup";
      $r=$db->Execute($query);
   }
   else {     // superuser
      $query="SELECT id FROM $table ";
      $r=$db->Execute($query);
   }
   if ($r)
      return make_SQL_ids($r,$ids);
}


////
// !To deal with differences in databases
function may_read_SQL ($db,$table,$USER) {
   global $db_type;

   if ($db_type=="mysql")
      return may_read_SQL_JOIN ($db,$table,$USER);
   else
      return may_read_SQL_subselect ($db,$table,$USER);
}
////
// !determines whether or not the user may read this record
function may_read ($db,$table,$id,$USER) {
   $list=may_read_SQL($db,$table,$USER);
   $query="SELECT id FROM $table WHERE $id IN ($list)";
   $r=$db->Execute($query);
   if (!$r)
      return false;
   if ($r->EOF)
      return false;
   else
      return true;
}

////
// !checks if this user may write/modify/delete these data
function may_write ($db,$table,$id,$USER) {
   include ('includes/defines_inc.php');
   
   if ($USER["permissions"] & $SUPER)
      return true;
   if ( ($USER["permissions"] & $WRITE) && (!$id))
      return true;
   $usergroup=get_cell($db,"users","groupid","id",$USER["id"]);
   $r=$db->Execute("SELECT groupid FROM users LEFT JOIN $table ON 
                    users.id=$table.ownerid WHERE antibodies.id=$id");
   $ownergroup=$r->fields["groupid"];
   if ($USER["permissions"] & $ADMIN) {
      if ($usergroup==$ownergroup)
         return true;
   }
   if ($id) {
      $userid=$USER["id"];
      if ($r=$db->Execute("SELECT * FROM $table WHERE id=$id AND
            ownerid=$userid AND SUBSTRING(access FROM 2 FOR 1)='w'")) 
         if (!$r->EOF)
            return true;
      if ($r=$db->Execute("SELECT * FROM $table WHERE id=$id AND
            ownerid=$userid AND SUBSTRING(access FROM 5 FOR 1)='w'")) 
         if (!$r->EOF && ($usergroup==$ownergroup) )
            return true;
   }
}

////
// !Returns an SQL search statement
// The whereclause should NOT start with WHERE
// The whereclause should contain the output of may_read_SQL and
// can also be used for sorting
function search ($table,$fields,$fieldvalues,$whereclause=false,$wcappend=true) {
   $columnvalues=$fieldvalues;
   $query="SELECT $fields FROM $table WHERE ";
   $column=strtok($fields,",");
   while ($column && !$columnvalues[$column])
      $column=strtok (",");
   if ($column && $columnvalues[$column]) {
      $test=true;
      if (is_string($columnvalues[$column])) {
         $columnvalue=$columnvalues[$column];
         $columnvalue=str_replace("*","%",$columnvalue);
	 if ($wcappend)
	    $columnvalue="%$columnvalue%";
	 else
	    $columnvalue="% $columnvalue %";
         $query.="$column LIKE '$columnvalue' ";
      }
      else
         $query.="$column='$columnvalues[$column]' ";
   }
   $column=strtok (",");
   while ($column) { 
      if ($column && $columnvalues[$column]) {
         if (is_string($columnvalues[$column])) {
            $columnvalue=$columnvalues[$column];
            $columnvalue=str_replace("*","%",$columnvalue);
            if ($wcappend)
	       $columnvalue="%$columnvalue%";
	    else
	       $columnvalue="% $columnvalue %";
            $query.="$column LIKE '$columnvalue' ";
         }
         else
            $query.="$column='$columnvalues[$column]' ";
      }
      $column=strtok (",");
   }
   if ($whereclause)
      if ($test)
         $query .= "AND $whereclause";
      else
         $query .= $whereclause;
   return $query;
}


?>
