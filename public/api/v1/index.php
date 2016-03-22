<?php
/**
  * @author Stefan Burke
  * @author Stefan Burke <stefan.burke@mydit.ie>
 */

//set the default timezone
date_default_timezone_set('Europe/Dublin');

//Requires in order to include all seperate files needed.
require_once '../include/DbOps.php';
require_once '../include/DBManager.php';
require_once '../include/config.php';
require '../libs/slim/Slim/Slim.php';


\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

/*
 * The Following checks are to enable CORS (Cross Origin Resource Sharing) to allow connections from anywhere.
 */
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

// instead of mapping
$app->options('/(:x+)', function() use ($app) {
    //return correct headers
    $app->response->setStatus(200);
});

//Slim API route for '/login'
$app->post('/login', function() use ($app){
	$dbManager = new DBManager(); //create instance of DBManager class.
    $db = new DbOps($dbManager); //create instance of DbOps class passing in DBManager instance.
    $dbManager->openConn(); //call open connection from DBManager class.

	$response = array(); //create response array.
	$json = $app->request->getBody(); //get the body of the request.
	$data = json_decode($json, true); //decode the body

	$email = $data['email']; //assign the email from body to variable.
	$pass = $data['password']; //assign the password from body to variable.

	$result = $db->checkLoginCreds($email); //check the login credentials.
	if($result == null){ //if result is null
		$response["error"] = true; //set return error to true
		$response["message"] = "credFail"; //set response the message
		sendResp(HTTPSTATUS_OK, $response); //send the response
	}
	else if($result != NULL){ //else if it is not null
		if(password_verify($pass, $result[0]['password'])){ //if the password is verified
			$response["error"] = false; //set the response error to false
			$response["user"] = $email; //set the user response to the user email.
			$response["token"] = bin2hex(openssl_random_pseudo_bytes(16)); //create a token for user and return it.
			$response["message"] = "confirmed"; //return the confirmed response

			$tokenExpire = date('Y-m-d H:i:s', strtotime('+1 hour')); //create a token expirery date/time.
			$db->updateUserToken($email, $response["token"], $tokenExpire); //update the user table with token and expirery.
			sendResp(HTTPSTATUS_OK, $response); //send response
		}else{ //else if password is not verified.
			$response["error"] = true; //set the response error to true.
			$response["message"] = "credFail"; //set the message to credFail
			sendResp(HTTPSTATUS_OK, $response); //send reponse.
		}
	}

});

//Slim API route for '/register'
$app->post('/register', function() use ($app){
	$dbManager = new DBManager(); //create instance of DBManager class.
    $db = new DbOps($dbManager); //create instance of DbOps class passing in DBManager instance.
    $dbManager->openConn(); //call open connection from DBManager class.

    $response = array(); //create response array.
	$json = $app->request->getBody(); //get the body of the request.
	$data = json_decode($json, true); //decode the body

    $name = $data['name']; //asign the body name variable to name
    if(strpos($name, ' ') !== FALSE) //if there is a space in the name
    {
        $splitName = explode(" ", $name); //split the name variable on space
        $forename = $splitName[0]; //assign splitname array at first element to forename
        $surname = $splitName[1]; //assign splitname array at second element to surname
    }else{ //else
    	$forename = $name; //assign name to forename
        $surname = ""; //assign blank to surname
    }

    //Assign all body data to variables
    $age = $data['age'];
    $sex = $data['sex'];
    $email = $data['emailNew'];
    $pass = $data['pass'];

    $options = ['cost' => 10, 'salt' => mcrypt_create_iv(22, MCRYPT_DEV_URANDOM)]; //options array for salt of password

    $password = password_hash($pass, PASSWORD_BCRYPT, $options); //create a hash of the password and add the salt

    $result = $db->registerUser($forename, $surname, $age, $sex, $email, $password); //call register user in DbOps.

    if($result == "Success"){ //if result is success
    	$response["error"] = false; //set response error to false
        $response["message"] = "success"; //set response message to success.
        sendResp(HTTPSTATUS_CREATED, $response); //send response.
	} else if($result == "Duplicate User"){ //else if result is duplicate user
		$response["error"] = true; //set response error to true
		$response["message"] = "duplicate"; //set response message to duplicate
		sendResp(HTTPSTATUS_OK, $response); //send reponse
	}
	$dbManager->closeConn(); //close database connection
});

