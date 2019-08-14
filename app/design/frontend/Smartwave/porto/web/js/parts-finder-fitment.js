require(['jquery'], function( $ ) {
  "use strict";


	function update_fitments() {
		if ( $(document).find("#amfinder_1").length !== 0 ) {
			var year     = $('#finder-1--1 option:selected').text().trim();
			var make     = $('#finder-1--2 span[data-amfinder-js="text"]').html().trim();
			var model    = $('#finder-1--3 option:selected').text().trim();
			var submodel = $('#finder-1--4 option:selected').text().trim();

			var emptyText = "Please Select ...";

			if (year != emptyText && make != emptyText && model != emptyText && submodel != emptyText) {
				console.log("Year: " + year);
				console.log("Make: " + make);
				console.log("Model: " + model);
				console.log("Submodel: " + submodel);


				$.getJSON('/fitment/index.php?year=' + year + '&make=' + make + '&model=' + model + '&submodel=' + submodel, function(data) {
					console.log(data);

					$.each(data, function(i, item) {
						$(".fitloc[data-sku='" + item.SKU + "']").html( item.LOC );
					});
				}).fail(function(data, textStatus, error){
        				console.error("fitment getJSON failed, status: " + textStatus + ", error: "+error)
				});
			}
		}
		else {
			console.log("amasty fitment finder plugin not present on page, disabling ATVPC update_fitments()");
		}
	}

	$('document').ready(function(){
		if ($("#finder-1--4 option:selected").val() !== 0) {
			setTimeout(function(){
				update_fitments();
			}, 1000);
		}
	});
});
