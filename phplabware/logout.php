<?php

// logout.php - kills the current session
// logout.php - author: Nico Stuurman

include ('./includes/functions_inc.php');
include ('./includes/init_inc.php');
if (isset($system_settings['tmpdir']))
   session_save_path($system_settings['tmpdir']);
session_start();
if (isset($system_settings['homeURL'])) {
   $head="<META HTTP-EQUIV='Refresh' CONTENT='1;URL=".
          $system_settings['homeURL']."'>\n";
}
if ($HTTP_SESSION_VARS['PHP_AUTH_USER']) {
   printheader('Loging out',$head);
   navbar(false);
   session_destroy();
   echo "&nbsp;<br><h3 align='center'>Thanks for logging out!</h5><br>&nbsp;";
}
else {
   printheader('Loging out');
   navbar(false);
   echo "&nbsp;<br><h3 align='center'>You better login first!</hr><br>&nbsp;";
}
printfooter();
?>
