<?php

include 'fingerprint.php';
require_once('config.php');



#function mysql_to_array:
#mysql_to_array function will take the mysql query result as an argument.
#It will fetch those results, and store them to an array of arrays of
#Fingerprint objects, according to position coordinates and room name.
#The content of an array contains Fingerprint objects that all have same
#position and coordinates. The goal is to separate and order query results
#according to their room name and coordinates. The function will only
#work, if the mysql query was done ORDER BY Position. In the end, the
#function will return the array of arrays of Fingerprint objects.

function mysql_to_array($query_result)
{

	#Establishing the returned array and some helper variables:

	$fingerPrints = array(); #array of arrays, which will contain Fingerprint objects.
	$outer_index = 0; #outerindex for the array.
	$inner_index = 0; #innerindex for the array.
	$helper = 0; #helper variable for the first time.


	#Looping through the query result one row at a time:

	foreach ($query_result as $row)
	{


		if ($helper == 0)  #this is special case for the first Fingerprint object to be created
		{
			#for first time, the loop will insert a new Fingerprint object for 
			#the index [0][0], and then increase inner index by one.

			if (abs($row['Average']) < 90.0 and $row['Variance'] < 15.0) {

				$fingerPrints[$outer_index][$inner_index] = new Fingerprint($row['Room'], 
					$row['Position'], $row['AP_Mac'], $row['Average'], $row['Variance'],
						$row['Values_Num'], $row['id'] );

				$inner_index = $inner_index + 1;
				$helper = 1;

			}
		}
		elseif ($row['Position'] != $fingerPrints[$outer_index][$inner_index - 1]->get_position()
		       	or $row['Room'] != $fingerPrints[$outer_index][$inner_index - 1]->get_room())
		{
			#if the next row in the query results has a different position coordinates,
			#or different room than the one before, then we will know, that our function
			#has iterated all values of the same (coordinate - room), and is time to make
			#room for the next one, that will now begin. Therefore we will set the inner
			#index to zero, and increase outer index by one. So we will start filling
			#the next array with Fingerprint objects with either different room or 
			#coordinates, or both.

			if (abs($row['Average']) < 90.0 and $row['Variance'] < 15.0) {

			$inner_index = 0;
			$outer_index = $outer_index + 1;
			$fingerPrints[$outer_index][$inner_index] = new Fingerprint($row['Room'], 
				$row['Position'], $row['AP_Mac'], $row['Average'], $row['Variance'],
					$row['Values_Num'], $row['id'] );

			$inner_index = $inner_index + 1;
			}
		}
		else
		{
			#if the next row in the query results has the same position and coordinates
			#than the one before, we will know that we have not yet collected all results
			#with same room and coordinate. Therefore we will continue filling the current
			#array, and in the end increase the inner index.

			if (abs($row['Average']) < 90.0 and $row['Variance'] < 15.0) {


			$fingerPrints[$outer_index][$inner_index] = new Fingerprint($row['Room'],
				$row['Position'], $row['AP_Mac'], $row['Average'], $row['Variance'],
					$row['Values_Num'], $row['id'] );

			$inner_index = $inner_index + 1;

			}
		}
	}


	return $fingerPrints;

}



#function user_relations_to_array:
#calculate the user measured signal strength relations
#(subtracts), and insert them to an array. Takes
#user signals array as an arguments, and returns
#an array of relation (between different
#router signal strengths) values:
#edit: now it computes distances, and then subtracts
#them.

function user_relations_to_array($user_signals)
{
	$user_signals_relations = array();
	foreach ($user_signals as $key => $value) {
		foreach ($user_signals as $key_2 => $value_2) {
			if (($key_2 != $key) and 
				(array_key_exists($key . "-" . $key_2, $user_signals_relations)== FALSE)
				and (array_key_exists($key_2 . "-" . $key, $user_signals_relations) == FALSE)) {

					$abso_value = abs($value);
					$abso_value_2 = abs($value_2);


					if ($abso_value < 54) {

						$x = 5.0* (pow(10, (($abso_value - 54.0)/20.0)));

					}
					else {

						$x = 5.0* (pow(10, (($abso_value - 54.0)/35.0)));


					}

					if ($abso_value_2 < 54) {

						$y =  5.0* (pow(10, (($abso_value_2 - 54.0)/20.0)));


					}
					else {

						$y = 5.0* (pow(10, (($abso_value_2 - 54.0)/35.0)));

					}



					
					$user_signals_relations[$key . "-" . $key_2] = $x - $y;


			}
		}
	}	

	return $user_signals_relations;
}



#function database_relations_to_array:
#Calulcating the relations of different database router
#signal strengths; making every same position and room 
#signal strengths into an object, and storing all the objects
#to an array. It takes fingerPrints, an array of arrays of
#fingerPrint objects as an argument. Return value is
#array of database relation objects; FprintRelations.
#Every object in the allRelations array has 3 attributes / 
#variables: room name, coordinates and the relation (subtract)
#list.
#edit: now it computes distances to routers, and subtracts them.



