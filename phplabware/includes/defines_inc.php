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
$ACTIVE=1;
$READ=2;
$WRITE=4;
$EDIT=8;
$LAYOUT=16;
$ADMIN=32;
$SUPER=64;

// the following is needed in most scripts
$PHP_SELF=$HTTP_SERVER_VARS["PHP_SELF"];

// minimum password length
$PWD_MINIMUM=4;

// comma-separated list (no spaces!) of tables containing user entries
// these tables should have the field 'userid'
$tables="";
?>
