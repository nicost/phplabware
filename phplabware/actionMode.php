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

// mains includes
require ('./include.php');
require('./includes/db_inc.php');


$tableinfo=new tableinfo($db,false,$_POST['tableid']);

if (may_see_table($db,$USER,$tableinfo->id) && may_write($db,$tableinfo->id,$_post['recordid'],$USER)) {
   $db->Execute("UPDATE {$tableinfo->realname} SET {$_POST['field']}='{$_POST['newvalue']}' WHERE id={$_POST['recordid']}");
}
//print_r($HTTP_POST_VARS);

?>
