<?php
// For the sake of full text searches, from now on only store the first page on which a word occurred.  Here, we delete all entries with later occurrences:

function clean_text_column($table)
{
   $r=$db->Execute("SELECT wordid,fileid,pagenr,recordid FROM $table ORDER BY pagenr");
   while (!$r->eof()) {
      $rdouble=$db->Execute("SELECT wordid,fileid,pagenr,recordid FROM $table WHERE wordid={$r->fields[0]} AND fileid={$r->fields[1]} AND recordid={$r->fields[3]} ORDER BY pagenr");
       $rdouble->MoveNext();
       if ($rdouble->Numrows() > 1) {
          while (!$rdouble->eof()) {
             $db->Execute("DELETE FROM $table WHERE wordid={$rdouble->fields[0]} AND fileid={$rdouble->fields[1]} AND pagenr={$rdouble->fields[2]} AND recordid={$rdouble->fields[3]}");
             $rdouble->MoveNext();
          }
       }
   }
}
