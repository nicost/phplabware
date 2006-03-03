<?php

// functions_inc.php - Functions for all scripts
// functions_inc.php - author: Nico Stuurman <nicost@sourceforge.net>
  /***************************************************************************
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  Part of phplabware, a web-driven groupware suite for research labs      *
  *  This file contains classes and functions needed in all script.          *
  *                                                                          *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/  

/**
 *  class to get and store browser/OS info
 *
 */
class cl_client {
   var $browser;
   var $OS;

   function cl_client () {
      $HTTP_USER_AGENT = getenv (HTTP_USER_AGENT);
      $temp = strtolower ($HTTP_USER_AGENT);
      if (strstr($temp, "opera"))
         $this->browser = "Opera";
      elseif (strstr($temp, "msie"))
         $this->browser = "Internet Explorer";
      elseif (strstr($temp, "mozilla/4"))
         $this->browser = "Netscape 4";
      else
         $this->browser = "Netscape or the like";
      if (strstr($temp, "windows"))
         $this->OS = "Windows";
      elseif (strstr($temp, "linux"))
         $this->OS = "Linux";
      elseif (strstr($temp, "sunos"))
         $this->OS = "Sun";
      elseif (strstr($temp, "mac"))
         $this->OS = "Mac OS";
      elseif (strstr($temp, "irix"))
         $this->OS = "IRIX";
   }
}      

/**
 *  returns a string with a 'nice' representation of the input number of bytes
 *
 */
function nice_bytes ($bytes) {
   $last = $bytes[strlen($bytes)-1];
   $bytes = (float) $bytes;
   if (!is_float($bytes) ) return false;
   if ($last == "M") $bytes = $bytes*1048576;
   if ($bytes==0) return "0 bytes";
   elseif ($bytes==1) return "1 byte";
   elseif ($bytes < 1024) return "$bytes byte";
   elseif ($bytes < 16384) {
      $bytes = $bytes/1024;
      $bytes = number_format($bytes,1);
      return "$bytes kb";
   }
   elseif ($bytes < 1048576) {
      $bytes = $bytes/1024;
      $bytes = number_format($bytes,0);
      return "$bytes kb";
   }
   elseif ($bytes < 16777216) {
      $bytes = $bytes/1048576;
      $bytes = number_format($bytes,1);
      return "$bytes Mb";
   }   
   elseif ($bytes < 1073741824){
      $bytes = $bytes/1048576; 
      $bytes = number_format($bytes,0);
      return "$bytes Mb";      
   }
   elseif ($bytes >= 1073741824) {
      $bytes = $bytes/1073741824;
      $bytes = number_format($bytes,1);
      return "$bytes Gb";
   }        
   else return "$bytes byte";
}


/**
 *  function that checks if user is allowed to view page
 *
 * This function should be called before a printheader() call
 */
function allowonly($required, $current) {
   if (! ($required & $current)) {
      printheader('Not Allowed');
      navbar(1);
      echo "&nbsp;<br><h3 align='center'>Sorry, but this page is not for you!";
      echo "</hr><br>&nbsp;\n";
      printfooter();
      exit;
   }
}


/**
 * returns a link to a randomly selected image from the designated directory
 * imagedir should be relative to the running script and be web-accessible
 */
function randomImage($imagedir) {
   // determine contents of imagedir and store imagefilenames in imagearray
   $dirhandle = opendir ($imagedir);
   if (!$dirhandle)
      return false;
   $j = 0;
   while ($file = readdir ($dirhandle)) {
      if (strstr ($file, ".png") || strstr($file, ".jpg") || 
                                    strstr($file,".gif") ) {
         $imagearray[$j]=$file;
         $j++;
      }
   }
   $filecount=sizeof ($imagearray);
   // no files
   if (!$filecount)
      return false;
   // 'select' a random file
   srand((double)microtime()*1000000);
   if ($filecount>1)
      $filenr=rand (0,$filecount-1);
   else
      $filenr=0;
   // construct link to randomly selected file
   $filename=$imagearray[$filenr];
   return "<img src='$imagedir/$filename' alt='Pretty BioPicture here'>\n";   
}

/**
 *  returns get vars plus SID when needed
 *
 */
