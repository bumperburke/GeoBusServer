<?php
/**
  * @author Stefan Burke
  * @author Stefan Burke <stefan.burke@mydit.ie>
**/
date_default_timezone_set('Europe/Dublin');

require_once '../include/DbOps.php';
require_once '../include/DBManager.php';
require_once '../include/config.php';
require '../libs/slim/Slim/Slim.php';


\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}
// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

}

// instead of mapping:
$app->options('/(:x+)', function() use ($app) {
    //...return correct headers...
    $app->response->setStatus(200);
});

$app->post('/login', function() use ($app){
	$dbManager = new DBManager();
	$db = new DbOps($dbManager);
	$dbManager->openConn();

	$response = array();
	$json = $app->request->getBody();
	$data = json_decode($json, true);

	$email = $data['email'];
	$pass = $data['password'];

	$result = $db->checkLoginCreds($email);
	if($result == null){
		$response["error"] = true;
		$response["message"] = "credFail";
		sendResp(HTTPSTATUS_OK, $response);
	}
	else if($result != NULL){
		if(password_verify($pass, $result[0]['password'])){
			$response["error"] = false;
			$response["user"] = $email;
			$response["token"] = bin2hex(openssl_random_pseudo_bytes(16));
			$response["message"] = "confirmed";

			$tokenExpire = date('Y-m-d H:i:s', strtotime('+1 hour'));
			$db->updateUserToken($email, $response["token"], $tokenExpire);
			sendResp(HTTPSTATUS_OK, $response);
		}else{
			$response["error"] = true;
			$response["message"] = "credFail";
			sendResp(HTTPSTATUS_OK, $response);
		}
	}

});

$app->post('/register', function() use ($app){
	$dbManager = new DBManager();
    $db = new DbOps($dbManager);
    $dbManager->openConn();

    $response = array();
    $json = $app->request->getBody();
    $data = json_decode($json, true);

    $name = $data['name'];
    if(strpos($name, ' ') !== FALSE)
    {
        $splitName = explode(" ", $name);
        $forename = $splitName[0];
        $surname = $splitName[1];
    }else{
    	$forename = $name;
        $surname = "";
    }

    $age = $data['age'];
    $sex = $data['sex'];
    $email = $data['emailNew'];
    $pass = $data['pass'];

    $options = ['cost' => 10, 'salt' => mcrypt_create_iv(22, MCRYPT_DEV_URANDOM)];

    $password = password_hash($pass, PASSWORD_BCRYPT, $options);

    $result = $db->registerUser($forename, $surname, $age, $sex, $email, $password);

    if($result == "Success"){
    	$response["error"] = false;
        $response["message"] = "success";
        sendResp(HTTPSTATUS_CREATED, $response);
	} else if($result == "Duplicate User"){
		$response["error"] = true;
		$response["message"] = "duplicate";
		sendResp(HTTPSTATUS_OK, $response);
	}
	$dbManager->closeConn();
});

$app->post('/updateLocation', function() use ($app){
	$dbManager = new DBManager();
	$db = new DbOps($dbManager);
	$dbManager->openConn();

	$response = array();
	$json = $app->request->getBody();
	$data = json_decode($json, true);

	$lat = $data['latitude'];
	$lon = $data['longitude'];

	$dateTime = $data['time'];
	$parseTime = explode("T", $dateTime);
	$parseTime2 = explode(".", $parseTime[1]);
	$time = $parseTime[0]. " " .$parseTime2[0];

	$deviceID = $data['deviceID'];
	$routeID = $data['routeID'];

	$result = $db->updateLocation($lat, $lon, $time, $deviceID, $routeID);
	if($result == "success"){
		$response["error"] = false;
		$response["message"] = "success";
		sendResp(HTTPSTATUS_CREATED, $response);
	}else{
		$response["error"] = true;
		$response["message"] = "fail";
		sendResp(HTTPSTATUS_OK, $response);
	}
	$dbManager->closeConn();
});

