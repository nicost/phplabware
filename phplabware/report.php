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

/// main include thingies
require("include.php");
require("includes/db_inc.php");
require("includes/general_inc.php");
require("includes/report_inc.php");

$tableinfo=new tableinfo($db);

if (!$tableinfo->id) {
   printheader($httptitle);
   navbar($USER["permissions"]);
   echo "<h3 align='center'> Table: <i>$HTTP_GET_VARS[tablename]</i> does not exist.</h3>";
   printfooter();
   exit();
}

$reportid=(int)$HTTP_GET_VARS["reportid"];
$recordid=(int)$HTTP_GET_VARS["recordid"];

if (!($reportid && $recordid) ) {
   printheader($httptitle);
   navbar($USER["permissions"]);
   echo "<h3 align='center'>Not enough information to generate the report.</h3>";
   printfooter();
   exit();
}

if (!may_read($db,$tableinfo,$recordid,$USER)) {
   printheader($httptitle);
   navbar($USER["permissions"]);
   echo "<h3 align='center'>This information is not intended to be seen by you.</h3>";
   printfooter();
   exit();
}
   
$fields=comma_array_SQL($db,$tableinfo->desname,"columnname");
$Allfields=getvalues($db,$tableinfo,$fields,"id",$recordid);

$db->debug=true;
$tp=fopen($system_settings["templatedir"]."/$reportid.tpl","r");
while (!feof($tp))
   $template.=fgets($tp);
fclose($tp);
 
if (!$template) {
   printheader($httptitle);
   navbar($USER["permissions"]);
   echo "<h3 align='center'>Template file was not found!</h3>";
   printfooter();
   exit();
}

$report=make_report($template,$Allfields);
//echo $template;
echo $report;

?>
