require(['jquery'], function( $ ) {
  "use strict";


	var yearElem = document.getElementsByName('finder[1]')[0];
	var modelElem = document.getElementsByName('finder[3]')[0];
	var submodelElem = document.getElementsByName('finder[4]')[0];
	
	if ( submodelElem.selectedIndex !== -1) {
		var year = yearElem.options[ yearElem.selectedIndex ].text; 
		var make = document.querySelector('[data-amfinder-js="text"]').innerHTML;
		var model = modelElem.options[ modelElem.selectedIndex ].text; 
		var submodel = submodelElem.options[ submodelElem.selectedIndex ].text; 

		$.getJSON('/fitments/index.php?' +
					'year=' + year + 
					'&make=' + make + 
					'&model=' + model +
					'&submodel=' + submodel, function(data) {

			$.each(result, function(i, item) {
				$(".fitment[data-sku='" + item.SKU + "']").html( item.FIT );
			});
		});
	}

});