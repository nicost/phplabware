<?php
  
// groups.php -  List, modify, delete and add groups
// groups.php - author: Nico Stuurman <nicost@soureforge.net>
  /***************************************************************************
  * This script displays a table with groups in phplabware. It can only be   *
  * called by the sysadmin                                                   *
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/                                             

// main include thingies
require("include.php");

allowonly($SUPER, $USER["permissions"]);

// register variables
$PHP_SELF = $HTTP_SERVER_VARS ["PHP_SELF"];
$get_vars = "mod,groupid,groupname,";
globalize_vars ($get_vars,$HTTP_GET_VARS);
$post_vars = "add,groupid,groupname,submit,";
globalize_vars ($post_vars, $HTTP_POST_VARS);

// main global vars
$title = "Admin Groups";

////
// !Adds group to database.
// An error string is returned when problems occur 
function add_new_group ($db,$groupname) {

   // check if a groupname is entered
   if ($groupname) {
      $query = "SELECT * FROM groups WHERE name='$groupname'";
      $r = $db->Execute($query);
      // test if a result is found
      if (!$r->EOF) {
          return "groupname already exists; please try again";
      }
      else {
         $id=$db->GenID("groups_id_seq");
         if ($db->Execute("INSERT INTO groups(id,name) VALUES('$id','$groupname') ") ) {
            echo "Group <b>$groupname</b> added,";
         }
         else 
            echo "Group was not added<br>";
      }      
   }
   else
      return "Please enter a groupname!";
}

////
// !Change that groupname.  
// On error, returns an error string
function modify_group ($db, $groupid, $new_name) {
   // Only change the name when there is a new one provided
   if (!$new_name)
      return "Please enter a groupname!";

   // check whether a group with the new name already exists
   $r = $db->Execute ("SELECT id FROM groups WHERE 
                   (name='$new_name' AND NOT id='$groupid');");
   if (!$r->EOF)
      return "Groupname <b>$new_name</b> already exists, please select another name";     
   // now change the name
   else {
      // get the old name
      $old_name = get_cell($db,"groups","name","id",$groupid);
      $query = "UPDATE groups SET name='$new_name' WHERE id='$groupid';";
      if ($db->Execute($query))
         echo "New groupname is <b>$new_name</b><br><br>";
   }
}


////
// !Delete given group
// The group is only deleted when it has no users
function delete_group ($db, $groupid) {

   // check if a groupname is entered
   if (!$groupid) 
      return false;
 
   $query = "SELECT name FROM groups WHERE id='$groupid'";
   $r = $db->Execute($query);
 
   // just in case, test if a result has been found
   if ($r->EOF)
      echo "Group does not exists; Nothing to delete.";
   else {
      // get groupname from database result
      $groupname = $r->fields["name"];
 
      // test if group has no more users
      $users_query = "SELECT id FROM users WHERE groupid='$groupid'";
      $r2 = $db->Execute($users_query);
      if (!$r2->EOF) {
         echo "Group <b>$groupname</b> can not be removed since it still has users!!<br>";
         echo "First delete all users, then remove the group.<br>";
      }
      else { 
         // remove given group entry from database
         $db_remove_query = "DELETE FROM groups WHERE id=$groupid";
         if ($db->Execute($db_remove_query))
            echo "Group <b>$groupname</b> has been deleted.";
         else
            echo "Failed to remove group <b>$groupname</b>.";
      }
   }
}


////
// !Displays form to modify or add a group
// if groupid is given the groupname will be modified, otherwise add a new group
function group_form ($groupid, $groupname) {
   global $PHP_SELF;
?>
<form method='post' action='<?php echo $PHP_SELF ?>?<?=SID?>'>
<?php
   if ($groupid)
      echo "<input type='hidden' name='groupid' value='$groupid'>\n";
   echo "<table align='center'>\n";
   echo "<tr><td>New Group Name:</td>\n";
   echo "<td><input type='text' name='groupname' value='$groupname'></td></tr>\n";
   echo "<tr><td colspan=2 align='center'>";
   if ($groupid)
      echo "<input type='submit' name='submit' value='Modify Group'>";
   else
      echo "<input type='submit' name='submit' value='Add Group'>";
   echo "</td></tr>\n";
   echo "</table>\n";
   echo "</form>\n";
 
}
/****************************************************************/

printheader("Groups of PhpLabware");
navbar($USER["permissions"]);

// when the 'Add a new Group' button has been chosen: 
if ($add)
   group_form ("","");

