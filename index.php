<?php

	$fitments = array_map('str_getcsv', file('fitments.csv'));

	if (isset($_GET['dump'])) {
		echo "<pre>";
		print_r($fitments);
		die();
	}
	else if (isset($_GET['year']) === FALSE) {
		die();
	}
	else {
		// Sanity checking
		if (is_numeric($_GET['year']) === FALSE || $_GET['year'] < 1970 || $_GET['year'] > date("Y") + 1) {
			die("Invalid Year!");
		}

		$_GET = array_map('strtolower', $_GET);
	}

	$relevantFitments = array();
	
	foreach ($fitments as $fitment) {
		if ($_GET['year']     == $fitment[0] && 
			$_GET['make']     == strtolower($fitment[1]) && 
			$_GET['model']    == strtolower($fitment[2]) && 
			$_GET['submodel'] == strtolower($fitment[3]) 
		   ) {

			array_push($relevantFitments, array('SKU' => $fitment[4], 'LOC' => $fitment[5]));
		}
	}

	echo json_encode($relevantFitments);
?>
