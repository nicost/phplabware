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

ini_set('max_execution_time','480');

/// main include thingies
require('./include.php');
require('./includes/db_inc.php');
require('./includes/general_inc.php');
require('./includes/report_inc.php');

$tableinfo=new tableinfo($db);

if (!$tableinfo->id) {
   printheader($httptitle);
   navbar($USER['permissions']);
   echo "<h3 align='center'> Table: <i>$_GET[tablename]</i> does not exist.</h3>";
   printfooter();
   exit();
}

$reportid=(int)$_GET['reportid'];
$recordid=(int)$_GET['recordid'];
$tableview=$_GET['tableview'];
// determine which fields are going to be seen:
$viewid=$_GET['viewid'];
if ($viewid) {
   $Fieldscomma=viewlist($db,$tableinfo,$viewid);
} else {
   $Fieldscomma=comma_array_SQL($db,$tableinfo->desname,columnname,"WHERE display_table='Y'");
}

 
if (!($reportid && ($recordid || $tableview)) ) {
   printheader($httptitle);
   navbar($USER['permissions']);
   echo "<h3 align='center'>Not enough information to generate the report.</h3>";
   printfooter();
   exit();
}

if (!may_see_table($db,$USER,$tableinfo->id)) {
   printheader($httptitle);
   navbar($USER['permissions']);
   echo "<h3 align='center'>This information is not intended to be seen by you.</h3>";
   printfooter();
   exit();
}
   
if ($reportid>0) {
   $reportname=get_cell($db,'reports','label','id',$reportid);
   $tp=@fopen($system_settings['templatedir']."/$reportid.tpl",'r');
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

// if we want to output to a file, send the appropriate headers now
if ($USER['settings']['reportoutput']==2) {
   header('Accept-Ranges: bytes');
   header('Connection: close');
   header("Content-Type: text/html");
   // we don't yet know how long this is going to be
   //header("Content-Length: $filesize");
   header("Content-Disposition-type: attachment");
   header("Content-Disposition: attachment; filename=$reportname");
}

// displays multiple records in a report (last search statement)
if ($_GET['tableview']) {
   // figure out the current query:
   $queryname=$tableinfo->short.'_query';
   if (session_is_registered ($queryname) && isset($_SESSION[$queryname])) {
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
      elseif ($reportid==-1) { // xml
         echo "<phplabware_base>\n";
      } elseif ($reportid==-2) { // tab headers
         $Allfields=getvalues($db,$tableinfo,$fields_table);
	 print_r($Fieldscomma);
         echo make_tab ($db,$Allfields,$tableinfo,$USER['settings']['reportoutput'],$Fieldscomma);
      } elseif ($reportid==-3) { // comma headers
         $Allfields=getvalues($db,$tableinfo,$fields_table);
         echo make_comma ($db,$Allfields,$tableinfo,$USER['settings']['reportoutput'],$Fieldscomma);
      }
      $counter=1;
      while ($r && !$r->EOF) {
         $Allfields=getvalues($db,$tableinfo,$fields_table,'id',$r->fields['id']);
         if ($reportid>0) {
            echo make_report($db,$template,$Allfields,$tableinfo,$USER['settings']['reportoutput'],$counter);
         } elseif ($reportid==-1) {
            echo make_xml ($db,$Allfields,$tableinfo);
         } elseif ($reportid==-2) {
            echo make_tab ($db,$Allfields,$tableinfo,$USER['settings']['reportoutput'],$Fieldscomma);
         } elseif ($reportid==-3) {
            echo make_comma ($db,$Allfields,$tableinfo,$USER['settings']['reportoutput'],$Fieldscomma);
         }
         $r->MoveNext();
         $counter++;
      }
     if ($reportid>0) {
        foreach ($Allfields as $column) {
           if ($column['name']) {
              //$sums is defined as global in function make_reports.  It sums whatever it finds while looping through the records it displays. All occurences of '&fieldname' in the footer will be replaced with the sum of the rows.
              $footer=str_replace("&".$column['name'],$sums[$column['name']],$footer);
           }
         }
         echo $footer;
      } elseif ($reportid==-1) {
         echo '</'."phplabware_base>\n>";
      }
   }
}
else { // just a single record
   $fields=comma_array_SQL($db,$tableinfo->desname,'columnname');
   $Allfields=getvalues($db,$tableinfo,$fields,'id',$recordid);

   $report=make_report($db,$template,$Allfields,$tableinfo);
   echo $report;
}
?>
