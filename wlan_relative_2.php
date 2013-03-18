<?php
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
    </content>
</session>
XML;


$xml=simplexml_load_string($string);
$mac_addresses = array(  );
$user_signals = array(  );
foreach ($xml->content->item as $value) { 
    array_push($mac_addresses, $value->MAC);
    $user_signals[(string) $value->MAC] = floatval($value->SIG);
}
$mac_string = "'". implode("', '",$mac_addresses) ."'";


// Fetch data from DB
$stmt = $db->prepare("SELECT * FROM fingerprints_TestOnly WHERE AP_Mac = :mac ORDER BY Position");
$stmt->bindValue(':mac', $mac_string, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);




#calculate the user measured signal strength relations (subtracts), and insert them to an array:

$user_signals_relations = array();
foreach ($user_signals as $key => $value) {
    foreach ($user_signals as $key_2 => $value_2) {
        if (($key_2 != $key) and (array_key_exists($key . "-" . $key_2, $user_signals_relations) == FALSE) and (array_key_exists($key_2 . "-" . $key, $user_signals_relations) == FALSE)) {
            $user_signals_relations[$key . "-" . $key_2] = $value - $value_2;
            }
        }
    }

echo "<br />######## User Measured Relations ############<br />";
foreach($user_signals_relations as $key => $value) {


    echo $key . ": " . $value . "<br />";



    }
echo "<br />---------------------------------------------<br />";


#creating fingerPrintsDB double array and some helper variables:

$fingerPrintsDB = array();
$outer_index = 0;   //represent the different MAC on same coordinate
$inner_index = 0;   //represent the different room/coordinate
$helper = 0;

#fetching the mysql results got from the query, and storing them to fingerPrintsDB array as Fingerprint elements according to their position coordinates and room name:

foreach($result as $row)
    {
        if ($helper == 0)
        {
            $fingerPrintsDB[$outer_index][$inner_index] = new Fingerprint($row['Room'], $row['Position'], $row['AP_Mac'], $row['Average'], $row['Variance'], $row['Values_Num'], $row['id'] );
            $inner_index = $inner_index + 1;
            $helper = 1;
        }
        elseif ($row['Position'] != $fingerPrintsDB[$outer_index][$inner_index - 1]->get_position() or $row['Room'] != $fingerPrintsDB[$outer_index][$inner_index - 1]->get_room())
        {
            $inner_index = 0;
            $outer_index = $outer_index + 1;
            $fingerPrintsDB[$outer_index][$inner_index] = new Fingerprint($row['Room'], $row['Position'], $row['AP_Mac'], $row['Average'], $row['Variance'], $row['Values_Num'], $row['id'] );
            $inner_index = $inner_index + 1;
        }
        else
        {
            $fingerPrintsDB[$outer_index][$inner_index] = new Fingerprint($row['Room'], $row['Position'], $row['AP_Mac'], $row['Average'], $row['Variance'], $row['Values_Num'], $row['id'] );
            $inner_index = $inner_index + 1;
        }
    }

#Calulcating the relations of different database router signal strengths; making every same position and room signal strengths into an object, and storing all the objects to an array:




$allRelationsDB = array();


foreach ($fingerPrintsDB as $value) {
    $relationsDB = array();
    foreach ($value as $value_2) {
        foreach ($value as $value_3) {
            if (($value_2 != $value_3) and (array_key_exists($value_2->get_mac() . "-" . $value_3->get_mac(), $relationsDB) == FALSE) and (array_key_exists($value_3->get_mac() . "-" . $value_2->get_mac(), $relationsDB) == FALSE)) {
                $relationsDB[$value_2->get_mac() . "-" . $value_3->get_mac()] = $value_2->get_average() - $value_3->get_average();
            }   
        }
    }
    $fprintRelations = new FprintRelations($value_2->get_room(), $value_2->get_position(), $relationsDB);
    array_push($allRelationsDB, $fprintRelations);
}

echo "<br />############ DB Relations ###################<br />";
foreach ($allRelationsDB as $value) {

    $localRelations = $value->get_relations();

    echo $value->get_room() . " " . $value->get_position() . " " . "<br />";


    foreach($localRelations as $key => $value2) {

        echo $key . ": " . $value2 . " " . "<br />";

    }


}

