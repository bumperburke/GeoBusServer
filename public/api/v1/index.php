<?php

require_once '../include/DbOps.php';
require_once '../include/Conn.php';
require '../libs/slim/Slim/Slim.php';


\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$app->get('/', function() use($app){
    echo "Home - My Slim Application";
});

$app->get('/getUsers', function(){
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
});


$app->post('/register', function() use($app){
	echo '<h1>Why Wont You Work</h1>';
	/*$response = array();
	
	$name = $app->request->post('name');
	$splitName = explode(" ", $name);
	$forename = $splitName[0];
	$surname = $splitName[1];
	
	$dob = $app->request->post('dob');
	$splitDob = explode("T", $dob);
	$birthDate = $splitDob[0];
	
	$sex = $app->request->post('sex');
	$email = $app->request->post('emailNew');
	$pass = $app->request->post('password');
	
	$db = new DbOps();
	$result = $db->registerUser($forename, $surname, $birthDate, $sex, $email, $pass);
	
	if($result == "Success"){
		$response["error"] = false;
		$response["message"] = "Registration Successful";
		echoResponse(201, $response);
	} else if($result == "Fail"){
		$response["error"] = true;
		$response["message"] = "Sorry, Something's Gone Wrong";
		echoResponse(200, $response);
	} else if($result == "Duplicate User"){
		$response["error"] = true;
		$response["message"] = "Sorry, This User Already Exists";
		echoResponse(200, $response);
	}*/
});

function echoResponse($statusCode, $resp){
	$app = \Slim\Slim::getInstance();
	
	$app->status($statusCode);
	
	$app->contentType('application/json');
	
	echo json_encode($resp);
}

$app->run();
?>