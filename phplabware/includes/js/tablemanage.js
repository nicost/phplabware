// dynamically alters selection list.  Found somewhere on the web, altered
function fillSelectFromArray(selectCtrl, itemArray) {
   var i, j;
   var prompt;

   // remember what was selected
   selectedID = selectCtrl.selectedIndex;
   selectedText = selectCtrl.options[selectedID];
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
