<?php
/* getActiveGames.php

INPUT	-- EMAIL, ACCESS TOKEN
OUTPUT	-- numberOfActiveGames IN inProgress OR ReadyToPlay status , gameInstance, STring with PlayersName, and status of each Game

*/

$self= $_GET['self'];
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

$selfID = $loginrow['userID'];
$selfName = $loginrow['userName'];
$numberOfActiveGames=0;
$json_arr = array();
//   *********** GETTING GAMES WITH STATUS READYTOPLAY OR IN PROGRESS *****************
$gameInstanceQuery = "SELECT DISTINCT gameInstanceID from userGameData where userID='$selfID'";
$gameInstanceData = $conn->query($gameInstanceQuery);
while($gameInstancesrow = $gameInstanceData->fetch_assoc())
{
	$gameInstanceID = $gameInstancesrow['gameInstanceID'];
	$userStatusQuery = "SELECT * FROM userGameData where userID='$selfID' AND gameInstanceID = '$gameInstanceID' ORDER BY userGameDataID DESC LIMIT 1";
	$userStatusData = $conn->query($userStatusQuery);
	$userStatusrow = $userStatusData->fetch_assoc();
	$userStatus = $userStatusrow['userGameStatus'];
	if($userStatus=='inProgress' || $userStatus=='ReadyToPlay'||$userStatus=='inProgress/hotPotato')
	{
		// *** CheCK GAMe STATUS
		$gameStatusQuery = "SELECT * from gameInstance where gameInstanceID = '$gameInstanceID'";
		$gameStatusData = $conn->query($gameStatusQuery);
		$gameStatusrow = $gameStatusData->fetch_assoc();
		$gameStatus = $gameStatusrow['gameStatus'];
		$groupID = $gameStatusrow['groupID'];
		if(($gameStatus=='ReadyToPlay')||($gameStatus=='inProgress'))
		{
			$numberOfActiveGames=$numberOfActiveGames+1;
			array_push($json_arr,$gameInstanceID);
			$playersQuery = "SELECT * FROM userGroups WHERE groupID='$groupID'";
                	$playersData = $conn->query($playersQuery);
                	$players ="";
                	while($playersrow = $playersData->fetch_assoc())
                	{
                        	$playerID = $playersrow['userID'];
                        	// ********* check if the player  has not QUIT
                        	$playerStatusQuery = "SELECT * FROM userGameData WHERE userID ='$playerID' AND gameInstanceID='$gameInstanceID' ORDER BY userGameDataID DESC LIMIT 1";
	
        	                $playerStatusData = $conn->query($playerStatusQuery);
        	                $playerStatusrow = $playerStatusData->fetch_assoc();
        	                if($playerStatusrow['userGameStatus']!='Quit')
                	        {
                        	        // *** GET USER NAME OF PLAYER
                        	        $userNameQuery = "SELECT * FROM users WHERE userID = '$playerID'";
                        	        $userNameData = $conn->query($userNameQuery);
                        	        $userNamerow = $userNameData->fetch_assoc();
                               	 	$userName = $userNamerow['userName'];
                                	if(!($userName==$selfName))
                                	{
                                        	$players = $players.$userName.",";
                                	}
                        	}
                	}
               	 	$players = substr($players, 0, -1);
                	array_push($json_arr, $players);
                	array_push($json_arr, $gameStatus);
		}
	}


}









/*



$gameInstancesQuery = "SELECT * from userGameData where userID='$selfID' AND (userGameStatus ='inProgress' OR userGameStatus ='ReadyToPlay')";
$gameInstances =$conn->query($gameInstancesQuery);
$numberOfActiveGames=0;
$json_arr = array();
while($gameInstancesrow = $gameInstances->fetch_assoc())
{
        $gameInstanceID = $gameInstancesrow['gameInstanceID'];
        $userGameStatus = $gameInstancesrow['userGameStatus'];
        $gameStatusQuery = "SELECT * FROM gameInstance where gameInstanceID = '$gameInstanceID'";
        $gameStatusData = $conn->query($gameStatusQuery);
        $gameStatusrow = $gameStatusData->fetch_assoc();
        $gameStatus = $gameStatusrow['gameStatus'];
	if(($gameStatus=='ReadyToPlay')||($gameStatus=='inProgress'))
	{
		$numberOfActiveGames=$numberOfActiveGames+1;
		array_push($json_arr,$gameInstanceID);
		// ***** GET PLAYER NAMES(NOT QUIT) IN THE GAME INSTANCE ID *************
                $groupID = $gameStatusrow['groupID'];
                $playersQuery = "SELECT * FROM userGroups WHERE groupID='$groupID'";
                $playersData = $conn->query($playersQuery);
                $players ="";
                while($playersrow = $playersData->fetch_assoc())
                {
                        $playerID = $playersrow['userID'];
                        // ********* check if the player  has not QUIT                          
                        $playerStatusQuery = "SELECT * FROM userGameData WHERE userID ='$playerID' AND gameInstanceID='$gameInstanceID'";
                        $playerStatusData = $conn->query($playerStatusQuery);
                        $playerStatusrow = $playerStatusData->fetch_assoc();
                        if($playerStatusrow['userGameStatus']!='Quit')
                        {
                                // *** GET USER NAME OF PLAYER
                                $userNameQuery = "SELECT * FROM users WHERE userID = '$playerID'";
                                $userNameData = $conn->query($userNameQuery);
                                $userNamerow = $userNameData->fetch_assoc();
                                $userName = $userNamerow['userName'];
                                if(!($userName==$selfName))
                                {
                                        $players = $players.$userName.",";
                                }
                        }
                }
		$players = substr($players, 0, -1);
		array_push($json_arr, $players);
		array_push($json_arr, $gameStatus);
	}
}
*/
$json_arr['numberOfActiveGames'] = $numberOfActiveGames;
$json_data = json_encode($json_arr);
echo $json_data;

?>
		