echo "<br />---------------------------------------------<br />";



#Calculating the absolute distance between database router signal strength relations and user scanned signal strength relations:

#echo "<br />~~~~~~~~~~~~~~Calculation~~~~~~~~~~~~~~~~~~~~<br />";
$userComparison = array();
foreach ($allRelationsDB as $value) {
    echo "One Attempt" . "<br />";
    $localRelations = array();
    foreach ($user_signals_relations as $key => $value_2) {
        echo $key . "  =>  " . $value_2 . "<br />";
        $helperArray = $value->get_relations();
        foreach ($helperArray as $key_x => $value_x) {
            if ($key == $key_x){
                echo "Match-----";
                $localRelations[$key] = abs($value_2 - $value_x); 
                echo $value_2 . " => " . $value_x . "<br />";
            }
            else{
                echo "not match" . "<br />";
            }
        }
    }
    echo ">>>>>>>>>>" . "<br />";
    $comparisonRelations = new FprintRelations($value->get_room(), $value->get_position(), $localRelations);
    array_push($userComparison, $comparisonRelations);
}
echo "<br />~~~~~~~~~~~~  Calculation End  ~~~~~~~~~~~~~<br />";
#foreach ($userComparison as $value) {

#   echo $value->get_room() . " " . $value->get_position();

#   $localRelations = $value->get_relations();

#   foreach ($localRelations as $key => $value2) {

#       echo $key . ": " . $value2 . " ";


#       }


#   }


#Collecting to an array all the best (smallest) values:
/*
$collectionArray = array();

foreach($userComparison as $value) {
    $localRelations = $value->get_relations();
    foreach ($localRelations as $key => $value_2) {
        
        foreach ($collectionArray as $key_col => $value_col){
            if ($key == $key_col) {
                echo get_difference($value_col) . "<br />";
                if (get_difference($value_col) > $value_2) {
                    $difference = new FprintDifference($value->get_room(), $value->get_position(), $value_2);
                    $value_col = $difference;
                }
            }
            else {
                $difference = new FprintDifference($value->get_room(), $value->get_position(), $value_2);
                $collectionArray[$key] = $difference;
            }
        }
    }
} 
*/


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



echo "<br />### Calculated Results >>> Closed coordinate in DB ###<br />";
foreach($collectionArray as $key => $value) {


    echo $key . ": " . $value->get_room() . " " . $value->get_position() . " " . $value->get_difference . "<br />";



    }
echo "<br />--------------------------------------------------<br />";

#Counting that which position has the most of the smallest values:

$countingArray = array();

foreach($collectionArray as $value) {
    $key = $value->get_room() . ": " . $value->get_position();
    if (array_key_exists($key, $countingArray)) {
        if ($value->get_difference() < 0.2) {
            $countingArray[$key] = $countingArray[$key] + 1;
            }
        else {
            $countingArray[$key] = $countingArray[$key] + 0.5;
            }
        }
    else {
        if ($value->get_difference() < 0.2) {
            $countingArray[$key] = 1;
            }
        else {
            $countingArray[$key] = 0.5;
            }
        }
    }

$countingNum = array();

foreach($collectionArray as $value) {
    $key = $value->get_room() . ": " . $value->get_position();
    if (array_key_exists($key, $countingNum)) {
        $countingNum[$key] = $countingNum[$key] + 1;
    }
    else {
        $countingNum[$key] = 1;
    }
}

$totalVar = array();

foreach($collectionArray as $value) {
    $key = $value->get_room() . ": " . $value->get_position();
    if (array_key_exists($key, $totalVar)) {
        $totalVar[$key] = $totalVar[$key] + $value->get_difference();
    }
    else {
        $totalVar[$key] = $value->get_difference();
    }
}



echo "<br />";
echo "Closest Coordinates Rank:" . "<br />";
foreach($countingArray as $key => $value) {

    echo $key . " >>Points>> " . $value . "<br />";

}

echo "<br />----------------------<br />";

foreach($countingNum as $key => $value) {

    echo $key . " >>Numbers>> " . $value . "<br />";

}

echo "<br />----------------------<br />";

foreach($totalVar as $key => $value) {

    echo $key . " >>Total Variance>> " . $value . "<br />";

}

?>