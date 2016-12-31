<?php
  
// general.php -  List, modify, delete and add to general databases
// general.php - author: Nico Stuurman <nicost@soureforge.net>
  /***************************************************************************
  * This script displays a table with protocols in phplabware.               *
  *                                                                          *
  * Copyright (c) 2002 by Ethan Garner,Nico Stuurman<nicost@sf.net>          *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of version 2 of the GNU General Public License          *
  *  as published by the Free Software Foundation                            *
  \**************************************************************************/


/// main include thingies
require('./include.php');
require('./includes/db_inc.php');
require('./includes/general_inc.php');


// variable initialization
$num_p_r=10;

// turn on adodb logging:
//$db->LogSQL();

$tableinfo=new tableinfo($db);

if (!$tableinfo->id) {
   printheader($httptitle);
   navbar($USER['permissions']);
   $_GET['tablename'] = str_replace('<', ' ', $_GET['tablename']);
   $_GET['tablename'] = str_replace('>', ' ', $_GET['tablename']);
   echo "<h3 align='center'> Table: <i>$_GET[tablename]</i> does not exist.</h3>";
   printfooter();
   exit();
}

$tableinfo->queryname=$queryname=$tableinfo->short.'_query';
$tableinfo->pagename=$pagename=$tableinfo->short.'_curr_page';

// Acquire active view from settings or get/post vars
if (isset($USER['settings']['view']["$tableinfo->name"]))
   $viewid=$USER['settings']['view']["$tableinfo->name"];
if (isset($_GET['viewid']) && is_numeric($_GET['viewid']))
   $viewid=$_GET['viewid'];
elseif (isset($_POST['viewid']) && is_numeric($_POST['viewid']))
      $viewid=$_POST['viewid'];

// Activate selected view or default
if (!empty($viewid) && $viewid) {
   // get list from preferences in table tableviews
   $Fieldscomma=viewlist($db,$tableinfo,$viewid); 
   // write viewid back to user preferences
   $USER['settings']['view'][$tableinfo->name]=$viewid;
} else {
   $viewid=NULL;
   // read all fields in from the description file
   $Fieldscomma=comma_array_SQL($db,$tableinfo->desname,'columnname',"WHERE display_table='Y'");
   // release viewid we remembered
   if (isset($USER['settings']['view'][$tableinfo->name]))
      unset($USER['settings']['view'][$tableinfo->name]);
}

// some browsers (Safari!!) do not return GET variables from a form using method='GET'
// but they return them as POST!!!
// I added the GET variable copyPOST to the form to try to work around this browser bug
// this penalizes server execution time for browsers (Firefox) that play fair
if (array_key_exists('copyPOST', $_GET) && $_GET['copyPOST']) {
   foreach ($_POST as $key => $value) {
      $_GET[$key] = $value;
   }
}

// Activate selected report output or default
if ( isset ($_GET['reportoutput']) && is_numeric($_GET['reportoutput']) ) {
   $USER['settings']['reportoutput']=$_GET['reportoutput'];
}
   

// load plugin php code if it has been defined 
$plugin_code=get_cell($db,'tableoftables','plugin_code','id',$tableinfo->id);
if ($plugin_code)
   @include($plugin_code);

// register variables
$get_vars='tablename,md,showid,edit_type,add,jsnewwindow,modify,search,searchj,serialsortdirarray';
globalize_vars($get_vars, $_GET);


// check if sortup or sortdown arrow was been pressed
foreach($_GET as $key => $value) {
   if (substr($key,0,6)=='sortup') {
      // some browsers (like Safari) add '_y' to image links clicked
      if (substr($key,-2)=='_y')
         $sortup=substr($key,7,-2);
      else
         $sortup=substr($key,7);
   } elseif (substr($key,0,8)=='sortdown') {
      if (substr($key,-2)=='_y')
         $sortdown=substr($key,9,-2);
      else
         $sortdown=substr($key,9);
   }
   // also check if the del button was pressed:
   if (substr($key,0,4)=='del_') {
      $_POST[$key]=$value;
   }
   $_GET['search']='Search';
}
reset($_GET);


