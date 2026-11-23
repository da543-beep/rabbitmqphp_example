#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

//working on MFA, created the sendMFACode function
//need to change logic in doLogin and login case statement
//also added the mfa in the requestProcessor case statement
require_once "Mail.php";
require_once "Mail/smtp.php";
function sendMFACode($username)
{
	$host = "ssl://smtp.gmail.com";
	$username = getenv("ADMIN_EMAIL");
	$password = getenv("ADMIN_PASSWORD");
	$port = "465";

	$db = getDb();
	$mfa_code = random_int(100000, 999999);

	//getting the email address based on the username
	$stmt = $db->prepare("SELECT email FROM users WHERE username = ?");
	$stmt->bind_param('s', $username);
	$stmt->execute;
	$stmt->bind_result($email);
	$stmt->close;
	
	//inserting code into table at that username
	$stmt = $db->prepare("UPDATE users SET mfa_code = ? WHERE username = ?");
	$stmt->bind_param('is', $mfa_code, $username);
	$stmt->execute;
	$stmt->close;


	$db->close;

	

	echo "User and email found";
	$to = $email;
	$email_from = getenv("ADMIN_EMAIL");
	
	$email_subject = "Your Multi Factor Authentication Code";
	$email_body = "Your MFA Code is $mfa_code";
	$email_address = getenv("ADMIN_EMAIL");
	$content = "text/html; charset=utf-8";
	$mime = "1.0";

	$headers = array ('From' => $email_from,
		'To' => $to,
		'Subject' => $email_subject,
		'Reply-To' => $email_address,
		'MIME-Version' => $mime,
		'Content-Type' => $content);

	$params = array ('host' => $host,
		'port' => $port,
		'auth' => true,
		'username' => $username,
		'password' => $password);

	$stmt = Mail::factory ('stmt', $params);
	$mail = $smtp->send($to, $headers, $email_body);
	
	return ['success' => true];

}


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function getDb()
{
    $ini = parse_ini_file(__DIR__ . '/db.ini', true);
    if ($ini === false || !isset($ini['database'])) {
        echo "db.ini is missing or has no [database] section" . PHP_EOL;
        return null;
    }
    $cfg = $ini['database'];

    try {
        return new mysqli($cfg['host'], $cfg['user'], $cfg['password'], $cfg['name']);
    } catch (mysqli_sql_exception $e) {
        echo "Database connection failed: " . $e->getMessage() . PHP_EOL;
        return null;
    }
}

function doRegistration($username, $email, $password)
{
  
    if (!is_string($username) || !preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        return ['success' => false,
                'message' => 'Username must be 3-50 characters: letters, numbers, underscore'];
    }
    if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }
    if (!isset($password)) {
        return ['success' => false, 'message' => 'Requires password asshole!'];
    }

    $db = getDb();
    if ($db === null) {
	    return ['success' => false, 'message' => 'Database unavailable'];
	
    }


    try {
	$hash=password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $username, $email, $hash);
        $stmt->execute();
        $stmt->close();
        $db->close();

	echo "Registered user: " . $username . PHP_EOL; 
        return ['success' => true];
    } catch (mysqli_sql_exception $e) {
        $db->close();

        if ($e->getCode() == 1062) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        echo "Registration error: " . $e->getMessage() . PHP_EOL;
        return ['success' => false, 'message' => 'Registration failed'];
    } 
}

function doValidate($sessionKey){
	$db = getDb();
		if ($db === null) {
			return ['success' => false, 'message' => 'Database unavailable'];
		}			
	try{
	$stmt = $db->prepare("SELECT username FROM users WHERE session_key = ? AND session_expires > NOW()");
	$stmt->bind_param('s',$sessionKey);
	$stmt->execute();
	$stmt->bind_result($username);
	$session_found =$stmt->fetch();
	$stmt->close();
	
	if ($session_found){
		$db->close();
		echo "Valid session for user: " . $username . PHP_EOL;
		return ['success' => true, 'username'=> $username]; 
	}
	$stmt = $db->prepare("UPDATE users SET session_key = NULL, session_expires = NULL WHERE session_key=?");
	$stmt->bind_param('s',$sessionKey);
	$stmt->execute();
	$stmt->close();
	$db->close();

	echo "Invalid session" . PHP_EOL;
	return ['success' => false, 'message'=>'Session expired or invalid'];

	} catch (mysqli_sql_exception $e) {
		echo "Session check error: " . $e->getMessage() . PHP_EOL;
		$db->close();
		return ['success' => false, 'message' => 'Session check failed'];
	}
	
}

