<?php
/**
  * @author Stefan Burke
  * @author Stefan Burke <stefan.burke@mydit.ie>
  */

//Set the default timezone
date_default_timezone_set('Europe/Dublin');


class DbOps {

	private $conn; //create private conn variable

	/**
  	* Class contructor. Called anytime DbOps is intialized.
  	*
  	* @param $DBManager is an instance of the DBManager class.
  	*/
	function DbOps($DBManager){
		$this->conn = $DBManager; //assign the conn variable to the $DBManager instance
	}

	/**
  	* Function to register user. Called in index.php in the /register path.
  	*
  	* @param String $forename is passed in from index.php and is send by the client register form.
  	* @param String $surname is passed in from index.php and is send by the client register form.
  	* @param Integer $age is passed in from index.php and is send by the client register form.
  	* @param String $sex is passed in from index.php and is send by the client register form.
  	* @param String $email is passed in from index.php and is send by the client register form.
  	* @param String $pass is passed in from index.php and is send by the client register form.
  	* 
  	* @return String Return a message based on the result of Database opertation.
  	*/
	public function registerUser($forename, $surname, $age, $sex, $email, $pass){

		//if checkExistingUsers method returns false
		if($this->checkExistingUsers($email) == false){
			//Create SQL statement, prepare statement, bind values and execute query
			$stmt = "INSERT INTO user(forename, surname, age, sex, email, password) VALUES (?,?,?,?,?,?)";
			$query = $this->conn->prepareQuery($stmt);
			$this->conn->bindVal($query, 1, $forename, $this->conn->STRING);
			$this->conn->bindVal($query, 2, $surname, $this->conn->STRING);
			$this->conn->bindVal($query, 3, $age, $this->conn->INT);
			$this->conn->bindVal($query, 4, $sex, $this->conn->STRING);
			$this->conn->bindVal($query, 5, $email, $this->conn->STRING);
			$this->conn->bindVal($query, 6, $pass, $this->conn->STRING);

			$result = $this->conn->executeQuery($query);
			return "Success";
		} else {
			return "Duplicate User";
        }
    }


    /**
  	* Function to check if an email is already in use. Called by functions in this class.
  	*
  	* @param String $email is passed in from index.php and is send by the client register form.
  	* 
  	* @return Boolean Returns true if email found else false if not found.
  	*/
	private function checkExistingUsers($email){
		$stmt = "SELECT email FROM user WHERE email = ?";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $email, $this->conn->STRING);
		$result = $this->conn->executeQuery($query);
		$fetchedResults = $this->conn->fetchResults($query);

