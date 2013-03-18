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

$receive = file_get_contents('php://input');
$string = <<<XML
<?xml version='1.0'?> 
<session>
	<number>1</number>
	<coordinates>111,222</coordinates>
	<room>Puuhamaa</room>
	<content>
		<item>
			<variance>3,0</variance>
			<num_of_values>5</num_of_values>
			<MAC>08:1f:f3:b2:d1:d1</MAC>
			<SIG>-84.0</SIG>
		</item>
		<item>
			<variance>3,0</variance>
			<num_of_values>5</num_of_values>
			<MAC>e4:ce:8f:6d:b5:95</MAC>
			<SIG>-95.9</SIG>
		</item>
		<item>
			<variance>3,0</variance>
			<num_of_values>5</num_of_values>
			<MAC>00:22:55:75:44:b0</MAC>
			<SIG>-59.4</SIG>
		</item>
		<item>
			<variance>3,0</variance>
			<num_of_values>5</num_of_values>
			<MAC>08:1f:f3:b2:d1:d0</MAC>
			<SIG>-82.0</SIG>
		</item>
		<item>
			<variance>3,0</variance>
			<num_of_values>5</num_of_values>
			<MAC>11:1f:f3:b2:d1:d0</MAC>
			<SIG>-82.0</SIG>
		</item>
		<item>
			<variance>3,0</variance>
			<num_of_values>5</num_of_values>
			<MAC>00:00:00:00:01:01</MAC>
			<SIG>-75.0</SIG>
		</item>

	</content>
</session>
XML;
$xml=simplexml_load_string($receive);

$stmt = $db->prepare("DELETE FROM fingerprints_TestOnly WHERE Room = :room AND Position = :position ORDER BY Position");
$stmt->bindValue(':room', $xml->room, PDO::PARAM_STR);
$stmt->bindValue(':position', $xml->coordinates, PDO::PARAM_STR);
$stmt->execute();

//mysql_query("DELETE FROM fingerprints_TestOnly WHERE Room = '$xml->room' AND Position = '$xml->coordinates'");


foreach ($xml->content->item as $value) { 

	$hash_id = $xml->room . $xml->coordinates . $value->MAC;
	$id = md5($hash_id);

	$stmt_2 = $db->prepare("INSERT INTO fingerprints_TestOnly (Room, Position, AP_Mac, Average, Variance, Values_Num, id, datetime) VALUES (:room,:coordinates,:mac,:sig,:variance,:numOfValues,:id,:datetime)");

	$stmt_2->bindValue(':room', $xml->room, PDO::PARAM_STR);
	$stmt_2->bindValue(':coordinates', $xml->coordinates, PDO::PARAM_STR);

	$stmt_2->bindValue(':mac', $value->MAC, PDO::PARAM_STR);

	$stmt_2->bindValue(':sig', $value->SIG, PDO::PARAM_STR);

	$stmt_2->bindValue(':variance', $value->variance, PDO::PARAM_STR);

	$stmt_2->bindValue(':numOfValues', $value->num_of_values, PDO::PARAM_STR);

	$stmt_2->bindValue(':id', $id, PDO::PARAM_STR);
	
	$stmt_2->bindValue(':datetime', date('Y-m-d H:i:s') , PDO::PARAM_STR);

	$stmt_2->execute();

	//$query_insert = mysql_query("INSERT INTO fingerprints_TestOnly (Room, Position, AP_Mac, Average, Variance, Values_Num, id) VALUES ('$xml->room','$xml->coordinates','$value->MAC','$value->SIG','$value->variance','$value->num_of_values','$id')");
	//if($query_insert) {
	//	echo "update completed";
	//}
	//else {
	//	echo "update failed";
	//}


}

?>
				