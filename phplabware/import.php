<?php

// import.php - imports delimited data into phplabware tables 
// import.php - author: Nico Stuurman <nicost@sourceforge.net>

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


require('include.php');
require('includes/db_inc.php');
require('includes/general_inc.php');
require('includes/tablemanage_inc.php');
include ('includes/defines_inc.php');

$post_vars='delimiter,delimiter_type,quote,quote_type,tableid,nrfields,pkey,pkeypolicy,skipfirstline,tmpfile,ownerid,localfile';
globalize_vars($post_vars, $HTTP_POST_VARS);

$permissions=$USER['permissions'];
$httptitle.=' Import data';
printheader($httptitle,false,$jsfile);
$tmpdir=$system_settings['tmpdir'];

if (!($permissions & $SUPER)) {
	navbar($USER['permissions']);
	echo "<h3 align='center'><b>Sorry, this page is not for you</B></h3>";
	printfooter($db,$USER);
	exit;
}

navbar($USER['permissions']);

////
// !returns variable delimiter, based on delimiter_type (a POST variable)
function get_delimiter ($delimiter,$delimiter_type) {
   if ($delimiter)
      return $delimiter;
   if ($delimiter_type=='space')
      $delimiter=" ";
   elseif ($delimiter_type=='tab')
      $delimiter="\t";
   elseif ($delimiter_type=='comma')
      $delimiter=',';
   elseif ($delimiter_type=='semi-colon')
      $delimiter=';';
   return $delimiter;
} 
 
////
// !returns the string 'quote', based on quote_type (a POST variable)
function get_quote ($quote,$quote_type) {
   if ($$quote)
      return $$quote;
   if ($quote_type=='doublequote')
      $quote="\"";
   elseif ($quote_type=='singlequote')
      $quote="'";
   elseif ($quote_type=='none')
      $quote=false;
   return $quote;
} 

////
// !corrects problems in input data (Removes quotes around text, 
// checks for ints and floats, quotes text
function check_input ($fields, $field_types, $nrfields)
{
   for ($i=0;$i<$nrfields;$i++) {
      if ($fields[$i]) {
         // strip quotes semi-inteligently  Can not use trim when php <4.1
         if ($fields[$i]{0}==$fields[$i]{strlen($fields[$i])-1} &&
             ($fields[$i]{0}=="\"" || $fields[$i]{0}=="'") )
            $fields[$i]=substr($fields[$i],1,-1);
         if ($field_types[$i]=='int'){
            $fields[$i]=(int)$fields[$i];
         }
         elseif($field_types[$i]=='id')
            $fields[$i]=(int)$fields[$i];
         elseif($field_types[$i]=='float')
            $fields[$i]=(float)$fields[$i];
         else
            $fields[$i]=addslashes($fields[$i]);
      }
   }
   return $fields;
}
          
////
// !in quoted lines, empty fields are (sometimes) not quoted
// we try to deal nicely with those here
function check_line(&$line,$quote,$delimiter) {
   if ($quote) {
      // delimiters without quotes can be a problem, so add quotes to them
      $newlength=1;
      $length=0;
      while ($newlength!=$length) {
         $length=strlen($line);
         $line=str_replace($quote.$delimiter.$delimiter,$quote.$delimiter.$quote.$quote.$delimiter,$line);
         $newlength=strlen($line);
      }
      $newlength=1;
      $length=0;
      while ($newlength!=$length) {
         $length=strlen($line);
         $line=str_replace($delimiter.$delimiter.$quote,$delimiter.$quote.$quote.$delimiter.$quote,$line);
         $newlength=strlen($line);
      }
      $line=substr($line,1,-1);
   }
}


