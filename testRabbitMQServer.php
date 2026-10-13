#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
//I made comments for my group to understand what I did -Damien Alicea
function doLogin($username,$password)
{
    $request = array();
    $request['type'] = 'Login';
    $request['username'] = $username;
    $request['password'] = $password;
    echo "Sending login attemt to db listsener";

    //connect to group m8 listener
    $client = new rabbitMQClient("testRabbitMQ.ini", "databaseServer");
    //Send login request
    $response = $client->send_request($request);
    echo "DB listener responded to login" . PHP_EOL;
    var_dump($response);
    return $response;
}

function doLogout($session_key, $username)
{
     $request = array();
     $request['type'] = "logout";
     $request['session_key'] = $session_key;
     $request['username'] = $username;
     $client = new rabbitMQClient("testRabbitMQ.ini", "databaseServer");

     echo "Sending logout request to DB listener" . PHP_EOL;
     $response = $client->send_request($request);
     echo "DB listener responded to logout" .PHP_EOL;
     var_dump($response);
     return $response;
}

function doRegistration($username, $email, $password)
{
//trying to build something to send to group m8's listner
 $request = array();
 $request['type'] = "Registration";
 $request['username'] = $username;
 $request['email'] = $email;
 $request['password'] = $password;
//This should allow mw to connect to his listner
 $client = new rabbitMQClient("testRabbitMQ.ini", "databaseServer");
 echo "Sending a registration attempt to listner" . PHP_EOL;
 //Sending a request and waiting for response
 $response = $client->send_request($request);
 echo "Database listner worked" . PHP_EOL;
 var_dump($response);
 return $response;
}

function doAPI($search)
{
   //Building request for DMZ listener
   $request = array();
   $request['type'] = "get_data";
   $request['search'] = $search;

   //Connect to dmz server through BIg MQ
   $client = new rabbitMQClient("testRabbitMQ.ini", "dmzServer");
   echo "Sending API request ti DMZ listener" .PHP_EOL;
   //Send request and wait for response 
   $response = $client->send_request($request);
   echo "DMZ listener responded to API request" . PHP_EOL;
   return $response;
}

function doValidate($session_key)
{
    // Build request to send to database listener
    $request = array();
    $request['type'] = "validate_session";
    $request['session_key'] = $session_key;
    // Connect to database listener through RabbitMQ
    $client = new rabbitMQClient("testRabbitMQ.ini",  "databaseServer");
    echo "Sending session validation to DB listener" . PHP_EOL;
    // Send session ID and wait for database response
    $response = $client->send_request($request);
    echo "DB listener responded to session validation" . PHP_EOL;
    var_dump($response);
    // Send database response back toward frontend
    return $response;
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
	    $result = doLogin($request['username'],$request['password']);
	    return $result;
    case "logout":
	    $result = doLogout($request['session_key'], $request['username']);
	    return $result;
    case "Registration":
	    $result =  doRegistration($request['username'],$request['email'],$request['password']);
	    return $result;
	    var_dump($response);
    case "get_data":
	    $result = doAPI($request['search']);
	    return $result;
    case "validate_session":
            $result =  doValidate($request['session_key']);
	    return $result;
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

$server = new rabbitMQServer("testRabbitMQ.ini","testServer");

$server->process_requests('requestProcessor');
exit();