function database_relations_to_array($fingerPrints)
{

	$allRelations = array();


	foreach ($fingerPrints as $value) {
		$relations = array();
		foreach ($value as $value_2) {
			foreach ($value as $value_3) {
				if (($value_2 != $value_3) and 
					(array_key_exists($value_2->get_mac()."-".$value_3->get_mac(), 
					$relations)==FALSE) and
					(array_key_exists($value_3->get_mac()."-".$value_2->get_mac(),
					$relations) == FALSE)) {

					$abso_value_2 = abs($value_2->get_average());
					$abso_value_3 = abs($value_3->get_average());
				
					if ($abso_value_2 < 54) {

						$x = 5.0* (pow(10, (($abso_value_2 - 54.0)/20.0)));

					}
					else {

						$x = 5.0* (pow(10, (($abso_value_2 - 54.0)/35.0)));


					}

					if ($abso_value_3 < 54) {

						$y =  5.0* (pow(10, (($abso_value_3 - 54.0)/20.0)));


					}
					else {

						$y = 5.0* (pow(10, (($abso_value_3 - 54.0)/35.0)));

					}




					$relations[$value_2->get_mac() . "-" . $value_3->get_mac()] = $x - $y;
				}	
			}
		}
		$fprintRelations = new FprintRelations($value_2->get_room(), $value_2->get_position(), $relations);
		array_push($allRelations, $fprintRelations);
	}


	return $allRelations;


}


#function absolute_distance:
#Calculating the absolute distance between database router signal
#strength relations and user scanned signal strength relations:

function absolute_distance($allRelations, $user_signals_relations)
{
	$userComparison = array();
	foreach ($allRelations as $value) {
		$localRelations = array();
		$helperArray = $value->get_relations();
		foreach ($user_signals_relations as $key => $value_2) {
			$keyBackwards_firstPart = $key[18].$key[19].$key[20].$key[21].$key[22].$key[23].$key[24].
				$key[25].$key[26].$key[27].$key[28].$key[29].$key[30].$key[31]
				.$key[32].$key[33].$key[34]."-";
			$keyBackwards_secondPart = $key[0].$key[1].$key[2].$key[3].$key[4].$key[5].$key[6].
				$key[7].$key[8].$key[9].$key[10].$key[11].$key[12].$key[13].$key[14].
				$key[15].$key[16];
			$keyBackwards = $keyBackwards_firstPart . $keyBackwards_secondPart;
			if (array_key_exists($key, $helperArray)) {
				$localRelations[$key] = abs($value_2 - $helperArray[$key]); 
			}
			if (array_key_exists((string)$keyBackwards, $helperArray)) {
				$localRelations[$key] = abs($value_2 + $helperArray[$keyBackwards]);
			}
		}
		$comparisonRelations = new FprintRelations($value->get_room(), $value->get_position(), $localRelations);
		array_push($userComparison, $comparisonRelations);
	}

	return $userComparison;


}


#function collect_best: 
#Takes userComparison array as an argument. The array
#consists of FprintRelations objects. Returns collectionArray
#, that has all the best (smallest) relation values and
#their respective room and position information:

function collect_best($userComparison)
{
	
	$collectionArray = array();

	foreach($userComparison as $value) {
		$localRelations = $value->get_relations();
		foreach ($localRelations as $key => $value_2) {
			if (array_key_exists($key, $collectionArray)) {
				if ($collectionArray[$key]->get_difference() > $value_2) {
					$difference = new FprintDifference($value->get_room(), $value->get_position(), $value_2);
					$collectionArray[$key] = $difference;
					}
				}
			else {
				$difference = new FprintDifference($value->get_room(), $value->get_position(), $value_2);
				$collectionArray[$key] = $difference;
				}
			}
		}
	return $collectionArray;

}



#function count_best:
#Counting that which position has the most of the smallest values.
#For the smallest value, if difference is bigger than 10, then it
#won't count. That is probably not found in the correct place, 
#and then found in some wrong reference point in the database.

function count_best($collectionArray)
{
	$countingArray = array();

	foreach($collectionArray as $value) {
		$key = $value->get_room() . ": " . $value->get_position();
		if (array_key_exists($key, $countingArray)) {
			if ($value->get_difference() < 10) {
				$countingArray[$key] = $countingArray[$key] + 1;
				}
			else {
				$countingArray[$key] = $countingArray[$key] + 0;
				}
			}
		else {
			if ($value->get_difference() < 10) {
				$countingArray[$key] = 1;
				}
			else {
				$countingArray[$key] = 0;
				}
			}
		}

	return $countingArray;

}




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