// when modify has been pressed:
elseif ($mod == "true")
   group_form ($groupid, $groupname);

else {
   // print header of table
   echo "<table border=\"1\" align=center >\n";
   echo "<caption>\n";
   // first handle addition of a new group
   if ($submit == "Add Group") {
      if ($test = add_new_group ($db, $groupname) ) {
         echo "</caption>\n</table>\n";
         echo "<table align='center'><caption>$test</caption></table>";
         group_form ("",$groupname);
         printfooter ();
         exit;
      }
   }
   // then look whether groupname should be modified
   elseif ($submit =="Modify Group") {
      if ($test = modify_group ($db,$groupid, $groupname)) {
         echo "</caption>\n</table>\n";
         echo "<table align='center'><caption>$test</caption></table>";
         group_form ($groupid, $groupname);
         printfooter ();
         exit;
      }
   } 
  //determine wether or not the remove-command is given and act on it
   elseif ($HTTP_POST_VARS) {
      while((list($key, $val) = each($HTTP_POST_VARS))) {
         if (substr($key, 0, 3) == "del") {
            $delarray = explode("_", $key);
            delete_group($db, $delarray[1]);
         }
         if (substr($key, 0, 3) == "mod") {
            $modarray = explode("_", $key);
            echo "</caption>\n</table>\n";
            group_form($modarray[1],get_cell($db,"groups","name","id",$modarray[1]) );
            printfooter();
            exit();
         }
      }
   } 

   echo "</caption>\n";
   // print form needed for 'delete' buttons
?>
<form name='form' method='post' action='<?php echo $PHP_SELF?>?<?=SID?>'>
<?php
   echo "<tr>\n";
   echo "<th>Group</th>";
   echo "<th>Admins</th>";
   echo "<th>Users</th>";
   echo "<th colspan=\"2\">Action</th>\n";
   echo "</tr>\n";

   // retrieve all groups and their info from database
   $query = "SELECT * FROM groups ORDER BY name";
   $r=$db->Execute($query);
   // print all group admins per group in table cells
   while (!($r->EOF)) {
 
      // get results of each row
      $groupid = $r->fields["id"];
      $groupname = $r->fields["name"];
      $adminid = $r->fields["adminid"];

      // print start of row of selected group
      echo "<tr>\n";
      echo "<td>$groupname</td>\n";

      // get names of admins belonging to selected group
      $query2="SELECT firstname,lastname,id,login FROM users WHERE groupid='$groupid' 
               AND (permissions >= $ADMIN)";
      $r2=$db->Execute($query2);
      // if number of rows greater than zero then print found results
      if (!$r2->EOF) { 
         echo "<td>";
         while (!$r2->EOF) {
            $username = $r2->fields["firstname"]." ".$r2->fields["lastname"];
            if ($username==" ")
               $username=$r2->fields["login"];
            echo "<b>".$username."</b><br>";
            $r2->MoveNext();
         }
         echo "</td>\n";
      }
      else
         echo "<td>&nbsp;</td>\n";

      // get names of users belonging to selected group
      $query2="SELECT firstname,lastname,id,login FROM users WHERE groupid='$groupid' 
               AND (permissions < $ADMIN)";
      $r2=$db->Execute($query2);
      // if number of rows greater than zero then print found results
      if (!$r2->EOF) { 
         echo "<td>";
         while (!$r2->EOF) {
            $username = $r2->fields["firstname"]." ".$r2->fields["lastname"];
            if ($username==" ")
               $username=$r2->fields["login"];
            echo "<b>".$username."</b><br>";
            $r2->MoveNext();
         }
         echo "</td>\n";
      }
      else
         echo "<td>&nbsp;</td>\n";


      // print last columns with links to adjust group
      $modstring = "<input type=\"submit\" name=\"mod_" . $groupid . "\" value=\"Modify\">";
      echo "<td align='center'>$modstring</td>\n";
      $delstring = "<input type=\"submit\" name=\"del_" . $groupid . "\" value=\"Remove\" ";
      $delstring .= "Onclick=\"if(confirm('Are you sure the group $groupname ";
      $delstring .= "should be removed?')){return true;}return false;\">";                                           
      echo "<td align='center'>$delstring</td>\n";
      echo "</tr>\n";
   
      $r->MoveNext();
   }

   // print footer of table
   echo "<tr><td colspan=5 align='center'>";
   echo "<input type=\"submit\" name=\"add\" value=\"Add Group\">";
   echo "</td></tr>";
   echo "</table>\n";
   echo "</form>\n";

}

printfooter();

?>
