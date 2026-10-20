#!/bin/php
<?php

require_once(__DIR__ . '/../path.inc');
require_once(__DIR__ . '/../get_host_info.inc');
require_once(__DIR__ . '/../rabbitMQLib.inc');

$clientID = getenv("API_CLIENT_ID");
$authorization = getenv("API_Authorization");

$query = "
	fields
	name,
	genres.name,
	platforms.name,
	release_dates.human,
	cover.url;

	sort name desc;
	limit 10;";

$url = curl_init("https://api.igdb.com/v4/games");

curl_setopt_array($url, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $query, CURLOPT_HTTPHEADER => ["Client-ID: $clientID", "Authorization: $authorization", "Content-Type: text/plain"], CURLOPT_RETURNTRANSFER => true]);

$apiResponse = curl_exec($url);
curl_close($url);

$games = json_decode($apiResponse, true);

$formattedGames = json_encode($games, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

echo $formattedGames;

$client = new rabbitMQClient(__DIR__ . '/../testRabbitMQ.ini', "testServer");

$request = [];

$request["type"] = "API Response";
$request["games"] = $formattedGames;

$response = $client->send_request($request);
?>
