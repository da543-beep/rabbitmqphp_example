#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function doLogin($username,$password)
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
    // This should be able to search through the users table
    $query = "SELECT password FROM users WHERE username = ?";
    //Hopefully this will prepar the SQL query
    $stmt = $mydb->prepare($query);
    //Attaches the username to the message in the query
    $stmt->bind_param("s", $username);
    //Execute the query
    $stmt->execute();
    //Get the result from MYsql
    $result = $stmt->get_result();
    //See if it exists
    if ($result->num_rows == 1){
        //Get the user's info
        $row = $result->fetch_assoc();
       // Going to test this
       // This will compare the entered password with the stored password
       if ($password === $row['password']){
           $stmt->close();
           $mydb->close();
           //if both user and password are correct
           return true;
       }
    }
    //close everthing if login fails
    $stmt->close();
    $mydb->close();
    //incorrect username or password
    return false;
    // lookup username in databas
    // check password
   // return true;
    //return false if not valid
}

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

function requestProcessor($request)
{
  echo "received request".PHP_EOL;
  var_dump($request);
  if(!isset($request['type']))
  {
    return "ERROR: unsupported message type";
  }
  switch ($request['type'])
  {
    case "Login":
      return doLogin($request['username'],$request['password']);
    case "Registration":
      return doRegistration($request['username'],$request['password'],$request['email']);
    case "validate_session":
      return doValidate($request['sessionId']);
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

$server = new rabbitMQServer("testRabbitMQ.ini","testServer");

$server->process_requests('requestProcessor');
exit();
?>

