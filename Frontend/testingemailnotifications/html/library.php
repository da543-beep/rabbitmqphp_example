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
 * Make sure username, password, and email were provided.
 */
if (!isset($_POST["username"]) || !isset($_POST["game_id"]) )
{
    echo json_encode([
        "success" => false,
        "message" => "Game name and platform are required"
    ]);
    exit;
}

$username = $_POST["username"];
$game_id = $_POST["game_id"];

try
{
    /*
     * Connect to existing RabbitMQ configuration.
     */
    $client = new rabbitMQClient(
        "/var/www/rabbitmq/testRabbitMQ.ini",
        "testServer"
    );

    /*
     * Build the registration request.
     * This matches existing RabbitMQ client.
     */
    $request = array();

    $request["type"] = "add_to_library";
    $request["username"] = $username;
    $request["game_id"] = $game_id;
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
