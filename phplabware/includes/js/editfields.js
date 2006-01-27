// Javascript code to send requests to change fields in the tablemanage page to the server
function displayResponse() {
   // Server has responded when we get readyState=4
   if (http.readyState == 4) {
      //alert (http.status);
      if (http.responseText != 'SUCCESS!') {
         alert ("Failed to send your changes to the server.  This page will reload to bring you back in sync with the server");
         window.location.reload(true);
      }
   }
}


// tell the server what changed without having to reload the whole page
function tellServer (url, id, variable, value) {
   // read out the values in the row in which the change occurred
   var tablename = document.getElementsByName("table_name")[0].value;

   var request = url  ;

   // send a post form to the server, ascynchronous
   http.open('POST', request, true);
   // This header must be set for a POST request
   http.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
   // function displayResponse actually does not do anything
   http.onreadystatechange = displayResponse; 
   // prepare POST variables
   var postrequest = "jsrequest=true&modcolumnjs_" + id + "=true&variable=" + variable + "&value=" + value + "&table_name=" + tablename;
   // and now really send it
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
