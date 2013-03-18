<?php

require_once('config.php');



// DB connection
try {
	$db = new PDO('mysql'.':host='.DB_HOST.';dbname='.DB_NAME,DB_USER,DB_PASSWORD,
									array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $est) {
		die("PDO connection error! ". $est->getMessage() ."<br/>");
}



$stmt = $db->prepare("SELECT * FROM fingerprints_TestOnly WHERE AP_Mac = :mac ORDER BY Position");
$stmt->bindValue(':mac', '00:22:55:75:44:b0', PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);


echo "<pre>";

echo var_dump($result);

echo "</pre>";


echo "-------";


foreach ($result as $val) {
	echo var_dump($val);
	echo $val['id'];
	break;
}

?>
