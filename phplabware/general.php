<?php
  
// general.php -  List, modify, delete and add to general databases
// general.php - author: Nico Stuurman <nicost@soureforge.net>
  /***************************************************************************
  * This script displays a table with protocols in phplabware.               *
  *                                                                          *
  * Copyright (c) 2002 by Ethan Garner,Nico Stuurman<nicost@sf.net>          *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

/// main include thingies
require('include.php');
require('includes/db_inc.php');
require('includes/general_inc.php');

$tableinfo=new tableinfo($db);

if (!$tableinfo->id) {
   printheader($httptitle);
   navbar($USER['permissions']);
   echo "<h3 align='center'> Table: <i>$HTTP_GET_VARS[tablename]</i> does not exist.</h3>";
   printfooter();
   exit();
}

$tableinfo->queryname=$queryname=$tableinfo->short.'_query';
$tableinfo->pagename=$pagename=$tableinfo->short.'_curr_page';
if ($HTTP_GET_VARS['viewid'])
   $viewid=$HTTP_GET_VARS['viewid'];
else
   $viewid=$HTTP_POST_VARS['viewid'];

if ($viewid) 
   // get list from preferences in table tableviews
   $Fieldscomma=viewlist($db,$tableinfo,$viewid); 
else
   // read all fields in from the description file
   $Fieldscomma=comma_array_SQL($db,$tableinfo->desname,columnname,"WHERE display_table='Y'");

// load plugin php code if it has been defined 
$plugin_code=get_cell($db,'tableoftables','plugin_code','id',$tableinfo->id);
if ($plugin_code)
   @include($plugin_code);

// register variables
$get_vars='tablename,md,showid,edit_type,add,jsnewwindow,modify';
globalize_vars($get_vars, $HTTP_GET_VARS);
$post_vars = 'add,md,edit_type,submit,search,searchj,serialsortdirarray';
globalize_vars($post_vars, $HTTP_POST_VARS);
// hack
if (isset($HTTP_POST_VARS['subm'])) {
   $submit=$HTTP_POST_VARS['subm'];
}

$httptitle .=$tableinfo->label;

// this shows a record entry in a new window, called through javascript
if ($jsnewwindow && $showid && $tableinfo->name) {
   printheader($httptitle.' - View record ');
   if (function_exists('plugin_show')){
      plugin_show($db,$tableinfo,$showid,$USER,$system_settings,false);
   }   
   else {
      // find the next and previous ids, so that we can show prev/next buttons
      $listb=may_read_SQL($db,$tableinfo,$USER,'tempb');
      $r=$db->Execute($HTTP_SESSION_VARS[$tableinfo->queryname]);
      while ($r && $r->fields['id']!=$showid && !$r->eof) {
         $previousid=$r->fields['id'];
         $r->MoveNext();
      }
      if ($r->fields['id']==$showid && !$r->eof) {
         $r->MoveNext();
         $nextid=$r->fields['id'];
      }
      show_g($db,$tableinfo,$showid,$USER,$system_settings,false,$previousid,$nextid);
   }   
   //show_report_templates_menu($db,$tableinfo,$showid);
   printfooter();
   exit();
}

// open a modify window in a new window, called through javascript
if ($jsnewwindow && $modify) {
   // simply translate a GET variable into a POST variable
   // it will be picked up below
   while((list($key, $val) = each($HTTP_GET_VARS))) {	
      // display form with information regarding the record to be changed
      if (substr($key, 0, 4) == 'mod_' && $val=='Modify') {
         $HTTP_POST_VARS[$key]=$val;
      }
   }
}

// Mode can be changed through a get var and is perpetuated through post vars
if ($HTTP_GET_VARS['md'])
   $md=$HTTP_GET_VARS['md'];

foreach($HTTP_POST_VARS as $key =>$value) {
   // for table links, search in the linked table instead of the current one
   if (substr($key, 0, 3) == 'max') {
      $cname=substr($key,4);
      $field=strtok($cname,'_');
      $value=$HTTP_POST_VARS["$cname"];
      // we need to replace this value with an id if appropriate
      if ($value)
         $HTTP_POST_VARS["$cname"]=find_nested_match($db,$tableinfo,$field,$value);
   }
   // check if sortup or sortdown arrow was been pressed
   else {
      if (substr($key,0,6)=='sortup') {
         // some browsers (like Safari) add '_y' to image links clicked
         if (substr($key,-2)=='_y')
            $sortup=substr($key,7,-2);
         else
            $sortup=substr($key,7);
      }
      if (substr($key,0,8)=='sortdown') {
         if (substr($key,-2)=='_y')
            $sortdown=substr($key,9,-2);
         else
            $sortdown=substr($key,9);
      }
   }
} 
reset ($HTTP_POST_VARS);
if ($searchj || $sortup || $sortdown)
   $search='Search';

/*****************************BODY*******************************/