$post_vars = 'add,md,edit_type,showid,submit,search,searchj,serialsortdirarray';
globalize_vars($post_vars, $_POST);
// hack
if (isset($_POST['subm'])) {
   $submit=$_POST['subm'];
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
      $r=$db->Execute($_SESSION[$tableinfo->queryname]);
      while ($r && $r->fields['id']!=$showid && !$r->eof) {
         $previousid=$r->fields['id'];
         $r->MoveNext();
      }
      if ($r->fields['id']==$showid && !$r->eof) {
         $r->MoveNext();
         $nextid=$r->fields['id'];
      }
      show_g($db,$tableinfo,$showid,$USER,$system_settings,false,$previousid,$nextid,$viewid);
   }   
   printfooter();
   exit();
}


// open a modify window in a new window, called through javascript
if ($jsnewwindow && $modify) {
   // simply translate a GET variable into a POST variable
   // it will be picked up below
   while((list($key, $val) = each($_GET))) {	
      // display form with information regarding the record to be changed
      if (substr($key, 0, 4) == 'mod_' && $val=='Modify') {
         $_POST[$key]=$val;
      }
      if (substr($key, 0, 4) == 'chg_') {
         $_POST[$key]=$val;
      }
   }
   reset($_GET);
}

// Mode can be changed through a get var and is perpetuated through post vars
if (array_key_exists('md', $_GET) && $_GET['md'])
   $md=$_GET['md'];
// check to avoid cross-site scripting
if ($md != 'edit')
   $md = 'notediting';

foreach($_POST as $key =>$value) {
   // for table links, search in the linked table instead of the current one
   if (substr($key, 0, 3) == 'max') {
      $cname=substr($key,4);
      $field=strtok($cname,'_');
      $value=$_POST[$cname];
      // we need to replace this value with an id if appropriate
      if ($value)
         $_POST[$cname]=find_nested_match($db,$tableinfo,$field,$value);
   }
} 
reset ($_POST);

if ($searchj || isset($sortup) || isset($sortdown) || array_key_exists('next', $_POST) || array_key_exists('previous', $_POST)) {
   $search='Search';
}

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
while((list($key, $val) = each($_POST))) {	
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
         // The extra fields are needed to keep the access permissions right
         $Fieldsarray=explode(',',$Fieldscomma.',grr,grw,evr,evw');
         reset($_POST);
         while((list($key, $val) = each($_POST))) {	
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
            $modfields=comma_array_SQL_where($db,$tableinfo->desname,'columnname','modifiable','Y');
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
      $id=$_POST['id'];
      if ($filename)
         echo "<h3 align='center'>Deleted file <i>$filename</i>.</h3>\n";
      else
         echo "<h3 align='center'>Failed to delete file <i>$filename</i>.</h3>\n";
      add_g_form ($db,$tableinfo,$_POST,$id,$USER,$PHP_SELF,$system_settings);
      printfooter();
      exit();
   }
   // show the record only when javascript is not active
   if (substr($key, 0, 4) == 'view' && !$_SESSION['javascript_enabled']) {
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
      printheader($httptitle,'','./includes/js/tablemanage.js');
      $modarray = explode('_', $key);
      include('./includes/type_inc.php');
      add_type($db,$edit_type);
      show_type($db,$edit_type,'',$tableinfo->name);	
      printfooter();
      exit();
   }
   if (substr($key, 0, 6) == 'mdtype' && ($USER['permissions'] & $LAYOUT)) {
      $modarray = explode("_", $key);
      include('./includes/type_inc.php');
      // Ajax-based request do not need much in terms of an answer:
      if ($_POST['jsrequest']) {
         mod_type($db,$edit_type,$modarray[1]);
      } else {
         printheader($httptitle,"",'./includes/js/tablemanage.js');
         mod_type($db,$edit_type,$modarray[1]);
         show_type($db,$edit_type,"",$tableinfo->name);
         printfooter();
      }
      exit();
   }
   if (substr($key, 0, 6) == 'dltype' && ($USER['permissions'] & $LAYOUT)) {
      printheader($httptitle,"",'./includes/js/tablemanage.js');
      $modarray = explode('_', $key);
      include('./includes/type_inc.php');
      del_type($db,$edit_type,$modarray[1],$tableinfo);
      show_type($db,$edit_type,"",$tableinfo->name);
      printfooter();
      exit();
   }
}

