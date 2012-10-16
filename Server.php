<?php
/*
----------------------------------------------------
Mpass - Multi-Process Socket Server for PHP

copyright (c) 2010 Laruence
http://www.laruence.com

If you have any questions or comments, please email:
laruence@yahoo.com.cn

*/

define("MPASS_VERSION", "0.1.0");

define("MPASS_DEBUG", TRUE);

/** EOF flag */
define("MPASS_EOF", "\r\n\r\n");

define("MPASS_ROOT", dirname(__FILE__));

require_once(MPASS_ROOT . "/Request.php");
require_once(MPASS_ROOT . "/Worker.php");

require_once(MPASS_ROOT . "/Log.php");

/**
 * Mpass Server Class
 *
 * @package Mpass
 * @depend pecl pcntl
 */
class Mpass_Server {

	private $is_master  = TRUE;

	private $_host 		= NULL;
	private $_port 		= NULL;
	private $_socket	= NULL;
	private $_worker 	= NULL;

	private $_children 	= 0;
	private $_running	= FALSE;

	public function __construct($host = "127.0.0.1", $port = 9001, Mpass_IExecutor $executor) {
		if (!extension_loaded("pcntl")) {
			die("Mpass require pcntl extension loaded");
		}

		/** assure run in cli mode */
		if (substr(php_sapi_name(), 0, 3) !== 'cli') {
			die("This Programe can only be run in CLI mode");
		}

        if ($host) {
		    $this->_host = $host;
        }

        if ($port) {
            $this->_port = (int)($port);
        }

		$this->_worker = new Mpass_Worker($executor);
	}

	public function run() {
		/** no need actually */
		set_time_limit(0);

		$signals = array(
			SIGCHLD => "SIGCHLD",
			SIGCLD	=> "SIGCLD",
            SIGINT  => "SIGINT",
            SIGHUP  => "SIGHUP",
            SIGQUIT => "SIGQUIT",
		);

		if (version_compare(phpversion(), "5.3.0", "lt")) {
			/* tick use required as of PHP 4.3.0 */
			declare(ticks = 1);
		}

		foreach ($signals as $signal => $name) {
			if (!pcntl_signal($signal, array($this, "handler"))) {
				die("Install signal handler for {$name} failed");
			}
		}

		$context = stream_context_create();

		$dns    = "tcp://{$this->_host}:{$this->_port}";

		$server = stream_socket_server($dns, $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $context);

		if (FALSE === $server) {
			die($errstr);
        } else {
            Mpass_Log::log("start listening on {$dns}", __METHOD__);
        }

		$this->_socket  = $server;

		$this->_running = TRUE;

		while ($this->_running) {

			$client = @stream_socket_accept($server, 5);
		
			if (FALSE !== $client) {
				Mpass_Log::log("accepted connection from \"" . stream_socket_get_name($client, TRUE) . "\"", __METHOD__);
				$this->_execute($client);
			} 

			if (version_compare(phpversion(), "5.3.0", "ge")) {
				/** since php 5.3.0 declare statement is deprecated, 
				 *  use pcntl_signal_dispatch instead */
				pcntl_signal_dispatch();
			}
		}

		return TRUE;
	}

	public function handler($signo) {
		switch(intval($signo)) {
		case SIGCLD:
		case SIGCHLD:
            /** declare = 1, that means one signal may be correspond multi-process die */
            while( ($pid = pcntl_wait($status, WNOHANG|WUNTRACED)) > 0 ) {
                if (FALSE === pcntl_wifexited($status)) {
                    Mpass_Log::warn("sub proccess {$pid} exited unormally with code {$status}", __METHOD__);
                } else {
                    Mpass_Log::log("sub proccess {$pid} exited normally", __METHOD__);
                }
                $this->_children--;
            }
            break;
        case SIGINT:
        case SIGQUIT:
        case SIGHUP:
            $this->_cleanup();
            exit(0);
            break;
		default:
			break;
		}
	}

	protected function _execute($client) {

		$pid = pcntl_fork(); 

        if ($pid == 0) {
            /** this is a child */
			$this->is_master = FALSE;

			$this->_worker->run($client);

			exit(0);
		} else {
            ++$this->_children;
		}
		return TRUE;
	}

	protected function _cleanup() {
		if (!$this->is_master) {
			return;
		}

		Mpass_Log::log("cleanup", __METHOD__);

		$this->_running = FALSE;

        while ($this->_children > 0) {
			$pid = pcntl_wait($status, WNOHANG | WUNTRACED);
            if ($pid > 0) {
                if (FALSE === pcntl_wifexited($status)) {
                    Mpass_Log::warn("sub proccess {$pid} exited unormally with code {$status}", __METHOD__);
                } else {
                    Mpass_Log::log("sub proccess {$pid} exited normally", __METHOD__);
                }
                $this->_children--;
            } else {
                continue;
            }
		}

        if ($this->_socket) {
            if (version_compare(phpversion(), "5.2.1", "ge")) {
                stream_socket_shutdown($this->_socket, STREAM_SHUT_RDWR);
            } else {
                fclose($client);
            }
        }

	}

	public function __destruct() {
	}
}

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
