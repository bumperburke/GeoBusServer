<?php
/**
  * @author Stefan Burke
  * @author Stefan Burke <stefan.burke@mydit.ie>
  */
date_default_timezone_set('Europe/Dublin');

class DbOps {

	private $conn;

	function DbOps($DBManager){
		$this->conn = $DBManager;
	}

	//Function to register user. Called in index.php in the /register path function.
	public function registerUser($forename, $surname, $age, $sex, $email, $pass){

		if($this->checkExistingUsers($email) == false){
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

	public function updateUserToken($email, $token, $expireTime){
		$stmt = "UPDATE user SET apiToken = ?, tokenExpire = ? WHERE email = ?";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $token, $this->conn->STRING);
		$this->conn->bindVal($query, 2, $expireTime, $this->conn->STRING);
		$this->conn->bindVal($query, 3, $email, $this->conn->STRING);
		$this->conn->executeQuery($query);
	}

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

	public function keepTokenAlive($token, $expireTime){
		$stmt = "UPDATE user SET tokenExpire = ? WHERE apiToken = ?";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $expireTime, $this->conn->STRING);
		$this->conn->bindVal($query, 2, $token, $this->conn->STRING);
		$this->conn->executeQuery($query);
	}

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

	public function updateRouteMap($lat, $lon){
		$location = 'POINT('.$lat." ".$lon.')';
		$stmt = "INSERT INTO routeMap (routeID, location) VALUES (2, PointFromText(?))";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $location, $this->conn->STRING);
		$this->conn->executeQuery($query);

		return "success";
	}

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

	public function getTimetable($timetable){
		$routeID = $this->fetchRouteID($timetable);
		//$availDays = $this->checkDays($routeID);

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
