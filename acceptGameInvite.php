
 <?php
/* acceptGameInvite.php 
INPUT -- EMAIL, ACCESS TOKEN, GAME INSTANCE ID  
OUTPUT -- userGameStatus'=>"updated"
*/

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

if($act!= $accessToken||!$accessToken)
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



// ******** UPDATE USER GAME STATUS IN userGameData *****

$userGameDataQuery = "SELECT * from userGameData where gameInstanceID = '$gameInstanceID' AND userID='$selfID' ORDER BY userGameDataID DESC LIMIT 1";
$userGameData = $conn->query($userGameDataQuery);
if($userGameData->num_rows >0)
{
	$userGameDatarow = $userGameData ->fetch_assoc();
	$gameID = $userGameDatarow['gameID'];
	$step=0;

	$updateUserGameStatus = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected, startSteps, startActiveTime, gameInstanceID, userGameStatus) VALUES ('$selfID', '$gameID', '$currDate','0','0','$selfStartSteps','$selfStartActiveTime','$gameInstanceID', 'ReadyToPlay')"; 
	//$updateUserGameStatus = "UPDATE userGameData SET userGameStatus= 'ReadyToPlay' where gameInstanceID = '$gameInstanceID' AND userID='$selfID'";
	//$updateUserGameStatusData = $conn->query($updateUserGameStatus);
	if($conn->query($updateUserGameStatus))
	{
		$json_arr = array('userGameStatus'=>"updated");
		$json_data = json_encode($json_arr);
		echo $json_data;
	}
}

// ** UPDATE GAME INSTANCE STATUS IF EVERYONE IS READY TO PLAY *****
// ***** GET GROUP ID *********

$groupIDQuery = "SELECT * from gameInstance WHERE gameInstanceID = '$gameInstanceID'";
$groupIDData = $conn->query($groupIDQuery);
$groupIDrow = $groupIDData->fetch_assoc();
$groupID = $groupIDrow['groupID'];

//  ******* GET ALL PLAYERS IN THAT GROUP ********
$gameStatus =0;
$playersQuery = "SELECT * FROM userGroups WHERE groupID = '$groupID'";
$playersData = $conn->query($playersQuery);
while($playersrow = $playersData->fetch_assoc())
{
        $userID = $playersrow['userID'];
	// *** GET GAME STATUS OF THIS USER 
	$userStatusQuery = "SELECT * from userGameData where gameInstanceID = '$gameInstanceID' AND userID='$userID' ORDER BY userGameDataID DESC LIMIT 1";
	$userStatusData = $conn->query($userStatusQuery);
	$userStatusrow = $userStatusData->fetch_assoc();
	$userStatus = $userStatusrow['userGameStatus'];
	if($userStatus == "Invited")
	{
		$gameStatus=1;
	}
}

if($gameStatus ==0)
{
	$updateGameInstanceQuery = "UPDATE gameInstance SET gameStatus='ReadyToPlay' where gameInstanceID = '$gameInstanceID'";
	$updateGameInstanc = $conn->query($updateGameInstanceQuery);
	
}

?>
