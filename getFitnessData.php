<?php
/*  getFitnessData.php
 INPUT -- EMAIL, ACCESS TOKEN
OUTPUT -- STEPS AND ACTIVE MINUTES */

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
$selfID=$loginrow['userID'];
$selfName = $loginrow['userName'];
if($act!= $accessToken)
{
        $json_arr = array('error'=> "accessToken does not match");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}


// *** GET CURRENT FITNESS DATA FOR SELF ****

$activeUserFitBitTokenQuery = "SELECT * FROM users WHERE userID = '$selfID'";
$activeUserFitBitTokenData = $conn->query($activeUserFitBitTokenQuery);
$activeUserFitBitTokenrow = $activeUserFitBitTokenData->fetch_assoc();
$activeUserFitBitToken = $activeUserFitBitTokenrow['fitbitID'];
$activeUserAccessToken = $activeUserFitBitTokenrow['accessToken'];
$expTime = $activeUserFitBitTokenrow['tokenExpDate'];
if($currDate>$expTime)
{
	// ************* If Tokens EXPIRED ***********************";
	$RF_token = $activeUserFitBitTokenrow['refreshToken'];
	$data = array ('grant_type' => 'refresh_token','refresh_token' => $RF_token, 'expires_in' => 3700 );
	$data = http_build_query($data);
	$opt_refresh = array(
		'http'=>array(
		'method'=>"POST",
		'header'=>"Authorization: Basic $encoding\r\n" .
		"Content-Type: application/x-www-form-urlencoded\r\n" .
		"Content-Length: " . strlen($data) . "\r\n" ,
		'content' => $data
		));
	$context = stream_context_create($opt_refresh);
	$data_authentication = json_decode(file_get_contents($url, false, $context), true);
	$seconds_to_expire = $data_authentication['expires_in'];
	$minutes_to_expire = $seconds_to_expire/60;
	$duration = "+".$minutes_to_expire." minutes";
	$expiryTime = date("Y/m/d H:i:s", strtotime($duration));
	$activeUserAccessToken = $data_authentication['access_token'];
	$RF_token = $data_authentication['refresh_token'];
	$update = "UPDATE users SET accessToken = '$activeUserAccessToken', refreshToken = '$RF_token', tokenExpDate = '$expiryTime' where email= '$self'";
	$conn->query($update);
	
}	
$url2 = "https://api.fitbit.com/1/user/".$activeUserFitBitToken."/profile.json";
$url3 = "https://api.fitbit.com/1/user/".$activeUserFitBitToken."/activities/date/".$todaysDate.".json";

$opts2 = array(
                         'http'=>array(
                         'method'=>"GET",
                         'header'=>"Authorization: Bearer ".$activeUserAccessToken."\r\n"
                        ));
$context = stream_context_create($opts2);
$file_contents = file_get_contents($url2,false,$context);
$data_user_activity = json_decode(file_get_contents($url3, false, $context), true);
$data_user_summary = $data_user_activity['summary'];
if($data_user_summary['steps']){
        $activeTime = $data_user_summary['fairlyActiveMinutes']+$data_user_summary['veryActiveMinutes'];
        $currentSteps = $data_user_summary['steps'];}


$json_arr = array('steps'=>$currentSteps, 'activityTime' => $activeTime);
$json_data = json_encode($json_arr);
echo $json_data;
?> 
