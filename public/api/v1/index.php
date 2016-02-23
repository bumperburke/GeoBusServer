<?php
/**
  * @author Stefan Burke
  * @author Stefan Burke <stefan.burke@mydit.ie>
  */
date_default_timezone_set('Europe/Dublin');

require_once '../include/DbOps.php';
require_once '../include/Conn.php';
require '../libs/slim/Slim/Slim.php';


\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$app->get('/', function(){
    echo "Home - My Slim Application";
});

$app->post('/login', function() use ($app){
	$response = array();

	$json = $app->request->getBody();
	$data = json_decode($json, true);

	$email = $data['email'];
	$pass = $data['password'];

	//error_log(print_r($email,true));
	//error_log(print_r($pass,true));

	$db = new DbOps();
	$result = $db->checkLoginCreds($email);
	if($result == null){
		$response["error"] = true;
		$response["message"] = "credFail";
		sendResp(200, $response);
	}
	else if($result != NULL){
		if(password_verify($pass, $result['password'])){
			$response["error"] = false;
			$response["user"] = $email;
			$response["token"] = bin2hex(openssl_random_pseudo_bytes(16));
			$response["message"] = "confirmed";

			$tokenExpire = date('Y-m-d H:i:s', strtotime('+1 hour'));
			$db->updateUserToken($email, $response["token"], $tokenExpire);
			sendResp(200, $response);
		}else{
			$response["error"] = true;
			$response["message"] = "credFail";
			sendResp(200, $response);
		}
	}
	
});

$app->post('/register', function() use ($app){
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

	$dob = $data['dob'];
	$splitDob = explode("T", $dob);
	$birthDate = $splitDob[0];
	
	$sex = $data['sex'];
	$email = $data['emailNew'];
	$pass = $data['password'];
	
	$options = ['cost' => 10,
			'salt' => mcrypt_create_iv(22, MCRYPT_DEV_URANDOM)];
	
	$password = password_hash($pass, PASSWORD_BCRYPT, $options);
	
	$db = new DbOps();
	$result = $db->registerUser($forename, $surname, $birthDate, $sex, $email, $password);
	
	if($result == "Success"){
		$response["error"] = false;
		$response["message"] = "success";
		sendResp(201, $response);
	} else if($result == "Fail"){
		$response["error"] = true;
		$response["message"] = "fail";
		sendResp(200, $response);
	} else if($result == "Duplicate User"){
		$response["error"] = true;
		$response["message"] = "duplicate";
		sendResp(200, $response);
	}
});

$app->post('/updateLocation', function() use ($app){
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

	$db = new DbOps();
	$result = $db->updateLocation($lat, $lon, $time, $deviceID);
	if($result == "success"){
		$response["error"] = false;
		$response["message"] = "success";
		sendResp(201, $response);
	}else{
		$response["error"] = true;
		$response["message"] = "fail";
		sendResp(200, $response);
	}
});

$app->get('/getLocation', function() use ($app){
	$response = array();

	$db = new DbOps();
	$result = $db->getLocation();

	$response["error"] = false;
	while($loc = $result->fetch_assoc()){
		$response["location"] = $loc['Ypos']. " " .$loc['Xpos'];
	}
	sendResp(200, $response);
});

/*function authenticate(\Slim\Route\ $route){
	$header = apache_request_headers();
	$response = array();
	$app = \Slim\Slim::getInstance();
	
	if(isset($header['Auth'])){
		$db = new DbOps();
		
		$token = $header['Auth'];
		if(!$db->tokenCheck($token)){
			$response["error"] = true;
			$response["message"] = "invalid token";
			sendResp(401, $response);
			$app->stop();
		}else if(db->hasTokenExpired($token)){
			$response["error"] = true;
			$response["message"] = "expired token";
			sendResp(401, $response);
			$app->stop();
		}else {
			$tokenExpire = date('Y-m-d H:i:s', strtotime('+1 hour'));
			$db->keepTokenAlive($token, $tokenExpire);
			$this->next->call();
		}
	}
}*/

function sendResp($respCode, $resp){
	$app = \Slim\Slim::getInstance();
	
	$app->status($respCode);
	
	$app->contentType('application/json');
	
	echo json_encode($resp);
}

$app->run();
?>
