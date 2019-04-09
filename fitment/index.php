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

/* TODO: Convert
	if (isset($_GET['dump'])) {
		echo "<pre>";
		print_r($fitments);
		die();
	}
*/

if (isset($_GET['year']) === FALSE) {
	die();
}

// Sanity checking
if (is_numeric($_GET['year']) === FALSE || $_GET['year'] < 1970 || $_GET['year'] > date("Y") + 1) {
	die("Invalid Year!");
}

$_GET = array_map('strtolower', $_GET);

$stmt = 'SELECT sku AS SKU, location AS LOC FROM fitment_locations WHERE year=:year AND UPPER(make)=UPPER(:make) AND UPPER(model)=UPPER(:model) AND UPPER(submodel)=UPPER(:submodel)';
$stmt = $pdo->prepare($stmt);
$stmt->execute(array(
				'year' => $_GET['year'], 
				'make' => $_GET['make'], 
				'model' => $_GET['model'], 
				'submodel' => $_GET['submodel']
				));
$matches = $stmt->fetchAll();
echo json_encode($matches);
?>
