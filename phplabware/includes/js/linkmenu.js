function linkmenu (selectmenu) {
	selecteditem=selectmenu.selectedIndex;
	newurl=selectmenu.options[selecteditem].value;
	if (newurl.indexOf("target ")==0) {
		newurl=newurl.slice(7);
		var newWindow=open(newurl,"otherWindow","scrollbars=1,resizable=1,statusbarbar=1,navigator=1,defaultStatus=1,width=800,height=400,top=1");
	}
	else {
		if (newurl.length!=0) {
			location.href=newurl;
		}
	}
}
