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

/**
 * Need to restrict the following 3 function to outputting only the fields that rae seen in the current view
 */

  
/**
 *  Formats record(s) into xml
 *
 */
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


/**
 *  Formats record(s) into tab-delimited output
 * $output: 1=screen, 2=file
 *
 */
function make_sheet ($db,$data,$tableinfo,$output,$fieldscomma,$delimiter) {
   // by also chekcing for the last comma, we reduce the risk of falsely reporting things
   $fieldscomma.=',';
   // some characters will kill nice parsing of our output, so take these guys out:
   $badchars= array ("\n", "\t", "\m", "\r");
   if ($data[0]) {
      foreach ($data as $column) {
         if (false !== strpos($fieldscomma,$column['name'].',')) {
            $column['text']=str_replace($badchars,'',$column['text']);
            $out.="{$column['text']}$delimiter";
         }
      }
   } else { // no id so assume this is a header
      foreach ($data as $column) {
         if (false !== strpos($fieldscomma,$column['name'].',')) {
            $out.="{$column['label']}$delimiter";
         }
      }
   }
   if ($output==1) {
      $out.='<br>';
   }
   $out.="\n";
   return $out;
}


/**
 * Helper function to sort the array $data according to the length of the its field 'name'
 */
function compareData($a,$b) {
   if (strlen($a['name']) < strlen($b['name'])) {
      return true;
   } else {
      return false;
   }
}

/**
 *  Takes a template and data and generates a report
 *
 * $target: 1=screen(html), 2=file
 * $value are translated into the text value
 * %value are translated into the actual value
 * &value are translated into the sum of all values seen so far
 */
function make_report ($db,$template,$data,$tableinfo,$target=1,$counter=false) {
   global $sums;
   // to do the replacements 'greedy', we need to sort $data so that the longest names come first 
   // Use usort with the above helperfunction to direct the sort 

   usort ($data,'compareData');

   foreach ($data as $column) {
      if ($column['name']) {
         $sums[$column['name']]+=$column['values'];
         $template=str_replace("&".$column['name'],$sums[$column['name']],$template);
         // give a chance to replace with value only
         if ($column['datatype']=='table') {
            $template=str_replace("%".$column['name'],$column['nested']['values'],$template);
          } else {
             $template=str_replace("%".$column['name'],$column['values'],$template);
          }
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
         // we do not want to send bad links to a file
         elseif ($column['link'] && $target==1)
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
