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
         }
         $r->MoveNext();
      }
   }
   $r=$db->Execute("DROP TABLE $real_tablename");
   $r=$db->Execute("DROP TABLE $real_tablename"."_id");
   $r=$db->Execute("DROP SEQUENCE $real_tablename"."_id");
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
function add_table ($db,$tablename,$tablelabel,$sortkey) {
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
		access varchar(9), 
		ownerid int, 
		magic int, 
		lastmodby int, 
		lastmoddate int, 
		date int)");
      if ($r) {
         $string= "Succesfully Added Table $tablename";
         // check if shortname has been taken, if so, add id
         $r=$db->Execute("SELECT id FROM tableoftables WHERE shortname='$shortname'");
         if ($r->fields["id"])
            $shortname.="$id";
  	 $r=$db->Execute("INSERT INTO tableoftables (id,sortkey,tablename,label,real_tablename,shortname,display,permission,table_desc_name) Values($id,'$sortkey','$tablename','$tablelabel','$real_tablename','$shortname','Y','Users','$desc')");
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
		associated_sql text,
		associated_local_key text,
		thumb_x_size int,
		thumb_y_size int)");   

         $fieldstring="id,label,columnname,sortkey,display_table,display_record, required, type, datatype, associated_table, associated_sql"; 
         $descid=$db->GenId("$desc"."_id");  
  	 $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'id','id','100','N','N','N','int(11)','text','','')");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'access','access','110','N','N','N','varchar(9)','text','','')");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'ownerid','ownerid','120','N','N','N','int(11)','text','','')");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'magic','magic','130','N','N','N','int(11)','text','','')");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'title','title','140','Y','Y','Y','text','text','','')");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'lastmoddate','lastmoddate','150','N','N','N','int(11)','text','','')");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'lastmodby','lastmodby','160','N','N','N','int(11)','text','','')");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'date','date','170','N','N','N','int(11)','text','','')");
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
function mod_table($db,$id,$tablename,$tablesort,$tabledisplay,$label,$tablegroups) {
   global $string;

   $label=strtr($label,",'","  ");
   $r=$db->Execute("UPDATE tableoftables SET sortkey='$tablesort',display='$tabledisplay',label='$label' where id='$id'");   	
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
function add_columnecg($db,$tablename2,$colname2,$label,$datatype,$Rdis,$Tdis,$req,$sort)
   {
   global $string;

   $SQL_reserved="absolute,action,add,allocate,alter,are,assertion,at,between,bit,bit_length,both,cascade,cascaded,case,cast,catalog,char_length,charachter_length,cluster,coalsce,collate,collation,column,connect,connection,constraint,constraints,convert,corresponding,cross,current_date,current_time,current_timestamp,current_user,date,day,deallocate,deferrrable,deferred,describe,descriptor,diagnostics,disconnect,domain,drop,else,end-exec,except,exception,execute,external,extract,false,first,full,get,global,hour,identity,immediate,initially,inner,input,insensitive,intersect,interval,isolation,join,last,leading,left,level,local,lower,match,minute,month,names,national,natural,nchar,next,no,nullif,octet_length,only,outer,output,overlaps,pad,partial,position,prepare,preserve,prior,read,relative,restrict,revoke,right,rows,scroll,second,session,session_user,size,space,sqlstate,substring,system_user,temporary,then,time,timepstamp,timezone_hour,timezone_minute,trailing,transaction,translate,translation,trim,true,unknown,uppper,usage,using,value,varchar,varying,when,write,year,zone";

   // find the id of the table and therewith the tablename
   $r=$db->Execute("SELECT id FROM tableoftables WHERE tablename='$tablename2'"); 
   $id=$r->fields["id"];
   $search=array("' '","','","';'","'\"'");
   $replace=array("_","_","","");
   $colname = preg_replace ($search,$replace, $colname2);
   $real_tablename=get_cell($db,"tableoftables","real_tablename","id",$id);

   $fieldstring="id,columnname,label,sortkey,display_table,display_record,required,type,datatype,associated_table,associated_sql"; 
   $desc=$real_tablename . "_desc";
   $fieldid=$db->GenId($desc."_id");
   $label=strtr($label,",'","  ");
   // avoid having more than one column of type 'file'
   if ($datatype=="file") {
      $r=$db->Execute("SELECT id FROM $desc WHERE datatype='file'");
      if ($r->fields["id"])
         $filecolumnfound=true;
   }
   if ($colname=="")
      $string="Please enter a columnname";
   elseif ($label=="")
      $string="Please enter a Label";
   elseif (strpos($SQL_reserved,strtolower($colname))) 
      $string="Column name <i>$colname</i> is a reserved SQL word.  Please pick another column name";
   elseif ($filecolumnfound)
      $string="Only one column can be of Datatype <i>file</i>.";
   else {
      if ($datatype=="pulldown") {
         // create an associated table, not overwriting old ones, using a max number
         $ALLTABLES=$db->MetaTables();  
         $tablestr=$real_tablename;$tablestr.="ass";
	 $tables=array();
	 $tables=preg_grep("/$tablestr/",$ALLTABLES); 
	 $tables2=array_values($tables);
	 $numhave=array_count_values($tables2);
	 $allnums=array();	
	 array_push($allnums,"0");
	 foreach($tables2 as $currvalues)
			{
	    $DDD=explode("_",$currvalues);
	    $nownumber=$DDD[1];
	    array_push($allnums,$nownumber);
	 }		
	 $maxnum=max($allnums);$newnum=$maxnum+1;
	 $tablestr.="_$newnum";	
	 $r=$db->Execute("INSERT INTO $desc ($fieldstring) Values($fieldid,'$colname','$label','$sort','$Tdis','$Rdis','$req','text','$datatype','$tablestr','$colname from $tablestr where ')");
	 $rs=$db->Execute("CREATE TABLE $tablestr (id int PRIMARY KEY, sortkey int, type text, typeshort text)");
	 $rsss=$db->Execute("ALTER table $real_tablename add column $colname int");
	 if (($r)&&($rs)&&($rsss)&&(!($colname==""))) 
            $string="Added column <i>$colname</i> into table <i>$tablename2</i>";
	 else 
	    $string="Problems creating this column.";
      }
      else {
$db->debug=true;
         $r=$db->Execute("INSERT INTO $desc ($fieldstring) Values($fieldid,'$colname','$label','$sort','$Tdis','$Rdis','$req','text','$datatype','','')");
         $rsss=$db->Execute("ALTER table $real_tablename add column $colname text");
 	 if (($r)&&$rsss&&(!($colname==""))) 
            $string="Added column <i>$colname</i> into table: <i>$tablename2</i>";
         else 
            $string="Please enter all values";
      }
   }
}

/////////////////////////////////////////////////////////////////////////
//// 
// !modifies a general column entry
function mod_columnECG($db,$id,$sort,$tablename,$colname,$label,$datatype,$Rdis,$Tdis,$req) {
   global $string;

   // find the id of the table and therewith the tablename
   $r=$db->Execute("SELECT id FROM tableoftables WHERE tablename='$tablename'");
   $tableid=$r->fields["id"];
   // escape bad stuffin label
   $label=strtr($label,",'","  ");
   $tablename=$tablename."_".$tableid;
   $desc=$tablename."_desc";
   $r=$db->Execute("UPDATE $desc SET sortkey='$sort',display_table='$Tdis', display_record='$Rdis',required='$req',label='$label' where id='$id'");   	
   if ($r) 
      $string="Succesfully Changed Column $colname in $tablename";
   else 
      $string="Please enter all fields";
   return false;	
}

/////////////////////////////////////////////////////////////////////////
//// 
// !deletes a general column entry
function rm_columnecg($db,$tablename,$id,$colname,$datatype) {
   global $string,$USER;

   // find the id of the table and therewith the tablename
   $r=$db->Execute("SELECT id FROM tableoftables WHERE tablename='$tablename'");
   $tableid=$r->fields["id"];
   $real_tablename=$tablename."_".$tableid;
   $desc=$real_tablename."_desc";
   // if there are files associated, these have to be deleted as well
   $r=$db->Execute ("SELECT datatype FROM $desc WHERE id='$id'");
   if ($r->fields["datatype"]=="files") {
      $r=$db->Execute("SELECT id FROM files WHERE tablesfk='$tableid'");
      while (!$r->EOF)
         delete_file($db,$r->fields["id"],$USER);
   } 
   $r=$db->Execute("ALTER TABLE $real_tablename DROP COLUMN $colname");
   $rv=$db->Execute("select associated_table from $desc where id ='$id'");
   $tempTAB=array();
   if ($rv) {
      while (!$rv->EOF) {
         if ($rv->fields[0])
             $db->Execute("DROP TABLE ".$rv->fields[0]);
            $rv->MoveNext();
      }
   }
   $rrr=$db->Execute("DELETE FROM $desc WHERE id='$id'");
   if (($r)&&($rrr)) 
      $string="Deleted Column <i>$colname</i> from Table <i>$tablename</i>.";
}

?>
