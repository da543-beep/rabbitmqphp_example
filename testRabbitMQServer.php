#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

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
 return $response
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
	    $result =  doRegistration($request['username'],$request['email'],$request['password']);
	    return $result;
	    var_dump($response);
    case "validate_session":
      return doValidate($request['sessionId']);
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

$server = new rabbitMQServer("testRabbitMQ.ini","testServer");

$server->process_requests('requestProcessor');
exit();
>