function doLogin($username, $password){
	if(!is_string($username) || !preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)){
		return ['success' => false, 'message' => 'Username invalid'];
	}

	if(!isset($password)){
		return ['success' => false, 'message' => 'Password invalid'];
	}
	//Connects to Database
	$db = getDb();
    		if ($db === null) {
			return ['success' => false, 'message' => 'Database unavailable'];
		}
	try {
        $stmt = $db->prepare("SELECT password, email FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
	$stmt->execute();
	$stmt->bind_result($storedPassword, $email);
	$found = $stmt->fetch();
	$stmt->close();

	if ($found && password_verify($password, $storedPassword)) {
		$sessionKey = bin2hex(random_bytes(32));
		$stmt = $db->prepare("UPDATE users SET session_key = ?, session_expires = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE username=?");
		$stmt->bind_param('ss', $sessionKey, $username);
		$stmt->execute();
		$stmt->close();
		$db->close();
		
		 
		
		echo "Logged in user: " . $username. PHP_EOL;
		return ['success' => true, 'username'=> $username,'email' => $email, 'session_key' => $sessionKey];
	}

        echo "Failed login for user: " . $username . PHP_EOL;  
	return ['success' => false, 'message' => 'Login failed'];
	}

	catch (mysqli_sql_exception $e) 
	{
		echo "Login error: " . $e->getMessage() . PHP_EOL;
		$db->close();	
		return ['success'=>false, 'message'=>'Unable to process login'];
	}
	
}
function doLogout($username, $sessionKey)
{
	$db = getDb();
		if ($db === null) {
			return ['success' => false, 'message' => 'Database unavailable'];
		}
	try{
		$stmt=$db->prepare("SELECT username FROM users WHERE username = ? AND session_key=?");
		$stmt->bind_param('ss',$username, $sessionKey);
		$stmt->execute();
		$stmt->bind_result($storedUsername);
		$validUser = $stmt->fetch();
		$stmt->close();
		

		if($validUser)
		{
			$stmt=$db->prepare('UPDATE users SET session_key=NULL, session_expires=NULL WHERE username=? AND session_key =?');
			$stmt->bind_param('ss',$username, $sessionKey);
			$stmt->execute();
			$stmt->close();
			$db->close();

			echo "Logged out user: " . $username. PHP_EOL;
			return ['success' => true, 'username'=> $username];
		}
		$db->close();
		echo "Failed logout for some reason: " . $username. PHP_EOL;
		return ['success' => false, 'message'=>'Logout Failed '];
	}
	catch (mysqli_sql_exception $e)
	{
		echo "Logout error: " . $e->getMessage() . PHP_EOL;
		$db->close();
		return ['success'=>false, 'message'=>'Unable to process logout'];
	}
}

