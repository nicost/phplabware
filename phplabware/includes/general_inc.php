<?php

// general_inc.php - functions used by general.php, user-defined tabels
// general.php - author: Ethan Garner, Nico Stuurman <nicost@sf.net>
  /***************************************************************************
  * Copyright (c) 2002 by Ethan Garner, Nico Stuurman                        *
  * ------------------------------------------------------------------------ *
  *  Part of phplabware, a web-driven groupware suite for research labs      *
  *  This file contains classes and functions needed in general.php.         *
  *                                                                          *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

//// 
// !Displays information on the record's owner
// needs to be called within a table
function user_entry($id,$real_tablename) {
   global $db;
   $ownerid=get_cell($db,"$real_tablename","ownerid","id","$id");
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
function date_entry($id,$real_tablename) {
   global $db,$system_settings;

   $date=get_cell($db,$real_tablename,"date","id","$id");
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
// !Displays information in table in edit mode
function display_table_change($db,$tableid,$DB_DESNAME,$Fieldscomma,$pr_query,$num_p_r,$pr_curr_page,$page_array,$r=false) {
   global $nr_records,$max_menu_length,$USER,$LAYOUT;

   $tablename=get_cell($db,"tableoftables","tablename","id",$tableid);
   $real_tablename=get_cell($db,"tableoftables","real_tablename","id",$tableid);
   $first_record=($pr_curr_page - 1) * $num_p_r;
   $current_record=$first_record;
   $last_record=$pr_curr_page * $num_p_r;
   if (!$r)
      $r=$db->Execute($pr_query);
   $r->Move($first_record);
   // print all entries
   while (!($r->EOF) && $r && $current_record < $last_record)  {
      // Get required ID and title
      $id=$r->fields["id"];
      $title=$r->fields["title"];		
      $Allfields=getvalues($db,$real_tablename,$DB_DESNAME,$tableid,$Fieldscomma,id,$id);
      $may_write=may_write($db,$tableid,$id,$USER);

      // print start of row of selected record
      if ($current_record % 2) echo "<tr class='row_even' align='center'>\n";
         else echo "<tr class='row_odd' align='center'>\n";
      echo "<input type='hidden' name='chgj_".$id."' value=''>\n";
      $js="onChange='document.g_form.chgj_".$id.".value=\"Change\";document.g_form.submit()'";
      foreach($Allfields as $nowfield) {
         if ($nowfield[required]=="Y")
            $thestar="<sup style='color:red'>&nbsp;*</sup>";
         else
            $thestar=false;
         if ( ($nowfield["modifiable"]=="N") || !$may_write) {
            echo "<input type='hidden' name='$nowfield[name]_$id' value='$nowfield[values]'>\n";
            echo "<td>$nowfield[text]</td>\n";
         }
         elseif ($nowfield[datatype]=="text") {
     	    echo "<td><input type='text' name='$nowfield[name]_$id' value='$nowfield[values]' size=15 $js>$thestar</td>\n";
         }
         elseif ($nowfield[datatype]=="int") {
     	    echo "<td><input type='text' name='$nowfield[name]_$id' value='$nowfield[values]' size=8 $js>$thestar</td>\n";
         }
         elseif ($nowfield[datatype]=="float") {
     	    echo "<td><input type='text' name='$nowfield[name]_$id' value='$nowfield[values]' size=8 $js>$thestar</td>\n";
         }
         elseif ($nowfield[datatype]=="textlong") {
     	    echo "<td><input type='text' name='$nowfield[name]_$id' value='$nowfield[values]' size=15>$thestar</td>\n"; 
         }
         elseif ($nowfield[datatype]=="link") {
            echo "<td><input type='text' name='$nowfield[name]_$id' value='$nowfield[values]' size=15>$thestar</td>\n";
         }
         elseif ($nowfield[datatype]=="pulldown") {
            // get previous value	
            $rp=$db->Execute("SELECT typeshort,id FROM $nowfield[ass_t] ORDER BY sortkey");
            $text=$rp->GetMenu2("$nowfield[name]_$id",$nowfield[values],true,false,0,$js);
            echo "\n<td>$text $thestar</td>\n";
         }
         elseif ($nowfield[datatype]=="table") {
            // only display primary key here
            if (!$nowfield["ass_local_key"]) { 
               $text=false;
               // get previous value	
               if ($nowfield[ass_column_name] && $nowfield[ass_table_name]) { 
                  $rt=$db->Execute("SELECT $nowfield[ass_column_name],id FROM $nowfield[ass_table_name]");
                  $text=$rt->GetMenu2("$nowfield[name]_$id",$nowfield[values],true,false,0,$js);
               }
               echo "<td>$text $thestar</td>\n";
            }
            else
               echo "<td>$nowfield[text] $thestar</td>\n";
         }
	 elseif ($nowfield[datatype]=="textlarge") {
	    echo "<td colspan=6><textarea name='$nowfield[name]_$id' rows='5' cols='100%'>$nowfield[values]</textarea>$thestar</td>\n";
	 }
         else
            echo "<td>$nowfield[text]</td>\n";
      }

      echo "<td align='center'>&nbsp;\n";  
      echo "<input type=\"submit\" name=\"view_" . $id . "\" value=\"View\">\n";
      if ($may_write) {
         echo "<input type=\"submit\" name=\"chg_" . $id . "\" value=\"Change\">\n";
         $delstring = "<input type=\"submit\" name=\"del_" . $id . "\" value=\"Remove\" ";
         $delstring .= "Onclick=\"if(confirm('Are you sure that you want to remove record $title?'))";
         $delstring .= "{return true;}return false;\">"; 
         echo "$delstring\n";
      }
      echo "</td>\n";
      echo "</tr>\n";
      $r->MoveNext();
      $current_record++;
   }
   // Add Record button
   if (may_write($db,$tableid,false,$USER)) {
      echo "<tr><td colspan=20 align='center'>";
      echo "<input type=\"submit\" name=\"add\" value=\"Add Record\">";
      echo "</td></tr>";
   }

   echo "</table>\n";
   next_previous_buttons($page_array);
   echo "</form>\n";
}


///////////////////////////////////////////////////////////
//// 
// !Displays all information within the table
function display_table_info($db,$tableid,$DB_DESNAME,$Fieldscomma,$pr_query,$num_p_r,$pr_curr_page,$page_array,$r=false) {
   global $nr_records,$max_menu_length,$USER,$LAYOUT;

   $tablename=get_cell($db,"tableoftables","tablename","id",$tableid);
   $real_tablename=get_cell($db,"tableoftables","real_tablename","id",$tableid);
   $first_record=($pr_curr_page - 1) * $num_p_r;
   $current_record=$first_record;
   $last_record=$pr_curr_page * $num_p_r;
   if (!$r)
      $r=$db->Execute($pr_query);
   $r->Move($first_record);
   // print all entries
   while (!($r->EOF) && $r && ($current_record < $last_record) )  {
      // Get required ID and title
      $id=$r->fields["id"];
      $title=$r->fields["title"];		
      $Allfields=getvalues($db,$real_tablename,$DB_DESNAME,$tableid,$Fieldscomma,id,$id);
      // print start of row of selected group
      if ($current_record % 2) echo "<tr class='row_odd' align='center'>\n";
         else echo "<tr class='row_even' align='center'>\n";
  
      foreach($Allfields as $nowfield) 
         echo "<td>$nowfield[text]</td>\n"; 

      echo "<td align='center'>&nbsp;\n";  
      echo "<input type=\"submit\" name=\"view_" . $id . "\" value=\"View\">\n";
      if (may_write($db,$tableid,$id,$USER)) {
         echo "<input type=\"submit\" name=\"mod_" . $id . "\" value=\"Modify\">\n";
         $delstring = "<input type=\"submit\" name=\"del_" . $id . "\" value=\"Remove\" ";
         $delstring .= "Onclick=\"if(confirm('Are you sure that you want to remove record $title?'))";
         $delstring .= "{return true;}return false;\">"; 
         echo "$delstring\n";
      }
      echo "</td>\n";
      echo "</tr>\n";
      $r->MoveNext();
      $current_record++;
   }
   // Add Record button
   if (may_write($db,$tableid,false,$USER)) {
      echo "<tr><td colspan=20 align='center'>";
      echo "<input type=\"submit\" name=\"add\" value=\"Add Record\">";
      echo "</td></tr>";
   }

   echo "</table>\n";
   next_previous_buttons($page_array);
   echo "</form>\n";
}

///////////////////////////////////////////////////////////
////
// !Display a record in a nice format
function display_record($db,$Allfields,$id,$tablename,$real_tablename) {
   global $PHP_SELF, $md;

   echo "<table border=0 align='center'>\n";
   $count=0;
   echo "<tr>\n";
   foreach ($Allfields as $nowfield) {
      //see if display_table is set
      if ($nowfield[display_record]=="Y") {
         if ($count && !($count % 2))
            echo "</tr><tr>\n";
         if ($nowfield[datatype]=="textlong") {
            $textlarge=nl2br(htmlentities($nowfield[values]));
            echo "<th>$nowfield[label]</th><td colspan=2>$textlarge</td>\n";
            //echo "<tr><th>$nowfield[label]</th><td colspan=6>$textlarge</td>\n";
            //echo "</tr>\n";
         }
         elseif ($nowfield[datatype]=="file") {
            $files=get_files($db,$tablename,$id,$nowfield["columnid"]);
            if ($files) { 
               echo "<th>Files:</th>\n<td colspan=5>";
               for ($i=0;$i<sizeof($files);$i++)  {
                  echo $files[$i]["link"]."&nbsp;&nbsp;(<i>".$files[$i]["name"]."</i>, ".$files[$i]["type"];
                  echo " file, ".$files[$i]["size"].")<br>\n";
               }
               echo "<td>\n";
               //echo "<td></tr>\n";
            }				
         }
         // most datatypes are handled in getvalues
         else {
            //echo "<tr><th>$nowfield[label]</th>\n";
            //echo "<td colspan=2>$nowfield[text]</td></tr>\n";
            echo "<th>$nowfield[label]</th>\n";
            echo "<td colspan=2>$nowfield[text]</td>\n";
         }
         $count++;
      }
   }
   user_entry($id,$real_tablename);
   date_entry($id,$real_tablename);
   make_link($id,$tablename);
   echo "<form method='post' action='$PHP_SELF?tablename=$tablename&".SID."'>\n";
   echo "<input type='hidden' name='md' value='$md'>\n";
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
function display_add($db,$tableid,$real_tablename,$tabledesc,$Allfields,$id,$namein,$system_settings) { 
   global $PHP_SELF, $db_type, $md, $USER;

   $tablename=get_cell($db,"tableoftables","tablename","id",$tableid);
   $dbstring=$PHP_SELF;$dbstring.="?";$dbstring.="tablename=$tablename&";
   echo "<form method='post' id='protocolform' enctype='multipart/form-data' action='$dbstring";
	?><?=SID?>'><?php

   if (!$magic)
      $magic=time();
   echo "<input type='hidden' name='magic' value='$magic'>\n";
   echo "<input type='hidden' name='md' value='$md'>\n";
   echo "<table border=0 align='center'>\n";   
   if ($id) {
      echo "<tr><td colspan=5 align='center'><h3>Modify $tablename entry <i>$namein</i></h3></td></tr>\n";
      echo "<input type='hidden' name='id' value='$id'>\n";
   }
   else {
      echo "<tr><td colspan=5 align='center'><h3>New $tablename entry</h3></td></tr>\n";
   }
   echo "<table border=0 align='center'>\n<tr align='center'>\n<td colspan=2></td>\n";
   foreach ($Allfields as $nowfield) {
      //see if display_record is set
      if ( (($nowfield["display_record"]=="Y") || ($nowfield["display_table"]=="Y")) ) {
         if ($nowfield["modifiable"]=="N") {
            echo "<input type='hidden' name='$nowfield[name]' value='$nowfield[values]'>\n";
            if ($nowfield[text] && $nowfield[text]!="" && $nowfield[text]!=" ") {
               echo "<tr><th>$nowfield[label]:</th>"; 
               echo "<td>$nowfield[text]</td></tr>\n";
            }
         }
         elseif ($nowfield[datatype]=="text" || $nowfield[datatype]=="int" || $nowfield[datatype]=="float") {
            echo "<tr><th>$nowfield[label]:"; 
            if ($nowfield[required]=="Y") {
               echo "<sup style='color:red'>&nbsp;*</sup>";
            }
            echo "</th>\n";
            if ($nowfiel[datatype]=="text")
               $size=60;
            else
               $size=10;
     	    echo "<td><input type='text' name='$nowfield[name]' value='$nowfield[values]' size=60> </td>\n</tr>";
         }
         elseif ($nowfield[datatype]=="textlong") {
            echo "<tr><th>$nowfield[label]:";
            if ($nowfield[required]=="Y") 
               echo "<sup style='color:red'>&nbsp;*</sup>";
     	    echo "<td><textarea name='$nowfield[name]' rows='5' cols='100%' value='$nowfield[values]'>$nowfield[values]</textarea></td></tr>\n";     	     
         }
         elseif ($nowfield[datatype]=="link") {
            echo "<tr><th>$nowfield[label] (http link)";
            if ($nowfield[required]=="Y")
               echo "<sup style='color:red'>&nbsp;*</sup>";
            echo "<td><input type='text' name='$nowfield[name]' value='$nowfield[values]' size=60> </td>\n</tr>";
         }
         elseif ($nowfield[datatype]=="pulldown") {
            // get previous value	
            $r=$db->Execute("SELECT typeshort,id FROM $nowfield[ass_t] ORDER BY sortkey");
            $text=$r->GetMenu2("$nowfield[name]",$nowfield[values],true,false);
            echo "<tr><th>$nowfield[label]";
            if ($USER["permissions"] && $LAYOUT) {
               echo "<a href='$PHP_SELF?tablename=$tablename&edit_type=$nowfield[ass_t]&<?=SID?>'>";
               echo "<FONT size=1 color='#00ff00'> <small>(edit)</small></font></a>";
            }
            if ($nowfield[required]=="Y")
               echo"<sup style='color:red'>&nbsp;*</sup>";
            echo "</th>\n<td>$text<br>";
            echo "</tr>";
         }
         elseif ($nowfield[datatype]=="table") {
            // only display primary key here
            if (!$nowfield["ass_local_key"]) { 
               // get previous value	
               $r=$db->Execute("SELECT $nowfield[ass_column_name],id FROM $nowfield[ass_table_name]");
               $text=$r->GetMenu2("$nowfield[name]",$nowfield[values],true,false);
               echo "<tr><th>$nowfield[label]";
               if ($nowfield[required]=="Y")
                  echo"<sup style='color:red'>&nbsp;*</sup>";
               echo "</th>\n<td>$text<br>";
               echo "</tr>";
            }
         }
	 if ($nowfield[datatype]=="textlarge") {
	    echo "<tr><th>$nowfield[name]";
            if ($nowfield[required]=="Y")
	       echo"<sup style='color:red'>&nbsp;*</sup>";
	    echo "</th><td colspan=6><textarea name='$nowfield[name]' rows='5' cols='100%'>$nowfield[values]</textarea></td></tr>\n";
	 }
	 if ($nowfield[datatype]=="file") {
	    $files=get_files($db,$real_tablename,$id,$nowfield["id"]);
	    echo "<tr>";
	    echo "<th>File:";
	    echo "</th>\n";
	    echo "<td colspan=4> <table border=0>";
	    for ($i=0;$i<sizeof($files);$i++)  {
	       echo "<tr><td colspan=2>".$files[$i]["link"];
	       echo "&nbsp;&nbsp;(<i>".$files[$i]["name"]."</i>, ".$files[$i]["type"]." file)</td>\n";
	       echo "<td><input type='submit' name='def_".$files[$i]["id"]."' value='Delete' Onclick=\"if(confirm('Are you sure the file ".$files[$i]["name"]." should be removed?')){return true;}return false;\"></td></tr>\n";
	    }
	    echo "<tr><th>Replace file(s) with</th>\n";
	    echo "<td>&nbsp;</td><td><input type='file' name='file[]' value='$filename'></td>\n";
	    echo "</tr></table></td></tr><br>\n\n";
	 }
      }
	
   }	
   echo "<td colspan=4>";
   show_access($db,$tableid,$id,$USER,$system_settings);
   echo "</td></tr>\n"; echo "<tr>";
   if ($id) $value="Modify Record"; 
   else $value="Add Record";

   // submit and clear buttons
   echo "<td colspan=7 align='center'><input type='submit' name='submit' value='$value'>\n";
   echo "&nbsp;&nbsp;<input type='submit' name='submit' value='Cancel'></td>\n";
   echo "</tr>\n</table>\n</form>\n";

   //end of table
   $dbstring=$PHP_SELF;$dbstring.="?";$dbstring.="tablename=$tablename&";
   echo "<form method='post' id='protocolform' enctype='multipart/form-data' action='$dbstring";
?><?=SID?>'><?php

}

///////////////////////////////////////////////////////////////////////
////
// !Get all description table values out for a display
// Returns an array with lots of information on every column
// If qfield is set, database values for that record will be returned as well
function getvalues($db,$DBNAME,$DB_DESNAME,$tableid,$fields,$qfield=false,$field=false) {
   if ($qfield) {
      $r=$db->Execute("SELECT $fields FROM $DBNAME WHERE $qfield=$field"); 
      $rid=$db->Execute("SELECT id FROM $DBNAME WHERE $qfield=$field");
      $id=$rid->fields["id"];
   }
   $column=strtok($fields,",");
   $Allfields=array();
   while ($column) {
      if($column!="id") {
         if ($r)
            ${$column}["values"]= $r->fields[$column];
         $rb=$db->CacheExecute(2,"SELECT id,label,datatype,display_table,display_record,associated_table,associated_column,associated_local_key,required,link_first,link_last,modifiable FROM $DB_DESNAME WHERE columnname='$column'");
         ${$column}["name"]=$column;
         ${$column}["columnid"]=$rb->fields["id"];
         ${$column}["label"]=$rb->fields["label"];
         ${$column}["datatype"]=$rb->fields["datatype"];
         ${$column}["display_table"]=$rb->fields["display_table"];
         ${$column}["display_record"]=$rb->fields["display_record"];
         ${$column}["ass_t"]=$rb->fields["associated_table"];
         ${$column}["ass_column"]=$rb->fields["associated_column"];
         ${$column}["ass_local_key"]=$rb->fields["associated_local_key"];
         ${$column}["required"]=$rb->fields["required"];
         ${$column}["modifiable"]=$rb->fields["modifiable"];
         if ($rb->fields["datatype"]=="table") {
            ${$column}["ass_table_desc_name"]=get_cell($db,"tableoftables","table_desc_name","id",$rb->fields["associated_table"]);
            ${$column}["ass_table_name"]=get_cell($db,"tableoftables","real_tablename","id",$rb->fields["associated_table"]);
            ${$column}["ass_column_name"]=get_cell($db,${$column}["ass_table_desc_name"],"columnname","id",$rb->fields["associated_column"]);
	 }
         if ($id) {
            if ($rb->fields["datatype"]=="table") {
               if ($rb->fields["associated_local_key"]) {
                  ${$column}["ass_local_column_name"]=get_cell($db,$DB_DESNAME,"columnname","id",$rb->fields["associated_local_key"]);
                  ${$column}["values"]=get_cell($db,$DBNAME,${$column}["ass_local_column_name"],"id",$id); 
               }
               $text=false;
               if (${$column}["values"])
                  $text=get_cell($db,${$column}["ass_table_name"],${$column}["ass_column_name"],"id",${$column}["values"]); 
               if (!$text)
                  $text="&nbsp;";
               ${$column}["text"]=$text;
            }
            elseif ($rb->fields["datatype"]=="link") {
               if (${$column}["values"])
                  ${$column}["text"]="<a href='".${$column}["values"]."' target='_blank'>link</a>";
            }
            elseif ($rb->fields["datatype"]=="pulldown") {
               ${$column}["text"]=get_cell($db,${$column}["ass_t"],"typeshort","id",${$column}["values"]); 
            }
            elseif ($rb->fields["datatype"]=="textlong") {
               if (${$column}["values"]=="")
                  ${$column}["text"]="no";
               else 
                  ${$column}["text"]="yes";
            }
            elseif ($rb->fields["datatype"]=="file") {
               $tbname=get_cell($db,"tableoftables","tablename","id",$tableid);
               $files=get_files($db,$tbname,$id,${$column}["columnid"],3);
               if ($files) 
                  for ($i=0;$i<sizeof($files);$i++)
                     ${$column}["text"].=$files[$i]["link"];
            }
            else
               ${$column}["text"]=${$column}["values"];

            if ($rb->fields["link_first"] && ${$column}["values"]) {
               ${$column}["text"]="<a href='".$rb->fields["link_first"].${$column}["text"].$rb->fields["link_last"]."'>".${$column}["text"]."</a>\n";
            }
 
            if (! isset(${$column}["text"]) || strlen(${$column}["text"])<1 )
               ${$column}["text"]="&nbsp;";
         }
      }
      array_push ($Allfields, ${$column});
      $column=strtok(",");
   }		
   if (function_exists("plugin_getvalues"))
      plugin_getvalues($db,$Allfields,$id);
   return $Allfields;
}


//////////////////////////////////////////////////////
////
// !SQL where search that returns a comma delimited string
function comma_array_SQL_where($db,$tablein,$column,$searchfield,$searchval)
	{
	$tempa=array();
	$rs = $db->Execute("select $column from $tablein where $searchfield='$searchval' order by sortkey");

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
function comma_array_SQL($db,$tablein,$column,$where=false) {
	$tempa=array();
	$rs = $db->Execute("select $column from $tablein $where order by sortkey");
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
function check_g_data ($db,&$field_values, $DB_DESNAME,$modify=false) {

   $rs = $db->Execute("select columnname,datatype from $DB_DESNAME where required ='Y' and (datatype != 'file')");
   while (!$rs->EOF) {
      $fieldA=$rs->fields[0];
      if (!$field_values["$fieldA"]) {
         echo "<h3 color='red'>Please enter all starred fields.</center></h3>";
	 return false;
      }
      $rs->MoveNext();
   }
   // make sure ints and floats are correct
   $rs = $db->Execute("select columnname,datatype from $DB_DESNAME where datatype IN ('int','float')");
   while ($rs && !$rs->EOF) {
      $fieldA=$rs->fields[0];
      if (isset($field_values["$fieldA"]) && (strlen($field_values[$fieldA]) >0)) {
         if ($rs->fields[1]=="int")
            $field_values["$fieldA"]=(int)$field_values["$fieldA"];
         elseif ($rs->fields[1]=="float")
            $field_values["$fieldA"]=(float)$field_values["$fieldA"];
      }
      $rs->MoveNext();
   }

   // Hooray, the first call to a plugin function!!
   if (function_exists("plugin_check_data")) {
      if (!plugin_check_data($db,$field_values,$DB_DESNAME,$modify))
         return false;
   }

   return true;
}


////
// !Prints a form with addition stuff
// $fields is a comma-delimited string with column names
// $field_values is hash with column names as keys
// $id=0 for a new entry, otherwise it is the id
function add_g_form ($db,$fields,$field_values,$id,$USER,$PHP_SELF,$system_settings,$real_tablename, $tableid,$DB_DESNAME) {
   if (!may_write($db,$tableid,$id,$USER)) 
      return false; 
   if ($id) {
   echo $nowfield["id"].".<br>";
	$Allfields=getvalues($db,$real_tablename,$DB_DESNAME,$tableid,$fields,id,$id);
	$namein=get_cell($db,$DBNAME,"title","id",$id);		
	display_add($db,$tableid,$real_tablename,$DB_DESNAME,$Allfields,$id,$namein,$system_settings);
   }    
   else {
	$Allfields=getvalues($db,$DBNAME,$DB_DESNAME,$tableid,$fields);
	display_add($db,$tableid,$real_tablename,$DB_DESNAME,$Allfields,$id,"",$system_settings);
   }
}

////
// !Shows a page with nice information on the record
function show_g($db,$fields,$id,$USER,$system_settings,$tableid,$real_tablename,$DB_DESNAME)  {
   $tablename=get_cell($db,"tableoftables","tablename","id",$tableid);
   if (!may_read($db,$tableid,$id,$USER))
       return false;
   $Allfields=getvalues($db,$real_tablename,$DB_DESNAME,$tableid,$fields,id,$id);
   display_record($db,$Allfields,$id,$tablename,$real_tablename);
}
	
////
// !Tries to convert a MsWord file into html 
// It calls wvHtml.  
// Version >= 0.70 have to be called differently
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
      // version of wvHtml >= 0.7 have to be called differently:
      if (@is_readable($temp) || filesize($temp) < 1) {
         $command="$word2html --targetdir=".$system_settings["tmpdir"]." \"$filepath\" $converted_file";
         $result=exec($command);
      } 
      if (@is_readable($temp) && filesize($temp)) {
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
      else
         @unlink($temp);
   }
}



?>
