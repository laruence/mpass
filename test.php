<?php
require ('./Server.php');

class TestPserver implements Mpass_IExecutor {
	function execute(Mpass_Request $client) {

		$input = "";

		$input = $client->read(1024);
		sleep(10);

		$str = "Hello World! " . microtime(true)
			            . "<pre>{$input}</pre>";

		$response = "HTTP/1.1 200 OK\r\n"
			. "Connection: close\r\n"
			. "Content-Type: text/html\r\n"
			. "Content-Length:" . strlen($str) . "\r\n"
			. "\r\n"
			. $str;

		$client->write($response);
		return TRUE;
	}
}

$host = "10.81.7.33";
$port = 8991;

$service = new Mpass_Server($host, $port, new TestPserver);

$service->run();