// do the final parsing (part 3)
if ($HTTP_POST_VARS['assign']=='Import Data') {
   // set longer time-out
   ini_set('max_execution_time','0');
   // get description table name
   $r=$db->Execute("SELECT table_desc_name,real_tablename,tablename,custom FROM tableoftables WHERE id='$tableid'");
   $desc=$r->fields[0];
   $table=$r->fields[1];
   $table_label=$r->fields[2];
   $table_custom=$r->fields[3];

   // Columns with datatype 'sequence' are going to be automatically updated:
   // Here we query for them 
   $rseq=$db->Execute("SELECT columnname FROM $desc WHERE datatype='sequence'");

   if ($table_custom)
      $table_link="$table_custom?".SID;
   else
      $table_link="general.php?tablename=$table_label"."&".SID;

   // file the tmp table with file related data
   if ($localfile) {
       $dumpdir='phplabwaredump';
       $filename=$dumpdir.'/dumpcontent.txt';
       // read in table file and put into temp table
       $tablefile=$dumpdir.'/files.txt';
       $ft=fopen("$tmpdir/$tablefile",'r');
       $columnnames=explode("\t",$firstline);
       if ($ft) {
          $firstline=chop(fgets($ft,1000000));
          $columns=explode("\t",$firstline);
          // create the table for temp file data
          $db->Execute("CREATE TEMPORARY TABLE tmpfiles (
			id int UNIQUE NOT NULL,
			name TEXT,
			mime TEXT,
			size TEXT,
			type TEXT)");
          // and file with data from file
          $rs=$db->Execute("SELECT * FROM tmpfiles");
          while (!feof($ft)) {
             $line=chop(fgets($ft,1000000));
             $columnvalues=explode("\t",$line);
             foreach ($columns as $columnname) {
                list($t,$value)=each($columnvalues);
                $row[$columnname]=$value;
             }
             if ($line)
                $db->Execute($db->GetInsertSQL(&$rs,$row));
             print_r($column);
         }
      }
   }

   // find out to which columns each parsed file text will be assigned to
   // Array $to_fields contains the target column ids,
   // Array $to_types contains the target column types
   for ($i=0;$i<$nrfields;$i++) {
      $the_field=$HTTP_POST_VARS["fields_$i"];
      if (isset($the_field) && $the_field && $the_field!="") {
         $to_fields[$i]=get_cell($db,$desc,'columnname','id',$the_field);
         if ($to_fields[$i]=='id')
            $id_chosen=true;
         $to_types[$i]=get_cell($db,$desc,'type','id',$the_field);
         // type checking and cleanup for database upload
         // since we can have int(11) etc...
         if (substr($to_types[$i],0,3)=='int'){
            $to_types[$i]='int';
            $fields[$i]=(int)$fields[$i];
         }
         // and float8...
         elseif(substr($to_types[$i],0,5)=='float') {
            $to_types[$i]='float';
            $fields[$i]=(float)$fields[$i];
         }
         else
            $fields[$i]=addslashes($fields[$i]);
      }
   } 
   // sanity check:
   $freq_array=array_count_values($to_fields);
   if (sizeof($freq_array) < sizeof($to_fields)) {
       $error_string="<h3 align='center'>Some columns in the database were selected more than once.  Please correct this and try again.</h3>";
       $HTTP_POST_VARS['dataupload']='Continue';
   }
   // do the database upload
   else {
      $delimiter=get_delimiter($delimiter,$delimiter_type);
      $quote=get_quote($quote,$quote_type);
      $fh=fopen("$tmpdir/$tmpfile",'r');
      if ($fh) {
         $access=$system_settings['access'];
         $lastmoddate=time();
         $lastmodby=$USER['id'];
         if ($skipfirstline=='yes')
            $line=chop(fgets($fh,1000000));
         // fgets should not need second parameter, but my php version does...
         while ($line=chop(fgets($fh,1000000))) {
            check_line($line,$quote,$delimiter);
            
            $fields=check_input (explode($quote.$delimiter.$quote,$line),$to_types,$nrfields);
            $worthit=false;
            unset($recordid);
            // if there is a column being used as primary key, we do an SQL
            // UPDATE, otherwise an insert
            if (isset($pkey) && $pkey!="") {
               // check if we already had such a record
               $r=$db->Execute("SELECT id FROM $table WHERE $to_fields[$pkey]='$fields[$pkey]'");
               $recordid=$r->fields[0];
            }
            if (isset($recordid) && ($pkeypolicy=='overwrite' || $pkeypolicy=='onlyupdate')) {
               $query="UPDATE $table SET ";
               // import data from file into SQL statement
               for ($i=0; $i<$nrfields;$i++) {
                  if ($to_fields[$i]) {
                     $worthit=true;
                     $query.="$to_fields[$i]='$fields[$i]',";
                  }
               }
               while ($rseq && !$rseq->EOF) {
                  $rmax=$db->Execute("SELECT max(".$rseq->fields[0].") FROM $table");
                  $vmax=$rmax->fields[0]+1;
                  $query.=$rseq->fields[0]."='$vmax',";
                  $rseq->MoveNext();
               }
               $rseq->MoveFirst();
               if ($worthit) {
                  // strip last comma
                  // $query.=" WHERE $to_fields[$pkey]='$fields[$pkey]'";
                  // only the first record that matched will be modified
                  // when doing an update we leave access and owner untouched
                  $query.=" lastmoddate='$lastmoddate', lastmodby='$lastmodby' WHERE $to_fields[$pkey]='$fields[$pkey]'";
                  if ($r=$db->Execute($query))
                      $modified++;
               }
            }

            // if there is no primary key set, we simply INSERT a new record
            //if ( !(isset($pkey) || isset($recordid)) ) {
	    elseif ($pkeypolicy!='onlyupdate') {
               $query_start="INSERT INTO $table (";
               $query_end=" VALUES (";
               $newid=false;
               for ($i=0;$i<$nrfields;$i++) {
                  if ($fields[$i] && $to_fields[$i]) {
                     $worthit=true;
                     $query_start.="$to_fields[$i],";  
                     $query_end.="'$fields[$i]',";
                     if ($to_fields[$i]=="id")
                        $newid=$fields[$i];
                  }
               }
               while ($rseq && !$rseq->EOF) {
                  $rmax=$db->Execute("SELECT max(".$rseq->fields[0].") FROM $table");
                  $vmax=$rmax->fields[0]+1;
                  $query_start.=$rseq->fields[0].",";
                  $query_end.="'$vmax',";
                  $rseq->MoveNext();
               }
               $rseq->MoveFirst();
               // delete last commas and combine into SQL INSERT query
               if ($worthit) {
                  if (!$newid) {
                     $id=$db->GenID($table."_id_seq");
                     if ($id)
                        $query="$query_start id,access,date,lastmoddate,lastmodby,ownerid) $query_end '$id','$access','$lastmoddate','$lastmoddate','$lastmodby','$ownerid')";
                  }
                  else {
                     // let's make sure the 'next' id will be higher
                     if ($newid)
                        while ($id<$newid)
                           $id=$db->GenID($table.'_id_seq');
                     // check whether this id was used
                     if (get_cell($db,$table,'id','id',$newid))
                        $duplicateid++;
                     $query="$query_start access,date,lastmoddate,lastmodby,ownerid) $query_end '$access','$lastmoddate','$lastmoddate','$lastmodby','$ownerid')";
                  }
$db->debug=true;
                  if ($r=$db->Execute($query))
                      $inserted++;
$db->debug=false;
               } 
            }
         }
         
         // communicate results
         if (!isset($inserted))
            $inserted=0;
         if (!isset($modified))
            $modified=0;
         if (!isset($duplicateid))
            $duplicateid=0;
         if ($inserted==1)
            $inserted_text='record was';
         else
            $inserted_text='records were';
         if ($modified==1)
            $modified_text='record was';
         else
            $modified_text='records were';
         if ($duplicateid==1)
            $duplicate_text='record was';
         else
            $duplicate_text='records were';
         echo "<h3 align='center'>$inserted $inserted_text inserted, and $modified $modified_text modified in Database <a href='$table_link'>$table_label</a></h3>";
         if ($duplicateid)
            echo "<h3 align='center'>$duplicateid $duplicate_text rejected because their ids have already been used.</h3>\n";

         fclose($fh);
         unlink ("$tmpdir/$tmpfile");
         printfooter();
      }
      exit();
   }
}


