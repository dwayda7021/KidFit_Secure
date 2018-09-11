<?php
$gameInstanceID = $_GET['gameInstanceID'];
$self= $_GET['self'];
$accessToken = $_GET['accessToken'];
$newGroupName = $_GET['newGroupName'];
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
        $json_arr = array('error' =>"Invalid Game Instance");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}

// **** GET GROUPID FROM GAME INSTANCE *********

$groupIDQuery = "SELECT * FROM gameInstance where gameInstanceID='$gameInstanceID'";
$groupIDData = $conn->query($groupIDQuery);
$groupIDrow = $groupIDData->fetch_assoc();
$groupID = $groupIDrow['groupID'];
$gameID = $groupIDrow['gameID'];

// ***** UPDATE GROUP Table **********
$groupNameUpdateQuery = "UPDATE groupTable SET groupName = '$newGroupName' WHERE groupID = '$groupID' AND gameID = '$gameID'";
$groupNameUpdateData = $conn->query($groupNameUpdateQuery);

$json_arr = array('update' =>'success', 'newGroupName' => $newGroupName);
$json_data = json_encode($json_arr);
echo $json_data;

?>


