<?php

///////////////////////////////////////////////////////////////////////
////
//  !display functions for general types

///////////////////////////////////////////////////////////
//// 
// !get the user who made the entry
function user_entry($id,$DBNAME) {
   global $db;
   $ownerid=get_cell($db,"$DBNAME","ownerid","id","$id");
   $r=$db->Execute("SELECT firstname,lastname,email FROM users WHERE id=$ownerid");
   if ($r->fields["email"])  {
      echo "<tr><th>Submitted by: </th><td><a href='mailto:".$r->fields["email"]."'>";
      echo $r->fields["firstname"]." ".$r->fields["lastname"]."</a></td>\n";
   }
   else {
      echo "<tr><th>Submitted by: </th><td>".$r->fields["firstname"]." ";
      echo $r->fields["lastname"] ."</td>\n";
   }
   echo "<td>&nbsp;</td>";
}

///////////////////////////////////////////////////////////
//// 
// !Prints name and date
// Needs to be called within a table
function date_entry($id,$DBNAME) {
   global $db,$system_settings;

   $date=get_cell($db,$DBNAME,"date","id","$id");
   $dateformat=get_cell($db,"dateformats","dateformat","id",$system_settings["dateformat"]);
   $date=date($dateformat,$date);
   echo "<th>Date entered: </th><td colspan=3>$date</td></tr>\n";
   if ($lastmodby && $lastmoddate)  {
      echo "<tr>";
      $r=$db->Execute("SELECT firstname,lastname,email FROM users WHERE id=$lastmodby");
      if ($r->fields["email"])  {
         echo "<tr><th>Last modified by: </th><td><a href='mailto:".$r->fields["email"]."'>";
         echo $r->fields["firstname"]." ".$r->fields["lastname"]."</a></td>\n";
      }
      else { 
      echo "<tr><th>Last modified by: </th><td>".$r->fields["firstname"]." ";
         echo $r->fields["lastname"] ."</td>\n";
      }
      echo "<td>&nbsp;</td>";
      $lastmoddate=date($dateformat,$lastmoddate);
      echo "<th>Date modified: </th><td colspan=3>$lastmoddate</td></tr>\n";
   }   
}



///////////////////////////////////////////////////////////
//// 
// !Displays all information within the table
function display_table_info($db,$tablename,$real_tablename,$DB_DESNAME,$Fieldscomma,$pr_query,$num_p_r,$pr_curr_page) {
   global $nr_records,$max_menu_length,$USER,$LAYOUT;

   $r=$db->PageExecute($pr_query,$num_p_r,$pr_curr_page);
   $rownr=1;
   // print all entries
   while (!($r->EOF) && $r)  {
      // Get required ID and title
      $id=$r->fields["id"];
      $title=$r->fields["title"];		
      $Allfields=getvalues($db,$real_tablename,$DB_DESNAME,$Fieldscomma,id,$id);
      // print start of row of selected group
      if ($rownr % 2)echo "<tr class='row_odd' align='center'>\n";
         else echo "<tr class='row_even' align='center'>\n";
  
      foreach($Allfields as $nowfield) { 	// read in all entries into variables
         if ($nowfield[datatype] =="text") {
            if ($nowfield[values])
               echo "<td>$nowfield[values]</td>\n"; 
            else
               echo "<td>&nbsp;</td>\n";
         }
         if ($nowfield[datatype] =="link") {
            if ($nowfield[values])
               echo "<td><a href='$nowfield[values]' target='_blank'>link</a></td>\n";
            else 
               echo "<td>&nbsp;</td>\n";
         } 
         if ($nowfield[datatype] =="pulldown") {
            $text=get_cell($db,$nowfield[ass_t],"typeshort","id",$nowfield[values]); 
            if (!$text)
               $text="&nbsp;";
            echo "<td>$text</td>";
         }
         if ($nowfield[datatype] == "textlong") {
            if ($nowfield[values]=="")
               echo "<td>no</td>\n";
            else 
               echo "<td>yes</td>\n"; 
         }
         if ($nowfield[datatype] == "file") {
            $files=get_files($db,$tablename,$id,3);
            echo "<td>";
            if ($files) 
               for ($i=0;$i<sizeof($files);$i++)
                  echo $files[$i]["link"];
            else
               echo "&nbsp;";
            echo "</td>\n";
         }
      }	
      echo "<td align='center'>&nbsp;\n";  
      echo "<input type=\"submit\" name=\"view_" . $id . "\" value=\"View\">\n";
      if (may_write($db,$real_tablename,$id,$USER)) {
         echo "<input type=\"submit\" name=\"mod_" . $id . "\" value=\"Modify\">\n";
         $delstring = "<input type=\"submit\" name=\"del_" . $id . "\" value=\"Remove\" ";
         $delstring .= "Onclick=\"if(confirm('Are you want to remove $title";
         $delstring .= "?')){return true;}return false;\">";                                           
         echo "$delstring\n";
      }
      echo "</td>\n";
      echo "</tr>\n";
      $r->MoveNext();
      $rownr+=1;
   }
   // Add Record button
   if (may_write($db,$real_tablename,false,$USER)) {
      echo "<tr><td colspan=10 align='center'>";
      echo "<input type=\"submit\" name=\"add\" value=\"Add Record\">";
      echo "</td></tr>";
   }

   echo "</table>\n";
   next_previous_buttons($r);
   echo "</form>\n";
}

