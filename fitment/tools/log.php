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
$conf = include '/srv/htdocs/store.atvpc.com/app/etc/env.php';
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

/* 
 * FUNCTIONS
 ***********************************************************/ 

function validate_date($date) {
	$tmpDate = array_map('intval', explode('-', $date));

	if (count($tmpDate) != 3) {
		return false;
	}
	else {
		return checkdate($tmpDate[1], $tmpDate[2], $tmpDate[0]);
	}
}


if ( isset($_POST['submit']) && $_POST['submit'] == 'Show Log') {
	if (isset($_POST['start_date']) && isset($_POST['end_date'])) {
		if (validate_date($_POST['start_date']) === false) {
			echo "<h1>Error</h1>";
			echo $_POST['start_date'] . " is not a valid start date";
			die();
		}

		if (validate_date($_POST['end_date']) === false) {
			echo "<h1>Error</h1>";
			echo $_POST['end_date'] . " is not a valid end date";
			die();
		}

		$stmt = 'SELECT log_user,log_date,year,make,model,submodel,sku,location FROM fitment_locations WHERE log_date BETWEEN :start_date AND :end_date ORDER BY log_date DESC';
		$stmt = $pdo->prepare($stmt);
		$stmt->execute(array(
						':start_date' => $_POST['start_date'], 
						':end_date' => $_POST['end_date']
						));
		$matches = $stmt->fetchAll();

		if (sizeof($matches) > 0) {
			echo '<table style="width: 100%; border-collapse: collapse"><tr style="width: 100%; background-color: #ddd; border-bottom: 3px double black">';
			echo '<th>User</th><th>Date</th><th>Fitment Location</th><th>SKU</th><th>Vehicle</th>';
			echo '</tr>';
			foreach ($matches as $match) {
				echo '<tr style="border-bottom: 1px solid black">';
				echo '<td style="background-color: #eee;">' . $match['log_user'] . '</td>';
				echo '<td style="background-color: #eee; border-right: 1px dotted black">' . date('m/d/y g:i A', strtotime($match['log_date'])) . '</td>';
				echo "<td>" . $match['location'] . "</td>";
				echo "<td>" . $match['sku'] . "</td>";
				echo '<td><table style="padding-right: 2em;">';
				echo "	<tr>";
				echo "		<th>Year</th><th>Make</th><th>Model</th><th>Submodel</th>";
				echo "	</tr><tr>";
				echo "		<td>". $match['year'] ."</td><td>". $match['make'] ."</td><td>". $match['model'] ."</td><td>". $match['submodel'] ."</td>";
				echo "	</tr>";
				echo "</table></td>";
				echo "</tr>";
			}
			echo "</table>";
		}
		else {
			echo "<h1>Error</h1>";
			echo "There was no activity between " . $_POST['start_date'] . " and " . $_POST['end_date'];
			die();
		}
	}
	else {
		echo "<h1>Error</h1>";
		echo "You must set a start date and end date!";
		die();
	}
}
else {
?>

<!DOCTYPE html>
<html>
<head>
  <title>CSV Fitment Location Upload Log</title>
</head>
<body>
<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
    <div style="display: inline-block">
		<label for="start_date">Start Date:</label><br>
		<input type="date" name="start_date">
	</div>
	<div style="display: inline-block">
		<label for="end_date">End Date:</label><br>
		<input type="date" name="end_date">
	</div>

	<div>
		<input type="submit" name="submit" value="Show Log">
	</div>
</form>
</body>
</html>

<?php } ?>
