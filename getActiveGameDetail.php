<?php
/*  getActiveGameDetail.php
 INPUT -- EMAIL, ACCESS TOKEN, GAME INSTANCE ID AND GAME ID
OUTPUT -- {"timeRemaining":-154167,"currentUser":"1","stageInterval":2000,"self":true,"currentSteps":6869,"StartSteps":0000} AND ACTIVE USER NAME  */

$self= $_GET['self'];
$accessToken = $_GET['accessToken'];
$gameInstanceID = $_GET['gameInstanceID'];
$gameID = $_GET['gameID'];


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
$selfID=$loginrow['userID'];
$selfName = $loginrow['userName'];
if($act!= $accessToken)
{
        $json_arr = array('error'=> "accessToken does not match");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}

if(!$gameInstanceID)
{
        $json_arr = array('error'=>"No Such Game Found! Please refresh");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;

}


//  ***** GET GAME STATUS  ***********
$groupIDQuery = "SELECT * from gameInstance where gameInstanceID= '$gameInstanceID'";
$groupIDData =$conn->query($groupIDQuery);
$groupIDrow = $groupIDData->fetch_assoc();
$groupID = $groupIDrow['groupID'];
$gameStatus = $groupIDrow['gameStatus'];
$activeUserID = $groupIDrow['activeUser'];
$endDate = strtotime($groupIDrow['endDate']);
$startDateTime = strtotime("now");
$timeRemaining = $endDate-$startDateTime;
if($timeRemaining<=0)
{
	$json_arr = array('error'=> "Game Over");
	$json_data = json_encode($json_arr);
	echo $json_data;
	return;
}

if(!($gameStatus == 'inProgress'))
{
        $json_arr = array('error'=> "Not an Active Game");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}


// ****** ACTIVE USER NAME *************
$activeUsernameQuery = "SELECT * FROM users WHERE userID='$activeUserID'";
$activeUsernameData = $conn->query($activeUsernameQuery);
$activeUsernamerow = $activeUsernameData->fetch_assoc();
$activeUserName = $activeUsernamerow['userName'];
$activeUserFitBitToken = $activeUsernamerow['fitbitID'];
$activeUserAccessToken = $activeUsernamerow['accessToken'];

 
// *** IF I AM THE ACTIVE USER *******
if($selfID == $activeUserID)
{
//OUTPUT -- {"timeRemaining":-154167,"currentUser":"1","stageInterval":2000,"self":true,"currentSteps":diffrence of current - start steps,"StartSteps":0000} ACTIVEUSERNAME 
$startStepsTimeQuery = "Select * from userGameData where userID= '$selfID' AND gameInstanceID = '$gameInstanceID' AND userGameStatus = 'inProgress/hotPotato'";
$startStepsTimeData = $conn->query($startStepsTimeQuery);
$startStepsTimerow = $startStepsTimeData->fetch_assoc();
$startStepsTime = $startStepsTimerow['createDate'];
$startDate = date('Y-m-d',strtotime($startStepsTime));
$startTime = date('H:i:s',strtotime($startStepsTime));
echo $startTime;

// ********  NOW GET STEPS AT THIS TIME *******



}



// *** GET CURRENT FITNESS DATA FOR ACTIVE USER ****

$activeUserFitBitTokenQuery = "SELECT * FROM users WHERE userID = '$activeUserID'";
$activeUserFitBitTokenData = $conn->query($activeUserFitBitTokenQuery);
$activeUserFitBitTokenrow = $activeUserFitBitTokenData->fetch_assoc();
$activeUserFitBitToken = $activeUserFitBitTokenrow['fitbitID'];
$activeUserAccessToken = $activeUserFitBitTokenrow['accessToken'];
$url = "https://api.fitbit.com/1/user/".$activeUserFitBitToken."/activities/date/".$todaysDate.".json";
$opts2 = array(
                         'http'=>array(
                         'method'=>"GET",
                         'header'=>"Authorization: Bearer ".$activeUserAccessToken."\r\n"
                        ));
$context = stream_context_create($opts2);
$data_user_activity = json_decode(file_get_contents($url, false, $context), true);
$data_user_summary = $data_user_activity['summary'];
if($data_user_summary['steps']){
        $currentSteps = $data_user_summary['steps'];}
else
        {$currentSteps=0;}


$endDate = strtotime($groupIDrow['endDate']);
$startDate = strtotime("now");
//echo $endDate-$startDate;
$timeRemaining = $endDate-$startDate;

if($selfID == $activeUserID)
{
$data = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$timeRemainingi, 'currentUserName' => $activeUserName, 'currentUser' => $activeUserID, 'stageInterval'=>2000, 'startTime'=>$startTime, 'self' => true, currentSteps => $currentSteps);
$json_data = json_encode($data);
echo $json_data;


}
else
{
$data = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$timeRemaining, 'currentUserName' => $activeUserName, 'currentUser' => $activeUserID, 'stageInterval'=>2000, 'startTime'=>$startTime, 'self' => false);
$json_data = json_encode($data);
echo $json_data;
}
?>
