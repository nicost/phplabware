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
//
function move ($fromfile, $tofile) {
   //  guess this won't work on Windows
   `mv '$fromfile' '$tofile'`;
    // but this should
    if (!@filesize($tofile)) 
      copy($fromfile,$tofile);
    return filesize($tofile);
}

// interpret a date string according to the system settings
// this can fail when importing data from another continent
function mymktime($datestring) {
   global $system_settings;

   if ($system_settings['dateformat']==1) {
      $month=strtok($datestring,'/');
      $day=strtok('/');
      $year=strtok('/');
   }
   elseif ($system_settings['dateformat']==2) {
      $month=strtok($datestring,' ');
      $day=strtok(' ');
      $year=strtok(' ');
   }
   elseif ($system_settings['dateformat']>=3) {
      $day=strtok($datestring,' ');
      $month=strtok(' ');
      $year=strtok(' ');
   }
   return mktime(0,0,0,$month,$day,$year);
}

////
// !Upload files and enters then into table files
// files should be called file[] in HTTP_POST_FILES
// filetitle in HTTP_POST_VARS will be inserted in the title field of table files
// returns id of last uploaded file upon succes, false otherwise
function import_file ($db,$tableid,$id,$columnid,$columnname,$tmpfileid,$system_settings)
{
   if (!$tmpfileid)
      return false;
   $table=get_cell($db,'tableoftables','tablename','id',$tableid);
   $real_tablename=get_cell($db,'tableoftables','real_tablename','id',$tableid);

   if (!($db && $table && $id)) {
      echo "Error in code: $db, $table, or $id is not defined.<br>";
      return false;
   }
   if (isset($tmpfileid) && !$filedir=$system_settings['filedir']) {
      echo "<h3><i>Filedir</i> was not set.  The file was not uploaded. Please contact your system administrator</h3>";
      return false;
   }
   $r=$db->Execute("SELECT name,mime,type FROM tmpfiles WHERE id=$tmpfileid");
   if (!$r->fields[0])
      return false;
   if (!$fileid=$db->GenID("files_id_seq"))
      return false;
   $originalname=$r->fields['name'];
   $mime=$r->fields['type'];
   $filestype=$r->fields['type'];
   $filesdir=$system_settings['tmpdir'].'/phplabwaredump/files/';
   $tmplocation=$filesdir.$tmpfileid.'_'.$originalname;
   $size=filesize($tmplocation);
   $title=$$originalname;
   if (!$title)
      $title='NULL'; 
   else
      $title="'$title'";
   $type=$filestype;
   if (move($tmplocation,"$filedir/$fileid".'_'."$originalname")) {
      $query="INSERT INTO files (id,filename,mime,size,title,tablesfk,ftableid,ftablecolumnid,type) VALUES ($fileid,'$originalname','$mime','$size',$title,'$tableid',$id,'$columnid','$filestype')";
      $db->Execute($query);
   }
   else
      $fileid=false;
   return $fileid;
}

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
function check_input ($tableinfo, &$fields, $to_fields, $field_types, $field_datatypes, $nrfields)
{
   global $db;

   for ($i=0;$i<$nrfields;$i++) {
      if ($fields[$i]) {
         // strip quotes semi-inteligently  Can not use trim when php <4.1
         if ($fields[$i]{0}==$fields[$i]{strlen($fields[$i])-1} &&
             ($fields[$i]{0}=="\"" || $fields[$i]{0}=="'") )
            $fields[$i]=substr($fields[$i],1,-1);
         if ($field_datatypes[$i]=='pulldown') {
            // see if we have this value already, otherwise make a new entry in the type table....
             // &nbsp; is used as a place holder in output of phplabware.  Unset the value if found
             if ($fields[$i]=="&nbsp;")
                unset ($fields[$i]);
             else {
                $Allfields=getvalues($db,$tableinfo,$to_fields[$i]);
                $rtemp=$db->Execute("SELECT id FROM {$Allfields[0]['ass_t']} WHERE typeshort='{$fields[$i]}'");
                if ($rtemp && $rtemp->fields[0]) {
                   $fields[$i]=$rtemp->fields[0];
                }
                else { // insert this new value in the type table:
                   $typeid=$db->GenId($Allfields[0]['ass_t'].'_id_seq');
                   unset($rtemp);
                   $rtemp=$db->Execute("INSERT INTO {$Allfields[0]['ass_t']} (id,type,typeshort,sortkey) VALUES ($typeid,'{$fields[$i]}','{$fields[$i]}','0')");
                   if ($rtemp && $rtemp->fields[0]) {
                      $fields[$i]=$rtemp->fields[0];
                   }
                }
             }
         }
         // for mpulldowns we have a problem since we do not have the new id yet

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
   //return $fields;
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
   $tableinfo=new tableinfo($db,false,$tableid);
   $desc=$tableinfo->desname;
   $table=$tableinfo->realname;
   $table_label=$tableinfo->name;

   // Columns with datatype 'sequence' are going to be automatically updated:
   // Here we query for them 
   $rseq=$db->Execute("SELECT columnname FROM $desc WHERE datatype='sequence'");

   if ($table_custom)
      $table_link="$table_custom?".SID;
   else
      $table_link="general.php?tablename=$table_label"."&".SID;

   // fill the tmp table with file related data
   if ($localfile) {
       $dumpdir='phplabwaredump';
       $filename=$dumpdir.'/dumpcontent.txt';
       // read in table file and put into temp table
       $tablefile=$dumpdir.'/files.txt';
       $ft=fopen("$tmpdir/$tablefile",'r');
       $columnnames=explode("\t",$firstline);
       if ($ft) {
          $firstline=chop(fgets($ft,1024));
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
             $line=chop(fgets($ft,1024));
             $columnvalues=explode("\t",$line);
             foreach ($columns as $columnname) {
                list($t,$value)=each($columnvalues);
                $row[$columnname]=$value;
             }
             if ($line)
                $db->Execute($db->GetInsertSQL(&$rs,$row));
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
         $to_datatypes[$i]=get_cell($db,$desc,'datatype','id',$the_field);
         // type checking and cleanup for database upload
         // since we can have int(11) etc...
         if ($to_datatypes[$i]=='file'){
            // this has type int, but its value changed, so set to_type to text
            $to_types[$i]='text';
         }
         elseif ($to_datatypes[$i]=='date'){
            // if date is an int, assume it is UNIX date, otherwise convert 
            if (!is_int($fields[$i]))
               $fields[$i]=mymktime($fields[$i]);
            // this has type int (wrong!), set to_type to text
            $to_types[$i]='text';
         }
         elseif (substr($to_types[$i],0,3)=='int'){
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
         // translate the $access string into our new format
         if ($access{3}=='r')
            $gr=1;
         if ($access{4}=='w')
            $gw=1;
         if ($access{6}=='r')
            $er=1;
         if ($access{7}=='w')
            $ew=1;
         $lastmoddate=time();
         $lastmodby=$USER['id'];
         if ($skipfirstline=='yes')
            $line=chop(fgets($fh,1000000));
         // fgets should not need second parameter, but my php version does...
         while ($line=chop(fgets($fh,1000000))) {
            check_line($line,$quote,$delimiter);
            
            //$fields=check_input (explode($quote.$delimiter.$quote,$line),$to_types,$nrfields);
            $fields=explode($quote.$delimiter.$quote,$line);
            check_input ($tableinfo,$fields,$to_fields,$to_types,$to_datatypes,$nrfields);
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
                  if ($to_fields[$i] && $to_datatypes[$i]!='file') {
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
                  if ($r=$db->Execute($query)) {
                      $modified++;
                      for ($i=0; $nrfields;$i++) {
                         if ($to_datatypes[$i]=='file') {
                            $fileids=explode(',',$fields[$i]);
                            foreach ($fileids as $fileid)
			       import_file ($db,$tableid,$recordid,$HTTP_POST_VARS["fields_$i"],$to_fields[$i],$fileid,$system_settings);
                          }
                      }
                   }
               }
            }

            // if there is no primary key set, we simply INSERT a new record
            //if ( !(isset($pkey) || isset($recordid)) ) {
	    elseif ($pkeypolicy!='onlyupdate') {
$db->debug=true;
               $query_start="INSERT INTO $table (";
               $query_end=" VALUES (";
               $newid=false;
               for ($i=0;$i<$nrfields;$i++) {
                  if ($fields[$i] && $to_fields[$i] && $to_datatypes[$i]!='file') {
                     // if date is an int, assume it is UNIX date, otherwise convert 
                     if ($to_datatypes[$i]=='date') {
                        if (!is_int($fields[$i]))
                           $fields[$i]=mymktime($fields[$i]);
                     }
                     //  
                     if ($to_fields[$i]=='date') {
                        $newdate=true;
                     }
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
                     if ($id && $newdate)
                        $query="$query_start id,gr,gw,er,ew,lastmoddate,lastmodby,ownerid) $query_end '$id',$gr,$gw,$er,$ew,'$lastmoddate','$lastmodby','$ownerid')";
                     else
                        $query="$query_start id,gr,gw,er,ew,date,lastmoddate,lastmodby,ownerid) $query_end '$id',$gr,$gw,$er,$ew,'$lastmoddate','$lastmoddate','$lastmodby','$ownerid')";
                  }
                  else {
                     // let's make sure the 'next' id will be higher
                     if ($newid)
                        while ($id<$newid)
                           $id=$db->GenID($table.'_id_seq');
                     // check whether this id was used
                     if (get_cell($db,$table,'id','id',$newid))
                        $duplicateid++;
                     if ($newdate)
                     $query="$query_start gr,gw,er,ew,lastmoddate,lastmodby,ownerid) $query_end $gr,$gw,$er,$ew,'$lastmoddate','$lastmodby','$ownerid')";
                     else
                        $query="$query_start gr,gw,er,ew,date,lastmoddate,lastmodby,ownerid) $query_end $gr,$gw,$er,$ew,'$lastmoddate','$lastmoddate','$lastmodby','$ownerid')";
                  }
                  if ($r=$db->Execute($query)) {
                      $inserted++;
                      for ($i=0; $i<$nrfields;$i++) {
                         if ($to_datatypes[$i]=='file') {
                            $fileids=explode(',',$fields[$i]);
                            foreach ($fileids as $fileid)
                               import_file ($db,$tableid,$id,$HTTP_POST_VARS["fields_$i"],$to_fields[$i],$fileid,$system_settings);
                          }
                      }
                  }
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
         @unlink ("$tmpdir/$tmpfile");
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
            $menu=$r->GetMenu("fields_$i",$fields[$i]);
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
         echo "<td><input type='radio' name='skipfirstline' value='yes' checked> Yes</input></td>\n";
         echo "<td><input type='radio' name='skipfirstline' value='no'> no</input></td></tr>\n";

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
   <td><input type='radio' name='delimiter_type' value='comma'> comma</td>
   <td><input type='radio' name='delimiter_type' value='tab' checked> tab</td>
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
