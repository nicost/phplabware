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

if (may_see_table($db,$USER,$tableinfo->id) && may_write($db,$tableinfo->id,$_post['recordid'],$USER)) {
   $db->Execute("UPDATE {$tableinfo->realname} SET {$_POST['field']}='{$_POST['newvalue']}' WHERE id={$_POST['recordid']}");
}

?>
