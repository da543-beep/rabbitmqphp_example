#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function doLogin($username,$password)
{
  //Connect to the MySql DB
  $mydb = new mysqli(
      "100.121.9.69",
      "testUser", 
      "12345",
      "testdb"
   );

   //Going to check iof the DB failed
    if ($mydb->connect_errno != 0)
    {
	echo "Failed to connect to database: ". $mydb->connect_error . PHP_EOL;
	return false;
    }

    // lookup username in databas
    // check password
   // return true;
    //return false if not valid
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
    case "login":
      return doLogin($request['username'],$request['password']);
    case "validate_session":
      return doValidate($request['sessionId']);
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

$server = new rabbitMQServer("testRabbitMQ.ini","testServer");

$server->process_requests('requestProcessor');
exit();
?>