function url_get_string ($url) {
   $get_string=getenv("QUERY_STRING");
   $sid=SID;
   if ($get_string) {
      $url=$url."?$get_string";
      if ($sid)
         $url=$url."&amp;$sid";
      return $url;
   }
   if ($sid)
      $url=$url."?$sid";
   return $url;
}

/**
 *  presents the login screen when authenticating witth sessions
 *
 */
function loginscreen ($message="<h3>Login to PhpLabWare</h3>") {
   global $_SERVER, $system_settings;

   $PHP_SELF=$_SERVER['PHP_SELF'];
   if ($system_settings['secure_server']) {
      $server= getenv ('HTTP_HOST');
      if (!$server)
         $server=$_SERVER['HTTP_HOST'];
      $addres="https://$server$PHP_SELF";
   }
   else
      $addres=$PHP_SELF;
   $addres=url_get_string($addres);
   printheader ("Login to PhpLabWare");
   echo "<form name='loginform' method='post' action='$addres' onSubmit='this.javascript_enabled.value=\"true\"'>\n";
   echo "<input type='hidden' name='logon' value='true'>\n";
   echo "<input type='hidden' name='javascript_enabled' value='0'>\n";
   echo "<table align=center>\n";
   echo "<tr><td colspan=2 align='center'>$message</td>\n";
   $imstring = randomimage("frontims");
   if ($imstring);
      echo "<td rowspan=6>&nbsp;&nbsp&nbsp;$imstring</td>";
   echo "</tr>\n";
   echo "<tr><td>Your login name:</td>\n";
   echo "<td><input name='user' size=10 value=''></td></tr>\n";
   echo "<tr><td>Password:</td>\n";
   echo "<td><input type='password' name='pwd' size=10 value=''></td></tr>\n";
   echo "<tr><td colspan=2 align='center'>";
   if ($system_settings["secure_server"]) {
      echo "<input type='checkbox' name='ssl' checked>Keep a secure connection";
   }
   echo "</td></tr>\n";
   echo "<tr><td colspan=2 align='center'>";
   echo "<input type='submit' name='submit' value='Login'></td></tr>\n";
   echo "<tr><td colspan=2 align='center'>";
   //echo "Note:  Cookies must be enabled beyond this point</td></tr>\n";
   echo "</table>\n</form>\n";
   printfooter();
}


/**
 *  checks wether variables are present in ${type} and makes them available
 *
 * variables are only set when they are not null in ${type}
 * $var_string is a comma delimited list
 */
function globalize_vars ($var_string, $type) {

   if ($var_string && $type) {
      $var_name = strtok ($var_string, ',');
      global ${$var_name};
      if (!${$var_name})
         ${$var_name} = $type[$var_name];
      while ($var_name) {
         $var_name = strtok (",");
         global ${$var_name};
         if (!${$var_name})
            ${$var_name} = $type[$var_name];
      }
   }
}


/**
 *  Return the value of specified cell in the database
 *
 * Returns false if no or multiple rows have requested value 
 */
function get_cell ($db, $table, $column, $column2, $value) {
   $query="SELECT $column FROM $table WHERE $column2='$value'";
   $result=$db->Execute($query);
   if ($result) {
      $out=$result->fields[0];
   }
   else {
      return false;
   }
   $result->MoveNext();
   if ($result->EOF) {
      return $out;
   }
   else {
      return false;
   }
}

/**
 *   Returns a formatted link with name of the person identified by id
 *
 */
function get_person_link ($db,$id) {
   $query="SELECT firstname,lastname,email FROM users WHERE id=$id";
   $r=$db->Execute($query);
   if ($r->fields["email"]) {
      $submitter="<a href='mailto:".$r->fields["email"]."'>";
      $submitter.= $r->fields["firstname"]." ".$r->fields["lastname"]."</a> ";
   }
   else {
      $submitter=$r->fields["firstname"]." ";
      $submitter.=$r->fields["lastname"] ." ";
   }
   return $submitter;
}

/**
 *  Prints a table with usefull links 
 *
 */
