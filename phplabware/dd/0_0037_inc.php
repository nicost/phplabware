<?php




$newtableid=$db->GenID("tableoftables_gen_id_seq");
$newtablename=pdfs_new;
$newtablelabel="pdfs new";
for ($i=0;$i<$hownew;$i++) {$newtablelabel.="new";}
while (get_cell($db,"tableoftables","id","tablename",$newtablename)) {
   $newtablename.="new";
   $hownew++;
}
for ($i=0;$i<$hownew;$i++) 
   $newtablelabel.="new";
$newtableshortname=substr($newtablename,0,3).$newtableid;
$newtable_realname=$newtablename."_".$newtableid;
$newtable_desc_name=$newtable_realname."_desc";
$r=$db->Execute("INSERT INTO tableoftables (id,sortkey,tablename,shortname,display,permission,custom,real_tablename,table_desc_name,label,plugin_code) VALUES ('$newtableid','0','$newtablename','$newtableshortname','Y','Users',NULL,'$newtable_realname','$newtable_desc_name','$newtablelabel','plugins/pdfs_plugin.php')");
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
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'100','id','id','N','N','N','int','text','','','','','','','','')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'110','access','access','N','N','N','varchar(9)','text','','','','','','','','')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'120','ownerid','ownerid','N','N','N','int','text','','','','','','','','')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'130','magic','magic','N','N','N','int','text','','','','','','','','')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'150','lastmoddate','lastmoddate','N','N','N','int','text','','','','','','','','')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'160','lastmodby','lastmodby','N','N','N','int','text','','','','','','','','')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'170','date','date','N','N','N','int','text','','','','','','','','')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'200','Category','category','Y','Y','N','text','pulldown','pdfs_new_10015ass_21','','','','','','','Y')");
      $ass_tablec=$newtable_realname."ass";
      $id_ass=$db->GenId($ass_tablec,20);
      $ass_tablec.="_$id_ass";
      $db->Execute("CREATE TABLE $ass_tablec (
         id int PRIMARY KEY,
         sortkey int,
         type text,
         typeshort text)");
      $db->Execute("UPDATE $newtable_desc_name SET associated_table='$ass_tablec' WHERE id=$newid");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $filecolumnid=$newid;
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'300','PDF File','file','Y','Y','N','text','file','','','','','','','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'180','Author(s)','author','Y','Y','N','text','text','','','','','','','','N')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'210','Journal','journal','Y','Y','N','text','pulldown','pdfs_new_10015ass_23','','','','','','','N')");
      $ass_tablej=$newtable_realname."ass";
      $id_ass=$db->GenId($ass_tablej,20);
      $ass_tablej.="_$id_ass";
      $db->Execute("CREATE TABLE $ass_tablej (
         id int PRIMARY KEY,
         sortkey int,
         type text,
         typeshort text)");
      $db->Execute("UPDATE $newtable_desc_name SET associated_table='$ass_tablej' WHERE id=$newid");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'240','First page','fpage','Y','Y','N','int','int','','','','','','','','N')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'260','Last Page','lpage','N','Y','N','int','int','','','','','','','','N')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'140','Title','title','Y','Y','N','text','text','','','','','','','','N')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'205','Abstract','abstract','Y','Y','N','text','textlong','','','','','','','','N')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'300','Notes','notes','N','Y','N','text','textlong','','','','','','','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'219','Volume','volume','Y','Y','N','int','int','','','','','','','','N')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'215','Year','pubyear','Y','Y','N','int','int','','','','','','','','N')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'160','PMID','pmid','N','Y','N','int','int','','','','','','','','Y')");
      // and finally create the table
      $rc=$db->Execute(" CREATE TABLE $newtable_realname (
         id int  NOT NULL,
         access varchar(9) ,
         ownerid int ,
         magic int ,
         lastmoddate int ,
         lastmodby int ,
         date int ,
         category text ,
         file text ,
         author text ,
         journal text ,
         fpage int ,
         lpage int ,
         title text ,
         abstract text ,
         notes text ,
         volume int ,
         pubyear int ,
         pmid int  ) ");

   }


   // copy data from 'old' pdfs table to the new one
   set_magic_quotes_runtime(1);
   $tablesfk=get_cell($db,"tableoftables","id","real_tablename","pdfs");
   $rcb=$db->Execute("SELECT * FROM pdfs");
   while (!$rcb->EOF && $rcb) {
      $newid=$db->Genid($newtable_realname."_id_seq");
      $row=$rcb->fields;
      $rcopy=$db->Execute("INSERT INTO $newtable_realname VALUES ('$newid','$row[access]','$row[ownerid]','$row[magic]','$row[lastmoddate]','$row[lastmodby]','$row[date]','$row[type2]','$row[file]','$row[author]','$row[type1]','$row[fpage]','$row[lpage]','$row[title]','$row[abstract]','$row[notes]','$row[volume]','$row[year]','$row[pmid]')");
      if (!$rcopy)
         $failed=true;
      else {
         $counter++;
         //change ownership of file to new table:
         $rfile=$db->Execute("UPDATE files SET tablesfk='$newtableid',ftableid='$newid',ftablecolumnid='$filecolumnid' WHERE tablesfk='$tablesfk' AND ftableid='$row[id]'");
      }
      $rcb->MoveNext();
   }
   echo "Inserted $counter records.<br>";
   // Now copy supporting table (type1=journal, type2=categories
   $rcj=$db->Execute("SELECT * FROM pd_type1 ORDER By id");
   while ($rcj && !$rcj->EOF) {
      $newid=$db->Genid($ass_tablej."_id_seq");
      $row=$rcj->fields;
      $rcj_copy=$db->Execute("INSERT INTO $ass_tablej VALUES ('$newid','$row[sortkey]','$row[type]','$row[typeshort]')");
      if (!$rcj_copy)
         $failed=true;
      // since the ids are not necessarily the same, we'll have to adjust the links
      // in the main table
      $db->Execute("UPDATE $newtable_realname SET journal='$newid' WHERE journal='$row[id]'");
      $rcj->MoveNext();
   }
   $rcc=$db->Execute("SELECT * FROM pd_type2 ORDER BY id");
   while ($rcc && !$rcc->EOF) {
      $newid=$db->Genid($ass_tablec."_id_seq");
      $row=$rcc->fields;
      $rcc_copy=$db->Execute("INSERT INTO $ass_tablec VALUES ('$newid','$row[sortkey]','$row[type]','$row[typeshort]')");
      if (!$rcc_copy)
         $failed=true;
      $db->Execute("UPDATE $newtable_realname SET category='$newid' WHERE category='$row[id]'");
      $rcc->MoveNext();
   }
   if ($failed)
      echo "Failed";
   else
      echo "Succes!";

}
?>
