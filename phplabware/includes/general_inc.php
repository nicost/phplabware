<?php

///////////////////////////////////////////////////////////////////////
////
//  !display functions for general types

///////////////////////////////////////////////////////////
function user_entry($id)//get the user who made the entry
	{
	global $db,$DBNAME,$DB_DESNAME;
	$ownerid=get_cell($db,$DBNAME,"ownerid","id","$id");
	$r=$db->Execute("SELECT firstname,lastname,email FROM users WHERE id=$ownerid");
	if ($r->fields["email"]) 
		{
		echo "<tr><th>Submitted by: </th><td><a href='mailto:".$r->fields["email"]."'>";
		echo $r->fields["firstname"]." ".$r->fields["lastname"]."</a></td>\n";
		}
	else {echo "<tr><th>Submitted by: </th><td>".$r->fields["firstname"]." ";echo $r->fields["lastname"] ."</td>\n";}
    echo "<td>&nbsp;</td>";
	}
///////////////////////////////////////////////////////////
function date_entry($id)// Get the date the entry was made
	{	
	global $db,$DBNAME,$DB_DESNAME,$system_settings;
    $date=simple_SQL($db,$DBNAME,"date","id","$id");
    $dateformat=get_cell($db,"dateformats","dateformat","id",$system_settings["dateformat"]);
    $date=date($dateformat,$date);
    echo "<th>Date entered: </th><td colspan=3>$date</td></tr>\n";
	if ($lastmodby && $lastmoddate) 
		{
		echo "<tr>";
		$r=$db->Execute("SELECT firstname,lastname,email FROM users WHERE id=$lastmodby");
		if ($r->fields["email"]) 
			{
   	     echo "<tr><th>Last modified by: </th><td><a href='mailto:".$r->fields["email"]."'>";
   	     echo $r->fields["firstname"]." ".$r->fields["lastname"]."</a></td>\n";
   	     }
   	 else 
    		{
    	    echo "<tr><th>Last modified by: </th><td>".$r->fields["firstname"]." ";
    	    echo $r->fields["lastname"] ."</td>\n";
    	    }
   	 echo "<td>&nbsp;</td>";
   	 $dateformat=get_cell($db,"dateformats","dateformat","id",$system_settings["dateformat"]);
   	 $lastmoddate=date($dateformat,$lastmoddate);
   	 echo "<th>Date modified: </th><td colspan=3>$lastmoddate</td></tr>\n";
   	}   
	}
