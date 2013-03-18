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
			<SIG>-36.0</SIG>
		</item>
		<item>
			<variance>3,0</variance>
			<num_of_values>5</num_of_values>
			<MAC>08:1f:f3:b2:d1:d1</MAC>
			<SIG>-65.666</SIG>
		</item>
		<item>
			<variance>3,0</variance>
			<num_of_values>5</num_of_values>
			<MAC>e4:ce:8f:6d:b5:95</MAC>
			<SIG>-78.666</SIG>
		</item>
		<item>
			<variance>3,0</variance>
			<num_of_values>5</num_of_values>
			<MAC>00:22:55:75:44:b0</MAC>
			<SIG>-47.777</SIG>
		</item>
		<item>
			<variance>3,0</variance>
			<num_of_values>5</num_of_values>
			<MAC>08:1f:f3:b2:d1:d0</MAC>
			<SIG>-64.666</SIG>
		</item>
		<item>
			<variance>3,0</variance>
			<num_of_values>5</num_of_values>
			<MAC>00:22:55:75:44:11</MAC>
			<SIG>-88.8</SIG>
		</item>
		<item>
			<variance>3,0</variance>
			<num_of_values>5</num_of_values>
			<MAC>00:22:55:75:44:10</MAC>
			<SIG>-89.4</SIG>
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

// Format mac addresses in a list
$list = join(',', array_fill(0, count($mac_addresses), '?'));

// Fetch data from DB
$stmt = $db->prepare("
	SELECT *
    FROM fingerprints_TestOnly2
    WHERE AP_Mac IN ($list)
    ORDER BY Position");
$stmt->execute($mac_addresses);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

#calculate the user measured signal strength relations (subtracts), 
#and insert them to an array:

$user_signals_relations = user_relations_to_array($user_signals);




#fetching the mysql results got from the query, and storing 
#them to fingerPrints array as Fingerprint objects 
#according to their position coordinates and room name:

$fingerPrints = mysql_to_array($result);

#foreach($fingerPrints as $value){
#	foreach($value as $value_2) {
#	
#		echo $value_2->get_room();
#		echo "<BR>";
#		echo $value_2->get_position();
#		echo "<BR>";
#		echo "<BR>";
#
#
#	}
#
#
#}


#Calculating the relations of different database router signal strengths; 
#making every same position and room signal strengths into an object, and 
#storing all the objects to an array:

$allRelations = database_relations_to_array($fingerPrints);


#foreach($allRelations as $value) {

#	echo "<BR>";
#	echo "<BR>";

#	echo $value->get_room() . ": " . $value->get_position() . ": ";
	
#	$localRelations = $value->get_relations();

#	foreach($localRelations as $key => $value_2) {

#	echo "<BR>";
#	echo "<BR>";
	
#	echo $key . ": " . $value_2;


#	}

#}


#Calculating the absolute distance between database router signal 
#strength relations and user scanned signal strength relations:

$userComparison = absolute_distance($allRelations, $user_signals_relations);

#foreach($userComparison as $value) {

#	echo "<BR>";
#	echo "<BR>";

#	echo $value->get_room() . ": " . $value->get_position();

#	$localRelations = $value->get_relations();

#	foreach($localRelations as $key => $value_2) {

#	echo "<BR>";

#	echo "<BR>";

#	echo $key . ": " . $value_2;

#	}
	

#}


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

$allTotals = show_position($userComparison);

echo "<BR>";
echo "<BR>";

foreach($allTotals as $value) {

	$room = $value->get_room();
	$position = $value->get_position();
	$difference = $value->get_difference();

	echo"Room: " . $room . " Position: " . $position . " Difference: " . $difference;

	echo "<BR>";
	echo "<BR>";


}


#Collecting to an array all the best (smallest) values:

#$collectionArray = collect_best($userComparison);

	

#Counting that which position has the most of the smallest values:

#$countingArray = count_best($collectionArray);


#echoing the results. Key is room and position, value is
#how many times that room and position had the most closest
#value:

#echo "does it work";

#foreach($countingArray as $key => $value) {

#	echo "anyone";

#	echo $key . " " . $value;


#	}


?>
