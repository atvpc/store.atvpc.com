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


function dump_fitment_locations() {
	global $pdo;

	$stmt = 'SELECT * FROM fitment_locations';
	$stmt = $pdo->prepare($stmt)->execute();

	return $stmt->fetchAll();
}

function print_table($array) {
	echo "<table>";
	foreach ($array as $row) {
		echo "<tr>";
		foreach ($row as $col) {
			echo "<td>" . $col . "</td>";
		}
		echo "</tr>";
	}
	echo "</table>";
}


print_table( dump_fitment_locations() );
?>