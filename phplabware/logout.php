<?php

// logout.php - kills the current session
// logout.php - author: Nico Stuurman

session_start();
include ('includes/functions_inc.php');
printheader("Loging out");
navbar(false);
if ($HTTP_SESSION_VARS["PHP_AUTH_USER"]) {
   session_destroy();
   echo "&nbsp;<br><h3 align='center'>Thanks for logging out!</hr><br>&nbsp;";
}
else
   echo "&nbsp;<br><h3 align='center'>You better login first!</hr><br>&nbsp;";
printfooter();
?>
