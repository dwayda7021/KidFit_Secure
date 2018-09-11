<?php

/* addPlayerToGameInstance.php


*/

$gameInstanceID = $_GET['gameInstanceID'];
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


if($self==$frnd)
{
        $json_arr = array('error' =>"Cannot Add Self");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}

// ******** GET FRIEND DATA *******
$frndQuery = "SELECT * from users where email='$frnd'";
$frndData = $conn->query($frndQuery);
if($frndData->num_rows<1)
{
	// *********** Friend not in Databse, SEND email Invite


        $msg = "Hello ".$frnd.",\n Your friend $selfName would like to invite you to play kidFit Games. ";
        $msg = wordwrap($msg,70);
        mail($frnd,"Get Active with your friend,".$self.Name." with kidFit Games!" ,$msg);
        $insertUser = "INSERT INTO users ( fitbitID, email, pswrd, userName, createDate, accessToken, refreshToken, tokenExpDate, appAccessToken, appSalt, appTokenExpDate, userStatus, dataAccess, deviceRegistrationID) VALUES  ( 'null' ,'$frnd', 'null', '$frnd', '$currDate','null','null','$currDate','null', 'null','$currDate','InvitePending',false,'null')";
        if($conn->query($insertUser))
	{
		$frndID =$conn->insert_id;
	}
	$frndName = "";
	$friendStartSteps =0;
	$friendStartActiveTime=0;
}
else
{
	$frndrow = $frndData->fetch_assoc();
	$frndID=$frndrow['userID'];
	$frndName = $frndrow['userName'];
	$friendAccessToken = $frndrow['accessToken'];
	$friendFitBitToken = $frndrow['fitbitID'];


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
}


// GET GAME ID 
$gameIDQuery = "SELECT * from gameInstance WHERE gameInstanceID = '$gameInstanceID'";
$gameIDData = $conn->query($gameIDQuery);
$gameIDrow = $gameIDData->fetch_assoc();
$gameID = $gameIDrow['gameID'];
$groupID = $gameIDrow['groupID'];
$gameStatus = $gameIDrow['gameStatus'];
// ******* AVOID DUPLICATE ADDITION *******

$reAdding=false;
$duplicateQuery = "SELECT DISTINCT userID from userGameData where gameInstanceID = '$gameInstanceID'";
$duplicateQueryData = $conn->query($duplicateQuery);
while( $duplicateQueryrow= $duplicateQueryData->fetch_assoc())
{
	if($duplicateQueryrow['userID']==$frndID)
	{

		// TAKE THE LATEST ENTRY FOR THAT PLAYER FROM USER GAME DATA
		$duplicatefrndQuery = "SELECT * from userGameData where gameInstanceID = '$gameInstanceID' AND userID = '$frndID' ORDER BY userGameDataID DESC LIMIT 1";
		$duplicatefrndData = $conn->query($duplicatefrndQuery);
		$duplicatefrndrow = $duplicatefrndData->fetch_assoc();

		if($duplicatefrndrow['userGameStatus']=='Quit')
		{
			 $updateFrndStatus = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected, startSteps, startActiveTime, gameInstanceID, userGameStatus) VALUES ('$frndID','$gameID', '$currDate', '0','0','$friendStartSteps','$friendStartActiveTime', '$gameInstanceID', 'Invited')"; 
		 	//$updateFrndStatus = "UPDATE userGameData SET userGameStatus ='Invited' where gameInstanceID = '$gameInstanceID' AND userID = '$frndID'";
		 	$updateFrndData = $conn->query($updateFrndStatus);
			// ******** CHANGE GAME STATUS if number of players is 2 ***********
			if($gameStatus=='Quit')
			{
				$updateGameStatusQuery = "UPDATE gameInstance SET gameStatus='gameInvited' where gameInstanceID = '$gameInstanceID'";
				$updateGameStatusData = $conn->query($updateGameStatusQuery);
			}
			$reAdding=true;	
		}
		else
		{
			$json_arr = array('error' => "Duplicate Addition");
			$json_data = json_encode($json_arr);
			echo $json_data;
			return;
		}
	}

}




// ************ GET GROUP DATA ***********
$groupDataQuery = "SELECT * from groupTable where groupID ='$groupID'";
$groupData = $conn->query($groupDataQuery);
$groupDatarow = $groupData->fetch_assoc();
$groupName = $groupDatarow['groupName'];
$newGroupName = $groupName.$frndName[0];

// ****** UPDATE GROUP NAME ******
if(!$reAdding)
{
	$groupNameUpdate = "UPDATE groupTable SET groupName ='$newGroupName' where groupID = '$groupID'";
	$groupNameUpdateData = $conn->query($groupNameUpdate);
}

// ********* INSERT FRND IN USER GROUPS ***********
if(!$reAdding)
{
	$userGroupInsertQuery = "INSERT INTO userGroups (groupID, userID) VALUES ('$groupID','$frndID')";
	$userGroupInsert = $conn->query($userGroupInsertQuery);
}

// INSERT FRND IN USER GAME DATA ************
if(!$reAdding)
{
	$userGameDataInsertQuery = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected, startSteps, startActiveTime,  gameInstanceID, userGameStatus) VALUES ('$frndID','$gameID', '$currDate', '0','0','$friendStartSteps','$friendStartActiveTime','$gameInstanceID','Invited')";
	$userGameDataInsert = $conn->query($userGameDataInsertQuery);
}
$json_arr = array('addPlayer' => "success");
$json_data = json_encode($json_arr);
echo $json_data;
return;



?>