// check whether user may see this table
if (!may_see_table($db,$USER,$tableinfo->id)) {
   printheader($httptitle);
   navbar($USER['permissions']);
   echo "<h3 align='center'>These data are not for you.  Sorry;(</h3>\n";
   printfooter();
   exit();
}
// check if something should be modified, deleted or shown
while((list($key, $val) = each($HTTP_POST_VARS))) {	
   // display form with information regarding the record to be changed
   if (substr($key, 0, 3) == 'mod' && $val=='Modify') {
      printheader($httptitle.' - Modify record ');
      navbar($USER['permissions']);
      $modarray = explode('_', $key);
      $r=$db->Execute("SELECT $tableinfo->fields FROM ".$tableinfo->realname." WHERE id=$modarray[1]"); 
      add_g_form($db,$tableinfo,$r->fields,$modarray[1],$USER,$PHP_SELF,$system_settings);
      printfooter();
      exit();
   }
   if (substr($key, 0, 3) == 'chg') {
      $chgarray = explode('_', $key);
      if ($val=='Change') {
         //$Fieldscomma=comma_array_SQL_where($db,$tableinfo->desname,'columnname','display_table','Y');
         $Fieldsarray=explode(',',$Fieldscomma);
         reset($HTTP_POST_VARS);
         while((list($key, $val) = each($HTTP_POST_VARS))) {	
            $testarray=explode('_',$key);
            if ( ($testarray[1]==$chgarray[1]) && (in_array ($testarray[0],$Fieldsarray))) {
               if (is_array($val)) {
                  // special treatment for mpulldowns
                  $rk=$db->Execute("SELECT key_table FROM {$tableinfo->desname} WHERE columnname='{$testarray[0]}'");
                  update_mpulldown($db,$rk->fields[0],$testarray[1],$val);
               } 
               else
                  $change_values[$testarray[0]]=$val;  
            }
         }
         if(check_g_data ($db,$change_values,$tableinfo,true)) {
            $modfields=comma_array_SQL_where($db,$tableinfo->desname,"columnname","modifiable","Y");
            modify($db,$tableinfo->realname,$modfields,$change_values,$chgarray[1],$USER,$tableinfo->id); 
         }
         break;
      }
   } 
   // delete file and show protocol form
   if (substr($key, 0, 3) == 'def') {
      printheader($httptitle);
      navbar($USER['permissions']);
      $modarray = explode('_', $key);
      $filename=delete_file($db,$modarray[1],$USER);
      $id=$HTTP_POST_VARS['id'];
      if ($filename)
         echo "<h3 align='center'>Deleted file <i>$filename</i>.</h3>\n";
      else
         echo "<h3 align='center'>Failed to delete file <i>$filename</i>.</h3>\n";
      add_g_form ($db,$tableinfo,$HTTP_POST_VARS,$id,$USER,$PHP_SELF,$system_settings);
      printfooter();
      exit();
   }
   // show the record only when javascript is not active
   if (substr($key, 0, 4) == 'view' && !$HTTP_SESSION_VARS['javascript_enabled']) {
      printheader($httptitle);
      navbar($USER['permissions']);
      $modarray = explode('_', $key);
      if (function_exists('plugin_show'))
         plugin_show($db,$tableinfo,$showid,$USER,$system_settings,false);
      else
         show_g($db,$tableinfo,$modarray[1],$USER,$system_settings,true);
      printfooter();
      exit();
   }

// Add/modify/delete pulldown menu items 
   if (substr($key, 0, 7) == 'addtype' && ($USER['permissions'] & $LAYOUT)) {
      printheader($httptitle,'','includes/js/tablemanage.js');
      $modarray = explode('_', $key);
      include('includes/type_inc.php');
      add_type($db,$edit_type);
      show_type($db,$edit_type,'',$tableinfo->name);	
      printfooter();
      exit();
   }
   if (substr($key, 0, 6) == 'mdtype' && ($USER['permissions'] & $LAYOUT)) {
      printheader($httptitle,"","includes/js/tablemanage.js");
      $modarray = explode("_", $key);
      include("includes/type_inc.php");
      mod_type($db,$edit_type,$modarray[1]);
      show_type($db,$edit_type,"",$tableinfo->name);
      printfooter();
      exit();
   }
   if (substr($key, 0, 6) == 'dltype' && ($USER['permissions'] & $LAYOUT)) {
      printheader($httptitle,"","includes/js/tablemanage.js");
      $modarray = explode("_", $key);
      include('includes/type_inc.php');
      del_type($db,$edit_type,$modarray[1],$tableinfo);
      show_type($db,$edit_type,"",$tableinfo->name);
      printfooter();
      exit();
   }
}

