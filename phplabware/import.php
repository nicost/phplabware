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


require('./include.php');
require('./includes/db_inc.php');
require('./includes/general_inc.php');
require('./includes/tablemanage_inc.php');
include ('./includes/defines_inc.php');

$post_vars='delimiter,delimiter_type,quote,quote_type,tableid,nrfields,pkey,pkeypolicy,skipfirstline,tmpfile,ownerid,localfile';
globalize_vars($post_vars, $_POST);
$get_vars='tableid';
globalize_vars($get_vars, $_GET);

$permissions=$USER['permissions'];
/**
 * Prevent unauthorized assignment to other users:
 */
if (!($permissions & $SUPER)) {
   $ownerid=$USER['id'];
}
/**
 * Make sure  this user is allowed to work with the desired table
 */
if (isset($tableid)) {
   $query = "SELECT label,id FROM tableoftables LEFT JOIN groupxtable_display on tableoftables.id=groupxtable_display.tableid where display='Y' AND permission='Users' AND tableoftables.id=$tableid AND (groupid={$USER['group_array'][0]} ";
   for ($i=1;$i<sizeof($USER['group_array']);$i++) { 
      $query.="OR groupid='".$USER['group_array'][$i]."' ";
   }
   $query.=')';
   $r=$db->Execute($query);
   if (!$r->fields[0]) {
      navbar($USER['permissions']);
      echo "Illegal request detected. Bailing out";
      printfooter($db,$USER);
      exit;
   } else {
      $tablelabel=$r->fields[0];
   }
}

/**
 * The user who will own the imported records
 * This way we default to the one doing the import
 */
if (!empty($ownerid)) {
   $USERAS=getUserInfo($db,false,$ownerid);
}
if (!isset($USERAS)) {
   $USERAS=$USER;
}

/**
 * Print header and do further checks on permissions
 */
$httptitle.=' Import data';
printheader($httptitle,false,$jsfile);
$tmpdir=$system_settings['tmpdir'];

if (!($permissions & $USER)) {
   navbar($USER['permissions']);
   echo "<h3 align='center'><b>Sorry, this page is not for you</B></h3>";
   printfooter($db,$USER);
   exit;
}

navbar($USER['permissions']);

/**
 * interpret a date string according to the system settings
 * this can fail when importing data from another continent
 */
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

/**
 *  Upload files and enters then into table files
 *
 * files should be called file[] in _POST
 * filetitle in _POST will be inserted in the title field of table files
 * returns id of last uploaded file upon succes, false otherwise
 */
function import_file ($db,$tableid,$id,$columnid,$columnname,$tmpfileid,$system_settings)
{
   if (!$tmpfileid)
      return false;
print_r($tmpfileid);
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
   if (rename($tmplocation,"$filedir/$fileid".'_'."$originalname")) {
      $query="INSERT INTO files (id,filename,mime,size,title,tablesfk,ftableid,ftablecolumnid,type) VALUES ($fileid,'$originalname','$mime','$size',$title,'$tableid',$id,'$columnid','$filestype')";
      $db->Execute($query);
   }
   else
      $fileid=false;
   return $fileid;
}


/**
 *  returns variable delimiter, based on delimiter_type (a POST variable)
 *
 */
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
 
/**
 *  returns the string 'quote', based on quote_type (a POST variable)
 *
 */
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