//Slim API route for '/updateLocation'
$app->post('/updateLocation', function() use ($app){
	$dbManager = new DBManager(); //create instance of DBManager class.
    $db = new DbOps($dbManager); //create instance of DbOps class passing in DBManager instance.
    $dbManager->openConn(); //call open connection from DBManager class.

	$response = array(); //create response array.
	$json = $app->request->getBody(); //get the body of the request.
	$data = json_decode($json, true); //decode the body

	$lat = $data['latitude']; //get latitude from body and assign to variable
	$lon = $data['longitude']; //get longititude from body and assign to variable

	$dateTime = $data['time']; //get time from body and assign to variable
	$parseTime = explode("T", $dateTime); //parse time on T
	$parseTime2 = explode(".", $parseTime[1]); //parse again on '.'
	$time = $parseTime[0]. " " .$parseTime2[0]; //create the time from two parsed items 

	$deviceID = $data['deviceID']; //get device id from body and assign to variable
	$routeID = $data['routeID']; //get route id from body and assign to variable

	$result = $db->updateLocation($lat, $lon, $time, $deviceID, $routeID); //call update location in DbOps
	if($result == "success"){ //if result is success
		$response["error"] = false; //set response error to false
		$response["message"] = "success"; //set response message to success
		sendResp(HTTPSTATUS_CREATED, $response); //send response
	}else{ //else
		$response["error"] = true; //set response error to true
		$response["message"] = "fail"; //set response message to fail
		sendResp(HTTPSTATUS_OK, $response); //send response
	}
	$dbManager->closeConn(); //close database connection
});

//Slim API route for 'updateRouteMap'
$app->post('/updateRouteMap', function() use ($app){
	$dbManager = new DBManager(); //create instance of DBManager class.
    $db = new DbOps($dbManager); //create instance of DbOps class passing in DBManager instance.
    $dbManager->openConn(); //call open connection from DBManager class.

	$response = array(); //create response array.
	$json = $app->request->getBody(); //get the body of the request.
	$data = json_decode($json, true); //decode the body

	$lat = $data['latitude']; //get latitude from body and assign to variable
	$lon = $data['longitude']; //get longitude from body and assign to variable

	$result = $db->updateRouteMap($lat, $lon); //call updateRouteMap from DbOps
	if($result == "success"){ //if result is success
		$response["error"] = false; //set response error to false
		$response["message"] = "success"; //set response message to success
		sendResp(HTTPSTATUS_CREATED, $response); //send response
	}else{
		$response["error"] = true; //set response error to true
		$response["message"] = "fail"; //set response message to fail
		sendResp(HTTPSTATUS_OK, $response); //send response
	}
	$dbManager->closeConn(); //close database connection
});

//SLim API for '/getLocation/:route'. URL takes a parameter.
$app->get('/getLocation/:route', function($route) use ($app){
	$headerCheck = headerCheck(); //call header check
	if($headerCheck == "invalid" || $headerCheck == "expired"){ //if header check is invalid or expired
		$response = "invalid/expired token"; //set response
		sendResp(HTTPSTATUS_OK, $response); //send response
	}else{
		$dbManager = new DBManager(); //create instance of DBManager class.
    	$db = new DbOps($dbManager); //create instance of DbOps class passing in DBManager instance.
    	$dbManager->openConn(); //call open connection from DBManager class.

    	$response = array(); //create response array
		$result = $db->getLocation($route); //call getLocation from DbOps
		$response["error"] = false; //set response error to false

		if($result == false){ //if result is false
			$response["data"] = "No Data Available"; //set respone data
		}else{ //else
			$response["data"] = $result; //set reponse data
		}

		sendResp(HTTPSTATUS_OK, $response); //send response
		$dbManager->closeConn(); //close database connection
	}
});