///////////////////////////////////////////////////////////
////
// !Display a record in a nice format
function display_record($db,$Allfields,$id,$tablename,$real_tablename) {
   global $PHP_SELF;

   echo "<table border=0 align='center'>\n";
   echo "<tr align='center'>\n";
   echo "<td colspan=2></td>\n";
   foreach ($Allfields as $nowfield) {
      //see if display_table is set
      if ($nowfield[display_table]==Y) {
         if ($nowfield[datatype]=="text"){
            echo "<tr>\n";
            echo "<th>$nowfield[name]</th>\n";
            if ($nowfield[values])
               echo "<td colspan=2>$nowfield[values]</td></tr>\n";
            else
               echo "<td colspan=2>&nbsp;</td></tr>\n";
         }
	 if ($nowfield[datatype]=="link") {
            echo "<tr>\n<th>$nowfield[name]</th>\n";
            echo "<td colspan=2><a href='$nowfield[values]' target='_blank'>$nowfield[values]</a></td></tr>\n";
         }
         if ($nowfield[datatype]=="pulldown") {
            $text=get_cell($db,$nowfield[ass_t],"type","id",$nowfield[values]);
            echo "<tr>\n<th>$nowfield[name]:</th>\n<td>$text</td></tr>\n";
         }
         if ($nowfield[datatype]=="textlong") {
            $textlarge=nl2br(htmlentities($nowfield[values]));
            echo "<tr><th>$nowfield[name]</th><td colspan=6>$textlarge</td>\n";
            echo "</tr>\n";
         }
         if ($nowfield[datatype]=="file") {
            $files=get_files($db,$tablename,$id);
            if ($files) { 
               echo "<tr><th>Files:</th>\n<td colspan=5>";
               for ($i=0;$i<sizeof($files);$i++)  {
                  echo $files[$i]["link"]."&nbsp;&nbsp;(".$files[$i]["type"];
                  echo " file)<br>\n";
               }
               echo "<td></tr>\n";
            }				
         }
      }
   }
   user_entry($id,$real_tablename);
   date_entry($id,$real_tablename);
   make_link($id,$tablename);
   echo "<form method='post' action='$PHP_SELF?tablename=$tablename&".SID."'>\n";
   echo "<tr>\n<td colspan=7 align='center'>";
   echo "<input type='submit' name='submit' value='Back'></td>\n</tr>\n";
   echo "</table>";
}

///////////////////////////////////////////////////////////
////
// !make a nice link to the record
function make_link($id,$DBNAME) {
   global $PHP_SELF,$system_settings;
   echo "<tr><th>Link:</th><td colspan=7><a href='$PHP_SELF?tablename=$DBNAME&showid=$id&".SID;
   echo "'>".$system_settings["baseURL"].getenv("SCRIPT_NAME")."?tablename=$DBNAME&showid=$id</a></td></tr>\n";
}

