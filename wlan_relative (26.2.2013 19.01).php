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







/*$con = mysql_connect("dbman.pacewebmedia.com","pdp_inpoint","*R4Ja8asWUz$");
if (!$con) {
	die('Could not connect: ' . mysql_error());
}
mysql_select_db("pdp_inpoint", $con);*/


#Creating a test XML-string, to be used in testing only:

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
			<SIG>-60.667</SIG>
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
			<SIG>-96.0</SIG>
		</item>
		<item>
			<variance>3,0</variance>
			<num_of_values>5</num_of_values>
			<MAC>00:22:55:75:44:b0</MAC>
			<SIG>-59.556</SIG>
		</item>
		<item>
			<variance>3,0</variance>
			<num_of_values>5</num_of_values>
			<MAC>08:1f:f3:b2:d1:d0</MAC>
			<SIG>-82.111</SIG>
		</item>
	</content>
</session>
XML;


// Load XML raw string
$receive = file_get_contents('php://input');
$xml=simplexml_load_string($string);

//
$mac_addresses = array(  );
$user_signals = array(  );

// Parse items from XML
foreach ($xml->content->item as $value) { 
	
	// Append to array
	array_push($mac_addresses, $value->MAC);
	
	// Append to array
	$user_signals[(string) $value->MAC] = floatval($value->SIG);
	
}
$mac_addresses = array('00:22:55:75:44:b1', '08:1f:f3:b2:d1:d1');

// Format mac addresses in array
$mac_string = "'". implode("', '",$mac_addresses) ."'";
$list = join(',', array_fill(0, count($mac_addresses), '?'));

// Fetch data from DB
$stmt = $db->prepare("
	SELECT *
    FROM fingerprints
    WHERE AP_Mac IN ($list)
    ORDER BY Position");
//$stmt->bindParam(':mac', $mac_addresses);
$stmt->execute($mac_addresses);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo var_dump($result);
echo $mac_string;

#calculate the user measured signal strength relations (subtracts), 
#and insert them to an array:

$user_signals_relations = user_relations_to_array($user_signals);


#fetching the mysql results got from the query, and storing 
#them to fingerPrints array as Fingerprint objects 
#according to their position coordinates and room name:

$fingerPrints = mysql_to_array($result);


#Calculating the relations of different database router signal strengths; 
#making every same position and room signal strengths into an object, and 
#storing all the objects to an array:

$allRelations = database_relations_to_array($fingerPrints);


#Calculating the absolute distance between database router signal 
#strength relations and user scanned signal strength relations:

$userComparison = absolute_distance($allRelations, $user_signal_relations);


#function show_position:
#Sums up the absolute distances for each position. After that,
#it divides the total absolute distance for the amount of 
#relation that position had. This way we will have relative
#absolute total distance for every position. But, we require,
#that there must be more than 3 relations, otherwise it would
#not be accurate. Takes $userComparison array as an argument,
#that consists of FprintRelations objects inside. Echoes the
#right room, coordinates and respective relative absolute total
#distance of it. Returns an array filled with FprintDifference
#objects, which include the total absolute relative distance every 
#position with more than 3 relations had. Return value is just
#for testing.

//$allTotals = show_position($userComparison);




#Collecting to an array all the best (smallest) values:

$collectionArray = collect_best($userComparison);

	

#Counting that which position has the most of the smallest values:

$countingArray = count_best($collectionArray);


#echoing the results. Key is room and position, value is
#how many times that room and position had the most closest
#value:

foreach($countingArray as $key => $value) {

	echo "anyone";

	echo $key . " " . $value;


	}


?>