// interpret uploaded file and get information for final parsing (part 2)
if ($HTTP_POST_VARS['dataupload']=='Continue') {
   if ($error_string)
      echo $error_string;
   $filename=$HTTP_POST_FILES['datafile']['name'];
   $delimiter=get_delimiter($delimiter,$delimiter_type);
   $quote=get_quote($quote,$quote_type);
   if ($delimiter && $tableid && $ownerid && ($filename || $tmpfile || $localfile) ) {
      if (!$system_settings['filedir']) {
         echo "<h3><i>Filedir</i> was not set.  Please correct this first in <i>setup</i></h3>\n";
         printfooter();
         exit();
      }
      $tmpdir=$system_settings['tmpdir'];
      if ($tmpfile || $localfile || move_uploaded_file($HTTP_POST_FILES['datafile']['tmp_name'],"$tmpdir/$filename")) {
         if ($tmpfile)
            $filename=$tmpfile;
         // lcoalfiles can also have associated files packaged with them
         if ($localfile) {
             $dumpdir='phplabwaredump';
             $filename=$dumpdir.'/dumpcontent.txt';
         }
         $fh=fopen("$tmpdir/$filename",'r');
         if ($fh) {
            $firstline=chop(fgets($fh,1000000));
            check_line($firstline,$quote,$delimiter);
            $fields=explode($quote.$delimiter.$quote,$firstline);
            $secondline=chop(fgets($fh,1000000));
            if ($quote) {
               check_line($secondline,$quote,$delimiter);
            }
            $fields2=explode($quote.$delimiter.$quote,$secondline);
            $nrfields=sizeof($fields);
            fclose($fh);
         }
         $tablename=get_cell($db,'tableoftables','tablename','id',$tableid);
         echo "<h3 align='center'>Import Data(2): Assign fields to Columns of table <i>$tablename</i></h3>\n";
         echo "<form method='post' id='procesdata' enctype='multipart/form-data' ";
         $dbstring=$PHP_SELF;
         echo "action='$dbstring?".SID."'>\n"; 
         echo "<input type='hidden' name='tmpfile' value='$filename'>\n";
 	 if ($localfile)
            echo "<input type='hidden' name='localfile' value='true'>\n";
         echo "<input type='hidden' name='tableid' value='$tableid'>\n";
         echo "<input type='hidden' name='nrfields' value='$nrfields'>\n";
         echo "<input type='hidden' name='delimiter_type' value='$delimiter_type'>\n";
         echo "<input type='hidden' name='delimiter' value='$delimiter'>\n";
         echo "<input type='hidden' name='quote_type' value='$quote_type'>\n";
         echo "<input type='hidden' name='ownerid' value='$ownerid'>\n";
         echo "<table align='center'>\n<tr>\n";
         echo "<th>First line:</th>\n";
         for($i=0;$i<$nrfields;$i++)
            echo "   <td align='center'>$fields[$i]</td>\n";
         echo "</tr>\n";
         echo "<th>Second line:</th>\n";
         for($i=0;$i<$nrfields;$i++)
            echo "   <td align='center'>$fields2[$i]</td>\n";
         echo "</tr>\n";
         echo "<tr>\n   <th>Assign to Column:</th>\n";
         $desc=get_cell($db,'tableoftables','table_desc_name','id',$tableid);
         $r=$db->Execute("SELECT label,id FROM $desc WHERE (display_record='Y' OR display_table='Y' OR columnname='id') AND datatype<>'sequence' ORDER BY sortkey");
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

         echo "<br><table align='center'>\n";

         echo "<tr><th>Update/Overwrite policy</th>\n";
         echo "<td colspan=3><input type='radio' name='pkeypolicy' value='overwrite'> Overwrite when primary key matches, otherwise add the new record</input><br>\n";
         echo "<input type='radio' name='pkeypolicy' value='onlyupdate'> Overwrite when primary key matches, otherwise ignore the new record</input><br>\n";
         echo "<input type='radio' name='pkeypolicy' value='skip' checked> Skip when primary key matches, otherwise add the new record</input></td></tr>\n";

         echo "<tr><th>Skip first line?</th>\n";
         echo "<td><input type='radio' name='skipfirstline' value='yes'> Yes</input></td>\n";
         echo "<td><input type='radio' name='skipfirstline' value='no' checked> no</input></td></tr>\n";

        // echo "<br><br>\n<table align='center>\n";
	 
         echo "<tr><td colspan=5 align='center'><input type='submit' name='assign' value='Import Data'></input></td></tr>\n";
         echo "</table>\n</form>\n<br>\n";

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


// Page with file to be uploaded, delimiter, table, and owner (Part 1)
echo "<h3 align='center'>$string</h3>";
echo "<h3 align='center'>Import Data(1): Select File, delimiter, and Table to import data into</h3>\n";
echo "<form method='post' id='importdata' enctype='multipart/form-data' ";
$dbstring=$PHP_SELF;echo "action='$dbstring?".SID."'>\n"; 
echo "<table align='center' border='0' cellpadding='5' cellspacing='0'>\n";

echo "<tr>\n";
echo "<th>File with data</th>\n";
echo "<th>Delimiter</th>\n";
echo "<th>Quotes around field</th>\n";
echo "<th>Table</th>\n";
echo "<th>Assign new records to:</th>\n";
echo "</tr>\n";

echo "<tr><td><input type='file' name='datafile' value='' ><br>\n";
$localfile=$system_settings['tmpdir'].'/phplabwaredump/dumpcontent.txt';
if (file_exists($localfile))
   echo "<b>Or:</b><input type='checkbox' name='localfile'> use $localfile";
echo "</td>\n";
echo "<td align='center'> <table><tr>
   <td><input type='radio' name='delimiter_type' value='comma' checked> comma</td>
   <td><input type='radio' name='delimiter_type' value='tab'> tab</td>
   <td><input type='radio' name='delimiter_type' value='space'> space</td></tr>
   <tr><td><input type='radio' name='delimiter_type' value='semi-colon'> ;</td>
   <td colspan=2><input type='radio' name='delimiter_type' value='other'> other:
   <input type='text' name='delimiter' value='$delimiter' size='2'></td></tr>
   </table></td>\n";
echo "<td align='center'> <table><tr>
   <td><input type='radio' name='quote_type' value='doublequote'> \"&nbsp;</td>
   <td><input type='radio' name='quote_type' value='singlequote'> '&nbsp;</td>
   <td><input type='radio' name='quote_type' value='none' checked> none</td></tr>
   <tr><td>&nbsp;</td></tr>
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

echo "<tr><td colspan='4' align='center'><input type='submit' name='dataupload' value='Continue'></td></tr>\n";

echo "</table>\n";
echo "</form>\n";   
printfooter($db,$USER);
?>