//////////////////////////////////////////////////////////////////////////////	
///Displays all information within a table in the headers for easy searching
function display_tablehead($Allfields,$list)  
{
	global $db,$DBNAME,$DB_DESNAME,$nr_records,$max_menu_length,$USER,$LAYOUT,$db_type;
   // row with search form
   echo "<tr align='center'>\n";
   echo "<input type='hidden' name='searchj' value=''>\n";

	foreach($Allfields as $nowfield) 
	{   		
	if ($nowfield[datatype]== "link")
		{echo "<td style='width: 10%'>link</td>\n";}		
	if ($nowfield[datatype]== "text")
		{	
	   // show title we may see, when too many, revert to text box
		if ($list && ($nr_records < $max_menu_length) ) 
			{	
  	      $r=$db->Execute("SELECT $nowfield[name] FROM $DBNAME WHERE id IN ($list)");
	        $text=$r->GetMenu("$nowfield[name]","",true,false,0,"style='width: 80%' $jscript");
	 	   echo "<td style='width: 10%'>$text</td>\n";
	    	}
	    else
	    	echo  " <td style='width: 10%'><input type='text' name='$nowfield[name]' value='$nowfield[name]' size=8></td>\n";
		}
	if ($nowfield[datatype]== "textlong")
		{echo "<td style='width: 10%'>&nbsp</td>\n";}
	if ($nowfield[datatype]== "pulldown")
		{	
 	   echo "<td style='width: 10%'>";
  	  if ($USER["permissions"] & $LAYOUT) 
  	  	{
   	 	echo "<a href='$PHP_SELF?tablename=$DBNAME&edit_type=$nowfield[ass_t]&<?=SID?>";
    		echo "'>Edit $nowfield[name]</a><br>\n";
			}	 		 			
		$r=$db->Execute("SELECT $nowfield[name] FROM $DBNAME WHERE id IN ($list)");
		$list2=make_SQL_ids($r,false,"$nowfield[name]");
		if ($list2) 
		   {
		   if ($nowfield[name]=="authors")
		   	{
		   	if ($db_type=="mysql") // mysql does not use the ansi SQL || operator
		   		{$r=$db->Execute("SELECT CONCAT(type, ' ', typeshort),id from $nowfield[ass_t] WHERE id IN ($list2) ORDER by typeshort");}
		   	else
		   		{$r=$db->Execute("SELECT type || ' ' || typeshort,id from $nowfield[ass_t] WHERE id IN ($list) ORDER by typeshort");}
				}
			else 
    			{$r=$db->Execute("SELECT typeshort,id from $nowfield[ass_t] WHERE id IN ($list2) ORDER by typeshort");}
    		
			$text=$r->GetMenu2("$nowfield[name]","",true,false,0,"style='width: 80%' $jscript");   
    	    echo "$text</td>\n";
   		}  
		}
	if ($nowfield[datatype] == "file")
		{echo "<td style='width: 10%'>File Display &nbsp;</td>";}
	}	
   echo "<td><input type=\"submit\" name=\"search\" value=\"Search\">&nbsp;";
   echo "<input type=\"submit\" name=\"search\" value=\"Show All\"></td>";
   echo "</tr>\n";
}
///////////////////////////////////////////////////////////
// Displays all information within the table
function display_table_info($Fieldscomma,$pr_query,$num_p_r,$pr_curr_page)
{
	global $db,$DBNAME,$DB_DESNAME,$nr_records,$max_menu_length,$USER,$LAYOUT;
	$r=$db->PageExecute($pr_query,$num_p_r,$pr_curr_page);
	$rownr=1;
	// print all entries
	while (!($r->EOF) && $r) 
       {
       // Get required ID and title
       $id=$r->fields["id"];
       $title=$r->fields["title"];		
       $Allfields=getvalues($db,$DBNAME,$DB_DESNAME,$Fieldscomma,id,$id);
       // print start of row of selected group
       if ($rownr % 2)echo "<tr class='row_odd' align='center'>\n";
       else echo "<tr class='row_even' align='center'>\n";
  
   	foreach($Allfields as $nowfield)  	// read in all entries into variables
   		{
		   if ($nowfield[datatype] =="text") {echo "<td>$nowfield[values]</td>\n";} 
  		 if ($nowfield[datatype] =="link") 
  		 	{
  		 	if ($nowfield[values]){echo "<td><a href='$nowfield[values]' target='_blank'>link</a></td>\n";}
				else {echo "<td></td>\n";}
  		 	} 
		   if ($nowfield[datatype] =="pulldown")
		   	{$text=get_cell($db,$nowfield[ass_t],"typeshort","id",$nowfield[values]); echo "<td>$text</td>";}
			if ($nowfield[datatype] == "textlong")
			   {
				  if ($nowfield[values]=="")echo "<td>no</td>\n";
 			 	else echo "<td>yes</td>\n"; 
			   }
			if ($nowfield[datatype] == "file")
				{   	
                $files=get_files($db,$DBNAME,$id,3);
      		  echo "<td>";
  		      if ($files) 
         	   for ($i=0;$i<sizeof($files);$i++)
	    		echo $files[$i]["link"];
   		     else
                echo "&nbsp;";echo "</td>\n";
				}
			}	
      echo "<td align='center'>&nbsp;\n";  echo "<input type=\"submit\" name=\"view_" . $id . "\" value=\"View\">\n";
      if (may_write($db,$DBNAME,$id,$USER)) 
      	{
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
   if (may_write($db,$DBNAME,false,$USER)) {
      echo "<tr><td colspan=10 align='center'>";
      echo "<input type=\"submit\" name=\"add\" value=\"Add Record\">";
      echo "</td></tr>";
   }

   echo "</table>\n";
   next_previous_buttons($r);
   echo "</form>\n";
}

///////////////////////////////////////////////////////////
//Display a record in a nice format
function display_record($Allfields,$id)
	{
	global $db,$DBNAME,$DB_DESNAME, $system_settings;
	echo "<table border=0 align='center'>\n";
    echo "<tr align='center'>\n";
    echo "<td colspan=2></td>\n";
	foreach ($Allfields as $nowfield) 
		{
	    //see if display_table is set
		if ($nowfield[display_table]==Y)
			{
			if ($nowfield[datatype]=="text")
				{
				echo "<tr>\n";
				echo "<th>$nowfield[name]</th>\n";
				echo "<td colspan=2>$nowfield[values]</td></tr>\n";
				}
			if ($nowfield[datatype]=="link")
				{
				echo "<tr>\n";
				echo "<th>$nowfield[name]</th>\n";
				echo "<td colspan=2><a href='$nowfield[values]' target='_blank'>$nowfield[values]</a></td></tr>\n";
				}
			if ($nowfield[datatype]=="pulldown")		
				{
				// get author value	
				$text=get_cell($db,$nowfield[ass_t],"type","id",$nowfield[values]);
 	  		 echo "<th>$nowfield[name]:</th>\n<td>$text<br>";echo "</tr><br>";
				}
			if ($nowfield[datatype]=="textlong")
				{
				$textlarge=nl2br(htmlentities($nowfield[values]));
				echo "<th>$nowfield[name]</th><td colspan=6>$textlarge</td>\n";echo "</tr>\n";
				}
			if ($nowfield[datatype]=="file")
				{
				$files=get_files($db,$DBNAME,$id);
   			 if ($files) 
   			 	{
       	 		echo "<tr><th>Files:</th>\n<td colspan=5>";
      		 	 for ($i=0;$i<sizeof($files);$i++) 
      		  		{
         			   echo $files[$i]["link"]."&nbsp;&nbsp;(".$files[$i]["type"];
         	  	 	echo " file)<br>\n";
			      	}
 				   echo "<td></tr>\n";
				   }				
				}
			}
		}
		user_entry($id);
		date_entry($id);
		make_link($id);
		echo "</table>";
	}

///////////////////////////////////////////////////////////
//make a nice link to the record
function make_link($id)
	{
	global $db,$DBNAME,$DB_DESNAME,$system_settings;
    echo "<tr><th>Link:</th><td colspan=7><a href='$PHP_SELF?tablename=$DBNAME&showid=$id&<?=SID?>";
    echo "'>".$system_settings["baseURL"].getenv("SCRIPT_NAME")."?tablename=$DBNAME&showid=$id</a></td></tr>\n";
	}

///////////////////////////////////////////////////////////
////
// !display addition and modification form
function display_add($Allfields,$id,$namein)  
	{
	global $db, $DBNAME, $DB_DESNAME, $db_type, $USER;	

    $dbstring=$PHP_SELF;$dbstring.="?";$dbstring.="tablename=$DBNAME&";
    echo "<form method='post' id='protocolform' enctype='multipart/form-data' action='$dbstring";
	?><?=SID?>'><?php

   if (!$magic) $magic=time();
   echo "<input type='hidden' name='magic' value='$magic'>\n";
   echo "<table border=0 align='center'>\n";   
   if ($id) 
 	  	{
 	      echo "<tr><td colspan=5 align='center'><h3>Modify $DBNAME entry <i>$namein</i></h3></td></tr>\n";
 	      echo "<input type='hidden' name='id' value='$id'>\n";
 	      }
    else{echo "<tr><td colspan=5 align='center'><h3>New $DBNAME entry</h3></td></tr>\n";}
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
     		   	echo "<a href='$PHP_SELF?tablename=$DBNAME&edit_type=$nowfield[ass_t]&<?=SID?>'>";
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
				$files=get_files($db,$DBNAME,$id);
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
show_access($db,$DBNAME,$id,$USER,$system_settings);
echo "</td></tr>\n"; echo "<tr>";
if ($id) $value="Modify Record"; 
else $value="Add Record";

// submit and clear buttons
echo "<td colspan=7 align='center'><input type='submit' name='submit' value='$value'>\n";
echo "&nbsp;&nbsp;<input type='submit' name='submit' value='Cancel'></td>\n";
echo "</tr>\n";
echo "</table></form>\n";

//end of table
$dbstring=$PHP_SELF;$dbstring.="?";$dbstring.="tablename=$DBNAME&";
echo "<form method='post' id='protocolform' enctype='multipart/form-data' action='$dbstring";
?><?=SID?>'><?php

}

///////////////////////////////////////////////////////////////////////
////
// !Get all description table values out for a display
function getvalues($db,$DBNAME,$DB_DESNAME,$fields,$qfield,$field) {
	$r=$db->Execute("SELECT $fields FROM $DBNAME WHERE $qfield=$field"); 
	$column=strtok($fields,",");
	$Allfields=array();
	while ($column) 
	   	{
	  	 if(!(!$id && $column=="id"))
	 	  	{  			  	
  	  		${$column}[values]= $r->fields[$column];
	 	   	${$column}[datatype]=simple_SQL($db,$DB_DESNAME,"datatype","label","$column");
				${$column}[display_table]=simple_SQL($db,$DB_DESNAME,"display_table","label","$column");
				${$column}[display_record]=simple_SQL($db,$DB_DESNAME,"display_record","label","$column");
				${$column}[ass_t]=simple_SQL($db,$DB_DESNAME,associated_table,label,$column);
				${$column}[ass_query]=simple_SQL($db,$DB_DESNAME,associated_sql,label,$column);
				${$column}[required]=simple_SQL($db,$DB_DESNAME,required,label,$column);
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
function comma_array_SQL($db,$tablein,$column)
	{
	global $db;
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

//////////////////////////////////////////////////////
////
// !SQL search that returns a single cell
function simple_SQL($db,$tablein,$column,$searchfield,$searchval)
	{
	$rs = $db->Execute("select $column from $tablein where $searchfield ='$searchval'");
	$fieldA=$rs->fields[0];
	return $fieldA;
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
function add_g_form ($db,$fields,$field_values,$id,$USER,$PHP_SELF,$system_settings,$DB_DESNAME) 
	{
	global $db, $DBNAME, $DB_DESNAME;
	if (!may_write($db,$DBNAME,$id,$USER)) return false; 
	if ($id)
		{
    	if ($r->EOF) {echo "<h3>Could not find this record in the database</h3>";return false;}		
		$Allfields=getvalues($db,$DBNAME,$DB_DESNAME,$fields,id,$id);
		$namein=get_cell($db,$DBNAME,"title","id",$id);		
		display_add($Allfields,$id,$namein);
		}    
	else 
		{
		$Allfields=getvalues($db,$DBNAME,$DB_DESNAME,$fields,blank,blank);display_add($Allfields,$id,"");}
	}

////
// !Shows a page with nice information on the record
function show_g($db,$fields,$id,$USER,$system_settings,$DBNAME,$DB_DESNAME) 
	{
    if (!may_read($db,"$DBNAME",$id,$USER))
    return false;
	$Allfields=getvalues($db,$DBNAME,$DB_DESNAME,$fields,id,$id);
	display_record($Allfields,$id);
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
      if (is_readable($temp)) {
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
// !table management functions
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
// !deletes a general table
function del_table($db,$tablename,$id) {
   global $HTTP_POST_VARS, $string;

   $modarray = explode("_", $key);
   $table=$modarray[1]."_".$modarray[2];
   $real_tablename=$id."_$tablename";
   $desc=$real_tablename."_desc";
   $r=$db->Execute("select associated_table from $desc");
   $tempTAB=array();
	if ($r)
	   {while (!$r->EOF){$fieldA=$r->fields[0];array_push($tempTAB, $fieldA);$r->MoveNext();}
 	   foreach ($tempTAB as $DDD)
 	   	{
 	   		$DD2=$DDD; $DD2.="_id_seq";
 	   		$r=$db->Execute("Drop table if exists $DDD");
 	   		$r=$db->Execute("Drop table if exists $DD2");
			}
 	   }
	$DD3=$table; $DD3.="_id_seq";
	$r=$db->Execute("Drop table if exists $real_tablename");
	$r=$db->Execute("Drop table if exists $desc");
	$r=$db->Execute("Drop table if exists $DD3");
	$r=$db->Execute("Delete from tableoftables WHERE id=$id");
	if ($r){$string="Table $tablename has been deleted";}
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
      $string="please enter a title for the table!";
   if ($isbad)
      {$string="A table with the name $tablename already exists!";}
   if (!$isbad && $tablename) {
           $id=$db->GenID("tableoftables"."_id_seq");
	   $real_tablename=$id."_$tablename";
           $desc=$real_tablename . "_desc";
  	   $r=$db->Execute("CREATE TABLE $real_tablename (id int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, title text, access varchar(9), ownerid int(11), magic int(11), lastmodby int(11), lastmoddate int(11),date int(11))");
  	   if ($r)
  	   	{
  	   	$string= "Succesfully Added Table $tablename";
  	       $r=$db->Execute("INSERT INTO tableoftables (id,sortkey,tablename,shortname,Display,Permission) Values($id,'$sortkey','$tablename','$shortname','Y','Users')");
 		$r=$db->Execute("CREATE TABLE $desc (id int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,sortkey int,label text, display_table char(1), display_record char(1), required char(1), type text, datatype text, associated_table text, associated_sql text)");   

  	   $fieldstring="label, sortkey, display_table, display_record, required, type, datatype, associated_table, associated_sql"; 
  	   $db->Execute("INSERT INTO $desc ($fieldstring) Values('id','100','N','N','N','int(11)','text','','')");
 			$db->Execute("INSERT INTO $desc ($fieldstring) Values('access','110','N','N','N','varchar(9)','text','','')");
 			$db->Execute("INSERT INTO $desc ($fieldstring) Values('ownerid','120','N','N','N','int(11)','text','','')");
 			$db->Execute("INSERT INTO $desc ($fieldstring) Values('magic','130,'N','N','N','int(11)','text','','')");
 			$db->Execute("INSERT INTO $desc ($fieldstring) Values('title','140','Y','Y','Y','text','text','','')");
 			$db->Execute("INSERT INTO $desc ($fieldstring) Values('lastmoddate','150','N','N','N','int(11)','text','','')");
 			$db->Execute("INSERT INTO $desc ($fieldstring) Values('lastmodby','160','N','N','N','int(11)','text','','')");
	 		$db->Execute("INSERT INTO $desc ($fieldstring) Values('date','170','N','N','N','int(11)','text','','')");
  		   }  
  	   else {$string="Please enter all fields";}
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
	// find the id of the table and therewith the tablename
	$r=$db->Execute("SELECT id FROM tableoftables WHERE tablename='$tablename2'");
	$id=$r->fields["id"];
	$search=array("' '","','","';'","'\"'");
	$replace=array("_","_","","");
	$colname = preg_replace ($search,$replace, $colname2);
	$tablename = preg_replace ($search,$replace, $tablename2); 
	$real_tablename=$id."_$tablename";

	$fieldstring="label,sortkey, display_table, display_record,required, type, datatype, associated_table, associated_sql"; 
	$desc=$real_tablename . "_desc";
	if ($colname=="")
	   $string="Please enter a columnname";
	else {
	   if ($datatype=="pulldown")
			{ 
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
			$db->debug=true;
			$r=$db->Execute("INSERT INTO $desc ($fieldstring) Values('$colname','$sort','$Tdis','$Rdis','$req','text','$datatype','$tablestr','$colname from $tablestr where ')");
			$rs=$db->Execute("CREATE TABLE $tablestr (id int PRIMARY KEY, sortkey int, type text, typeshort text)");
			$rsss=$db->Execute("ALTER table $real_tablename add column $colname text");
			if (($r)&&($rs)&&($rsss)&&(!($colname==""))) {$string="Added column $colname into table <i>$tablename</i>";}
			else 
			   $string="Problems creating this column.";
		}				
		else
			{  
			$r=$db->Execute("INSERT INTO $desc ($fieldstring) Values('$colname','$sort','$Tdis','$Rdis','$req','text','$datatype','','')");
			$rsss=$db->Execute("ALTER table $real_tablename add column $colname text");
			if (($r)&&$rsss&&(!($colname==""))) {$string="Added column $colname into table: <i>$tablename</i>";}
			else {$string="Please enter all values";}	
			}
		}
		$db->debug=false;
	}

/////////////////////////////////////////////////////////////////////////
//// 
// !modifies a general column entry
function mod_columnECG($db,$id,$sort,$tablename,$colname,$datatype,$Rdis,$Tdis,$req)
	{
	global $string;
	$desc=$tablename;$desc.="_desc";
    $r=$db->Execute("UPDATE $desc SET sortkey='$sort',display_table='$Tdis', display_record='$Rdis',required='$req' where id='$id'");   	
    if ($r) {$string="Succesfully Changed Column $colname in $tablename";}
    else {$string="Please enter all fields";}
    return false;	
	}

/////////////////////////////////////////////////////////////////////////
//// 
// !deletes a general column entry
function rm_columnecg($db,$tablename,$id,$colname,$datatype) {
	global $string;
	// find the id of the table and therewith the tablename
	$r=$db->Execute("SELECT id FROM tableoftables WHERE tablename='$tablename2'");
	$id=$r->fields["id"];
	$real_tablename=$id."_$tablename";
	$desc=$real_tablename."_desc";
	$r=$db->Execute("Alter table $real_tablename drop column $colname");
	$rv=$db->Execute("select associated_table from $desc where id ='$id'");
    $tempTAB=array();
	if ($rv) {
	   while (!$rv->EOF) {
	      $fieldA=$rv->fields[0];
	      array_push($tempTAB, $fieldA);
	      $rv->MoveNext();
	   }
	   foreach ($tempTAB as $DDD) {
	      $rb=$db->Execute("Drop table if exists $DDD");
	   }
        }
        $rrr=$db->Execute("DELETE FROM $desc WHERE id='$id'");
	if (($r)&&($rrr)) 
	     $string="Succesfully deleted Column <i>$colname</i> from Table <i>$tablename</i>.";
}



?>
