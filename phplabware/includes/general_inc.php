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
function display_table_change($db,$tableinfo,$Fieldscomma,$pr_query,$num_p_r,$pr_curr_page,$page_array,$r=false) {
   global $nr_records,$USER,$LAYOUT,$HTTP_SESSION_VARS;

   $first_record=($pr_curr_page - 1) * $num_p_r;
   $current_record=$first_record;
   $last_record=$pr_curr_page * $num_p_r;
   if (!$r)
      $r=$db->Execute($pr_query);
   $r->Move($first_record);
   if ($HTTP_SESSION_VARS["javascript_enabled"]) {
      echo "<script language='JavaScript'><!--window.name='mainwin';--></script>\n";
   }
   // print all entries
   while (!($r->EOF) && $r && $current_record < $last_record)  {
      // Get required ID and title
      $id=$r->fields["id"];
      $title=$r->fields["title"];		
      $Allfields=getvalues($db,$tableinfo,$Fieldscomma,id,$id);
      $may_write=may_write($db,$tableinfo->id,$id,$USER);

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
         elseif ($nowfield['datatype']=='int' || $nowfield['datatype']=='sequence' || $nowfield['datatype']=='float') {
     	    echo "<td><input type='text' name='$nowfield[name]_$id' value='$nowfield[values]' size=8 $js>$thestar</td>\n";
         }
/*
         elseif ($nowfield[datatype]=="float") {
     	    echo "<td><input type='text' name='$nowfield[name]_$id' value='$nowfield[values]' size=8 $js>$thestar</td>\n";
         }
*/
         elseif ($nowfield['datatype']=='textlong') {
     	    echo "<td><input type='text' name='$nowfield[name]_$id' value='$nowfield[values]' size=15>$thestar</td>\n"; 
         }
         elseif ($nowfield['datatype']=='link') {
            echo "<td><input type='text' name='$nowfield[name]_$id' value='$nowfield[values]' size=15>$thestar</td>\n";
         }
         elseif ($nowfield['datatype']=='pulldown') {
            // get previous value	
            $rp=$db->Execute("SELECT typeshort,id FROM $nowfield[ass_t] ORDER BY sortkey");
            $text=$rp->GetMenu2("$nowfield[name]_$id",$nowfield[values],true,false,0,$js);
            echo "\n<td>$text $thestar</td>\n";
         }
         elseif ($nowfield['datatype']=='table') {
            // only display primary key here
            if (!$nowfield['ass_local_key']) { 
               $text=false;
               // get previous value	
               if ($nowfield['ass_column_name'] && $nowfield['ass_table_name']) { 
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
      if ($HTTP_SESSION_VARS["javascript_enabled"]) {
         $jscript=" onclick='MyWindow=window.open (\"general.php?tablename=".$tableinfo->name."&showid=$id&jsnewwindow=true\",\"view\",\"scrollbar=yes,resizable=yes,width=600,height=400\")'";
         echo "<input type=\"button\" name=\"view_" . $id . "\" value=\"View\" $jscript>\n";
      }
      else
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
   if (may_write($db,$tableinfo->id,false,$USER)) {
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
function display_table_info($db,$tableinfo,$Fieldscomma,$pr_query,$num_p_r,$pr_curr_page,$page_array,$r=false) {
   global $nr_records,$USER,$LAYOUT,$HTTP_SESSION_VARS;

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
      $Allfields=getvalues($db,$tableinfo,$Fieldscomma,id,$id);
      // print start of row of selected group
      if ($current_record % 2) echo "<tr class='row_odd' align='center'>\n";
         else echo "<tr class='row_even' align='center'>\n";
  
      foreach($Allfields as $nowfield) 
         if ($nowfield['link'])
            echo "<td>{$nowfield['link']}</td>\n";
         else
            echo "<td>{$nowfield['text']}</td>\n"; 

      echo "<td align='center'>&nbsp;\n";  
      if ($HTTP_SESSION_VARS["javascript_enabled"]) {
         $jscript=" onclick='MyWindow=window.open (\"general.php?tablename=".$tableinfo->name."&showid=$id&jsnewwindow=true\",\"view\",\"scrollbar=yes,resizable=yes,width=600,height=400\")'";
         echo "<input type=\"button\" name=\"view_" . $id . "\" value=\"View\" $jscript>\n";
      }
      else
         echo "<input type=\"submit\" name=\"view_" . $id . "\" value=\"View\">\n";
      if (may_write($db,$tableinfo->id,$id,$USER)) {
         echo "<input type=\"submit\" name=\"mod_" . $id . "\" value=\"Modify\">\n";
         $delstring = "<input type=\"submit\" name=\"del_" . $id . "\" value=\"Remove\" ";
	 $jstitle=str_replace("'"," ",$title);
         $delstring .= "Onclick=\"if(confirm('Are you sure that you want to remove record $jstitle?'))";
         $delstring .= "{return true;}return false;\">"; 
         echo "$delstring\n";
      }
      echo "</td>\n";
      echo "</tr>\n";
      $r->MoveNext();
      $current_record++;
   }
   // Add Record button
   if (may_write($db,$tableinfo->id,false,$USER)) {
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
function display_record($db,$Allfields,$id,$tableinfo,$backbutton=true) 
{
   global $PHP_SELF, $md;

   echo "&nbsp;<br>\n";
   echo "<table border=0 align='center'>\n";
   $count=0;
   echo "<tr>\n";
   foreach ($Allfields as $nowfield) {
      //Only show the entry when display_record is set
      if ($nowfield[display_record]=="Y") {
         // We display the fieldsin two columns
         if ($count && !($count % 2))
            echo "</tr>\n<tr>\n";
         if ($nowfield[datatype]=="textlong") {
            $textlarge=nl2br(htmlentities($nowfield[values]));
            echo "<th>$nowfield[label]</th><td colspan=2>$textlarge</td>\n";
         }
         elseif ($nowfield["datatype"]=="file" || $nowfield["datatype"]=="image") {
            $files=get_files($db,$tableinfo->name,$id,$nowfield["columnid"],0,"big");
            if ($files) { 
               echo "<th>$nowfield[label]:</th>\n<td colspan=5>";
               for ($i=0;$i<sizeof($files);$i++)  {
                  echo $files[$i]["link"]."&nbsp;&nbsp;(<i>".$files[$i]["name"]."</i>, ".$files[$i]["type"];
                  echo " file, ".$files[$i]["size"].")<br>\n";
               }
               echo "<td>\n";
            }
            // to keep odd and even fields right
            else
               $count--;
         }
         // most datatypes are handled in getvalues
         else {
            echo "<th>$nowfield[label]</th>\n";
            echo "<td colspan=2>$nowfield[text]</td>\n";
         }
         $count++;
      }
   }
   echo "</tr>\n";
   make_link($id,$tableinfo->name);
   show_reports($db,$tableinfo,$id);
   if (function_exists ("plugin_display_show")){
      plugin_display_show ($db,$Allfields,$id);
      return $Allfields;
   } 
   echo "<form method='post' action='$PHP_SELF?tablename=".$tableinfo->name."&".SID."'>\n";
   echo "<input type='hidden' name='md' value='$md'>\n";
   if ($backbutton) {
      echo "<tr>\n<td colspan=8 align='center'>";
      echo "<input type='submit' name='submit' value='Back'></td>\n</tr>\n";
   }
   else
      echo "<tr><td colspan=8 align='center'>&nbsp;<br><button onclick='self.close();window.opener.focus();' name='Close' value='close'>Close</button></td></tr>\n";
   echo "</table>";
}

///////////////////////////////////////////////////////////
////
// !make a nice link to the record
function make_link($id,$DBNAME) {
   global $PHP_SELF,$system_settings;
   echo "<tr><th>Link:</th><td colspan=7><a href='$PHP_SELF?tablename=$DBNAME&showid=$id&".SID;
   //echo "'>".$system_settings["baseURL"].getenv("SCRIPT_NAME")."?tablename=$DBNAME&showid=$id</a></td></tr>\n";
   echo "'>".$system_settings["baseURL"].$PHP_SELF."?tablename=$DBNAME&showid=$id</a></td></tr>\n";
}


///////////////////////////////////////////////////////////
////
// ! Make dropdown menu with available templates
// When one is chosen, open the formatted record in a new window
function show_reports($db,$tableinfo,$recordid) {
   $r=$db->Execute("SELECT id,label FROM reports WHERE tableid=".$tableinfo->id);
   if ($r && !$r->EOF) {
      $menu="<tr><th>Report:</th>\n";
      $menu.="<td><select name='reportlinks' onchange='linkmenu(this)'>\n";
      $menu.="<option value=''>---Reports---</option>\n";
      while (!$r->EOF) {
         $url="target "."report.php?tablename=".$tableinfo->name."&reportid=".$r->fields["id"]."&recordid=$recordid";
         $menu.="<option value='$url'>".$r->fields["label"]."</option>\n";
         $r->MoveNext();
      }
      $menu.="</select>\n";
      $menu.="</td></tr>\n";
      echo $menu;
   }
}
///////////////////////////////////////////////////////////
////
// !display addition and modification form
function display_add($db,$tableinfo,$Allfields,$id,$namein,$system_settings) { 
   global $PHP_SELF,$db_type,$md,$max_menu_length,$USER,$LAYOUT,$HTTP_POST_VARS;
   
   $dbstring=$PHP_SELF;$dbstring.="?";$dbstring.="tablename=".$tableinfo->name."&";
   echo "<form method='post' id='protocolform' enctype='multipart/form-data' name='form' action='$dbstring";
	?><?=SID?>'><?php

   if (!$magic)
      $magic=time();
   echo "<input type='hidden' name='magic' value='$magic'>\n";
   echo "<input type='hidden' name='md' value='$md'>\n";
   echo "<table border=0 align='center'>\n";   
   if ($id) {
      echo "<tr><td colspan=5 align='center'><h3>Modify ".$tableinfo->label." entry <i>$namein</i></h3></td></tr>\n";
      echo "<input type='hidden' name='id' value='$id'>\n";
   }
   else {
      echo "<tr><td colspan=5 align='center'><h3>New ".$tableinfo->label." entry</h3></td></tr>\n";
   }
   echo "<table border=0 align='center'>\n<tr align='center'>\n<td colspan=2></td>\n";
   foreach ($Allfields as $nowfield) {
      // see if display_record is set
      if ( (($nowfield['display_record']=="Y") || ($nowfield['display_table']=='Y')) ) {
         // To persist between multiple invocation, grab POST vars 
         if ($nowfield['modifiable']=='Y' && isset($HTTP_POST_VARS[$nowfield['name']]) && $HTTP_POST_VARS[$nowfield['name']] && isset($HTTP_POST_VARS['submit']))
            $nowfield['values']=$HTTP_POST_VARS[$nowfield['name']];
         if ($nowfield['modifiable']=='N' && $nowfield['datatype']!='sequence') {
            echo "<input type='hidden' name='$nowfield[name]' value='$nowfield[values]'>\n";
            if ($nowfield['text'] && $nowfield['text']!="" && $nowfield['text']!=" ") {
               echo "<tr><th>$nowfield[label]:</th>"; 
               echo "<td>$nowfield[text]";
            }
         }
         elseif ($nowfield['modifiable']=='Y' && ($nowfield['datatype']=='text' || $nowfield['datatype']=='int' || $nowfield['datatype']=='float' || $nowfield['datatype']=='date')) {
            echo "<tr><th>$nowfield[label]:"; 
            if ($nowfield['required']=='Y') {
               echo "<sup style='color:red'>&nbsp;*</sup>";
            }
            echo "</th>\n";
            if ($nowfield[datatype]=="text")
               $size=60;
            else
               $size=10;
     	    echo "<td><input type='text' name='$nowfield[name]' value='$nowfield[text]' $size>";
         }
	 elseif ($nowfield['datatype']=='sequence') {
	    if (!$nowfield['text']) {
	       // find the highest sequence and return that plus one
	       $rmax=$db->Execute("SELECT MAX (${nowfield['name']}) AS ${nowfield['name']} FROM ".$tableinfo->realname);
	       $newseq=$rmax->fields[0]+1;
	    }
	    else
	       $newseq=$nowfield['text'];
            echo "<input type='hidden' name='$nowfield[name]' value='$newseq'>\n";
            echo "<tr><th>$nowfield[label]:"; 
            if ($nowfield['required']=='Y') {
               echo "<sup style='color:red'>&nbsp;*</sup>";
	    }
	    echo "</th>\n";
            if ($nowfield['modifiable']=='N') {
               echo "<td>$newseq";
	    }
	    else
     	       echo "<td><input type='text' name='$nowfield[name]' value='$newseq' 10>";
	 }
         elseif ($nowfield['datatype']=='textlong') {
            echo "<tr><th>$nowfield[label]:";
            if ($nowfield[required]=='Y') 
               echo "<sup style='color:red'>&nbsp;*</sup>";
     	    echo "<td><textarea name='$nowfield[name]' rows='5' cols='100%' value='$nowfield[values]'>$nowfield[values]</textarea>";
         }
         elseif ($nowfield['datatype']=='link') {
            echo "<tr><th>$nowfield[label] (http link):";
            if ($nowfield['required']=='Y')
               echo "<sup style='color:red'>&nbsp;*</sup>";
            echo "<td><input type='text' name='$nowfield[name]' value='$nowfield[values]' size=60>";
         }
         elseif ($nowfield['datatype']=='pulldown') {
            // get previous value	
            $r=$db->Execute("SELECT typeshort,id FROM $nowfield[ass_t] ORDER BY sortkey");
            $text=$r->GetMenu2("$nowfield[name]",$nowfield[values],true,false);
            echo "<tr><th>$nowfield[label]:";
            if ($nowfield['required']=='Y')
               echo"<sup style='color:red'>&nbsp;*</sup>";
            echo "</th>\n<td>";
            if ($USER["permissions"] & $LAYOUT) {
               $jscript=" onclick='MyWindow=window.open (\"general.php?tablename=".$tableinfo->name."&edit_type=$nowfield[ass_t]&jsnewwindow=true&formname=form&selectname=$nowfield[name]".SID."\",\"type\",\"scrollbar=yes,resizable=yes,width=600,height=400\");MyWindow.focus()'";
               echo "<input type='button' name='edit_button' value='Edit $nowfield[label]' $jscript><br>\n";
            }
            echo "$text<br>";
         }
         elseif ($nowfield['datatype']=='table') {
            // only display primary key here
            if (!$nowfield['ass_local_key']) { 
               // get previous value	
               $r=$db->Execute("SELECT COUNT(id) FROM {$nowfield['ass_table_name']}");
               if ($r->fields[0] > $max_menu_length) {
                  $text="<input type='text' name='{$nowfield['name']}' value='{$nowfield['text']}'>";
               }
               else {
                  $r=$db->Execute("SELECT $nowfield[ass_column_name],id FROM $nowfield[ass_table_name] ORDER BY {$nowfield['ass_column_name']}");
               
                  $text=$r->GetMenu2("$nowfield[name]",$nowfield[values],true,false);
               }
               echo "<tr><th>$nowfield[label]:";
               if ($nowfield[required]=="Y")
                  echo"<sup style='color:red'>&nbsp;*</sup>";
               echo "</th>\n<td>$text<br>";
            }
         }
	 if ($nowfield['datatype']=='textlarge') {
	    echo "<tr><th>$nowfield[name]:";
            if ($nowfield['required']=='Y')
	       echo"<sup style='color:red'>&nbsp;*</sup>";
	    echo "</th><td colspan=6><textarea name='$nowfield[name]' rows='5' cols='100%'>$nowfield[values]</textarea>";
	 }
	 if ($nowfield["datatype"]=="file" || $nowfield["datatype"]=="image") {
	    $files=get_files($db,$tableinfo->name,$id,$nowfield["columnid"],0,"big");
	    echo "<tr>";
	    echo "<th>$nowfield[label]:</th>\n";
	    echo "</th>\n";
	    echo "<td colspan=4> <table border=0>";
	    for ($i=0;$i<sizeof($files);$i++)  {
	       echo "<tr><td colspan=2>".$files[$i]["link"];
	       echo "&nbsp;&nbsp;(<i>".$files[$i]["name"]."</i>, ".$files[$i]["type"]." file)</td>\n";
	       echo "<td><input type='submit' name='def_".$files[$i]["id"]."' value='Delete' Onclick=\"if(confirm('Are you sure the file ".$files[$i]["name"]." should be removed?')){return true;}return false;\"></td></tr>\n";
	    }
            if ($files)
	       echo "<tr><th>Replace ".$nowfield["datatype"]."(s) with</th>\n";
            else
	       echo "<tr><th>Upload ".$nowfield["datatype"]."</th>\n";
	    echo "<td>&nbsp;</td><td><input type='file' name='".$nowfield[name]."[]' value='$filename'></td>\n";
	    echo "</tr></table><br>\n\n";
	 }
      }
      if (function_exists("plugin_display_add"))
         plugin_display_add($db,$tableinfo->id,$nowfield);
      echo "</td></tr>\n";
  
	
   }	
   /* Call to a function that runs at the end when adding a new record*/   
   if ((function_exists("plugin_display_add_post")) && (!($id))){
      plugin_display_add_post($db,$tableinfo->id);
   }
         
   echo "<td colspan=4>";
   show_access($db,$tableinfo->id,$id,$USER,$system_settings);
   echo "</td></tr>\n"; echo "<tr>";
   if ($id) $value="Modify Record"; 
   else $value="Add Record";

   // submit and clear buttons
   echo "<td colspan=7 align='center'><input type='submit' name='submit' value='$value'>\n";
   echo "&nbsp;&nbsp;<input type='submit' name='submit' value='Cancel'></td>\n";
   echo "</tr>\n</table>\n</form>\n";

   //end of table
   $dbstring=$PHP_SELF;$dbstring.="?";$dbstring.="tablename=$tableinfo[name]&";
   echo "<form method='post' id='protocolform' enctype='multipart/form-data' action='$dbstring";
?><?=SID?>'><?php

}

///////////////////////////////////////////////////////////////////////
////
// !Get all description table values out for a display
// Returns an array with lots of information on every column
// If qfield is set, database values for that record will be returned as well
function getvalues($db,$tableinfo,$fields,$qfield=false,$field=false) {
   global $system_settings;
   if ($qfield) {
      $r=$db->Execute("SELECT $fields FROM $tableinfo->realname WHERE $qfield=$field"); 
      $rid=$db->Execute("SELECT id FROM $tableinfo->realname WHERE $qfield=$field");
      $id=$rid->fields["id"];
   }
   $columns=split(",",$fields);
   $Allfields=array();
   foreach ($columns as $column) {
      if($column!="id") {
         if ($r)
            ${$column}["values"]= $r->fields[$column];
         $rb=$db->CacheExecute(2,"SELECT id,label,datatype,display_table,display_record,associated_table,associated_column,associated_local_key,required,link_first,link_last,modifiable FROM $tableinfo->desname WHERE columnname='$column'");
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
            if ($rb->fields['datatype']=='table') {
               if ($rb->fields['associated_local_key']) {
                  ${$column}['ass_local_column_name']=get_cell($db,$tableinfo->desname,"columnname","id",$rb->fields["associated_local_key"]);
                  ${$column}['values']=get_cell($db,$tableinfo->realname,${$column}["ass_local_column_name"],"id",$id); 
               }
               $text=false;
               if (${$column}['values']) {
                  $asstableinfo=new tableinfo($db,${$column}['ass_table_name']);
                  $tmpvalue=getvalues($db,$asstableinfo,${$column}['ass_column_name'],'id',${$column}['values']);
                  ${$column}['link']="<a target=_ href=\"general.php?tablename={$asstableinfo->name}&showid=".${$column}['values']."\">{$tmpvalue[0]['text']}</a>\n";
                  $text=$tmpvalue[0]['text'];
               }
               if (!$text)
                  $text="&nbsp;";
               ${$column}['text']=$text;
            }
            elseif ($rb->fields['datatype']=='link') {
               if (${$column}['values'])
                  ${$column}['text']="<a href='".${$column}["values"]."' target='_blank'>link</a>";
            }
            elseif ($rb->fields['datatype']=='pulldown') {
               ${$column}['text']=get_cell($db,${$column}['ass_t'],'typeshort','id',${$column}['values']); 
            }
            elseif ($rb->fields['datatype']=='textlong') {
               if (${$column}['values']=="")
                  ${$column}['text']='no';
               else 
                  ${$column}['text']='yes';
            }
            elseif ($rb->fields['datatype']=='file' || $rb->fields['datatype']=='image') {
               $tbname=get_cell($db,'tableoftables','tablename','id',$tableinfo->id);
               $files=get_files($db,$tbname,$id,${$column}['columnid'],3);
               if ($files) 
                  for ($i=0;$i<sizeof($files);$i++)
                     ${$column}["text"].=$files[$i]['link'];

            }
            elseif ($rb->fields['datatype']=='user') {
               $rname=$db->Execute("SELECT firstname,lastname,email FROM users WHERE id=".${$column}["values"]);
               if ($rname && $rname->fields) {
                  if ($rname->fields['email'])
                     ${$column}['text']="<a href='mailto:".$rname->fields['email']."'>".$rname->fields['firstname']." ".$rname->fields['lastname']."</a>\n";
                  else
                     ${$column}['text']=$rname->fields['firstname']." ".$rname->fields['lastname']."\n";
               }
            }
            elseif ($rb->fields['datatype']=='date' && ${$column}['values']>0) {
               $dateformat=get_cell($db,'dateformats','dateformat','id',$system_settings['dateformat']);
               ${$column}['text']=date($dateformat,${$column}['values']);
            }
            else
               ${$column}['text']=${$column}['values'];

            if ($rb->fields['link_first'] && ${$column}['values']) {
               ${$column}['text']="<a href='".$rb->fields['link_first'].${$column}['text'].$rb->fields['link_last']."'>".${$column}['text']."</a>\n";
            }
 
            if (! isset(${$column}['text']) || strlen(${$column}['text'])<1 )
               ${$column}['text']='&nbsp;';
         }
      }
      array_push ($Allfields, ${$column});
   }
   if (function_exists("plugin_getvalues"))
      plugin_getvalues($db,$Allfields,$id,$tableinfo->id);
   return $Allfields;
}


//////////////////////////////////////////////////////////////////////
////  general functions
/****************************FUNCTIONS***************************/
////
// !Checks input data to addition
// returns false if something can not be fixed     
function check_g_data ($db,&$field_values, $DB_DESNAME,$modify=false) {
   $max_menu_length;

   // make sure all the required fields are there 
   $rs = $db->Execute("SELECT columnname,datatype FROM $DB_DESNAME where required='Y' and (datatype != 'file')");
   while (!$rs->EOF) {
      $fieldA=$rs->fields[0];
      if (!$field_values["$fieldA"]) {
         echo "<h3 color='red' align='center'>Please enter all fields marked with a <sup style='color:red'>&nbsp;*</sup>.</h3>";
	 return false;
      }
      $rs->MoveNext();
   }

   // make sure ints and floats are correct, try to set the UNIX date
   $rs = $db->Execute("SELECT columnname,datatype,associated_table,associated_column FROM $DB_DESNAME WHERE datatype IN ('int','float','table','date','sequence')");
   while ($rs && !$rs->EOF) {
      $fieldA=$rs->fields[0];
      if (isset($field_values["$fieldA"]) && (strlen($field_values[$fieldA]) >0)) {
         if ($rs->fields[1]=='int')
            $field_values["$fieldA"]=(int)$field_values["$fieldA"];
         elseif ($rs->fields[1]=='float')
            $field_values["$fieldA"]=(float)$field_values["$fieldA"];
         elseif ($rs->fields[1]=='table') {
            $source_tableinfo=new tableinfo($db,false,$rs->fields[2]);
            $rcount=$db->Execute("SELECT COUNT(id) FROM {$source_tableinfo->realname}");
            if ($rcount->fields[0] > $max_menu_length) {
               // go through some stuff to discover which item the user wants to link against.  Guess we should even ask for input if we are in doubt
               $source_columnname=get_cell($db,$source_tableinfo->desname,'columnname','id',$rs->fields[3]);
               $rtable=$db->Execute("SELECT id FROM {$source_tableinfo->realname} WHERE $source_columnname='{$field_values["$fieldA"]}'");
               $field_values["$fieldA"]=$rtable->fields[0];
            }
            else
               $field_values["$fieldA"]=(int)$field_values["$fieldA"];
         }
         elseif ($rs->fields[1]=='date') {
            $field_values["$fieldA"]=strtotime($field_values["$fieldA"]);
            if ($field_values["$fieldA"] < 0)
               $field_values["$fieldA"]="";
         }
         elseif ($rs->fields[1]=='sequence') {
            $field_values["$fieldA"]=(int)$field_values["$fieldA"];
	    if ($field_values["$fieldA"]<1)
	       $field_values["$fieldA"]=0;
         }   
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
function add_g_form ($db,$tableinfo,$field_values,$id,$USER,$PHP_SELF,$system_settings) {
   if (!may_write($db,$tableinfo->id,$id,$USER)) 
      return false; 
   if ($id) {
	$Allfields=getvalues($db,$tableinfo,$tableinfo->fields,id,$id);
	$namein=get_cell($db,$tableinfo->desname,"title","id",$id);		
	display_add($db,$tableinfo,$Allfields,$id,$namein,$system_settings);
   }    
   else {
	$Allfields=getvalues($db,$tableinfo,$tableinfo->fields);
	display_add($db,$tableinfo,$Allfields,$id,"",$system_settings);
   }
}

////
// !Shows a page with nice information on the record
function show_g($db,$tableinfo,$id,$USER,$system_settings,$backbutton=true)  {
   if (!may_read($db,$tableinfo,$id,$USER))
       return false;
   $Allfields=getvalues($db,$tableinfo,$tableinfo->fields,id,$id);
   display_record($db,$Allfields,$id,$tableinfo,$backbutton);
}
	
////
// !Tries to convert a MsWord file into html 
// It calls wvHtml.  
// When succesfull, the file is added to the database
// Returns id of uploaded file
function process_file($db,$fileid,$system_settings) {
   global $HTTP_POST_FILES,$HTTP_POST_VARS;
   $mimetype=get_cell($db,"files","mime","id",$fileid);
   if (!strstr($mimetype,"html")) {
      $word2html=$system_settings["word2html"];
      $wv_version=$system_settings["wvHtml_version"];
      $filepath=file_path($db,$fileid);
      if (!$filepath)
         return false;
      if ($wv_version<0.7) {
         $temp=$system_settings["tmpdir"]."/".uniqid("file");
         $command= "$word2html $filepath $temp";
         $result=exec($command);
      }
      // version of wvHtml >= 0.7 have to be called differently:
      //if (@is_readable($temp) || @filesize($temp) < 1) {
      else {
         $converted_file=uniqid("file");
         $command="$word2html --targetdir=".$system_settings["tmpdir"]." \"$filepath\" $converted_file";
         $result=exec($command);
	 $temp=$system_settings["tmpdir"]."/".$converted_file;
      } 
      if (@is_readable($temp) && filesize($temp)) {
         unset ($HTTP_POST_FILES);
         $r=$db->query ("SELECT filename,mime,title,tablesfk,ftableid,ftablecolumnid FROM files WHERE id=$fileid");
         if ($r && !$r->EOF) {
            $filename=$r->fields("filename");
            // change .doc to .html in a lousy way
            $filename=str_replace(".doc",".htm",$filename); 
            $mime="text/html";
            $type=substr(strrchr($mime,"/"),1);
            $size=filesize($temp);
            $id=$db->GenID("files_id_seq");
            $query="INSERT INTO files (id,filename,mime,size,title,tablesfk,ftableid,ftablecolumnid,type) VALUES ($id,'$filename','$mime','$size','".$r->fields("title")."','".$r->fields("tablesfk")."','".$r->fields("ftableid")."','".$r->fields("ftablecolumnid")."','$type')";
           if ($db->execute($query)) {
                $newloc=file_path($db,$id);
               `mv $temp '$newloc'`;
                return $id;
            }
            else
               unlink($temp); 
         }    
      }
      else
         @unlink($temp);
   }
   return false;
}


////
// !Indexes the content of the given file
// The file is converted to a text file (pdfs with ghost script,
// word files were already converted to html,html characters are stripped),
// all words are lowercased, it is checked whether an entry in the table words
// already exists, if not, it is added.  A relation to the word is made in 
// the table associated with the given column
function indexfile ($db,$tableinfo,$indextable,$recordid,$fileid,$htmlfileid) 
{
   return false;
   if (!$indextable)
      return false;
   // if the html file exists, we'll work with that one
   if ($htmlfileid) {
      $fp=fopen(file_path($db,$htmlfileid),"r");
      if ($fp) {
         while (!feof($fp)) {
            $filetext.=fgetss($fp,64000);
         }
         fclose($fp);
      }
      $filetext=strtolower($filetext);
      doindexfile ($db,$filetext,$htmlfileid,$indextable,$recordid,$pagenr);
   }
}

?>
