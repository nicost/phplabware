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

/**
 *  Displays information on the record's owner
 *
 * needs to be called within a table
 */
function user_entry($id,$real_tablename) {
   global $db;
   $ownerid=get_cell($db,$real_tablename,'ownerid','id',$id);
   $r=$db->Execute("SELECT firstname,lastname,email FROM users WHERE id=$ownerid");
   if ($r->fields['email'])  {
      echo "<tr><th>Submitted by: </th><td><a href='mailto:".$r->fields["email"]."'>";
      echo $r->fields['firstname']." ".$r->fields['lastname']."</a></td>\n";
   }
   else {
      echo "<tr><th>Submitted by: </th><td>".$r->fields['firstname']." ";
      echo $r->fields["lastname"] ."</td>\n";
   }
   echo "<td>&nbsp;</td>";
}



/**
 * * 
 *  Prints name and date
 *
 * Needs to be called within a table
 */
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


/**
 *  Displays searchbar in table view
 *
 * For data of type table, recursive calls are used
 * The ugly stuff with _POST could be done better
 * it would also be nicer if a string was returned instead of writing directly
 */
function searchfield ($db,$tableinfo,$nowfield,$_POST,$jscript) {
   global $USER;

   $LAYOUT=16;
   $column=strtok($tableinfo->fields,",");
   while ($column) {
      ${$column}=$_POST[$column];
      $column=strtok(",");
   }
   if ($nowfield['datatype']== 'link')
      echo "<td style='width: 10%'>&nbsp;</td>\n";
   // datatype of column ownerid is text (historical oversight...)
   elseif ($nowfield['name']=='ownerid') {
       //if ($list) {
      $rowners=$db->Execute("SELECT ownerid FROM $tableinfo->realname");
      while ($rowners && !$rowners->EOF) {
         $ownerids[]=$rowners->fields[0];
         $rowners->MoveNext();
      }
      if ($ownerids) {
          $ownerlist=implode(',',$ownerids);
      }
      if ($ownerlist) {   
         $rowners2=$db->Execute("SELECT lastname,id FROM users WHERE id IN ($ownerlist)");
          $text=$rowners2->GetMenu2("$nowfield[name]",${$nowfield[name]},true,false,0,"style='width: 80%' $jscript");
         echo "<td style='width:10%'>$text</td>\n";
      }   
      else
         echo "<td style='width:10%'>&nbsp;</td>\n";
    }
    elseif ($nowfield['datatype']=='int' || $nowfield['datatype']=='float' || $nowfield['datatype']=='sequence' || $nowfield['datatype']=='date') {
  	    echo  " <td style='width: 10%'><input type='text' name='$nowfield[name]' value='".${$nowfield[name]}."'size=5 align='middle'></td>\n";
    }
    elseif ($nowfield['datatype']== 'text' || $nowfield['datatype']=='file')
       echo  " <td style='width: 25%'><input type='text' name='$nowfield[name]' value='".${$nowfield[name]}."'size=7></td>\n";
    elseif ($nowfield['datatype']== 'textlong')
       echo  " <td style='width: 10%'><input type='text' name='$nowfield[name]' value='".${$nowfield[name]}."'size=8></td>\n";
    elseif ($nowfield['datatype']== 'pulldown' || $nowfield['datatype']=='mpulldown') {
      echo "<td style='width: 10%'>";
      $rpull=$db->Execute("SELECT typeshort,id from $nowfield[ass_t] ORDER by sortkey,type");
      if ($rpull)
         if ($nowfield['datatype']=='mpulldown')
            $text=$rpull->GetMenu2("$nowfield[name]",${$nowfield[name]},false,true,0,"style='width: 80%' align='left'");   
         else 
            $text=$rpull->GetMenu2("$nowfield[name]",${$nowfield[name]},true,false,0,"style='width: 80%' $jscript");   
      else
          $text="&nbsp;";
      echo "$text\n";
      // Draw a modify icon to let qualified users change the pulldown menus
      if ( ($USER['permissions'] & $LAYOUT) && $_SESSION['javascript_enabled'])  {
          $jscript2=" onclick='MyWindow=window.open (\"general.php?tablename=".$tableinfo->name."&amp;edit_type=$nowfield[ass_t]&amp;jsnewwindow=true&amp;formname=$formname&amp;selectname=$nowfield[name]".SID."\",\"type\",\"scrollbars,resizable,toolbar,status,menubar,width=600,height=400\");MyWindow.focus()'";
           echo "<A href=\"javascript:void(0)\" $jscript2> <img src=\"icons/edit_modify.png\" alt=\"modify {$nowfield['name']}\" title=\"modify {$nowfield['label']}\" border=\"0\"/></A>\n";
          //echo "<input type='button' name='edit_button' value='Edit $nowfield[label]' $jscript2><br>\n";
      }	 		 			
      echo "</td>\n";
   }
   elseif ($nowfield['datatype']== 'table') {
       $ass_tableinfo=new tableinfo ($db,$nowfield['ass_table_name'],false);
       $rasslk=$db->Execute("SELECT columnname FROM {$ass_tableinfo->desname} WHERE id={$nowfield['ass_column']}");
       $ass_Allfields=getvalues($db,$ass_tableinfo,$rasslk->fields[0]);
       // scary acks, their ugliness shows that we need to reorganize some stuff
       $ass_Allfields[0]['name']=$nowfield['name']; 
       $ass_tableinfo->fields="{$nowfield['name']}";
       searchfield($db,$ass_tableinfo,$ass_Allfields[0],$_POST,$jscript);
    }
    elseif ($nowfield["datatype"]=="image")
       echo "<td style='width: 10%'>&nbsp;</td>";
}

/**
 * *
 *  Generated comma separated list of columns based on view prefs
 *
 */
function viewlist($db,$tableinfo,$viewid) {
   global $USER;
   $r=$db->Execute("SELECT columnid FROM tableviews WHERE viewnameid=$viewid AND viewmode=1");
   while ($r && !$r->EOF) {
      $rb=$db->Execute("SELECT columnname,sortkey FROM {$tableinfo->desname} WHERE id={$r->fields[0]}");
      $list[$rb->fields[1]]=$rb->fields[0];
      $r->MoveNext();
   }
   //$r=$db->Execute("SELECT columnid FROM tableviews WHERE viewnameid=$viewid");
   ksort($list);
   reset($list);
   return implode (",",$list);
}

      
/**
 * *
 *  Generated menu with user-defined views
 *
 */
function viewmenu($db, $tableinfo,$viewid,$useronly=1,$jscript='OnChange="document.g_form.submit()"') {
   global $USER, $db_type;
   
   if ($useronly)
      $userreq="tableviews.userid={$USER['id']} AND";
   // first find views accessible to user
   if ($db_type=='mysql') 
      $r=$db->Execute("SELECT DISTINCT viewname,viewnames.viewnameid FROM viewnames LEFT JOIN tableviews ON viewnames.viewnameid=tableviews.viewnameid WHERE $userreq tableviews.tableid={$tableinfo->id} AND tableviews.viewmode=1");
   else
      $r=$db->Execute("SELECT viewname,viewnameid FROM viewnames WHERE viewnameid IN (SELECT viewnameid FROM tableviews WHERE $userreq tableid={$tableinfo->id} AND viewmode=1)"); 
   if ($r) {
      $viewname.= 'View: '.$r->GetMenu2('viewid',$viewid,true,false,0,$jscript);
   }
   if ($viewid)
      $viewidtext="&amp;viewid=$viewid";
   $viewname.="<a href='views.php?tablename={$tableinfo->name}$viewidtext'>Edit views</a>";

   return $viewname;
}



/**
 * * 
 *  Displays information in table in edit mode
 *
 */
