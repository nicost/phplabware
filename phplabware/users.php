<?php


// users.php - List, modify, add, and delete users
// users.php - author: Nico Stuurman<nicost@sourceforge.net>
  /***************************************************************************
  * This script displays a table with users in group of calling admin.       *
  * Functions to add or modify users and add auditors are integrated.        *
  *
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/                                                                                     

$userfields ="id,login,firstname,lastname,pwd,groupid,permissions,email,indir,outdir";

// main global vars
$title = "Admin Users";

// main include calls
require("include.php");

// register variables
$get_vars = "modify,id,audit_add,user_add,userdel";
$post_vars = "email,id,firstname,homedir,lastname,login,modify,outdir,perms,pwd,pwdcheck,status,user_group,";
$post_vars .= "create,trust_array,audit_add,";

globalize_vars ($get_vars, "HTTP_GET_VARS");
globalize_vars ($post_vars, "HTTP_POST_VARS");

////
// !check the form input data for validity
function check_input () {
   global $lastname, $login, $pwd, $user_group, $pwdcheck, $type, $PWD_MINIMUM;
   if ($lastname and $login and $user_group) {
      if ($pwd != $pwdcheck) {
         echo "Passwords do not match! <br> Please try again.";
         return false;
      }
      elseif ($pwd && (strlen($pwd)<$PWD_MINIMUM) ) {
         echo "The password should be at least $PWD_MINIMUM characters long.";
         return false;
      }
      elseif ($type=="create"&& !$pwd) {
         echo "Please provide a password.";
         return false;
      } 
      else
         return true;
   }
   else
      echo "<h5>Some input is lacking!</h5>\n";
   return false;
}

////
// !Deletes users after seom checks
function delete_user ($db, $id) {
   global $USER;

   include ('includes/defines_inc.php');
   $original_permissions=get_cell ($db,"users","permissions","id",$id);

   // check whether this is illegitimate
   if (! (($USER["permissions"] & $SUPER) || 
         (($USER["permissions"] & $ADMIN) && ($USER["groupid"]==$user_group) && 
          ($USER["permissions"] > $original_permissions) ) || 
         ($USER["id"]==$id) ) ) {
      echo "You are not allowed to do this. <br>";
      return false;
   }
   // checks/cleanup should be build in here eventually

   $query="DELETE FROM users WHERE id='$id'";
   if ($db->Execute($query) )
      echo "Deleted user<br>";

}

////
// !Interacts with the SQL database to create/modify users
// can be called to create (type=create) or modify (type=modify) other users or oneselves (type=me) 
function modify ($db, $type) {
   global $HTTP_POST_VARS, $USER, $perms;

   include ('includes/defines_inc.php');

   // creates variables from all values passed by http_post_vars, 
   // skips ones those are already set
   extract ($HTTP_POST_VARS, EXTR_PREFIX_SKIP, "post_vars");
   if($perms)
      for ($i=0; $i<sizeof($perms); $i++)
         $permissions=$permissions | $perms[$i];

   // check status of the victim to check that it is smaller than 
   //  the current users status
   if ($type == "modify")
      $original_permissions=get_cell ($db,"users","permissions","id",$id);

   // check whether this is not illegitimate
   if (! (($USER["permissions"] & $SUPER) || 
         (($USER["permissions"] & $ADMIN) && ($USER["groupid"]==$user_group) && 
          ($USER["permissions"] > $original_permissions) ) || 
         ($USER["id"]==$id) ) ) {
      echo "You are not allowed to do this. <br>";
      return false;
   }

   if (!($status)) $status = $G_USER; 
   if ($type=="modify"  && $id) {
      $query = "UPDATE users SET login='$login', firstname='$firstname', 
                     lastname='$lastname',  
                     groupid='$user_group', email='$email',  
                     permissions='$permissions'";
      if ($pwd) {
          $pwd=md5($pwd);
          $query.=", pwd='$pwd'";
      }
      $query .= " WHERE id='$id';";
      if ($db->Execute($query))
         echo "User <b>$firstname $lastname</b> modified.<br>\n";
      else
         echo "Could not modify user: <b>$firstname $lastname</b>.<br>\n";
   }
   elseif ($type =="create") {
         $id=$db->GenID("users_id_seq");
         $pwd=md5($pwd);
         $query = "INSERT INTO users (id, login, pwd, groupid, firstname, lastname, permissions, email) ";
         $query .= "VALUES('$id','$login','$pwd','$user_group','$firstname','$lastname', '$permissions', '$email')"; 

         if ($db->Execute($query))
            echo "User <b>$firstname $lastname</b> added.<br>\n";
         else
            echo "Failed to add user: <b>$firstname $lastname</b>.<br>\n";
   }
   else 
      echo "Strange error!< Please report to your system administrator<br>\n";
}

////
// !can be called to create (type=create) or modify (type=modify) other users or oneselves (type=me) 
function show_user_form ($type) {
   global $userfields, $HTTP_SERVER_VARS, $perms;
   global $USER, $impose_folder, $infolder, $outfolder, $db;

   include ('includes/defines_inc.php');
 
   // read in essential variables
   $fieldname = strtok ($userfields,",");
   while ($fieldname) {
      global ${$fieldname};
      //echo "$fieldname: ${$fieldname}<br>";
      $fieldname=strtok(","); 
   }

   if($perms)
      for ($i=0; $i<sizeof($perms); $i++)
         $permissions=$permissions | $perms[$i];

   if (!$groupid) $groupid = $USER["groupid"];

   // check whether this is not illegitimate
   if (! ( ($USER["permissions"] & $SUPER) || 
         ( ($USER["permissions"] & $ADMIN) && ($USER["groupid"] & $groupid)  
         && ($USER["permissions"] > $status) ) || 
            ($USER["id"] == $id) ) ) {

      echo "groupid=$groupid<br>";

      echo "You are not allowed to do this. <br>";
      return false;
   }

   echo "<form method='post' action='$PHP_SELF'>\n";
   echo "<input type='hidden' name='id' value='$id'>\n";
   echo "<table>\n";

   echo "<tr><td>First name:</td>\n";
   if ($type=="create" || $type=="modify")
      echo "<td><input type='text' name='firstname' maxlength=50 size=25 value='$firstname'></td></tr>\n";
   elseif ($type=="me")
      echo "<td>$firstname</td></tr>\n";
   echo "<tr><td>Last name:</td>\n";
   if ($type=="create" || $type=="modify")
      echo "<td><input type='text' name='lastname' maxlength=50 size=25 value='$lastname'><sup style='color:red'>&nbsp(required)</sup></td></tr>\n";
   elseif ($type=="me")
      echo "<td>$lastname</td></tr>\n";

   echo "<tr><td>Email Address:</td><td><input type='text' name='email' maxlength=150 size=25 value='$email'></td></tr>\n";

   if ($type == "create")
      echo "<tr><td>Login Name (max. 20 characters):</td><td><input type='text' name='login' maxlength=20 size=20 value='$login'><sup style='color:red'>&nbsp(required)</sup></td></tr>\n";
   else {
      echo "<tr><td>Login Name: </td><td>$login</td></tr>\n";
      echo "<input type='hidden' name='login' value='$login'>\n";
   }
   echo "<tr><td>Password (max. 20 characters):</td><td><input type='password' name='pwd' maxlength=20 size=10 value=''>";
   if ($type=="create")
      echo "<sup style='color:red'>&nbsp(required)</sup></td></tr>\n";
   echo "<tr><td>Password reType(max. 10 characters):</td><td><input type='password' name='pwdcheck' maxlength=10 size=10 value=''>";
   if ($type=="create")
      echo "<sup style='color:red'>&nbsp(required)</sup></td></tr>\n";


   if ($USER["permissions"] & $SUPER) {
      echo "<tr>\n<td>Group:</td>\n<td>";
      $r = $db->Execute("SELECT name,id FROM groups");
      echo $r->GetMenu2("user_group",$groupid,false);
      echo "</td>\n</tr>";
   }
   else {
      echo "<input type=\"hidden\" name=\"user_group\" value=\"" . $USER["groupid"] . "\">";
   }

   // Checkboxes to give user spermissions 
   // set default choice 
   if ( !($permissions) )
      $permissions = $ACTIVE | $READ | $WRITE;
   if ( ($type=="modify" || $type=="create") && 
        ($USER["permissions"] & $ADMIN) ) {
      if ($USER["permissions"] & $SUPER) {
         echo "<tr><td>Group-Admin:</td>\n";
         if ($permissions & $ADMIN)
            $checked = "checked";
         else
            $checked = "";
         echo "<td><input type='checkbox' name='perms[]' value='$ADMIN' $checked></td></tr>\n";
      }
      if ($permissions & $WRITE )
        $checked = "checked";
      else
         $checked = "";
      echo "<tr><td>Write:</td>\n<td><input type='checkbox' name='perms[]' value='$WRITE' $checked></td></tr>\n";

      if ($permissions & $READ)
         $checked = " checked";
      else
         $checked = "";
      echo "<tr><td>Read:</td>\n";
      echo "<td><input type='checkbox' name='perms[]' value='$READ' $checked></td></tr>\n";

      if ($permissions & $ACTIVE)
         $checked = " checked";
      else
         $checked = "";
      echo "<tr><td>Login Allowed:</td>\n";
      echo "<td><input type='checkbox' name='perms[]' value='$ACTIVE' $checked></td></tr>\n";
   }
    
   if ($type == "modify")
      echo "<tr><td></td><td><input type='submit' name='modify' value='Modify User'></td></tr>\n";
   elseif ($type == "create")
      echo "<tr><td></td><td><input type='submit' name='create' value='Create User'></td></tr>\n";
   echo"</table>\n";
   echo "</form>\n";
}


/****************************** main script ***********************************/