if ($edit_type && ($USER['permissions'] & $LAYOUT)) {
   printheader($httptitle, "", './includes/js/tablemanage.js');
   include('./includes/type_inc.php');
   $assoc_name=get_cell($db,$tableinfo->desname,label,associated_table,$edit_type);
   show_type($db,$edit_type,$assoc_name,$tableinfo->name);
   printfooter();
   exit();
}

if ($md=='edit') {
   printheader($httptitle,'','./includes/js/editview.js');
} else {
   printheader($httptitle);
}
navbar($USER['permissions']);

// provide a means to hyperlink directly to a record
if ($showid && !$jsnewwindow) {

   if (function_exists('plugin_show'))
      plugin_show($db,$tableinfo,$showid,$USER,$system_settings,false);
   else {
      show_g($db,$tableinfo,$showid,$USER,$system_settings,true,false,false,$viewid);
   }   
   printfooter();
   exit();
}



// when the 'Add' button has been chosen: 
if ($add && $md!='edit') {
   add_g_form($db,$tableinfo,$field_values,0,$USER,$PHP_SELF,$system_settings);
} else {
   // in edit mode, create a new record filled with default values
   if ($md=='edit' && $add) {
      $fieldvalues=set_default($db,$tableinfo,$tableinfo->fields,$USER,$system_settings);
      $id=add($db,$tableinfo->realname,$tableinfo->fields,$fieldvalues,$USER,$tableinfo->id);
      // Reset Search statement so that we can be sure the newly added records is on top:
      $search='Show All';
      
   } 
    // first handle addition of a new record
   if (isset($submit) && $submit == 'Add Record') {
      if (!(check_g_data($db, $_POST, $tableinfo) && 
            $id=add($db,$tableinfo->realname,$tableinfo->fields,$_POST,$USER,$tableinfo->id) ) ) {
         add_g_form($db,$tableinfo,$_POST,0,$USER,$PHP_SELF,$system_settings);
         printfooter ();
         exit;
      }
      else {  
         // $id ==-1 when the record was already uploaded
         if ($id>0) {
            // mpulldown
            $rd=$db->Execute('SELECT columnname,key_table FROM '.$tableinfo->desname." WHERE datatype='mpulldown'");
            while ($rd && !$rd->EOF){
               update_mpulldown($db,$rd->fields['key_table'],$id,$_POST[$rd->fields['columnname']]);
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
            if (function_exists('plugin_add'))
               plugin_add($db,$tableinfo->id,$id);
         }
         // to not interfere with search form 
         unset ($_POST);
	 // or we won't see the new record
	 unset ($_SESSION["{$queryname}"]);
      }
   }
   // then look whether it should be modified
   elseif (isset($submit) && $submit=='Modify Record') {
      $modfields=comma_array_SQL_where($db,$tableinfo->desname,"columnname","modifiable","Y");
      // The pdf plugin wants to modify fields that have been set to modifiable=='N'
      if (! (check_g_data($db,$_POST,$tableinfo,true) && 
             modify($db,$tableinfo->realname,$modfields,$_POST,$_POST['id'],$USER,$tableinfo->id)) ) {
         add_g_form ($db,$tableinfo,$_POST,$_POST['id'],$USER,$PHP_SELF,$system_settings);
         printfooter ();
         exit;
      }
      else { 
         // mpulldown
         $rd=$db->Execute('SELECT columnname,key_table FROM '.$tableinfo->desname." WHERE datatype='mpulldown'");
         while ($rd && !$rd->EOF){
            update_mpulldown($db,$rd->fields['key_table'],$_POST['id'],$_POST[$rd->fields['columnname']]);
            $rd->MoveNext();
         }
         // upload files and images
         $rc=$db->Execute("SELECT id,columnname,datatype,thumb_x_size FROM $tableinfo->desname WHERE datatype='file' OR datatype='image'");
         while (!$rc->EOF) {
            if ($_FILES[$rc->fields['columnname']]['name'][0]) {
               // delete all existing files
               //delete_column_file ($db,$tableinfo->id,$rc->fields['id'],$_POST['id'],$USER);
               // store the file uploaded by the user
               $fileid=upload_files($db,$tableinfo->id,$_POST['id'],$rc->fields['id'],$rc->fields['columnname'],$USER,$system_settings);
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
         unset ($_POST);
      }
   } 
   elseif (isset($submit) && $submit=='Cancel')
      // to not interfere with search form 
      unset ($_POST);
   // or deleted
   elseif ($_POST) {
      reset ($_POST);
      while((list($key, $val) = each($_POST))) {
         if ( (substr($key, 0, 3) == 'del') && ($val=='Remove') ) {
            $delarray = explode('_', $key);
            delete ($db,$tableinfo->id,$delarray[1], $USER);
         }
      }
   } 

   // this variable is used to store table related data into _SESSION
   $fieldvarsname=$tableinfo->short.'_fieldvars';

   if ($search=='Show All') {
      // unset all the search and sort parameters
      $num_p_r=$_GET['num_p_r'];
      $_GET=array();
      ${$pagename}=1;
      unset ($_SESSION[$queryname]);
      unset ($serialsortdirarray);
   } elseif ($search!='Search') {
      // if search is not set, we want to restore the search statement to the last time the user visited this page.  Restore the relevant settings in $_POST from $_SESSION:
      if (is_array($_SESSION[$fieldvarsname])) {
         foreach ($_SESSION[$fieldvarsname] as $key => $value) {
            if (($key != 'next') && ($key != 'previous')) {
               $_GET[$key]=$value;
            }
         }
      }
      $serialsortdirarray=$_GET['serialsortdirarray'];
      $search='Search';
   }
   $column=strtok($tableinfo->fields,',');
   while ($column) {
       if (!empty($_GET) && array_key_exists($column, $_GET))
          ${$column}=$_GET[$column];
      $column=strtok(",");
   }
 
   // sort stuff
   if (!empty($serialsortdirarray))
      $sortdirarray=unserialize(stripslashes($serialsortdirarray));
   if (empty($sortup))
      $sortup=false;
   if (empty($sortdown))
      $sortdown=false;
   $sortstring=sortstring($db,$tableinfo,$sortdirarray,$sortup,$sortdown);

   // set the number of records per page
   $num_p_r=paging($num_p_r,$USER);

   // get a list with all records we may see, create temp table tempb
   $listb=may_read_SQL($db,$tableinfo,$USER,'tempb');

   // prepare the search statement and remember it
   $fields_table='id,'.$Fieldscomma;

   ${$queryname}=make_search_SQL($db,$tableinfo,$fields_table,$USER,$search,$sortstring,$listb['sql']);
//$db->debug=true;
   $r=$db->Execute(${$queryname});
//$db->debug=false;

   // store sortdirarry in _SESSION using the appropriate table designation
   // need to do this after make_search_SQL
   $_SESSION[$fieldvarsname]['serialsortdirarray']=serialize($sortdirarray);

   // when search fails we'll revert to Show All after showing an error message
   if (!$r) {
      echo "<h3 align='center'>The server encountered an error executing your search.  Showing all records instead.</h3><br>\n";
      $num_p_r=$_GET['num_p_r'];
      unset ($_GET);
      ${$pagename}=1;
      unset (${$queryname});
      unset ($_SESSION[$queryname]);
      unset ($serialsortdirarray);
      unset ($sortstring);
      //session_unregister($queryname);
      ${$queryname}=make_search_SQL($db,$tableinfo,$fields_table,$USER,$search,$sortstring,$listb['sql']);
      $r=$db->Execute(${$queryname});

      if (!isset($r)  ) {
         echo "${$queryname}.<br>";
	 $db->debug=true;
         $r=$db->Execute(${$queryname});
	 $db->debug=false;
     }

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
   $actionLink='tablename='.$tableinfo->name;
   if ($sid) {
      $actionLink.='&amp;'.$sid;
   }

   // output javascript to cpature enter key and use it start Search:
   echo "<script type='text/javascript' language='JavaScript'>
   function searchOnEnter(e) {
      var pK = e? e.which: window.event.keyCode;
      var node = (e.target) ? e.target : ((e.srcElement) ? e.srcElement : null);
      if (pK == 13  && !(node.type==\"textarea\") ) {   
         document.g_form.searchj.value=\"Search\"; 
         document.g_form.submit();
      }
   }
   document.onkeypress = searchOnEnter;
   if (document.layers)
   document.captureEvents(Event.KEYPRESS);
   </script>
   ";

   // print form;
   //$dbstring=$PHP_SELF."?"."tablename=$tableinfo->name&";
   $formname='g_form';
   // NS 2015-02-24: change method from GET to POST, not sure if there are side-effects
   echo "<form name='$formname' method='POST' id='generalform' enctype='multipart/form-data' action='$PHP_SELF?$actionLink&amp;copyPOST=true'>\n";

   echo "<input type='hidden' name='tablename' value='$tableinfo->name'>\n";
   echo "<input type='hidden' name='md' value='$md'>\n";

   echo "<table border='0' width='75%' align='center'>\n<tr>\n";
   
   // variable md contains edit/view mode setting.  Propagated to remember state.  md can only be changed as a get variable
   $may_write=may_write($db,$tableinfo->id,false,$USER);

   if ($may_write)
      $modetext="<a href='$PHP_SELF?tablename=$tableinfo->name&amp;md=";
 
   if ($md=='edit') {
      $tabletext='Now Editing Table: ';
      if ($may_write)
         $modetext.="view&amp;".SID."'>(to view mode)</a>\n";
      else
         $modetext="";
   }
   else {
      $tabletext='Now Viewing Table: ';
      if ($may_write)
         $modetext.="edit'>(to edit mode)</a>\n";
   }
   // write the first line shown in table view 
   if ($may_write) {
      if ($md=='edit') {
         echo "<td align='center'><a href='$PHP_SELF?add=Add&amp;tablename=$tableinfo->name&amp;md=edit&amp;".SID."'>Add Record</a></td>\n"; 
      } else {
         //echo "<td align='center'><a href='$PHP_SELF?add=Add&amp;tablename=$tableinfo->name&amp;".SID."' target='_blank'>Add Record</a></td>\n"; 
         echo "<td align='center'><a href='$PHP_SELF?add=Add&amp;tablename=$tableinfo->name&amp;".SID."'>Add Record</a></td>\n"; 
      }
   } else {
       echo "<td>&nbsp;</td>\n";
   }
   echo "<td align='center'>$tabletext <B>$tableinfo->label</B> $modetext</td>";
   echo "<td align='center'>".viewmenu($db,$tableinfo,$viewid,false)."</td>\n";
   if ($may_write)
      echo "<td align='center'><a href='import.php?tableid={$tableinfo->id}'>Import Data</a></td>\n";
   echo "</tr>\n</table>\n";
   next_previous_buttons($rp,true,$num_p_r,$numrows,${$pagename},$db,$tableinfo,$viewid);

   // get a list with ids we may see, $listb has all the ids we may see
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

   echo "<input type='hidden' name='searchj' value=''>\n";
   // print header of table
   echo "<table border='1' align='center'>\n";

   // row with search form
   echo "<tr align='center'>\n";

   foreach($Allfields as $nowfield)  {
      if (!empty($_GET) && $_GET[$nowfield['name']]) {
         $list=$listb['sql']; 
	 $count=$listb['numrows'];
      }
      else {
         $list=$lista;   
         $count=$listb['numrows'];
      }
      searchfield($db,$tableinfo,$nowfield,$_GET,$jscript);
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
      display_table_info($db,$tableinfo,$Fieldscomma,${$queryname},$num_p_r,${$pagename},$rp,$r,$viewid);

// stop adodb logging:
//$db->LogSQL(false);

   printfooter($db,$USER);
   // php 4.2.2. stores only the contenct of $HTTP_SESSION_VARS, not of $_SESSION, so work around this bug here:
   if (version_compare(phpversion(), '4.2.2', '<=')) {
      $HTTP_SESSION_VARS=$_SESSION;
   }
   session_write_close();
}
?>
