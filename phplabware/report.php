<?php
  
// report.php - Outputs one or more reocrds based on specified format 
// report.php - author: Nico Stuurman <nicost@soureforge.net>
  /***************************************************************************
  * Copyright (c) 2003 by Nico Stuurman<nicost@sf.net>                       *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

// Needs getvars:tablename,reportid,recordid
// optional getvar: tableview

/// main include thingies
require('include.php');
require('includes/db_inc.php');
require('includes/general_inc.php');
require('includes/report_inc.php');

$tableinfo=new tableinfo($db);

if (!$tableinfo->id) {
   printheader($httptitle);
   navbar($USER['permissions']);
   echo "<h3 align='center'> Table: <i>$HTTP_GET_VARS[tablename]</i> does not exist.</h3>";
   printfooter();
   exit();
}

$reportid=(int)$HTTP_GET_VARS['reportid'];
$recordid=(int)$HTTP_GET_VARS['recordid'];
$tableview=$HTTP_GET_VARS['tableview'];
 
if (!($reportid && ($recordid || $tableview)) ) {
   printheader($httptitle);
   navbar($USER['permissions']);
   echo "<h3 align='center'>Not enough information to generate the report.</h3>";
   printfooter();
   exit();
}

if (!may_read($db,$tableinfo,$recordid,$USER)) {
   printheader($httptitle);
   navbar($USER['permissions']);
   echo "<h3 align='center'>This information is not intended to be seen by you.</h3>";
   printfooter();
   exit();
}
   
if ($reportid>0) {
   $tp=@fopen($system_settings['templatedir']."/$reportid.tpl","r");
   if ($tp) {
      while (!feof($tp)) {
         $line=fgets($tp);
         if (stristr($line,"<!--fields-->")) {
            $header=$template;
            unset($template);
         }
         elseif (stristr($line,"<!--/fields-->")) {
            $footerset=true;
         }
         elseif ($footerset)
            $footer.=$line;
         else
            $template.=$line; 
      }
      fclose($tp);
   }
 
   if (!$template) {
      printheader($httptitle);
      navbar($USER['permissions']);
      echo "<h3 align='center'>Template file was not found!</h3>";
      printfooter();
      exit();
   }
}

// displays multiple records in a report (last search statement)
if ($HTTP_GET_VARS['tableview']) {
   // figure out the current query:
   $queryname=$tableinfo->short.'_query';
   if (session_is_registered ($queryname) && isset($HTTP_SESSION_VARS[$queryname])) {
      // get a list with all records we may see, create temp table tempb
      $listb=may_read_SQL($db,$tableinfo,$USER,'tempb');

      // read all fields in from the description file
      $fields_table=comma_array_SQL($db,$tableinfo->desname,columnname,"");
      $fields_table="id,".$fields_table;

      // prepare the search statement
      $query=make_search_SQL($db,$tableinfo,$fields_table,$USER,$search,$sortstring,$listb["sql"]);
      //$db->debug=true;
      $r=$db->Execute($query);
      //$db->debug=false;
      if ($reportid>0)
         echo $header;
      else //xml
         echo "<phplabware_base>\n";
      $counter=1;
      while ($r && !$r->EOF) {
         $Allfields=getvalues($db,$tableinfo,$fields_table,'id',$r->fields['id']);
         if ($reportid>0)
            echo make_report($db,$template,$Allfields,$tableinfo,$counter);
         else
            echo make_xml ($db,$Allfields,$tableinfo);
         //echo $report;
         $r->MoveNext();
         $counter++;
      }
     if ($reportid>0)
         echo $footer;
      else
         echo '</'."phplabware_base>\n>";
   }
}
else { // just a single record
   $fields=comma_array_SQL($db,$tableinfo->desname,'columnname');
   $Allfields=getvalues($db,$tableinfo,$fields,'id',$recordid);

   $report=make_report($db,$template,$Allfields,$tableinfo);
   echo $report;
}
?>
