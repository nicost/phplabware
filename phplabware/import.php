<?php

// import.php - imports delimited data into phplabware tables 
// impoert.php - author: Nico Stuurman <nicost@soureforge.net>

  /***************************************************************************
  * This script imports data into phplabware tables                          *
  *                                                                          *
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/


require("include.php");
require("includes/db_inc.php");
require("includes/general_inc.php");
require("includes/tablemanage_inc.php");
include ('includes/defines_inc.php');

$post_vars="delimiter,delimiter_type,tableid,nrfields,pkey,tmpfile";
globalize_vars($post_vars, $HTTP_POST_VARS);

$permissions=$USER["permissions"];
printheader($httptitle,false,$jsfile);

if (!($permissions & $SUPER)) {
	navbar($USER["permissions"]);
	echo "<h3 align='center'><b>Sorry, this page is not for you</B></h3>";
	printfooter($db,$USER);
	exit;
}

function get_delimiter ($delimiter_type) {
   if ($delimiter_type=="space")
      $delimiter=" ";
   elseif ($delimiter_type=="tab")
      $delimiter="\t";
   elseif ($delimiter_type=="comma")
      $delimiter=",";
   elseif ($delimiter_type=="semi-colon")
      $delimiter=";";
    return $delimiter;
} 
 

// do the final parsing
if ($HTTP_POST_VARS["assign"]=="Import Data") {
   // get description table name
   $desc=get_cell($db,"tableoftables","table_desc_name","id",$tableid);
   $table=get_cell($db,"tableoftables","real_tablename","id",$tableid);
   // find out to which columns each parsed file text will be assigned to
   for ($i=0;$i<$nrfields;$i++) {
      $the_field=$HTTP_POST_VARS["fields_$i"];
      if ($the_field)
         $to_field[$i]=get_cell($db,$desc,"columnname","id",$the_field);
   } 
   if (!$delimiter)
      $delimiter=get_delimiter($delimiter_type);
   $tmpdir=$system_settings["tmpdir"];
   $fh=fopen("$tmpdir/$tmpfile","r");
   print_r($to_field);
   if ($fh) {
      while ($line=chop(fgets($fh,1000000))) {
         $fields=explode($delimiter,$line);
         if ($pkey) {
            // check if we already had such a record
$db->debug=true;
            $r=$db->Execute("SELECT id FROM $table WHERE $to_field[$pkey]='$fields[$pkey]'");
            $recordid=$r->fields[0];
            if ($recordid && $pkeypolicy=="overwrite") {
               $query="UPDATE $table SET ";
               for ($i=0; $i<$nrfields;$i++) {
                  if ($to_field[$i]) {
                     $worthit=true;
                     $query.="$to_field[$i]=$fields[$i],";
                  }
               }
               if ($worthit) {
                  // strip last comma
                  $query=substr($query,0,-1);
                  $query.=" WHERE $to_field[$pkey]='$fields[$pkey]'";
                  $db->Execute($query);
               }
            }
            elseif (!$recorid) {
               $query="INSERT INTO $table ";
            }
         }   
      }
         
print_r ($fields);
   }
   fclose($fh);
   printfooter();
   exit();
}




// interpret uploaded file and get information for final parsing
if ($HTTP_POST_VARS["dataupload"]=="Continue") {
   $filename=$HTTP_POST_FILES["datafile"]["name"];
   if (!$delimiter)
      $delimiter=get_delimiter($delimiter_type);
   if ($delimiter && $tableid && $filename) {
      navbar($USER["permissions"]);
      if (!$system_settings["filedir"]) {
         echo "<h3><i>Filedir</i> was not set.  Please correct this first in <i>setup</i></h3>\n";
         printfooter();
         exit();
      }
      $tmpdir=$system_settings["tmpdir"];
      if (move_uploaded_file($HTTP_POST_FILES["datafile"]["tmp_name"],"$tmpdir/$filename")) {
         $fh=fopen("$tmpdir/$filename","r");
         if ($fh) {
            $firstline=chop(fgets($fh,1000000));
            fclose($fh);
            $fields=explode($delimiter,$firstline);
            $nrfields=sizeof($fields);
         }
         // echo "$nrfields fields found.<br>";
         $tablename=get_cell($db,"tableoftables","tablename","id",$tableid);
         echo "<h3 align='center'>Import Data(2): Assign fields to Columns of table <i>$tablename</i></h3>\n";
         echo "<form method='post' id='procesdata' enctype='multipart/form-data' ";
         $dbstring=$PHP_SELF;
         echo "action='$dbstring?".SID."'>\n"; 
         echo "<input type='hidden' name='tmpfile' value='$filename'>\n";
         echo "<input type='hidden' name='tableid' value='$tableid'>\n";
         echo "<input type='hidden' name='nrfields' value='$nrfields'>\n";
         echo "<input type='hidden' name='delimiter_type' value='$delimiter_type'>\n";
         echo "<input type='hidden' name='delimiter' value='$delimiter'>\n";
         echo "<table align='center'><tr>\n";
         echo "<th>Input File:</th>\n";
         for($i=0;$i<$nrfields;$i++)
            echo "   <td align='center'>$fields[$i]</td>\n";
         echo "</tr>\n<tr>\n   <th>Assign to Column:</th>\n";
         $desc=get_cell($db,"tableoftables","table_desc_name","id",$tableid);
         $r=$db->Execute("SELECT label,id FROM $desc");
         for($i=0;$i<$nrfields;$i++) {
            $menu=$r->GetMenu2("fields_$i");
            echo "   <td align='center'>$menu</td>\n";
            $r->Move(0);
         }
         echo "</tr>\n<tr>\n   <th>Primary Key:</th>\n";
         for($i=0;$i<$nrfields;$i++)
            echo "   <td align='center'><input type='radio' name='pkey' value='$i'></td>\n";
         echo "   <td align='center'><input type='radio' name='pkey' value='' checked> (none)</td>\n";
         echo "</tr>\n";
         $colspan=$nrfields+2;
         echo "</table>\n";

         echo "<table align='center'><tr>\n";
         echo "<tr><th>When a record with identical Primary key is already present, overwrite existing data or skip the record?</th></tr>\n";
         echo "<tr><td align='center'><input type='radio' name='pkeypolicy' value='overwrite'> overwrite</input>\n";
         echo "<input type='radio' name='pkeypolicy' value='skip' checked> skip</input></td></td></tr>\n";
         echo "<tr><td align='center'><input type='submit' name='assign' value='Import Data'></td></tr>\n";
         echo "</table>\n</form>";

      }
      else {
         echo "<h3>Problems with file upload, please try again.</h3>\n";
      }
      printfooter();
      exit();
   }
   else
      $string="Please enter all fields";  
}

// Page with file to be uploaded, delimiter and table
navbar($USER["permissions"]);
echo "<h3 align='center'>$string</h3>";
echo "<h3 align='center'>Import Data(1): Select File, delimiter, and Table to import data into</h3>\n";
echo "<form method='post' id='importdata' enctype='multipart/form-data' ";
$dbstring=$PHP_SELF;echo "action='$dbstring?".SID."'>\n"; 
echo "<table align='center' border='0' cellpadding='2' cellspacing='0'>\n";

echo "<tr>\n";
echo "<th>File with data</th>\n";
echo "<th>Delimiter</th>\n";
echo "<th>Table</th>\n";
echo "</tr>\n";

echo "<tr><td><input type='file' name='datafile' value='' ></td>\n";
echo "<td align='center'> <table><tr>
   <td><input type='radio' name='delimiter_type' value='comma' checked> comma</td>
   <td><input type='radio' name='delimiter_type' value='tab'> tab</td>
   <td><input type='radio' name='delimiter_type' value='space'> space</td></tr>
   <tr><td><input type='radio' name='delimiter_type' value='semi-colon'> ;</td>
   <td colspan=2><input type='radio' name='delimiter_type' value='other'> other:
   <input type='text' name='delimiter' value='$delimiter' size='2'></td></tr>
   </table></td>\n";
$query = "SELECT label,id FROM tableoftables where display='Y' or display='N' ORDER BY sortkey";
$r=$db->Execute($query);
if ($r)
   $menu=$r->GetMenu2("tableid",$tableid);
echo "<td>$menu</td>\n";
echo "</tr>\n";

echo "<tr><td colspan='3' align='center'><input type='submit' name='dataupload' value='Continue'></td></tr>\n";

echo "</table>\n";
echo "</form>\n";   
printfooter($db,$USER);
?>
