<?php

// functions.php - Functions for all scripts
// functions.php - author: Nico Stuurman <nicost@sourceforge.net>
  /***************************************************************************
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  Part of phplabware, a web-driven groupware suite for research labs      *
  *  This file contains classes and functions needed in all script.          *
  *                                                                          *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/  

////
// !class to get and store browser/OS info
class cl_client {
   var $browser;
   var $OS;

   function cl_client () {
      $HTTP_USER_AGENT = getenv (HTTP_USER_AGENT);
      $temp = strtolower ($HTTP_USER_AGENT);
      if (strstr($temp, "opera"))
         $this->browser = "Opera";
      elseif (strstr($temp, "msie"))
         $this->browser = "Internet Explorer";
      else
         $this->browser = "Netscape or the like";
      if (strstr($temp, "windows"))
         $this->OS = "Windows";
      elseif (strstr($temp, "linux"))
         $this->OS = "Linux";
      elseif (strstr($temp, "sunos"))
         $this->OS = "Sun";
      elseif (strstr($temp, "mac"))
         $this->OS = "Mac OS";
      elseif (strstr($temp, "irix"))
         $this->OS = "IRIX";
   }
}      

////
// !function that checks if user is allowed to view page
// This function should be called before a printheader() call
function allowonly($required, $current) {
   if (! ($required & $current)) {
      printheader("Not Allowed");
      navbar(1);
      echo "&nbsp;<br><h3 align='center'>Sorry, but this page is not for you!";
      echo "</hr><br>&nbsp;\n";
      printfooter();
      exit;
   }
}


////
// returns a link to a randomly selected image from the designated directory
// imagedir should be relative to the running script and be web-accessible
function randomImage($imagedir) {
   // determine contents of imagedir and store imagefilenames in imagearray
   $dirhandle = opendir ("$imagedir");
   if (!$dirhandle)
      return false;
   $j = 0;
   while ($file = readdir ($dirhandle)) {
      if (strstr ($file, ".png") || strstr($file, ".jpg") || 
                                    strstr($file,".gif") ) {
         $imagearray[$j]=$file;
         $j++;
      }
   }
   $filecount=sizeof ($imagearray);
   // no files
   if (!$filecount)
      return false;
   // 'select' a random file
   srand((double)microtime()*1000000);
   if ($filecount>1)
      $filenr=rand (0,$filecount-1);
   else
      $filenr=0;
   // construct link to randomly selected file
   $filename=$imagearray[$filenr];
   return "<img src='$imagedir/$filename'>\n";   
}


////
// !presents the login screen when authenticating witth sessions
function loginscreen ($message="<h3>Login to PhpLabWare</h3>") {
   global $HTTP_SERVER_VARS, $system_settings;

   $PHP_SELF=$HTTP_SERVER_VARS["PHP_SELF"];
   if ($system_settings["secure_server"]) {
      $server= getenv ("HTTP_HOST");
      $addres="https://$server$PHP_SELF";
   }
   else
      $addres=$PHP_SELF;

   printheader ("Login to PhpLabWare");
   echo "<form name='loginform' method='post' action=$addres>\n";
   echo "<input type='hidden' name='logon' value='true'>\n";
   echo "<table align=center>\n";
   echo "<tr><td colspan=2 align='center'>$message</td>\n";
   $imstring = randomimage("frontims");
   if ($imstring);
      echo "<td rowspan=6>&nbsp;&nbsp&nbsp;$imstring</td>";
   echo "</tr>\n";
   echo "<tr><td>Your login name:</td>\n";
   echo "<td><input name='user' size=10 value=''></td></tr>\n";
   echo "<tr><td>Password:</td>\n";
   echo "<td><input type='password' name='pwd' size=10 value=''></td></tr>\n";
   echo "<tr><td colspan=2 align='center'>";
   if ($system_settings["secure_server"]) {
      echo "<input type='checkbox' name='ssl'>Keep a secure connection";
   }
   echo "</td></tr>\n";
   echo "<tr><td colspan=2 align='center'>";
   echo "<input type='submit' name='submit' value='Login'></td></tr>\n";
   echo "<tr><td colspan=2 align='center'>";
   //echo "Note:  Cookies must be enabled beyond this point</td></tr>\n";
   echo "</table>\n";
   printfooter();
}


////
// !checks wether variables are present in ${type} and makes them available
// variables are only set when they are not null in ${type}
// $var_string is a comma delimited list
function globalize_vars ($var_string, $type) {

   if ($var_string && $type) {
      $var_name = strtok ($var_string, ",");
      global ${$var_name};
      if (!${$var_name})
         ${$var_name} = $type["$var_name"];
      while ($var_name) {
         $var_name = strtok (",");
         global ${$var_name};
         if (!${$var_name})
            ${$var_name} = $type["$var_name"];
      }
   }
}


////
// !Return the value of specified cell in the database
// Returns false if no or multiple rows have requested value 
function get_cell ($db, $table, $column, $column2, $value) {
   $query="SELECT $column FROM $table WHERE $column2='$value'";
   $result=$db->Execute($query);
   if ($result) {
      $out=$result->fields[$column];
   }
   else {
      return false;
   }
   $result->MoveNext();
   if ($result->EOF)
      return $out;
   else {
      return false;
   }
}


////
// !Prints a table with usefull links 
function navbar($permissions) {
   include ('includes/defines_inc.php');

   echo "<table border=0 width=100%>\n";
   echo "<tr bgcolor='eeeeff' align='right'>\n";
   if ($permissions & $ACTIVE) {
      ?>
      <td align='center'><a href="antibodies.php?<?=SID?>">antibodies</a></td>
      <td align='center'><a href="users.php?type=me&<?=SID?>">settings</a></td>
      <?php
   }
   if ($permissions & $ADMIN) {
      ?>
      <td align='center'><a href="users.php?<?=SID?>">users</a></td>
      <?php
   }
   if ($permissions & $SUPER) {
      ?>
      <td align='center'><a href="groups.php?<?=SID?>">groups</a></td>
      <td align='center'><a href="setup.php?<?=SID?>">system</a></td>
      <?php
   }
   if ($permissions) {
      ?>
      <td align='right'><a href="logout.php?<?=SID?>">logout</a></td>
      <?php
   }
   else
      echo "<td align='right'><a href='login.php'>login</a></td>";
   echo "</tr>\n</table>\n&nbsp;<br>";
   echo "<!--************************END OF NAVBAR**********************-->\n";
}


////
// !Prints initial part of webpage
function printheader($title) {
   global $version;

   header("Cache-Control: private, no-cache, musti-revalidate");
   header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
   header("Pragma: no-cache");
?>
<!DOCTYPE HTML PUBLIC "-//W3C/DTD HTML 4.01 TRANSITIONAL//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
<TITLE><?php echo "$title" ?></TITLE>
<LINK rel="STYLESHEET" type="text/css" href="phplabware.css">
</HEAD>

<BODY BGCOLOR="#ffffff">
<a name="top"></a>
<table border=0 width=100%>
   <tr class='header' bgcolor="333388" align=right>
      <td align=right>
         <font size=+2 color="#ffffff"><i>PhpLabWare  
             <?php if ($version) echo "version $version"; ?></i></font>
      </td>
   </tr>
</table>
<!--************************END OF PRINTHEADER**************************-->

<?php

}

////
// !Prints footer
function printfooter($db=false,$USER=false) {
?>

<!--********************START OF PRINTFOOTER****************************-->
&nbsp;<br>
<hr>
</BODY>
</HTML>

<?php
   if ($db && $USER["settings"])
      $db->Execute("UPDATE users SET settings='".serialize($USER["settings"])."'
       WHERE id=".$USER["id"]);
}
?>
