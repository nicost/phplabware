<?php

// Adds the table constructs

$newtablename="constructs";
$newtablelabel="constructs";
$newtableid=$db->GenID("tableoftables_gen_id_seq");
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
$r=$db->Execute("INSERT INTO tableoftables (id,sortkey,tablename,shortname,display,permission,custom,real_tablename,table_desc_name,label,plugin_code) VALUES ('$newtableid','0','$newtablename','$newtableshortname','Y','Users',NULL,'$newtable_realname','$newtable_desc_name','$newtablelabel','')");
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
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'100','id','id','N','N','N','int','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'110','access','access','N','N','N','varchar(9)','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'130','magic','magic','N','N','N','int','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'140','Name/Source','source','Y','Y','N','text','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'100','Construct Name','title','Y','Y','Y','text','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'160','Cloning Vector','vector','Y','Y','N','text','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'260','Insert Name','insertname','Y','Y','N','text','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'280','Size-Insert (kB)','insertsize','N','Y','N','float','float',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'320','Sequenced','sequenced','N','Y','N','int','pulldown','constructs_10014ass_23',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
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
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'360','Comments','comments','N','Y','N','text','textlong',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'380','Sequence','sequence','N','Y','N','text','textlong',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'121','Number (VB)','number2','Y','Y','N','int','sequence',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'180','Size-Vector (kB)','vectorsize','N','Y','N','float','float',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'240','Subcloning Enzyme sites','enzymes','N','Y','N','text','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'400','Sequence file','file','Y','Y','N','int','file','constructs_10014_wi_24',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'420','Date entered','date','N','Y','N','int','date',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'430','Last modified on','lastmoddate','N','N','N','int','date',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'440','Last modified by','lastmodby','N','N','N','int','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'410','Submitted by','ownerid','Y','Y','N','int','user',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,' ')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'220','Bacterial Host','host','N','Y','N','int','pulldown','constructs_10014ass_21',NULL,NULL,NULL,NULL,NULL,NULL,NULL,' ')");
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
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'300','Organism of Origin','organism','N','Y','N','int','pulldown','constructs_10014ass_22',NULL,NULL,NULL,NULL,NULL,NULL,NULL,' ')");
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
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'200','Bacterial Resistance','resistance','Y','Y','N','int','pulldown','constructs_10014ass_20',NULL,NULL,NULL,NULL,NULL,NULL,NULL,' ')");
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
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'235','Eucaryotic Host','euhost','N','Y','N','int','pulldown','constructs_10014ass_25',NULL,NULL,NULL,NULL,NULL,NULL,NULL,' ')");
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
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'407','Gel (image file)','gel','N','Y','N','text','image',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'340','Concentration (ug/ul)','concentration','N','Y','N','float','float',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'402','Info file','infof','Y','Y','N','int','file','constructs_10014_wi_31',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'405','Map (image file)','map','N','Y','N','text','image',NULL,NULL,NULL,NULL,'256',NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'111','group read','gr','N','N','N','smallint','int',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'112','group write','gw','N','N','N','smallint','int',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'113','everyone read','er','N','N','N','smallint','int',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'114','everyone write','ew','N','N','N','smallint','int',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      
for ($i=0;$i<sizeof($lid);$i++) {
   // find local associated column
   $r=$db->Execute("SELECT id FROM $newtable_desc_name WHERE columnname='{$lasscolumnname[$i]}'");
   $db->Execute("UPDATE $newtable_desc_name SET associated_local_key='{$r->fields[0]}' WHERE id='{$lid[$i]}'");
}
// and finally create the table
      $rc=$db->Execute(" CREATE TABLE $newtable_realname (id int NOT NULL
,
         access varchar(9) ,
         magic int ,
         source text ,
         title text ,
         vector text ,
         insertname text ,
         insertsize float ,
         sequenced int ,
         comments text ,
         sequence text ,
         number2 int ,
         vectorsize float ,
         enzymes text ,
         file int ,
         date int ,
         lastmoddate int ,
         lastmodby int ,
         ownerid int ,
         host int ,
         organism int ,
         resistance int ,
         euhost int ,
         gel text ,
         concentration float ,
         infof int ,
         map text ,
         gr smallint ,
         gw smallint ,
         er smallint ,
         ew smallint  ) ");

   }
}
?>
