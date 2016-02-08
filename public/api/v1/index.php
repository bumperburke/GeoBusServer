<?php

require_once '../include/DbOps.php';
require_once '../include/Conn.php';
require '../libs/slim/Slim/Slim.php';


\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$app->get('/', function(){
    echo "Home - My Slim Application";
});

/*$app->get('/getUsers', function(){
	$response = array();
	
	$db = new DbOps();
	
	$result = $db->getAllUsers();
	
	$response["error"] = false;
	$response["users"] = array();
	
	while($user = $result->fetch_assoc()){
		$tmp = array();
		$tmp["userID"] = $user["userID"];
		$tmp["forename"] = $user["forename"];
		$tmp["surname"] = $user["surname"];
		$tmp["dob"] = $user["birthDate"];
		$tmp["sex"] = $user["sex"];
		$tmp["email"] = $user["email"];
		$tmp["pass"] = $user["password"];
		array_push($response["users"], $tmp);
	}
	
	echoResponse(200, $response);
});*/


$app->post('/login', function() use ($app)){
	$response = array();
	
	$json = $app->request->getBody();
	$data = json_decode($json, true);
	
	$email = $data['email'];
	$pass = $data['password'];
	
	$db = new DbOps();
	$result = $db->checkLoginCreds($email);
	if($result == null){
		$response["error"] = true;
		$response["message"] = "Invalid Login Credentials!";
		echoResponse(200, $response);
	}
	else if($result != NULL){
		var_dump($result);
	}
	
}

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
				'salt' => mycrypt_create_iv(22, MCRYPT_DEV_URANDOM)];
	
	$password = password_hash($pass, PASSWORD_BCRYPT, $options);
	
	$db = new DbOps();
	$result = $db->registerUser($forename, $surname, $birthDate, $sex, $email, $password);
	
	if($result == "Success"){
		$response["error"] = false;
		$response["message"] = "success";
		echoResponse(201, $response);
	} else if($result == "Fail"){
		$response["error"] = true;
		$response["message"] = "fail";
		echoResponse(200, $response);
	} else if($result == "Duplicate User"){
		$response["error"] = true;
		$response["message"] = "duplicate";
		echoResponse(200, $response);
	}
});

function echoResponse($statusCode, $resp){
	$app = \Slim\Slim::getInstance();
	
	$app->status($statusCode);
	
	$app->contentType('application/json');
	
	echo json_encode($resp);
}

$app->run();
?>
