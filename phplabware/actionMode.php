<?php

/**
 * Modifies a single field in a record in a given table 
 *
 * Part of Phplabware
 * 
 * @License: GPL
 * @author: Nico Stuurman
 * @date: Nov. 2004
 */

/**
 * The following POST variables are required:
 * tableid,recordid,field,newvalue
 */

// main includes
require ('./include.php');
require('./includes/db_inc.php');

$tableinfo=new tableinfo($db,false,$_POST['tableid']);

// don't have these fields changes:
$forbidden_fields=array ('id','ownerid');
if (in_array($_POST['field'],$forbidden_fields)) {
   return false;
}
if ($_POST['datatype']=='date') {
   $_POST['newvalue']=strtotime($_POST_newvalue);
}

//$db->debug=true;
if (may_see_table($db,$USER,$tableinfo->id) && may_write($db,$tableinfo->id,$_post['recordid'],$USER)) {
   $db->Execute("UPDATE {$tableinfo->realname} SET {$_POST['field']}='{$_POST['newvalue']}' WHERE id={$_POST['recordid']}");
}

?>
