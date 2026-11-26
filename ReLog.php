<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
$queueName = getenv('LOG_QUEUE');
if (!$queueName){
	die("Queue required\n");
}
$connection = new AMQPStreamConnection(getenv('RABBITMQ_HOST') ?: 'localhost', 5672, 'test', getenv('RABBITMQ_PASSWORD'), 'tesHost');
$channel = $connection->channel();
$channel->exhchange_declare('LoggingExchange', 'fanout', false, true, false);
$channel->queue_declare($queueName, false, true, false, false);
$channel->queue_bind($queueName, 'LoggingExchange');
$logDir = __DIR__ . '/logs';
if(!is_dir($logDir)){
	mkdir($logDir, 0750, true);
}
$logFile = $logDir . '/recieved-events.log';
$callback = function ($msg) use ($logFile) {
	try{
		$data = json_decode($msg->body, true, 512, JSON_THROW_ON_ERROR);
		if (!is_array($data)){
			throw new RuntimeException("Invalid log data");
		}
		$line = sprintf("[%s] [%s] [%s] [%s] %s\n", $data['timestamp'] ?? '', $data['hostname'] ?? '', $data['service'] ?? '',
			$data['level'] ?? '', str_replace(["\r", "\n"], ' ', (string)($data['message'] ?? '')));
		if (file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX) === false){
			throw new RuntimeException("Log write failed");
		}

		$msg->ack();
	} catch (Throwable $e){
		error_log($e->getMessage());
		$msg->nack(false, true);
		sleep(2);
	}
}

$channel->basic_qos(null, 1, null);
$channel->basic_consume($queueName, '', false, false, false, false, $callback);
while ($channel->is_consuming()){
	$channel->wait();
}
 
?>
