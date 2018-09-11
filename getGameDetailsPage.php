 <?php
/* INPUT --- EMAIL, ACCESS TOKEN AND GAMEINSTANCE ID
OUTPUT -- groupName, NUMBEROFPLAYERS , OWNER OF GAME,GAMESTATUS AND USERNAME USERID AND GAMESTATUS OF EACH PLAYER */



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

// **** FIND GROUP NAME **********
$groupIDQuery = "SELECT * from gameInstance where gameInstanceID= '$gameInstanceID'";
$groupIDData =$conn->query($groupIDQuery);
$groupIDrow = $groupIDData->fetch_assoc();
$groupID = $groupIDrow['groupID'];
$gameStatus = $groupIDrow['gameStatus']; 
$ownerID = $groupIDrow['ownerUserID'];
$groupNameQuery = "SELECT * from groupTable where groupID='$groupID'";
$groupNameData = $conn->query($groupNameQuery);
$groupNamerow= $groupNameData->fetch_assoc();
$groupName =$groupNamerow['groupName'];

 

// **** FIND PLAYERS WHO DID NOT QUIT ********

$playersQuery = "SELECT * from userGroups WHERE groupID = '$groupID'";
$playersData = $conn->query($playersQuery);
$count =0;
$json_arr = array('groupName' => $groupName);
while($playersrow = $playersData->fetch_assoc())
{
	$userID = $playersrow['userID'];
	$userGameStatusQuery = "SELECT * from userGameData WHERE userID = '$userID' AND gameInstanceID= '$gameInstanceID' ORDER BY userGameDataID DESC LIMIT 1";
	$userGameStatusData = $conn->query($userGameStatusQuery);
	$userGameStatusrow = $userGameStatusData->fetch_assoc();
	$userGameStatus = $userGameStatusrow['userGameStatus'];
	if($userGameStatus!='Quit')
	{
		if(!($selfID ==$userID))
		{
			$count=$count+1;
			$userNameQuery = "SELECT userName from users where userID ='$userID'";
			$userNameData = $conn->query($userNameQuery);
			$userNamerow = $userNameData->fetch_assoc();
			$userName = $userNamerow['userName'];
			array_push($json_arr,$userName);
			array_push($json_arr,$userID);
			array_push($json_arr,$userGameStatus);
		}
	}	

}
$json_arr['numberOfPlayers'] = $count;
if($selfID==$ownerID)
{
	$json_arr['owner']='true';
}
else
{
	$json_arr['owner']='false';
}
$json_arr['gameStatus'] = $gameStatus;
$json_data = json_encode($json_arr);
echo $json_data;


?>







