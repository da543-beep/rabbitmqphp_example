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
 * Make sure game name or genre were provided. Subject to change
 */
/*
if (!isset($_POST["game_name"]) || (!isset($_POST["genre"])))
{
    echo json_encode([
        "success" => false,
        "message" => "Username and password are required"
    ]);
    exit;
}
 */
$username = $_POST["username"]; //subject to change
//$genre = $_POST["genre"]; // subject to change

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

    $request["type"] = "get_library";
    $request["username"] = $username;
    //$request["genre"] = $genre;
    //$request["platform"] = $platform;

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
