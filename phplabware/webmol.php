<?php
// webmol.php - a simple script to launch Webmol for any given PDB
// webmol.php - author: Nico Stuurman
// Copyright Nico Stuurman, 2002

// This code is Free Software and licensed under the GPL version 2 or later (your choice).  Do some cool stuff with it!


require ("./include.php");
// pdbid can be transfered as a GET or POST variable
$pdbid=$_GET["pdbid"];
if (!$pdbid) 
   $pdbid=$_POST["pdbid"];
if (!$pdbid) {
   echo "It does not make much sense to launch webmol without a molecule...";
   exit();
}
$pdbid=strtolower($pdbid);
// set wherever you'll run Webmol from:
$codebase="http://www.cmpharm.ucsf.edu/~walther/webmol/";


$httptitle .= "Webmol:$pdbid";
printheader ($httptitle);
navbar($USER["permissions"]);
?>
<table align='center' border='0'>
<tr><td>
<applet code="proteinViewer.class"
codebase=<?php echo "'$codebase'"; ?>
  width=700 height=600 align='center'>
<PARAM NAME="PROTEIN"
VALUE=<?php echo "'$pdbid'";?>>
<PARAM NAME="PDB_STRING"
VALUE="">
<PARAM NAME="PATH" VALUE="">
<?php echo "<PARAM NAME='URL' VALUE='$codebase/pdb/'>"?>
<PARAM NAME="EXT" VALUE="ent">
<PARAM NAME="SearchString"
VALUE="varSnfnvcrl|C|lpgtPEaicaty">
</applet>
</tr></td>
</table>
<?php
printfooter ();
?>
