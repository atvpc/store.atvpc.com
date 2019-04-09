<?php
/* 
 * DEBUG
 ***********************************************************/ 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* 
 * DATABASE CONNECTION
 ***********************************************************/ 

// Grab Magento's DB config
$conf = include '../app/etc/env.php';
$conf = $conf['db']['connection']['default']; // remove the extra unneeded conf settings

$dsn = "mysql:host=" . $conf['host'] . ";dbname=" . $conf['dbname'] . ';charset=utf8';
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
     $pdo = new PDO($dsn, $conf['username'], $conf['password'], $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// add the local upload password; this is not tracked 
// in this repo, sample format:
//   <?php 
//   $conf['upload_password'] = 'your password here';
include 'config.php'; 

/* 
 * FUNCTIONS
 ***********************************************************/ 

function getVehiclePID($year, $make, $model, $submodel) {
	/* returns the unique parent ID for a specific vehicle from 
	 * Amasty's part finder plugin
	 * 
	 * The SQL DB table has relationships stored in a single table,
	 * so the "name" column might be either a Year, Make, Model, 
	 * or Submodel. This also causes a lot of duplicates: duplicate
	 * makes for every year in the DB
	 * 
	 * There's a single ID for each specific {Year, Make, Model, 
	 * Submodel} that's used in other tables. It's stored as the 
	 * Submodel's ID, but getting to that point is convoluted:
	 *   - Get the ID of the Year
	 *   - Match Makes with name = ? and PID = Year ID
	 *   - Match Models with name = ? and PID = Make ID
	 *   - Get ID from Submodel with name = ? and PID = Model ID
	 */ 
	global $pdo;

	$stmt = <<< ENDSQL
SELECT value_id FROM amasty_finder_value WHERE UPPER(name)=UPPER(:submodel) AND parent_id IN (
  SELECT value_id AS parent_id FROM amasty_finder_value WHERE UPPER(name)=UPPER(:model) AND parent_id IN (
    SELECT value_id AS parent_id FROM amasty_finder_value WHERE UPPER(name)=UPPER(:make) AND parent_id IN (
      SELECT value_id AS parent_id FROM amasty_finder_value WHERE UPPER(name)=UPPER(:year)
    )
  )
)
ENDSQL;

	$stmt = $pdo->prepare($stmt);
	$stmt->execute(['year' => $year, 'make' => $make, 'model' => $model, 'submodel' => $submodel]);
	return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function printErrors($errors) {
	if (sizeof($errors) > 0) {
		echo '<h1>Error</h1>' .
		     '<table style="width: 100%; border-collapse: collapse">' .
		     '<tr style="width: 100%; background-color: #ddd; border-bottom: 3px double black">' .
		     '	<th>CSV Row / Line</th><th>Message</th>' .
		     '</tr>';

		foreach ($errors as $error) {
			echo '<tr style="border-bottom: 1px solid black">' .
			     '	<td align="center" style="border-right: 1px dotted black"><h2><em>'. $error['line'] .'</em></h2></td>' .
			     '	<td style="padding: 1em">'. $error['msg'];

			if (isset($error['hint'])) {
				echo '<br><br>' .
					 '<strong>HINT:</strong><br>' . 
					 $error['hint'];
			}

			if (isset($error['debug'])) {
				echo '<br><br>'.
					 '<h3>CSV DEBUG DUMP:</h3><br>' .
					 '<em>This is how I interpreted the CSV file. Make sure it matches what you expected:</em><br>' .
					 '<table cellspacing="10"><tr>';

				foreach ($error['debug'] as $key=>$element) {
					if (is_numeric($key)) {
						$key = 'Column ' . $key;
					}
					echo '<th>'. $key .'</th>'; 
				}
				echo '</tr><tr>';

				foreach ($error['debug'] as $key=>$element) echo '<td>'. $element .'</td>';

				echo '</tr></table>';
			}
			echo '</tr>';
		}
		echo '</table>';
		die();
	}
}

function validateSingleFitment($name) {
	/* Sees if a specific element of vehicle fitment exists 
	 * In otherwords, is the given Year, Make, Model, or 
	 * Submodel in the DB?
	 * 
	 * The SQL DB table has relationships stored in a single table,
	 * so the "name" column might be either a Year, Make, Model, 
	 * or Submodel
	 */ 
	global $pdo;

	$stmt = 'SELECT * FROM amasty_finder_value WHERE UPPER(name)=UPPER(:name)';
	$stmt = $pdo->prepare($stmt);
	$stmt->execute(['name' => $name]);

	if (sizeOf( $stmt->fetchAll(PDO::FETCH_COLUMN) ) > 0) return true;
	else return false;
}

function validateVehicle($year, $make, $model, $submodel) {
	/* helper function to make sure there's a match for 
	 * a specific {Year, Make, Model, Submodel} combo.
	 * 
	 * There should always be exactly one match as 
	 * getVehiclePID() should return a unique PID. 
	 * 
	 * getVehiclePID()'s code normally would be in this
	 * function, but validateSKU() needs the same logic,
	 * so this helps reduce lines of code
	 */ 
	$matches = getVehiclePID($year, $make, $model, $submodel);
	if (sizeOf($matches) == 1) return true;
	else return false;
}

function validateSKU($year, $make, $model, $submodel, $sku) {
/* validates a SKU goes with a specific fitment combo. 
 * 
 * SKUs are stored seperately from fitments. The fitments 
 * table has a unique value_id that's used to reference an 
 * exact {Year, Make, Model, Submodel} combo. 
 */ 

	global $pdo;
	$vehicle_pid = getVehiclePID($year, $make, $model, $submodel);
	$stmt = 'SELECT * FROM amasty_finder_map WHERE value_id=:vehicle_pid AND sku=:sku';

	$stmt = $pdo->prepare($stmt);
	$stmt->execute(['vehicle_pid' => $vehicle_pid[0], 'sku' => $sku]);
	$matches = $stmt->fetchAll(PDO::FETCH_COLUMN);

	if (sizeOf($matches) > 0) return true;
	else return false;
}

function validateCSV($csvArr) {
	global $pdo;

	$errors = array();
	foreach ($csvArr as $i=>$vehicle) {
		$i++; // to display correct line number to end user

		if (sizeof($vehicle) != 6) {
			$errors[] = array(
							'line'  => $i,
							'msg'   => 'Wrong number of columns in CSV ('. sizeof($vehicle) .' vs. 6 cols)',
							'hint'  => 'look for extra commas',
							'debug' => $vehicle
						);
		}
		else {
			$vehicle = array_map('trim', $vehicle);
			list($year, $make, $model, $submodel, $sku, $null) = $vehicle;

			if (validateVehicle($year, $make, $model, $submodel) === false) {
				$fitmentErr = array();
				if (validateSingleFitment($year) === false)     $fitmentErr[] = 'Year';
				if (validateSingleFitment($make) === false)     $fitmentErr[] = 'Make';
				if (validateSingleFitment($model) === false)    $fitmentErr[] = 'Model';
				if (validateSingleFitment($submodel) === false) $fitmentErr[] = 'Submodel';

				if (sizeof($fitmentErr) > 0){
					$errors[] = array(
								'line' => $i,
								'msg'  => 'Amasty does not have the given ' . implode(', ', $fitmentErr) . ' in the parts finder yet',
								'hint' => 'Check for typos or add the missing fitment to Amasty',
								'debug' => array('Year' => $year, 'Make' => $make, 'Model' => $model, 'Submodel' => $submodel)
								);
				}
				else {
					$errors[] = array(
								'line'  => $i,
								'msg'   => 'Amasty does not have this specific fitment combination. ' .
								           'Individually, the year, make, model and submodel are correct, but not combined as a vehicle',
								'hint'  => 'Make sure this combination has been added to the Amasty parts finder',
								'debug' => array('Year' => $year, 'Make' => $make, 'Model' => $model, 'Submodel' => $submodel)
			 					);
				}
			}
			else if (validateSKU($year, $make, $model, $submodel, $sku) === false) {
				$errors[] = array(
								'line'  => $i,
								'msg'   => 'Amasty does not have this SKU for this specific fitment {Year, Make, Model, Submodel}',
								'hint'  => 'The fitment is correct and exists. The SKU might not be in Amasty or might be misspelled in the CSV',
								'debug' => array('Year' => $year, 'Make' => $make, 'Model' => $model, 'Submodel' => $submodel, 'SKU' => $sku)
								);
			}
		}
	}

	printErrors($errors);
}

function parseCsv($file) {
	// Parse CSV into Array
	$fitments = array_map('str_getcsv', file($file));

	// Remove CSV header if present
	if ($fitments[0][0] == "Year") {
		unset($fitments[0]);
	}

	return $fitments;
}



if ( isset($_POST['submit']) && $_POST['submit'] == 'Upload') {
	if ( hash('sha256', $_POST['password']) != hash('sha256', $conf['upload_password']) ) {
		echo "<h1>Error</h1>";
		echo "Invalid password";
		die();
	}

	if ( isset($_FILES['csvFile']['tmp_name']) ) {
		$fh = finfo_open(FILEINFO_MIME);
		$mimetype = finfo_file($fh, $_FILES['csvFile']['tmp_name']);
	
		if ($mimetype != 'text/plain; charset=us-ascii') {
			echo "<h1>Error</h1>";
			echo "File is not a CSV or text-file";
			die();
		}
	}
	else {
		echo "<h1>Error</h1>";
		echo "File upload failed";
		die();
	}

	$newcsv = parseCsv($_FILES['csvFile']['tmp_name']);
	validateCSV($newcsv);

	$messages = array();

	foreach ($newcsv as $i=>$newfit) {
		list($year, $make, $model, $submodel, $sku, $loc) = $newfit;

		$stmt = 'SELECT id,location FROM fitment_locations WHERE year=:year AND UPPER(make)=UPPER(:make) AND UPPER(model)=UPPER(:model) AND UPPER(submodel)=UPPER(:submodel) AND UPPER(sku)=UPPER(:sku)';
		$stmt = $pdo->prepare($stmt);
		$stmt->execute(array(
						'year' => $year, 
						'make' => $make, 
						'model' => $model, 
						'submodel' => $submodel, 
						'sku' => $sku
						));
		$matches = $stmt->fetchAll(PDO::FETCH_COLUMN);

		if (sizeof($matches) > 0) {
			if (strtoupper($matches['location']) == strtoupper($loc) ) {
				$messages[] = "<em>Line " . ($i + 1) . "</em> - Duplicate entry removed, already in Database";
			}
			else {
				$messages[] = "<em>Line " . ($i + 1) . "</em> - Overwrote old fitment location <strong>" . 
							  $matches['location'] ."</strong> with <strong>". $loc ."</strong>";

				$stmt = 'UPDATE fitment_locations SET location=:location WHERE id=:id';
				$stmt = $pdo->prepare($stmt);
				$stmt->execute([ 'location' => $loc, 'id' => $matches['id'] ]);
			}
		}
		else {
			$stmt = "INSERT INTO fitment_locations (year, make, model, submodel, sku, location) VALUES (:year, :make, :model, :submodel, :sku, :location)";
			$stmt= $pdo->prepare($stmt);
			$stmt->execute(['year' => $year, 'make' => $make, 'model' => $model, 'submodel' => $submodel, 'sku' => $sku, 'location' => $loc]);
		}
	}

	echo "<h1>Successfully Wrote to CSV</h1>";

	if (sizeof($messages) > 0) {
		echo "Informational messages:";
		echo "<ul>";
			foreach ($messages as $message) {
				echo "<li>$message";
			}
		echo "</ul>";
	}
}
else {
?>

<!DOCTYPE html>
<html>
<head>
  <title>CSV Fitment Location Upload</title>
</head>
<body>
<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
    <div>
		<label for="csvFile">CSV File:</label><br>
		<input type="file" name="csvFile">
	</div>

	<div>
		<label for="password">Upload Password:</label><br>
		<input type="password" name="password"> 
	</div>

	<div>
		<input type="submit" name="submit" value="Upload">
	</div>
</form>
</body>
</html>

<?php } ?>
