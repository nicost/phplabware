<?php


// updates table antibodies and removes settings from tableoftables

$newtableid=$db->GenID("tableoftables_gen_id_seq");
$newtablename=ab;
$newtablelabel="antibodies";
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
$r=$db->Execute("INSERT INTO tableoftables (id,sortkey,tablename,shortname,display,permission,custom,real_tablename,table_desc_name,label,plugin_code) VALUES ('$newtableid','0','$newtablename','$newtableshortname','Y','Users',NULL,'$newtable_realname','$newtable_desc_name','$newtablelabel','plugins/antibodies_plugin.php')");
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
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'140','Name','title','Y','Y','Y','text','text','','','','','','','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'160','Antigen','antigen','Y','Y','N','text','text','','','','','','','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'180','Notes','notes','Y','Y','N','text','textlong','','','','','','','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'200','Prim/Sec','type1','Y','Y','N','int','pulldown','antibodiesnew_10061ass_20','','','','','','','Y')");
      $ass_table_t1=$newtable_realname."ass";
      $id_ass=$db->GenId($ass_table_t1,20);
      $ass_table_t1.="_$id_ass";
      $db->Execute("CREATE TABLE $ass_table_t1 (
         id int PRIMARY KEY,
         sortkey int,
         type text,
         typeshort text)");
      $db->Execute("UPDATE $newtable_desc_name SET associated_table='$ass_table_t1' WHERE id=$newid");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'220','Label','type5','Y','Y','N','int','pulldown','antibodiesnew_10061ass_21','','','','','','','Y')");
      $ass_table_t5=$newtable_realname."ass";
      $id_ass=$db->GenId($ass_table_t5,20);
      $ass_table_t5.="_$id_ass";
      $db->Execute("CREATE TABLE $ass_table_t5 (
         id int PRIMARY KEY,
         sortkey int,
         type text,
         typeshort text)");
      $db->Execute("UPDATE $newtable_desc_name SET associated_table='$ass_table_t5' WHERE id=$newid");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'240','Mono-/Polyclonal','type2','Y','Y','N','int','pulldown','antibodiesnew_10061ass_22','','','','','','','Y')");
      $ass_table_t2=$newtable_realname."ass";
      $id_ass=$db->GenId($ass_table_t2,20);
      $ass_table_t2.="_$id_ass";
      $db->Execute("CREATE TABLE $ass_table_t2 (
         id int PRIMARY KEY,
         sortkey int,
         type text,
         typeshort text)");
      $db->Execute("UPDATE $newtable_desc_name SET associated_table='$ass_table_t2' WHERE id=$newid");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'260','Host','type3','Y','Y','N','int','pulldown','antibodiesnew_10061ass_23','','','','','','','Y')");
      $ass_table_t3=$newtable_realname."ass";
      $id_ass=$db->GenId($ass_table_t3,20);
      $ass_table_t3.="_$id_ass";
      $db->Execute("CREATE TABLE $ass_table_t3 (
         id int PRIMARY KEY,
         sortkey int,
         type text,
         typeshort text)");
      $db->Execute("UPDATE $newtable_desc_name SET associated_table='$ass_table_t3' WHERE id=$newid");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'280','Class','type4','Y','Y','N','int','pulldown','antibodiesnew_10061ass_24','','','','','','','Y')");
      $ass_table_t4=$newtable_realname."ass";
      $id_ass=$db->GenId($ass_table_t4,20);
      $ass_table_t4.="_$id_ass";
      $db->Execute("CREATE TABLE $ass_table_t4 (
         id int PRIMARY KEY,
         sortkey int,
         type text,
         typeshort text)");
      $db->Execute("UPDATE $newtable_desc_name SET associated_table='$ass_table_t4' WHERE id=$newid");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'300','Location','location','Y','Y','N','text','text','','','','','','','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $filecolumnid=$newid;
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'300','Files','files','Y','Y','N','text','file','','','','','','','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'340','Buffer','buffer','N','Y','N','text','text','','','','','','','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'360','Concentration (mg/ml)','concentration','Y','Y','N','float','float','','','','','','','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'380','Source','source','N','Y','N','text','text','','','','','','','','Y')");
      $newid=$db->GenID("newtable_desc_name"."_id");
      $db->Execute("INSERT INTO $newtable_desc_name VALUES($newid,'170','Epitope','epitope','N','Y','N','text','text','','','','','','','','Y')");
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
         antigen text ,
         notes text ,
         type1 int ,
         type5 int ,
         type2 int ,
         type3 int ,
         type4 int ,
         location text ,
         files text ,
         buffer text ,
         concentration float ,
         source text ,
         epitope text  ) ");

   }  


   // copy data from 'old' protocol table to the new one
   unset($counter);
   set_magic_quotes_runtime(1);
   $tablesfk=get_cell($db,"tableoftables","id","real_tablename","antibodies");
   $rcb=$db->Execute("SELECT * FROM antibodies");
   while (!$rcb->EOF && $rcb) {
      $newid=$db->Genid($newtable_realname."_id_seq");
      $row=$rcb->fields;
      $rcopy=$db->Execute("INSERT INTO $newtable_realname VALUES ('$newid','$row[access]','$row[ownerid]','$row[magic]','$row[lastmoddate]','$row[lastmodby]','$row[date]','$row[name]','$row[antigen]','$row[notes]','$row[type1]','$row[type5]','$row[type2]','$row[type3]','$row[type4]','$row[location]','$row[files]','$row[buffer]','$row[concentration]','$row[source]','$row[epitope]')");
      if (!$rcopy)
         $failed=true;
      else {
         $counter++;
         //change ownership of file to new table:
         $rfile=$db->Execute("UPDATE files SET tablesfk='$newtableid',ftableid='$newid',ftablecolumnid='$filecolumnid' WHERE tablesfk='$tablesfk' AND ftableid='$row[id]'");
      }
      $rcb->MoveNext();
   }
   if ($counter)
      echo "(antibodies:) Inserted $counter records.<br>";
   // Now copy supporting table (type1=author, type2=categories
   for ($i=1;$i<=5;$i++) {
      $tablestring="ass_table_t".$i;
      $ass_table=${$tablestring};
      $rcj=$db->Execute("SELECT * FROM ab_type$i ORDER BY id");
      while ($rcj && !$rcj->EOF) {
         $newid=$db->Genid($ass_table."_id_seq");
         $row=$rcj->fields;
         $rcj_copy=$db->Execute("INSERT INTO $ass_table VALUES ('$newid','$row[sortkey]','$row[type]','$row[type]')");
         if (!$rcj_copy)
            $failed=true;
         // since the ids are not necessarily the same, we'll have to adjust the links
         // in the main table
         $db->Execute("UPDATE $newtable_realname SET type$i='$newid' WHERE type$i='$row[id]'");
         $rcj->MoveNext();
      }
   }
   if ($failed)
      echo "Failed copying contents of table protocols.<br>";
   else {
      echo "Succes!<br>";
      // delete the old tables
      $rnt=$db->Execute("SELECT * FROM $newtable_realname");
      if ($rnt->Numrows==$rcb->Numrows) {
         $db->Execute("DROP TABLE antibodies");
         $db->Execute("DROP TABLE antibodies_id_seq");
         $db->Execute("DROP SEQUENCE antibodies_id_seq");
         $db->Execute("DROP TABLE ab_type1"); 
         $db->Execute("DROP TABLE ab_type1_id_seq"); 
         $db->Execute("DROP SEQUENCE ab_type1_id_seq"); 
         $db->Execute("DROP TABLE ab_type2"); 
         $db->Execute("DROP TABLE ab_type2_id_seq"); 
         $db->Execute("DROP SEQUENCE ab_type2_id_seq"); 
         $db->Execute("DROP TABLE ab_type3"); 
         $db->Execute("DROP TABLE ab_type3_id_seq"); 
         $db->Execute("DROP SEQUENCE ab_type3_id_seq"); 
         $db->Execute("DROP TABLE ab_type4"); 
         $db->Execute("DROP TABLE ab_type4_id_seq"); 
         $db->Execute("DROP SEQUENCE ab_type4_id_seq"); 
         $db->Execute("DROP TABLE ab_type5"); 
         $db->Execute("DROP TABLE ab_type5_id_seq"); 
         $db->Execute("DROP SEQUENCE ab_type5_id_seq"); 
         $db->Execute("DELETE FROM tableoftables WHERE tablename='antibodies'");
         $db->Execute("UPDATE tableoftables SET label='antibodies' WHERE id='$newtableid'");
      }
      else
         echo "Problems copying the content of table protocols to the new table protocols.<br>";
   }
}

// settings does not appear any more along with other tables in navbar:
$db->Execute("DELETE FROM tableoftables WHERE tablename='settings'");
?>