function old_calculate_position($fingerPrints, $user_signals)
{

	$closest_distance = 100000000.0;
	$closest_coordinates = "000,000";
	$closest_room = "default";
	$a = 0;
	foreach ($fingerPrints as $outer) {
		$temp_sum = 0.0;
		foreach ($outer as $value) {	
			foreach($user_signals as $mac => $sig) {
				if ($mac == $value->get_mac()) {
					$signal_distance = $value->get_average() - $sig;
					$absolute_distance = abs($signal_distance);
					$temp_sum = $temp_sum + $absolute_distance;	
					break;
				}
			}			  
		}
		if ($temp_sum < $closest_distance) {
			$closest_distance = $temp_sum;
			$closest_coordinates = $fingerPrints[$a][0]->get_position();
		$closest_room = $fingerPrints[$a][0]->get_room();
		}
		$a = $a + 1;
	}
	echo $closest_coordinates; 
	echo "_";
	echo $closest_room;
}




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

function show_position($userComparison) 
{
	$allTotals = array();

	$smallestValue = -1.0;

	$rightRoom = "default";

	$rightCoordinates = "default";

	foreach($userComparison as $value) {
	

		$local_relations = $value->get_relations();

		$temp_sum = 0.0;

		$n = 0;

		foreach($local_relations as $value_2) {	
			$temp_sum = $temp_sum + $value_2;
			$n = $n + 1;	
			}
	
		if ( $n >= 3) {

			$relative_sum = $temp_sum / (double)($n);

			if ($smallestValue == -1.0) {

				$smallestValue = $relative_sum;
				$rightRoom = $value->get_room();
				$rightCoordinates = $value->get_position();

				}
			elseif ($smallestValue > $relative_sum) {
			
				$smallestValue = $relative_sum;
				$rightRoom = $value->get_room();
				$rightCoordinates = $value->get_position();
			
				}


			$totalDifference = new FprintDifference($value->get_room(), $value->get_position(), $relative_sum);
			array_push($allTotals, $totalDifference);
			}

	}


	echo $rightRoom . ": " . $rightCoordinates . ", relative distance was:" . $smallestValue;


	return $allTotals;


}


#Relative positioning functions that use division instead of subtract:
#
#

function user_divisions_to_array($user_signals) {

	$user_signals_relations = array();
	foreach ($user_signals as $key => $value) {
		foreach ($user_signals as $key_2 => $value_2) {
			if (($key_2 != $key) and 
				(array_key_exists($key . "/" . $key_2, $user_signals_relations)== FALSE)
			       		and (array_key_exists($key_2 . "/" . $key, $user_signals_relations) == FALSE)) {

				$user_signals_relations[$key . "/" . $key_2] = $value / $value_2;
			}
		}
	}	

	return $user_signals_relations;



}

function database_divisions_to_array($fingerPrints) {

	$allRelations = array();


	foreach ($fingerPrints as $value) {
		$relations = array();
		foreach ($value as $value_2) {
			foreach ($value as $value_3) {
				if (($value_2 != $value_3) and 
					(array_key_exists($value_2->get_mac()."/".$value_3->get_mac(), 
					$relations)==FALSE) and
					(array_key_exists($value_3->get_mac()."/".$value_2->get_mac(),
					$relations) == FALSE)) {

					$relations[$value_2->get_mac() . "/" . $value_3->get_mac()] = 
					$value_2->get_average() / $value_3->get_average();
				}	
			}
		}
		$fprintRelations = new FprintRelations($value_2->get_room(), $value_2->get_position(), $relations);
		array_push($allRelations, $fprintRelations);
	}


	return $allRelations;



}

function division_distance($allRelations, $user_signals_relations) {

	$userComparison = array();
	foreach ($allRelations as $value) {
		$localRelations = array();
		$helperArray = $value->get_relations();
		foreach ($user_signals_relations as $key => $value_2) {
			$keyBackwards_firstPart = $key[18].$key[19].$key[20].$key[21].$key[22].$key[23].$key[24].
				$key[25].$key[26].$key[27].$key[28].$key[29].$key[30].$key[31]
				.$key[32].$key[33].$key[34]."/";
			$keyBackwards_secondPart = $key[0].$key[1].$key[2].$key[3].$key[4].$key[5].$key[6].
				$key[7].$key[8].$key[9].$key[10].$key[11].$key[12].$key[13].$key[14].
				$key[15].$key[16];
			$keyBackwards = $keyBackwards_firstPart . $keyBackwards_secondPart;
			if (array_key_exists($key, $helperArray)) {
				$localRelations[$key] = abs($value_2 - $helperArray[$key]); 
			}
			if (array_key_exists((string)$keyBackwards, $helperArray)) {
				$localRelations[$key] = abs($value_2 -  (1 / $helperArray[$keyBackwards]));
			}
		}
		$comparisonRelations = new FprintRelations($value->get_room(), $value->get_position(), $localRelations);
		array_push($userComparison, $comparisonRelations);
	}

	return $userComparison;		




}




?>

