<?php
/*
eutils_elink_class v0.00.01 (first semi-official release)

TODO - esummary/esearch/efetch need to be better abstracted,
there is a fair amount of "copied"/duplicated code between them.

Part of the BioPHP project(s) (http://bioinformatics.org/biophp).

(c) 2003 Sean Clark, consider this licensed to you under
the GPL v2.0 - put simply, you can do anything you want to
this code, but if you redistribute modified versions you have
to offer the source (un-obfuscated) to whomever you distribute
it and you can't restrict THEIR freedom to do the same...

Sends Elink queries to NCBI's Elink interface and returns
record ID numbers and "translation" information (where ESearch
converts the terms passed into "standard" search terms).

This version doesn't yet support "web environment"/history
directly, though those determined to use it SHOULD be able
to set the appropriate information manually, e.g. by calling
$this->setManualSearchParam("usehistory","y");

Thus far only TESTED on pubmed searches (the default), but SHOULD
work for any of the available databases.


*/
require_once('./plugins/elink/postbox_class.php');
require_once('./plugins/elink/simple_parser_xml.inc.php');

class eutils_link 
{

    var $searchparams=Array(
        'dbfrom'=>'pubmed',
        'term'=>'',
        'retmax'=>'50',
        'tool'=>'BioPHP'
    );
    //implemented parameters - yes, extremely limited at the moment...
    var $host='eutils.ncbi.nlm.nih.gov';
    var $URL='/entrez/eutils/elink.fcgi';
    var $ignore_other_tags=false; //whether to catch or ignore not-specifically-handled tags
    var $postinterface;
    var $parser; //will be instantiated at search initiation time.

    var $resultIDs=Array(); //array of ID's returned by the query
    var $termset=Array();
    var $translations=Array(); //key=original terms value=text converted to
    var $search_matches=0;

//#############Class Constructor######################
    // We'll take a list (array) of ids and feed these to NCBI
    function eutils_link($id='') 
    {
        if($id!='') {
            if (!is_array($id)) {
                $ids[0]=$id;
            }
            else {
                 $ids=$id;
            }
            $this->setIDs($ids);
        }
        $this->postinterface=new postbox();
    }


//##############Get/Set functions######################
    function getIDs() 
   {
        return $this->resultIDs;
    }

    function getSearchMatches() 
    {
        return $this->search_matches;
    }

    function getTerms() 
    {
       //returns just the list of terms used in the search
        return (array_keys($this->termset));
    }

    function getTermInfo($term) 
    {
        return $this->termset[$term];
    }

    function getTranslations() 
    {
        //fetch a list of the translated terms
        return array_keys($this->translations);
    }

    function getTranslation($term) 
    {
        return $this->translations[$term];
    }

    function ignoreOtherTags($ignore=true) 
    {
        //pass false to this function if you want to
        //manually deal with "extra" tags "by hand", e.g.
        //webenv/usehistory and such.
        $this->ignore_other_tags=$ignore;
    }

    function setDB($db) 
    {
        //note - doesn't yet check for valid DB!
        $this->searchparams['db']=$db;
    }

    function setDBfrom($dbfrom) 
    {
        //note - doesn't yet check for valid DB!
        $this->searchparams['dbfrom']=$dbfrom;
    }
    function setMaxAge($days) 
    {
        //"reldate" - number of days from current
        $this->searchparams['reldate']=$days;
    }

    function setMaxResults($number) 
    {
        //define maximum number of matches to retrieve
        $this->searchparams['retmax']=$number;
    }

    function setManualSearchParam($term,$value) 
    {
        //allow setting of any search parameter not yet
        //directly handled, e.g. history, webenv, etc
    	$this->searchparams[$term]=$value;
    }

    function setSearchTerms($terms) 
    {
        $this->searchparams['term']=$terms;
    }

    function setIDs($ids) 
    {
        foreach ($ids as $id) {
            if (is_numeric($id)) {
                $found_one=true;
                $this->searchparams['id']=$id.',';
            }
        }
        if ($found_one) {
            $this->searchparams['id']=substr($this->searchparams['id'],0,-1);
        }
    }

//##############"Utility" Functions####################
    function doSearch() 
    {
        $this->postinterface->sethost($this->host);
        $this->postinterface->seturl($this->URL);
        foreach(array_keys($this->searchparams) as $term) {
            $this->postinterface->setqueryterm($term,$this->searchparams[$term]);
        }
        if($filehandle=$this->postinterface->post_query($this->searchparams,$this->host,$this->URL)) {
            $this->parser=new simple_parser_xml($filehandle);
            $this->parser->makeXMLTree();

            return true;
        }
        else {
            return false;
        }
    }
}

?>
