<?php

/**
 * This implements a very simple XML parser that translates a given xml file
 * into a hashed Array
 *
 * Code found on the php documentation site.
 * No attempt is made to interpret the data.  It will probably be difficult with large files.
 * In the current setting, the data will be read from a filehandle, it could be easily modified to read from other data sources.
 * @author Nico Stuurman
 */
class simple_parser_xml
{

    //#########Constructor#############
    function simple_parser_xml($filehandle) 
    {
        $this->filehandle=$filehandle;
    }

    function makeXMLTree ()
    {
         $output = array();
         while (!feof($this->filehandle)) {
            $data.=fread($this->filehandle,2048);
         }
         $parser = xml_parser_create('ISO-8859-1');

         xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
         xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	 $result=xml_parse_into_struct($parser, $data, $values, $tags);
         //echo "<br>Error string: " . xml_error_string(xml_get_error_code($parser))."<br>";
         xml_parser_free($parser);

         $hash_stack = array();
         $a=0; 
         foreach ($values as $key => $val) {
             switch ($val['type']) {
             case 'open':
                  array_push($hash_stack, $val['tag']);
             break;
             case 'close':
                  array_pop($hash_stack);
             break;
             case 'complete':
                  array_push($hash_stack, $val['tag']);
                  eval("\$this->content[\$a]['" . implode($hash_stack, "']['") . "'] = \"{$val['value']}\";\$a++;");
                  //eval("\$this->content['" . implode($hash_stack, "']['") . "'] = \"{$val['value']}\";");
                  array_pop($hash_stack);
             break;
             }
         }
         return true;
    }

}
?>
