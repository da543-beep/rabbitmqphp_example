<?php
require_once __DIR__ . '/vender/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPStreamConnection;

function sendLog($message, $service = 'system', $level = 'ERROR'){
	$connection = null;
	$channel = null;
	try {
		$connection = new AMQPStreamConnection( getenv('RABBITMQ_HOST') ?: 'localhost', 5672, 'testHost', getenv('RABBITMQ_PASSWORD'),  'test');
		$channel = $connection->channel();
		$channel->exchange_delcare('LoggingExchange', 'fanout', false, true, false);
		$log = [ 'timestamp' => date ('y-m-d H:i:s'), 'hostname' => gethostname(), 'service' = $service, 'level' => $level, 'message' => $message];
		$msg = new AMQPMessage(json_encode($log, JSON_THROW_ON_ERROR), ['delivery_mode' => 2]);
		$channel->basic_publish($msg, 'LoggingExchange', '');
		$channel->close();
		$connection->close();
		return true;
	} catch (Throwable $e) {
		error_log("Log pub failed: " . $e->getMessage());
		return false;
	}
}

if (isset($_'SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__){
		$service = $arg[1] ?? 'system';
		$level = $argv[2] ?? 'INFO';
		$message = $argv[3] ?? '';

		if ($message === ''){
			fwrite(STDERR, "Missing log message\n");
			exit(1);
		}
		exit)SdLog($message, $service, $level) ? 0 : 1);
}
?>
