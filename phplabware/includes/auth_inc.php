<?php

  // auth_inc.php - Takes care of authorization.  
  // auth_inc.php - author: Nico Stuurman  
  /***************************************************************************
  *                                                                          *
  * Author: Nico Stuurman                                                    *
  * email: nicos@itsa.ucsf.edu                                               *
  * Copyright (c) 2000, 2001 by Nico Stuurman                                *
  * ------------------------------------------------------------------------ *
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
   global $HTTP_SERVER_VARS, $settings;

   $PHP_SELF=$HTTP_SERVER_VARS["PHP_SELF"];
   if ($settings["secure_server"]) {
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
   if ($settings["secure_server"]) {
      echo "<input type='checkbox' name='ssl'>Keep a secure connection";
   }
   echo "</td></tr>\n";
   echo "<tr><td colspan=2 align='center'>";
   echo "<input type='submit' name='submit' value='Login'></td></tr>\n";
   echo "<tr><td colspan=2 align='center'>";
   echo "Note:  Cookies must be enabled beyond this point</td></tr>\n";
   echo "</table>\n";
   printfooter();
   exit();
}

/******************** start of global script ********************/

$client = new cl_client;

// protect from outside variables
$auth=false;
$use_sessions=true;

if ($use_sessions) {
   if (function_exists ("session_cache_limiter"))
      session_cache_limiter("private");
   session_start();

   // if this is a login, authenticate the user:
   if ($HTTP_POST_VARS["logon"]) {
      $PHP_AUTH_USER=$HTTP_POST_VARS["user"];
      $PHP_AUTH_PW=$HTTP_POST_VARS["pwd"];
      if ($PHP_AUTH_USER && $PHP_AUTH_PW) {

         // check submitted login and passwd in SQL database
         $pwd=md5($PHP_AUTH_PW);
         $db_query = "SELECT login FROM users WHERE login='$PHP_AUTH_USER' AND pwd='$pwd'";
         $db_result = $db->Execute($db_query);
         if ($db_result)
            $auth=$db_result->fields[0];
         // check that there is no one else like this
         $db_result->Movenext();
         if (!$db_result->EOF)
            $auth=false; 

         // if pam_prg is present, check whether the user is known on the system 
         if ($pam_prg && ! $auth) {
            // this only makes sense if the user has an account on sidb
            if (exist_user ($PHP_AUTH_USER)) {
               $esc_user = escapeshellarg($PHP_AUTH_USER);
               $esc_pass = escapeshellarg($PHP_AUTH_PW);
               $test = exec ("echo $esc_pass | $pam_prg $esc_user", $dummy,$result);
               if ($result) {  // we are authenticated
                  $auth = true; 
                  $S_PAMAUTH = true;
                  session_register("S_PAMAUTH");
               }
            }
         }       
 
         // if authenticated, this session is OK:
         if ($auth) {
            session_register ("PHP_AUTH_USER");
            // when the login was secure but user does not wanna stay secure
            if (getenv("HTTPS") && !$HTTP_POST_VARS["ssl"]) {
               // send meta tag redirecting to http page and exit
               $PHP_SELF=$HTTP_SERVER_VARS["PHP_SELF"];
               $server= getenv ("HTTP_HOST");
               $url="http://$server$PHP_SELF";
               echo "<html>\n<head>\n";
               echo "<meta http-equiv='refresh' content=0;URL='$url'>\n";
               echo "</head>\n</html>";
               exit();
            } 
         }
         else {
            $PHP_AUTH_USER = false;
            loginscreen("<h4>Your credentials were not accepted, Please try again</h4>");
            exit();
         }
      }
      else 
         loginscreen("<h4>Please enter your username and password</h4>");
   } 

   // if the $PHP_AUTH_USER is not set, we need to identify and authenticate 
   if (!$PHP_AUTH_USER)
      $PHP_AUTH_USER = $HTTP_SESSION_VARS["PHP_AUTH_USER"];
   if (!$PHP_AUTH_USER) {
      // display logon screen
      loginscreen();
   } 
   else {
      // we must have been authenticated directly or through the session
      $db_query = "SELECT * FROM users WHERE login='$PHP_AUTH_USER'";
      $db_result = $db->Execute($db_query);
      if (! ($db_result) ) {
         echo "Fatal database error.<br>";
         exit();
      }
      // save frequently used variables
      $USER=$db_result->fields;
      
      // check whether account allows logins
      $active = $USER["permissions"] & $ACTIVE;
      if ($active) {
         $BROWSER = $client->browser;
         $NAME = $USER["firstname"] . " " . $USER["lastname"];
		$db_result->fields["lastname"];
         $S_PAMAUTH = $HTTP_SESSION_VARS["S_PAMAUTH"];
         if ($impose_folder) {
            $dir = $infolder;
            $outdir = $outfolder;
         }
         else {
            $dir = $USER["indir"]; 
            $outdir = $USER["outdir"];
         }   
         if ($dir)
            $S_HOMEDIR = $people_dir . "/".$S_LOGIN."/".$dir;
         if ($outdir)
            $S_OUTDIR = $people_dir . "/" .$S_LOGIN."/".$outdir;
         if ($settings = $USER["settings"] ) 
            $S_SETTINGS = unserialize ($settings);
         if (!$S_SETTINGS["skin"])
            $S_SKIN = "classic";
         else
            $S_SKIN = $S_SETTINGS["skin"];
      }
      else
         echo "Inactive user.<br>";
   }
}

?>
