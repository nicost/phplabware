<?php

// we don't want Ethan to end up with another ordertable:
$newtablename="Ordering";
if (!get_cell($db,"tableoftables","id","tablename",$newtablename)) {
$newtableid=$db->GenID("tableoftables_gen_id_seq");
$newtablelabel="Ordering";
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
$db->debug=true;
$r=$db->Execute("INSERT INTO tableoftables (id,sortkey,tablename,shortname,display,permission,custom,real_tablename,table_desc_name,label,plugin_code) VALUES ('$newtableid','0','$newtablename','$newtableshortname','Y','Users',NULL,'$newtable_realname','$newtable_desc_name','$newtablelabel','plugins/ordering_plugin.php')");
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
      key_table text,
      thumb_x_size int,
      thumb_y_size int,
      link_first text,
      link_last text,
      modifiable char(1) )");
   if ($rb) {
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'110','access','access','N','N','N','varchar(9)','text','','','','','','','','','')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'10009','Submitted by','ownerid','N','Y','N','int','user','','','','','','','','','')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'130','magic','magic','N','N','N','int','text','','','','','','','','','')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'1','title','title','Y','Y','Y','text','text','','','','','','','','','Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'10029','Last modified on','lastmoddate','N','N','N','int','date','','','','','','','','','')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'10039','Last modified by','lastmodby','N','N','N','int','user','','','','','','','','','')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'10019','Date entered','date','N','Y','N','int','date','','','','','','','','','')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'8','Company','Company','Y','Y','N','text','text','','','','','','','','','Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'4','date_requested','date_requested','Y','Y','N','text','text','','','','','','','','','Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'6','date_ordered','date_ordered','Y','Y','N','text','text','','','','','','','','','Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'13','reorder','reorder','N','Y','N','text','link','','','','','','','','','Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'3','ordered_by','ordered_by','Y','Y','N','text','text','','','','','','','','','Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'9','catalog number','catnum','Y','Y','N','text','text','','','','','','','','','Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'15','Notes','notes','Y','Y','N','text','textlong','','','','','','','','','Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'10','Quantity','quantity','Y','Y','N','text','text','','','','','','','','','Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'11','price','price','Y','Y','N','text','text','','','','','','','','','Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'2','urgency','urgency','Y','Y','N','text','pulldown','Ordering_10010ass_20','urgency from Ordering_10010ass_20 where ','','','','','','','Y')");
      $ass_table=$newtable_realname."ass";
      $id_ass=$db->GenId($ass_table,20);
      $ass_table.="_$id_ass";
      $db->Execute("CREATE TABLE $ass_table (
         id int PRIMARY KEY,
         sortkey int,
         type text,
         typeshort text)");
      $db->Execute("UPDATE $newtable_desc_name SET associated_table='$ass_table' WHERE id=$newid");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'12','email_me','confirmme','N','Y','N','int','pulldown','Ordering_10010ass_22','','','','','','','','Y')");
      $ass_table=$newtable_realname."ass";
      $id_ass=$db->GenId($ass_table,20);
      $ass_table.="_$id_ass";
      $db->Execute("CREATE TABLE $ass_table (
         id int PRIMARY KEY,
         sortkey int,
         type text,
         typeshort text)");
      $db->Execute("UPDATE $newtable_desc_name SET associated_table='$ass_table' WHERE id=$newid");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'9999','confirmed','confirmed','N','N','N','text','text','','','','','','','','','Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'7','Date recieved','date_rec','Y','Y','N','text','text','','','','','','','','','Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'5','Has been Ordered','isordered','Y','Y','N','int','pulldown','Ordering_10010ass_23','','','','','','','','Y')");
      $ass_table=$newtable_realname."ass";
      $id_ass=$db->GenId($ass_table,20);
      $ass_table.="_$id_ass";
      $db->Execute("CREATE TABLE $ass_table (
         id int PRIMARY KEY,
         sortkey int,
         type text,
         typeshort text)");
      $db->Execute("UPDATE $newtable_desc_name SET associated_table='$ass_table' WHERE id=$newid");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'111','group read','gr','N','N','N','smallint','int','','','','','','','','','Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'112','group write','gw','N','N','N','smallint','int','','','','','','','','','Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'113','everyone read','er','N','N','N','smallint','int','','','','','','','','','Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'114','everyone write','ew','N','N','N','smallint','int','','','','','','','','','Y')");

for ($i=0;$i<sizeof($lid);$i++) {
   // find local associated column
   $r=$db->Execute("SELECT id FROM $newtable_desc_name WHERE columnname='{$lasscolumnname[$i]}'");
   $db->Execute("UPDATE $newtable_desc_name SET associated_local_key='{$r->fields[0]}' WHERE id='{$lid[$i]}'");
}
// and finally create the table
      $rc=$db->Execute(" CREATE TABLE $newtable_realname (
         id int NOT NULL,
         access varchar(9) ,
         ownerid int ,
         magic int ,
         title text ,
         lastmoddate int ,
         lastmodby int ,
         date int ,
         Company text ,
         date_requested text ,
         date_ordered text ,
         reorder text ,
         ordered_by text ,
         catnum text ,
         notes text ,
         quantity text ,
         price text ,
         urgency text ,
         confirmme int ,
         confirmed text ,
         date_rec text ,
         isordered int ,
         gr smallint ,
         gw smallint ,
         er smallint ,
         ew smallint  ) ");

   }
$db->debug=false;
}
}
?>
