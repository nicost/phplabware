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

$post_vars="delimiter,delimiter_type,tableid,nrfields,pkey,pkeypolicy,tmpfile,ownerid";
globalize_vars($post_vars, $HTTP_POST_VARS);

$permissions=$USER["permissions"];
printheader($httptitle,false,$jsfile);

if (!($permissions & $SUPER)) {
	navbar($USER["permissions"]);
	echo "<h3 align='center'><b>Sorry, this page is not for you</B></h3>";
	printfooter($db,$USER);
	exit;
}

navbar($USER["permissions"]);

////
// !returns variable delimiter, based on delimiter_type
function get_delimiter ($delimiter,$delimiter_type) {
   if ($delimiter)
      return $delimiter;
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
 

// do the final parsing (part 3)
if ($HTTP_POST_VARS["assign"]=="Import Data") {
   // get description table name
   $r=$db->Execute("SELECT table_desc_name,real_tablename,tablename,custom FROM tableoftables WHERE id='$tableid'");
   $desc=$r->fields[0];
   $table=$r->fields[1];
   $table_label=$r->fields[2];
   $table_custom=$r->fields[3];
   if ($table_custom)
      $table_link="$table_custom?".SID;
   else
      $table_link="general.php?tablename=$table_label"."&".SID;

   // find out to which columns each parsed file text will be assigned to
   for ($i=0;$i<$nrfields;$i++) {
      $the_field=$HTTP_POST_VARS["fields_$i"];
      if ($the_field) {
         $to_fields[$i]=get_cell($db,$desc,"columnname","id",$the_field);
         $to_types[$i]=get_cell($db,$desc,"type","id",$the_field);
         // type checking and cleanup for database upload
         // since we can have int(11) etc...
         if (substr($to_types[$i],0,3)=="int"){
            $to_types[$i]="int";
            $fields[$i]=(int)$fields[$i];
         }
         elseif($to_types[$i]=="float")
            $fields[$i]=(float)$fields[$i];
         else
            $fields[$i]=addslashes($fields[$i]);
      }
   } 
   // sanity check:
   $freq_array=array_count_values($to_fields);
   if (sizeof($freq_array) < sizeof($to_fields)) {
       $error_string="<h3 align='center'>Some columns in the database were selected more than once.  Please correct this and try again.</h3>";
       $HTTP_POST_VARS["dataupload"]="Continue";
   }
   // do the database upload
   else {
      $delimiter=get_delimiter($delimiter,$delimiter_type);
      $tmpdir=$system_settings["tmpdir"];
      $fh=fopen("$tmpdir/$tmpfile","r");
      if ($fh) {
         $access=$system_settings["access"];
         $lastmoddate=time();
         $lastmodby=$USER["id"];
         // fgets should not need second parameter, but my php version does...
         while ($line=chop(fgets($fh,1000000))) {
            $fields=explode($delimiter,$line);
            $worthit=false;
            if (isset($pkey)) {
               // check if we already had such a record
               $r=$db->Execute("SELECT id FROM $table WHERE $to_fields[$pkey]='$fields[$pkey]'");
               $recordid=$r->fields[0];
               if (isset($recordid) && $pkeypolicy=="overwrite") {
                  $query="UPDATE $table SET ";
                  for ($i=0; $i<$nrfields;$i++) {
                     if ($to_fields[$i]) {
                        $worthit=true;
                        $query.="$to_fields[$i]='$fields[$i]',";
                     }
                  }
                  if ($worthit) {
                     // strip last comma
                     // $query.=" WHERE $to_fields[$pkey]='$fields[$pkey]'";
                     // only the first record that matched will be modified
                     // when doing an update we leave access and owner untouched
                     $query.=" lastmoddate='$lastmoddate', lastmodby='$lastmodby' WHERE id='$recordid'";
                     if ($r=$db->Execute($query))
                         $modified++;
                  }
               }
            }

            if ( !(isset($pkey) || isset($recorid)) ) {
               $query_start="INSERT INTO $table (";
               $query_end=" VALUES (";
               for ($i=0;$i<$nrfields;$i++) {
                  if ($fields[$i] && $to_fields[$i]) {
                     $worthit=true;
                     $query_start.="$to_fields[$i],";  
                     $query_end.="'$fields[$i]',";
                  }
               }
               // delete last commas and combine into SQL INSERT query
               if ($worthit) {
                  $id=$db->GenID($table."_id_seq");
                  if ($id) {
                     $query="$query_start id,access,lastmoddate,lastmodby,ownerid) $query_end '$id','$access','$lastmoddate','$lastmodby','$ownerid')";
                     if ($r=$db->Execute($query))
                         $inserted++;
                  }
               } 
            }
         }
         
         // communicate results
         if (!isset($inserted))
            $inserted=0;
         if (!isset($modified))
            $modified=0;
         if ($inserted==1)
            $inserted_text="record was";
         else
            $inserted_text="records were";
         if ($modified==1)
            $modified_text="record was";
         else
            $modified_text="records were";
         echo "<h3 align='center'>$inserted $inserted_text inserted, and $modified $modified_text modified in Database <a href='$table_link'>$table_label</a></h3>";
         fclose($fh);
         unlink ("$tmpdir/$tmpfile");
         printfooter();
      }
      exit();
   }
}


// interpret uploaded file and get information for final parsing (part 2)
if ($HTTP_POST_VARS["dataupload"]=="Continue") {
   if ($error_string)
      echo $error_string;
   $filename=$HTTP_POST_FILES["datafile"]["name"];
   $delimiter=get_delimiter($delimiter,$delimiter_type);
   if ($delimiter && $tableid && $ownerid && ($filename || $tmpfile) ) {
      if (!$system_settings["filedir"]) {
         echo "<h3><i>Filedir</i> was not set.  Please correct this first in <i>setup</i></h3>\n";
         printfooter();
         exit();
      }
      $tmpdir=$system_settings["tmpdir"];
      if ($tmpfile || move_uploaded_file($HTTP_POST_FILES["datafile"]["tmp_name"],"$tmpdir/$filename")) {
         if ($tmpfile)
            $filename=$tmpfile;
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
         echo "<input type='hidden' name='ownerid' value='$ownerid'>\n";
         echo "<table align='center'><tr>\n";
         echo "<th>Input File:</th>\n";
         for($i=0;$i<$nrfields;$i++)
            echo "   <td align='center'>$fields[$i]</td>\n";
         echo "</tr>\n<tr>\n   <th>Assign to Column:</th>\n";
         $desc=get_cell($db,"tableoftables","table_desc_name","id",$tableid);
         $r=$db->Execute("SELECT label,id FROM $desc WHERE (display_record='Y' OR display_table='Y') AND datatype<>'file'");
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


// Page with file to be uploaded, delimiter, table, and owner (Page 1)
echo "<h3 align='center'>$string</h3>";
echo "<h3 align='center'>Import Data(1): Select File, delimiter, and Table to import data into</h3>\n";
echo "<form method='post' id='importdata' enctype='multipart/form-data' ";
$dbstring=$PHP_SELF;echo "action='$dbstring?".SID."'>\n"; 
echo "<table align='center' border='0' cellpadding='2' cellspacing='0'>\n";

echo "<tr>\n";
echo "<th>File with data</th>\n";
echo "<th>Delimiter</th>\n";
echo "<th>Table</th>\n";
echo "<th>Assign new records to:</th>\n";
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
$query = "SELECT label,id FROM tableoftables where id>1000 ORDER BY sortkey";
$r=$db->Execute($query);
if ($r)
   $menu=$r->GetMenu2("tableid",$tableid);
echo "<td>$menu</td>\n";
$query = "SELECT login,id FROM users";
$r=$db->Execute($query);
if ($r)
   $menu2=$r->GetMenu2("ownerid",$ownerid);
echo "<td>$menu2</td>\n";
echo "</tr>\n";

echo "<tr><td colspan='3' align='center'><input type='submit' name='dataupload' value='Continue'></td></tr>\n";

echo "</table>\n";
echo "</form>\n";   
printfooter($db,$USER);
?>