///////////////////////////////////////////////////////////
////
// !display addition and modification form
function display_add($db,$tablename,$real_tablename,$tabledesc,$Allfields,$id,$namein,$system_settings)  
	{
	global $PHP_SELF, $db_type, $USER;	

    $dbstring=$PHP_SELF;$dbstring.="?";$dbstring.="tablename=$tablename&";
    echo "<form method='post' id='protocolform' enctype='multipart/form-data' action='$dbstring";
	?><?=SID?>'><?php

   if (!$magic) $magic=time();
   echo "<input type='hidden' name='magic' value='$magic'>\n";
   echo "<table border=0 align='center'>\n";   
   if ($id) 
 	  	{
 	      echo "<tr><td colspan=5 align='center'><h3>Modify $tablename entry <i>$namein</i></h3></td></tr>\n";
 	      echo "<input type='hidden' name='id' value='$id'>\n";
 	      }
    else{echo "<tr><td colspan=5 align='center'><h3>New $tablename entry</h3></td></tr>\n";}
    echo "<table border=0 align='center'>\n<tr align='center'>\n<td colspan=2></td>\n";
	foreach ($Allfields as $nowfield) 
		{
		//see if display_record is set
		if (($nowfield[display_record]==Y) || ($nowfield[display_table]==Y))
			{
			if ($nowfield[datatype]=="text")
				{
				echo "<tr><th>$nowfield[name]:"; 
				if ($nowfield[required]=="Y"){echo "<sup style='color:red'>&nbsp;*</sup>";}
				echo "</th>\n";
     	       echo "<td><input type='text' name='$nowfield[name]' value='$nowfield[values]' size=60> </td>\n</tr>";
				}
		if ($nowfield[datatype]=="textlong")
				{
				echo "<tr><th>$nowfield[name]:";
				if ($nowfield[required]=="Y"){echo "<sup style='color:red'>&nbsp;*</sup>";}
     	       echo "<td><textarea name='$nowfield[name]' rows='5' cols='100%' value='$nowfield[values]'>$nowfield[values]</textarea></td></tr>\n";     	     
				}
		if ($nowfield[datatype]=="link")
				{
				echo "<tr><th>$nowfield[name] (http link)";
				if ($nowfield[required]=="Y"){echo "<sup style='color:red'>&nbsp;*</sup>";}
				echo "<td><input type='text' name='$nowfield[name]' value='$nowfield[values]' size=60> </td>\n</tr>";
				}
			if ($nowfield[datatype]=="pulldown")		
				{
				// get previous value	
				if ($nowfield[name]=="authors")
					{
					if ($db_type=="mysql") // mysql does not use the ansi SQL || operator
			   	   {$r=$db->Execute("SELECT CONCAT(type, ' ', typeshort),id  from $nowfield[ass_t] ORDER by typeshort");}
  				  else
   					{$r=$db->Execute("SELECT type || ' ' || typeshort,id  from $nowfield[ass_t] ORDER by typeshort");}
					}
				else
					{$r=$db->Execute("SELECT typeshort,id FROM $nowfield[ass_t] ORDER BY sortkey");}

 	  		 $text=$r->GetMenu2("$nowfield[name]",$nowfield[values],true,false);
     		   echo "<tr><th>$nowfield[name]";
     		   if ($USER["permissions"] && $LAYOUT)
     		   	{
     		   	echo "<a href='$PHP_SELF?tablename=$tablename&edit_type=$nowfield[ass_t]&<?=SID?>'>";
     		   	echo "<FONT size=1 color='#00ff00'> <small>(edit)</small></font></a>";
     		   	}
     		   if ($nowfield[required]=="Y"){echo"<sup style='color:red'>&nbsp;*</sup>";}
     		   echo "</th>\n<td>$text<br>";
     		   if ($nowfield[name]=="authors")
   				{
	   			echo "Or: First<input type='text' size=8 name='firstname' value='$firstname'>\n";
 	  			echo "Last<input type='text' size=12 name='lastname' value='$lastname'></td>\n";
   				}   
     			echo "</tr>";
				}
			if ($nowfield[datatype]=="textlarge")
				{
  		      echo "<tr><th>$nowfield[name]";
  		      if ($nowfield[required]=="Y"){echo"<sup style='color:red'>&nbsp;*</sup>";}
  		      echo "</th><td colspan=6><textarea name='$nowfield[name]' rows='5' cols='100%'>$nowfield[values]</textarea></td></tr>\n";
				}
			if ($nowfield[datatype]=="file")
				{
				$files=get_files($db,$real_tablename,$id);
				echo "<tr>";
				echo "<th>File:";
				echo "</th>\n";
			
				echo "<td colspan=4> <table border=0>";
				for ($i=0;$i<sizeof($files);$i++) 
					{
					echo "<tr><td colspan=2>".$files[$i]["link"];
					echo "&nbsp;&nbsp;(".$files[$i]["type"]." file)</td>\n";
					echo "<td><input type='submit' name='def_".$files[$i]["id"]."' value='Delete' Onclick=\"if(confirm('Are you sure the file ".$files[$i]["name"]." should be removed?')){return true;}return false;\"></td></tr>\n";
					}
				echo "<tr><th>Replace file(s) with</th>\n";
				echo "<td>&nbsp;</td><td><input type='file' name='file[]' value='$filename'></td>\n";
				echo "</tr></table></td></tr><br>\n\n";
				echo "";
				}
			}
	
		}	
echo "<td colspan=4>";
show_access($db,$real_tablename,$id,$USER,$system_settings);
echo "</td></tr>\n"; echo "<tr>";
if ($id) $value="Modify Record"; 
else $value="Add Record";

// submit and clear buttons
echo "<td colspan=7 align='center'><input type='submit' name='submit' value='$value'>\n";
echo "&nbsp;&nbsp;<input type='submit' name='submit' value='Cancel'></td>\n";
echo "</tr>\n";
echo "</table></form>\n";

//end of table
$dbstring=$PHP_SELF;$dbstring.="?";$dbstring.="tablename=$tablename&";
echo "<form method='post' id='protocolform' enctype='multipart/form-data' action='$dbstring";
?><?=SID?>'><?php

}

