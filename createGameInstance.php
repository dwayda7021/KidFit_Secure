 <?php
/* createGameInstance.php
INPUT -- EMAIL, ACCESS TOKEN, GAMEID, FRIEND EMAIL
OUTPUT -- 'create'=>"success",'gameInstanceID'=>$gameInstanceID

*/

$gameID = $_GET['gameID'];
$self= $_GET['self'];
$frnd = $_GET['friend'];
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
$selfName = $loginrow['userName'];
$selfID = $loginrow['userID'];
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



if($act!= $accessToken||!$accessToken)
{
	$json_arr = array('error'=> "accessToken does not match");
	$json_data = json_encode($json_arr);
	echo $json_data;
	return;
}


if(!$gameID)
{
	$json_arr = array('error' =>"Inavlid Game ID");
	$json_data = json_encode($json_arr);
	echo $json_data;
	return;
}



if($self==$frnd)
{
	$json_arr = array('error' =>"Cannot Add Self");
	$json_data = json_encode($json_arr);
        echo $json_data;
        return;
}

$searchFriend = "SELECT * from users WHERE email ='$frnd'";
$frndData = $conn->query($searchFriend);
if($frndData->num_rows>0)
{
	//  ********** Friend Found in DataBase *****************
	$frndrow = $frndData->fetch_assoc();
	$friendID = $frndrow['userID'];
	$friendAccessToken = $frndrow['accessToken'];
	$friendFitBitToken = $frndrow['fitbitID'];	
	$groupName = $selfName[0].$frndrow['userName'][0];
	$groupInsert ="INSERT INTO groupTable (groupName, gameID) VALUES ('$groupName', '$gameID')";
	if($conn->query($groupInsert))
	{
		$groupID= $conn->insert_id;
	}
	
	// **** Insert DATA into userGroups Table *******

	$userGroupInsert = "INSERT INTO userGroups (groupID, userID) VALUES ('$groupID','$selfID')";
	$userGroupData = $conn->query($userGroupInsert);
	$userGroupInsert = "INSERT INTO userGroups (groupID, userID) VALUES ('$groupID','$friendID')";
        $userGroupData = $conn->query($userGroupInsert); 
	

	// ******* Create Game Instance  ************
	
	$gameInstanceInsert = "INSERT INTO gameInstance (createDate, gameID,groupID, endVaue, endDate, gameStatus, stageInterval, activeUser, ownerUserID) VALUES ('$currDate', '$gameID','$groupID','0','$currDate', 'gameInvited', '0', '0','$selfID')";	
	if($conn->query($gameInstanceInsert))
	{
		$gameInstanceID=$conn->insert_id;
	}
	

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



	$userGameDataInsert = "INSERT INTO userGameData (userID, gameID,createDate,stepsCollected, activeTimeCollected, startSteps, startActiveTime,gameInstanceID, userGameStatus) VALUES ('$selfID','$gameID','$currDate','0','0','$selfStartSteps','$selfStartActiveTime','$gameInstanceID','ReadyToPlay')";
	$userGameData = $conn->query($userGameDataInsert);
	$userGameDataInsert = "INSERT INTO userGameData (userID, gameID,createDate,stepsCollected, activeTimeCollected, startSteps, startActiveTime, gameInstanceID, userGameStatus) VALUES ('$friendID','$gameID','$currDate','0','0','$friendStartSteps','$friendStartActiveTime','$gameInstanceID','Invited')";
        $userGameData = $conn->query($userGameDataInsert);

	$json_arr = array('create'=>"success",'gameInstanceID'=>$gameInstanceID);
	$json_data = json_encode($json_arr);
	echo $json_data;
}
else
{
	// *********** Friend not in Databse, SEND email Invite
	$msg = "Hello ".$frnd.",\n Your friend $selfName would like to invite you to play kidFit Games. ";
	$msg = wordwrap($msg,70);
	mail($frnd,"Get Active with your friend,".$self.Name." with kidFit Games!" ,$msg);
	$insertUser = "INSERT INTO users ( fitbitID, email, pswrd, userName, createDate, accessToken, refreshToken, tokenExpDate, appAccessToken, appSalt, appTokenExpDate, userStatus, dataAccess, deviceRegistrationID) VALUES  ( 'null' ,'$frnd', 'null', '$frnd', '$currDate','null','null','$currDate','null', 'null','$currDate','InvitePending',false, 'null')";
	if($conn->query($insertUser))
	{ 
		$friendID =$conn->insert_id;
		$searchSelf = "SELECT * from users WHERE email ='$self'";
		$selfData = $conn->query($searchSelf);
		$selfrow = $selfData->fetch_assoc();
		$selfID = $selfrow['userID'];
		$groupName = $selfrow['userName'][0];
		
		// ********* INSERT INTO groupTable *********
		$groupInsert ="INSERT INTO groupTable (groupName, gameID) VALUES ('$groupName', '$gameID')";
		if($conn->query($groupInsert))
		{
			$groupID= $conn->insert_id;
		}
	

		 // **** Insert DATA into userGroups Table *******
	
		$userGroupInsert = "INSERT INTO userGroups (groupID, userID) VALUES ('$groupID','$selfID')";
		$userGroupData = $conn->query($userGroupInsert);
		$userGroupInsert = "INSERT INTO userGroups (groupID, userID) VALUES ('$groupID','$friendID')";
		$userGroupData = $conn->query($userGroupInsert);

		// ******* Create Game Instance  ************

		$gameInstanceInsert = "INSERT INTO gameInstance (createDate, gameID,groupID, endVaue, endDate, gameStatus, stageInterval, activeUser,ownerUserID) VALUES ('$currDate', '$gameID','$groupID','0','$currDate', 'gameInvited', '0', '0','$selfID')";
		if($conn->query($gameInstanceInsert))
		{
			$gameInstanceID=$conn->insert_id;
		}


		// **** INSERT DATA INTO USER GAME DATA **************		
		$userGameDataInsert = "INSERT INTO userGameData (userID, gameID,createDate,stepsCollected, activeTimeCollected, startSteps, startActiveTime, gameInstanceID, userGameStatus) VALUES ('$selfID','$gameID','$currDate','0','0','$selfStartSteps','$selfStartActiveTime','$gameInstanceID','ReadyToPlay')";
		 $userGameData = $conn->query($userGameDataInsert);
		$userGameDataInsert = "INSERT INTO userGameData (userID, gameID,createDate,stepsCollected, activeTimeCollected, startSteps, startActiveTime, gameInstanceID, userGameStatus) VALUES ('$friendID','$gameID','$currDate','0','0','0','0','$gameInstanceID','Invited')";
		$userGameData = $conn->query($userGameDataInsert);
	

		$json_arr = array('create'=>"success",'gameInstanceID'=>$gameInstanceID);
		$json_data = json_encode($json_arr);
		echo $json_data;
	}
		
}

?>
