<?php

// cron.php - Maintance tasks. To be called from cron.
// cron.php - author: Nico Stuurman <nicost@sourceforge.net>

  /***************************************************************************
  * Copyright (c) 2002 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/                                                                             


// This might take a while:
ini_set('max_execution_time','0');

include ('includes/defines_inc.php');
include ('includes/functions_inc.php');
include ('includes/init_inc.php');
include ('includes/db_inc.php');

////
// !Writes the index files needed for full text searches of files
function doindexfile ($db,$filetext,$fileid,$indextable,$recordid,$pagenr)
{
   if (!$pagenr)
      $pagenr=1;
   $thetext=split("[ ,.:;\"'\n]",$filetext);
   foreach ($thetext as $word) {
      if (strlen($word)>2) {
         $r=$db->Execute("SELECT id FROM words WHERE word='$word'");
         $wordid=$r->fields[0];
         if (!$wordid) {
            $wordid=$db->GenID('word_seq');
            $db->Execute("INSERT INTO words VALUES ($wordid,'$word')");
         }
         // only add this entry if we did not have it before
         // we are not keeping track any more on which page this word occurred, only the first one
         $r=$db->Execute("SELECT wordid FROM $indextable WHERE wordid=$wordid AND fileid=$fileid AND recordid=$recordid");
         if ($r && $r->RecordCount()==0)  {
            $db->Execute("INSERT INTO $indextable VALUES ($wordid,$fileid,$pagenr,$recordid)");
         }
      }
   }
   return true;
}


// main body

// text/html files are indexed directly, pdf files are first converted
// with ghostscript
// words are entered in table 'words'
// links between files/records are kept in specifc tables

// we keep track of the time it takes to do the indexing
$starttime=microtime();

$host=getenv('HTTP_HOST');
if (! ($host=='localhost' || $host=='127.0.0.1') ) {
   echo "This script should only be called by the CRON daemon.";
   exit ();
}

$gs=$system_settings['gs'];
if (!@is_readable($gs))
   echo "Could not read ghostscipt binary (gs) at '$gs'.<br>";
   
   
// find unindexed files with mime types we can work with
$rfiles=$db->Execute("SELECT id,filename,tablesfk,ftableid,mime,ftablecolumnid FROM files WHERE indexed IS NULL AND (mime LIKE '%text%' OR mime LIKE '%pdf%')");

while ($rfiles && !($rfiles->EOF)) {

   // find out to which table we are going to write the index 
   $rdesc=$db->Execute("SELECT table_desc_name FROM tableoftables WHERE id=".$rfiles->fields['tablesfk']);
   if($rdesc->fields['table_desc_name']) {
      $rindextable=$db->Execute("SELECT associated_table FROM ".$rdesc->fields[table_desc_name]." WHERE id=".$rfiles->fields['ftablecolumnid']);
      if ($rindextable->fields['associated_table']) {
         // add the filename to the text so that it will be indexed too
         $filepath=file_path($db,$rfiles->fields['id']);
         $filename=$rfiles->fields['filename'];
         // treat text files and pdf files differently
         if (strstr($rfiles->fields['mime'],'text')) {
            $fp=fopen($filepath,'r');
            if ($fp) {
               while (!feof($fp)) {
                  $filetext.=fgetss($fp,64000);
               }
               fclose($fp);
            }
            $filetext=strtolower($filetext)."\n".$filename;
            if (doindexfile ($db,$filetext,$rfiles->fields['id'],$rindextable->fields['associated_table'],$rfiles->fields['ftableid'],1)) {
               $db->Execute ("UPDATE files SET indexed=1 WHERE id=".$rfiles->fields['id']);
               $textfilecounter++;
            }
         }
         // for pdf files we use ghostscript.  Part of this code was taken from docmgr
         elseif (strstr($rfiles->fields['mime'],'pdf') && $gs) {
            //first we have to figure out how many pages
            //are in the file.  this is a rough method.
            //we have gs kick up an error after it opens
            //the file and sees how many pages there are

            $numpages = `$gs -dNODISPLAY "$filepath" -c quit`;
            $pos1 = strpos($numpages,'through');
            $numpages = substr($numpages,$pos1);
            $pos2 = strpos($numpages,".");
            $numpages= trim(substr($numpages,8,$pos2-8));

            for ($page=1;$page<=$numpages;$page++) {
               //gs the page and return as a string
               $tempstring=`$gs -q -dNODISPLAY -dNOBIND -dWRITESYSTEMDICT -dSIMPLE -dFirstPage=$page -dLastPage=$page -c save -f ps2ascii.ps "$filepath" -c quit`; 
              //strip out all the trash from the string
              //$tempstring = string_clean($tempstring,$preventIndex,$keepIndex);
               $filetext=strtolower($tempstring)."\n".$filename;
               doindexfile ($db,$filetext,$rfiles->fields['id'],$rindextable->fields['associated_table'],$rfiles->fields['ftableid'],$page); 
            }
            $db->Execute ("UPDATE files SET indexed=1 WHERE id=".$rfiles->fields['id']);
            $pdffilecounter++;
         }
      }
   }
   echo 'Indexed file: '.$rfiles->fields['filename'].'.<br>';
   $rfiles->MoveNext();
}

// The rest just serves to report some statistics..
if (!$textfilecounter)
   $textfilecounter=0;
if (!$pdffilecounter)
  $pdffilecounter=0;
$endtime=microtime();
list($startmu,$starts)=explode(" ",$starttime);
list($endmu,$ends)=explode(" ",$endtime);
$process=$ends-$starts;
$procesmu=$endmu-$startmu;
$pt=$process+$procesmu;
$ptime=sprintf("%0f",$pt);

echo "Indexed $textfilecounter text files and $pdffilecounter pdf files in $ptime seconds<br>";

// load plugin php code if it has been defined 
if ($HTTP_GET_VARS['tablename']) {
   $tableinfo=new tableinfo($db);
   $plugin_code=get_cell($db,'tableoftables','plugin_code','id',$tableinfo->id);
   if ($plugin_code) {
      @include($plugin_code);
      // and execute the cron plugin
      if (function_exists('plugin_cron'))
         plugin_cron($db,$tableinfo);
   }
}

// we'll do the postgres maintenance
if (substr($db_type,0,8)=='postgres') {
   $db->debug=true;
   $db->Execute('VACUUM');
   $db->Execute('ANALYZE');
   $db->Execute('VACUUM ANALYZE');
   $db->debug=false;
   echo "Finished postgres maintenance.\n";
}
?>
