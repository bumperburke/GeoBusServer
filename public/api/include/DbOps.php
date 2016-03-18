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
		$currentTime = date('Y-m-d H:i:s');
		if($currentTime > $fetchedResults['tokenExpire']){
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

	public function updateLocation($lat, $lon, $time, $busID){
		$location = 'POINT('.$lat." ".$lon.')';
		$stmt = "INSERT INTO location (geoLocation, timestamp, busID) VALUES (PointFromText(?), ?, ?)";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $location, $this->conn->STRING);
		$this->conn->bindVal($query, 2, $time, $this->conn->STRING);
		$this->conn->bindVal($query, 3, $busID, $this->conn->INT);
		$this->conn->executeQuery($query);

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

	public function getLocation(){
		$stmt = "SELECT ST_Y(geoLocation) as Ypos, ST_X(geoLocation) as Xpos FROM location WHERE busID = 1 AND locID <= 26";
		//$stmt = "SELECT ST_X(location) as Xpos, ST_Y(location) as Ypos FROM routeMap;";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->executeQuery($query);
		$locations = $this->conn->fetchResults($query);
		return $locations;
	}

	public function getRoute($route){
		$stmt = "SELECT routeID FROM route WHERE routeName LIKE ?;";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $route, $this->conn->STRING);
		$this->conn->executeQuery($query);
		$fetchedResults = $this->conn->fetchResults($query);
		$routeID = intval($fetchedResults[0]['routeID']);

		$stmt = "SELECT ST_X(location) as Xpos, ST_Y(location) as Ypos ";
		$stmt .= "FROM routeMap WHERE routeID = ?;";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $routeID, $this->conn->INT);
		$this->conn->executeQuery($query);
		$data = $this->conn->fetchResults($query);
		return $data;
	}

	public function getStops($route){
		$stmt = "SELECT routeID FROM route WHERE routeName LIKE ?;";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $route, $this->conn->STRING);
		$this->conn->executeQuery($query);
		$fetchedResults = $this->conn->fetchResults($query);
		$routeID = intval($fetchedResults[0]['routeID']);

		$stmt = "SELECT name, ST_X(location) as Xpos, ST_Y(location) as Ypos FROM stop WHERE routeID = ?;";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $routeID, $this->conn->INT);
		$this->conn->executeQuery($query);
		$data = $this->conn->fetchResults($query);
		return $data;
	}

	public function getTimetable($timetable){
		$stmt = "SELECT routeID FROM route WHERE routeName LIKE ?;";
		$query = $this->conn->prepareQuery($stmt);
		$this->conn->bindVal($query, 1, $timetable, $this->conn->STRING);
		$this->conn->executeQuery($query);
		$fetchedResults = $this->conn->fetchResults($query);
		$routeID = intval($fetchedResults[0]['routeID']);
		$availDays = $this->checkDays($routeID);

		if($availDays["monFri"] == true){
			$stmt2 = "SELECT s.name, mt.time, mt.direction FROM midweekTime mt ";
                	$stmt2 .= "INNER JOIN stop s ON s.stopID = mt.stopID ";
 			$stmt2 .= "WHERE s.routeID = ? ORDER BY midweekTimeID;";
			$query2 = $this->conn->prepareQuery($stmt2);
			$this->conn->bindVal($query2, 1, $routeID, $this->conn->INT);
			$this->conn->executeQuery($query2);
			$fetched["monFri"] = $this->conn->fetchResults($query2);
		}else{}

		if($availDays["sat"] == true){
			$stmt3 = "SELECT s.name, st.time, st.direction FROM satTime st ";
               		$stmt3 .= "INNER JOIN stop s ON s.stopID = st.stopID ";
			$stmt3 .= "WHERE s.routeID = ? ORDER BY satTimeID;";
			$query3 = $this->conn->prepareQuery($stmt3);
			$this->conn->bindVal($query3, 1, $routeID, $this->conn->INT);
			$this->conn->executeQuery($query3);
			$fetched["sat"] = $this->conn->fetchResults($query3);
		}else{}

		if($availDays["sun"] == true){
			$stmt4 = "SELECT s.name, st.time, st.direction FROM sunTime st ";
               		$stmt4 .= "INNER JOIN stop s ON s.stopID = st.stopID ";
 			$stmt4 .= "WHERE s.routeID = ? ORDER BY sunTimeID;";
			$query4 = $this->conn->prepareQuery($stmt4);
			$this->conn->bindVal($query4, 1, $routeID, $this->conn->INT);
			$this->conn->executeQuery($query4);
			$fetched["sun"] = $this->conn->fetchResults($query4);
		}else{}

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
}

?>
