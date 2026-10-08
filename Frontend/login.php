<?php

require_once('/var/www/rabbitmq/path.inc');
require_once('/var/www/rabbitmq/get_host_info.inc');
require_once('/var/www/rabbitmq/rabbitMQLib.inc');

header("Content-Type: application/json");

/*
 * Only accept POST requests.
 */
if ($_SERVER["REQUEST_METHOD"] !== "POST")
{
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);
    exit;
}

/*
 * Make sure username and password were provided.
 */
if (!isset($_POST["uname"]) || !isset($_POST["pword"]))
{
    echo json_encode([
        "success" => false,
        "message" => "Username and password are required"
    ]);
    exit;
}

$username = $_POST["uname"];
$password = $_POST["pword"];

try
{
    /*
     * Connect to your existing RabbitMQ configuration.
     */
    $client = new rabbitMQClient(
        "/var/www/rabbitmq/testRabbitMQ.ini",
        "testServer"
    );

    /*
     * Build the login request.
     * This matches your existing RabbitMQ client.
     */
    $request = array();

    $request["type"] = "Login";
    $request["username"] = $username;
    $request["password"] = $password;

    /*
     * Send the request through RabbitMQ.
     */
    $response = $client->send_request($request);

    /*
     * Send the RabbitMQ response back to the browser.
     */
    echo json_encode($response);
}
catch (Exception $e)
{
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to contact authentication service"
    ]);
}

exit;
?>
