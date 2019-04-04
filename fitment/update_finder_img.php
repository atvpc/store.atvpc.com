<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

$makes = $pdo->query('SELECT DISTINCT name FROM amasty_finder_value WHERE dropdown_id = 2 AND name != "Make" AND name != ""')->fetchAll(PDO::FETCH_COLUMN);
$count = 0;
foreach ($makes as $make) {
	$img = '/amasty/finder/images/' . $make . '/1.png';

	$sql = 'UPDATE amasty_finder_value SET image=:img WHERE name=:make AND dropdown_id=2';
	$stmt = $pdo->prepare($sql);
	$stmt->execute(['img' => $img, 'make' => $make]);

	$count += $stmt->rowCount();
}

echo "Updated $count images";

?>