/**
 *  corrects problems in input data (Removes quotes around text, 
 * checks for ints and floats, quotes text
 */
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
             if ($fields[$i]=="&nbsp;") {
                unset ($fields[$i]);
             } else {
                $Allfields=getvalues($db,$tableinfo,$to_fields[$i]);
                $rtemp=$db->Execute("SELECT id FROM {$Allfields[0]['ass_t']} WHERE typeshort='{$fields[$i]}'");
                if ($rtemp && $rtemp->fields[0]) {
                   $fields[$i]=$rtemp->fields[0];
                } else { // insert this new value in the type table:
                   $typeid=$db->GenId($Allfields[0]['ass_t'].'_id_seq');
                   unset($rtemp);
                   $rtemp=$db->Execute("INSERT INTO {$Allfields[0]['ass_t']} (id,type,typeshort,sortkey) VALUES ($typeid,'{$fields[$i]}','{$fields[$i]}','0')");
                   if ($rtemp) {
                      $fields[$i]=$typeid;
                   } else {
                      echo "Error during import.<br>";
                      unset($fields[$i]);
                   }
                }
             }
          } elseif ($field_datatypes[$i]=='table') {
            // Links to other tables are only made if this field istthe primary key and it matches to the entry in the associated table
             if ($fields[$i]=="&nbsp;") {
                unset ($fields[$i]);
             } else {
                $Allfields=getvalues($db,$tableinfo,$to_fields[$i]);
                print_r($Allfields);
                // only continue if this is the local key:
                exit;

             }
         } elseif ($field_datatypes[$i]!='mpulldown') {
          // for mpulldowns we have a problem since we do not have the new id yet
          // we'll deal with these later

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
}
          
/**
 *  in quoted lines, empty fields are (sometimes) not quoted
 *  we try to deal nicely with those here
 */
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

/**
 * Checks if these values exist in the mpulldown table
 * Adds them if they do not exist.
 * Makes the needed links in the ass table linking data and mpulldown tables
 */
