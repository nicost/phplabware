<?php

// dumpcontent.php - Dumps table content in tab-delimited file
// dumpcontent.php - author: Nico Stuurman<nicost@sourceforge.net>

  /***************************************************************************
  * Dumps table content in tab-delimited file                                *
  * Takes 'tablename' as a get variable                                      *
  *                                                                          *
  * Copyright (c) 2003 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
*  option) any later version.                                              *
  \**************************************************************************/                                                                                     
// this may take a long time.  Simply kill me if I hang
ini_set('max_execution_time','0');

require ('include.php');
require ('includes/db_inc.php');
require ('includes/general_inc.php');

printheader($httptitle,false);
navbar ($USER['permissions']);

if (!$USER['permissions'] & $SUPER) {
   echo "<h3 align='center'>Sorry, this page is not for you.</h3>\n";
   printfooter($db, $USER);
   exit();
}

// Three GET variables are useful:
// tablename
// fields - let's you export only a subset of columns
// valuesOnly - switch wether or not values should be 'expanded'

$tablename=$HTTP_GET_VARS['tablename'];
$tableid=get_cell($db,'tableoftables','id','tablename',$tablename);
if (!$tableid) {
   echo "<h3>This script will dump the contents of a table in a tab-delimited file. The contents of that file can be imported into phplabware or any other database program.</h3>";
   $r=$db->execute("SELECT id,tablename FROM tableoftables");
   if ($r) {
      echo "<table align='center'>\n";
      echo "<tr><td><h3>Select one of the following tables:</h3></td></tr>\n";
      while ($r && !$r->EOF) {
         echo "<tr><td><a href='dumpcontent.php?tablename=".$r->fields[1]."'>".$r->fields[1]."</a></td></tr>\n";
         $r->MoveNext();
      }
   }
   printfooter($d, $USER);
   exit();
}

$tableinfo=new tableinfo($db);

if (! ($HTTP_GET_VARS['fields'] || $HTTP_POST_VARS['selectfields'])) {
   $rfields=$db->Execute("SELECT columnname FROM {$tableinfo->desname}");
   $tr=$rfields->recordCount();
   $menu=$rfields->GetMenu('selectfields'," ",false,true,$tr);
   echo "<table align='center'>\n<tr><td>\n";
   echo "<form method='post' action='$PHP_SELF?tablename={$tableinfo->name}'>\n";
   echo "<h3>Select the fields you want to export: </h3><br>$menu<br>";
   echo "<input type='submit' name='submit' value='submit'>\n";
   echo "</form>\n</td></tr></table>\n";
   printfooter($d,$USER);
   exit();
}
if ($HTTP_POST_VARS['selectfields']) {
   foreach($HTTP_POST_VARS['selectfields'] as $field)
      $fields.=$field.",";
   // strip the last comma
   $fields=substr($fields,0,-1);
   //print_r($HTTP_POST_VARS['selectfields']);
}

if (!$tableinfo->id) {
   echo "<h3 align='center'>Table <i>$tablename</i> does not exist.</h3>\n";
   printfooter($d, $USER);
   exit();
}

$pre_seperator="";
$post_seperator="\t";

// open file to write output to
$tmpdir=$system_settings['tmpdir'].'/phplabwaredump';
if (!file_exists($tmpdir))
   mkdir ($tmpdir);
$outfile=$tmpdir.'/dumpcontent.txt';
$filedir=$tmpdir.'/files/';
$filetable=$tmpdir.'/files.txt';
// since it is a tmp dir we can destroy all content (right?)
if (file_exists($filedir))
   `rm -rf {$filedir}*`;
else
   mkdir ($filedir);

$fp=fopen($outfile,'w');
if (!$fp) {
   echo "<h3 align='center'>Failed to open <i>$outfile</i> for output</h3>\n";
   printfooter($db, $USER);
}
$ff=fopen($filetable,'w');
if (!$ff) {
   echo "<h3 align='center'>Failed to open <i>$filetable</i> for output</h3>\n";
   printfooter($db, $USER);
}
// write the column headers for the temp file table
fwrite ($ff,"id\tname\tmime\tsize\ttype\n");

if (!$fields) {
   if ($HTTP_GET_VARS['fields'])  
      $fields='id,'.$HTTP_GET_VARS['fields'];
   else
      $fields='id,'.comma_array_SQL($db,$tableinfo->desname,'columnname');
}
else
   $fields='id,'.$fields;
// we might have accidentaly added an extra id, delete that here:
$fields=preg_replace('/,id/','',$fields);

$headers=getvalues($db,$tableinfo,$fields);
if ($HTTP_GET_VARS['valuesOnly'])
   $valuesOnly=true;

foreach ($headers as $header) {
   if ($header['label'])
      fwrite ($fp,$pre_seperator.$header['label'].$post_seperator);
   else
      fwrite ($fp,$pre_seperator.'id'.$post_seperator);
}
fwrite ($fp,"\n");

$r=$db->Execute("SELECT $fields FROM ".$tableinfo->realname);
while ($r->fields['id'] && !$r->EOF) {
   $rowvalues=getvalues($db,$tableinfo,$fields,'id',$r->fields['id']);
   foreach ($rowvalues as $row) {
      if (is_array($row)) {
         // files will be exported to the directory files
         if ($row['datatype']=='file') {
            $files=get_files($db,$tableinfo->name,$row['recordid'],$row['columnid'],0);
            fwrite ($fp,$pre_seperator);
            for ($i=0;$i<sizeof($files);$i++) {
               $filecounter++;
               // write a temp table with all the file info,and provide an comma separated list of ids to that table here
               fwrite ($ff,++$filenr."\t".$files[$i]['name']."\t".$files[$i]['mime']."\t".$files[$i]['size']."\t".$files[$i]['type']."\n");
               fwrite ($fp,$filenr.',');
               $path=file_path($db,$files[$i]['id']);
               $cpstr="cp $path {$filedir}{$filenr}_{$files[$i]['name']}";
              `$cpstr`;
            }
            fwrite ($fp,$post_seperator);
         }
         else {
            
         if ($valuesOnly) {
            fwrite ($fp,$pre_seperator.$row['values'].$post_seperator);
         }
         else {
            if ($row['datatype']=='textlong')
               fwrite ($fp,$pre_seperator.$row['values'].$post_seperator);
            else
               fwrite ($fp,$pre_seperator.$row['text'].$post_seperator);
         }
         }
      }
      else
         fwrite ($fp,$pre_seperator.$row.$post_seperator);
   }
   fwrite ($fp,"\n");
   $counter++;
   $r->MoveNext();
}


fclose($fp);

echo "<h3>Wrote script to $outfile.</h3>";
echo "<h3>Wrote $counter records.</h3>";
if ($filecounter)
   echo "<h3>Saved $filecounter files in $filedir.</h3>";

printfooter($db, $USER);

?>
