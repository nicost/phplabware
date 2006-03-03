<?php
require('./include.php');

// register variables
$pdbid=$_POST['pdbid'];

globalize_vars($post_vars, $_POST,$DBNAME,$DB_DESNAME,$system_settings);
printheader($httptitle);
if ($pdbid)
	{
	$string="http://www.rcsb.org/pdb/cgi/export.cgi/$pdbid.pdb?format=PDB&pdbId=$pdbid&compression=None";
	echo "<meta http-equiv='refresh' content='1;url=$string'>";
	}	

?>
<form method='post' id='pdbform' enctype='multipart/form-data' action='<?php echo $PHP_SELF?>?<?=SID?>'> 
<?php

echo "<center><h3> This will download pdb files to your local machine<br></center></h3>";
echo "<table border=0 align='center'>\n";
echo "<tr><th>PDBID:</th><td></td>\n";
echo "<td><input type='text' name='pdbid' value='$pdbid' size=60>";
echo "<td colspan=7 align='center'><input type='submit' name='submit' value='$value'>\n";
echo "<input type='submit' name='submit' value='Cancel'></td></tr>\n";
echo "</table></form>\n";

?> 
<center><a href="#" onClick="MyWindow2=window.open('http://www.rcsb.org/pdb/searchlite.html','MyWindow2','toolbar=yes,location=no,directories=no,status=no,menubar=yes,scrollbars=yes,resizable=yes,width=600,height=300'); return false;">
Click here</a> to open another window to browse the rcsb database<br><p>
<a href="javascript:window.close();">Close Window</a></center>

