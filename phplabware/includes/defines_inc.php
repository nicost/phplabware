<?php

// defines_inc.php - constants used in phplabware
// defines_inc.php - author: Nico Stuurman
  /***************************************************************************
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  Part of phplabware, a web-driven groupware suite for research labs      *
  *                                                                          *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/  

// defines permissions
// these values are compared with $USER['permissions'] (using the & and > operators)
$ACTIVE=1;
$READ=2;
$WRITE=4;
$EDIT=8;
$LAYOUT=16;
$ADMIN=32;
$SUPER=64;

// defines permission2
// These values are not related, i.e., they are not compared in $USER['permissions2'], but rather used as a simple method to store binary settings
$URL_LOGIN=1;
$IP_SETTINGS=2;

// the following is needed in most scripts
$PHP_SELF=$_SERVER['PHP_SELF'];

// minimum password length
$PWD_MINIMUM=4;

// maximum length of items displayed in drop-down menu
$max_menu_length=40;

?>