// extend title if user is an admin
if ($USER["permissions"] & $ADMIN):
   $title .= " in ".get_cell($db,"groups","name","id",$USER["groupid"]);
endif;

// Only a groupadmin and sysadmin are allowed to view this page
allowonly($ADMIN,$USER["permissions"]);


printheader($title);
navbar($USER["permissions"]);

if ($user_add =="true") {
   show_user_form ("create");
}
elseif ( ($create == "Create User") && get_cell($db,"users","login","login",
                                                $login) ) { 
   echo "<h5>A user with that login name already exists. Please try another one.</h5>\n";
   $login = "";
   show_user_form ("create");
}

elseif ( ($create == "Create User") && !(check_input() )) { 
   show_user_form ("create");
}   

elseif ( ($modify == "Modify User") && !(check_input() )) { 
   show_user_form ("modify");
}   

elseif ($modify == "true") {
   // pull existing data from database
   $query = "SELECT $userfields FROM users WHERE id=$id;";
   $r = $db->Execute($query);
   // and translate into global variables
   $fieldname = strtok ($userfields,",");
   while ($fieldname) {
      ${$fieldname}= $r->fields["$fieldname"];
      //echo "$fieldname: ${$fieldname}<br>";
      $fieldname=strtok(","); 
   }
   show_user_form ("modify");
}

