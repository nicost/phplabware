<?php

// tablemanage_inc.php - support functions dealing with defining tables
// tablemanage_inc.php - author: Ethan Garner, Nico Stuurman <nicost@sf.net>
  /***************************************************************************
  * Copyright (c) 2001 by Ethan Garner, Nico Stuurman                        *
  * ------------------------------------------------------------------------ *
  *  Part of phplabware, a web-driven groupware suite for research labs      *
  *  This file contains classes and functions needed in tablemanage.php.     *
  *                                                                          *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/


/////////////////////////////////////////////////////////////////////
////  
// !Prints form with access to table management functions
function create_new_table($db){
   global $HTTP_POST_VARS,$PHP_SELF;
   echo "<form method='post' id='tablemanage' enctype='multipart/form-data' ";
   $dbstring=$PHP_SELF;
   echo "action='$dbstring".SID."'>\n"; 
   echo "<table align='center'>\n";
   echo "<caption><h3>Edit tables</h3></caption>\n";
   echo "<tr>\n";
   echo "<th>Name</th>";
   echo "<th>Display</th>";
   echo "<th>Sort key</th>\n";
   echo "<th>Custom</th>\n";
   echo "<th>Action</th>\n";
   echo "</tr>\n";
   echo "<tr><td><input type='text' name='table_name' value=''></td>\n";
   echo "<td><input type='text' name='table_display' value=''></td>\n";
   echo "<td><input type='text' name='table_sortkey' value=''></td>\n";
   echo "<td><input type='text' name='table_custom' value=''></td>\n";
   echo "<td align='center'><input type='submit' name='add_table' value='Add'></td></tr>\n";
 
   $query = "SELECT id,tablename,display,sortkey,custom FROM tableoftables ORDER BY sortkey";
   $r=$db->Execute($query);
   $rownr=0;
   // print all entries
   while (!($r->EOF) && $r) {
      // get results of each row
      $id = $r->fields["id"];
      $name = $r->fields["type"];
      $Display = $r->fields["display"];
      $sortkey = $r->fields["sortkey"];
      $Custom = $r->fields["custom"];
   
      // print start of row of selected group
      if ($rownr % 2)
         echo "<tr class='row_odd' align='center'>\n";
      else
         echo "<tr class='row_even' align='center'>\n";
      echo "<input type='hidden' name='type_id[]' value='$id'>\n";
      echo "<td><input type='text' name='table_name[]' value='$name'></td>\n";
      echo "<td><input type='text' name='table_display[]' value='$Display'></td>\n";
      echo "<td><input type='text' name='type_sortkey[]' value='$sortkey'></td>\n";
      echo "<td><input type='text' name='table_custom[]' value='$Custom'></td>\n";
      
      $modstring = "<input type='submit' name='tamod"."_$rownr' value='Modify'>";
      $delstring = "<input type='submit' name='tadel"."_$rownr' value='Remove' ";
      $delstring .= "Onclick=\"if(confirm('Are you sure the $name \'$type\' ";
      $delstring .= "should be removed?')){return true;}return false;\">";                                           
      echo "<td align='center'>$modstring $delstring</td>\n";
      echo "</tr>\n";
      $r->MoveNext();
      $rownr+=1;
   }

   // Dismiss button
   echo "<tr><td colspan=4 align='center'>\n";
   echo "<input type='submit' name='submit' value='Dismiss'>\n";
   echo "</td></tr>\n";

   echo "</table>\n";
   echo "</form>\n";

}

/////////////////////////////////////////////////////////////////////////
////  
// !deletes a user-generated table, including associated tables
function del_table($db,$tablename,$id,$USER) {
   global $HTTP_POST_VARS, $string;

   $real_tablename=get_cell($db,"tableoftables","real_tablename","id",$id);
   $desc=$real_tablename."_desc";
   // delete files owned by this table
   $r=$db->Execute("SELECT id FROM files WHERE tablesfk='$id'");
   while (!$r->EOF) {
      delete_file ($db,$r->fields["id"],$USER);
      $r->MoveNext();
   }   
   $r=$db->Execute("select associated_table from $desc");
   $tempTAB=array();
   if ($r) {
      while (!$r->EOF) {
         if ($r->fields["associated_table"]) {
            $db->Execute("DROP TABLE ".$r->fields["associated_table"]);
            $db->Execute("DROP TABLE ".$r->fields["associated_table"]."_id_seq");
            $db->Execute("DROP SEQUENCE ".$r->fields["associated_table"]."_id_seq");
         }
         $r->MoveNext();
      }
   }
   $r=$db->Execute("DROP TABLE $real_tablename");
   $r=$db->Execute("DROP TABLE $real_tablename"."ass");
   $r=$db->Execute("DROP SEQUENCE $real_tablename"."ass");
   $r=$db->Execute("DROP TABLE $real_tablename"."_id_seq");
   $r=$db->Execute("DROP SEQUENCE $real_tablename"."_id_seq");
   $r=$db->Execute("DROP TABLE $desc");
   $r=$db->Execute("DROP TABLE $desc"."_id");
   $r=$db->Execute("DROP SEQUENCE $desc"."_id");
   $r=$db->Execute("DELETE FROM groupxtabledisplay WHERE tableid=$id");
   $r=$db->Execute("Delete from tableoftables WHERE id=$id");
   if ($r) 
      $string="Table $tablename has been deleted";
   return $string;
}

/////////////////////////////////////////////////////////////////////////
////   
// !creates a general table 
// also adds the tabledescription table and the entry in tableoftables
function add_table ($db,$tablename,$tablelabel,$sortkey,$plugincode) {
    global $string;
    $shortname=substr($tablename,0,3);
   
   //check to ensure that duplicate table or database does not exist
   $r=$db->Execute("SELECT tablename FROM tableoftables");
   while ($r && !$r->EOF) {
      if ($tablename==$r->fields["tablename"])
         $isbad=true;
      $r->MoveNext();
   }
   if ($tablename=="")
      $string="Please enter a title for the table!";
   if ($isbad)
      $string="A table with the name $tablename already exists!";
   if (preg_match("/\W/",$tablename)) {
      $string="Please use only letters (no numbers, spaces and the like) in the tablename.";
      $isbad=true;
   }
   if (preg_match("/^[0-9]/",$tablename)) {
      $string="Tablenames should not start with a number. Sorry ;(";
      $isbad=true;
   }
   if (!$isbad && $tablename) {
      // ids > 10000 are available to users
      $id=$db->GenID("tableoftables"."_gen_id_seq",10000);
      $real_tablename=$tablename."_".$id;
      $desc=$real_tablename . "_desc";
      $r=$db->Execute("CREATE TABLE $real_tablename (
		id int PRIMARY KEY, 
		title text, 
                gr smallint,
                gw smallint,
                er smallint,
                ew smallint,
		ownerid int, 
		magic int, 
		lastmodby int, 
		lastmoddate int, 
		date int)");
      if ($r) {
         $string= "Succesfully Added Table $tablename";
         $db->Execute("CREATE INDEX $real_tablename"."_id_index ON $real_tablename (id)");
         $db->Execute("CREATE INDEX $real_tablename"."_title_index ON $real_tablename (title)");
         $db->Execute("CREATE INDEX $real_tablename"."_title_index ON $real_tablename (title(10))");
         $db->Execute("CREATE INDEX $real_tablename"."_gr_index ON $real_tablename (gr)");
         $db->Execute("CREATE INDEX $real_tablename"."_gw_index ON $real_tablename (gw)");
         $db->Execute("CREATE INDEX $real_tablename"."_er_index ON $real_tablename (er)");
         $db->Execute("CREATE INDEX $real_tablename"."_ew_index ON $real_tablename (ew)");
         $db->Execute("CREATE INDEX $real_tablename"."_ownerid_index ON $real_tablename (ownerid)");
         $db->Execute("CREATE INDEX $real_tablename"."_date_index ON $real_tablename (date)");
         // check if shortname has been taken, if so, add id
         $r=$db->Execute("SELECT id FROM tableoftables WHERE shortname='$shortname'");
         if ($r->fields["id"])
            $shortname.="$id";
         if ($plugincode) $plugincode="'".$plugincode."'";
         else $plugincode="NULL";
         $sortkey=(int)$sortkey;

  	 $r=$db->Execute("INSERT INTO tableoftables (id,sortkey,tablename,label,real_tablename,shortname,display,permission,table_desc_name,plugin_code) Values($id,'$sortkey','$tablename','$tablelabel','$real_tablename','$shortname','Y','Users','$desc',$plugincode)");
	 // let all groups see the table by default
	 $rg=$db->Execute("SELECT id FROM groups");
	 while ($rg && !$rg->EOF) {
	    $db->Execute("INSERT INTO groupxtable_display VALUES ('".$rg->fields["id"]."','$id')");
	    $rg->MoveNext();
	 }
         $label=strtr($label,",'","  ");
         $r=$db->Execute("CREATE TABLE $desc (
		id int PRIMARY KEY,
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
		modifiable char(1))");   

         $fieldstring="id,label,columnname,sortkey,display_table,display_record, required, type, datatype, associated_table, associated_column"; 
         $descid=$db->GenId("$desc"."_id");  
  	 $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'id','id','100','N','N','N','int(11)','text',NULL,NULL)");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'gr','group read','111','N','N','N','smallint','int',NULL,NULL)");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'gw','group write','112','N','N','N','smallint','int',NULL,NULL)");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'er','everyone read','113','N','N','N','smallint','int',NULL,NULL)");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'ew','everyone write','114','N','N','N','smallint','int',NULL,NULL)");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'owner','ownerid','120','N','N','N','int(11)','user',NULL,NULL)");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'magic','magic','130','N','N','N','int(11)','text',NULL,NULL)");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring,modifiable) Values($descid,'title','title','140','Y','Y','Y','text','text',NULL,NULL,'Y')");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'lastmoddate','lastmoddate','150','N','N','N','int(11)','date',NULL,NULL)");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'lastmodby','lastmodby','160','N','N','N','int(11)','user',NULL,NULL)");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'date','date','170','N','N','N','int(11)','date',NULL,NULL)");
      }  
      else {
         $string="Poblems adding this table.  Sorry ;(";
      }
   return false;
   }
}

/////////////////////////////////////////////////////////////////////////
////
// !modifies  the display properites of a table within navbar
function mod_table($db,$id,$offset) {
   global $HTTP_POST_VARS,$string;

   // prepare variable to feed into SQL statement
   if ($HTTP_POST_VARS["table_name"][$offset])
      $tablename="'".$HTTP_POST_VARS["table_name"][$offset]."'";
   else
      $tablename="NULL";
   if ($HTTP_POST_VARS["table_label"][$offset])
      $label="'".strtr($HTTP_POST_VARS["table_label"][$offset],",'","  ")."'";
   else
      $label="NULL";
   $tablesort=(int) $HTTP_POST_VARS["table_sortkey"][$offset];
   $tabledisplay= $HTTP_POST_VARS["table_display"][$offset];
   $tablegroups= $HTTP_POST_VARS["tablexgroups"][$id];
   if ($HTTP_POST_VARS["table_plugincode"][$offset])
      $plugincode="'".$HTTP_POST_VARS["table_plugincode"][$offset]."'";
   else
      $plugincode="NULL";

   // do the SQL update
   $r=$db->Execute("UPDATE tableoftables SET sortkey='$tablesort',display='$tabledisplay',label=$label,plugin_code=$plugincode where id='$id'");   	
   if ($r) {
      // Set permissions for groups to see these tables
      $db->Execute("DELETE FROM groupxtable_display WHERE tableid='$id'");
      if ($tablegroups) {
         foreach ($tablegroups AS $groupid)
            if ($groupid)
	       $db->Execute("INSERT into groupxtable_display VALUES ('$groupid','$id')");
      }
      $string="Succesfully Changed Record $tablename";
   }
   else 
      $string="Please enter all fields";
   return false;
}

/////////////////////////////////////////////////////////////////////////
//// 
// !adds a general column entry
function add_columnecg($db,$tablename2,$colname2,$label,$datatype,$Rdis,$Tdis,$req,$modifiable,$sort) {
   global $string;

   $SQL_reserved=",absolute,action,add,allocate,alter,are,assertion,at,between,bit,bit_length,both,cascade,cascaded,case,cast,catalog,char_length,charachter_length,cluster,coalsce,collate,collation,column,connect,connection,constraint,constraints,convert,corresponding,cross,current_date,current_time,current_timestamp,current_user,date,day,deallocate,deferrrable,deferred,describe,descriptor,diagnostics,disconnect,domain,drop,else,end-exec,except,exception,execute,external,extract,false,first,full,get,global,hour,identity,immediate,initially,inner,input,insensitive,intersect,interval,isolation,join,last,leading,left,level,local,lower,match,minute,month,names,national,natural,nchar,next,no,nullif,octet_length,only,outer,output,overlaps,pad,partial,position,prepare,preserve,prior,read,relative,restrict,revoke,right,rows,scroll,second,session,session_user,size,space,sqlstate,substring,system_user,temporary,then,time,timepstamp,timezone_hour,timezone_minute,trailing,transaction,translate,translation,trim,true,unknown,uppper,usage,using,value,varchar,varying,when,write,year,zone,";

   // find the id of the table and therewith the tablename
   $r=$db->Execute("SELECT id,real_tablename,table_desc_name,label,shortname FROM tableoftables WHERE tablename='$tablename2'"); 
   $id=$r->fields['id'];
   $real_tablename=$r->fields['real_tablename'];
   $desc=$r->fields['table_desc_name'];
   $tablelabel=$r->fields['label'];
   $shortname=$r->fields['label'];
   $search=array("' '","','","';'","'\"'","'_'","'-'");
   $replace=array('');
   $colname=preg_replace ($search,$replace, $colname2);
   if (!$sort)
      $sort='0';

   // for adodb's dml
   $dict=NewDataDictionary($db);
   $taboptArray=array('mysql'=>'TYPE=ISAM');

   $fieldstring="id,columnname,label,sortkey,display_table,display_record,required,modifiable,type,datatype,associated_table,associated_column,key_table"; 
   $fieldid=$db->GenId($desc."_id");
   $label=strtr($label,",'","  ");
   $colname=strtolower($colname);

   // check whether this name exists, the query should fail
   $rb=$db->Execute("SELECT $colname FROM $real_tablename");
   if ($rb)
      $string=('This columnname is in use.  Please choose something else.');
   elseif ($colname=="")
      $string='Please enter a columnname';
   elseif ($label=='')
      $string='Please enter a Label';
   elseif (strpos($SQL_reserved,",$colname,")) 
      $string="Column name <i>$colname</i> is a reserved SQL word.  Please pick another column name";
   else {
      if ($datatype=='pulldown' || $datatype=='mpulldown') {
         // create an associated table, not overwriting old ones, using a max number
         $tablestr=$real_tablename;
	 $tablestr.='ass';
         // simple and robust way to get UID.  Start at 20 to avoid clashes	
         $assid=$db->GenID($tablestr,20);
	 $tablestr.="_$assid";	
         if ($datatype=='mpulldown') {
            $keystr=$real_tablename;
            $keystr.='ask';
            $keystr.="_$assid";	
         }

	 $r=$db->Execute("INSERT INTO $desc ($fieldstring) Values($fieldid,'$colname','$label','$sort','$Tdis','$Rdis','$req','$modifiable','int','$datatype','$tablestr','','$keystr')");
	 $rs=$db->Execute("CREATE TABLE $tablestr (id int PRIMARY KEY, sortkey int, type text, typeshort text)");
   
         if ($datatype=='mpulldown') {
            $nflds="
               recordid I8 CONSTRAINTS 'FOREIGN KEY REFERENCES $real_tablename (id)',
               typeid I8 CONSTRAINTS 'FOREIGN KEY REFERENCES $tablestr(id)'
            ";
            $sqlArray=$dict->CreateTableSQL($keystr,$nflds,$taboptArray);
            $dict->ExecuteSQLArray($sqlArray);
             //$rss=$db->Execute("CREATE TABLE $keystr (recordid int, typeid int)");
            // create indexes
            $sqlArray=$dict->CreateIndexSQL($shortname."_ask_$assid".'_rid_index',$keystr,'recordid');
            $dict->ExecuteSQLArray($sqlArray);
            $sqlArray=$dict->CreateIndexSQL($shortname."_ask_$assid".'_tid_index',$keystr,'typeid');
            $dict->ExecuteSQLArray($sqlArray);
         }

         $rsss=$db->Execute("ALTER table $real_tablename add column $colname int");
	 if ($r && $rs && $rsss && (!($colname==""))) 
            $string="Added column <i>$colname</i> into table <i>$tablelabel</i>";
	 else 
	    $string="Problems creating this column.";
      }
      elseif ($datatype=="file") {
         // this table links words found in files to specific records
         $tablestr=$real_tablename."_wi"."_$fieldid";
         $rs=$db->Execute("CREATE TABLE $tablestr (wordid int, fileid int, pagenr int, recordid int,UNIQUE (wordid,fileid,pagenr,recordid))");
         $db->Execute("CREATE INDEX $tablestr"."_wi ON $tablestr (wordid)");
         $db->Execute("CREATE INDEX $tablestr"."_fi ON $tablestr (fileid)");
         $db->Execute("CREATE INDEX $tablestr"."_ri ON $tablestr (recordid)");

	 $r=$db->Execute("INSERT INTO $desc ($fieldstring) Values($fieldid,'$colname','$label','$sort','$Tdis','$Rdis','$req','$modifiable','int','$datatype','$tablestr',NULL,NULL)");
         // we do not need this column, but not having it  might break something
	 $rsss=$db->Execute("ALTER table $real_tablename add column $colname text");
	 if (($r)&&($rs)&&(!($colname==""))) 
            $string="Added column <i>$colname</i> into table <i>$tablelabel</i>";
	 else 
	    $string='Problems creating this column.';
      }
      else {
         if ($datatype=='int' || $datatype=='sequence' || $datatype=='date' || $datatype=='table')
            $sqltype='int';
         elseif ($datatype=='float')
            $sqltype='float';
         else
             $sqltype='text';
         $rsss=$db->Execute("ALTER table $real_tablename add column $colname $sqltype");
         if ($rsss)
            $r=$db->Execute("INSERT INTO $desc ($fieldstring) Values($fieldid,'$colname','$label','$sort','$Tdis','$Rdis','$req','$modifiable','$sqltype','$datatype','','',NULL)");
 	 if (($r)&&$rsss&&(!($colname==""))) {
            $string="Added column <i>$colname</i> into table: <i>$tablelabel</i>";
            return $fieldid;
         }
         else { 
            $string='Please enter all values';
            return false;
         }
      }
   }
}

/////////////////////////////////////////////////////////////////////////
//// 
// !modifies a general column entry
function mod_columnECG($db,$sort,$offset) {
   global $string,$HTTP_POST_VARS;

   $id=$HTTP_POST_VARS["column_id"][$offset]; 
   $colname=$HTTP_POST_VARS["column_name"][$offset];
   $label=$HTTP_POST_VARS["column_label"][$offset];
   $datatype=$HTTP_POST_VARS["column_datatype"][$offset];
   $thumbsize=$HTTP_POST_VARS["thumbsize"."_$offset"];
   if (!$thumbsize)
      $thumbsize="NULL";
   $Rdis=$HTTP_POST_VARS["column_drecord"][$offset];
   $Tdis=$HTTP_POST_VARS["column_dtable"][$offset];
   $sort=$HTTP_POST_VARS["column_sort"][$offset];
   $req=$HTTP_POST_VARS["column_required"][$offset];
   $modifiable=$HTTP_POST_VARS["column_modifiable"][$offset];

   // find the id of the table and therewith the tablename
   $tablename=$HTTP_POST_VARS["table_name"];
   $r=$db->Execute("SELECT id FROM tableoftables WHERE tablename='$tablename'");
   $tableid=$r->fields["id"];
   $real_tablename=get_cell($db,"tableoftables","real_tablename","id",$tableid);
   $desc=$real_tablename."_desc";

   // escape bad stuffin label
   $label=strtr($label,",'","  ");
   $r=$db->Execute("UPDATE $desc SET sortkey='$sort',display_table='$Tdis', display_record='$Rdis',required='$req',label='$label',modifiable='$modifiable',thumb_x_size=$thumbsize where id='$id'");   	
   if ($r) { 
      $string="Succesfully Changed Column $colname in $tablename";
      return true;
   }
   else 
      $string="Failed to modify column $colname.";
   return false;	
}

/////////////////////////////////////////////////////////////////////////
////
// !Modifies an entry for a report
function mod_report($db,$offset) {
   global $HTTP_POST_VARS,$HTTP_GET_VARS,$HTTP_POST_FILES,$system_settings;

   $id=$HTTP_POST_VARS["report_id"][$offset];
   $label=$HTTP_POST_VARS["report_label"][$offset];
   $sortkey=$HTTP_POST_VARS["report_sortkey"][$offset];
   $sortkey=(int)$sortkey;
   if (!$sortkey)
      $sortkey="NULL";
   $templatedir=$system_settings["templatedir"];
   if (isset($HTTP_POST_FILES["report_template"][$offset][0]) && !$templatedir) {
      echo "<h3 align='center'>Templatedir is not set.  Please correct this first.</h3>";
      exit;
   }
   // Upload file, if any
   $fileuploaded=move_uploaded_file($HTTP_POST_FILES["report_template"]["tmp_name"][$offset],"$templatedir/$id.tpl");
   if ($fileuploaded) {
      $filesize=$HTTP_POST_FILES["report_template"]["size"][$offset];
      if (!$filesize)
         $filesize="NULL";
   }
   // Write changes to database
   if ($filesize)
      $r=$db->Execute("UPDATE reports SET label='$label', sortkey=$sortkey, filesize=$filesize WHERE id='$id'");    
   else
      $r=$db->Execute("UPDATE reports SET label='$label', sortkey=$sortkey WHERE id='$id'");    
}

/////////////////////////////////////////////////////////////////////////
////
// !Deletes an entry for a report
function rm_report($db,$offset) {
   global $HTTP_POST_VARS,$system_settings;

   $id=$HTTP_POST_VARS["report_id"][$offset];
   $r=$db->Execute("DELETE FROM reports WHERE id=$id");
   @unlink ($system_settings["templatedir"]."/$id.tpl");
}

/////////////////////////////////////////////////////////////////////////
////
// !Deletes an entry for a report
function test_report($db,$offset,$tablename) {
   global $HTTP_POST_VARS,$HTTP_GET_VARS,$system_settings;
   $HTTP_GET_VARS["tablename"]=$tablename;

   $tableinfo=new tableinfo($db);
   $real_tablename=get_cell($db,"tableoftables","real_tablename","tablename",$tablename);
   $reportid=$HTTP_POST_VARS["report_id"][$offset];
   $r=$db->Execute("SELECT * FROM $real_tablename");

   $fields=comma_array_SQL($db,$tableinfo->desname,"columnname");
   $Allfields=getvalues($db,$tableinfo,$fields,"id",$r->fields["id"]);

   $tp=@fopen($system_settings["templatedir"]."/$reportid.tpl","r");
   if ($tp) {
      while (!feof($tp))
         $template.=fgets($tp,64000);
      fclose($tp);
   }
   require("includes/report_inc.php");
   $report=make_report($db,$template,$Allfields,$tableinfo,1);
   return $report;
   
}
/////////////////////////////////////////////////////////////////////////
////
// ! Streams a template back to the user
function export_report($db,$offset) {
   global $HTTP_POST_VARS,$system_settings;

   $templatedir=$system_settings["templatedir"];
   $id=$HTTP_POST_VARS["report_id"][$offset];
   if (is_readable("$templatedir/$id.tpl")) {
      header("Accept-Ranges: bytes");
      header("Connection: close");
      header("Content-Type: text/txt");
      //   header("Content-Length: $filesize");
      header("Content-Disposition-type: attachment");
      header("Content-Disposition: attachment; filename=$filename");
      readfile("$templatedir/$id.tpl");   
   }
}

/////////////////////////////////////////////////////////////////////////
////
// ! Adds a new entry for a report
function add_report($db) {
   global $HTTP_POST_VARS,$HTTP_POST_FILES,$HTTP_GET_VARS,$system_settings;

   $id=$db->GenID("reports"."_gen_id_seq");
   $tablename=$HTTP_GET_VARS["editreport"];
   $r=$db->Execute("SELECT id FROM tableoftables WHERE tablename='$tablename'");
   $tableid=$r->fields["id"];
   $label=$HTTP_POST_VARS["addrep_label"];
   $templatedir=$system_settings["templatedir"];
   $sortkey=$HTTP_POST_VARS["addrep_sortkey"];
   $sortkey=(int)$sortkey;
   if (!$sortkey)
      $sortkey="NULL";

   // checks on input
   if (!$label) {
      return "<h3 align='center'>Please provide a template name!</h3>\n";
   }
   $fileuploaded=move_uploaded_file($HTTP_POST_FILES["addrep_template"]["tmp_name"],"$templatedir/$id.tpl");
   if ($fileuploaded) 
      $filesize=$HTTP_POST_FILES["addrep_template"]["size"];
   if (!$filesize)
      $filesize="NULL";
   $db->Execute("INSERT INTO reports (id,label,tableid,sortkey,filesize) VALUES ($id,'$label',$tableid,$sortkey,$filesize)");

}


/////////////////////////////////////////////////////////////////////////
//// 
// !deletes a general column entry
function rm_columnecg($db,$tablename,$id,$colname,$datatype) {
   global $string,$USER;

   // find the id of the table and therewith the tablename
   $r=$db->Execute("SELECT id FROM tableoftables WHERE tablename='$tablename'");
   $tableid=$r->fields["id"];
   $real_tablename=get_cell($db,"tableoftables","real_tablename","id",$tableid);
   $tablelabel=get_cell($db,"tableoftables","label","id",$tableid);
   $desc=get_cell($db,"tableoftables","table_desc_name","id",$tableid);
   // if there are files associated, these have to be deleted as well
   $r=$db->Execute ("SELECT datatype FROM $desc WHERE id='$id'");
   if ($r->fields["datatype"]=="file") {
      $r=$db->Execute("SELECT id FROM files WHERE tablesfk='$tableid'");
      while (!$r->EOF)
         delete_file($db,$r->fields["id"],$USER);
   } 
   if ($r->fields["datatype"]=="pulldown" || $r->fields["datatype"=="file"]) {
      $rv=$db->Execute("select associated_table from $desc where id ='$id'");
      // $tempTAB=array();
      if ($rv) {
         while (!$rv->EOF) {
            if ($rv->fields[0])
                $db->Execute("DROP TABLE ".$rv->fields[0]);
               $rv->MoveNext();
         }
      }
   }
   $r=$db->Execute("ALTER TABLE $real_tablename DROP COLUMN $colname");
   $rrr=$db->Execute("DELETE FROM $desc WHERE id='$id'");
   // Postgres does know how to drop a column, so only check the second query
   if ($rrr) 
      $string="Deleted Column <i>$colname</i> from Table <i>$tablelabel</i>.";
}

////
// !helper function for show_table_column_page
function make_column_js_array($db,$r) {
   $result="new Array(\n";
   $rb=$db->Execute("SELECT label,id FROM ".$r->fields["table_desc_name"]." WHERE label NOT IN ('id','access','date','ownerid','magic','lastmoddate','lastmodby')");
   $result.="new Array(\"".$rb->fields["label"]."\", ".$rb->fields["id"].")"; 
   $rb->MoveNext();
   while (!$rb->EOF) {
      $result.=",\nnew Array(\"".$rb->fields["label"]."\", ".$rb->fields["id"].")"; 
      $rb->MoveNext();
   }
   $result.=")";
   return $result;
}


////
// ! show active link page 
function show_active_link_page ($db,$table_name,$addcol_name,$addcol_label,$link_part_a=false,$link_part_b=false) {
   echo "<form method='post' id='active_link'>\n";
   echo "<input type='hidden' name='table_name' value='$table_name'></input>\n";
   echo "<input type='hidden' name='addcol_name' value='$addcol_name'></input>\n";
   echo "<input type='hidden' name='addcol_label' value='$addcol_label'></input>\n";
   echo "<table align='center' cellpadding='2' cellspacing='0'>\n";
   echo "<tr><td>Enter the link (including http://) here. \"Cell content\" will be extracted from the database</td></tr>\n";
   echo "<tr><td><input type='text' name='link_part_a' value='$link_part_a'>\n";
   echo "cell content<input type='text'name='link_part_b' value='$link_part_b'></td></tr>\n";

   echo "<tr><td align='center'><input type='submit' name='submit' value='Submit'></input></td>\n";
   echo "</tr>\n</table>\n</form>\n";
}


////
// !Stores active link data
function add_active_link ($db,$table,$column,$link_a,$link_b) {
   $r=$db->Execute("SELECT table_desc_name FROM tableoftables WHERE tablename='$table'");
   $table_desc=$r->fields["table_desc_name"];
   if ($r) {
      $r=$db->Execute("UPDATE $table_desc SET link_first='$link_a',link_last='$link_b' WHERE columnname='$column'");
   }
}


////
// ! show page with choice of tables, dynamically generate list with columns
function show_table_column_page ($db,$table_name,$addcol_name,$addcol_label) {
   global $HTTP_GET_VARS;

   echo "<form method='post' id='table_type'>\n";
   echo "<input type='hidden' name='table_name' value='$table_name'></input>\n";
   echo "<input type='hidden' name='addcol_name' value='$addcol_name'></input>\n";
   echo "<input type='hidden' name='addcol_label' value='$addcol_label'></input>\n";
   // box 1 with tablenames
   $r=$db->Execute("SELECT tablename,id,table_desc_name FROM tableoftables WHERE permission='Users' AND tablename <> 'settings' AND tablename <> '$table_name'  AND table_desc_name IS NOT NULL ORDER BY sortkey");
   // box 2, dynamically filled with column names
   $the_array="modelinfo = new Array (\n";
   $the_array.=make_column_js_array($db,$r);
   $r->MoveNext();
   while (!$r->EOF) {
      $the_array.=",\n ".make_column_js_array($db,$r);
      $r->MoveNext();
   }
   $the_array.="\n)\n";
   echo add_js ($the_array);
   $jscript="onChange=\"fillSelectFromArray(this.form.table_column_select,((this.selectedIndex == -1) ? null : modelinfo[this.selectedIndex-1]));\"";

   echo "<h3 align='center'>Choose Table and column to be associated with column <i>$addcol_label</i> in table <i>$table_name</i>.</h3>\n";
   echo "<table align='center' cellpadding='2' cellspacing='0'>\n";
   echo "<tr><th>Table</th>\n<th>Column</th><th>&nbsp;</th></tr>\n";
   $r->MoveFirst();
   echo "<tr><td>".$r->GetMenu2("table_select","",true,false,0,$jscript)."</td>\n";
   echo "<td><select name='table_column_select'></select></td>\n";
   echo "</tr>\n";
   $HTTP_GET_VARS['tablename']=$table_name;
   $tableinfo=new tableinfo($db);
   $rs=$db->Execute("SELECT id,associated_table,associated_column,associated_local_key,label FROM {$tableinfo->desname} WHERE datatype='table'");
   if ($rs && !$rs->EOF) {
      echo "<tr><td colspan=3>Grouping:</td></tr>\n";
      echo "<tr><td colspan=3><input type='radio' name='ass_to'> Make this a primary key</input></td></tr>\n";
      while (!$rs->EOF) {
         if ($rs->fields['associated_table'] && !$rs->fields['associated_local_key']) {
            $ass_tableinfo=new tableinfo($db,false,$rs->fields['associated_table']);
            $ass_column=get_cell($db,$ass_tableinfo->desname,'label','id',$rs->fields['associated_column']);
            echo "<tr><td colspan=3><input type='radio' name='ass_to' value='{$rs->fields[0]}'> Associate with: Local column: <i>{$rs->fields['label']}</i> (Foreign table: <i>{$ass_tableinfo->name}</i>, column: <i>$ass_column</i>),</input></td></tr>\n";
         }
         $rs->MoveNext();
      }
   }
  
   echo "<tr><td colspan=3 align='center'><input type='submit' name='submit' value='Submit'></input></td></tr>\n";
   echo "</table>\n</form>\n";
}

////
// !Associates given column with a column in another table
//  if there is already an association with the other table, that association
//  will be used as a key
function add_associated_table($db,$table,$column,$table_ass,$column_ass) {
   global $HTTP_POST_VARS;

   $r=$db->Execute("SELECT table_desc_name FROM tableoftables WHERE tablename='$table'");
   $table_desc=$r->fields["table_desc_name"];
   $r=$db->Execute("UPDATE $table_desc SET associated_table='$table_ass', associated_column='$column_ass' WHERE columnname='$column'");
   $ass_to=(int)$HTTP_POST_VARS['ass_to'];
   if ($ass_to) {
      $r=$db->Execute("UPDATE $table_desc SET associated_local_key='$ass_to' WHERE columnname='$column'");
   }
}

?>