///////////////////////////////////////////////////////////////////////
////
// !Get all description table values out for a display
function getvalues($db,$DBNAME,$DB_DESNAME,$fields,$qfield=false,$field=false) {
	if ($qfield)
	   $r=$db->Execute("SELECT $fields FROM $DBNAME WHERE $qfield=$field"); 
	else
	   $r=$db->Execute("SELECT $fields FROM $DBNAME"); 
	$column=strtok($fields,",");
	$Allfields=array();
	while ($column) 
   	{
  	 if(!(!$id && $column=="id"))
 	  	{  			  	
  	  		${$column}[values]= $r->fields[$column];
	 	   	${$column}[datatype]=get_cell($db,$DB_DESNAME,"datatype","label","$column");
				${$column}[display_table]=get_cell($db,$DB_DESNAME,"display_table","label","$column");
				${$column}[display_record]=get_cell($db,$DB_DESNAME,"display_record","label","$column");
				${$column}[ass_t]=get_cell($db,$DB_DESNAME,associated_table,label,$column);
				${$column}[ass_query]=get_cell($db,$DB_DESNAME,associated_sql,label,$column);
				${$column}[required]=get_cell($db,$DB_DESNAME,required,label,$column);
				${$column}[name]=$column;
				}
		   array_push ($Allfields, ${$column});
		   $column=strtok(",");
		   }		
	return $Allfields;
}

/////////////////////////////////////////////////
////
// !displays the bar between the search header and the records
function display_midbar($labelcomma)
	{
	$labelarray=explode(",",$labelcomma);
	echo "<tr>\n";
    foreach($labelarray as $fieldlabel) 
	{echo "<th>$fieldlabel</th>";}
    echo "<th>Action</th>\n";
    echo "</tr>\n";
	}

//////////////////////////////////////////////////////
////
// !SQL where search that returns a comma delimited string
function comma_array_SQL_where($db,$tablein,$column,$searchfield,$searchval)
	{
	$tempa=array();
	$rs = $db->Execute("select $column from $tablein where $searchfield='$searchval'");

	if ($rs)
		{
		while (!$rs->EOF) {
		$fieldA=$rs->fields[0];
		array_push($tempa, $fieldA);
		$rs->MoveNext();
		}
	}
$out=join(",",$tempa);
return $out;
}

