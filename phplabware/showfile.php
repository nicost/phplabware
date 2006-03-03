<?php
  
// showfile.php -  Streams a file to the user
// showfile.php - author: Nico Stuurman <nicost@soureforge.net>
  /***************************************************************************
  * Afterchecking whether the user is allowed to see the file, it is send to *
  * the user's browser                                                       *
  *                                                                          *
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/


// main include thingies
require('./include.php');
require('./includes/db_inc.php');

$id=$_GET['id'];
$type=$_GET['type'];

if (!$id) {
   echo "<html><h3>404. File not found.</h3></html>";
   exit;
}
$r=$db->query("SELECT filename,tablesfk,ftableid,mime,size FROM files
               WHERE id=$id");
if ((!$r) || $r->EOF) {
   echo "<html><h3>404. File not found.</h3></html>";
   exit;
}
$tableid=$r->fields('tablesfk');
$tableitemid=$r->fields('ftableid');
$mime=$r->fields('mime');
// we keep a list with fileids that can be seen in the USER settings
if (! (@in_array($id,$USER['settings']['fileids'])))  {
   $tablename=get_cell($db,'tableoftables','tablename','id',$tableid);
   $_GET['tablename']=$tablename;
   $tableinfo=new tableinfo($db);
   if (!may_read($db,$tableinfo,$tableitemid,$USER))
      echo "<html><h3>401. Forbidden.</h3></html>";
}
if ($type=='small' || $type=='big') {   // this is an image
   if ($type=='small')
      $thumb=$system_settings['thumbnaildir']."/small/$id.jpg";
   if ($type=="big")
      $thumb=$system_settings['thumbnaildir']."/big/$id.jpg";
   if (@is_readable($thumb)) {
      header('Accept-Ranges: bytes');
      header('Connection: close');
      header('Content-Type: image/jpg');
      readfile($thumb);   
   }
   else
      echo "<html><h3>404. File not found.</h3></html>";
}
else {  // and this is a file
   $filedir=$system_settings['filedir'];
   $filename=$r->fields('filename');
   $filesize=$r->fields('size');
   // send headers
   header('Accept-Ranges: bytes');
   header('Connection: close');
   header("Content-Type: $mime");
   header("Content-Length: $filesize");
   header("Content-Disposition-type: attachment");
   header("Content-Disposition: attachment; filename=$filename");
   readfile("$filedir/$id"."_".$filename);   
}
?>
