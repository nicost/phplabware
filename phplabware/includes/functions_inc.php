<?php

// functions.php - Functions for all scripts
// functions.php - author: Nico Stuurman <nicost@sourceforge.net>
  /***************************************************************************
  * Copyright (c) 2001 by Nico Stuurman                                      *
  * ------------------------------------------------------------------------ *
  *  Part of phplabware, a web-driven groupware suite for research labs      *
  *                                                                          *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/  


////
// !checks wether variables are present in ${type} and makes them available
// variables are only set when they are not null in ${type}
// $var_string is a comma delimited list
function globalize_vars ($var_string, $type) {
   global ${$type};

   if ($var_string && $type) {
      $var_name = strtok ($var_string, ",");
      global ${$var_name};
      if (!${$var_name})
         ${$var_name} = ${$type}["$var_name"];
      while ($var_name) {
         $var_name = strtok (",");
         global ${$var_name};
         if (!${$var_name})
            ${$var_name} = ${$type}["$var_name"];
      }
   }
}


////
// !Return the value of specified cell in the database
// Returns false if no or multiple rows have requested value 
function get_cell ($db, $table, $column, $column2, $value) {
   $query="SELECT $column FROM $table WHERE $column2='$value'";
   $result=@$db->Execute($query);
   if ($result) {
      $out=$result->fields[$column];
   }
   else
      return false;
   $result->MoveNext();
   if ($result->EOF)
      return $out;
   else
      return false;
}


////
// !Prints a table with usefull links 
function navbar($permissions) {
   include ('includes/defines_inc.php');

   echo "<table border=0 width=100%>\n";
   echo "<tr bgcolor='eeeeff' align='right'>\n";
   if ($permissions & $ADMIN) {
      echo "<td align='center'><a href='users.php'>users</a></td>";
   }
   if ($permissions & $SUPER) {
      echo "<td align='center'><a href='groups.php'>groups</a></td>";
      echo "<td align='center'><a href='setup.php'>system</a></td>";
   }
   if ($permissions)
      echo "<td align='right'><a href='logout.php'>logout</a></td>";
   else
      echo "<td align='right'><a href='login.php'>login</a></td>";
   echo "</tr>\n</table>";
}


////
// !Prints initial part of webpage
function printheader($title) {
   global $version;

   header("Cache-Control: private, no-cache, musti-revalidate");
   header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
   header("Pragma: no-cache");
?>
<!DOCTYPE HTML PUBLIC "-//W3C/DTD HTML 4.01 TRANSITIONAL//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
<TITLE><?php echo "$title" ?></TITLE>
<LINK rel="STYLESHEET" type="text/css" href="phplabware.css">
</HEAD>

<BODY BGCOLOR="#ffffff">
<a name="top"></a>
<table border=0 width=100%>
   <tr bgcolor="333388" align=right>
      <td align=right>
         <font size=+2 color="#ffffff"><i>PhpLabWare  
             <?php if ($version) echo "version $version"; ?></i></font>
      </td>
   </tr>
</table>
<!--************************END OF PRINTHEADER**************************-->

<?php

}

////
// !Prints footer
function printfooter() {
?>

<!--********************START OF PRINTFOOTER****************************-->
<hr>
</BODY>
</HTML>

<?php

}
?>
