<?php
// avoid cross-site scripting issues in one central location
$_SERVER['PHP_SELF'] = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES);
include ('./includes/defines_inc.php');
include ('./includes/functions_inc.php');
include ('./includes/init_inc.php');
include ('./includes/auth_inc.php');
?>