//////////////////////////////////////////////////////
////
// !SQL search (entrire column) that returns a comma delimited string
function comma_array_SQL($db,$tablein,$column) {
	$tempa=array();
	$rs = $db->Execute("select $column from $tablein");
	if ($rs)
		{
		while (!$rs->EOF) {
			$fieldA=$rs->fields[0];
			array_push($tempa, $fieldA);
			$rs->MoveNext();
			}
		}
$out=join(",",$tempa);
return $out;
}


//////////////////////////////////////////////////////////////////////
////  general functions
/****************************FUNCTIONS***************************/
////
// !Checks input data to addition
// returns false if something can not be fixed     
function check_g_data ($db,&$field_values, $DB_DESNAME) {

   $allreq=array();
   $rs = $db->Execute("select label from $DB_DESNAME where required ='Y' and (datatype != 'file')");
	while (!$rs->EOF) 
		{
		$fieldA=$rs->fields[0];
		array_push($allreq, $fieldA);
		$rs->MoveNext();
		}
   foreach ($allreq as $checkme)
   		{
   		if (!$field_values["$checkme"]) 
   			{echo "<center><b>Please enter all starred fields.</center></b>";return false;}
		}
   if (!$field_values["title"]) {
      echo "<h3>Please enter a title for Record.</h3>";return false;}
   // When a new author was entered
   $firstname=$field_values["firstname"];$lastname=$field_values["lastname"];
   if ($firstname || $lastname) {
      // check if this entry exists already
	  $authortable=get_cell($db,$DB_DESNAME,associated_table,label,authors);
      $r=$db->Execute("SELECT id FROM $authortable WHERE type='$firstname' AND typeshort='$lastname'");
      if ($r && !$r->EOF) {$field_values["authors"]=$r->fields["id"];return true;}
      $id=$db->GenID("pr_type2_id_seq");
      $db->Execute("INSERT INTO $authortable (id,type,typeshort) VALUES ('$id','".
           $field_values["firstname"]."','".$field_values["lastname"]."')");
      $field_values["authors"]=$id;
   }
   return true;
}

////
// !Prints a form with addition stuff
// $fields is a comma-delimited string with column names
// $field_values is hash with column names as keys
// $id=0 for a new entry, otherwise it is the id
function add_g_form ($db,$fields,$field_values,$id,$USER,$PHP_SELF,$system_settings,$real_tablename, $tablename,$DB_DESNAME) {
   if (!may_write($db,$$real_tablename,$id,$USER)) 
           return false; 
   if ($id) {
		$Allfields=getvalues($db,$real_tablename,$DB_DESNAME,$fields,id,$id);
		$namein=get_cell($db,$DBNAME,"title","id",$id);		
		display_add($db,$tablename,$real_tablename,$DB_DESNAME,$Allfields,$id,$namein,$system_settings);
	}    
	else {
		$Allfields=getvalues($db,$DBNAME,$DB_DESNAME,$fields);
		display_add($db,$tablename,$real_tablename,$DB_DESNAME,$Allfields,$id,"",$system_settings);
	}
}

////
// !Shows a page with nice information on the record
function show_g($db,$fields,$id,$USER,$system_settings,$tablename,$real_tablename,$DB_DESNAME)  {
   if (!may_read($db,$real_tablename,$id,$USER))
       return false;
   $Allfields=getvalues($db,$real_tablename,$DB_DESNAME,$fields,id,$id);
   display_record($db,$Allfields,$id,$tablename,$real_tablename);
}
	