function display_table_change($db,$tableinfo,$Fieldscomma,$pr_query,$num_p_r,$pr_curr_page,$page_array,$r=false) {
   global $nr_records,$max_menu_length,$USER,$LAYOUT,$_SESSION;

   $first_record=($pr_curr_page - 1) * $num_p_r;
   $current_record=$first_record;
   $last_record=$pr_curr_page * $num_p_r;
   if (!$r)
      $r=$db->Execute($pr_query);
   $r->Move($first_record);
   // print all entries
   while (!($r->EOF) && $r && $current_record < $last_record)  {
      // Get required ID and title
      $id=$r->fields['id'];
      $title=get_cell($db, $tableinfo->realname,'title','id',$r->fields['id']);		
      $Allfields=getvalues($db,$tableinfo,$Fieldscomma,id,$id);
      $may_write=may_write($db,$tableinfo->id,$id,$USER);

      // print start of row of selected record
      if ($current_record % 2) {
         echo "<tr class='row_even' align='center'>\n";
      } else {
         echo "<tr class='row_odd' align='center'>\n";
      }
      foreach($Allfields as $nowfield) {
         // Javascript code that uses XMLHTTPRequest to send requests to the server asynchronously
         $js="tellServer(\"actionMode.php\",$tableinfo->id,$id,\"{$nowfield['name']}\",\"{$nowfield['datatype']}\",this.value)";
         if ($nowfield['required']=='Y')
            $thestar="<sup style='color:red'>&nbsp;*</sup>";
         else
            $thestar=false;

         if ( ($nowfield['modifiable']!='Y') || !$may_write) {
            echo "<td><input type='hidden' name='{$nowfield['name']}_$id' value=\"" . str_replace('"','&quot;',$nowfield['values']) ."\">\n";
            echo "{$nowfield['text']}</td>\n";
         } elseif ($nowfield['datatype']=='text') {
            // for comfortable input calculate size and present accordingly
            $maxwidth=40;
            if (strlen($nowfield['values'])) {
                $rows=floor (strlen($nowfield['values']) / $maxwidth) + 1;
                if ($rows <= 1) {
                   $columns=(strlen($nowfield['values']) % $maxwidth) + 1;
                } else {
                   $columns = $maxwidth;
                }
            } else { // no pre-exsisting value 
               $rows = 1;
               $columns = 10;
            }
            // since textareas with row==1 do not display nicely:
            if ($rows == 1) {    
               echo "<td><input type='text' name='{$nowfield['name']}_$id' value=\"".str_replace('"','&quot;',$nowfield['values'])."\" size=$columns onchange='$js'>$thestar</td>\n";    
             } else {   
               echo "<td>$thestar<textarea name='{$nowfield['name']}_$id' cols=$columns rows=$rows onchange='$js'>{$nowfield['values']}</textarea></td>\n";                   
             }
         } elseif ($nowfield['datatype']=='date') {
     	    echo "<td><input type='text' name='{$nowfield['name']}_$id' value='{$nowfield['text']}' size=12 onchange='$js'>$thestar</td>\n";
         } elseif ($nowfield['datatype']=='int' || $nowfield['datatype']=='sequence') {
            $js="if (isAnInt(this.value)) { $js } else {this.value=\"\"; this.focus(); return false;}";
     	    echo "<td>$thestar<input type='text' name='{$nowfield['name']}_$id' value='{$nowfield['values']}' size=6 onchange='$js'></td>\n";
         } elseif ($nowfield['datatype']=='float') {
            $js="if (isAFloat(this.value)) { $js } else {this.value=\"\"; return false;}";
     	    echo "<td><input type='text' name='{$nowfield['name']}_$id' value='{$nowfield['values']}' size=8 onchange='$js'>$thestar</td>\n";
         } elseif ($nowfield['datatype']=='textlong') {
     	    echo "<td><textarea name='$nowfield[name]_$id' cols=45 rows=3 onchange='$js'>$thestar{$nowfield['values']}</textarea></td>\n"; 
         }
         elseif ($nowfield['datatype']=='link') {
            echo "<td><input type='text' name='$nowfield[name]_$id' value='$nowfield[values]' size=15 onchange='$js'>$thestar</td>\n";
         }
         elseif ($nowfield['datatype']=='pulldown') {
            // get previous value	
            $rp=$db->Execute("SELECT typeshort,id FROM {$nowfield['ass_t']} ORDER BY sortkey,typeshort");
            $text=$rp->GetMenu2("$nowfield[name]_$id",$nowfield['values'],true,false,0,"onchange='$js'");
            echo "\n<td>$text $thestar </td>\n";
         }
         elseif ($nowfield['datatype']=='mpulldown') {
            $js="onchange='submit_changes($tableinfo->id,$id,\"{$nowfield['name']}\",\"{$nowfield['datatype']}\",this)'";
            // get previous values
            unset ($rp);
            $rp=$db->Execute("SELECT typeshort,id FROM {$nowfield['ass_t']} ORDER BY sortkey,typeshort");
            unset ($rbv);
            unset ($valueArray);
            $rbv=$db->Execute("SELECT typeid FROM {$nowfield['key_t']} WHERE recordid=$id");
            while ($rbv && !$rbv->EOF) {
               $valueArray[]=$rbv->fields[0];
               $rbv->MoveNext();
            }
            $text=$rp->GetMenu2($nowfield['name']."_$id",$valueArray,true,true,4,$js);
            echo "\n<td>$text $thestar</td>\n";
         }
         elseif ($nowfield['datatype']=='table') {
            // only display primary key here
            if (!$nowfield['ass_local_key']) { 
               $text=false;
               // get previous value	
               if ($nowfield['ass_column_name'] && $nowfield['ass_table_name']) { 
                  $rcount=$db->Execute("SELECT COUNT(id) FROM {$nowfield['ass_table_name']}");
                  if ($rcount && ($rcount->fields[0] < $max_menu_length)) 
                     $text=GetValuesMenu($db,"{$nowfield['name']}_$id",$nowfield['values'],$nowfield['ass_table_name'],$nowfield['ass_column_name'],false,$js);
                  else {
                     $text="<input type='hidden' name='max_{$nowfield['name']}_$id' value='true'>\n";
                     $text.="<input type='text' name='{$nowfield['name']}_$id' value='{$nowfield['text']}'>\n<br>";
                  }
               }
               echo "<td>$text $thestar</td>\n";
            }
            else
               echo "<td>{$nowfield['text']} $thestar</td>\n";
         }
     elseif ($nowfield['datatype']=='textlarge') {
	     echo "<td colspan=6><textarea name='{$nowfield['name']}_$id' rows='5' cols='100%' $js>{$nowfield['values']}</textarea>$thestar</td>\n";
      } else {
            echo "<td>{$nowfield['text']}</td>\n";
      }
      }

      // View, Change and Delete buttons
      echo "<td align='center'>&nbsp;\n";  
      // Hidden variables should go here
      // This one is used by Javascript
      echo "<input type='hidden' name='chgj_".$id."' value=''>\n";
      // Access rights
      $ra=$db->Execute("SELECT gr,gw,er,ew FROM {$tableinfo->realname} WHERE id={$r->fields['id']}");
      echo "<input type='hidden' name='grr_$id' value='{$ra->fields['gr']}'>\n";
      echo "<input type='hidden' name='evr_$id' value='{$ra->fields['er']}'>\n";
      echo "<input type='hidden' name='grw_$id' value='{$ra->fields['gw']}'>\n";
      echo "<input type='hidden' name='evw_$id' value='{$ra->fields['er']}'>\n";

      // Action column - icons and javascript to enable these by Michael Muller
      // View action
      if ($_SESSION['javascript_enabled']) {
         $jscript=" onclick='MyWindow=window.open (\"general.php?tablename=".$tableinfo->name."&amp;showid=$id&amp;jsnewwindow=true&amp;viewid=$viewid\",\"view\",\"status,menubar,toolbar,scrollbars,resizable,titlebar,width=700,height=500\");MyWindow.focus()'";
         echo "<A href=\"javascript:void(0)\" $jscript> <img src=\"icons/detail.png\" alt=\"detail\" title=\"detail\" border=\"0\"/></A>\n";
      } else {
         echo "<input type=\"submit\" name=\"view_" . $id . "\" value=\"View\">\n";
      }
      if (may_write($db,$tableinfo->id,$id,$USER)) {
         // Change action
         // this works, but how do you go back from the modify window to this one???
         if ($_SESSION['javascript_enabled']) {
            echo "<input type=\"hidden\" name=\"chg_" . $id . "\">\n";
            $jscript="Onclick=\"document.g_form.chg_$id.value='Change'; document.g_form.submit();\"";
            echo "<A href=\"javascript:void(0)\" $jscript> <img src=\"icons/edit_modify.png\" alt=\"modify\" title=\"modify\" border=\"0\"/></A>\n";
         } else {
            echo "<input type=\"submit\" name=\"chg_" . $id . "\" value=\"Change\">\n";
         }
         // Delete action
         if (! $_SESSION['javascript_enabled']) {
            $delstring = "<input type=\"submit\" name=\"del_" . $id . "\" value=\"Remove\">\n";
         } else {
	         $jstitle=str_replace("'"," ",$title);
            $delstring = "Onclick=\"if(confirm('Are you sure that you want to remove record $jstitle?'))";
            $delstring .= "{document.g_form.del_$id.value='Remove';document.g_form.submit();return true;}return false;\""; 
            $delstring = "<input type='hidden' name='del_$id'>\n<A href=\"javascript:void(0)\" $delstring> <img src=\"icons/delete.png\" alt=\"delete\" title=\"delete\" border=\"0\"/></A>";
         }
         echo "$delstring\n";
      }
      /*
      if ($_SESSION['javascript_enabled']) {
         $jscript=" onclick='MyWindow=window.open (\"general.php?tablename=".$tableinfo->name."&amp;showid=$id&amp;jsnewwindow=true\",\"view\",\"scrollbars,resizable,toolbar,status,menubar,width=700,height=500\");MyWindow.focus()'";
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
      */
      echo "</td>\n";
      echo "</tr>\n";
      $r->MoveNext();
      $current_record++;
   }

   // Add Record button
   if (may_write($db,$tableinfo->id,false,$USER)) {
      echo "<tr><td colspan=20 align='center'>";
      echo "<input type=\"submit\" name=\"add\" value=\"Add Record\">";
      echo "</td></tr>\n";
   }

   echo "</table>\n";
   next_previous_buttons($page_array);
   echo "</form>\n";
}


