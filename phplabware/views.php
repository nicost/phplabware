<?php
  
// views.php - Editor for users table layout preferences
// views.php - author: Nico Stuurman <nicost@soureforge.net>
  /***************************************************************************
  * Copyright (c) 2003 by Nico Stuurman<nicost@sf.net>                       *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

// Needs get/postvars:tablename
// optional get/postvar: viewnameid

/// main include thingies
require('./include.php');
require('./includes/db_inc.php');
require('./includes/general_inc.php');
require('./includes/report_inc.php');

printheader($httptitle);
navbar($USER['permissions']);

if ($USER['permissions'] < $WRITE) {
   echo "<h4 align='center'>You do not have sufficient priveleges to create or edit views</h4>\n";
   printfooter();
   exit;
}

if (isset ($_POST['tablename']))
   $_GET['tablename']=$_POST['tablename'];
$tableinfo=new tableinfo($db);
if ($_GET['viewid'])
   $viewid=$_GET['viewid'];
else
   $viewid=$_POST['viewid'];
//make sure that the viewid is in sync with the selected table
$r=$db->Execute("SELECT viewnameid FROM tableviews WHERE tableid={$tableinfo->id} AND userid={$USER['id']} AND viewnameid={$viewid}");
if (!($r && $r->fields[0]))
   unset($viewid);

// Analyze user input and take appropriate action

// Delete
if ($_POST['delete']=='Remove' && $viewid) {
   // do a check for userid to be sure nobody deletes someone elses views
   if ($db->Execute ("DELETE FROM tableviews WHERE viewnameid=$viewid AND userid={$USER['id']}")) {
      $db->Execute ("DELETE FROM viewnames WHERE viewnameid=$viewid");
      unset ($viewid);
   }
}
// Create new views
if ($_POST['Create']=='Create' && $_POST['newview']) {
   // insert into table viewnames
   $id=$db->GenId('viewnames_id_seq');
   $viewid=$id;
   $viewname=$db->qstr($_POST['newview']);
   if ($db->Execute("INSERT INTO viewnames (viewnameid,viewname) VALUES ($id,$viewname)")) {
      // put default values into the tableview table
      $r=$db->Execute("SELECT id,display_table,display_record FROM {$tableinfo->desname}");
      while ($r && !$r->EOF) {
         if ($r->fields[1]=='Y')
            $db->Execute("INSERT INTO tableviews (userid,tableid,viewmode,viewnameid,columnid) VALUES ({$USER['id']},{$tableinfo->id},1,$id,{$r->fields[0]})");
         if ($r->fields[2]=='Y')
            $db->Execute("INSERT INTO tableviews (userid,tableid,viewmode,viewnameid,columnid) VALUES ({$USER['id']},{$tableinfo->id},2,$id,{$r->fields[0]})");
         $r->MoveNext();
      }
   }
}
// Modify an exising view based on user input
if ($_POST['modify']=='Modify' && $viewid && $tableinfo) {
   // First delete all existing entries
   $db->Execute("DELETE FROM tableviews WHERE userid={$USER['id']} AND viewnameid=$viewid AND tableid={$tableinfo->id}");
   // then add them back only if the radiobox was set to 'Y'
   while (list($key,$value)=each($_POST)) {
      if ($value=='Y') {
         $v=explode('_',$key);
         if ($v[0]=='tv')
            $db->Execute("INSERT INTO tableviews (userid,tableid,viewmode,viewnameid,columnid) VALUES ({$USER['id']},{$tableinfo->id},1,$viewid,{$v[1]})");
         elseif ($v[0]=='rv')
            $db->Execute("INSERT INTO tableviews (userid,tableid,viewmode,viewnameid,columnid) VALUES ({$USER['id']},{$tableinfo->id},2,$viewid,{$v[1]})");
      }
   }
}


// First section list tables that can be edited, existing views, edit and delete buttons
// open form
echo "<form name='views' method='post' action='$PHP_SELF'>\n";

// table for first line only
echo "<table width='100%' border='0'>\n";

echo "<tr>\n";
echo "<td align='center'>Edit views for table: ";

// make dropdown with accessible tablenames, select the current tablename
if ($USER['permissions'] & $SUPER)
   $r=$db->Execute ("SELECT label,tablename FROM tableoftables ORDER by sortkey ");
else {
   // JOIN for mysql
//   if ($db_type=='mysql')
      $r=$db->Execute("SELECT tableoftables.label,tableoftables.tablename FROM tableoftables LEFT JOIN groupxtable_display ON tableoftables.id=groupxtable_display.tableid WHERE groupxtable_display.groupid IN ({$USER['group_list']}) ORDER BY tableoftables.sortkey");
//   else
      // subselect for the rest
//      $r=$db->Execute("SELECT label,tablename FROM tableoftables WHERE id IN (SELECT tableid FROM groupxtable_display WHERE groupid IN ({$USER['group_list']})) ORDER BY sortkey");
}
echo $r->GetMenu2('tablename',$tableinfo->name,true,false,0,'OnChange="document.views.submit()"');
echo "</td>\n";

echo "<td align='center'>Views: ";
//make dropdown with viewnames, possibly dynamically changed by the selected tablename
// when tablename was not set we not to alter our query:
if ($tableinfo->id)
   $tablerequirement="AND tableviews.tableid={$tableinfo->id}";
if ($db_type=='mysql')
   $r=$db->Execute ("SELECT DISTINCT viewname,viewnames.viewnameid FROM viewnames LEFT JOIN tableviews ON viewnames.viewnameid=tableviews.viewnameid WHERE tableviews.userid={$USER['id']} $tablerequirement AND tableviews.viewmode=1");
else
   $r=$db->Execute ("SELECT viewname,viewnameid FROM viewnames WHERE viewnameid IN (SELECT viewnameid FROM tableviews WHERE userid={$USER['id']} $tablerequirement AND viewmode=1)");
echo $r->GetMenu2('viewid',$viewid,true,false,0,'OnChange="document.views.submit()"');

// insert Modify and Delete buttons for active view
echo "<input type='submit' name='modify' value='Edit'>\n";
echo "<input type='submit' name='delete' value='Remove' Onclick=\"if(confirm('Are you sure this view should be deleted?')){return true;}return false;\">\n";
// test button links to general.php
echo "<a href='general.php?tablename={$tableinfo->name}&amp;viewid=$viewid'>Test</a>\n";
echo "</td>\n";

echo "<td align='center'>Create new view: <input type='text' name='newview' size='10'>\n";
echo "<input type='submit' name='Create' value='Create'></td>\n";
echo "</tr>\n</table>\n";

echo "<hr>\n";

// Display modification window
if ($viewid) {
   // assemble arrays with all columnids that will be displayed
   $r=$db->Execute("SELECT columnid FROM tableviews WHERE viewnameid=$viewid AND viewmode=1");
   while ($r && !$r->EOF) {
      $tableview[]=$r->fields[0];
      $r->MoveNext();
   }
   $r=$db->Execute("SELECT columnid FROM tableviews WHERE viewnameid=$viewid AND viewmode=2");
   while ($r && !$r->EOF) {
      $recordview[]=$r->fields[0];
      $r->MoveNext();
   }
   echo "<table align='center' width='100%'>\n";
   echo "<tr><td align='center'>\n";
   echo "<table align='center'>\n";
   //echo "<form method='post' name='list' action='$PHP_SELF'>\n";
   echo "<tr><td>&nbsp;</td><th colspan='2'>Tableview</th><th colspan='2'>Recordview</th></tr>\n";
   echo "<tr><td>&nbsp;</td><th>Yes</th><th>No</th><th>Yes</th><th>No</th></tr>\n";
   $r=$db->Execute("SELECT label,id FROM {$tableinfo->desname} ORDER BY sortkey");
   while ($r && !$r->EOF) {
      echo "<tr><td>{$r->fields[0]}</td>";
      if (in_array($r->fields[1],$tableview)) {
         echo "<td align='center'><input type='radio' name='tv_{$r->fields[1]}' checked value='Y'>yes</td>\n";
         echo "<td align='center'><input type='radio' name='tv_{$r->fields[1]}' value='N'>no</td>\n";
      }
      else {
         echo "<td align='center'><input type='radio' name='tv_{$r->fields[1]}' value='Y'>yes</td>\n";
         echo "<td align='center'><input type='radio' name='tv_{$r->fields[1]}' checked value='N'>no</td>\n";
      }
      if (in_array($r->fields[1],$recordview)) {
         echo "<td align='center'><input type='radio' name='rv_{$r->fields[1]}' checked value='Y'>yes</td>\n";
         echo "<td align='center'><input type='radio' name='rv_{$r->fields[1]}' value='N'>no</td>\n";
      }
      else {
         echo "<td align='center'><input type='radio' name='rv_{$r->fields[1]}' value='Y'>yes</td>\n";
         echo "<td align='center'><input type='radio' name='rv_{$r->fields[1]}' checked value='N'>no</td>\n";
      }
      echo "</tr>\n";
      $r->MoveNext();
  }
   echo "<tr><td colspan=3 align='center'><input type='submit' name='modify' value='Modify'></td>\n";
   echo "<td colspan=3 align='center'><input type='reset' name='reset' value='Reset'></td></tr>\n";
   echo "</table>\n</table>\n";
}

echo "</form>\n";

?>
