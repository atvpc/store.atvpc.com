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

$dsn = "mysql:host=" . $conf['host'] . ";dbname=" . $conf['dbname'];
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
SELECT value_id FROM amasty_finder_value WHERE name=:submodel AND parent_id IN (
  SELECT value_id AS parent_id FROM amasty_finder_value WHERE name=:model AND parent_id IN (
    SELECT value_id AS parent_id FROM amasty_finder_value WHERE name=:make AND parent_id IN (
      SELECT value_id AS parent_id FROM amasty_finder_value WHERE name=:year
    )
  )
)
ENDSQL;

	$stmt = $pdo->prepare($stmt);
	$stmt->execute(['year' => $year, 'make' => $make, 'model' => $model, 'submodel' => $submodel]);
	return $stmt->fetchAll(PDO::FETCH_COLUMN);
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

function validateValues($csvArr) {
	global $pdo;
	$years = $pdo->query('SELECT DISTINCT name FROM amasty_finder_value WHERE dropdown_id = 1 AND name != "Year" AND name != ""')->fetchAll(PDO::FETCH_COLUMN);
	$makes = $pdo->query('SELECT DISTINCT name FROM amasty_finder_value WHERE dropdown_id = 2 AND name != "Make" AND name != ""')->fetchAll(PDO::FETCH_COLUMN);
	$models = $pdo->query('SELECT DISTINCT name FROM amasty_finder_value WHERE dropdown_id = 3 AND name != "Model" AND name != ""')->fetchAll(PDO::FETCH_COLUMN);
	$submodels = $pdo->query('SELECT DISTINCT name FROM amasty_finder_value WHERE dropdown_id = 4 AND name != "--" AND name != ""')->fetchAll(PDO::FETCH_COLUMN);

	$makes = array_map('strtolower', $makes);
	$models = array_map('strtolower', $models);
	$submodels = array_map('strtolower', $submodels);

	$errors = array();
	foreach ($csvArr as $i=>$vehicle) {
		$i++; // to display correct line number to end user

		if (sizeof($vehicle) != 6) {
			$errors[] = "<em>Line $i</em> - Wrong number of columns in CSV (given: ". sizeof($vehicle) .", expected: 6)";
		}
		else {
			list($year, $make, $model, $submodel, $null, $null) = $vehicle;

			if (in_array($year, $years) === FALSE) {
				$errors[] = "<em>Line $i Year Column</em> - <strong>&quot;$year&quot;</strong> isn't in Amasty Parts Finder";
			}

			if (in_array(strtolower($make), $makes) === FALSE) {
				$errors[] = "<em>Line $i Make Column</em> - <strong>&quot;$make&quot;</strong> isn't in Amasty Parts Finder";
			}

			if (in_array(strtolower($model), $models) === FALSE) {
				$errors[] = "<em>Line $i Model Column</em> - <strong>&quot;$model&quot;</strong> isn't in Amasty Parts Finder";
			}

			if (in_array(strtolower($submodel), $submodels) === FALSE) {
				$errors[] = "<em>Line $i Submodel Column</em> - <strong>&quot;$submodel&quot;</strong> isn't in Amasty Parts Finder";
			}
		}
	}

	if (sizeof($errors) > 0) {
		echo "<h1>Error</h1>";
		echo "<ul>";
		foreach ($errors as $error) {
			echo "<li>$error";
		}
		echo "</ul>";
		die();
	}
}

function parseCsv($file) {
	// Parse CSV into Array
	$fitments = array_map('str_getcsv', file($file));

	// Make sure there's 6 columns
	if (sizeof($fitments[0]) != 6) {
		echo "<h1>Error</h1>";
		echo "Wrong number of columns in CSV. In order, the columns should be:";
		echo "<em>Year, Make, Model, Submodel, SKU, Fitment Location</em>";
		die();
	}

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

	$oldcsv = parseCsv("fitments.csv");
	$newcsv = parseCsv($_FILES['csvFile']['tmp_name']);
	validateValues($newcsv);

	$messages = array();

	foreach ($newcsv as $i=>$newfit) {
		list($nyear, $nmake, $nmodel, $nsubmodel, $nsku, $nloc) = $newfit;

		foreach ($oldcsv as $o=>$oldfit) {
			list($oyear, $omake, $omodel, $osubmodel, $osku, $oloc) = $oldfit;

			if ($nyear == $oyear && $nmake == $omake && $nmodel == $omodel && $nsubmodel == $osubmodel && $nsku == $osku) {
				if ($nloc == $oloc) {
					unset($newcsv[$i]);
					$messages[] = "<em>Line " . ($i + 1) . "</em> - Duplicate entry removed, already in CSV";
					break;
				}
				else {
					unset($oldcsv[$o]);
					$messages[] = "<em>Line " . ($i + 1) . "</em> - Overwrote old fitment location <strong>$oloc</strong> with <strong>$nloc</strong>";
					break;
				}
			}
		}
	}

	$writecsv = array_merge($oldcsv, $newcsv);
	$fh = fopen('fitments.csv', 'wa+');
	foreach ($writecsv as $line) {
		fputcsv($fh, $line);
	}
	fclose($fh);

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