function navbar($permissions) {
   include ('./includes/defines_inc.php');
   global $db, $USER, $PHP_SELF, $_SESSION; 
   if ($_SESSION['javascript_enabled'] && $USER['settings']['menustyle']) 
      $mode='menu';
   if ($mode=='menu') { 
      // construct link menu
      if ($permissions) {
         $r=$db->Execute("select display from tableoftables where tablename ='linkbar'");
         if ($r->fields[0]=='1') {
            $linkr=$db->Execute("select label,linkurl,target from linkbar where display ='Y' ORDER by sortkey");
            if ($linkr) {
               $linkmenu="<select name='themenu' onchange='linkmenu(this)'>\n";
               $linkmenu.="<option value=''>--Links--</option>\n";
               while (!$linkr->EOF) {
                  $Tlinkname=$linkr->fields[0];
                  $urlname=$linkr->fields[1];
                  // we use 'target ' as a code for js linkmenu to open a new win
                  if ($linkr->fields[2]=='N')
                     $urlname='target '.$urlname;
                  $linkmenu.="<option value='$urlname'>$Tlinkname</option>\n";    
                  $linkr->MoveNext(); 
               }
               $linkmenu.="</select>\n";
            }  
         }
         else
            $linkmenu="&nbsp;";
      }

      // construct Table menu
      $records=$db->Execute("select tablename,custom,id,label from tableoftables where display='Y' and permission='Users' ORDER by sortkey");
      $count=0;
      if ($records && $USER) {
         $query="SELECT tableid FROM groupxtable_display WHERE (groupid='".$USER["group_array"][0]."' ";
         for ($i=1;$i<sizeof($USER['group_array']);$i++)
            $query.="OR groupid='".$USER['group_array'][$i]."' ";
         $query.=')';
         $rb=$db->Execute($query);
         while ($rb && !$rb->EOF) {
            $showtables[]=$rb->fields['tableid'];
            $rb->MoveNext();
         }
         $tablemenu="<select name='tablemenu' onchange='linkmenu(this)'>\n";
         $tablemenu.="<option value=''>--Databases--</option>\n";
         while (!$records->EOF) {
            if (in_array($records->fields['id'],$showtables)) {
               $tabname=$records->fields[0];
               $scriptname=$records->fields[1];
               $label=$records->fields['label'];
               $linkname='';
               if ($scriptname=='')
                  $linkname="general.php?tablename=$tabname&amp;".SID;
               else 
                  $linkname=$scriptname.'?'.SID;
               // Allow use of separators, everything that starts with three dashes will not result in a link
               if (substr($label,0,3)=='---')
                  $tablemenu.="   <option value=''>$label</option>\n";    
               else
                  $tablemenu.="   <option value='$linkname'>$label</option>\n";    
            }
            $records->MoveNext(); 
         }
         $tablemenu .="</select>\n";
      }	

      // construct system menu
      $systemmenu="<select name='systemmenu' onchange='linkmenu(this)'>\n";
      $systemmenu.="<option value=''>--System--</option>\n";
      if (SID)
         $SID="?".SID;
      if ($permissions) {
         $systemmenu.="   <option value=\"users.php?type=me&amp;dummy=true&amp;".SID."\">my settings</option>\n";
         $systemmenu.="   <option value='views.php?".SID."'>table views</option>\n";
      }
      if ($permissions & $ADMIN)
         $systemmenu.="   <option value='users.php$SID'>users</option>\n";
      if ($permissions & $SUPER) {
         $systemmenu.="   <option value='groups.php$SID'>groups</option>\n";
         $systemmenu.="   <option value=''>-------</option>\n";
         $systemmenu.="   <option value='tablemanage.php$SID'>table design</option>\n";
         $systemmenu.="   <option value='restoretable.php$SID'>import table</option>\n";
         $systemmenu.="   <option value='dumptable.php$SID'>export table</option>\n";
         $systemmenu.="   <option value='import.php$SID'>import data</option>\n";
         $systemmenu.="   <option value='dumpcontent.php$SID'>export data</option>\n";
         $systemmenu.="   <option value=''>-------</option>\n";
         $systemmenu.="   <option value='linkbar.php$SID'>linkbar</option>\n";
         $systemmenu.="   <option value='setup.php$SID'>setup</option>\n";
      }
      if ($permissions) {
         $systemmenu.="   <option value='logout.php$SID'>logout</option>\n";
         $systemmenu .="</select>\n";
      }
      else
         $systemmenu="<a href='login.php'>login</a>\n";

      // display the menus
      echo "<form name='menubar' action='$PHP_SELF'>\n";
      echo "<table border=0 width='100%' cellspacing='0' cellpadding='0' bgcolor='eeeeff'>\n";
      echo "<tr>\n";
      echo "<td width='7%'>&nbsp;</td>\n";
      echo "<td width='1%'>$linkmenu</td>\n";
      echo "<td width='1%'>$tablemenu</td>\n";
      echo "<td width='1%'>$systemmenu</td>\n";
      echo "<td width='90%'>&nbsp;</td>\n";
      echo "</tr>\n</table>\n</form>\n";
   }
   
   // No javascript or the user preferes the old style menus:
   else {
      $links_per_row=6;
   
      if ($permissions & $ACTIVE) {
				
         echo "<table border=0 width='100%' cellspacing='0' cellpadding='0' bgcolor='eeeeff'>\n";
         $records=$db->Execute("select tablename,custom,id,label from tableoftables where display='Y' and permission='Users' ORDER by sortkey");
         $count=0;
         if ($records) {
            $query="SELECT tableid FROM groupxtable_display WHERE (groupid='".$USER["group_array"][0]."' ";
            for ($i=1;$i<sizeof($USER['group_array']);$i++)
               $query.="OR groupid='".$USER['group_array'][$i]."' ";
	    $query.=')';
            $rb=$db->Execute($query);
            while ($rb && !$rb->EOF) {
               $showtables[]=$rb->fields['tableid'];
               $rb->MoveNext();
            }
            while (!$records->EOF) {
               if (in_array($records->fields['id'],$showtables)) {
                  if (($count % $links_per_row)==0)
                     echo "</tr>\n<tr bgcolor='eeeeff' align='center'>\n";
                  $tabname=$records->fields[0];
                  $scriptname=$records->fields[1];
                  $label=$records->fields['label'];
                  $linkname="";
                  if ($scriptname=='')
                     $linkname="general.php?tablename=$tabname&amp;".SID;
                  else 
                     $linkname=$scriptname.'?'.SID;
                  echo "      <td style='width: 20%' align='center'><a href=\"$linkname\">$label</a></td>\n";
                  $count++;
	       }
               $records->MoveNext(); 
            }	
            // the following is needed to make table look decent in Netscape 4
            $range=$count % $links_per_row;
            if ($range) {
               $range=$links_per_row-$range;
               for ($i=0;$i<$range;$i++)
                  echo "<td style='width: 20% align='center'>&nbsp;</td>\n";
            }
         } 
      }
      echo "</tr>\n</table>\n\n";

      echo "<table border=0 width='100%' cellspacing='0' cellpadding='0'>\n";
      echo "<tr bgcolor='eeeeff' align='center'>";
      if ($permissions) {
         ?>
      <td align='center'><a href="users.php?type=me&amp;<?=SID?>">settings</a></td>
<?php 
      }
      if ($permissions & $ADMIN) {
         ?>      
      <td align='center'><a href="users.php?<?=SID?>">users</a></td>
<?php
      }
      if ($permissions & $SUPER) {
         ?>
      <td align='center'><a href="groups.php?<?=SID?>">groups</a></td>
      <td align='center'><a href="tablemanage.php?<?=SID?>">tables</a></td>
      <td align='center'><a href="linkbar.php?<?=SID?>">linkbar</a></td>
      <td align='center'><a href="setup.php?<?=SID?>">system</a></td>
<?php
      }
      if ($permissions) {
         ?>
      <td  align='right'><a href="logout.php?<?=SID?>">logout</a>&nbsp;</td>
<?php
      }
      else
         echo "<td align='right'><a href='login.php'>login</a></td>";
      echo "</tr>\n</table>\n<hr>\n";
   }
   echo "<!--************************END OF NAVBAR**********************-->\n";
}

