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
	limit 100;";

$url = curl_init("https://api.igdb.com/v4/games");

curl_setopt_array($url, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $query, CURLOPT_HTTPHEADER => ["Client-ID: $clientID", "Authorization: $authorization", "Content-Type: text/plain"], CURLOPT_RETURNTRANSFER => true]);

$apiResponse = curl_exec($url);
curl_close($url);

$games = json_decode($apiResponse, true);

$formattedGames = [];

foreach($games as $game)
{
	$formattedGames[] = [
		"id" => $game["id"],
		"name" => $game["name"],
		"genres" => implode(", ", array_column($game["genres"] ?? [], "name")),
		"platforms" => implode(", ", array_column($game["platforms"] ?? [], "name")),
		"release_dates" => implode(", ", array_column($game["release_dates"] ?? [], "human")),
		"cover" => isset($game["cover"]["url"]) ? "https:" . $game["cover"]["url"] : null
	];
}

echo json_encode($formattedGames, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  

$client = new rabbitMQClient(__DIR__ . '/../testRabbitMQ.ini', "testServer");

$request = [];

$request["type"] = "storeGames";
$request["games"] = json_encode($formattedGames, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$response = $client->send_request($request);
?>
