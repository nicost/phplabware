<?php

// plugin_inc.php - skeleton file for plugin codes
// plugin_inc.php - author: Nico Stuurman

/* 
Copyright 2002, Nico Stuurman

This is a skeleton file to code your own plugins.
To use it, rename this file to something meaningfull,
add the path and name to this file (relative to the phplabware root)
in the column 'plugin_code' of 'tableoftables', and code away.  
And, when you make something nice, please send us a copy!

This program is free software: you can redistribute it and/ormodify it under
the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

*/


////
// !This function is called after a record has been added
// As an example, it is used to write some data concerning the 
// new record to a file for inclusion in a webpage
/*
function plugin_add ($db,$tableid,$id)
{
}
*/


////
// !Change/calculate/check values just before they are added/modified
// $fieldvalues is an array with the column names as key.
// Any changes you make in the values of $fieldvalues will result in 
// changes in the database. 
// You could, for instance, calculate a value of a field based on other fields
// make sure this function returns true (or does not exist), or additions/modification
// will fail!
function plugin_check_data($db,&$fieldvalues,$table_desc,$modify=false) 
{
   global $HTTP_POST_VARS, $HTTP_POST_FILES;


# Genbank entry parsing constants
# (may need to adjust!)
$KEYCOL=0;
$VALUECOL=12;
$FEATURECOL=5;
$FEATUREVALCOL=21;

   // we can not demand a file to be there since this might be a modify
   if (is_readable($HTTP_POST_FILES["gbfile"]["tmp_name"][0])) {
      // force the mime-type to be pdb compliant
       $HTTP_POST_FILES["gbfile"]["type"][0]="chemical/seq-na-genbank";  

$lines = file($HTTP_POST_FILES["gbfile"]["tmp_name"][0]);
$feature_section=FALSE;
$sequence_section=FALSE;

#parse file
foreach ($lines as $line) {

  #Accession?
  if (preg_match("/^ACCESSION\s+(.+)/", $line, $matches)) {
    $accessions=preg_split("/\s+/",$matches[1]);
    continue;
  }

  #VERSION?
  if (preg_match("/^VERSION/", $line)) {
    $vernid = preg_split("/\s+/", $line);
    list($junk,$tmp)=preg_split("/:/",$vernid[3]);
    $vernid[3]='g'.$tmp;
    $version=$vernid[2];
    $Nid=$vernid[3];
    continue ;
  }

  #COMMENT?  - We need to check for VNTI info in here.
  if (preg_match("/^COMMENT\s+(.+)/", $line, $matches)) {
    $comment_text=$matches[1];

    if (preg_match("/This file is created by Vector NTI/",$comment_text)) {
      $VNTI_file=TRUE;
    }
    if (preg_match("/VNTNAME\|(.*)\|/",$comment_text,$matches)) {
      $name=$matches[1];
    }
    if (preg_match("/VNTAUTHORNAME\|(.*)\|/",$comment_text,$matches)) {
      $auth=$matches[1];
    }

    #Done with comment checking - most of the rest of the VNTI stuff looks like
    #drawing info
    continue ;
  }
  # special case for the features table
  # features section is all text from ^FEATURES to ^ORIGIN
  if (preg_match("/^FEATURES/",$line)){
      unset($keyword);
      $feature_section=TRUE;
  }
  if ($feature_section){ 
      if (preg_match("/^FEATURES/", $line)) {
	  unset ($features);
	  continue;
      }
      
      if (preg_match("/^(BASE COUNT|ORIGIN)/", $line)) {
	  if (isset($feature)) 
	      $features[]=$feature;
	  unset ($feature);
	  if (preg_match("/^BASE COUNT/", $line)) 
	      continue;
	  
	  #special case for the sequence itself
	  if (preg_match("/^ORIGIN/", $line)) {
	      $feature_section=FALSE;
	      $sequence_section=TRUE;
	      continue;
	  }
      }
      $featurelabel = trim(substr($line,$FEATURECOL,$FEATUREVALCOL-$FEATURECOL));
      $featurevalue = trim(substr($line,$FEATUREVALCOL));
      if (isset($featurelabel)) {
	  if (isset($feature))
	      $features[]=$feature;
	  $feature = array('label'=>$featurelabel,'value'=>$featurevalue);
      } else {
	  $feature['value'] .= $featurevalue;
      }
      continue;
  }


#For now, don't parse any other keys

  #just scoop up sequence into one big string; we'll clean it up later.
  #assumes sequence is last thing in file
  if($sequence_section) {
      if (preg_match("/^\/\//",$line))
	  continue;      
    $sequence.=$line;
  }
}

# remove leading numbers and whitespace
$sequence = preg_replace("/^(.{9,10})/m", "", $sequence); #strip leader
$sequence = preg_replace("/(.{10}) /m", "$1", $sequence); # remove spacers
$sequence = preg_replace("/\s/", "", $sequence); #strip newlines and whitespace

	#set table values appropriately
	$fieldvalues["fullname"]=$name;
	$fieldvalues["author"]=$auth;
}
  return true;
}

////
// !Overrides the standard 'show record'function
function plugin_show($db,$tableinfo,$id,$USER,$system_settings,$backbutton=true)
{
}


////
// !Extends the search query
// $query is the complete query that you can change and must return
// $fieldvalues is an array with the column names as key.
// if there is an $existing_clause (boolean) you should prepend your additions
// with ' AND' or ' OR', otherwise you should not
function plugin_search($query,$fieldvalues,$existing_clause) 
  {
    return $query;
  }


////
  // !Extends function getvalues
  // $allfields is a 2-D array containing the field names of the table in the first dimension
  // and name,columnid,label,datatype,display_table,display_record,ass_t,ass_column,
  // ass_local_key,required,modifiable,text,values in the 2nd D
  function plugin_getvalues($db,&$allfields,$id,$tableid) 
  {
  }

////
  // !Extends function display_add
  // This lets you add information to every specific item
  function plugin_display_add ($db,$tableid,$nowfield)
  {
    if ($nowfield["name"]=="gbfile") {
      echo "<br>If a Vector NTI file is uploaded (above), the fields Name and Author will be extracted from the file.";
    }

  }


?>
