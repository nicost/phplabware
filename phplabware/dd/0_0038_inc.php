<?php




$newtableid=$db->GenID("tableoftables_gen_id_seq");
$newtablename=protocols;
$newtablelabel="protocols";
unset($hownew);
while (get_cell($db,"tableoftables","id","tablename",$newtablename)) {
   $newtablename.="n";
   $hownew++;
}
for ($i=0;$i<$hownew;$i++) 
   $newtablelabel.="new";
$newtableshortname=substr($newtablename,0,3).$newtableid;
$newtable_realname=$newtablename."_".$newtableid;
$newtable_desc_name=$newtable_realname."_desc";
$r=$db->Execute("INSERT INTO tableoftables (id,sortkey,tablename,shortname,display,permission,custom,real_tablename,table_desc_name,label,plugin_code) VALUES ('$newtableid','0','$newtablename','$newtableshortname','Y','Users',NULL,'$newtable_realname','$newtable_desc_name','$newtablelabel','plugins/protocols_plugin.php')");
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
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'100','id','id','N','N','N','int','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'110','access','access','N','N','N','varchar(9)','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'120','ownerid','ownerid','N','N','N','int','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'130','magic','magic','N','N','N','int','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'150','lastmoddate','lastmoddate','N','N','N','int','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'160','lastmodby','lastmodby','N','N','N','int','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'170','date','date','N','N','N','int','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL)");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'500','Category','type1','Y','Y','N','int','pulldown','protocols_new_10042ass_20',NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $ass_table1="ass$newtableid";
      $id_ass=$db->GenId($ass_table1,20);
      $ass_table1.="_$id_ass";
      $db->Execute("CREATE TABLE $ass_table1 (
         id int PRIMARY KEY,
         sortkey int,
         type text,
         typeshort text)");
      $db->Execute("UPDATE $newtable_desc_name SET associated_table='$ass_table1' WHERE id=$newid");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'200','Authors','type2','Y','Y','N','int','pulldown','protocols_new_10042ass_21',NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $ass_table2="ass$newtableid";
      $id_ass=$db->GenId($ass_table2,20);
      $ass_table2.="_$id_ass";
      $db->Execute("CREATE TABLE $ass_table2 (
         id int PRIMARY KEY,
         sortkey int,
         type text,
         typeshort text)");
      $db->Execute("UPDATE $newtable_desc_name SET associated_table='$ass_table2' WHERE id=$newid");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'140','Title','title','Y','Y','Y','text','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'400','Notes','notes','Y','Y','N','text','textlong',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $newid=$db->GenID("$newtable_desc_name"."_id");
      $filecolumnid=$newid;
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'600','File','file','Y','Y','N','text','file',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Y')");
      $filecolumnid=$newid;
      // and finally create the table
      $rc=$db->Execute(" CREATE TABLE $newtable_realname (
         id int  NOT NULL,
         access varchar(9) ,
         ownerid int ,
         magic int ,
         lastmoddate int ,
         lastmodby int ,
         date int ,
         type1 int ,
         type2 int ,
         title text ,
         notes text ,
         file text  ) ");

   }


   // copy data from 'old' protocol table to the new one
   unset($counter);
   set_magic_quotes_runtime(1);
   $tablesfk=get_cell($db,"tableoftables","id","real_tablename","protocols");
   $rcb=$db->Execute("SELECT * FROM protocols");
   while (!$rcb->EOF && $rcb) {
      $newid=$db->Genid($newtable_realname."_id_seq");
      $row=$rcb->fields;
      $rcopy=$db->Execute("INSERT INTO $newtable_realname VALUES ('$newid','$row[access]','$row[ownerid]','$row[magic]','$row[lastmoddate]','$row[lastmodby]','$row[date]','$row[type1]','$row[type2]','$row[title]','$row[notes]','$row[file]')");
      if (!$rcopy)
         $failed=true;
      else {
         $counter++;
         //change ownership of file to new table:
         $rfile=$db->Execute("UPDATE files SET tablesfk='$newtableid',ftableid='$newid',ftablecolumnid='$filecolumnid' WHERE tablesfk='$tablesfk' AND ftableid='$row[id]'");
         //adjust trusted users:
         $db->Execute("UPDATE trust SET tableid='$newtableid',recordid='$newid' WHERE tableid=$tablesfk AND recordid='$row[id]'");
      }
      $rcb->MoveNext();
   }
   if ($counter)
      echo "(protocols:) Inserted $counter records.<br>";
   // Now copy supporting table (type1=author, type2=categories
   $rcj=$db->Execute("SELECT * FROM pr_type1 ORDER BY id");
   while ($rcj && !$rcj->EOF) {
      $newid=$db->Genid($ass_table1."_id_seq");
      $row=$rcj->fields;
      $rcj_copy=$db->Execute("INSERT INTO $ass_table1 VALUES ('$newid','$row[sortkey]','$row[type]','$row[typeshort]')");
      if (!$rcj_copy)
         $failed=true;
      // since the ids are not necessarily the same, we'll have to adjust the links
      // in the main table
      $db->Execute("UPDATE $newtable_realname SET type1='$newid' WHERE type1='$row[id]'");
      $rcj->MoveNext();
   }
   $rcc=$db->Execute("SELECT * FROM pr_type2 ORDER BY id");
   while ($rcc && !$rcc->EOF) {
      $newid=$db->Genid($ass_table2."_id_seq");
      $row=$rcc->fields;
      $rcc_copy=$db->Execute("INSERT INTO $ass_table2 VALUES ('$newid','$row[sortkey]','$row[type]','$row[typeshort]')");
      if (!$rcc_copy)
         $failed=true;
      $db->Execute("UPDATE $newtable_realname SET type2='$newid' WHERE type2='$row[id]'");
      $rcc->MoveNext();
   }
   if ($failed)
      echo "Failed copying contents of table protocols.<br>";
   else {
      // delete the old tables
      $rnt=$db->Execute("SELECT * FROM $newtable_realname");
      if ($rnt->Numrows==$rcb->Numrows) {
         $db->Execute("DROP TABLE protocols");
         $db->Execute("DROP TABLE protocols_id_seq");
         $db->Execute("DROP SEQUENCE protocols_id_seq");
         $db->Execute("DROP TABLE pr_type1"); 
         $db->Execute("DROP TABLE pr_type1_id_seq"); 
         $db->Execute("DROP SEQUENCE pr_type1_id_seq"); 
         $db->Execute("DROP TABLE pr_type2"); 
         $db->Execute("DROP TABLE pr_type2_id_seq"); 
         $db->Execute("DROP SEQUENCE pr_type2_id_seq"); 
         $db->Execute("DELETE FROM tableoftables WHERE tablename='protocols'");
         $db->Execute("UPDATE tableoftables SET label='protocols' WHERE id='$newtableid'");
      }
      else
         echo "Problems copying the content of table protocols to the new table protocols.<br>";
   }
}


?>
