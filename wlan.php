<?php
include 'position_functions.php';
include 'fingerprint.php';
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
	<content>
		<item>
			<MAC>00:22:55:75:44:b1</MAC>
			<SIG>-60.5</SIG>
		</item>
		<item>
			<MAC>08:1f:f3:b2:d1:d1</MAC>
			<SIG>-84.0</SIG>
		</item>
		<item>
			<MAC>e4:ce:8f:6d:b5:95</MAC>
			<SIG>-95.9</SIG>
		</item>
		<item>
			<MAC>00:22:55:75:44:b0</MAC>
			<SIG>-59.4</SIG>
		</item>
		<item>
			<MAC>08:1f:f3:b2:d1:d0</MAC>
			<SIG>-82.0</SIG>
		</item>
	</content>
</session>
XML;
$xml=simplexml_load_string($receive);
$mac_addresses = array(  );
$user_signals = array(  );
foreach ($xml->content->item as $value) { 
	array_push($mac_addresses, $value->MAC);
	$user_signals[(string) $value->MAC] = $value->SIG;
}
$mac_string = "'". implode("', '",$mac_addresses) ."'";


// Format mac addresses in a list
$list = join(',', array_fill(0, count($mac_addresses), '?'));

// Fetch data from DB
$stmt = $db->prepare("
	SELECT *
    FROM fingerprints_TestOnly
    WHERE AP_Mac IN ($list)
    ORDER BY Position");
$stmt->execute($mac_addresses);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);



#fetching the mysql results got from the query, and storing 
#them to fingerPrints array as Fingerprint objects 
#according to their position coordinates and room name:

$fingerPrints = mysql_to_array($result);




#function old_calculate_position:
#The positioning function used in the old positioning engine.
#It compares the scanned signal strengths to the database
#signal strengths, and determines the position with the
#smallest distance. The function is not relative, and
#also doesn't take into account the fact that different
#positions might have different amount of router signal strength
#catched. So it only works if the database is done with the
#same phone that is then used in positioning, and all
#reference points have equal amount of router signal strengths
#catched into the database. Takes database signals; fingerPrints
# and user_signals as arguments. Compares those, and then echoes
#the results.

old_calculate_position($fingerPrints, $user_signals);



?>
