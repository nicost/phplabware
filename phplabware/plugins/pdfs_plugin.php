<?php

// plugin_inc.php - skeleton file for plugin codes
// plugin_inc.php - author: Nico Stuurman

/** 
 * Plugin functions for  the pdfs table
 *
 * @author Nico Stuurman
 *
 * Copyright Nico STuurman, 2002
 *
 *
 *
 * This is a skeleton file to code your own plugins.
 * To use it, rename this file to something meaningfull,
 * add the path and name to this file (relative to the phplabware root)
 * in the column 'plugin_code' of 'tableoftables', and code away.  
 * And, when you make something nice, please send us a copy!
 * 
 * This program is free software: you can redistribute it and/ormodify it under
 * the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 *
 */


////
// ! outputs to a file a reference plus link to the newly added pdf
function plugin_add ($db,$tableid,$id) 
{
   global $PHP_SELF,$system_settings;
   $table_desc=get_cell($db,"tableoftables","table_desc_name","id",$tableid);
   $tablename=get_cell($db,"tableoftables","tablename","id",$tableid);
   $real_tablename=get_cell($db,"tableoftables","real_tablename","id",$tableid);
   $journaltable=get_cell($db,$table_desc,"associated_table","columnname","journal");

   $r=$db->Execute("SELECT ownerid,title,journal,pubyear,volume,fpage,lpage,author FROM $real_tablename WHERE id=$id");
   $fid=@fopen($system_settings['pdfs_file'],'w');
   if ($fid) {
      $link= $system_settings['baseURL'].getenv("SCRIPT_NAME")."?tablename=$tablename&showid=$id";
      $journal=get_cell($db,$journaltable,'type','id',$r->fields['journal']);
      $submitter=get_person_link($db,$r->fields['ownerid']);
      $text="<a href='$link'><b>".$r->fields['title'];
      $text.="</b></a> $journal (".$r->fields['pubyear']."), <b>".$r->fields['volume'];
      $text.="</b>:".$r->fields['fpage'].'-'.$r->fields['lpage'];
      $text.= '. '.$r->fields['author']." Submitted by $submitter.";
      fwrite($fid,$text);
      fclose($fid);
   }
}


