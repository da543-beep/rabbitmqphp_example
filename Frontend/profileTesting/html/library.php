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
 * Make sure Game name and platform provided.
 */
if (!isset($_POST["game_name"]) || !isset($_POST["platForm"]))
{
    echo json_encode([
        "success" => false,
        "message" => "Game name and platform are required"
    ]);
    exit;
}

$game_name = $_POST["game_name"];
$platForm = $_POST["platForm"];

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

    $request["type"] = "addOwnedGame";
    $request["game_name"] = $username;
    $request["platForm"] = $password;

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