////
// !Tries to convert a MsWord file into html 
// It calls wvHtml.  This does not work with wvHtml version 0.7-0.72
// Version 0.67 is fine....
// When succesfull, the file is added to the database
function process_file($db,$fileid,$system_settings) {
   global $HTTP_POST_FILES,$HTTP_POST_VARS;
   $mimetype=get_cell($db,"files","mime","id",$fileid);
   if (!strstr($mimetype,"html")) {
      $word2html=$system_settings["word2html"];
      $filepath=file_path($db,$fileid);
      $temp=$system_settings["tmpdir"]."/".uniqid("file");
      $command= "$word2html $filepath $temp";
      $result=exec($command);
      if (@is_readable($temp)) {
         unset ($HTTP_POST_FILES);
         $r=$db->query ("SELECT filename,mime,title,tablesfk,ftableid FROM files WHERE id=$fileid");
         if ($r && !$r->EOF) {
            $filename=$r->fields("filename");
            // change .doc to .html in a lousy way
            $filename=str_replace(".doc",".htm",$filename); 
            $mime="text/html";
            $type=substr(strrchr($mime,"/"),1);
            $size=filesize($temp);
            $id=$db->GenID("files_id_seq");
            $query="INSERT INTO files (id,filename,mime,size,title,tablesfk,ftableid,type) VALUES ($id,'$filename','$mime','$size','".$r->fields("title")."','".$r->fields("tablesfk")."','".$r->fields("ftableid")."','$type')";
           if ($db->execute($query)) {
                $newloc=file_path($db,$id);
               `mv $temp $newloc`;
            }
            else
               unlink($temp); 
         }    
      }
   }
}

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
 
   $query = "SELECT id,tablename,Display,sortkey,Custom FROM tableoftables ORDER BY sortkey";
   $r=$db->Execute($query);
   $rownr=0;
   // print all entries
   while (!($r->EOF) && $r) {
      // get results of each row
      $id = $r->fields["id"];
      $name = $r->fields["type"];
      $Display = $r->fields["Display"];
      $sortkey = $r->fields["sortkey"];
 	 $Custom = $r->fields["Custom"];
   
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
function del_table($db,$tablename,$id) {
   global $HTTP_POST_VARS, $string;

   $real_tablename=$tablename."_".$id;
   $desc=$real_tablename."_desc";
   echo "$desc, $real_tablename.<br>";
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
   $r=$db->Execute("DROP TABLE $real_tablename_id");
   $r=$db->Execute("DROP SEQUENCE $real_tablename_id");
   $r=$db->Execute("DROP TABLE $desc");
   $r=$db->Execute("DROP TABLE $desc"."_id");
   $r=$db->Execute("DROP SEQUENCE $desc"."_id");
   $r=$db->Execute("Delete from tableoftables WHERE id=$id");
   if ($r) 
      $string="Table $tablename has been deleted";
   return $string;
}

/////////////////////////////////////////////////////////////////////////
////   
// !creates a general table 
function add_table ($db,$tablename,$sortkey) {
    global $string;
    $shortname=substr($tablename,0,3);
   
   //check to ensure that duplicate table or database does not exist
   $r=$db->Execute("SELECT tablename FROM tableoftables");
   $ALLTABLES=$r->GetArray();
   foreach($ALLTABLES as $table1) {
      if ("$tablename" == "$table1")
         $isbad=true; 
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
      $id=$db->GenID("tableoftables"."gen_id_seq",10000);
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
  	 $r=$db->Execute("INSERT INTO tableoftables (id,sortkey,tablename,shortname,Display,Permission) Values($id,'$sortkey','$tablename','$shortname','Y','Users')");
         $r=$db->Execute("CREATE TABLE $desc (
		id int PRIMARY KEY,
		sortkey int,
		label text, 
		display_table char(1), 
		display_record char(1), 
		required char(1), 
		type text, 
		datatype text, 
		associated_table text, 
		associated_sql text)");   

         $fieldstring="id,label,sortkey,display_table,display_record, required, type, datatype, associated_table, associated_sql"; 
         $descid=$db->GenId("$desc"."_id");  
  	 $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid'id','100','N','N','N','int(11)','text','','')");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'access','110','N','N','N','varchar(9)','text','','')");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'ownerid','120','N','N','N','int(11)','text','','')");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'magic','130,'N','N','N','int(11)','text','','')");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'title','140','Y','Y','Y','text','text','','')");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'lastmoddate','150','N','N','N','int(11)','text','','')");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'lastmodby','160','N','N','N','int(11)','text','','')");
         $descid=$db->GenId("$desc"."_id");  
         $db->Execute("INSERT INTO $desc ($fieldstring) Values($descid,'date','170','N','N','N','int(11)','text','','')");
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
function mod_table($db,$id,$tablename,$tablesort,$tabledisplay) 
	{
	global $string;
    $r=$db->Execute("UPDATE tableoftables SET sortkey='$tablesort',Display='$tabledisplay' where id='$id'");   	
    if ($r) {$string="Succesfully Changed Record $tablename";}
    else {$string="Please enter all fields";}
    return false;
	}

