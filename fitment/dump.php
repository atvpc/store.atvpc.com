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

function dump_vehicles() {
	global $pdo;

	$stmt = 'SELECT year.name AS year, make.name AS make, model.name AS model, submodel.name AS submodel ' .
		'FROM amasty_finder_value AS year '.
		'INNER JOIN amasty_finder_value AS make ON make.parent_id = year.value_id '.
		'INNER JOIN amasty_finder_value AS model ON model.parent_id = make.value_id '.
		'INNER JOIN amasty_finder_value AS submodel ON submodel.parent_id = model.value_id '.
		'WHERE submodel.dropdown_id = 4 AND submodel.name != "Submodel" '.
		'ORDER BY year.name DESC, make.name ASC';

	$stmt = $pdo->prepare($stmt);
	$stmt->execute();
	return $stmt->fetchAll();
}

function dump_skus() {
	global $pdo;

	$stmt = 'SELECT year.name AS year, make.name AS make, model.name AS model, submodel.name AS submodel, sku.sku AS sku ' .
		'FROM amasty_finder_value AS year '.
		'INNER JOIN amasty_finder_value AS make ON make.parent_id = year.value_id '.
		'INNER JOIN amasty_finder_value AS model ON model.parent_id = make.value_id '.
		'INNER JOIN amasty_finder_value AS submodel ON submodel.parent_id = model.value_id '.
		'INNER JOIN amasty_finder_map AS sku ON sku.value_id = submodel.value_id '.
		'WHERE sku.sku != "SKU" '.
		'ORDER BY year.name DESC, make.name ASC';

	$stmt = $pdo->prepare($stmt);
	$stmt->execute();
	return $stmt->fetchAll();
}

function dump_missing_locations() {
	global $pdo;
	$skus = dump_skus();
	$missing = array();

	foreach ($skus as $sku) {
		$stmt = 'SELECT id FROM fitment_locations WHERE year=:year AND UPPER(make)=UPPER(:make) AND UPPER(model)=UPPER(:model) AND UPPER(submodel)=UPPER(:submodel) AND UPPER(sku)=UPPER(:sku)';
		$stmt = $pdo->prepare($stmt);
		$stmt->execute([
			'year'     => $sku['year'],
			'make'     => $sku['make'],
			'model'    => $sku['model'],
			'submodel' => $sku['submodel'],
			'sku'      => $sku['sku']
			]);

		if (sizeof($stmt->fetchAll()) === 0) {
			$missing[] = $sku;
		}
	}

	return $missing;
}

function dump_fitment_locations() {
	global $pdo;

	$stmt = 'SELECT year,make,model,submodel,sku,location FROM fitment_locations ORDER BY year DESC, make ASC';
	$stmt = $pdo->prepare($stmt);
	$stmt->execute();

	return $stmt->fetchAll();
}

function print_table($array) {
	echo "<table>";

	echo "<tr>";
	foreach (array_keys($array[0]) as $key) {
		echo "<th>" . $key . "</th>";
	}
	echo "</tr>";

	foreach ($array as $row) {
		echo "<tr>";

		foreach ($row as $col) {
			echo "<td>" . $col . "</td>";
		}

		echo "</tr>";
	}
	echo "</table>";
}

if (isset($_GET['dump']) === false) $_GET['dump'] = false;

switch ($_GET['dump']) {
	case 'vehicles':
		print_table( dump_vehicles() );
		break;
	case 'fitments':
		print_table( dump_fitment_locations() );
		break;
	case 'skus':
		print_table( dump_skus() );
		break;
	case 'missing':
		print_table( dump_missing_locations() );
		break;
	default:
		?>
		<ul>
			<li><a href="dump.php?dump=vehicles">All Vehicles in Amasty</a>
			<li><a href="dump.php?dump=fitments">All Fitment Locations</a>
			<li><a href="dump.php?dump=skus">All SKUs and their fitments in Amasty</a>
			<li><a href="dump.php?dump=missing">SKUs missing fitment location</a>
		</ul>
<?php } ?>
