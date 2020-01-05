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
 * The following are optional:
 * datatype
 */

// main includes
require ('./include.php');
require('./includes/db_inc.php');


$tableinfo=new tableinfo($db,false,$_POST['tableid']);

// don't have these fields changed:
$forbidden_fields=array ('id','ownerid');
if (in_array($_POST['field'],$forbidden_fields)) {
   return false;
}
if ($_POST['datatype']=='date') {
   $_POST['newvalue']=strtotime($_POST['newvalue']);
}

//$db->debug=true;
if (may_see_table($db,$USER,$tableinfo->id) && 
            may_write($db,$tableinfo->id,$_POST['recordid'],$USER)) {
   if ($_POST['datatype']=='mpulldown') {
      // $newvalue is a comma separated list with ids of the selected items
      // remove the last (extra) comma)
      $_POST['newvalue']=substr($_POST['newvalue'],0,-1);
      $valueArray=explode(',',$_POST['newvalue']);
      // figure out name of keytable
      $keytable=get_cell($db,$tableinfo->desname,'key_table','columnname',$_POST['field']);
      if ($keytable) {
         update_mpulldown($db,$keytable,$_POST['recordid'],$valueArray);
      } 
   } else {
      // if the value is really empty, set to NULL, this is essential for numeric fields
      if ($_POST['newvalue'] =='') {
         $result = $db->Execute("UPDATE {$tableinfo->realname} SET {$_POST['field']}=NULL WHERE id={$_POST['recordid']}");
      } else { 
	 // escape nasty stuff before sending it the database 
	 $_POST['newvalue']=addslashes($_POST['newvalue']);
         $result= $db->Execute("UPDATE {$tableinfo->realname} SET {$_POST['field']}='{$_POST['newvalue']}' WHERE id={$_POST['recordid']}");
      }
      if ($result) {
         // The javascript code likes this answer, otherwise it will reload
         echo "SUCCESS!";
      } else {
         echo "FAILED!";
      }
   }
}

?>
