<?php

// 0_0030_inc.php - Adds table trust to refine access rights
// 0_0030_inc.php - author: Nico Stuurman

  /***************************************************************************
  * Copyright (c) 2002 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  * This code is part of phplabware (http://phplabware.sf.net)               *
  *                                                                          *
  *                                                                          *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/ 

if ($db_type=="mysql")
   $db->Execute("CREATE TABLE trust (
              tableid int,
              recordid int,
              trusteduserid int,
              rw char,
              UNIQUE (tableid,recordid,trusteduserid,rw))");
else
   $db->Execute("CREATE TABLE trust (
              tableid int,
              recordid int,
              trusteduserid int,
              rw char,
              CONSTRAINT special PRIMARY KEY (tableid,recordid,trusteduserid,rw))");


?>