		if(count($fetchedResults) > 0){
			return true;
		}else{
			return false;
        	}
	}

	/**
  	* Function to check the login credentials against the database.
  	*
  	* @param String $email is passed in from index.php and is send by the client register form.
  	* 
  	* @return Array Returns an array of credentials if found or array of null if not found.
  	*/
	public function checkLoginCreds($email){
		$stmt = "SELECT email, password FROM user WHERE email = ?";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $email, $this->conn->STRING);
		$result = $this->conn->executeQuery($query);
		$fetchedResults = $this->conn->fetchResults($query);

		if(count($fetchedResults) > 0){
			return $fetchedResults;
		}else{
			$user = null;
			return $user;
		}
	}

	/**
  	* Function to update the user API token.
  	*
  	* @param String $email is passed in from index.php and is send by the client register form.
  	* @param String $token is passed in from index.php and is send by the client register form.
  	* @param String $expireTime the time to update the time expirery field.
  	* 
  	*/
	public function updateUserToken($email, $token, $expireTime){
		$stmt = "UPDATE user SET apiToken = ?, tokenExpire = ? WHERE email = ?";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $token, $this->conn->STRING);
		$this->conn->bindVal($query, 2, $expireTime, $this->conn->STRING);
		$this->conn->bindVal($query, 3, $email, $this->conn->STRING);
		$this->conn->executeQuery($query);
	}

	/**
  	* Function to check if the users token has expired.
  	*
  	* @param String $token is passed in from index.php and is send by the client register form.
  	* 
  	* @return Boolean Returns true if token is expired or false if not.
  	*/
	public function hasTokenExpired($token){
		$stmt = "SELECT tokenExpire FROM user WHERE apiToken = ?";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $token, $this->conn->STRING);
		$this->conn->executeQuery($query);
		$fetchedResults = $this->conn->fetchResults($query);
		$tokenTime = $fetchedResults[0]["tokenExpire"];
		$tokenTime = strtotime($tokenTime);

		$currentTime = date('Y-m-d H:i:s');
		$currentTime = strtotime($currentTime);

		if($currentTime > $tokenTime){
			return true;
		}
		else{
			return false;
		}
	}

	/**
  	* Function to keep token alive when user is navigating throught app when logged in.
  	*
  	* @param String $token user API token.
  	* @param String $expireTime The new time to set the expirery to.
  	* 
  	*/
	public function keepTokenAlive($token, $expireTime){
		$stmt = "UPDATE user SET tokenExpire = ? WHERE apiToken = ?";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $expireTime, $this->conn->STRING);
		$this->conn->bindVal($query, 2, $token, $this->conn->STRING);
		$this->conn->executeQuery($query);
	}

	/**
  	* Function to check if a token exists.
  	*
  	* @param String $token is passed in from index.php and is send by the client register form.
  	* 
  	* @return Boolean Returns true if token is found or false if not.
  	*/
	public function tokenCheck($token){
		$stmt = "SELECT userID FROM user WHERE apiToken = ?";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $token, $this->conn->STRING);
		$this->conn->executeQuery($query);
		$fetchedResults = $this->conn->fetchResults($query);

		if(count($fetchedResults) > 0){
			return true;
		}
		else{
			return false;
		}
	}

	/**
  	* Function to update the location of a bus.
  	*
  	* @param String $lat the latitude passed from the device on a bus.
  	* @param String $lon the longititude passed from the device on a bus.
  	* @param String $time the time passed from the device on a bus.
  	* @param Integer $deviceID the ID of the device on a bus.
  	* @param Integer $routeID the route ID of the device.
  	* 
  	* @return String Return a message based on the result of Database opertation.
  	*/
	public function updateLocation($lat, $lon, $time, $deviceID, $routeID){
		$stmt = "SELECT deviceID FROM location WHERE deviceID = ?;";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $deviceID, $this->conn->INT);
		$this->conn->executeQuery($query);
		$fetchedResults = $this->conn->fetchResults($query);
		$location = 'POINT('.$lat." ".$lon.')';

		if(empty($fetchedResults)){
			$stmt = "INSERT INTO location (routeID, deviceID, geoLocation, timestamp) VALUES (?, ?, PointFromText(?), ?)";
			$query = $this->conn->prepareQuery($stmt);
			$this->conn->bindVal($query, 1, $routeID, $this->conn->INT);
			$this->conn->bindVal($query, 2, $deviceID, $this->conn->INT);
			$this->conn->bindVal($query, 3, $location, $this->conn->STRING);
			$this->conn->bindVal($query, 4, $time, $this->conn->STRING);
			$this->conn->executeQuery($query);
		}else{
			$devID = $fetchedResults[0]["deviceID"];
			$stmt = "UPDATE location SET routeID = ?, deviceID = ?, geoLocation = PointFromText(?), timestamp = ? ";
			$stmt .= "WHERE deviceID = ?;";
			$query = $this->conn->prepareQuery($stmt);
			$this->conn->bindVal($query, 1, $routeID, $this->conn->INT);
			$this->conn->bindVal($query, 2, $deviceID, $this->conn->INT);
			$this->conn->bindVal($query, 3, $location, $this->conn->STRING);
			$this->conn->bindVal($query, 4, $time, $this->conn->STRING);
			$this->conn->bindVal($query, 5, $devID, $this->conn->INT);
			$this->conn->executeQuery($query);
		}

		return "success";
	}

	/**
  	* Function to map the route the bus takes. Used to collect route data during implementation
  	*
  	* @param String $lat the latitude passed from the device on a bus.
  	* @param String $lon the longititude passed from the device on a bus.
  	* 
  	* @return String Return a message based on the result of Database opertation.
  	*/
	public function updateRouteMap($lat, $lon){
		$location = 'POINT('.$lat." ".$lon.')';
		$stmt = "INSERT INTO routeMap (routeID, location) VALUES (2, PointFromText(?))";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $location, $this->conn->STRING);
		$this->conn->executeQuery($query);

		return "success";
	}

	/**
  	* Function that returns the location data or location of bus based on what client process requests it.
  	*
  	* @param String $route the route that you want to get a location/s from.
  	* 
  	* @return String Return a location.
  	*/
	public function getLocation($route){
		if(intval($route) == 0){
			$routeID = $this->fetchRouteID($route);

			if($routeID == null){
				return false;
			}else{
				$stmt = "SELECT ST_X(geoLocation) as Xpos, ST_Y(geoLocation) as Ypos, timestamp, deviceID FROM location WHERE routeID = ?;";
				$query = $this->conn->prepareQuery($stmt);
				$this->conn->bindVal($query, 1, $routeID, $this->conn->INT);
				$this->conn->executeQuery($query);
				$location = $this->conn->fetchResults($query);
				return $location;
			}
		}else{
			$stmt = "SELECT ST_X(geoLocation) as Xpos, ST_Y(geoLocation) as Ypos, timestamp FROM location WHERE deviceID = ?;";
			$query = $this->conn->prepareQuery($stmt);
			$this->conn->bindVal($query, 1, $route, $this->conn->INT);
			$this->conn->executeQuery($query);
			$location = $this->conn->fetchResults($query);
			return $location;
		}
	}

	/**
  	* Function to get all points of a Route.
  	*
  	* @param String $lat the latitude passed from the device on a bus.
  	* 
  	* @return Array Returns an array of points that make up a route.
  	*/
	public function getRoute($route){
		$routeID = $this->fetchRouteID($route);

		$stmt = "SELECT ST_X(location) as Xpos, ST_Y(location) as Ypos ";
		$stmt .= "FROM routeMap WHERE routeID = ?;";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $routeID, $this->conn->INT);
		$this->conn->executeQuery($query);
		$data = $this->conn->fetchResults($query);
		return $data;
	}

	/**
  	* Function to get all of the stops on a certain route.
  	*
  	* @param String $route the name of the route whose stops to retrieve.
  	*
  	* @return Array Returns an array of points.
  	*/
	public function getStops($route){
		$routeID = $this->fetchRouteID($route);
		$data = null;
		if($routeID == 3){
			$stmt = "SELECT name, ST_X(location) AS Xpos, ST_Y(location) AS Ypos FROM stop WHERE stopID <=8 UNION ";
			$stmt .= "SELECT name, ST_X(location) AS Xpos, ST_Y(location) AS Ypos FROM stop WHERE routeID = ?;";
			$query = $this->conn->prepareQuery($stmt);
			$this->conn->bindVal($query, 1, $routeID, $this->conn->INT);
			$this->conn->executeQuery($query);
			$data = $this->conn->fetchResults($query);
		}else{
			$stmt = "SELECT name, ST_X(location) as Xpos, ST_Y(location) as Ypos FROM stop WHERE routeID = ?;";
			$query = $this->conn->prepareQuery($stmt);
			$this->conn->bindVal($query, 1, $routeID, $this->conn->INT);
			$this->conn->executeQuery($query);
			$data = $this->conn->fetchResults($query);
		}

		return $data;
	}

	/**
  	* Function to retrieve all timetable data for a certain timetable.
  	*
  	* @param String $timetable the name of the timetable.
  	*
  	* @return Array Returns an associative array of any data retrieved.
  	*/
	public function getTimetable($timetable){
		$routeID = $this->fetchRouteID($timetable);
		$availDays = $this->checkDays($routeID);

		if($availDays["monFri"] == true){
			$stmt2 = "SELECT s.name, mt.time, mt.direction FROM midweekTime mt ";
			$stmt2 .= "INNER JOIN stop s ON mt.stopID = s.stopID ";
			$stmt2 .= "WHERE mt.routeID = ? ORDER BY midweekTimeID;";
			$query2 = $this->conn->prepareQuery($stmt2);
			$this->conn->bindVal($query2, 1, $routeID, $this->conn->INT);
			$this->conn->executeQuery($query2);
			$fetched["monFri"] = $this->conn->fetchResults($query2);
		}

		if($availDays["sat"] == true){
			$stmt3 = "SELECT s.name, st.time, st.direction FROM satTime st ";
               		$stmt3 .= "INNER JOIN stop s ON s.stopID = st.stopID ";
			$stmt3 .= "WHERE s.routeID = ? ORDER BY satTimeID;";
			$query3 = $this->conn->prepareQuery($stmt3);
			$this->conn->bindVal($query3, 1, $routeID, $this->conn->INT);
			$this->conn->executeQuery($query3);
			$fetched["sat"] = $this->conn->fetchResults($query3);
		}

		if($availDays["sun"] == true){
			$stmt4 = "SELECT s.name, st.time, st.direction FROM sunTime st ";
               		$stmt4 .= "INNER JOIN stop s ON s.stopID = st.stopID ";
 			$stmt4 .= "WHERE s.routeID = ? ORDER BY sunTimeID;";
			$query4 = $this->conn->prepareQuery($stmt4);
			$this->conn->bindVal($query4, 1, $routeID, $this->conn->INT);
			$this->conn->executeQuery($query4);
			$fetched["sun"] = $this->conn->fetchResults($query4);
		}

		return $fetched;
	}

	/**
  	* Function to update the location of a bus.
  	*
  	* @param Integer $routeID the route ID of a route to check what timetable days are available.
  	* 
  	* @return Array Returns an associative array of booleans.
  	*/
	private function checkDays($routeID){
		$stmt1 = "SELECT routeID from midweekTime WHERE routeID = ?;";
		$query1 = $this->conn->prepareQuery($stmt1);
		$this->conn->bindVal($query1, 1, $routeID, $this->conn->INT);
		$result1 = $query1->execute();

		$stmt2 = "SELECT routeID from satTime WHERE routeID = ?;";
		$query2 = $this->conn->prepareQuery($stmt2);
		$this->conn->bindVal($query2, 1, $routeID, $this->conn->INT);
		$result2 = $query2->execute();

		$stmt3 = "SELECT routeID from sunTime WHERE routeID = ?;";
		$query3 = $this->conn->prepareQuery($stmt3);
		$this->conn->bindVal($query3, 1, $routeID, $this->conn->INT);
		$result3 = $query3->execute();

		if($result1 == false){$results["monFri"] = false;}
		else{$results["monFri"] = true;}
		if($result2 == false){$results["sat"] = false;}
		else{$results["sat"] = true;}
		if($result3 == false){$results["sun"] = false;}
		else{$results["sun"] = true;}

		return $results;
	}

	/**
  	* Function to get the route id of a specific route.
  	*
  	* @param String $route the route that you want the ID of.
  	* 
  	* @return Integer $routeID returns the ID of a route.
  	*/
	private function fetchRouteID($route){
		$stmt = "SELECT routeID FROM route WHERE routeName LIKE ?;";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $route, $this->conn->STRING);
		$this->conn->executeQuery($query);
		$fetchedResults = $this->conn->fetchResults($query);
		$routeID = intval($fetchedResults[0]['routeID']);
		return $routeID;
	}
}

?>
