<?php


// users.php - List, modify, add, and delete users
// users.php - author: Nico Stuurman<nicost@sourceforge.net>
// TABLES: users
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

$userfields ='id,login,firstname,lastname,pwd,groupid,permissions,email,indir,outdir';

// main include calls
require('include.php');

// register variables
$post_vars = 'email,id,firstname,lastname,login,me,modify,perms,pwd,pwdcheck,user_group,user_add_groups,';
$post_vars .= 'create,user_add,';

if (!$type)
   $type=$HTTP_GET_VARS['type'];
globalize_vars ($post_vars, $HTTP_POST_VARS);


////
// !check the form input data for validity
function check_input () {
   global $lastname, $login, $pwd, $user_group, $pwdcheck, $type, $PWD_MINIMUM;
   if ($lastname and $login and $user_group) {
      if ($pwd != $pwdcheck) {
         echo "<h5 align='center'>Passwords do not match! <br> Please try again.</h5>";
         return false;
      }
      elseif ($pwd && (strlen($pwd)<$PWD_MINIMUM) ) {
         echo "<h5 align='center'>The password should be at least $PWD_MINIMUM characters long.</h5>";
         return false;
      }
      elseif ($type=='create' && !$pwd) {
         echo "<h5 align='center'>Please provide a password.</h5>";
         return false;
      } 
      else
         return true;
   }
   else
      echo "<h5 align='center'>Some input is lacking!</h5>\n";
   return false;
}


////
// !Generates a comma-separated list of tables holding data
// tablenames are read from tableoftables
function tablestring ($db) {
   $r=$db->Execute("SELECT id,real_tablename FROM tableoftables WHERE tablename <> 'settings' AND permission <> 'System' ORDER BY id");
   while (!$r->EOF) {
      $string.=$r->fields['real_tablename'];
      $string.=",";
      $r->Movenext();
   }
   // chop of last comma
   return substr ($string,0,-1);  
}


////
// !Deletes users after some checks
function delete_user ($db, $id) {
   global $USER;

   include ('includes/defines_inc.php');
   $tables=tablestring($db);
   $original_permissions=get_cell ($db,'users','permissions','id',$id);
   $original_login=get_cell($db,'users','login','id',$id);
   if (!$original_login)
      return true;

   // check whether this is illegitimate
   if (! (($USER['permissions'] & $SUPER) || 
         (($USER['permissions'] & $ADMIN) && ($USER['groupid']==$user_group) && 
          ($USER['permissions'] > $original_permissions) ) || 
         ($USER['id']==$id) ) ) {
      echo "You are not allowed to do this. <br>";
      return false;
   }
   // cleanup records owned by this user
   $db->BeginTrans();
   $test=true;
   if ($tables) {
      $table=strtok($tables,",");
      while ($table) {
         $query="DELETE FROM $table WHERE ownerid='$id'";
         if (!$db->Execute($query))
            $test=false;
         $table=strtok (",");
       }
   }
   $query="DELETE FROM users WHERE id='$id'";
   if (!$db->Execute($query) )
      $test=false;
   if ($test) {
      if ($db->CommitTrans()) {
         echo "User <i>$original_login</i> was succesfully deleted.";
         return true;
      }
   }
   $db->RollbackTrans();
   echo "Failed to remove user <i>$original_login</i>.";
   return true;
}