/**
 * * 
 *  Displays all information within the table
 *
 */
function display_table_info($db,$tableinfo,$Fieldscomma,$pr_query,$num_p_r,$pr_curr_page,$page_array,$r=false,$viewid=false) {
   global $nr_records,$USER,$LAYOUT,$_SESSION;

   $first_record=($pr_curr_page - 1) * $num_p_r;
   $current_record=$first_record;
   $last_record=$pr_curr_page * $num_p_r;
   if (!$r)
      $r=$db->Execute($pr_query);
   $r->Move($first_record);

   // we keep a list with fileids in the user settings
   // these files can be seen without checking the database
   // to be sure that only the current files can be seen, unset the entry first
   unset($USER['settings']['fileids']);
   
   // print all entries
   while (!($r->EOF) && $r && ($current_record < $last_record) )  {
      // Get required ID and title
      $id=$r->fields['id'];
      $title=get_cell($db, $tableinfo->realname,'title','id',$r->fields['id']);		
      $Allfields=getvalues($db,$tableinfo,$Fieldscomma,id,$id);
      // print start of row of selected group
      if ($current_record % 2) echo "<tr class='row_odd' align='center'>\n";
         else echo "<tr class='row_even' align='center'>\n";
  
      foreach($Allfields as $nowfield) {
         // nested table links to the current world:
         if (isset($nowfield['nested'])) {
            $nowfield['text']=$nowfield['nested']['text'];
            $nowfield['values']=$nowfield['nested']['values'];
            $nowfield['datatype']=$nowfield['nested']['datatype'];
            $nowfield['fileids']=$nowfield['nested']['fileids'];
         }
         // display the contents 
         if ($nowfield['link'])
            echo "<td>{$nowfield['link']}</td>\n";
         elseif ($nowfield['datatype']=='mpulldown')
            echo "<td align='left' cellpadding='5%'>{$nowfield['text']}</td>\n"; 
         elseif ( $nowfield['datatype'] == 'textlong' && (strlen($nowfield['text'])>59) && $_SESSION['javascript_enabled'] && (substr($nowfield['text'],0,7) !='<a href') ) {
            // provide long text by mouseover -- by MM
            $startofText = substr($nowfield['text'],0,60);
            echo "<td><a class='Tooltip' href=\"javascript:void(0);\" ";
            // single quotes causes javascript problems even when 'htmled'
            $escapedText = str_replace("'",'"',$escapedText);
            echo "onmouseover=\"this.T_WIDTH=400;return escape";
            $escapedText = htmlspecialchars($nowfield['text'], ENT_QUOTES);
            // returns spoil the party
            $escapedText = preg_replace("/\r\n|\n|\r/", "<br>", $escapedText);
            // Even when converted to HTML characters, the following kill tooltips.  Remove
            $escapedText = str_replace('&quot;',' ',$escapedText);
            $escapedText = str_replace('&#039;',' ',$escapedText);
            echo '(\''.$escapedText.'\')">'.$startofText."...</a></td>\n";
         } else {
            echo "<td>{$nowfield['text']}</td>\n"; 
         }

         // write file ids to user settings so that we do not need to check them again when downloading thumbnails
         if (isset($nowfield['fileids'])) {
            foreach ($nowfield['fileids'] as $fileid)
               $USER['settings']['fileids'][]=$fileid;
         }
      }

      // Action column - icons and javascript to enable these by Michael Muller
      echo "<td align='center'>&nbsp;\n";  
      // View action
      if ($_SESSION['javascript_enabled']) {
         $jscript=" onclick='MyWindow=window.open (\"general.php?tablename=".$tableinfo->name."&amp;showid=$id&amp;jsnewwindow=true&amp;viewid=$viewid\",\"view\",\"status,menubar,toolbar,scrollbars,resizable,titlebar,width=700,height=500\");MyWindow.focus()'";
         echo "<A href=\"javascript:void(0)\" $jscript> <img src=\"icons/detail.png\" alt=\"detail\" title=\"detail\" border=\"0\"/></A>\n";
      } else {
         echo "<input type=\"submit\" name=\"view_" . $id . "\" value=\"View\">\n";
      }
      if (may_write($db,$tableinfo->id,$id,$USER)) {
         // Modify action
         // this works, but how do you go back from the modify window to this one???
         if ($_SESSION['javascript_enabled']) {
            $jscript="onclick='MyWindow=window.open (\"general.php?tablename=".$tableinfo->name."&amp;jsnewwindow=true&amp;modify=true&amp;mod_".$id."=Modify\",\"modify\",\"scrollbars,resizable,status,menubar,toolbar,width=700,height=500\");MyWindow.focus()'";
            echo "<A href=\"javascript:void(0)\" $jscript> <img src=\"icons/edit_modify.png\" alt=\"modify\" title=\"modify\" border=\"0\"/></A>\n";
         } else {
            echo "<input type=\"submit\" name=\"mod_" . $id . "\" value=\"Modify\">\n";
         }
         // Delete action
         if (! $_SESSION['javascript_enabled']) {
            $delstring = "<input type=\"submit\" name=\"del_" . $id . "\" value=\"Remove\">\n";
         } else {
	         $jstitle=str_replace("'"," ",$title);
            $delstring = "Onclick=\"if(confirm('Are you sure that you want to remove record $jstitle?'))";
            $delstring .= "{document.g_form.del_$id.value='Remove';document.g_form.submit();return true;}return false;\""; 
            $delstring = "<input type='hidden' name='del_$id'>\n<A href=\"javascript:void(0)\" $delstring> <img src=\"icons/delete.png\" alt=\"delete\" title=\"delete\" border=\"0\"/></A>";
         }
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
      if ($_SESSION['javascript_enabled']) {
         $jscript=" onclick='MyWindow=window.open (\"general.php?tablename=".$tableinfo->name."&amp;add=Add&amp;jsnewwindow=true\",\"view\",\"scrollbars,resizable,toolbar,status,menubar,width=700,height=500\");MyWindow.focus()'";
         echo "<input type=\"button\" name=\"add\" value=\"Add Record\" $jscript>";
      }
      else {
         echo "<input type=\"submit\" name=\"add\" value=\"Add Record\">";
      }
      echo "</td></tr>";
   }

   echo "</table>\n";
   next_previous_buttons($page_array);
   echo "</form>\n";
   echo "<script language='JavaScript' type='text/javascript' src='includes/js/wz_tooltip.js'></script>";
}

/**
 * *
 *  Display a record in a nice format
 *
 */
function display_record($db,$Allfields,$id,$tableinfo,$backbutton=true,$previousid=false,$nextid=false,$viewid=false) 
{
   global $PHP_SELF, $md,$USER;

   if (!$Allfields[1]['recordid']) {
      echo "<table border=0 align='center'>\n";
      echo "<tr>\n<td align='center'><h3>Record not found</h3>\n</td>\n</tr>";
      echo "<tr>\n<td align='center'>\n<button onclick='self.close();window.opener.focus();' name='Close' value='close'>Close</button></td></tr>\n";
      echo "</table>\n";
      exit;
   }
   echo "&nbsp;<br>\n";
   echo "<table border=0 align='center'>\n";
   $count=0;
   echo "<tr>\n";
   // if viewid is defined we will over-ride display record with values from the view settings
   if ($viewid) {
      $r=$db->Execute("SELECT columnid FROM tableviews WHERE viewnameid=$viewid AND viewmode=2");
      while ($r && !$r->EOF) {
         $viewlist[]=$r->fields[0];
         $r->MoveNext();
      }
   }
   //print_r($Allfields);

   foreach ($Allfields as $nowfield) {

      // decide whether this field will be shown
      unset ($thisfield);
      // if we have a viewid, check the list
      if ($viewlist){
         $thisfield=in_array($nowfield['columnid'],$viewlist);
      }
      else {
         //Only show the entry when display_record is set
         $thisfield= ($nowfield['display_record']==='Y');
      }
      if ($thisfield && is_array($nowfield)) {
         // explode nested table links to the current world:
         if (isset($nowfield['nested'])) {
            $nowfield['text']=$nowfield['nested']['text'];
            $nowfield['values']=$nowfield['nested']['values'];
            $nowfield['datatype']=$nowfield['nested']['datatype'];
            $nowfield['fileids']=$nowfield['nested']['fileids'];
         }
         // display the fields in two columns
         if ($count && !($count % 2))
            echo "</tr>\n<tr>\n";
         if ($nowfield['datatype']=='textlong') {
            $textlarge=nl2br(htmlentities($nowfield['values']));
            echo "<th>$nowfield[label]</th><td colspan=2>$textlarge</td>\n";
         }
         elseif ($nowfield['datatype']=='file' || $nowfield['datatype']=='image') {
            // if this came through a associated table:
            if ($nowfield['nested']['nested_tbname'] && $nowfield['nested']['nested_columnid'])
               $files=get_files($db,$nowfield['nested']['nested_tbname'],$nowfield['nested']['nested_id'],$nowfield['nested']['nested_columnid'],0,'big');
            // the normal/direct way
            else
               $files=get_files($db,$tableinfo->name,$id,$nowfield['columnid'],0,'big');
            if ($files) { 
               echo "<th>$nowfield[label]:</th>\n<td colspan=5>";
               for ($i=0;$i<sizeof($files);$i++)  {
                  echo $files[$i]['link']."&nbsp;&nbsp;(<i>".$files[$i]['name']."</i>, ".$files[$i]['type'];
                  echo " file, ".$files[$i]['size'].")<br>\n";
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
	    if ($nowfield['link'])
               echo "<td colspan=2>{$nowfield['link']}</td>\n";
            else       
               echo "<td colspan=2>{$nowfield['text']}</td>\n";
         }
         $count++;
      }
   }
   echo "</tr>\n";
   make_link($id,$tableinfo->name);
   show_reports($db,$tableinfo,$id,$viewid);
   if (function_exists ("plugin_display_show")){
      plugin_display_show ($db,$Allfields,$id);
      return $Allfields;
   } 
   echo "</table>\n";

   echo "<form method='post' name='g_form' action='$PHP_SELF?tablename=".$tableinfo->name."&".SID."'>\n";
   echo "<input type='hidden' name='md' value='$md'>\n";
   echo "<input type='hidden' name='showid' value='$id'>\n";
   //echo "<input type='hidden' name='jsnewwindow' value='false'>\n";

   // for organizational purpose, define buttons here:
   // next and previous buttons
   if ($previousid)
      $previousbutton="<input type=\"button\" name=\"view_".$previousid."\" value=\"Previous\" onClick='MyWindow=window.open(\"general.php?tablename={$tableinfo->name}&amp;showid=$previousid&amp;jsnewwindow=true&amp;viewid=$viewid\",\"view\",\"scrollbars,resizable,toolbar,width=600,height=400\")'>\n";
   if ($nextid)
      $nextbutton="<input type=\"button\" name=\"view_".$nextid."\" value=\"Next\" onClick='MyWindow=window.open(\"general.php?tablename={$tableinfo->name}&amp;showid=$nextid&amp;jsnewwindow=true&amp;viewid=$viewid\",\"view\",\"scrollbars,resizable,toolbar,width=600,height=400\")'>\n";
   // closebutton
   $closebutton="<input type=\"button\" onclick='self.close();window.opener.focus();' name='Close' value='Close'>\n";
   if ($backbutton) {
      $backbutton="<input type='submit' name='submit' value='Back'>\n";
   }
   // modify button 
   if (may_write($db,$tableinfo->id,$id,$USER)) {
      $modifybutton= "<input type=\"submit\" name=\"mod_" . $id . "\" value=\"Modify\">\n";
   }
   // viewmenu:
   $viewmenu=viewmenu($db,$tableinfo,$viewid,false);

   // and now display the buttons
   echo "<table border=0 align='center' width='100%'>\n";
   if ($backbutton) {
      echo "<tr>\n<td align='left'>";
      echo " $previousbutton</td><td align='center'>$modifybutton $backbutton $viewmenu</td><td align='right'>$nextbutton </td>\n</tr>\n";
   }
   else
      echo "<tr><td align='left'>$previousbutton &nbsp;</td><td align='center'> $modifybutton $closebutton </td><td>$viewmenu</td><td align='right'>$nextbutton &nbsp;</td></tr>\n";
   echo "</table>\n\n";
   echo "</form>\n";
}

/**
 * *
 *  make a nice link to the record
 *
 */
function make_link($id,$DBNAME) {
   global $PHP_SELF,$system_settings;
   echo "<tr><th>Link:</th><td colspan=7><a href='$PHP_SELF?tablename=$DBNAME&amp;showid=$id&amp;".SID;
   //echo "'>".$system_settings["baseURL"].getenv("SCRIPT_NAME")."?tablename=$DBNAME&showid=$id</a></td></tr>\n";
   echo "'>".$system_settings['baseURL'].$PHP_SELF."?tablename=$DBNAME&amp;showid=$id</a></td></tr>\n";
}


/**
 * *
 *   Make dropdown menu with available templates
 *
 * When one is chosen, open the formatted record in a new window
 */
function show_reports($db,$tableinfo,$recordid=false,$viewid=false) {
   global $USER;
//echo "$viewid.<br>";
   $r=$db->Execute("SELECT id,label FROM reports WHERE tableid=".$tableinfo->id);
   //if ($r && !$r->EOF) {
   if ($r) {
      if ($recordid) {
         $menu="<tr><th>Report:</th>\n";
         $menu.="<td><select name='reportlinks' onchange='linkmenu(this)'>\n";
         $url="target "."report.php?tablename=".$tableinfo->htmlname."&amp;recordid=$recordid";
	 if ($viewid) {
	    $url.="&amp;viewid=$viewid";
	 }
	 $url.='&amp;reportid';
         $menu.="<option value=''>---Reports---</option>\n";
         $menu.="<option value='$url-1'>xml</option>\n";
         $menu.="<option value='$url-2'>tab</option>\n";
         $menu.="<option value='$url-3'>csv</option>\n";
         while (!$r->EOF) {
            $url="target "."report.php?tablename=".$tableinfo->htmlname."&amp;reportid=".$r->fields["id"]."&amp;recordid=$recordid";
	    if ($viewid)
	       $url.="&amp;viewid=$viewid";
            $menu.="<option value='$url'>".$r->fields['label']."</option>\n";
            $r->MoveNext();
         }
         $menu.="</select>\n";
         $menu.="</td></tr>\n";
      } else { // for tableview reports
         $menu="<td>Report: \n";
         $menu.="<select name='reportlinks' onchange='linkmenu(this)'>\n";
         $menu.="<option value=''>---Reports---</option>\n";
         $url="target "."report.php?tablename=".$tableinfo->htmlname."&amp;tableview=true";
	 if ($viewid) {
	    $url.="&amp;viewid=$viewid";
	 }
	 $url.='&amp;reportid';
         $menu.="<option value='$url=-1'>xml</option>\n";
         $menu.="<option value='$url=-2'>text</option>\n";
         $menu.="<option value='$url=-3'>csv</option>\n";
         while (!$r->EOF) {
            $url="target "."report.php?tablename=".$tableinfo->htmlname."&amp;reportid=".$r->fields["id"]."&amp;tableview=true";
	    if ($viewid) {
	       $url.="&amp;viewid=$viewid";
	    }
            $menu.="<option value='$url'>".$r->fields['label']."</option>\n";
            $r->MoveNext();
         }
         $menu.="</select>\n";
         // add radio buttons for output options:
         $menu.="Send to:\n";
         if (!(isset($USER['settings']['reportoutput'])) || $USER['settings']['reportoutput']==1)
             $checked='checked';
         $menu.="<input type='radio' name='reportoutput' $checked value='1' onClick='document.g_form.submit();'>screen\n";
         if ($USER['settings']['reportoutput']==2)
             $checked2='checked';
         $menu.="<input type='radio' name='reportoutput' $checked2 value='2' onClick='document.g_form.submit();'>file\n";

         $menu.= "<a href='editreports.php?tablename={$tableinfo->htmlname}'>Edit reports</a>\n";

         $menu.="</td>\n";
      }
      echo $menu;
   }
}

/**
 * *
 *  display addition and modification form
 *
 */
function display_add($db,$tableinfo,$Allfields,$id,$namein,$system_settings) { 
   global $PHP_SELF,$md,$max_menu_length,$USER,$LAYOUT,$_POST,$_SESSION;
   
   $dbstring=$PHP_SELF;$dbstring.="?";$dbstring.="tablename=".$tableinfo->name."&";
   echo "<form method='post' id='protocolform' enctype='multipart/form-data' name='subform' action='$dbstring";
	?><?=SID?>'><?php

   if (!$magic)
      $magic=time();
   echo "<input type='hidden' name='magic' value='$magic'>\n";
   echo "<input type='hidden' name='md' value='$md'>\n";
   echo "<table border=0 align='center'>\n";   
   if ($id) {
      echo "<tr><td align='center'><h3>Modify ".$tableinfo->label." entry <i>$namein</i></h3>\n";
      echo "<input type='hidden' name='id' value='$id'></td></tr>\n";
   }
   else {
      echo "<tr><td align='center'><h3>New ".$tableinfo->label." entry</h3></td></tr>\n";
   }
   echo "<tr><td>\n<table border=0 align='center'>\n";
   
   foreach ($Allfields as $nowfield) {
      // give plugin a chance to modify data
      if (function_exists('plugin_display_add_pre'))
         plugin_display_add_pre($db,$tableinfo->id,$nowfield);
	 
      // see if display_record is set
      if ( (($nowfield['display_record']=='Y') || ($nowfield['display_table']=='Y')) ) {
         // To persist between multiple invocation, grab POST vars 
         if ($nowfield['modifiable']=='Y' && isset($_POST[$nowfield['name']]) && $_POST[$nowfield['name']] && isset($_POST['submit'])) {
            $nowfield['values']=$_POST[$nowfield['name']];
            $nowfield['text']=$_POST[$nowfield['name']];
         }
         if ($nowfield['modifiable']!='Y' && $nowfield['datatype']!='sequence') {
            echo "<input type='hidden' name='$nowfield[name]' value='$nowfield[values]'>\n";
            if ($nowfield['text'] && $nowfield['text']!='' && $nowfield['text']!=' ') {
               echo "<tr><th>{$nowfield['label']}:</th>"; 
               echo "<td>{$nowfield['text']}";
            }
         } elseif ($nowfield['modifiable']=='Y' && ($nowfield['datatype']=='text' || $nowfield['datatype']=='int' || $nowfield['datatype']=='float' || $nowfield['datatype']=='date')) {
            echo "<tr><th>$nowfield[label]:"; 
            if ($nowfield['required']=='Y') {
               echo "<sup style='color:red'>&nbsp;*</sup>";
            }
            echo "</th>\n";
            if ($nowfield['datatype']=='text') {
               //$size=60;
               // mike likes this to be a textlong field, let's try
     	       echo "<td><textarea name='{$nowfield['name']}' rows='2'cols=80>{$nowfield['values']}</textarea>";
            }
            // for dates we have to dsiplay the test and not the value
            elseif ($nowfield['datatype']=='date') {
               $size=10;
     	       echo "<td><input type='text' name='{$nowfield['name']}' value='{$nowfield['text']}' size='$size'>";
            }
            else {
               $size=10;
     	       echo "<td><input type='text' name='{$nowfield['name']}' value='{$nowfield['values']}' size='$size'>";
            }
	    } elseif ($nowfield['datatype']=='sequence') {
          if (!$nowfield['text']) {
             // find the highest sequence and return that plus one
             $rmax=$db->Execute("SELECT MAX(${nowfield['name']}) AS ${nowfield['name']} FROM ".$tableinfo->realname);
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
          } else {
             echo "<td><input type='text' name='$nowfield[name]' value='$newseq' size='10'>";
          }
       } elseif ($nowfield['datatype']=='textlong') {
          echo "<tr><th>{$nowfield['label']}:";
          if ($nowfield['required']=='Y')  {
             echo "<sup style='color:red'>&nbsp;*</sup>";
          }
     	    echo "<td><textarea name='{$nowfield['name']}' rows='5' cols='80' value='{$nowfield['values']}'>{$nowfield['values']}</textarea>";
       } elseif ($nowfield['datatype']=='link') {
         echo "<tr><th>$nowfield[label] (http link):";
         if ($nowfield['required']=='Y') {
            echo "<sup style='color:red'>&nbsp;*</sup>";
         }
         echo "<td><input type='text' name='$nowfield[name]' value='$nowfield[values]' size='60'>";
      } elseif ($nowfield['datatype']=='pulldown') {
         // get previous value	
         $r=$db->Execute("SELECT typeshort,id FROM {$nowfield['ass_t']} ORDER BY sortkey,typeshort");
         if ($nowfield['datatype']=='pulldown')
            $text=$r->GetMenu2("$nowfield[name]",$nowfield['values'],true,false);
         else
            $text=$r->GetMenu2("$nowfield[name]",$nowfield['values'],true,true);
         echo "<tr><th>$nowfield[label]:";
         if ($nowfield['required']=='Y')
            echo"<sup style='color:red'>&nbsp;*</sup>";
         echo "</th>\n<td>";
         if ($USER['permissions'] & $LAYOUT) {
            $jscript=" onclick='MyWindow=window.open (\"general.php?tablename=".$tableinfo->name."&amp;edit_type=$nowfield[ass_t]&amp;jsnewwindow=true&amp;formname=subform&amp;selectname=$nowfield[name]".SID."\",\"type\",\"scrollbars,resizable,toolbar,status,width=700,height=500\");MyWindow.focus()'";
            echo "<input type='button' name='edit_button' value='Edit $nowfield[label]' $jscript><br>\n";
         }
         echo "$text<br>";
      } elseif ($nowfield['datatype']=='table') {
         // only display primary key here
         if (!$nowfield['ass_local_key']) { 
            // get previous value	
            $r=$db->Execute("SELECT COUNT(id) FROM {$nowfield['ass_table_name']}");
            if ($r->fields[0] > $max_menu_length) {
               $text="<input type='hidden' name='max_{$nowfield['name']}' value='true'>\n";
               $text.="<input type='text' name='{$nowfield['name']}' value='{$nowfield['nested']['values']}'>";
            } else {
               $text=GetValuesMenu($db,$nowfield['name'],$nowfield['values'],$nowfield['ass_table_name'],$nowfield['ass_column_name'],false);
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
       if ($nowfield['datatype']=='file' || $nowfield['datatype']=='image') {
          $files=get_files($db,$tableinfo->name,$id,$nowfield['columnid'],0,'big');
          echo '<tr>';
          echo "<th>$nowfield[label]:</th>\n";
          echo "</th>\n";
          echo '<td colspan=4> <table border=0>';
          for ($i=0;$i<sizeof($files);$i++)  {
             echo "<tr><td colspan=2>".$files[$i]['link'];
             echo "&nbsp;&nbsp;(<i>".$files[$i]['name']."</i>, ".$files[$i]['type']." file)</td>\n";
             echo "<td><input type='submit' name='def_".$files[$i]["id"]."' value='Delete' Onclick=\"if(confirm('Are you sure the file ".$files[$i]["name"]." should be removed?')){return true;}return false;\"></td></tr>\n";
          }
          echo '<tr><th>Upload '.$nowfield['datatype']."</th>\n";
	       echo "<td>&nbsp;</td><td><input type='file' name='".$nowfield[name]."[]' value='$filename'></td>\n";
	       echo "</tr></table><br>\n\n";
       }  elseif ($nowfield['datatype']=='mpulldown') {
           unset ($valueArray);
           // get previous value	
           $r=$db->Execute("SELECT typeshort,id FROM {$nowfield['ass_t']} ORDER BY sortkey,type");
           $rbv=$db->Execute("SELECT typeid FROM {$nowfield['key_t']} WHERE recordid=$id");
           while ($rbv && !$rbv->EOF) {
              $valueArray[]=$rbv->fields[0];
              $rbv->MoveNext();
           }
           $text=$r->GetMenu2($nowfield['name'].'[]',$valueArray,true,true);
           echo "<tr><th>{$nowfield['label']}:";
           if ($nowfield['required']=='Y')
              echo"<sup style='color:red'>&nbsp;*</sup>";
           echo "</th>\n<td>";
           if ($USER['permissions'] & $LAYOUT) {
              $jscript=" onclick='MyWindow=window.open (\"general.php?tablename=".$tableinfo->name."&edit_type=$nowfield[ass_t]&amp;jsnewwindow=true&amp;formname=subform&amp;selectname=$nowfield[name]".SID."\",\"type\",\"scrollbars,resizable,toolbar,status,width=700,height=500\");MyWindow.focus()'";
              echo "<input type='button' name='edit_button' value='Edit $nowfield[label]' $jscript><br>\n";
           }
           echo "$text<br>";
        }
     }
     if (function_exists('plugin_display_add'))
        plugin_display_add($db,$tableinfo->id,$nowfield);
   } // end of foreach ($Allfields)	

   echo "</table>\n</td>\n</tr>\n";

   /* Call to a function that runs at the end when adding a new record*/   
   if ((function_exists("plugin_display_add_post")) && (!($id))){
      plugin_display_add_post($db,$tableinfo->id);
   }
         
   echo '<tr><td colspan=4>';
   show_access($db,$tableinfo->id,$id,$USER,$system_settings);
   echo "</td></tr>\n"; echo "<tr>";
   if ($id) {
      $value="Modify Record"; 
   } else {
      $value="Add Record";
   }

   // submit and clear buttons
   echo "<td align='center'>\n";
   if ($_SESSION['javascript_enabled']) {
      echo "<input type='hidden' name='subm' value=''>\n";
      echo "<input type='button' name='sub' value='$value' onclick='document.subform.subm.value=\"$value\"; document.subform.submit(); window.opener.document.g_form.search.value=\"Search\"; window.opener.document.g_form.submit(); window.opener.focus(); '>\n";
      echo "<input type='button' name='Close' onclick='self.close(); window.opener.focus();' value='Cancel'>\n";
   }
   else {
      echo "<input type='submit' name='submit' value='$value'>\n";
      echo "&nbsp;&nbsp;";
      echo "<input type='submit' name='submit' value='Cancel'></td>\n";
   }
   echo "</tr>\n</table>\n</form>\n";
}


/**
 * *
 *  Get all description table values out for a display
 *
 * Returns an array with lots of information on every column
 * If qfield is set, database values for that record will be returned as well
 */
function getvalues($db,$tableinfo,$fields,$qfield=false,$field=false) 
{
   global $system_settings;

   if ($qfield) {
      $r=$db->Execute("SELECT $fields FROM $tableinfo->realname WHERE $qfield=$field"); 
      if ($qfield=='id') {
         $id=$field;
      } else {
         $rid=$db->Execute("SELECT id FROM $tableinfo->realname WHERE $qfield=$field");
         $id=$rid->fields['id'];
      }
   }

   $columns=split(',',$fields);
   $Allfields=array();
   foreach ($columns as $column) {
      if($column!='id') {
         if ($r)
            // Take slashes out that were put in to be able to enter stuff into the database
            ${$column}['values']= stripslashes($r->fields[$column]);
         $rb=$db->CacheExecute(2,"SELECT id,label,datatype,display_table,display_record,associated_table,key_table,associated_column,associated_local_key,required,link_first,link_last,modifiable FROM $tableinfo->desname WHERE columnname='$column'");
         ${$column}['name']=$column;
         ${$column}['columnid']=$rb->fields['id'];
         ${$column}['label']=$rb->fields['label'];
         ${$column}['datatype']=$rb->fields['datatype'];
         ${$column}['display_table']=$rb->fields['display_table'];
         ${$column}['display_record']=$rb->fields['display_record'];
         ${$column}['ass_t']=$rb->fields['associated_table'];
         ${$column}['key_t']=$rb->fields['key_table'];
         ${$column}['ass_column']=$rb->fields['associated_column'];
         ${$column}['ass_local_key']=$rb->fields['associated_local_key'];
         ${$column}['required']=$rb->fields['required'];
         ${$column}['modifiable']=$rb->fields['modifiable'];
         if ($rb->fields['datatype']=='table') {
            ${$column}['ass_table_desc_name']=get_cell($db,'tableoftables','table_desc_name','id',$rb->fields['associated_table']);
            ${$column}['ass_table_name']=get_cell($db,'tableoftables','real_tablename','id',$rb->fields['associated_table']);
            ${$column}['ass_column_name']=get_cell($db,${$column}['ass_table_desc_name'],'columnname','id',$rb->fields['associated_column']);
	 }
         if ($id) {
            ${$column}['recordid']=$id;

            // datatype table (the toughest of all)
            if ($rb->fields['datatype']=='table') {
               // if there is an associated local key, we'll need to get the actual value from there
               if ($rb->fields['associated_local_key']) {
                  ${$column}['ass_local_column_name']=get_cell($db,$tableinfo->desname,'columnname','id',$rb->fields['associated_local_key']);
                  ${$column}['values']=get_cell($db,$tableinfo->realname,${$column}['ass_local_column_name'],'id',$id); 
               }
               $text=false;
               $values=false;
               // if there is a value, we'll dig into the associated table to find out what our associated value and text are
               if (${$column}['values']) {
                  $asstableinfo=new tableinfo($db,${$column}['ass_table_name']);
                  // we always link to the id column of the associated table
                  $tmpvalue=getvalues($db,$asstableinfo,${$column}['ass_column_name'],'id',${$column}['values']);
                  if (is_array($tmpvalue[0])) {
                     if (isset($tmpvalue[0]['nested']))
                        ${$column}['nested']=$tmpvalue[0]['nested'];
                     else {
                        ${$column}['nested']['text']=$tmpvalue[0]['text']; 
                        ${$column}['nested']['values']=$tmpvalue[0]['values']; 
                        ${$column}['nested']['datatype']=$tmpvalue[0]['datatype']; 
                        ${$column}['nested']['fileids']=$tmpvalue[0]['fileids']; 
                        //the following are needed to get files and images right
                        ${$column}['nested']['nested_id']=$tmpvalue[0]['nested_id'];
                        ${$column}['nested']['nested_tbname']=$tmpvalue[0]['nested_tbname'];
                        ${$column}['nested']['nested_columnid']=$tmpvalue[0]['nested_columnid'];
                     }
                  }
                  else {
                     $text=$tmpvalue[0];
                     $values=$text;
                  }
                  ${$column}['link']="<a target=_ href=\"general.php?tablename={$asstableinfo->name}&amp;showid=".${$column}['values']."\">{${$column}['nested']['text']}</a>\n";
               }
               $text=${$column}['nested']['text'];
               if (!$text)
                  $text="&nbsp;";
               ${$column}['text']=$text;
            }

            // datatype link
            elseif ($rb->fields['datatype']=='link') {
               if (${$column}['values'])
                  ${$column}['text']="<a href='".${$column}["values"]."' target='_blank'>link</a>";
            }
            // datatype pulldown
            elseif ($rb->fields['datatype']=='pulldown') {
               ${$column}['text']=get_cell($db,${$column}['ass_t'],'type','id',${$column}['values']); 
            }
            // datatype mpulldown
            elseif ($rb->fields['datatype']=='mpulldown') {
               unset($rasst);
               unset($rasst2);
               unset ($typeids);
               $rasst=$db->Execute("SELECT typeid FROM {${$column}['key_t']} WHERE recordid='$id'");
               while ($rasst && !$rasst->EOF){
                  $typeids.=$rasst->fields[0].',';
                  $rasst->MoveNext();
               }
               if ($typeids){
                  $typeids=substr($typeids,0,-1);
                  $rasst2=$db->Execute("SELECT type from {${$column}['ass_t']} where id IN ($typeids) ORDER BY sortkey");
                  while ($rasst2 && !$rasst2->EOF) {
                     ${$column}['text'].=$rasst2->fields[0].'<br>'; 
                     $rasst2->MoveNext();
                  }
               }
            }
            // datatype textlong
            elseif ($rb->fields['datatype']=='textlong') {
               if (${$column}['values']=='') {
                  ${$column}['text']='';
               }
               else {
                  if ($_SESSION['javascript_enabled']) {
                     // with javascript the tooltip stuff will take care of long text fields
                     ${$column}['text']=${$column}['values'];
                  } else {
                     ${$column}['text']="<input type=\"button\" name=\"view_$id\" value=\"View\" onclick='MyWIndow=window.open(\"general.php?tablename={$tableinfo->name}&amp;showid=$id&amp;jsnewwindow=true\",\"view\",\"scrollbars,resizable,width=600,height=400\")'>\n";
                  }
               }
            }
            // datatypes file and image
            elseif ($rb->fields['datatype']=='file' || $rb->fields['datatype']=='image') {
               $tbname=get_cell($db,'tableoftables','tablename','id',$tableinfo->id);
               // we can get naming conflicts here. Use a really weird name
               $fzsk=get_files($db,$tbname,$id,${$column}['columnid'],3);
               if ($fzsk) {
                  ${$column}['nested_id']=$id;
                  ${$column}['nested_tbname']=$tbname;
                  ${$column}['nested_columnid']=${$column}['columnid'];
                  for ($i=0;$i<sizeof($fzsk);$i++) {
                     ${$column}['text'].=$fzsk[$i]['link'];
                     ${$column}['fileids'][]=$fzsk[$i]['id'];
                  }
               }
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
      $Allfields[]= ${$column};
      //$Allfields[${$column}['name']]=${$column};
   }
   if (function_exists("plugin_getvalues")) {
      plugin_getvalues($db,$Allfields,$id,$tableinfo->id);
   }
   return $Allfields;
}


/**
 * *  general functions
 */
/****************************FUNCTIONS***************************/
/**
 *  Checks input data before they are entered in the database
 *
 * returns false if something can not be fixed     
 */
function check_g_data ($db,&$field_values,$tableinfo,$modify=false) {
   global $max_menu_length, $system_settings;

   // make sure all the required fields are there 
   $rs = $db->Execute("SELECT columnname,datatype,label FROM {$tableinfo->desname} where required='Y' and (datatype != 'file')");
   while (!$rs->EOF) {
      $fieldA=$rs->fields[0];
      if (!$field_values["$fieldA"]) {
         echo "<h3 color='red' align='middle'>Please enter all fields marked with a <sup style='color:red'>&nbsp;*</sup>. Currently, a value for field: <i>{$rs->fields[2]}</i> is missing.</h3>";
	 return false;
      }
      $rs->MoveNext();
   }

   // make sure ints and floats are correct, try to set the UNIX date
   $rs = $db->Execute("SELECT columnname,datatype,label,associated_table,associated_column FROM {$tableinfo->desname} WHERE datatype IN ('int','float','table','date','sequence', 'textlong')");
   while ($rs && !$rs->EOF) {
      $fieldA=$rs->fields[0];
      if (isset($field_values["$fieldA"]) && (strlen($field_values[$fieldA]) >0)) {
         if ($rs->fields[1]=='int') {
            if ($field_values[$fieldA]==' ')
               $field_values[$fieldA]='';
            else
               $field_values[$fieldA]=(int)$field_values[$fieldA];
         }
         elseif ($rs->fields[1]=='float') {
            if ($field_values[$fieldA]==' ')
               $field_values[$fieldA]='';
            else
               $field_values[$fieldA]=(float)$field_values[$fieldA];
         }
         elseif ($rs->fields[1]=='table') {
             $field_values[$fieldA]=(int)$field_values[$fieldA];
         }
         elseif ($rs->fields[1]=='date') {
            if ($system_settings['dateformat']<3) {
               // we have a US date, change dashes to slashes
               $field_values[$fieldA]=strtr($field_values[$fieldA],'-','/');
            }
            $field_values[$fieldA]=strtotime($field_values[$fieldA]);
            if ($field_values[$fieldA] < 0)
               $field_values[$fieldA]="";
         }
         // Firefox on the Mac has issues with very long words
         // cut off words at 80 characters and insert a line break
         elseif ($rs->fields[1]=='textlong') {
             $longtxts = explode(' ',$field_values[$fieldA]);
             unset($field_values[$fieldA]);
             foreach ($longtxts  as $longtxt) {
                $field_values[$fieldA].=wordwrap($longtxt, 80, ' ',1) . ' ';
             }
         }
         elseif ($rs->fields[1]=='sequence') {
            $field_values[$fieldA]=(int)$field_values[$fieldA];
	    if ($field_values[$fieldA]<1)
	       unset($field_values[$fieldA]);
            // for new additions, check if this number was given out before:
	    if (!$modify) {
               if (get_cell($db,$tableinfo->realname,$rs->fields[0],$rs->fields[0],$field_values[$fieldA])) {
                  $rmax=$db->Execute("SELECT max({$rs->fields[0]}) FROM {$tableinfo->realname}");
                  if ($rmax->fields[0])
                     $nextmax=$rmax->fields[0]+1;
                  echo "<h3 color='red' align='middle'>The number <i>{$field_values[$fieldA]}</i> has already been used in field <i>{$rs->fields[2]}</i>. ";
                  if ($nextmax) {
                     echo "Try <i>$nextmax</i> instead.";
                     $field_values[$fieldA]=$nextmax;
                  }
                  echo "</h3>\n";
	          return false;
	       }
            }
         }   

      }
      $rs->MoveNext();
   }

   // Hooray, the first call to a plugin function!!
   if (function_exists('plugin_check_data')) {
      if (!plugin_check_data($db,$field_values,$tableinfo->desname,$modify))
         return false;
   }

   return true;
}

/**
 * Go through the comma-separated list $Fieldscomma, and retrun an array ($fields) with every field that needs a value set to a default
 *
 * 
 */
function set_default($db,$tableinfo,$Fieldscomma,$USER,$system_settings) {
   if (!may_write($db,$tableinfo->id,false,$USER)) {
      return false;
   }
   $Allfields=getvalues($db,$tableinfo,$Fieldscomma);
   foreach ($Allfields as $nowfield) {
      // give plugin a chance to modify data
      if (function_exists('plugin_display_add_pre')) {
         plugin_display_add_pre($db,$tableinfo->id,$nowfield);
      }
      // For sequences, provide the next available number
      if ($nowfield['datatype']=='sequence') {
         $rmax=$db->Execute("SELECT MAX(${nowfield['name']}) AS ${nowfield['name']} FROM ".$tableinfo->realname);
	 $fields[$nowfield['name']]=$rmax->fields[0]+1;
      } elseif (in_array($nowfield['name'], array('gr','gw','er','ew'))) {
         $fields[$nowfield['name']]=get_access(false,$nowfield['name']);
      } else {
	 // here we pick up values we got from the plugin code
	 $fields[$nowfield['name']]=$nowfield['values'];
      }

      // For required fields, we simply enter the field name, or 0 for numerics
      if ($nowfield['required']=='Y' && !isset($nowfield['values'])) {
      
         if ($nowfield['datatype']=='text' || $nowfield['datatype']=='textlong') {
            $fields[$nowfield['name']]=$nowfield['label'];
         } elseif ($nowfield['datatype']=='int' || $nowfield['datatype']=='float') {
            $fields[$nowfield['name']]=0;
         } elseif ($nowfield['datatype']=='date') {
            // assign current date
	    $fields[$nowfield['name']]=time();
         } elseif ($nowfield['datatype']=='link') {
            $fields[$nowfield['name']]=$nowfield['label'];
         } elseif ($nowfield['datatype']=='pulldown') {
            $fields[$nowfield['name']]=0;
         } elseif ($nowfield['datatype']=='table') {
            $fields[$nowfield['name']]=0;
	 } if ($nowfield['datatype']=='file' || $nowfield['datatype']=='image') {
            $fields[$nowfield['name']]=0;
         } elseif ($nowfield['datatype']=='mpulldown') {
            $fields[$nowfield['name']]=0;
         }
      }
   }
   return ($fields);
}


/**
 *  Prints a form with addition stuff
 *
 * $fields is a comma-delimited string with column names
 * $field_values is hash with column names as keys
 * $id=0 for a new entry, otherwise it is the id
 */
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

/**
 *  Shows a page with nice information on the record
 *
 */
function show_g($db,$tableinfo,$id,$USER,$system_settings,$backbutton=true,$previousid=false,$nextid=false,$viewid=false)  {
   if (!may_read($db,$tableinfo,$id,$USER))
       return false;
   $Allfields=getvalues($db,$tableinfo,$tableinfo->fields,id,$id);
   display_record($db,$Allfields,$id,$tableinfo,$backbutton,$previousid,$nextid,$viewid);
}
	
/**
 *  Tries to convert a MsWord file into html 
 *
 * It calls wvHtml.  
 * When succesfull, the file is added to the database
 * Returns id of uploaded file
 */
function process_file($db,$fileid,$system_settings) {
   global $_FILES,$_POST;
   $mimetype=get_cell($db,'files','mime','id',$fileid);
   if (!strstr($mimetype,'html')) {
      $word2html=$system_settings['word2html'];
      $wv_version=$system_settings['wvHtml_version'];
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
         // we now know this was an MSword file, so lets make sure the mime type is OK
         $db->query("UPDATE files SET mime='application/msword',type='msword' WHERE id=$fileid");
         unset ($_FILES);
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


/**
 *  Indexes the content of the given file
 *
 * The file is converted to a text file (pdfs with ghost script,
 * word files were already converted to html,html characters are stripped),
 * all words are lowercased, it is checked whether an entry in the table words
 * already exists, if not, it is added.  A relation to the word is made in 
 * the table associated with the given column
 */
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

/**
 *  Searches (nested) for a match with $value 
 *
 * returns the id of the record in the associated value by searching recursively
 * that can be used in a SQL search
 */
function find_nested_match($db,$tableinfo,$field,$value,$first=true) {
   $info=getvalues($db,$tableinfo,$field);

   if ($info[0]['datatype']=='table') {
      $ass_tableinfo=new tableinfo($db,$info[0]['ass_table_name']);
      $value=find_nested_match($db,$ass_tableinfo,$info[0]['ass_column_name'],$value,false);
   } elseif ($info[0]['datatype']=='int') {
      $value= trim($value);
      // I am getting desperate, but the browser inserts junk in the first postions, test if it is a number, if not, delete it. 
      if (!is_numeric($value{0})) {
         $value=substr($value,1);
      }
      return get_cell($db,$tableinfo->realname,'id',$field,(int) $value);
   } elseif ($info[0]['datatype']=='float') {
      $value= trim($value);
      if (!is_numeric($value{0})) {
         $value=substr($value,1);
      }
      return get_cell($db,$tableinfo->realname,'id',$field,(float) $value);
   } elseif ($info[0]['datatype']=='pulldown') {
      $value=get_cell($db,$info[0]['ass_t'],'id','typeshort',$value);
      return get_cell($db,$tableinfo->realname,'id',$field,$value);
   }
   elseif (!$first) {
      return get_cell($db,$tableinfo->realname,'id',$field,$value);
   }
   return $value;
}
?>