if ($edit_type && ($USER['permissions'] & $LAYOUT)) {
   printheader($httptitle);
   include('includes/type_inc.php');
   $assoc_name=get_cell($db,$tableinfo->desname,label,associated_table,$edit_type);
   show_type($db,$edit_type,$assoc_name,$tableinfo->name);
   printfooter();
   exit();
}

printheader($httptitle);
navbar($USER['permissions']);

// provide a means to hyperlink directly to a record
if ($showid && !$jsnewwindow) {

   if (function_exists('plugin_show'))
      plugin_show($db,$tableinfo,$showid,$USER,$system_settings,false);
   else {
      show_g($db,$tableinfo,$showid,$USER,$system_settings,true);
   }   
   printfooter();
   exit();
}

// when the 'Add' button has been chosen: 
if ($add) {
   add_g_form($db,$tableinfo,$field_values,0,$USER,$PHP_SELF,$system_settings);
}
else { 
    // first handle addition of a new record
   if ($submit == 'Add Record') {
      if (!(check_g_data($db, $HTTP_POST_VARS, $tableinfo) && 
            $id=add($db,$tableinfo->realname,$tableinfo->fields,$HTTP_POST_VARS,$USER,$tableinfo->id) ) ) {
         add_g_form($db,$tableinfo,$HTTP_POST_VARS,0,$USER,$PHP_SELF,$system_settings);
         printfooter ();
         exit;
      }
      else {  
         // $id ==-1 when the record was already uploaded
         if ($id>0) {
            // mpulldown
            $rd=$db->Execute('SELECT columnname,key_table FROM '.$tableinfo->desname." WHERE datatype='mpulldown'");
            while ($rd && !$rd->EOF){
               update_mpulldown($db,$rd->fields['key_table'],$id,$HTTP_POST_VARS[$rd->fields['columnname']]);
               $rd->MoveNext();
            }
            // upload files
            $rb=$db->Execute("SELECT id,columnname,associated_table FROM ".$tableinfo->desname." WHERE datatype='file'");
            while (!$rb->EOF) {
       	       $fileid=upload_files($db,$tableinfo->id,$id,$rb->fields['id'],$rb->fields['columnname'],$USER,$system_settings);
               // try to convert word files into html files
               if ($fileid)
                  $htmlfileid=process_file($db,$fileid,$system_settings); 
               $rb->MoveNext(); 
            }
            // upload images
            $rc=$db->Execute("SELECT id,columnname,associated_table,thumb_x_size FROM ".$tableinfo->desname." WHERE datatype='image'");
            while (!$rc->EOF) {
       	       $imageid=upload_files($db,$tableinfo->id,$id,$rc->fields['id'],$rc->fields['columnname'],$USER,$system_settings);
               // make thumbnails and do image specific stuff 
               if ($imageid)
                  process_image($db,$imageid,$rc->fields['thumb_x_size']);
               $rc->MoveNext(); 
            }
            // call plugin code to do something with newly added data
            if (function_exists("plugin_add"))
               plugin_add($db,$tableinfo->id,$id);
         }
         // to not interfere with search form 
         unset ($HTTP_POST_VARS);
	 // or we won't see the new record
	 unset ($HTTP_SESSION_VARS["{$queryname}"]);
      }
   }
   // then look whether it should be modified
   elseif ($submit=='Modify Record') {
      $modfields=comma_array_SQL_where($db,$tableinfo->desname,"columnname","modifiable","Y");
      // The pdf plugin wants to modify fields that have been set to modifiable=='N'
      if (! (check_g_data($db,$HTTP_POST_VARS,$tableinfo,true) && 
             // modify($db,$tableinfo->realname,$tableinfo->fields,$HTTP_POST_VARS,$HTTP_POST_VARS['id'],$USER,$tableinfo->id)) ) {
             modify($db,$tableinfo->realname,$modfields,$HTTP_POST_VARS,$HTTP_POST_VARS['id'],$USER,$tableinfo->id)) ) {
         add_g_form ($db,$tableinfo,$HTTP_POST_VARS,$HTTP_POST_VARS['id'],$USER,$PHP_SELF,$system_settings);
         printfooter ();
         exit;
      }
      else { 
         // mpulldown
         $rd=$db->Execute('SELECT columnname,key_table FROM '.$tableinfo->desname." WHERE datatype='mpulldown'");
         while ($rd && !$rd->EOF){
            update_mpulldown($db,$rd->fields['key_table'],$HTTP_POST_VARS['id'],$HTTP_POST_VARS[$rd->fields['columnname']]);
            $rd->MoveNext();
         }
         // upload files and images
         $rc=$db->Execute("SELECT id,columnname,datatype,thumb_x_size FROM $tableinfo->desname WHERE datatype='file' OR datatype='image'");
         while (!$rc->EOF) {
            if ($HTTP_POST_FILES[$rc->fields['columnname']]['name'][0]) {
               // delete all existing files
               delete_column_file ($db,$tableinfo->id,$rc->fields['id'],$HTTP_POST_VARS['id'],$USER);
               // store the file uploaded by the user
               $fileid=upload_files($db,$tableinfo->id,$HTTP_POST_VARS['id'],$rc->fields['id'],$rc->fields['columnname'],$USER,$system_settings);
               if ($rc->fields['datatype']=='file') {
                  // try to convert it to an html file
                  if ($fileid)
                     $htmlfileid=process_file($db,$fileid,$system_settings);
               }
               elseif ($rc->fields['datatype']=='image'){
                  // make thumbnails and do image specific stuff
                  if ($fileid)
                     process_image($db,$fileid,$rc->fields['thumb_x_size']);
               }
            }
            $rc->MoveNext(); 
         }
         // to not interfere with search form 
         unset ($HTTP_POST_VARS);
      }
   } 
   elseif ($submit=='Cancel')
      // to not interfere with search form 
      unset ($HTTP_POST_VARS);
   // or deleted
   elseif ($HTTP_POST_VARS) {
      reset ($HTTP_POST_VARS);
      while((list($key, $val) = each($HTTP_POST_VARS))) {
         if (substr($key, 0, 3) == 'del') {
            $delarray = explode('_', $key);
            delete ($db,$tableinfo->id,$delarray[1], $USER);
         }
      }
   } 

   if ($search=='Show All') {
      $num_p_r=$HTTP_POST_VARS['num_p_r'];
      unset ($HTTP_POST_VARS);
      ${$pagename}=1;
      unset ($HTTP_SESSION_VARS[$queryname]);
      unset ($serialsortdirarray);
      session_unregister($queryname);
   }
   $column=strtok($tableinfo->fields,',');
   while ($column) {
      ${$column}=$HTTP_POST_VARS[$column];
      $column=strtok(",");
   }
 
   // sort stuff
   $sortdirarray=unserialize(stripslashes($serialsortdirarray));
   $sortstring=sortstring($sortdirarray,$sortup,$sortdown);

   // set the number of records per page
   $num_p_r=paging($num_p_r,$USER);

   // get a list with all records we may see, create temp table tempb
   $listb=may_read_SQL($db,$tableinfo,$USER,'tempb');

   // prepare the search statement and remember it
   $fields_table='id,'.$Fieldscomma;

   ${$queryname}=make_search_SQL($db,$tableinfo,$fields_table,$USER,$search,$sortstring,$listb['sql']);
   $r=$db->Execute(${$queryname});

   // when search fails we'll revert to Show All after showing an error message
   if (!$r) {
      echo "<h3 align='center'>The server encountered an error executing your search.  Showing all records instead.</h3><br>\n";
echo "${$queryname}.<br>";
      $num_p_r=$HTTP_POST_VARS['num_p_r'];
      unset ($HTTP_POST_VARS);
      ${$pagename}=1;
      unset (${$queryname});
      unset ($HTTP_SESSION_VARS[$queryname]);
      unset ($serialsortdirarray);
      unset ($sortstring);
      session_unregister($queryname);
      ${$queryname}=make_search_SQL($db,$tableinfo,$fields_table,$USER,$search,$sortstring,$listb['sql']);
      $r=$db->Execute(${$queryname});
   }

   // set variables needed for paging
   $numrows=$r->RecordCount();

   // set the current page to what the user ordered
   ${$pagename}=current_page(${$pagename},$tableinfo->short,$num_p_r,$numrows);

   // work around bug in adodb/mysql
   $r->Move(1);

   // set $rp->AtFirstPage and $rp->AtLastPage, will be used in nex-Previous buttons
   first_last_page ($rp,${$pagename},$num_p_r,$numrows);

   // get variables for links 
   $sid=SID;
   if ($sid) $sid='&'.$sid;
   if ($tableinfo->name) $sid.="&tablename=$tableinfo->name";

   // print form;
   // $headers = getallheaders();

   $dbstring=$PHP_SELF."?"."tablename=$tableinfo->name&";
   $formname='g_form';
   echo "<form name='$formname' method='post' id='generalform' enctype='multipart/form-data' action='$PHP_SELF?$sid'>\n";
   echo "<input type='hidden' name='md' value='$md'>\n";

   echo "<table border=0 width='50%' align='center'>\n<tr>\n";
   
   // variable md contains edit/view mode setting.  Propagated as post var to remember state.  md can only be changed as a get variable
   $modetext="<a href='$PHP_SELF?tablename=$tableinfo->name&md=";
 
   $may_write=may_write($db,$tableinfo->id,false,$USER);
   if ($md=='edit') {
      $tabletext='Edit Table ';
      if ($may_write)
         $modetext.="view&".SID."'>(to view mode)</a>\n";
      else
         $modetext="";
   }
   else {
      $tabletext='View Table ';
      $modetext.="edit'>(to edit mode)</a>\n";
   }
   // write the first line shown in table view 
   if ($may_write)
      echo "<td align='center'><a href='$PHP_SELF?&add=Add&tablename=$tableinfo->name&".SID."'>Add Record</a></td>\n"; 
   else
       echo "<td>&nbsp;</td>\n";
   echo "<td align='center'>$tabletext <B>$tableinfo->label</B> $modetext</td>";
   echo "<td align='center'>".viewmenu($db,$tableinfo,$viewid)."</td>\n";
   echo "</tr>\n</table>\n";
   next_previous_buttons($rp,true,$num_p_r,$numrows,${$pagename},$db,$tableinfo);

   // print header of table
   echo "<table border='1' align='center'>\n";

   // get a list with ids we may see, $listb has all the ids we may see
   //$r=$db->CacheExecute(2,${$queryname});
   if ($db_type=='mysql') {
      $lista=make_SQL_csf ($r,false,'id',$nr_records);
      if (!$lista)
         $lista="-1";
      $lista=" id IN ($lista) ";
   }
   else {
      make_temp_table($db,'tempa',$r);
      $lista= " ($tableinfo->realname.id=tempa.uniqueid) ";
   }

   //  get a list of all fields that are displayed in the table
   $Allfields=getvalues($db,$tableinfo,$Fieldscomma,false,false);	
   
   // javascript to automatically execute search when pulling down 
   $jscript="onChange='document.g_form.searchj.value=\"Search\"; document.g_form.submit()'";

   // row with search form
   echo "<tr align='center'>\n";
   echo "<input type='hidden' name='searchj' value=''>\n";

   foreach($Allfields as $nowfield)  {
      if ($HTTP_POST_VARS[$nowfield['name']]) {
         $list=$listb['sql']; 
	 $count=$listb['numrows'];
      }
      else {
         $list=$lista;   
         $count=$listb['numrows'];
      }
      searchfield($db,$tableinfo,$nowfield,$HTTP_POST_VARS,$jscript);
   }	 

   echo "<td style='width: 5%'><input type=\"submit\" name=\"search\" value=\"Search\">&nbsp;";
   echo "<input type=\"submit\" name=\"search\" value=\"Show All\"></td>";
   echo "</tr>\n\n";

   if ($sortdirarray)
      echo "<input type='hidden' name='serialsortdirarray' value='".serialize($sortdirarray)."'>\n";
   echo "<tr>\n";

   foreach($Allfields as $nowfield)  {
      tableheader ($sortdirarray,$nowfield);
   }

   echo "<th>Action</th>\n";
   echo "</tr>\n\n";

   if ($md=='edit')
      display_table_change($db,$tableinfo,$Fieldscomma,${$queryname},$num_p_r,${$pagename},$rp,$r);
   else
      display_table_info($db,$tableinfo,$Fieldscomma,${$queryname},$num_p_r,${$pagename},$rp,$r);
   printfooter($db,$USER);
}
?>