////
// !Interacts with the SQL database to create/modify users
// can be called to create (type=create) or modify (type=modify) other users or oneselves (type=me) 
function modify ($db, $type) {
   global $HTTP_POST_VARS, $USER, $perms, $post_vars;

   $id=$HTTP_POST_VARS['id'];
   $login=$HTTP_POST_VARS['login'];
   $pwd=$HTTP_POST_VARS['pwd'];
   $user_group=$HTTP_POST_VARS['user_group'];
   $user_add_groups=$HTTP_POST_VARS['user_add_groups'];
   $firstname=$HTTP_POST_VARS['firstname'];
   $lastname=$HTTP_POST_VARS['lastname'];
   $email=$HTTP_POST_VARS['email'];
   $USER['settings']['style']=$HTTP_POST_VARS['user_style'];

   if($perms)
      for ($i=0; $i<sizeof($perms); $i++)
         $permissions=$permissions | $perms[$i];
   if (!$permissions)
      $permissions=0;

   // include here, to avoid being overwritten by post_vars 
   include ('includes/defines_inc.php');

   // check whether status of the victim is smaller than 
   //  the current users status
   if ($type == 'modify')
      $original_permissions=get_cell ($db,'users','permissions','id',$id);

   // check whether this is not illegitimate
   if (! (($USER['permissions'] & $SUPER) || 
         (($USER['permissions'] & $ADMIN) && ($USER['groupid']==$user_group) && 
          ($USER['permissions'] > $original_permissions) ) || 
         ($USER['id']==$id) ) ) {
      echo 'You are not allowed to do this. <br>';
      return false;
   }

   // log some info
   $theid=$USER['id'];
   $theip=getenv('REMOTE_ADDR');
   $thedate=time();

   if ($type=='modify'  && $id) {
      $query = "UPDATE users SET login='$login', firstname='$firstname', 
                     lastname='$lastname',  
                     groupid='$user_group', email='$email',  
                     permissions='$permissions', modbyid='$theid',
		     modbyip='$theip', moddate='$thedate'";
      if ($pwd) {
          $pwd=md5($pwd);
          $query.=", pwd='$pwd'";
      }
      $query .= " WHERE id='$id';";
      if ($db->Execute($query)) {
         echo "Modified settings of user <i>$firstname $lastname</i>.<br>\n";
	 $db->Execute ("DELETE FROM usersxgroups WHERE usersid=$id");
	 if ($user_add_groups)
	    foreach ($user_add_groups AS $add_groupid)
	       $db->Execute("INSERT INTO usersxgroups VALUES ('$id','$add_groupid')");
      }    
      else
         echo "Could not modify settings of user: <i>$firstname $lastname</i>.<br>\n";
   }
   elseif ($type =='create') {
         $id=$db->GenID('users_id_seq');
         $pwd=md5($pwd);
         $new_user_settings['menustyle']=1;
         $new_user_settings=serialize($new_user_settings);
         $query = "INSERT INTO users (id, login, pwd, groupid, firstname, lastname, permissions, email, createdbyid, createdbyip, createddate, settings) ";
         $query .= "VALUES('$id','$login','$pwd','$user_group','$firstname','$lastname', '$permissions', '$email', '$theid', '$theip', '$thedate', '$new_user_settings')"; 

         if ($db->Execute($query)) {
            echo "User <i>$firstname $lastname</i> added.<br>\n";
	    if ($user_add_groups)
	       foreach ($user_add_groups AS $add_groupid)
	          $db->Execute("INSERT INTO usersxgroups VALUES ('$id','$add_groupid')");
         } 
         else
            echo "Failed to add user: <i>$firstname $lastname</i>.<br>\n";
   }
   elseif ($type=='me'  && $id) {
      $query = "UPDATE users SET firstname='$firstname', 
                     lastname='$lastname',  
                     email='$email',
		     modbyid='$theid',
		     moddate='$thedate',
		     modbyip='$theip'";  
      if ($pwd) {
          $pwd=md5($pwd);
          // require at least write permissions to change the password
          if ($USER["permissions"] >= $WRITE) 
             $query.=", pwd='$pwd'";
      }
      $query .= " WHERE id='$id';";
      $result.="\n<table border=0 align='center'>\n  <tr>\n    <td align='center'>\n      ";
      if ($db->Execute($query)) {
         // modify menu view in settings
         if ($HTTP_POST_VARS['menustyle']==1)
            $USER['settings']['menustyle']=1;
         else
            $USER['settings']['menustyle']=0;
         $result.= "Your settings have been modified.<br>\n";
         // superuser can do whatever he please also with herself
         if ($USER['permissions'] & $SUPER) {
            $db->Execute ("DELETE FROM usersxgroups WHERE usersid=$id");
	    if ($user_add_groups)
	       foreach ($user_add_groups AS $add_groupid)
	          $db->Execute("INSERT INTO usersxgroups VALUES ('$id','$add_groupid')");
         }
      }
      else
         $result.="Failed to modify you settings.<br>\n";
      $result.="    </td>\n  </tr>\n</table>\n\n";
   }
   else 
      $result.= "Strange error!< Please report to your system administrator<br>\n";
   return $result;
}

