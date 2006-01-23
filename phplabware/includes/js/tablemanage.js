// dynamically alters selection list.  Found somewhere on the web, altered
function fillSelectFromArray(selectCtrl, itemArray) {
   var i, j;
   var prompt;

   // remember what was selected
   //selectedID = selectCtrl.selectedIndex;
   selectedID = 0;
   //   selectedText = selectCtrl.options[selectedID];
   selectedText = "";
   // empty existing items
   for (i = selectCtrl.options.length; i >= 0; i--) {
	selectCtrl.options[i] = null;
   }

   j = 0;
  
   if (itemArray != null) {
      // add new items
      for (i = 0; i < itemArray.length; i++) {
	 selectCtrl.options[j] = new Option(itemArray[i][0]);
	 if (itemArray[i][1] != null) {
	    selectCtrl.options[j].value = itemArray[i][1];
	    if (selectCtrl.options[j].text == selectedText)
               selectCtrl.options[j].selected = true;
            selected = true;
	 }
	 j++;
      }
      // if nothing selected, select first item
      if (selected != true)
         selectCtrl.options[0].selected = true;
   }
}

function displayResponse() {
   // Server has responded when we get readyState=4
   if (http.readyState == 4) {
      //alert (http.status);
      //alert (http.responseText);
   }
}



// tell the server what changed without having to reload the whole page
function tellServer (url, id) {
   // read out the values in the row in which the change occurred
   var type = document.getElementById("type_type_" + id).value;
   var typeshort = document.getElementById("type_typeshort_" + id).value;
   var typesortkey = document.getElementById("type_sortkey_" + id).value;

   var request = url +  "&typeid=" + id ;

   // send a post form to the server, ascynchronous
   http.open('POST', request, true);
   // This header must be set for a POST request
   http.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
   // function displayResponse actually does not do anything
   http.onreadystatechange = displayResponse; 
   // prepare POST variables
   var postrequest = "jsrequest=true&type_type_" + id + "=" + type + "&type_typeshort_" + id + "=" + typeshort + "&type_sortkey_" + id + "=" + typesortkey + "&mdtype_" + id + "=Modify";
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

