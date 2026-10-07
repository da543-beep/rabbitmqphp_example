#!/usr/bin/php
<?php
// databaselistener.php
// Runs on the database VM. Waits for requests from RabbitMQ, talks to MySQL,
// and returns an array that the library sends back to the requester.
//
// Start it with:  php databaselistener.php
// Needs in the same folder: path.inc, get_host_info.inc, rabbitMQLib.inc,
//                           testRabbitMQ.ini (broker settings), db.ini (MySQL settings)

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

// Make mysqli throw exceptions on every PHP version, so errors are caught below.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/**
 * Open a MySQL connection using the settings in db.ini.
 * Returns a mysqli object, or null if the connection fails.
 */
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

/**
 * Register a new user.
 * Returns ['success' => true] or ['success' => false, 'message' => '...'].
 */
function doRegistration($username, $email, $password)
{
    // 1. Validate the input before touching the database.
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

    // 2. Connect to MySQL.
    $db = getDb();
    if ($db === null) {
	    return ['success' => false, 'message' => 'Database unavailable'];
	
    }


    // 4. Insert with a prepared statement (protects against SQL injection).
    try {
        $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $username, $email, $password);
        $stmt->execute();
        $stmt->close();
        $db->close();

	echo "Registered user: " . $username . PHP_EOL;   // never print the password
	//send_request('Message Success!');
        return ['success' => true];
    } catch (mysqli_sql_exception $e) {
        $db->close();

        // 1062 = duplicate entry (username or email already in the table)
        if ($e->getCode() == 1062) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        echo "Registration error: " . $e->getMessage() . PHP_EOL;
        return ['success' => false, 'message' => 'Registration failed'];
    } 
}

/*function doLogin($username, $password){

}*/
/**
 * Called by the RabbitMQ library for every message that arrives.
 * Whatever this returns is sent back to the requester as the reply.
 */
function requestProcessor($request)
{
    echo "received request of type: " . ($request['type'] ?? 'none') . PHP_EOL;

    if (!isset($request['type'])) {
        return ['success' => false, 'message' => 'Missing request type'];
    }

    switch (strtolower($request['type'])) {
   	 case 'registration':
            if (!isset($request['username'], $request['email'], $request['password'])) {
                return ['success' => false, 'message' => 'Missing registration fields'];
            }
            $result = doRegistration($request['username'], $request['email'], $request['password']);

	    echo "replying: " . json_encode($result) . PHP_EOL;
	    return $result;
	    
        // Add more cases here as you build them, for example:
        // case 'login': return doLogin($request['username'], $request['password']);
    }
/*case 'login':
	if (!isset($request['username'], $request['password'])) {
		return ['success' => false, 'message' => 'Missing login fields'];
	}*/
	//need a doLogin() function
    return ['success' => false, 'message' => 'Unknown request type'];
}

// Connect to RabbitMQ using the [testServer] section of testRabbitMQ.ini
// (the front end's client must use the same section / exchange / queue).
$server = new rabbitMQServer('testRabbitMQ.ini', 'databaseServer');

echo "database listener started, waiting for requests..." . PHP_EOL;
$server->process_requests('requestProcessor');
echo "database listener stopped" . PHP_EOL;
exit();
/*
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST")
{
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "POST required"
    ]);

    exit;
}

$username = $_POST["username"] ?? "";
$password = $_POST["password"] ?? "";
$email    = $_POST["email"] ?? "";

if ($username === "" || $password === "" || $email === "")
{
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Missing registration data"
    ]);

    exit;
}

/*
 * Connect to MySQL locally.
 *
 * This is the ONLY file that should know
 * the database credentials.
 
$db = new mysqli(
    "127.0.0.1",
    "mv466",
    "burnttoast",
    "users"
);

if ($db->connect_errno)
{
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]);

    exit;
}

/*
 * Hash the password before storing it.
 
$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$stmt = $db->prepare(
    "INSERT INTO users (username, password, email)
     VALUES (?, ?, ?)"
);

$stmt->bind_param(
    "sss",
    $username,
    $passwordHash,
    $email
);

if ($stmt->execute())
{
    echo json_encode([
        "success" => true,
        "message" => "User registered successfully"
    ]);
}
else
{
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Database insert failed"
    ]);
}

$stmt->close();
$db->close();


/*
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function getDB(){
	$cfg = parse_ini_file(__DIR__ . '/db.ini', true)['database'];
function doRegistration($username, $email, $password)



{


  //Connect to the MySql DB


  $mydb = new mysqli(

      "100.95.75.38",
      "mv466", 
      "burnttoast",
      "IT490"

   );

   //Going to check iof the DB failed


    if ($mydb->connect_errno != 0)
    {
        echo "Failed to connect to database: ". $mydb->connect_error . PHP_EOL;
        return false;
    }


    //Prepare an INSERT statement for a new account
    $query = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";

    $stmt = $mydb->prepare($query);

    //Put the username and password into the query

    // ss means both values are string

    $stmt->bind_param('sss', $username, $email, $password);

    // attempt to make an account

   if ($stmt->execute()){

      echo "User registered" . PHP_EOL;

      $stmt->close();

      $mydb->close();

      return true;

   }

   echo "Register failed" . PHP_EOL;

   $stmt->close();


   $mydb->close();


   return false;


}
 */
?>
