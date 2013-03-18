<?php
$con = mysql_connect("dbman.pacewebmedia.com","pdp_inpoint","*R4Ja8asWUz$");
if (!$con) {
	die('Could not connect: ' . mysql_error());
}
mysql_select_db("pdp_inpoint", $con);
$receive = file_get_contents('php://input');
$string = <<<XML
<?xml version='1.0'?> 
<session>
	<number>1</number>
	<coordinates>666,666</coordinates>
	<room>Puuhamaa</room>
	<content>
		<item>
			<variance>3,0</variance>
			<num_of_values>5</num_of_values>
			<MAC>00:22:55:75:44:b1</MAC>
			<SIG>-60.5</SIG>
		</item>
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
	</content>
</session>
XML;
$xml=simplexml_load_string($receive);

mysql_query("DELETE FROM liberRouter WHERE Room = '$xml->room' AND Position = '$xml->coordinates'");


foreach ($xml->content->item as $value) { 

	$hash_id = $xml->room . $xml->coordinates . $value->MAC;
	$id = md5($hash_id);

	$query_insert = mysql_query("INSERT INTO liberRouter (Room, Position, AP_Mac, Average, Variance, Values_Num, id) VALUES ('$xml->room','$xml->coordinates','$value->MAC','$value->SIG','$value->variance','$value->num_of_values','$id')");
	if($query_insert) {
		echo "update completed";
	}
	else {
		echo "update failed";
	}


}



mysql_close($con);




?>