////
// !Change/calculate/check values just before they are added/modified
// $fieldvalues is an array with the column names as key.
// Any changes you make in the values of $fieldvalues will result in 
// changes in the database. 
// You could, for instance, calculate a value of a field based on other fields
function plugin_check_data($db,&$field_values,$table_desc,$modify=false) 
{

   global $HTTP_POST_FILES;
   // we need some info from the database
   $tableid=get_cell($db,'tableoftables','id',"table_desc_name",$table_desc);
   $pdftable=get_cell($db,"tableoftables","real_tablename","table_desc_name",$table_desc);
   $pdftablelabel=get_cell($db,"tableoftables","tablename","table_desc_name",$table_desc);
   $journaltable=get_cell($db,$table_desc,"associated_table","columnname","journal");

   // some browsers do not send a mime type??  
   if (is_readable($HTTP_POST_FILES['file']['tmp_name'][0])) {
      if (!$HTTP_POST_FILES['file']['type'][0]) {
         // we simply force it to be a pdf risking users making a mess
         $HTTP_POST_FILES['file']['type'][0]='application/pdf';
      }
   }
   // avoid problems with spaces and the like
   $field_values['pmid']=trim($field_values['pmid']);
   $pmid=$field_values['pmid'];

   
   // check whether we had this one already
   if (!$modify) {
      $existing_id=get_cell($db,$pdftable,'id','pmid',$field_values['pmid']);
      if ($existing_id) {
         echo "<h3 align='center'><a href='general.php?tablename=$pdftablelabel&showid=$existing_id'>That paper </a>is already in the database.</h3>\n";
         return false;
      }
   }

   // this will protect quotes in the imported data
   set_magic_quotes_runtime(1);

   if ($pmid) {
      // rename file to pmid.pdf
      if ($HTTP_POST_FILES['file']['name'][0]) {
         $HTTP_POST_FILES['file']['name'][0]=$field_values['pmid'].'.pdf';
      }
      // get data from pubmed and parse
      $pubmedinfo=@file("http://www.ncbi.nlm.nih.gov/entrez/utils/pmfetch.fcgi?db=PubMed&id=$pmid&report=abstract&report=abstract&mode=text");
      if ($pubmedinfo) {
         // lines appear to be broken randomly, but parts are separated by empty lines
         // get them into array $line
         for ($i=0; $i<sizeof($pubmedinfo);$i++) {
            $line[$lc].=str_replace("\n"," ",$pubmedinfo[$i]);
            if ($pubmedinfo[$i]=="\n")
	       $lc++;
         }
         // parse the first line.  1: journal  date;Vol:fp-lp
         $jstart=strpos($line[1],": ");
         $jend=strpos($line[1],". ")-1;
         $journal=trim(substr($line[1],$jstart+1,$jend-$jstart));
         $dend=strpos($line[1],";");
         $date=trim(substr($line[1],$jend+2,$dend-$jend-1));
         $year=$field_values['pubyear']=strtok($date," ");
         $vend=strpos($line[1],":",$dend);
         // if we can not find this, it might not have vol. first/last page
         if ($vend) {
            $volumeinfo=trim(substr($line[1],$dend+1,$vend-$dend-1));
            $volume=$field_values['volume']=trim(strtok($volumeinfo,"(")); 
            $pages=trim(substr($line[1],$vend+1));
            $fpage=strtok($pages,'-');
            $lpage1=strtok('-');
            $lpage=substr_replace($fpage,$lpage1,strlen($fpage)-strlen($lpage1));
         }
         // echo "$jstart,$jend,$journal,$date,$year,$volume,$fpage,$lpage1,$lpage.<br>";
         $field_values['fpage']=(int)$fpage;
         $field_values['lpage']=(int)$lpage;
         // there can be a line 2 with 'Comment in:' put in notes and delete
         // same for line with Erratum in:
         // ugly shuffle to get everything right again
         if ((substr($line[2],0,11)=="Comment in:") || (substr($line[2],0,11)=="Erratum in:") ) {
            $field_values['notes']=$line[2].$field_values['notes'];
            $line[2]=$line[3];
	    $line[3]=$line[4];
            $line[5]=$line[6];
         }
         $field_values['title']=$line[2];
         $field_values['author']=$line[3];
         // check whether there is an abstract
         if ((substr($line[5],0,4)!='PMID'))
            $field_values['abstract']=$line[5];
         // check wether the journal is in journaltable, if not, add it
         $r=$db->Execute("SELECT id FROM $journaltable WHERE typeshort='$journal'");
         if ($r && $r->fields('id'))
            $field_values['journal']=$r->fields('id');
         else {
            $tid=$db->GenID("$journaltable".'_id_seq');
            if ($tid) {
	       $r=$db->Execute("INSERT INTO $journaltable (id,type,typeshort,sortkey) VALUES ($tid,'$journal','$journal',0)");
	       if ($r)
	          $field_values["journal"]=$tid;
	    }
         }
      }
      else {
         echo "<h3>Failed to import the Pubmed data</h3>\n";
         set_magic_quotes_runtime(0);
         return true;
      }
   }

   // do a final check to see if we can commit these data
   if (!($field_values['title'] && $field_values['author'])) {
      echo "<h3>Please enter a Pubmed ID or provide at least the authors and title of this paper</h3>\n";
      set_magic_quotes_runtime(0);
      return false;
   }

   // check if there is a file (in database for modify, in _POST_FILES for add)
   if ($modify && !$HTTP_POST_FILES['file']['tmp_name'][0]) {
      // check in database whether there already is a file uploaded
      $r=$db->Execute("SELECT id FROM files 
                    WHERE tablesfk=$tableid AND ftableid={$field_values['id']}");
      if (isset($r->fields[0])) { 
         $file_uploaded=true;
      }
   } elseif ($HTTP_POST_FILES['file']['tmp_name'][0]) {
         $file_uploaded=true;
   }

   // some stuff goes wrong when this remains on
   set_magic_quotes_runtime(0);

   if (!$file_uploaded) {
      // no file uploaded, try to fetch it directly 
//echo "Calling fetch_pdf with: $pmid and $journal.<br>";
      fetch_pdf($pmid,$journal);
   }

   return true;
}


/**
 * Extracts host and getstring from a url
 */
function get_host_getstring($link,&$host,&$getstring) 
{
   preg_match("/^(http:\/\/)?([^\/]+)/i", $link, $matches);
   $host = $matches[2];
   $getstring=substr($link,strlen($matches[0]));
}


/**
 * Reads data from a http connection (webserver)
 * 
 * Optionally quites after reading just the header
 *
 * Returns the website as an array with 'header' and 'body' as members
 */
function read_web_page($host,$getstring,&$header,&$body,$headersonly=false,$timeout=5) 
{
   $fp=fsockopen($host,80,$errno,$errstr,$timeout);
   if ($fp) {
       $out="GET $getstring HTTP/1.0\r\n"; $out.="Host: $host\r\n";
       $out.="Connection: Close\r\n\r\n";
       fwrite($fp,$out);
       $header='';
       while ($str=trim(fgets($fp,4096))) {
          $header.=$str."\n"; 
       }
       if (!$headersonly) {
          $body='';
          while (!feof($fp) ) {
             $body.=fgets($fp,4096);
          }
       }
       return true;
   }
   return false;
}


/**
 * Searches a header for a 'Location: ' field and returns this when found
 * Returns false otherwise
 */
function get_location ($header)
{
   $line=strtok($header,"\n");
   while ($line) {
       $keyvalue=explode(": ",$line);
       if ($keyvalue[0]=='Location') {
           return $keyvalue[1];
       }
       $line=strtok("\n");
    }
    return false; 
}



/**
 * Finds the journal link through eutils elink
 *
 * When it knows the journal, will try to download the pdf directly
 *
 */
function fetch_pdf($pmid,$journal)
{ 
   include_once('./plugins/elink/eutils_elink_class.php');
   include_once ('./plugins/elink/simple_parser_xml.inc.php');

   if (! (isset($pmid) && isset($journal) )) {
      return false;
   }
   $search= new eutils_link($pmid);
   $search->setMaxResults(5);
   $search->setManualSearchParam('cmd','llinks');
   if ($search->doSearch()) {
      // we get the xml file back in a kind of funny array...
      foreach($search->parser->content as $hit) {
//print_r($hit);
         if (isset($hit['eLinkResult']['LinkSet']['IdUrlList']['IdUrlSet']['ObjUrl']['Url'])) {
            $links[]=$hit['eLinkResult']['LinkSet']['IdUrlList']['IdUrlSet']['ObjUrl']['Url'];
          }
      }
       
      foreach($links as $link) {
         /**
          * grep the base of the url and handle all know cases accordingly.
          * This is where we'll have to write grabbers for each journal
          */
         get_host_getstring($link,&$host,&$getstring); 
//echo "host: $host, getstring: $getstring.<br>";
         /**
          * Some links immediately link through to other (journal) sites
          * We better resolve these first before parsing out all the individual
          * sites
          * In this part we change the host and getstring before going into
          * the major switch statement
          */
         switch ($host) {
         case 'dx.doi.org':
            /** 
             * This leads to at the very least the Nature journals
             */
            if ($website=read_web_page($host,$getstring,$header,$body,true,5)) {
               if (substr($header,0,41)=='HTTP/1.1 302 Moved Temporarily
Location: ') {
                  $journallink=strtok(substr($header,41),"\n");
               }
//echo substr($header,0,41).".<br>\n";
//echo "$journallink.<br>\n";
               preg_match("/^(http:\/\/)?([^\/]+)/i", $journallink, $matches);
               $host = $matches[2];
               $getstring=substr($journallink,strlen($matches[0]));
//echo "host: $host, getstring: $getstring.<br>";
            }
         break;
         }
         /**
          * Here comes the part specific to each website/journal/organization
          * These will break anytime a website changes its organization
          */
         switch ($host) {
         case 'www.kluweronline.com':
             /**
              * These guys are straight and clear.  Thanks!
              */
             $website=read_web_page($host,$getstring,$header,$body,true,5); 
             $linktopdf=get_location($header);
             get_host_getstring($linktopdf,&$pdfhost,&$pdfgetstring); 
             if (do_pdf_download($pdfhost,$pdfgetstring,'file')) {
                 return true;
             }
         break;
         
         case 'www.pnas.org':
         case 'www.genesdev.org':
             $website=read_web_page($host,$getstring,$header,$body,true,5); 
             $linktopdf=get_location($header);
             /**
              * Massage the link to get the right thing
              */
             $linktopdf=str_replace('content/full','reprint',$linktopdf);
             $linktopdf.='.pdf';
//echo "link: $host$linktopdf.<br>\n";
             if (do_pdf_download($host,$linktopdf,'file')) {
                 return true;
             }
         break;
            
         /**
          * All of the following appear to be running the same software
          */
         case 'www.sciencemag.org':
         case 'dev.biologists.org':
         case 'www.biophysj.org':
         case 'mct.aacrjournals.org':
         case 'jcs.biologists.org':
         case 'www.jbc.org':
         case 'www.jcb.org':
             $website=read_web_page($host,$getstring,$header,$body,true,5); 
             $link=get_location($header);
             $link=str_replace('content/full','reprint',$link);
             $link.='.pdf';
//echo "$host$link.<br>";
             if (do_pdf_download($host,$link,'file')) {
                 return true;
             }
          break;

         case 'www.molbiolcell.org':
            /**
             * We could possibly just add '.pdf' to the getstring, but this
             * looks more robust
             */
             $getstring=str_replace('reprint','reprintframed',$getstring); 
             $website=read_web_page($host,$getstring,$header,$body,false,5); 
             $token=strtok($body,"\n");
             while ($token && !$done) {
                if ($link=strstr($token,'/cgi/reprint/')) {
                   $getstring=substr($link,0,strpos($link,'"'));
                   $done=true;
                }
                $token=strtok("\n");
             }
             if (do_pdf_download($host,$getstring,'file')) {
                 return true;
             }
         break;    

         case 'www.nature.com':
            /**
             * This should resolve most nature journals
             * The first link gives a redirect that we can create ourselves:
             */
            $getstring='/cgi-bin/doifinder.pl?URL='.$getstring;
            if ($website=read_web_page($host,$getstring,$header,$body,true,5)) { 
               /**
                * if we get a redirect find the new host and location
                */
                $linktopdf=get_location($header);
//echo "LINKTOPDF: $linktopdf.<br>\n";
                get_host_getstring($linktopdf,&$pdfhost,&$pdfgetstring); 
                /**
                 * It seems that we can now find the link to the pdf by taking
                 * the part after DynaPage.taf?file= up to the first _
                 * Also replace 'abs' with 'pdf'
                 */
                 $pdfgetstring=str_replace('abs','pdf',$pdfgetstring);
                 $start=strpos($pdfgetstring,'DynaPage.taf?file=')+18;
                 // this is not robust yet:
                 $end=strpos($pdfgetstring,'_');
                 if (!$end) {
                    $end=strpos($pdfgetstring,'.html');
                 }
                 $pdfgetstring=substr($pdfgetstring,$start,$end-$start).'.pdf';
                 if (do_pdf_download($pdfhost,$pdfgetstring,'file')) {
                     return true;
                 }
            }
         break;

         case 'linkinghub.elsevier.com':
            /**
             * elsevier offers the choise between science direct and journal 
             * specific links.  For now, explore science direct:
             */
            if ($website=read_web_page($host,$getstring,$header,$body,false,5)) { 
               // find the sciencedirect link in the page we just downloaded
               $line=strtok($body,"\n");
               while ($line) {
                  if ($sdirect=strstr($line,'http://www.sciencedirect.com')) {
//echo "FOUND SCIENCE DIRECT LINK<br>";
                     $sdirect=substr($sdirect,28,-2);
                     unset($line);
                  } else {
                     $line=strtok("\n");
                  }
               }
//echo "Sdirect: $sdirect.<br>";
               if ($sdirect) {
                  $sdirect=str_replace("&amp;",'&',$sdirect);
                  // Now follow a 'Moved Permanently in the header
                  $host='www.sciencedirect.com';
                  unset($header);
                  if (read_web_page($host,$sdirect,$header,$dummy,true,5)) { 
//echo "$host.<br>";
//echo "$out.<br>";
//echo $header;
                     $line=strtok($header,"\n");
                     while ($line) {
//echo "$line.<br>\n";
                        if ($pdflink=strstr($line,'http://www.sciencedirect.com')) {
                           $pdflink=substr($pdflink,28);
                           unset($line);
                        } else {
                           $line=strtok("\n");
                        }
                     }
//echo "PDFLINK: $pdflink.<br>\n";
                     unset($header);
                     unset($body);
                     // we finally get to the page with the link to the pdf
                     if (isset($pdflink) && read_web_page($host,$pdflink,$header,$body,false,5) ) {
                        // This one is tricky. Easiest way seems to be to spot the first part of the link.  The link is also split over mulitple lines
                        $line=strtok($body,"\n");
                        while ($line) {
                           if ($pdflink2=strstr($line,'http://www.sciencedirect.com/science?_ob=M')) {
                              while (!$position=strpos($line,">")) {
                                 $line=strtok("\n");
                                 $pdflink2.=$line;
                              }
                              $pdflink2=substr($pdflink2,0,strpos($pdflink2,'>'));
                              unset($line);
                           } else {
                              $line=strtok("\n");
                           }
                       }
                       get_host_getstring($pdflink2,$pdfhost,$pdfgetstring);
                       /**
                        * Sometimes we have a quote at the end
                        */
                       if (substr($pdfgetstring,strlen($pdfgetstring)-1,1)=='"') {
                          $pdfgetstring=substr($pdfgetstring,0,-1);
                       }
//echo "PDFLINK2: $pdfgetstring.<br>";

                       // if we have it we can do the real download
                        if (do_pdf_download($pdfhost,$pdfgetstring,'file')) {
                           return true;
                        }
                     }
                  }
               }
            }
         break; // end of linkinghub.elsevier.com
/*               
         case 'www.jcb.org':
            // jcb gives a page with a redirect on it.  The redirect has the link to the pdf on it, however, once the redirect address is known, we can simply construct  the link to the pdf and grab it.
            $fp=fsockopen($host,80,$errno,$errstr,5);
            if ($fp) {
               $out="GET $getstring HTTP/1.0\r\n"; $out.="Host: $host\r\n";
               $out.="Connection: Close\r\n\r\n";
               fwrite($fp,$out);
               while (!feof($fp)) {
                  $redirect.=fgets($fp,128);
               }
               fclose($fp);  
               // The header has the Location: field in it, that is what we need:
               $start=strpos($redirect,'Location: ') + 10;
               $end=strpos($redirect,'Connection');
               $url=substr($redirect,$start,$end-$start);
               // et voila, the url to the pdf:
               $url=str_replace('full','pdf',$url);
//echo "Url is: $host$url.<br>";
               if (isset($url)) {
                  if (do_pdf_download($host,$url,'file')) {
                     return true;
                  }
               }
            }
         break;
*/
         case 'www.pubmedcentral.gov' :
            /**
             * For pubmed central we need the 'artid'.  Retrieve this using elink
             */
            $searchid= new eutils_link($pmid);
            $searchid->setMaxResults(5);
            $searchid->setManualSearchParam('db','pmc');
            if ($searchid->doSearch()) {
               foreach($searchid->parser->content as $hit) {
                  if (isset($hit['eLinkResult']['LinkSet']['LinkSetDb']['Link']['Id'])) {
                     $artid=$hit['eLinkResult']['LinkSet']['LinkSetDb']['Link']['Id'];
                     if (is_numeric($artid)) { 
                        $url="/picrender.fcgi?artid=$artid&action=stream&blobtype=pdf";
                        if (do_pdf_download($host,$url,'file')) {
                             return true;
                        }
                     }
                  }
               }
            }
         break;
         }
      }
   }
}



/**
 * Given a host and url(the part after the host), downloads a pdf and stores info in HTTP_POST_FILES
 * 
 * @author Nico Stuurman
 */
function do_pdf_download ($host,$url,$fieldname) 
{
   global $HTTP_POST_FILES, $system_settings;

//echo "$host/$url<br>";
   // download the pdf, using a netsocket so that we can use the header
   $fp=fsockopen($host,80,$errno,$errstr,5);
   if ($fp) {
      /**
       * Construct http request
       */
      $out="GET $url HTTP/1.0\r\n";
      $out.="Host: $host\r\n";
      $out.="Connection: Close\r\n\r\n";
      /**
       * Initiate http request and read response
       */
      fwrite($fp,$out);
      while (!feof($fp) && strlen(trim($data=(fgets($fp,1024))))>1) {
         $header.=$data;
      }
      /**
       * Check headers that the mime type is pdf, if not bail out
       */
//echo "Final link headers:<br>\n";
//echo $header.".<br>";
      $headers=explode("\r\n",$header);
      foreach ($headers as $line) {
         $content=explode(': ',$line);
         if ($content[0]=='Content-Type') {
            $mime=$content[1];
         }
      }
//echo "Mime: $mime.<br>"; 
      if ($mime!='application/pdf') {
         fclose($fp);
         return false;
      }
      /**
       * Construct tmp filename, it would be best to put the file in the upload_temp_dir, but we can not always reliably determine what that is
       */
      $tmpfile=$system_settings['tmpdir'].'/'.uniqid('file');
      /**
       * reading from sockets seems unreliable in php < 4.3, use curl instead
       */
      if (version_compare(phpversion(),'4.3','<')) {
         $command="curl \"http://$host$url\" -o $tmpfile";
//echo "$command.<br>";
         `$command`;
         //`curl $host$url -o $tmpfile`; 
      } else {
         $fout=fopen ($tmpfile,'w');
         while ($fout && !feof($fp)) {
            fwrite($fout,fgets($fp,2048));
         }
         fclose($fout);
      }
      fclose($fp);
   } else { // Could not open socket
      return false;
   }

   // set relevant keys in $_POST:
   $HTTP_POST_FILES[$fieldname]['tmp_name'][0]=$tmpfile;
   $HTTP_POST_FILES[$fieldname]['name'][0]='pdf.pdf';
   $HTTP_POST_FILES[$fieldname]['type'][0]='application/pdf';
   $HTTP_POST_FILES[$fieldname]['size'][0]=filesize($tmpfile);
   $HTTP_POST_FILES[$fieldname]['error'][0]=0;

   /**
    * If we made it here we are doing OK
    */
   return true;
}




////
// !Overrides the standard 'show record'function
function plugin_show($db,$tableinfo,$id,$USER,$system_settings,$backbutton=true)
{
   global $PHP_SELF;
   $journaltable=get_cell($db,$tableinfo->desname,"associated_table","columnname","journal");
   $categorytable=get_cell($db,$tableinfo->desname,"associated_table","columnname","category");
   if (!may_read($db,$tableinfo,$id,$USER))
      return false;

   // get values 
   $r=$db->Execute("SELECT $tableinfo->fields FROM $tableinfo->realname WHERE id=$id");
   if ($r->EOF) {
      echo "<h3>Could not find this record in the database</h3>";
      return false;
   }
   $column=strtok($tableinfo->fields,",");
   while ($column) {
      ${$column}=$r->fields[$column];
      $column=strtok(",");
   }
   echo "&nbsp;<br>\n";
   echo "<table border=0 align='center'>\n";
   echo "<tr>\n";
   echo "<th>Article: </th>\n";
   echo "<td>$title<br>\n$author<br>\n";
   $text=get_cell($db,$journaltable,"type","id",$journal);
   echo "$text ($pubyear), <b>$volume</b>:$fpage-$lpage\n";
   echo "</td></tr>\n";
   
   if ($abstract) {
      echo "<tr>\n<th>Abstract</th>\n";
      echo "<td>$abstract</td>\n</tr>\n";
   }
   // Category
   if ($category) {
      $type2=get_cell($db,$categorytable,"type","id",$category);
      echo "<tr>\n<th>Category</th>\n";
      echo "<td>$type2</td>\n</tr>\n";
   }

   echo "<tr>";
   $query="SELECT firstname,lastname,email FROM users WHERE id=$ownerid";
   $r=$db->Execute($query);
   if ($r->fields["email"]) {
      echo "<th>Submitted by: </th><td><a href='mailto:".$r->fields["email"]."'>";
      echo $r->fields["firstname"]." ".$r->fields["lastname"]."</a> ";
   }
   else {
      echo "<th>Submitted by: </th><td>".$r->fields["firstname"]." ";
      echo $r->fields["lastname"] ." ";
   }
   $dateformat=get_cell($db,"dateformats","dateformat","id",$system_settings["dateformat"]);
   $date=date($dateformat,$date);
   echo "($date)</td>\n";
   echo "</tr>\n";

   if ($lastmodby && $lastmoddate) {
      echo "<tr>";
      $query="SELECT firstname,lastname,email FROM users WHERE id=$lastmodby";
      $r=$db->Execute($query);
      if ($r->fields["email"]) {
         echo "<th>Last modified by: </th><td><a href='mailto:".$r->fields["email"]."'>";
         echo $r->fields["firstname"]." ".$r->fields["lastname"]."</a>";
      }
      else {
         echo "<th>Last modified by: </th><td>".$r->fields["firstname"]." ";
         echo $r->fields["lastname"];
      }
      $dateformat=get_cell($db,"dateformats","dateformat","id",$system_settings["dateformat"]);
      $lastmoddate=date($dateformat,$lastmoddate);
      echo " ($lastmoddate)</td>\n";
      echo "</tr>\n";
   }

   echo "<tr>";
   $notes=nl2br(htmlentities($notes));
   echo "<th>Notes: </th><td>$notes</td>\n";
   echo "</tr>\n";

   $columnid=get_cell($db,$tableinfo->desname,"id","columnname","file");
   $files=get_files($db,$tableinfo->name,$id,$columnid,1);
   if ($files) {
      echo "<tr><th>Files:</th>\n<td>";
      for ($i=0;$i<sizeof($files);$i++) {
         echo $files[$i]["link"]." (".$files[$i]["type"]." file, ".$files[$i]["size"].")<br>\n";
      }
      echo "</tr>\n";
   }
   
   echo "<tr><th>Links:</th><td colspan=7><a href='$PHP_SELF?tablename=".$tableinfo->name."&showid=$id&";
   echo SID;
   echo "'>".$system_settings["baseURL"].getenv("SCRIPT_NAME")."?tablename=".$tableinfo->name."&showid=$id</a> (This page)<br>\n";

   echo "<a href='http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?";
   if ($system_settings["pdfget"])
      $addget="&".$system_settings["pdfget"];
   echo "cmd=Retrieve&db=PubMed&list_uids=$pmid&dopt=Abstract$addget'>This article at Pubmed</a><br>\n";
   echo "<a href='http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?";
   echo "cmd=Link&db=PubMed&dbFrom=PubMed&from_uid=$pmid$addget'>Related articles at Pubmed</a><br>\n";
   if ($supmat) {
      echo "<a href='{$supmat}'>Supplemental material</a><br>\n";
   }
   echo "</td></tr>\n";;
   show_reports($db,$tableinfo,$id);

?>   
<form method='post' id='pdfview' action='<?php echo "$PHP_SELF?tablename=".$tableinfo->name?>&<?=SID?>'> 
<?php
   if ($backbutton) {
      echo "<tr>";
      echo "<td colspan=7 align='center'><input type='submit' name='submit' value='Back'></td>\n";
      echo "</tr>\n";
   }
   else
      echo "<tr><td colspan=8 align='center'>&nbsp;<br><button onclick='self.close();window.opener.focus();' name='Close' value='close'>Close</button></td></tr>\n";
   echo "</table></form>\n";
}


/*

////
// !Extends the search query
// $query is the complete query that you can change and must return
// $fieldvalues is an array with the column names as key.
// if there is an $existing_clause (boolean) you should prepend your additions
// with ' AND' or ' OR', otherwise you should not
function plugin_search($query,$fieldvalues,$existing_clause) 
{
   return $query;
}


////
// !Extends function getvalues
// $allfields is a 2-D array containing the field names of the table in the first dimension
// and name,columnid,label,datatype,display_table,display_record,ass_t,ass_column,
// ass_local_key,required,modifiable,text,values in the 2nd D
function plugin_getvalues($db,&$allfields) 
{
}
*/

////
// !Extends display_add
function plugin_display_add($db,$tableid,$nowfield)
{
   if ($nowfield['name']=='pmid') {
      echo "<br>Find the Pubmed ID for this article at <a target='_BLANK' href='http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?db=PubMed'>PubMed</a>. Enter the Pubmed ID <b>OR</b> enter title, authors, journal, Year, Volume, First page and Last Page.";
   }
}


?>