/**
 *  adds javascript headers to argument
 *
 */
function add_js ($script) {
   $out="\n<script language='Javascript'>\n<!--\n";
   $out.=$script;
   $out.="\n//End Javascript -->\n</script>\n\n";
   return $out;
}

/**
 *  Prints initial part of webpage
 *
 * jsfiles can be a single file or an array containing a list of files
 */
function printheader($title,$head=false, $jsfiles=false) {
   global $client,$db,$version,$active,$USER,$_SESSION;

   // let Netscape 4 users use their back button
   // all others should not cache
   if ($client->browser != 'Netscape 4') {
      header('Cache-Control: private, no-cache, must-revalidate');
      header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
      header('Pragma: no-cache');
   }
   header ('Content-Type: text/html; charset=ISO-8859-1');

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
<?php 
echo $head;
if ($_SESSION['javascript_enabled'] && $USER['settings']['menustyle']) {
   echo "\n<script type='text/javascript' language='Javascript'>\n<!--\n";

   // include single or multiple javascript files
   if (is_array($jsfiles)) {
      foreach ($jsfiles as $jsfile) {
         if ($jsfile && is_readable($jsfile)) {
            readfile($jsfile);
         }
      }
   } elseif ($jsfiles && is_readable($jsfiles)) {
      readfile($jsfiles);
   }
   // always include the javascript code to build the drop down menu
   readfile('./includes/js/linkmenu.js');
   echo "\n// End Javascript -->\n</script>\n\n";
   $mode='menu';
} 
if ($mode<>'menu') {
      $links_per_row=5;
      $r=$db->Execute("select display from tableoftables where tablename ='linkbar'");
      if ($r->fields[0]=='1') {
         $linkr=$db->Execute("select label,linkurl,target from linkbar where display ='Y' ORDER by sortkey");
         if ($linkr) {
            while (!$linkr->EOF) {
               if ($count && (($count%$links_per_row)==0) )
                 $linkbar.="</tr><tr bgcolor='333388' align='center'>";
               $Tlinkname=$linkr->fields[0];
               $urlname=$linkr->fields[1];
               if ($linkr->fields[2]=='S')
                  $targetstr="target='_TOP'";
               else 
                  $targetstr="target='_BLANK'";
               $linkbar.="<td style='width: 20%' align='center'><a href=\"$urlname\" $targetstr><font color='#ffffff'>$Tlinkname</font></a></td>\n";
               $count++;
               $linkr->MoveNext(); 
            }
         } 
      }
   }

// set the desired stylesheet.  Make sure it exists
if (!isset($USER['settings']['style'])) {
   $USER['settings']['style']='phplabware.css';
}
$stylesheet='stylesheets/'.$USER['settings']['style'];
if (!@is_readable($stylesheet)) {
   $stylesheet='stylesheets/phplabware.css';
}
?> 
<TITLE><?php echo "$title" ?></TITLE>
<LINK rel="STYLESHEET" type="text/css" href="<?php echo $stylesheet?>">
</HEAD>
<BODY BGCOLOR="#ffffff">
<table width="100%" rules="none" border="0" cellspacing="0" cellpadding="0">
<tr><td>
<table width="100%" rules="none" border="0" cellspacing="0" cellpadding="0" bgcolor="333388">
<tr class='header' bgcolor="333388">
<?php
   // get the time
   $today = date('F j, Y, g:i a');
   // first display the linkbar if activated
   // only show linkbar when we have been authenticated
   if ($active) {
      echo '<td>';
      if ($mode<>'menu')
         echo "$linkbar";
      echo '</td>';
   }

   ?>
      <td align=left>
         <font color="#ffffff"><b><i>
         <?php echo "$today"; ?>
         </i></b></font>
      </td>
      <td align=right>
         <a href="http://phplabware.sourceforge.net">
         <font color="#ffffff"><i>PhpLabWare  
             <?php if ($version) echo "version $version"; ?>&nbsp;</i></font>
         </a>
      </td>
   </tr>
</table>
</td></tr>
<tr><td>
<a name="top"></a>
<!--************************END OF PRINTHEADER**************************-->

<?php
}

/**
 *  Prints footer
 *
 */
function printfooter($db=false,$USER=false) {
?>

<!--********************START OF PRINTFOOTER****************************-->
&nbsp;<br>
<hr>
</td></tr>
</table>
</BODY>
</HTML>

<?php
   if ($db && $USER["settings"])
      $db->Execute("UPDATE users SET settings='".serialize($USER["settings"])."'
       WHERE id=".$USER["id"]);
}
?>
