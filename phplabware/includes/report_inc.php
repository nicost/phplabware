<?php

// report_inc.php - support functions dealing with reports
// report_inc.php - author: Nico Stuurman <nicost@sf.net>
  /***************************************************************************
  * Copyright (c) 2003 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  Part of phplabware, a web-driven groupware suite for research labs      *
  *  This file contains classes and functions needed in reports.php.         *
  *                                                                          *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

////
// !Takes a template and data and generates a report
function make_report ($template,$data) {
   foreach ($data as $column) {
      if ($column["name"])
         $template=str_replace("$".$column["name"],$column["text"],$template);
   }
   return $template;
}
