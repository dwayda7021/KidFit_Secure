<?php

/* removePlayerFromGameInstance.php
INPUT -- EMAIL, ACCESS TOKEN, FRND TO BE REMOVED AND GAME INSTANCE ID
OUTPUT -- SUCCESS 
	

*/ 

$gameInstanceID = $_GET['gameInstanceID'];
$self= $_GET['self'];
$frndID = $_GET['friendID'];
$accessToken = $_GET['accessToken'];


// ****** CONNECT TO THE DATABASE ***********

$servername = "127.0.0.1";
$username = "root";
$password = "K1dzteam!";
$dbname = "FitData";
$client = '228NH4';
$secret = 'acd369c14cacd73f6985f84b24d4267d';
$encoding = base64_encode("$client:$secret");
$url = 'https://api.fitbit.com/oauth2/token';
$sqlDateTime = date_create('now');
$currDate = date("Y-m-d H:i:s", strtotime("now"));
$expiryTime = date("Y-m-d H:i:s", strtotime("now"));
$todaysDate = date_create('now');
$todaysDate = date_format($todaysDate, 'Y-m-d');
$conn = new mysqli($servername, $username, $password, $dbname);



if ($conn->connect_error) {

   die("Connection failed: " . $conn->connect_error);
}


// **** LOGIN VERIFICATION ********
$loginQuery = "SELECT * from users where email = '$self'";
$loginData = $conn->query($loginQuery);
$loginrow = $loginData->fetch_assoc();
$act = $loginrow['appAccessToken'];
$selfID = $loginrow['userID'];

if($act!= $accessToken)
{
        $json_arr = array('error'=> "accessToken does not match");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}


if(!$gameInstanceID)
{
        $json_arr = array('error' =>"Inavlid Game Instance");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}
// ****** GET USERID OF FRND ******
$userIDQuery = "SELECT * from users where email = '$frndID'";
$userIDData = $conn->query($userIDQuery);
$userIDrow = $userIDData->fetch_assoc();
$frndID = $userIDrow['userID'];
$friendAccessToken = $userIDrow['accessToken'];
$friendFitBitToken = $userIDrow['fitbitID'];

// ********* GET ACTIVITY DETAILS FOR FRIEND *****************

        $opts2 = array(
                         'http'=>array(
                         'method'=>"GET",
                         'header'=>"Authorization: Bearer ".$friendAccessToken."\r\n"
                        ));
        $url3 = "https://api.fitbit.com/1/user/".$friendFitBitToken."/activities/date/".$todaysDate.".json";
        $context = stream_context_create($opts2);
        $data_user_activity = json_decode(file_get_contents($url3, false, $context), true);
        $data_user_summary = $data_user_activity['summary'];
        $friendStartSteps = $data_user_summary['steps'];
        $friendStartActiveTime = $data_user_summary['veryActiveMinutes']+$data_user_summary['fairlyActiveMinutes'];


// ************ GET NUMBER OF PLAYERS (NON QUIT) IN THE GAME INSTANCE ***********

$groupIDQuery = "SELECT * FROM gameInstance WHERE gameInstanceID = '$gameInstanceID'";
$groupIDData = $conn->query($groupIDQuery);
$groupIDrow = $groupIDData->fetch_assoc();
$groupID = $groupIDrow['groupID'];
$ownerID = $groupIDrow['ownerUserID'];
$gameStatus = $groupIDrow['gameStatus'];
$gameID = $groupIDrow['gameID'];
if($gameStatus == 'Quit')
{
	$json_arr = array('error' =>"Game Not Active");
	$json_data = json_encode($json_arr);
        echo $json_data;
        return;

}
if(!($ownerID==$selfID))
{
	$json_arr = array('error' => "Only game Owner can Delete Players");
	$json_data = json_encode($json_arr);
	echo $json_data;
	return;
}
$playerQuery = "SELECT * FROM userGroups WHERE groupID='$groupID'";
$playersData = $conn->query($playerQuery);
$noPlayers = 0;
$isFrndinGroup=false;
while($playersrow= $playersData->fetch_assoc())
{
	
	$playerID = $playersrow['userID'];
	// **** GET USER GAME STATUS FOR THIS PLAYER
	$userGameStatusQuery = "SELECT * FROM userGameData where userID='$playerID' AND gameInstanceID ='$gameInstanceID' ORDER BY userGameDataID DESC LIMIT 1";
	$userGameStatusData = $conn->query($userGameStatusQuery);
	$userGameStatusrow = $userGameStatusData->fetch_assoc();
	$userGameStatus = $userGameStatusrow['userGameStatus'];
	if($userGameStatus !='Quit')
	{
		if($playerID==$frndID)
		{
			$isFrndinGroup=true;
		}
	 
		$noPlayers=$noPlayers+1;
	}
}

if(!$isFrndinGroup)
{
	$json_arr = array('error' => "Player Not Found in group or Player Already Quit");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}
if($noPlayers>2)
{
	// **** UPDATE ENTRIES FOR MORE THAN TWO PLAYERS IN THE GROUP
	$updateUserGameStatusQuery = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected,startSteps, startActiveTime, gameInstanceID, userGameStatus) VALUES ('$frndID', '$gameID', '$currDate' , '0','0','$friendStartSteps','$friendStartActiveTime','$gameInstanceID', 'Quit')";
	//$updateUserGameStatusQuery = "UPDATE userGameData SET userGameStatus='Quit' where userID='$frndID' AND gameInstanceID ='$gameInstanceID'";
        if($conn->query($updateUserGameStatusQuery))
	{
		$json_arr = array("success"=>"Player Removed From Game Instance");
		$json_data = json_encode($json_arr);
		echo $json_data;

	}
}
else
{
	//*****  CHANGE GAME STATUS TO QUIT AND UPDATE USER GAME DATA
	$updateUserGameStatusQuery = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected,startSteps, startActiveTime, gameInstanceID, userGameStatus) VALUES ('$frndID', '$gameID', '$currDate' , '0','0','$friendStartSteps','$friendStartActiveTime','$gameInstanceID', 'Quit')";
	//$updateUserGameStatusQuery = "UPDATE userGameData SET userGameStatus='Quit' where userID='$frndID' AND gameInstanceID ='$gameInstanceID'";
	if($conn->query($updateUserGameStatusQuery))
	{
		$json_arr = array("success"=>"Player Removed From Game Instance");
                $json_data = json_encode($json_arr);
                echo $json_data;
	}
	$updateGameInstanceQuery = "UPDATE gameInstance SET gameStatus='Quit' where gameInstanceID ='$gameInstanceID'";
	$updateGameInstanceData = $conn->query($updateGameInstanceQuery);


}

?>
