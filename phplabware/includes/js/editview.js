// Support function for editview.
// Opens a new window under the existing one, fill in a form with changes, sends the form to the server, and closes the window

var newWindow;


function submit_changes ($tableid,$recordid,$field,$datatype,$newvalue) {
   // Open new window under existing one, make sure it does not yes exist
   if (!newWindow || newWindow.closed) {
      newWindow = window.open("","PHPLabware","status,height=200,width=300");
      newWindow.blur();
      window.focus();
      // supposedly needed for IE, current Mozilla does not know it
      //setTimeout ("writeToWindow()",50);
   }
   
   // for debugging, we give the new window focus
   //newWindow.focus();

   // Prepare the form data that we will write to the new form
   var newForm = "<html>><body>\n";
   newForm += "<form name='editMode' method='post' enctype='multipart/form-data' action='actionMode.php'>\n";
   newForm += "<input type='hidden' name='tableid' value='" + $tableid + "'>\n";
   newForm += "<input type='hidden' name='recordid' value='" + $recordid + "'>\n";
   newForm += "<input type='hidden' name='field' value='" + $field + "'>\n";
   newForm += "<input type='hidden' name='datatype' value='" + $datatype + "'>\n";
   newForm += "<input type='hidden' name='newvalue' value='" + $newvalue + "'>\n";

   //newForm += "<input type='submit' name='Submit' value='Submit'>\n";
   newForm += "</form>\n</body>\n</html>\n";

   newWindow.document.write(newForm);

   newWindow.document.editMode.submit();

   //document.onblur=newWindow.close();

}

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

