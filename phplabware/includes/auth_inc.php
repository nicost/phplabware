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


//$post_vars="logon,user,pwd,ssl,submit";
//globalize_vars($post_vars,"HTTP_POST_VARS");
//echo "$user,$pwd,$ssl,$logon,$ssl,$post_vars.<br>";


$client = new cl_client;

// protect from outside variables
$auth=false;
$use_sessions=true;

if ($use_sessions) {
   if (isset($system_settings["tmpdir"]))
      session_save_path($system_settings["tmpdir"]);
   if (function_exists ("session_cache_limiter"))
      session_cache_limiter("private");
   session_start();

   // if this is a login, authenticate the user:
   if ($HTTP_POST_VARS["logon"]=="true") {
      $PHP_AUTH_USER=$HTTP_POST_VARS["user"];
      $PHP_AUTH_PW=$HTTP_POST_VARS["pwd"];
      if ($PHP_AUTH_USER && $PHP_AUTH_PW) {

         // check submitted login and passwd in SQL database
         $pwd=md5($PHP_AUTH_PW);
         $db_query = "SELECT login FROM users WHERE login='$PHP_AUTH_USER' AND pwd='$pwd'";
         $db_result = $db->Execute($db_query);
         if ($db_result)
            $auth=$db_result->fields["login"];
         // check that there is no one else like this
         $db_result->Movenext();
         if (!$db_result->EOF)
            $auth=false; 

         // if pam_prg is present, check whether the user is known on the system 
         $pam_prg=$system_settings["checkpwd"];
         if ($system_settings["authmethod"]==2 && $pam_prg && ! $auth) {
            // this only makes sense if the user has an account on sidb
            if (get_cell($db,"users","login","login",$PHP_AUTH_USER)) {
               $esc_user = escapeshellarg($PHP_AUTH_USER);
               $esc_pass = escapeshellarg($PHP_AUTH_PW);
               $test = exec ("echo $esc_pass | $pam_prg $esc_user", $dummy,$result);
               if ($result) {  // we are authenticated
                  $auth = true; 
                  $authmethod="pam";
               }
            }
         }       
 
         // if authenticated, this session is OK:
         if ($auth) {
            if (!$authmethod)
               $authmethod="sql";
            $HTTP_SESSION_VARS["authmethod"]=$authmethod;
            session_register ("authmethod");
            $HTTP_SESSION_VARS["PHP_AUTH_USER"]=$PHP_AUTH_USER;
            session_register ("PHP_AUTH_USER");
            // when the login was secure but user does not wanna stay secure
            if (getenv("HTTPS") && !$HTTP_POST_VARS["ssl"]) {
               // send meta tag redirecting to http page and exit
               $PHP_SELF=$HTTP_SERVER_VARS["PHP_SELF"];
               $server= getenv ("HTTP_HOST");
               $url="http://$server$PHP_SELF";
               $get_string=getenv("QUERY_STRING");
               $url=url_get_string($url);
               echo "<html>\n<head>\n";
               echo "<meta http-equiv='refresh' content=0;URL='$url'>";
               //echo "<meta http-equiv='refresh' content=0;URL='$url'>\n";
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
      else { 
         loginscreen("<h4>Please enter your username and password</h4>");
         exit();
      }
   } 

   // if the $PHP_AUTH_USER is not set, we need to identify and authenticate 
   if (!$PHP_AUTH_USER)
      $PHP_AUTH_USER = $HTTP_SESSION_VARS["PHP_AUTH_USER"];
   if (!$PHP_AUTH_USER) {
      // display logon screen
      loginscreen();
      exit();
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
      $USER["settings"]=unserialize($USER["settings"]);
      $USER["group_list"]=$USER["groupid"];
      $USER["group_array"][]=$USER["groupid"];
      $rg=$db->Execute("SELECT groupsid FROM usersxgroups WHERE usersid='".$USER["id"]."'");
      while ($rg && !$rg->EOF) {
         $USER["group_list"].=",".$rg->fields["groupsid"];
	 $USER["group_array"][]=$rg->fields["groupsid"];
	 $rg->MoveNext();
      }
      
      // check whether account allows logins
      $active = $USER["permissions"] & $ACTIVE;
      if ($active) {
         $BROWSER = $client->browser;
         $NAME = $USER["firstname"] . " " . $USER["lastname"];
      }
      else {
         loginscreen();
         exit();
      }
   }
}

?>
