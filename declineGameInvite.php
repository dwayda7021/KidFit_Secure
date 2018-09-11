<?php

$self= $_GET['self'];
$accessToken = $_GET['accessToken'];
$gameInstanceID = $_GET['gameInstanceID'];


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

if($act!= $accessToken)
{
        $json_arr = array('error'=> "accessToken does not match");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}


$selfID = $loginrow['userID'];
$selfName = $loginrow['userName'];
$selfAccessToken = $loginrow['accessToken'];
$selfFitbitToken = $loginrow['fitbitID'];


// ********* GET ACTIVITY DETAILS FOR SELF *********
        $opts2 = array(
                         'http'=>array(
                         'method'=>"GET",
                         'header'=>"Authorization: Bearer ".$selfAccessToken."\r\n"
                        ));
        $url3 = "https://api.fitbit.com/1/user/".$selfFitbitToken."/activities/date/".$todaysDate.".json";
        $context = stream_context_create($opts2);
        $data_user_activity = json_decode(file_get_contents($url3, false, $context), true);
        $data_user_summary = $data_user_activity['summary'];
        $selfStartSteps = $data_user_summary['steps'] ;
        $selfStartActiveTime = $data_user_summary['veryActiveMinutes']+$data_user_summary['fairlyActiveMinutes'];



// **** GET GROUP ID ******

$groupIDQuery = "SELECT * FROM gameInstance where gameInstanceID = '$gameInstanceID'";
$groupIDData = $conn->query($groupIDQuery);
$groupIDrow = $groupIDData->fetch_assoc();
$groupID = $groupIDrow['groupID'];
$gameID = $groupIDrow['gameID'];

// ********** UPDATE user Entry in USERGAMEDATA ********
$updateUserGameDataQuery = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, startSteps, startActiveTime, activeTimeCollected, gameInstanceID, userGameStatus) VALUES ('$selfID' ,  '$gameID' , '$currDate', '0', '0','$selfStartSteps','$selfStartActiveTime', '$gameInstanceID', 'Quit')";
//$updateUserGameDataQuery = "UPDATE userGameData SET userGameStatus='Quit' where userID = '$selfID' and gameInstanceID = '$gameInstanceID'";
$updateUserGameData= $conn->query($updateUserGameDataQuery);

// ********** UPDATE gameInstance Table ********
	
	// **** No. of users in that particular Group
$userNum=0;
$userNumQuery = "SELECT * FROM userGroups where groupID='$groupID'";
$userNumData = $conn->query($userNumQuery);
while($userNumrow = $userNumData->fetch_assoc())
{
	$playerID = $userNumrow['userID'];
	//  *** GET USER STATUS
	$userStatusQuery = "SELECT * FROM userGameData where userID ='$playerID' AND gameInstanceID = '$gameInstanceID' ORDER by userGameDataID DESC LIMIT 1";
	$userStatusData = $conn->query($userStatusQuery);
	$userStatusrow = $userStatusData->fetch_assoc();
	$userStatus = $userStatusrow['userGameStatus'];
	if($userStatus!='Quit')
	{
		$userNum=$userNum+1;
	}
}
if($userNum<=1)
{
	// ********* SET GAME INSTANCE STATUS TO QUIT **********
	$status = 'Quit';
	$updateGameStatusQuery = "UPDATE gameInstance SET gameStatus = '$status' where gameInstanceID = '$gameInstanceID'";
	$updateGameStatusData = $conn->query($updateGameStatusQuery);
}
else
{


}
$json_arr= array("success" =>"decline Invite");
$json_data = json_encode($json_arr);
echo $json_data;

?>