function addmpulldown($db,$tableinfo,$id,$to_field,$field)
{
   /**
    * Analyze $field first, accept <br> as field seperators
    */
   $Allfields=getvalues($db,$tableinfo,$to_field);
   $fields=explode('<br>',$field);
   foreach($fields as $value) {
      /**
       * Checks if this value is already in the mpulldown table, if not, add it:
       */
      if ($value && ($value!='&nbsp;')) {
         $r=$db->Execute("SELECT id FROM {$Allfields[0]['ass_t']} WHERE type='$value' OR typeshort='$value'");
         $rassid=$r->fields[0];
         if (!$rassid) {
            /**
             * Value not found, insert it now
             */
            $rassid=$db->genID($Allfields[0]['ass_t'].'_id_seq');
            $r=$db->query("INSERT INTO {$Allfields[0]['ass_t']} (id,type,typeshort)
                           VALUES($rassid,'$value','$value')");
         }
         // now link make the links in the keytable
         if ($rassid) {
            $db->Execute("INSERT INTO {$Allfields[0]['key_t']} (recordid,typeid) VALUES ($id,$rassid)");
         }
      }
   }
}



/**
 * Start of the main code
 * do the final parsing (part 3)
 */
if (array_key_exists('assign', $_POST) && $_POST['assign']=='Import Data') {
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

   $table_link="general.php?tablename=$table_label"."&".SID;

   // fill the tmp table with file related data
   // Files are only imported through the phplabwaredump directory 
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
                $db->Execute($db->GetInsertSQL($rs,$row));
         }
      }
   }

   // construct Array holding field numbers set as primary key
   // This is to allow pirmary keys consisting of multiple fields
   foreach ($_POST AS $key=>$value) {
      $stuff= explode ('_',$key);
      if ($stuff[0]=='pkey' && is_numeric($stuff[1])) {
	 $pkey[]=$stuff[1];
      }
   }

   
   // find out to which phplabware column each imported column belongs
   // Array $to_fields contains the target column ids,
   // Array $to_types contains the target column types
   for ($i=0;$i<$nrfields;$i++) {
      $the_field=$_POST["fields_$i"];
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
       $_POST['dataupload']='Continue';
   } else {
      // do the database upload
      $delimiter=get_delimiter($delimiter,$delimiter_type);
      $quote=get_quote($quote,$quote_type);
      $fh=fopen("$tmpdir/$tmpfile",'r');
      if ($fh) {
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
            // if there is a column being used as a Match Field, we do an SQL
            // UPDATE, otherwise first create a default entry and modify that

            // First figure out if such a record already exists
	    $first = true;
	    $primaryKeyLine = '';
	    if ($pkey) {
	       foreach($pkey AS $j) {
		  if (!$first) 
		     $primaryKeyLine .= ' AND ';
		  $primaryKeyLine.= "$to_fields[$j]='$fields[$j]' ";
		  $first=false;
	       } 
	    }
	    // Go through 

            if (isset($primaryKeyLine)) {
               $r=$db->Execute("SELECT id FROM $table WHERE $primaryKeyLine");
               // only the first record that matched will be modified
               $existingid=$r->fields[0];
            } 

            // now enforce the Update/Overwrite policies
            $makeNewId=false;
            if ($pkeypolicy=='overwrite') {
               if ($existingid) {
                  $recordid=$existingid;
               } else {
                  $makeNewId=true;
               }
            } elseif ($pkeypolicy=='onlyupdate') {
               if ($existingid) {
                  $recordid=$existingid;
               }
            } elseif ($pkeypolicy=='skip') {
               if (!$existingid) {
                  $makeNewId=true;
               }
            } elseif ($pkeypolicy=='addall') {
               $makeNewId=true;
            }

            // if no Match Field was set, we'll add all records as new ones
            if (!$primaryKeyLine && ($pkeypolicy != 'onlyupdate')) {
               $makeNewId=true;
            }
               
            if ($makeNewId) {
               // generate a default record
               $tmpfields='gr,gw,er,ew';
               $fieldvalues=set_default($db,$tableinfo,$tmpfields,$USERAS,$system_settings);
               $recordid=add($db,$tableinfo->realname,$tableinfo->fields,$fieldvalues,$USERAS,$tableinfo->id);
            }

            // only continue if we have a record (existing or newly made) to modify
            if (isset($recordid)) {
               $query="UPDATE $table SET ";
               // import data from file into SQL statement
               for ($i=0; $i<$nrfields;$i++) {
                  if ($fields[$i] && $to_fields[$i] && $to_datatypes[$i]!='file' && $to_datatypes[$i]!='mpulldown') { // if date is an int, assume it is UNIX date, otherwise convert 
                     if ($to_datatypes[$i]=='date') {
                        if (!is_int($fields[$i])) {
                           $fields[$i]=mymktime($fields[$i]);
                        }
                     }
                     $worthit=true;
                     $query.="$to_fields[$i]='$fields[$i]',";
                  }
               }
               if ($worthit) {
                  // when doing an update we leave access and owner untouched
                  $query.=" lastmoddate='$lastmoddate', lastmodby='$lastmodby' WHERE id=$recordid";
                  if ($r=$db->Execute($query)) {
                      // keep count of new and modified records
                      if ($makeNewId) {
                         $inserted++;
                      } else {
                         $modified++;
                      }
                      for ($i=0; $i<$nrfields;$i++) {
                         if ($to_datatypes[$i]=='file') {
                            $fileids=explode(',',$fields[$i]);
                            foreach ($fileids as $fileid)
			       import_file ($db,$tableid,$recordid,$_POST["fields_$i"],$to_fields[$i],$fileid,$system_settings);
                          } elseif($to_datatypes[$i]=='mpulldown') {
                             addmpulldown($db,$tableinfo,$recordid,$to_fields[$i],$fields[$i]);
                         }
                      }
                   }
//$db->debug=false;
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
if (array_key_exists('dataupload', $_POST) && 
         $_POST['dataupload']=='Continue' && 
         may_write($db,$tableid,false,$USERAS)) {
   if ($error_string)
      echo $error_string;
   $filename=$_FILES['datafile']['name'];
   $delimiter=get_delimiter($delimiter,$delimiter_type);
   $quote=get_quote($quote,$quote_type);
   if ($delimiter && $tableid && $ownerid && ($filename || $tmpfile || $localfile) ) {
      if (!$system_settings['filedir']) {
         echo "<h3><i>Filedir</i> was not set.  Please correct this first in <i>setup</i></h3>\n";
         printfooter();
         exit();
      }
      $tmpdir=$system_settings['tmpdir'];
      if ($tmpfile || $localfile || move_uploaded_file($_FILES['datafile']['tmp_name'],"$tmpdir/$filename")) {
         if ($tmpfile) {
            $filename=$tmpfile;
         }
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
         echo "<th>Second line:</th>\n";
         echo "<th>Assign to Column:</th>\n";
         echo"<th>Match Field:</th>\n";
         echo "</tr>\n<tr>\n";
         $desc=get_cell($db,'tableoftables','table_desc_name','id',$tableid);
         $r=$db->Execute("SELECT label,id FROM $desc WHERE (display_record='Y' OR display_table='Y') ORDER BY sortkey");
         for($i=0;$i<$nrfields;$i++) {
            echo "   <td>$fields[$i]</td>\n";
            echo "   <td>$fields2[$i]</td>\n";
            $menu=$r->GetMenu("fields_$i",$fields[$i]);
            echo "   <td>$menu</td>\n";
            $r->Move(0);
            echo "   <td><input type='checkbox' name='pkey_$i' value='{$_POST['pkey_$i']}'></td>\n";
            echo "</tr>\n<tr>\n";
         }
         //echo "<td colspan=3></td><td><input type='checkbox' name='pkey_none' value='' checked> (none)</td>\n";
         echo "</tr>\n";
         $colspan=$nrfields+2;
         echo "</table>\n";

         echo "<br><table align='center'>\n";

         echo "<tr><th>Update/Overwrite policy</th>\n";
         echo "<td colspan=3><input type='radio' name='pkeypolicy' value='overwrite'> Overwrite when 'Match Field' matches, otherwise add the new record</input><br>\n";
         echo "<input type='radio' name='pkeypolicy' value='onlyupdate'> Overwrite when 'Match Field' matches, otherwise ignore the new record</input><br>\n";
         echo "<input type='radio' name='pkeypolicy' value='skip' checked> Skip when 'Match Field' matches, otherwise add the new record</input><br>\n";
         echo "<input type='radio' name='pkeypolicy' value='addall' checked> Ignore 'Match Field', add all new records</input></td></tr>\n";

         echo "<tr><th>Skip first line?</th>\n";
         echo "<td><input type='radio' name='skipfirstline' value='yes' checked> Yes</input></td>\n";
         echo "<td><input type='radio' name='skipfirstline' value='no'> No</input></td></tr>\n";

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

if (!empty($tableif) && $USERAS && !may_write($db,$tableid,false,$USERAS)) {
   $string.="Error: The selected user may not write to the selected database. ";
}


// Page with file to be uploaded, delimiter, table, and owner (Part 1)
if (!empty($string))  {
   echo "<h3 align='center'>$string</h3>";
}
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
$query= "SELECT label,id FROM tableoftables LEFT JOIN groupxtable_display on tableoftables.id=groupxtable_display.tableid where display='Y' AND permission='Users' AND (groupid={$USER['group_array'][0]} ";
for ($i=1;$i<sizeof($USER['group_array']);$i++) { 
   $query.="OR groupid='".$USER['group_array'][$i]."' ";
}
$query.= ') ORDER BY sortkey';
$r=$db->Execute($query);
$menu=$r->GetMenu2('tableid',$tableid);
echo "<td>$menu</td>\n";

if ($permissions & $SUPER) {
   $query = 'SELECT login,id FROM users ORDER By login';
   $r=$db->Execute($query);
   if ($r)
      $menu2=$r->GetMenu2('ownerid',$ownerid);
   echo "<td>$menu2</td>\n";
} else {
   echo "<td><input type='hidden' name='ownerid' value={$USER['id']}>";
   echo "{$USER['login']}</td>\n";
}
echo "</tr>\n";

echo "<tr><td colspan='4' align='center'><input type='submit' name='dataupload' value='Continue'></td></tr>\n";

echo "</table>\n";
echo "</form>\n";   
printfooter($db,$USER);
?>
