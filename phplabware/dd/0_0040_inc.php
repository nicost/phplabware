<?php

// creates table IPnumbers

$newtableid=$db->GenID("tableoftables_gen_id_seq");
$newtablename=ipnumbers;
$newtablelabel="IP";
for ($i=0;$i<$hownew;$i++) {$newtablelabel.="new";}
while (get_cell($db,"tableoftables","id","tablename",$newtablename)) {
   $newtablename.="n";
   $hownew++;
}
for ($i=0;$i<$hownew;$i++) 
   $newtablelabel.="new";
$newtableshortname=substr($newtablename,0,3).$newtableid;
$newtable_realname=$newtablename."_".$newtableid;
$newtable_desc_name=$newtable_realname."_desc";
$r=$db->Execute("INSERT INTO tableoftables (id,sortkey,tablename,shortname,display,permission,custom,real_tablename,table_desc_name,label,plugin_code) VALUES ('$newtableid','0','$newtablename','$newtableshortname','Y','Users',NULL,'$newtable_realname','$newtable_desc_name','$newtablelabel','plugins/ipnumbers_plugin.php')");
$rg=$db->Execute ("SELECT id FROM groups");
while (!$rg->EOF) {
   $groupid=$rg->fields[0];
   $db->Execute ("INSERT INTO groupxtable_display VALUES ($groupid,$newtableid)");
   $rg->MoveNext();
}

if ($r) {
   $rb=$db->Execute("CREATE TABLE $newtable_desc_name (
      id int NOT NULL,
      sortkey int,
      label text,
      columnname text,
      display_table char(1),
      display_record char(1),
      required char(1),
      type text,
      datatype text,
      associated_table text,
      associated_column text,
      associated_local_key text,
      thumb_x_size int,
      thumb_y_size int,
      link_first text,
      link_last text,
      modifiable char(1) )");
   if ($rb) {
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'175','online','online','Y','Y','N','text','text','','','','','','','','N')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'140','Computer/User','title','Y','Y','Y','text','text','','','','','','','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'180','Apple talk Name','atalkname','Y','Y','N','text','text','','','','','','','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'200','Other','other','Y','Y','N','text','text','','','','','','','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'160','IP','ipnumber','Y','Y','N','text','text','','','','','','','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'170','OS','osother','Y','Y','N','text','pulldown','ipnumbers_10005ass_1','osother from ipnumbers_10005ass_1 where ','','','','','','Y')");
      $ass_table=$newtable_realname."ass";
      $id_ass=$db->GenId($ass_table,20);
      $ass_table.="_$id_ass";
      $db->Execute("CREATE TABLE $ass_table (
         id int PRIMARY KEY,
         sortkey int,
         type text,
         typeshort text)");
      $db->Execute("UPDATE $newtable_desc_name SET associated_table='$ass_table' WHERE id=$newid");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'100','id','id','N','N','N','int','text','','','','','','','','N')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'110','access','access','N','N','N','varchar(9)','text','','','','','','','','N')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'120','ownerid','ownerid','N','N','N','int','text','','','','','','','','N')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'130','magic','magic','N','N','N','int','text','','','','','','','','N')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'150','lastmoddate','lastmoddate','N','N','N','int','text','','','','','','','','N')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'160','lastmodby','lastmodby','N','N','N','int','text','','','','','','','','N')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'170','date','date','N','N','N','int','text','','','','','','','','N')");
      // and finally create the table
      $rc=$db->Execute(" CREATE TABLE $newtable_realname (
         online text ,
         title text ,
         atalkname text ,
         other text ,
         ipnumber text ,
         osother text ,
         id int  NOT NULL,
         access varchar(9) ,
         ownerid int ,
         magic int ,
         lastmoddate int ,
         lastmodby int ,
         date int  ) ");

   }


}

?>
