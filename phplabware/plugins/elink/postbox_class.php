<?php
class postbox
{
    var $host=""; //string containing host for query
    var $url=""; //actually just the URL relative to the host
    var $port=80;
    var $queryterms=Array(); //array of query=>value
    var $filepointer; //handle from which results are read - minus headers.
    var $headers=Array("User-Agent"=>"PHP Postbox","Connection"=>"Keep-Alive",
    "Content-type"=>"application/x-www-form-urlencoded");

    function postbox($queryterms=Array()) {
    //can be passed an array of terms=>values initially
        if(count($queryterms) > 0) {
            $this->queryterms=$queryterms;
        }
    }

//###################get/set functions#############################

    function clearquery() {
        unset($this->queryterms);
        $this->queryterms=Array();
    }

    function setheader($header,$value) {
        $this->headers[$header]=$value;
    }

    function sethost($hostname) {
        $this->host=$hostname;
    }

    function setport($port) {
        $this->port=$port;
    }

    function seturl($url) {
    //URL RELATIVE to root of the host, not the whole thing
        $this->url=$url;
    }

    function setqueryterm($term,$value) {
        $this->queryterms[$term]=$value;
    }

    function setqueryterms($termarray)
    {//takes a term=>value array of terms
    //completely resets the term array
        $this->queryterms=$termarray;
    }

//###################utility functions#############################
    function encodequery($queryarray) {
    //takes and array of key=>value pairs and
    //returns a string formatted for GET/POST queries
        $temparray=Array();

        foreach(array_keys($queryarray) as $key) {
            $temparray[]=urlencode($key)."=".urlencode($queryarray[$key]);
        }
        return(implode("&",$temparray));
    }

    function post_query() 
    {
    //take key=>value pairs, generate POST query,
    //send to $URL on $host at $port, return filehandle for response.
        $query=$this->encodequery($this->queryterms);
        $querysize=strlen($query);

        $sendtext="POST ".$this->url." HTTP/1.0\n";
        foreach(array_keys($this->headers) as $header) {
            $sendtext.=$header.": ".$this->headers[$header]."\n";
        }
        $sendtext.="Content-length: $querysize\n";
        $sendtext.="\n";
        $sendtext.="$query";


        if($this->filehandle=($this->netSend($this->host,$this->port,$sendtext))) {
            return($this->filehandle);
        }
        else {
            return false;
        }
    }

    function netSend($host,$port,$sendtext) 
    {
    //send $sendtext via socket to $host at $port
        if($socket = fsockopen($host,$port,$errnum,$errstr,30)) { //30 second timeout
            $response="";
            fputs($socket,$sendtext);
            while(strlen(trim($data=(fgets($socket,1024))))>1) {
            //don't do nothin' - just read past the headers
            //    $response.=fgets($socket,1024);
            }
            //fclose($socket);

            //return $response;
            return $socket;
        }
        else {
            return false;
        }
    }
}

?>
