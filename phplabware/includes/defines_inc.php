<?php

// defines_inc.php - constants used in phplabware
// defines_inc.php - author: Nico Stuurman

// defines permissions
$ACTIVE=1;
$READ=2;
$WRITE=4;
$EDIT=8;
$LAYOUT=16;
$ADMIN=32;
$SUPER=64;

// the following will be needed in most scripts
$PHP_SELF=$HTTP_SERVER_VARS["PHP_SELF"];

// minimum password length
$PWD_MINIMUM=4;
?>
