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
// !Formats record(s) into xml
function make_xml ($db,$data,$tableinfo) {
   $xml.="<{$tableinfo->label} record>\n";
   foreach ($data as $column) {
      $xml.="<{$column['name']}>\n";
      $xml.="{$column['text']}\n";
      $xml.="</{$column['name']}>\n";
   }
   $xml.="</{$tableinfo->label} record>\n";
   return $xml;
}

////
// !Takes a template and data and generates a report
function make_report ($db,$template,$data,$tableinfo,$counter=false) {
   foreach ($data as $column) {
      if ($column['name']) {
         // give a chance to replace with value only
         $template=str_replace("%".$column['name'],$column['values'],$template);
         if ($column['datatype']=='textlong') {
            $textlarge=nl2br(htmlentities($column['values']));
            $template=str_replace("$".$column['name'],$textlarge,$template);
         }
         // we'll need to squeeze in images and files too

         elseif ($column['datatype']=='file' || $column['datatype']=='image') {
            $files=get_files($db,$tableinfo->name,$column['recordid'],$column['columnid'],0,'big');
            unset ($ftext);
            for ($i=0;$i<sizeof($files);$i++) {
               $ftext.=$files[$i]['link'];
            }
            $template=str_replace("$".$column['name'],$ftext,$template);
         }
         elseif ($column['link'])
            $template=str_replace("$".$column['name'],$column['link'],$template);
         else   
            $template=str_replace("$".$column['name'],$column['text'],$template);
      }
   }
   // Replace $counter with current sequence number
   if ($counter)
      $template=str_replace('$counter',$counter,$template);
   return $template;
}
