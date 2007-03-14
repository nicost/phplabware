<?php

  // auth_inc.php - Takes care of authorization.  
  // auth_inc.php - author: Nico Stuurman <nicost@sourceforge.net> 
  /***************************************************************************
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  Part of phplabware, a web-driven groupware suite for research labs      *
  *                                                                          *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

/**
 * Returns an array with all essential info about the given user
 *
 * $user_tag can be a userid (when numeric) or the user login (when text)
 * Be sure not to allow numeric logins!
 */
function getUserInfo ($db, $username,$userid) 
{
   include ('./includes/defines_inc.php');

   if ($userid) {
      $db_query = "SELECT * FROM users WHERE id=$userid";
   } elseif ($username) {
      $db_query = "SELECT * FROM users WHERE login='$username'";
   }
   $db_result = $db->Execute($db_query);
   if (! ($db_result) ) {
      echo "Fatal database error.<br>";
      return false;
   }
   // save frequently used variables
   $USER=$db_result->fields;
   $USER['settings']=unserialize($USER['settings']);
   if ($USER['permissions2'] & $IP_SETTINGS) {
      // get IP from user here...
      $ip=$_SERVER['REMOTE_ADDR'];
      $ip=explode('.',$ip);
      $db_query = "SELECT settings from usersettings WHERE userid={$db_result->fields['id']} AND ip0={$ip[0]} AND ip1={$ip[1]} AND ip2={$ip[2]} AND ip3={$ip[3]}";
      $db_result=$db->Execute($db_query);
      if ($db_result->fields[0])
         $USER['settings']=unserialize($db_result->fields[0]);
      else // default new users to menus!
         $USER['settings']['menustyle']=1;
   }
   $USER['group_list']=$USER['groupid'];
   $USER['group_array'][]=$USER['groupid'];
   $rg=$db->Execute("SELECT groupsid FROM usersxgroups WHERE usersid='".$USER["id"]."'");
   while ($rg && !$rg->EOF) {
      $USER['group_list'].=','.$rg->fields['groupsid'];
      $USER['group_array'][]=$rg->fields['groupsid'];
      $rg->MoveNext();
   }
   return $USER;
}



$client = new cl_client;

// protect from outside variables, this is not needed on a well-configure server
$auth=false;

// the following can probably be cleaned up, we now always use sessions
$use_sessions=true;

if ($use_sessions) {
   if (isset($system_settings['tmpdir']))
      session_save_path($system_settings['tmpdir']);
   if (function_exists ('session_cache_limiter'))
      session_cache_limiter('private');
   session_start();
   //print_r($_SESSION);

   // If we have PHP_AUTH_USER in the session, this user is authenticated 
   $PHP_AUTH_USER = $_SESSION['PHP_AUTH_USER'];

   if (!isset($PHP_AUTH_USER)) {
      // logins can happen through a post or get mechanism (the latter restricted for security reason, post logins run through a secure server when available)  
      if ($_POST['logon']=='true') {
         $PHP_AUTH_USER=$_POST['user'];
         $PHP_AUTH_PW=$_POST['pwd'];
      } elseif (isset($system_settings['direct_login'])) {
         $PHP_AUTH_USER=$_GET['user'];
         if ($PHP_AUTH_USER) {
             // we'll only continue if this user is allowed to do URL based logins 
             $permissions2=get_cell($db,'users','permissions2','login',$PHP_AUTH_USER); 
             if (! ($permissions2 & $URL_LOGIN) ) {
		//echo "$permissions2 $URL_LOGIN";
                // delay to discourage brute force cracks
                usleep(500000);
                $PHP_AUTH_USER = false;
                loginscreen("<h4>Your credentials were not accepted, Please try again</h4>");
                exit();
            }
            $PHP_AUTH_PW=$_GET['pwd'];
         }
      }

      if ($PHP_AUTH_USER && $PHP_AUTH_PW) {
         // check submitted login and passwd in SQL database
         $pwd=md5($PHP_AUTH_PW);
         $db_query = "SELECT login FROM users WHERE login='$PHP_AUTH_USER' AND pwd='$pwd'";
         $db_result = $db->Execute($db_query);
         if ($db_result)
            $auth=$db_result->fields['login'];
         // check that there is no one else like this
         $db_result->Movenext();
         if (!$db_result->EOF)
            $auth=false; 

         // if pam_prg is present, check whether the user is known on the system 
         $pam_prg=$system_settings['checkpwd'];
         if ($system_settings['authmethod']==2 && $pam_prg && ! $auth) {
            // this only makes sense if the user has an account on this instance of phplabware
            if (get_cell($db,'users','login','login',$PHP_AUTH_USER)) {
               $esc_user = escapeshellarg($PHP_AUTH_USER);
               $esc_pass = escapeshellarg($PHP_AUTH_PW);
               $test = exec ("echo $esc_pass | $pam_prg $esc_user", $dummy,$result);
               if ($result) {  // we are authenticated
                  $auth = true; 
                  $authmethod='pam';
               }
            }
         }       
 
         // if authenticated, this session is OK:
         if ($auth) {
            session_register('javascript_enabled');
            if ($_SESSION['javascript_enabled'] || ($_POST['javascript_enabled'] || $_GET['javascript_enabled']))
               $_SESSION['javascript_enabled']=true;
            else
               $_SESSION['javascript_enabled']=false;
            if (!$authmethod)
               $authmethod='sql';
            session_register ('authmethod');
            $_SESSION['authmethod']=$authmethod;
            session_register ('PHP_AUTH_USER');
            $_SESSION['PHP_AUTH_USER']=$PHP_AUTH_USER;
            // when the login was secure but user does not wanna stay secure
            if (getenv('HTTPS') && !$_POST['ssl']) {
               // send meta tag redirecting to http page and exit
               $PHP_SELF=$_SERVER['PHP_SELF'];
               $server= getenv ('HTTP_HOST');
               $url="http://$server$PHP_SELF";
               $get_string=getenv('QUERY_STRING');
               $url=url_get_string($url);
               echo "<html>\n<head>\n";
               echo "<meta http-equiv='refresh' content=0;URL='$url'>";
               echo "</head>\n</html>";
               exit();
            } 
         }
         else {
            $PHP_AUTH_USER = false;
            // delay to discourage brute force cracks
            usleep(500000);
            loginscreen("<h4>Your credentials were not accepted, Please try again</h4>");
            exit();
         }
      } else { // no username and/or passwd found in get or post
         loginscreen("<h4>Please enter your username and password</h4>");
         exit();
      }
   }

   // need to call this to maintain javascript state
   $javascript_enabled=$_SESSION['javascript_enabled'];
   if (!$PHP_AUTH_USER) {
      // display logon screen
      loginscreen();
      exit();
   } 
   else {
      // we must have been authenticated directly or through the session
      // get all the info about this user we might need:
      if (!is_int($PHP_AUTH_USER)) {
         $USER=getUserInfo($db,$PHP_AUTH_USER,false);
         if (!isset($USER)) {
            exit;
         }
      } else {
         echo "Numeric logins are not allowed.<br>";
      }  
      // check whether account allows logins
      $active = $USER['permissions'] & $ACTIVE;
      if ($active) {
         $BROWSER = $client->browser;
         $NAME = $USER['firstname'] . ' ' . $USER['lastname'];
      }
      else {
         loginscreen();
         exit();
      }
   }
}

?>