else {
   if ($modify == "Modify User") {
      modify ($db, "modify");
   }
   if ($create == "Create User") {
      modify ($db, "create");
   }
   elseif ($audit_add == 'Add guest') {
      $auditor = last_array_value ($trust_array);
      // first check wether user is indeed outside of group:
      if ($auditor) {
         $query = "SELECT groupid FROM users WHERE id='$auditor';";
         $result = do_database_query ($query);
         $r_array = db_fetch_array ($result,0);
         $groupid = $r_array["groupid"];
      
         if ($S_GROUPID <> $groupid) {
            $query = "INSERT INTO auditors VALUES ($auditor, $S_GROUPID);";
            do_database_query ($query);
         }
      }
   }
   elseif ($HTTP_POST_VARS) {
      //determine wether or not the remove-command is given and act on it
      while((list($key, $val) = each($HTTP_POST_VARS))) {
         if (substr($key, 0, 3) == "del") {
            $delarray = explode("_", $key);
            auditdel($delarray[1], $S_GROUPID);
            $del = true;
         }
      }
   }     
 
   if ($userdel=="true") {
      delete_user($db,$id);
   }

   echo "<form name='form' method='post' action='$PHP_SELF'>\n";

   // set database query
   $db_query = "SELECT * FROM users";

   // if user is not sysadmin then only list users of admin's group
   if ($S_GROUPID != 0):
      $db_query .= " WHERE groupid='$S_GROUPID'";
   endif;


   // extend query to order output list on login name
   $db_query .= " ORDER BY login";

   // print header of table which will list users
   echo "<table border='1'><caption><h5>Users</h5></caption>\n";
   echo "<tr>\n";
   echo "<th>Login</th>\n";
   echo "<th>Real Name</th>\n";
   echo "<th>Group</th>\n";
   echo "<th>Admin</th>\n";
   echo "<th>Write</th>\n";
   echo "<th>Read</th>\n";
   echo "<th>Active</th>\n";
   echo "<th colspan=\"2\">Action</th>\n";
   echo "</tr>\n";


   // get result and number of rows in result
   $r = $db->Execute($db_query);
   while (!$r->EOF) {
      // for each row, print result in table cells

      // display admin dot if status of user is admin
      for ($i=0;$i<4;$i++)
         $stat[$i] = "&nbsp;";
      if ($r->fields["permissions"] & $ADMIN) {
         $stat[0] = "<li>&nbsp;";
      }
      if ($r->fields["permissions"] & $WRITE) {
         $stat[1] = "<li>&nbsp;";
      }   
      if ($r->fields["permissions"] & $READ) {
         $stat[2] = "<li>&nbsp;";
      }   
      if ($r->fields["permissions"] & $ACTIVE) {
         $stat[3] = "<li>&nbsp";
      }
 
      // print table output per row
      echo "<tr>\n";
      echo "<td><b><a href=\"mailto:".$r->fields["email"]."\">".$r->fields["login"]."</a></b></td>\n";
      echo "<td>".$r->fields["firstname"]."&nbsp;".$r->fields["lastname"]."</td>\n";
      echo "<td>".get_cell ($db,"groups","name","id",$r->fields["groupid"])."</td>\n";
      for ($i=0;$i<4;$i++)
         echo "<td align=\"center\">$stat[$i]</td>\n";

      // don't delete/modify yourself, and, except for sysadmin, 
      // do not let admins fool around with other admins in group
      $id = $r->fields["id"];
      if ( ($USER["id"] <> $id) &&  ( !($r->fields["permissions"] & $ADMIN) || ($USER["permissions"] & $SUPER)) ) {
         echo "<td><a href=\"$PHP_SELF?modify=true&id=".$id."\">Modify</a></td>\n";
         echo "<td><a href=\"$PHP_SELF?userdel=true&id=".$id."\">Delete</a></td>\n";
      }
      else
         echo "<td>none</td><td>none</td>";
     
      echo "</tr>\n";
      $r->MoveNext();
   }

   // print end of table
   echo "</table>";

   // print link to perform adjustements made
   echo "<p><a href='$PHP_SELF?user_add=true'>Add User</a></p><br>";

   // if there are any, show a list with auditors
   if ($S_USERID <> $G_SUPER) {
      $query = "SELECT * FROM USERS WHERE id IN (SELECT id FROM auditors WHERE groupid=$S_GROUPID) 
             ORDER BY login";

      // get result and number of rows in result
      $db_result = do_database_query($query);
      $db_rows = db_numrows($db_result);          
      if ($db_rows > 0) {
         // print header of table which will list users
         echo "<table border='1'><caption><h5>Auditors (outside users who may see your group's images)</h5></caption>\n";
         echo "<tr>\n";
         echo "<th>Login</th>\n";
         echo "<th>Real Name</th>\n";
         echo "<th>Group</th>\n";
         echo "<th>Action</th>\n";
         echo "</tr>\n";


         // for each row, print result in table cells
         for ($i=0; $i<$db_rows; $i++) {
            // set database result per row
            $db_row_result = db_fetch_array($db_result, $i);

            // print table output per row
            echo "<tr>\n";
            echo "<td><b><a href=\"mailto:".$db_row_result["email"]."\">".$db_row_result["login"]."</a></b></td>\n";
            echo "<td>".$db_row_result["real_name"]."</td>\n";
            echo "<td>".groupname($db_row_result["groupid"])."</td>\n";
            $delstring = "<input type=\"submit\" name=\"del_" . $db_row_result["id"] . "\" value=\"Remove\" ";
            $delstring .= "Onclick=\"if(confirm('Are you sure this persons ability to see images from this group ";
            $delstring .= "should be revoked?')){return true;}return false;\">";
            echo "<td align=\"center\">$delstring</td></tr>\n";
            echo "</tr>\n";
         }
         echo "</table>\n";
      }
      // and a link to add so-called auditors
      echo "<p><a href=\"$PHP_SELF?audit_add=true\">Give an outsider the right to see images from this group</a></p>\n";

      echo "</form>\n";
   }
}
printfooter();

?>

