	var yearElem = document.getElementsByName('finder[1]')[0];
	var year = yearElem.options[ yearElem.selectedIndex ].text; 

	var make = document.querySelector('[data-amfinder-js="text"]').innerHTML;

	var modelElem = document.getElementsByName('finder[3]')[0];
	var model = modelElem.options[ modelElem.selectedIndex ].text; 
	
	var submodelElem = document.getElementsByName('finder[4]')[0];
	var submodel = submodelElem.options[ submodelElem.selectedIndex ].text; 

	alert(year + " : " + make + " : " + model + " : " + submodel);
