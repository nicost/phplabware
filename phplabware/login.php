<?php

// login.php - Generic script for PhpLabWare
// login.php - author: Nico Stuurman

include ('include.php');
printheader("PhpLabWare");
navbar ($USER["permissions"]);
echo "<h3 align=center>Welcome to PhpLabWare</h3>\n";
printfooter();
?>
