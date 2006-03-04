// Support function for editview.

// Javascript code to send requests to change fields in edit mode to the server
function displayResponse() {
   // Server has responded when we get readyState=4
   if (http.readyState == 4) {
      //alert (http.status);
      if (http.responseText != 'SUCCESS!') {
         //alert(http.responseText);
         alert ("Failed to send your changes to the server.  This page will reload to bring you back in sync with the server");
         window.location.reload(true);
      }
   }
}

// tell the server what changed without having to reload the whole page
function tellServer (url, tableid, recordid, field, datatype, newvalue) 
{
   var request = url;

   // send a post form to the server, ascynchronous
   http.open('POST', request, true);
   // This header must be set for a POST request
   http.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
   // function displayResponse will reload the page upon failure to keep content on client and server in sync
   http.onreadystatechange = displayResponse; 
   // prepare POST variables
   // for mpulldowns, we have to figure out which values are selected
   // for all others the value can be passed directly                        
   if (datatype=="mpulldown") {                                             
      // in this case $newvalue contains a pointer to the select object      
      var valueString="";                                                    
      for (var i=0; i<newvalue.length; i++ ) {                              
         if (newvalue.options[i].selected) {                                
            if (newvalue.options[i].value != "undefined" ) {                
               valueString += newvalue.options[i].value + ",";             
             }                                                                
         }                                                                   
      }                                                                      
      newvalue = valueString;
   }
   // construct the request
   var postrequest = "tableid=" + tableid + "&recordid=" + recordid + " &field=" + field + "&datatype=" + datatype + "&newvalue=" + newvalue;
   // and send it
   http.send(postrequest);
}


function getHTTPObject() 
{ 
   var xmlhttp; 
   /*@cc_on 
   @if (@_jscript_version >= 5) 
      try { 
         xmlhttp = new ActiveXObject("Msxml2.XMLHTTP"); 
      } catch (e) { 
         try { 
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP"); 
         } catch (E) { 
            xmlhttp = false; 
         } 
      } @else 
         xmlhttp = false; 
      @end 
   @*/  
   if (!xmlhttp && typeof XMLHttpRequest != 'undefined') { 
      try { 
         xmlhttp = new XMLHttpRequest(); 
      } catch (e) { 
         xmlhttp = false; 
      } 
   } 
   return xmlhttp; 
} 

var http = getHTTPObject(); // Create the HTTP Object immediately rather than waiting to throw an error only once it is needed

// simple functions to check input

function isInstring (inputValue,goodValues,alertText) {
   var allValid=true;
   for (i=0; i<inputValue.length; i++) {
      ch=inputValue.charAt(i)
      for (j=0; j<=goodValues.length;j++) {
         if (ch==goodValues.charAt(j)) {
            break;
         }
         if (j==goodValues.length) {
            allValid=false;
            break;
         }
      }
   }
   if (!allValid) {
       alert(alertText);
       return false;
   } else {
      return true;
   }
}

function isAnInt (inputValue) {
   var goodValues="0123456789";
   var alertText="Please use a valid number (fractions not allowed) in this field";
   return isInstring(inputValue,goodValues,alertText);
}

function isAFloat (inputValue) {
   var goodValues="0123456789" + "." + ",";
   var alertText="Please use a valid number (fractions allowed) in this field";
   return isInstring(inputValue,goodValues,alertText);
}

