<?php




$newtableid=$db->GenID("tableoftables_gen_id_seq");
$newtablename=pdbs;
$newtablelabel="pdbs ";
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
$r=$db->Execute("INSERT INTO tableoftables (id,sortkey,tablename,shortname,display,permission,custom,real_tablename,table_desc_name,label,plugin_code) VALUES ('$newtableid','0','$newtablename','$newtableshortname','Y','Users',NULL,'$newtable_realname','$newtable_desc_name','$newtablelabel','plugins/pdbs_plugin.php')");
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
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'140','title','title','Y','Y','N','text','text','','','','','','','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'160','Authors','author','Y','Y','N','text','text','','','','','','','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'180','Notes','notes','Y','Y','N','text','textlong','','','','','','','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'100','PDBID','pdbid','Y','Y','N','text','text','','','','','','http://www.rcsb.org/cgi/explre.cgi?pdbId=','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'220','File','file','Y','Y','N','text','file','','','','','','','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'200','Webmol','webmol','Y','Y','N','text','text','','','','','','webmol.php?pdbid=','','N')");
      // and finally create the table
      $rc=$db->Execute(" CREATE TABLE $newtable_realname (
         id int  NOT NULL,
         access varchar(9) ,
         ownerid int ,
         magic int ,
         lastmoddate int ,
         lastmodby int ,
         date int ,
         title text ,
         author text ,
         notes text ,
         pdbid text ,
         file text ,
         webmol text  ) ");

   }

   // copy data from 'old' pdbs table to the new one
   set_magic_quotes_runtime(1);
   $tablesfk=get_cell($db,"tableoftables","id","real_tablename","pdbs");
   $rcb=$db->Execute("SELECT * FROM pdbs");
   while (!$rcb->EOF && $rcb) {
      $newid=$db->Genid($newtable_realname."_id_seq");
      $row=$rcb->fields;
      $rcopy=$db->Execute("INSERT INTO $newtable_realname VALUES ('$newid','$row[access]','$row[ownerid]','$row[magic]','$row[lastmoddate]','$row[lastmodby]','$row[date]','$row[title]','$row[author]','$row[notes]','$row[pdbid]','$row[file]','$row[webmol')");
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

   if ($failed)
      echo "Failed copying contents of table protocols<br>";
   else {
      echo "Succes!<br>";
      // delete the old tables
      $rnt=$db->Execute("SELECT * FROM $newtable_realname");
      if ($rnt->Numrows==$rcb->Numrows) {
         $db->Execute("DROP TABLE pdbs");
         $db->Execute("DROP TABLE pdbs_id_seq");
         $db->Execute("DROP SEQUENCE pdbs_id_seq");
         $db->Execute("DELETE FROM tableoftables WHERE tablename='pdbs'");
      }
      else
         echo "Problems copying the content of table pdbs to the new table pdbs.<br>";
   }
}

?>