$app->post('/updateRouteMap', function() use ($app){
	$dbManager = new DBManager();
	$db = new DbOps($dbManager);
	$dbManager->openConn();

	$response = array();
	$json = $app->request->getBody();
	$data = json_decode($json, true);

	$lat = $data['latitude'];
	$lon = $data['longitude'];

	$result = $db->updateRouteMap($lat, $lon);
	if($result == "success"){
		$response["error"] = false;
		$response["message"] = "success";
		sendResp(HTTPSTATUS_CREATED, $response);
	}else{
		$response["error"] = true;
		$response["message"] = "fail";
		sendResp(HTTPSTATUS_OK, $response);
	}
	$dbManager->closeConn();
});

$app->get('/getLocation/:route', function($route) use ($app){
	$headerCheck = headerCheck();
	if($headerCheck == "invalid" || $headerCheck == "expired"){
		$response["error"] = true;
		$response["message"] = "invalid/expired token";
		sendResp(HTTPSTATUS_OK, $response);
	}else{
		$dbManager = new DBManager();
    		$db = new DbOps($dbManager);
    		$dbManager->openConn();
		var_dump($headers);

    		$response = array();
		$result = $db->getLocation($route);
		$response["error"] = false;

		if($result == false){
			$response["data"] = "No Data Available";
		}else{
			$response["data"] = $result;
		}

		sendResp(HTTPSTATUS_OK, $response);
		$dbManager->closeConn();
	}
});

$app->get('/getRouteMap/:route', function($route) use ($app){
	$headerCheck = headerCheck();

	if($headerCheck == "invalid" || $headerCheck == "expired"){
		$response = "invalid/expired token";
		sendResp(HTTPSTATUS_OK, $response);
	}else{
		$dbManager = new DBManager();
		$db = new DbOps($dbManager);
		$dbManager->openConn();

		$response = array();
		$result = $db->getRoute($route);

		$response["error"] = false;
		$response["data"] = array('route' => $result);

		sendResp(HTTPSTATUS_OK, $response);
		$dbManager->closeConn();
	}
});

$app->get('/getStops/:route', function($route) use ($app){
	/*$headerCheck = headerCheck();
	if($headerCheck == "invalid" || $headerCheck == "expired"){
		$response = "invalid/expired token";
		sendResp(HTTPSTATUS_OK, $response);
	}else{*/
		$dbManager = new DBManager();
    		$db = new DbOps($dbManager);
    		$dbManager->openConn();

		$response = array();
		$result = $db->getStops($route);

		$response["error"] = false;

		$response["data"] = array('stops' => $result);
		sendResp(HTTPSTATUS_OK, $response);
		$dbManager->closeConn();
	//}
});

$app->get('/getTimetable/:name', function($timetable) use ($app){
	$dbManager = new DBManager();
	$db = new DbOps($dbManager);
	$dbManager->openConn();

	$response = array();
	$result = $db->getTimetable($timetable);

	$response["error"] = false;
	$response["data"] = array('timetable' => $result);
	sendResp(HTTPSTATUS_OK, $response);
	$dbManager->closeConn();
});

function headerCheck(){
	$headers = apache_request_headers();

	if(!isset($headers["Auth"]) && $headers["Auth"] == false){
		return "invalid";
	}
	else{
		$dbManager = new DBManager();
    		$db = new DbOps($dbManager);
    		$dbManager->openConn();

		$token = $headers['Auth'];
		if(!$db->tokenCheck($token)){
			return  "invalid";
		}
		else{
			if($db->hasTokenExpired($token)){
				return "expired";
			}else {
				$tokenExpire = date('Y-m-d H:i:s', strtotime('+1 hour'));
				$db->keepTokenAlive($token, $tokenExpire);
				return "updated";
			}
		}

		$dbManager->closeConn();
	}
}

function sendResp($respCode, $resp){
	$app = \Slim\Slim::getInstance();

	$app->status($respCode);

	$app->contentType('application/json');

	echo json_encode($resp);
}

$app->run();
?>