/////////////////////////////////////////////////////////////////////////
//// 
// !adds a general column entry
function add_columnecg($db,$tablename2,$colname2,$datatype,$Rdis,$Tdis,$req,$sort)
   {
   global $string;

   $SQL_reserved="absolute,action,add,allocate,alter,are,assertion,at,between,bit,bit_length,both,cascade,cascaded,case,cast,catalog,char_length,charachter_length,cluster,coalsce,collate,collation,column,connect,connection,constraint,constraints,convert,corresponding,cross,current_date,current_time,current_timestamp,current_user,date,day,deallocate,deferrrable,deferred,describe,descriptor,diagnostics,disconnect,domain,drop,else,end-exec,except,exception,execute,external,extract,false,first,full,get,global,hour,identity,immediate,initially,inner,input,insensitive,intersect,interval,isolation,join,last,leading,left,level,local,lower,match,minute,month,names,national,natural,nchar,next,no,nullif,octet_length,only,outer,output,overlaps,pad,partial,position,prepare,preserve,prior,read,relative,restrict,revoke,right,rows,scroll,second,session,session_user,size,space,sqlstate,substring,system_user,temporary,then,time,timepstamp,timezone_hour,timezone_minute,trailing,transaction,translate,translation,trim,true,unknown,uppper,usage,using,value,varchar,varying,when,write,year,zone";

   // find the id of the table and therewith the tablename
   $r=$db->Execute("SELECT id FROM tableoftables WHERE tablename='$tablename2'");
   $id=$r->fields["id"];
   $search=array("' '","','","';'","'\"'");
   $replace=array("_","_","","");
   $colname = preg_replace ($search,$replace, $colname2);
   $real_tablename=$tablename2."_".$id;

   $fieldstring="id,label,sortkey, display_table, display_record,required, type, datatype, associated_table, associated_sql"; 
   $desc=$real_tablename . "_desc";
   $fieldid=$db->GenId($desc."_id");
   if ($colname=="")
      $string="Please enter a columnname";
   elseif (strpos($SQL_reserved,strtolower($colname))) 
      $string="Column name <i>$colname</i> is a reserved SQL word.  Please pick another column name";
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
	 $r=$db->Execute("INSERT INTO $desc ($fieldstring) Values($fieldid,'$colname','$sort','$Tdis','$Rdis','$req','text','$datatype','$tablestr','$colname from $tablestr where ')");
	 $rs=$db->Execute("CREATE TABLE $tablestr (id int PRIMARY KEY, sortkey int, type text, typeshort text)");
	 $rsss=$db->Execute("ALTER table $real_tablename add column $colname text");
	 if (($r)&&($rs)&&($rsss)&&(!($colname==""))) 
            $string="Added column <i>$colname</i> into table <i>$tablename2</i>";
	 else 
	    $string="Problems creating this column.";
      }
      else {
         $r=$db->Execute("INSERT INTO $desc ($fieldstring) Values($fieldid,'$colname','$sort','$Tdis','$Rdis','$req','text','$datatype','','')");
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
function mod_columnECG($db,$id,$sort,$tablename,$colname,$datatype,$Rdis,$Tdis,$req) {
   global $string;
   $desc=$tablename;$desc.="_desc";
   $r=$db->Execute("UPDATE $desc SET sortkey='$sort',display_table='$Tdis', display_record='$Rdis',required='$req' where id='$id'");   	
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
	global $string;
	// find the id of the table and therewith the tablename
	$r=$db->Execute("SELECT id FROM tableoftables WHERE tablename='$tablename'");
	$fieldid=$r->fields["id"];
	$real_tablename=$tablename."_".$fieldid;
	$desc=$real_tablename."_desc";
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