////
// !can be called to create (type=create) or modify (type=modify) other users or oneselves (type=me) 
function show_user_form ($type) {
   global $userfields, $HTTP_SERVER_VARS, $perms, $USER, $db, $system_settings;
   global $HTTP_SESSION_VARS;

   include ('includes/defines_inc.php');
 
   // read in essential variables
   $fieldname = strtok ($userfields,",");
   while ($fieldname) {
      global ${$fieldname};
      $fieldname=strtok(","); 
   }

   if($perms)
      for ($i=0; $i<sizeof($perms); $i++)
         $permissions=$permissions | $perms[$i];

   if (!$groupid) $groupid = $USER["groupid"];

   // check whether this is not illegitimate
   if (! ( ($USER['permissions'] & $SUPER) || 
         ( ($USER['permissions'] & $ADMIN) && ($USER['groupid'] & $groupid)  
         && ($USER['permissions'] > $status) ) || 
            ($USER['id'] == $id) ) ) {

      echo "<h3 align='center'>You are not allowed to do this. </h3>";
      return false;
   }
?>
<form method='post' action='<?php echo $PHP_SELF?>?<?=SID?>'>
<?php
   echo "<input type='hidden' name='id' value='$id'>\n";
   echo "<table align='center'>\n";

   echo "<tr><td>First name:</td>\n";
   echo "<td><input type='text' name='firstname' maxlength=50 size=25 value='$firstname'></td></tr>\n";
   echo "<tr><td>Last name:</td>\n";
   echo "<td><input type='text' name='lastname' maxlength=50 size=25 value='$lastname'><sup style='color:red'>&nbsp(required)</sup></td></tr>\n";
   echo "<tr><td>Email Address:</td><td><input type='text' name='email' maxlength=150 size=25 value='$email'></td></tr>\n";

   if ($type == 'create')
      echo "<tr><td>Login Name (max. 20 characters):</td><td><input type='text' name='login' maxlength=20 size=20 value='$login'><sup style='color:red'>&nbsp(required)</sup></td></tr>\n";
   else {
      echo "<tr><td>Login Name: </td><td>$login</td></tr>\n";
      echo "<input type='hidden' name='login' value='$login'>\n";
   }
   if ($type=='me') {
      echo "<tr><td>Menu display: </td>";
      if ($USER['settings']['menustyle'])
         $dchecked='checked';
      else
         $schecked='checked';
      echo "<td><input type='radio' name='menustyle' $schecked value='0'>scattered &nbsp;&nbsp;<input type='radio' name='menustyle' $dchecked value='1'>drop-down</td></tr>";
      echo "<tr><td>Style: </td>";
      // if there is no pref, set it to default
      if (!isset($USER['settings']['style'])) {
         $USER['settings']['style']='phplabware.css';
      }
      // list available stylesheets in folder stylesheets:
      echo '<td><select name="user_style">'."\n";
      $d=dir ('stylesheets');
      while (false !== ($file=$d->read())) {
         if (substr($file,0,10)=='phplabware') {
             if (substr($file,10,1)=='_') {
                 $entry=substr($file,11,-4)."<br>\n";
             } else {
                 $entry='default';
             }
             if ($file==$USER['settings']['style']) {
                $selected='selected';
             } else {
                 unset($selected);
             }
             echo "<option $selected value=\"$file\">$entry</option>\n";
         }
      }
      $d->close();
      echo "</select>\n</td></tr>\n";
      
      
   }
   
   if ($USER['permissions'] & $SUPER) {
      echo "<tr>\n<td>Primary group:</td>\n<td>";
      $r = $db->Execute('SELECT name,id FROM groups');
      echo $r->GetMenu2('user_group',$groupid,false);
      echo "</td>\n</tr>";
      echo "<tr>\n<td>Additional groups:</td>\n<td>";
      $r=$db->Execute("SELECT groupsid FROM usersxgroups WHERE usersid=$id");
      while ($r && !$r->EOF) {
         $add_groups[]=$r->fields['groupsid'];
	 $r->MoveNext();
      }
      $r = $db->Execute("SELECT name,id FROM groups");
      echo $r->GetMenu2("user_add_groups[]",$add_groups,true,true,3);
      echo "</td>\n</tr>";
   }
   else {
      echo "<input type=\"hidden\" name=\"user_group\" value=\"" . $USER["groupid"] . "\">";
   }

   if ($USER['permissions'] >= $WRITE && ($system_settings['authmethod'] <> 2
         || ($type=='me' && $HTTP_SESSION_VARS['authmethod']=='sql') 
         || $type=='create') ) {
      echo "<tr><td>Password (max. 20 characters):</td><td><input type='password' name='pwd' maxlength=20 size=20 value=''>";
      if ($type=='create')
         echo "<sup style='color:red'>&nbsp(required)</sup></td></tr>\n";
      echo "<tr><td>Password reType(max. 20 characters):</td><td><input type='password' name='pwdcheck' maxlength=20 size=20 value=''>";
      if ($type=='create')
         echo "<sup style='color:red'>&nbsp(required)</sup></td></tr>\n";
      if ($type=='modify' || $type=='me')
         echo "<tr><td colspan=2 align='center'>Leave the password fields blank to keep the current password</td></tr>\n";
      if ($type=='create' && $system_settings['authmethod']==2)
         echo "<tr><td colspan=2 align='center'>Leave the password fields blank to force PAM-based authentification</td></tr>\n";
/*      if ($permissions & $ACCESS_EDIT )
        $checked = "checked";
      else
         $checked = '';
      echo "<tr><td>Control access settings:</td>\n<td><input type='checkbox' name='perms[]' value='$ACCESS_EDIT' $checked></td></tr>\n";
*/
   }


   // Checkboxes to give user permissions 
   // set default choice 
   if ($type=='create' &&  !($permissions) )
      $permissions = $ACTIVE | $READ | $WRITE;
   if ( ($type=='modify' || $type=='create') && 
        ($USER['permissions'] & $ADMIN) ) {
      if ($USER['permissions'] & $SUPER) {
         echo "<tr><td>Group-Admin:</td>\n";
         if ($permissions & $ADMIN)
            $checked = 'checked';
         else
            $checked = '';
         echo "<td><input type='checkbox' name='perms[]' value='$ADMIN' $checked></td></tr>\n";
      }
      echo "<tr><td>Layout tables:</td>\n";
      if ($permissions & $LAYOUT)
         $checked = 'checked';
      else
         $checked = '';
      echo "<td><input type='checkbox' name='perms[]' value='$LAYOUT' $checked></td></tr>\n";

      if ($permissions & $WRITE )
        $checked = 'checked';
      else
         $checked = '';
      echo "<tr><td>Write:</td>\n<td><input type='checkbox' name='perms[]' value='$WRITE' $checked></td></tr>\n";

      if ($permissions & $READ)
         $checked = ' checked';
      else
         $checked = '';
      echo "<tr><td>Read:</td>\n";
      echo "<td><input type='checkbox' name='perms[]' value='$READ' $checked></td></tr>\n";

      if ($permissions & $ACTIVE)
         $checked = ' checked';
      else
         $checked = '';
      echo "<tr><td>Login Allowed:</td>\n";
      echo "<td><input type='checkbox' name='perms[]' value='$ACTIVE' $checked></td></tr>\n";
   }
    
   if ($type == 'modify')
      echo "<tr><td colspan=2 align='center'><input type='submit' name='modify' value='Modify User'></td></tr>\n";
   elseif ($type == 'create')
      echo "<tr><td colspan=2 align='center'><input type='submit' name='create' value='Create User'></td></tr>\n";
   elseif ($type == 'me')
      echo "<tr><td colspan=2 align='center'><input type='submit' name='me' value='Change Settings'></td></tr>\n";
   echo"</table>\n";
   echo "</form>\n";
}


