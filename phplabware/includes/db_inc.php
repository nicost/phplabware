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
   // test if upload already took place through variable magic
   if ($fieldvalues["magic"])
      if ($test=get_cell($db,$table,"id","magic",$fieldvalues["magic"]))
         return $test;
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
               $fieldvalues["access"]=get_access($fieldvalues);
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
// Fields lastmodby and lastmoddate will be automatically set
function modify ($db,$table,$fields,$fieldvalues,$id,$USER) {
   if (!may_write($db,$table,$id,$USER))
      return false;
   $query=" UPDATE $table SET ";
     $column=strtok($fields,",");
   while ($column) {
      if (! ($column=="id" || $column=="date" || $column=="ownerid") ) {
         $test=true;
         if ($column=="access")
            $fieldvalues["access"]=get_access($fieldvalues);
         if ($column=="lastmodby")
            $fieldvalues["lastmodby"]=$USER["id"];
         if ($column=="lastmoddate")
            $fieldvalues["lastmoddate"]=time();
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
function delete ($db, $table, $id, $USER, $filesonly=false) {
   if (!may_write($db,$table,$id,$USER))
      return false;
   // check for associated files
   $tableid=get_cell($db,"tableoftables","id","tablename",$table);
   $r=$db->Execute("SELECT id FROM files 
                    WHERE tablesfk=$tableid AND ftableid=$id");
   while ($r && !$r->EOF) {
      delete_file ($db,$r->fields("id"),$USER); 
      $r->MoveNext();
   }
   // and now delete for real
   if (!$filesonly) {
      if ($db->Execute("DELETE FROM $table WHERE id=$id"))
         return true;
      else
         return false;
   }
   else
      return true;
}



////
// !Upload files and enters then into table files
// files should be called file[] in HTTP_POST_FILES
// filetitle in HTTP_POST_VARS will be inserted in the title field of table files
// returns id of last uploaded file upon succes, false otherwise
function upload_files ($db,$table,$id,$USER,$system_settings) {
   global $HTTP_POST_FILES,$HTTP_POST_VARS;
   if (!$db && $table && $id)
      return false;
   if (!may_write($db,$table,$id,$USER))
      return false;
   if (isset($HTTP_POST_FILES["file"]["name"][0]) && !$filedir=$system_settings["filedir"]) {
      echo "<h3><i>Filedir</i> was not set.  The file was not uploaded. Please contact your system administrator</h3>";
      return false;
   }
   for ($i=0;$i<sizeof($HTTP_POST_FILES["file"]["name"]);$i++) {
      if (!$fileid=$db->GenID("files_id_seq"))
         return false;
      $originalname=$HTTP_POST_FILES["file"]["name"][$i];
      $mime=$HTTP_POST_FILES["file"]["type"][$i];
      // work around php bug??  
      $mime=strtok ($mime,";");
      $filestype=substr(strrchr($mime,"/"),1);
      $size=$HTTP_POST_FILES["file"]["size"][$i];
      $title=$HTTP_POST_VARS["filetitle"][$i];
      $type=$HTTP_POST_VARS["filetype"][$i];
      // this works asof php 4.02
      if (move_uploaded_file($HTTP_POST_FILES["file"]["tmp_name"][$i],"$filedir/$fileid"."_"."$originalname")) {
         $tablesid=get_cell($db,"tableoftables","id","tablename",$table);
         $query="INSERT INTO files (id,filename,mime,size,title,tablesfk,ftableid,type) VALUES ($fileid,'$originalname','$mime','$size','$title','$tablesid',$id,'$filestype')";
	 $db->Execute($query);
      }
   }
   return $fileid;
}


////
// !returns an array with id,name,title,and hyperlink to all
// files associated with the given record
function get_files ($db,$table,$id,$format=1) {
   $tableid=get_cell($db,"tableoftables","id","tablename",$table);
   $r=$db->Execute("SELECT id,filename,title,mime,type FROM files WHERE tablesfk=$tableid AND ftableid=$id");
   if ($r && !$r->EOF) {
      $i=0;
      $sid=SID;
      while (!$r->EOF) {
         $filesid=$files[$i]["id"]=$r->fields("id");
         $filesname=$files[$i]["name"]=$r->fields("filename");
         $filestitle=$files[$i]["title"]=$r->fields("title");
         $files[$i]["mime"]=$r->fields("mime");
         $filestype=$files[$i]["type"]=$r->fields("type");
	 if ($format==1) {
            if ($filestitle)
               $text=$filestitle;
            else
                $text=$filesname;
         }
	 elseif ($format==2)
	    $text="file_$i";
	 else
	    $text=$filesname;
         $text.="<br>\n";
         $icon="icons/$filestype.jpg";
         if (is_readable($icon))
            $text="<img src='$icon'>";
         $files[$i]["link"]="<a href='showfile.php?id=$filesid&name=$filesname&$sid'>$text</a>\n";
         $r->MoveNext();
         $i++;
      }
   return $files;
   }
}


////
// !Returns path to the file
function file_path ($db,$fileid) {
   global $system_settings;
   $filename=get_cell($db,"files","filename","id",$fileid);
   return $system_settings["filedir"]."/$fileid"."_$filename";
}



////
// !Deletes file identified with id.
// Checks 'mother table' whether this is allowed
// Returns name of deleted file on succes
function delete_file ($db,$fileid,$USER) {
   global $system_settings;
   $tableid=get_cell($db,"files","tablesfk","id",$fileid);
   $ftableid=get_cell($db,"files","ftableid","id",$fileid);
   $filename=get_cell($db,"files","filename","id",$fileid);
   $table=get_cell($db,"tableoftables","tablename","id",$tableid);
   if (!may_write($db,$table,$ftableid,$USER))
      return false;
   if (unlink($system_settings["filedir"]."/$fileid"."_$filename")) {
      $db->Execute("DELETE FROM files WHERE id=$fileid");
      return $filename;
   }
}


////
// !Prints a table with access rights
// input is string as 'rw-rw-rw-'
// names are same as used in get_access
function show_access ($db,$table,$id,$USER,$global_settings) {
   if ($id) {
      $access=get_cell($db,$table,"access","id",$id);
      $ownerid=get_cell($db,$table,"ownerid","id",$id);
      $groupid=get_cell($db,"users","groupid","id",$ownerid);
      $group=get_cell($db,"groups","name","id",$groupid);
   }
   else {
      $access=$global_settings["access"];
      $group=get_cell($db,"groups","name","id",$USER["groupid"]);
   }   
   echo "<table border=0>\n";
   echo "<tr><th>Access:</th><th>$group</th><th>Everyone</th></tr>\n";
   echo "<tr><th>Read</th>\n";
   if (substr($access,3,1)=="r") $sel="checked"; else $sel=false;
   echo "<td><input type='checkbox' $sel name='grr' value='&nbsp;'></td>\n";
   if (substr($access,6,1)=="r") $sel="checked"; else $sel=false;
   echo "<td><input type='checkbox' $sel name='evr' value='&nbsp;'></td>\n";
   echo "</tr>\n";
   echo "<tr><th>Write</th>\n";
   if (substr($access,4,1)=="w") $sel="checked"; else $sel=false;
   echo "<td><input type='checkbox' $sel name='grw' value='&nbsp;'></td>\n";
   if (substr($access,7,1)=="w") $sel="checked"; else $sel=false;
   echo "<td><input type='checkbox' $sel name='evw' value='&nbsp;'></td>\n";
   echo "</tr>\n";
   echo "</table>\n";
}


////
// !Returns a formatted access strings given an associative array
// with 'grr','evr','grw','evw' as keys
function get_access ($fieldvalues) {
   global $system_settings;

   if (!$fieldvalues)
      return $system_settings["access"];
   $access="rw-------";
   if ($fieldvalues["grr"]) 
      $access=substr_replace($access,"r",3,1);
   if ($fieldvalues["evr"]) 
      $access=substr_replace($access,"r",6,1);
   if ($fieldvalues["grw"]) 
      $access=substr_replace($access,"w",4,1);
   if ($fieldvalues["evw"]) 
      $access=substr_replace($access,"w",7,1);
   return $access;
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
function make_SQL_ids ($r,$ids,$field="id") {
   $id=$r->fields[$field];
   if (!$id)
      return false;
   $ids .="'$id'";
   $r->MoveNext();
   while (!$r->EOF) {
      $id=$r->fields[$field];
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
// !Generates an SQL query asking for the records that mey be seen by this users
// Generates a left join for mysql, subselect for postgres
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
   $ownerid=get_cell($db,$table,"ownerid","id",$id);
   $ownergroup=get_cell($db,"users","groupid","id",$ownerid);
  // $r=$db->Execute("SELECT groupid FROM users LEFT JOIN $table ON 
  //                  users.id=$table.ownerid WHERE $table.id=$id");
   //$ownergroup=$r->fields["groupid"];
   if ($USER["permissions"] & $ADMIN) {
      if ($usergroup==$ownergroup)
         return true;
   }
   if ( ($USER["permissions"] & $WRITE) && $id) {
      $userid=$USER["id"];
      // user write access
      if ($r=$db->Execute("SELECT * FROM $table WHERE id=$id AND
            ownerid=$userid AND SUBSTRING(access FROM 2 FOR 1)='w'")) 
         if (!$r->EOF)
            return true;
      // group write access
      if ($r=$db->Execute("SELECT * FROM $table WHERE id=$id AND
            SUBSTRING(access FROM 5 FOR 1)='w'")) 
         if (!$r->EOF && ($usergroup==$ownergroup) )
            return true;
      // world write access
      if ($r=$db->Execute("SELECT * FROM $table WHERE id=$id AND
            SUBSTRING(access FROM 8 FOR 1)='w'") ) 
         if (!$r->EOF) 
            return true;
   }
}

////
// !Helper function for search
// Interprets fields the right way
function searchhelp ($db,$table,$column,$columnvalues,$query,$wcappend) {
   if ($column=="ownerid") {
      $r=$db->Execute("SELECT id FROM $table WHERE ownerid=$columnvalues[$column]");
      $list=make_SQL_ids($r,false);
      if ($list) 
         $query[1] = "id IN ($list) ";
   }
   else {
      $query[2]=true;
      if (is_string($columnvalues[$column])) {
         $columnvalues[$column]=trim($columnvalues[$column]);
         $columnvalue=$columnvalues[$column];
         $columnvalue=str_replace("*","%",$columnvalue);
         if ($wcappend)
            $columnvalue="%$columnvalue%";
         else
            $columnvalue="% $columnvalue %";
         $query[0].="$column LIKE '$columnvalue' ";
      }
      else
         $query[0].="$column='$columnvalues[$column]' ";
   }
   return $query;
}

////
// !Returns an SQL search statement
// The whereclause should NOT start with WHERE
// The whereclause should contain the output of may_read_SQL and
// can also be used for sorting
function search ($table,$fields,$fieldvalues,$whereclause=false,$wcappend=true) {
   global $db;
   $columnvalues=$fieldvalues;
   $query[0]="SELECT $fields FROM $table WHERE ";
   $query[1]=$query[2]=false;
   $column=strtok($fields,",");
   while ($column && !$columnvalues[$column])
      $column=strtok (",");
   if ($column && $columnvalues[$column]) {
      $query=searchhelp ($db,$table,$column,$columnvalues,$query,$wcappend);
   }
   $column=strtok (",");
   while ($column) { 
      if ($column && $columnvalues[$column]) {
         $query=searchhelp ($db,$table,$column,$columnvalues,$query,$wcappend);
      }
      $column=strtok (",");
   }
   if ($query[1])
      if ($query[2])
         $query[0] .= "AND $query[1] ";
      else
         $query[0] .= "$query[1] ";
   if ($whereclause)
      if ($query[2] ||$query[1])
         $query[0] .= "AND $whereclause";
      else
         $query[0] .= $whereclause;
   return $query[0];
}


?>
