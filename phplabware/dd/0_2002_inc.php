<?php

// Translates columns access into columns gr,gw,er,ew

unset ($rb);
$rb=$db->Execute("SELECT real_tablename FROM tableoftables");
while (!($rb->EOF)) {
   if($rb->fields['real_tablename']) {
      unset ($rc);
      $rc=$db->Execute("SELECT id,access FROM {$rb->fields['real_tablename']}");
      while ($rc && !$rc->EOF) {
         if ($rc->fields['access']{3}=='r')
            $gr=1;
         else
            $gr=0;
         if ($rc->fields['access']{4}=='w')
            $gw=1;
         else
            $gw=0;
         if ($rc->fields['access']{6}=='r')
            $er=1;
         else
            $er=0;
         if ($rc->fields['access']{7}=='w')
            $ew=1;
         else
            $ew=0;
         $db->Execute("UPDATE {$rb->fields['real_tablename']} SET gr=$gr,gw=$gw,er=$er,ew=$ew WHERE id={$rc->fields['id']}");
         $rc->MoveNext();
      }
   }
   $rb->MoveNext();
}
