function linkmenu (selectmenu) {
	selecteditem=selectmenu.selectedIndex;
	newurl=selectmenu.options[selecteditem].value;
	if (newurl.length!=0) {
		location.href=newurl;
	}
}