function doStoreGames($games)
	{
	echo "work!: " . PHP_EOL;
	$games = json_decode($games, true);

	 $db = getDb();
                if ($db === null) {
                        return ['success' => false, 'message' => 'Database unavailable'];

		}
	 try
	 {
	
		 $stmt=$db->prepare("INSERT INTO games (gameID, URL, genre, platform, releaseDate, name) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name = VALUES(name), URL = VALUES(URL), 
			 genre = VALUES(genre), platform = VALUES(platform),
			 releaseDate = VALUES(releaseDate)");
		$stmt->bind_param('isssss', $id, $cover, $genres, $platforms, $release, $name);
		$db->begin_transaction();
		$stored = 0;
		$skipped = 0;

		foreach ($games as $g)
		{
			if (!isset($g['id'], $g['name'])){
				$skipped++;
				continue;									
			}
			$id = (int)$g['id'];
			$name = $g['name'];
			$cover = $g['cover'] ?? null;
			$genres = $g['genres'] ?? null;
			$platforms = $g['platforms'] ?? null;
			$release = $g['release_dates'] ?? null;

			$stmt->execute();
			$stored++;
		}

		$db->commit();
		$stmt->close();
		$db->close();

		echo "Stored games: " . $stored. " (skipped " . $skipped . ")" . PHP_EOL;
		return ['success'=> true, 'stored'=>$stored, 'skipped' => $skipped];
	 } catch (mysqli_sql_exception $e) {
		echo "Store games error: " . $e->getMessage() . PHP_EOL;
		$db->rollback();
		$db->close();
		return ['success'=>false, 'message'=>'Could not store games'];
	 }
	}

function doSearch($game, $genre, $platform)
{
	$game=is_string($game) ? trim($game) : '';
	$genre=is_string($genre) ? trim($genre) : '';
	$platform=is_string($platform) ? trim($platform) : '';

	 $db = getDb();
                if ($db === null) {
                        return ['success' => false, 'message' => 'Database unavailable'];
                }
	 
	 try
	 {
		 $where=[];
		 $params=[];

		 if($game !== '') {$where[] = "name LIKE ?"; $params[]='%' . $game . '%'; }
		 if($genre !== '') {$where[] = "genre LIKE ?"; $params[]='%' . $genre . '%';}
		if($platform !== '') {$where[] = "platform LIKE ?"; $params[]='%' . $platform . '%';}
		
		 $sql="SELECT gameID, name, URL, genre, platform, releaseDate FROM games";
		 if ($where) {
			 $sql .=" WHERE " . implode(' AND ', $where);
		 }
		 $sql .= " ORDER BY RAND()";

		 $stmt=$db->prepare("$sql");
		 if ($params) {
		 	$stmt->bind_param(str_repeat('s', count($params)), ...$params);
		 }
		 $stmt->execute();
		 $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
		 $stmt->close();
		 $db->close();

		 echo "Searched returned " . count($rows) . " games" . PHP_EOL;
		 return ['success'=> true, 'count' =>($rows), 'games'=> $rows];
	 } catch (mysqli_sql_exception $e) {
		 echo "Search error: " . $e->getMessage() . PHP_EOL;
		 $db->close();
		 return ['success'=>false, 'message'=>'Search failed'];

	 }
}
function doGetLibrary($username){
	$db = getDB();
	if ($db === null){
		return ['success' => false, 'message' => 'DB unavailable'];
	}
	try{
		$stmt = $db->prepare("SELECT g.gameID, g.name, g.URL, g.genre, g.platform, g.releaseDate, l.added_at 
		FROM library l
		JOIN games g ON g.gameID = l.game_id
		WHERE l.user_name = ?
		ORDER BY l.added_at DESC"  );

		$stmt->bind_param('s', $username);
		$stmt->execute();
		$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
		$stmt->close();
		$db->close();

		echo "Library for $username: " . count($rows) . " games" . PHP_EOL;
		return ['success' => true, 'count' => count($rows), 'games' => $rows];
	} catch (mysqli_sql_exception $e){
		echo "Library fetch error: " . $e->getMessage() . PHP_EOL;
		$db->close();
		return ['success' => false, 'message' => 'Could not load'];
	}
}

function doAddToLibrary($username, $game_id){

	$db = getDb();
	if ($db === null) {
		return ['success' => false, 'message' => 'DB unavailable'];
	}
	try{
		$stmt = $db->prepare("INSERT INTO library (user_name, game_id) VALUES(?, ?)");
		$stmt->bind_param('si', $username, $game_id);
		$stmt->execute();
		$stmt->close();
		$db->close();

		echo "Added game: " . $game_id ." to library of ". $username . PHP_EOL;

		return ['success' => true, 'message: ' => 'Game added successful!', 'game' => $game_id ];

		return ['success' => true, 'already_in_library' => false];

	} catch (mysqli_sql_exception $e) {
		$db->close();
		echo "Library add error: " . $e->getMessage() . PHP_EOL;
		return ['success' => false, 'message' => 'Could not add to library'];
	}
}

function doAddReview($user_name, $game_id, $rating, $review_text, $game_name, $genre){
	$db = getDB();
	if($db === null) {
		return ['success' => false, 'message' => 'DB unavailable'];
	}
	try{
		$stmt = $db->prepare("INSERT INTO reviews(user_name, game_id, rating, review_text, game_name, genre) VALUES (?, ?, ?, ?, ?, ?)");
		$stmt->bind_param("sissss", $user_name, $game_id, $rating, $review_text, $game_name, $genre);
		$stmt->execute();
		$db->close();

		return['success' => true, 'message' => 'Review added'];
	} catch (mysqli_sql_exception $e){
		error_log("Review error: " . $e->getMessage());
		$db->close();

		return ['success' => false, 'message' => 'Could not add a review'];
	}
}

function doGetReview($game_id){
	$db = getDB();
	if ($db === null) {
		return ['success' => false, 'message' => 'DB unavailable'];
	}
	try {
		$stmt = $db->prepare("SELECT user_name, rating, review_text, created_at FROM reviews WHERE game_id =?");
		$stmt->bind_param("i", $game_id);
		$stmt->execute();
		$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
		$stmt->close();
		$db->close();

		return ['success' =>true, 'count' => count($rows), 'reviews' => $rows];
	} catch (mysqli_sql_exception $e) {
		error_log("Review fetch error: " . $e->getMessage());
		$db->close();

		return ['success' => false, 'message' => 'Could not load review'];
	}
}

function doGetRecommendations($genre){
	echo "this shit better work";
	$db = getDB();
	echo "this shit better work";
	if ($db === null){
		return [ 'success' => false, 'message' => 'Database unavialable']; 
	}
	try {
	$sql = "SELECT game_id, game_name, genre, ROUND(AVG(CAST(rating AS FLOAT(3,1))), 2) AS average_rating, COUNT(*) AS total_reviews FROM reviews WHERE rating REGEXP '^[0-5]$' GROUP BY game_id, game_name, genre ORDER BY average_rating DESC, total_reviews DESC LIMIT 10";
	$result = $db->query($sql);
	$games = $result->fetch_all(MYSQLI_ASSOC);
	$db->close();
	return [ 'success' => true, 'count' =>count($games), 'games' => $games];
	} catch (mysqli_sql_exception $e) {
		error_log("Recommendation error: " . $e->getMessage());
		$db->close();
		return [ 'success' => false, 'message' => 'Could not load recommendations'];
	}
}

function requestProcessor($request)
{
    echo "received request of type: " . ($request['type'] ?? 'none') . PHP_EOL;
    switch (strtolower($request['type'])) {

   	 case 'registration':
            if (!isset($request['username'], $request['email'], $request['password'])) {
                return ['success' => false, 'message' => 'Missing registration fields'];
            }
            $result = doRegistration($request['username'], $request['email'], $request['password']);

	    echo "replying: " . json_encode($result) . PHP_EOL;
	    return $result;
	    break;
	    
	case 'login':
		if (!isset($request['username'], $request['password'])) {
			return ['success' => false, 'message' => 'Missing login fields'];
		}
		
		$result = doLogin($request['username'], $request['password']);
		echo "replying: " . json_encode($result) . PHP_EOL;
		return $result;
		break;
    		
	case 'logout':
		if (!isset($request['username'], $request['session_key'])){
			return ['success' => false, 'message' => 'Invalid logout fields'];
		}
		 $result = doLogout($request['username'], $request['session_key']);
		 echo "replying: " . json_encode($result). PHP_EOL;
		 return $result;
		 break;

    	case 'validate_session':
		if (!isset($request['session_key'])){
			return ['success' => false, 'message' => 'Invalid session key'];
		}
		$result = doValidate($request['session_key']);
		echo "replying: " . json_encode($result). PHP_EOL;
		return $result;
		break;

	case 'storegames':
		$result = doStoreGames($request['games']);
		echo "replying: " . json_encode($result). PHP_EOL;
		return $result;
		break;

	case 'search':
		$result = doSearch($request['name'] ?? '', $request['genre'] ?? '', $request['platform'] ?? '');
		echo "replying: " . json_encode($result). PHP_EOL;
		return $result;
		break;
	case 'add_to_library':
		if(!isset($request['username'], $request['game_id'])){
			return ['success' => false, 'message' => 'Missing Fields'];
	}
		$result = doAddToLibrary($request['username'], $request['game_id']);
		echo "replying: " . json_encode($result) . PHP_EOL;
		return $result;
		break;
	case 'get_library':
		if (!isset($request['username'])){
			return ['success' => false, 'message' => 'Missing username'];
		}
		$result = doGetLibrary($request['username']);
		echo "replying: " . json_encode($result) .PHP_EOL;
		return $result;
		break;
	case 'add_review':
		$result = doAddReview($request['user_name'], $request['game_id'], $request['rating'], $request['review_text'], $request['game_name'], $request['genre']);
		echo "replying: " . json_encode($result) . PHP_EOL;
		return $result;
		break;
	case 'get_review':
		$result = doGetReview($request['game_id']);
		echo "replying: " . json_encode($result) . PHP_EOL;
		return $result;
		break;
	case 'get_rec':
		echo "this shit better work";
		$result = doGetRecommendations($request['genre']);
		echo "replying: " . json_encode($result) . PHP_EOL;
		return $result;
		break;
	case 'mfa': //added this case for mfa
		$result = SendMFACode($request['username']);
		echo "replying: ". json_encode(result . PHP_EOL);
		return $result;
		break;
	default:
		echo "Unknown Request Type";
		return ['success' => false, 'message' => 'Unknown request type'];

		break;		
    }		
    return ['success' => false, 'message' => 'Unknown request type'];

    
}
$server = new rabbitMQServer('testRabbitMQ.ini', 'testServer');

echo "database listener started, waiting for requests..." . PHP_EOL;
$server->process_requests('requestProcessor');
exit();
?>