/****************************** main script ***********************************/

allowonly($ACTIVE,$USER['permissions']);


if ($type=='me') {
   $title .= 'Personal Settings';
   printheader($title);
   navbar($USER['permissions']);
   // pull existing data from database
   $query = "SELECT $userfields FROM users WHERE id=$USER[id];";
   $r = $db->Execute($query);
   $fieldname = strtok ($userfields,',');
   while ($fieldname) {
      ${$fieldname}= $r->fields["$fieldname"];
      $fieldname=strtok(','); 
   }
   show_user_form('me');
   printfooter($db,$USER);
   exit();
}
if ($me=='Change Settings') {
   $title.='Change Settings';
   $result=modify ($db, 'me');
   printheader($title);
   navbar ($USER['permissions']);
   echo $result;
   show_user_form('me');
   printfooter($db,$USER);
   exit();
}

// Only a groupadmin and sysadmin are allowed to view the remainder
allowonly($ADMIN,$USER['permissions']);

// set title and print headers
$title.='User administration';
// extend title if user is an admin
if (!($USER['permissions'] & $SUPER))
   $title .= ' in group '.get_cell($db,'groups','name','id',$USER['groupid']);
printheader($title);
navbar($USER['permissions']);

// Check whether modify or delete button has been chosen
$del=false;
$mod=false;
if ($HTTP_POST_VARS) {
   //determine wether or not the remove-command is given and act on it
   while((list($key, $val) = each($HTTP_POST_VARS))) {
      if (substr($key, 0, 3) == "del") {
         $delarray = explode("_", $key);
         $del = true; 
      }
      if (substr($key, 0, 3) == "mop") {
         $modarray = explode ("_", $key);
         $mod = true;
      } 
   }
} 