//Slim API '/getRouteMap/:route'. URL takes a parameter
$app->get('/getRouteMap/:route', function($route) use ($app){
	$headerCheck = headerCheck(); //call header check

	if($headerCheck == "invalid" || $headerCheck == "expired"){ //if header check is invalid or expired
		$response = "invalid/expired token"; //set response
		sendResp(HTTPSTATUS_OK, $response); //send response
	}else{
		$dbManager = new DBManager(); //create instance of DBManager class.
    	$db = new DbOps($dbManager); //create instance of DbOps class passing in DBManager instance.
    	$dbManager->openConn(); //call open connection from DBManager class.

		$response = array(); //create response array
		$result = $db->getRoute($route); //call getRoute from DbOps

		$response["error"] = false; //set response error to false
		$response["data"] = array('route' => $result); //set response data to an array of stops with data

		sendResp(HTTPSTATUS_OK, $response); //send response 
		$dbManager->closeConn(); //close database connection
	}
});

//Slim API route for '/getStops/:route'. URL takes a parameter
$app->get('/getStops/:route', function($route) use ($app){
	$headerCheck = headerCheck(); //call header check
	if($headerCheck == "invalid" || $headerCheck == "expired"){ //if heafercheck is invalid or expired
		$response = "invalid/expired token"; //set response
		sendResp(HTTPSTATUS_OK, $response); //send response
	}else{
		$dbManager = new DBManager(); //create instance of DBManager class.
    	$db = new DbOps($dbManager); //create instance of DbOps class passing in DBManager instance.
    	$dbManager->openConn(); //call open connection from DBManager class.

		$response = array(); //create response array
		$result = $db->getStops($route); //call get Stops from DbOps

		$response["error"] = false; //set response error to false

		$response["data"] = array('stops' => $result); //set response data to an array of stops containing data
		sendResp(HTTPSTATUS_OK, $response); //send response
		$dbManager->closeConn(); //close database connection
	}
});

//Slim API route for 'getTimetable/:name'. URL takes parameter.
$app->get('/getTimetable/:name', function($timetable) use ($app){
	$dbManager = new DBManager(); //create instance of DBManager class.
   	$db = new DbOps($dbManager); //create instance of DbOps class passing in DBManager instance.
   	$dbManager->openConn(); //call open connection from DBManager class.

	$response = array(); //create response array
	$result = $db->getTimetable($timetable); //call get timetable from DbOps

	$response["error"] = false; //set response error to false
	$response["data"] = array('timetable' => $result); //set response data to timetable array with data
	sendResp(HTTPSTATUS_OK, $response); //send response 
	$dbManager->closeConn(); //close database connection
});

/*
 * Function to check the headers of a request for Authorization Token
 * 
 * @return String Returns a message based on wether the token is valid or not.
 */
function headerCheck(){
	$headers = apache_request_headers(); //get all headers from request.

	//if the Auth header is not set the return invalid.
	if(!isset($headers["Auth"]) && $headers["Auth"] == false){
		return "invalid";
	}
	else{
		$dbManager = new DBManager(); //create instance of DBManager class.
    	$db = new DbOps($dbManager); //create instance of DbOps class passing in DBManager instance.
    	$dbManager->openConn(); //call open connection from DBManager class.

		$token = $headers['Auth']; //set token variable to token from header.
		if(!$db->tokenCheck($token)){ //if token check is true return invalid.
			return  "invalid";
		}
		else{
			if($db->hasTokenExpired($token)){ //if token has expired return expired.
				return "expired";
			}else {
				$tokenExpire = date('Y-m-d H:i:s', strtotime('+1 hour')); //get a new time.
				$db->keepTokenAlive($token, $tokenExpire); //call keepTokenAlive.
				return "updated"; //return updated.
			}
		}

		$dbManager->closeConn(); //close the DB connection.
	}
}

/*
 * Function to send response back to client.
 * 
 * @param Integer Response code (200, 201, 404 etc.)
 * @param Array The response array to send back.
 * 
 */
function sendResp($respCode, $resp){
	$app = \Slim\Slim::getInstance();

	$app->status($respCode);

	$app->contentType('application/json');

	echo json_encode($resp);
}

$app->run();
?>