if ($user_add =="Add User") {
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

elseif ($mod==true) {
   // pull existing data from database
   $query = "SELECT $userfields FROM users WHERE id=$modarray[1];";
   $r = $db->Execute($query);
   $fieldname = strtok ($userfields,",");
   while ($fieldname) {
      ${$fieldname}= $r->fields["$fieldname"];
      $fieldname=strtok(","); 
   }
   show_user_form ("modify");
}

else {
   echo "<table align='center' border='1'><caption><h5>";
   if ($modify == "Modify User") {
      modify ($db, "modify");
   }
   if ($create == "Create User") {
      modify ($db, "create");
   }
   if ($del==true) {
      if (!delete_user($db,$delarray[1])) {
         echo "</table>\n";
         printfooter();
         exit();
      }
   }
   echo "</h5></caption>\n";
?>
<form method='post' name='form' action='<?php echo $PHP_SELF?>?<?=SID?>'>
<?php
   // set database query
   $db_query = "SELECT * FROM users";

   // if user is not sysadmin then only list users of admin's group
   if (! ($USER['permissions'] & $SUPER))
      $db_query .= " WHERE groupid='" .$USER['groupid']."'";

   // extend query to order output list on login name
   $db_query .= " ORDER BY login";

   // print header of table which will list users
   echo "<tr>\n";
   echo "<th>Login</th>\n";
   echo "<th>Real Name</th>\n";
   echo "<th>Primary<br>Group</th>\n";
   echo "<th>Additional<br>Groups</th>\n";
   echo "<th>Admin</th>\n";
   echo "<th>Write</th>\n";
   echo "<th>Read</th>\n";
   echo "<th>Active</th>\n";
   echo "<th>Created</th>\n";
   echo "<th>Modified</th>\n";
   echo "<th colspan=\"2\">Action</th>\n";
   echo "</tr>\n";

   $dateformat=get_cell($db,"dateformats","dateformat","id",$system_settings["dateformat"]);

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
      $ra=$db->Execute("SELECT groupsid FROM usersxgroups WHERE usersid='".$r->fields["id"]."'");
      echo "<td>";
      if (!$ra || $ra->EOF)
         echo "&nbsp";
      else 
         while (!$ra->EOF) {
            echo get_cell($db,"groups","name","id",$ra->fields["groupsid"])."<br>";
	    $ra->MoveNext();
         }
      echo "</td>\n";
      for ($i=0;$i<4;$i++)
         echo "<td align=\"center\">$stat[$i]</td>\n";

      if ($r->fields["createddate"])
         $createddate=date($dateformat,$r->fields["createddate"]);
      else
         $createddate="&nbsp;";
	 
      echo "<td>$createddate</td>\n";
      if ($r->fields["moddate"])
         $moddate=date($dateformat,$r->fields["moddate"]);
      else
         $moddate="&nbsp;";
      echo "<td>$moddate</td>\n";

      // don't delete/modify yourself, and, except for sysadmin, 
      // do not let admins fool around with other admins in group
      $id = $r->fields["id"];
      if ( ($USER["id"] <> $id) &&  ( !($r->fields["permissions"] & $ADMIN) 
                                    || ($USER['permissions'] & $SUPER)) ) {
         $modstring="<input type=\"submit\" name=\"mop_".$id."\" value=\"Modify\">";
         $delstring="<input type=\"submit\" name=\"del_".$id."\" value=\"Remove\" ";
         $delstring.="Onclick=\"if(confirm('Do you really want to delete user: ";
         $delstring.= $r->fields["firstname"]." ".$r->fields["lastname"]. 
               ", and all his/her database entries? (NO UNDO POSSIBLE!)')){return true;}return false;\">";
         echo "<td align=\"center\">$modstring</td>\n";
         echo "<td align=\"center\">$delstring</td>\n";
      }
      else
         echo "<td align='center'>&nbsp;</td><td align='center'>&nbsp;</td>";
     
      echo "</tr>\n";
      $r->MoveNext();
   }

   echo "<tr border=0><td colspan=12 align='center'>";
   echo "<INPUT align='center' TYPE='submit' NAME='user_add' VALUE='Add User'></INPUT>\n";
   echo "</td></tr>\n";
   echo "</form>\n";
 
  echo "</table>";


}
printfooter($db,$USER);

?>

